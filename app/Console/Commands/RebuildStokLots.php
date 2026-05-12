<?php

namespace App\Console\Commands;

use App\Models\StokLot;
use App\Models\StokLotConsumption;
use App\Models\StokMutasi;
use App\Services\StokLotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildStokLots extends Command
{
    protected $signature = 'stok:rebuild-lots {--dry-run : Tampilkan ringkasan tanpa menulis ke DB}';

    protected $description = 'Rebuild stok_lots & stok_lot_consumptions dari riwayat mutasi (FIFO backfill). Idempotent.';

    public function handle(StokLotService $lots): int
    {
        $dry = $this->option('dry-run');

        $this->info($dry ? 'DRY RUN — tidak ada perubahan akan disimpan' : 'Memulai rebuild lots…');

        DB::beginTransaction();
        try {
            // Hapus data lama. Pakai DELETE (bukan TRUNCATE) supaya tetap di
            // dalam transaksi — TRUNCATE adalah DDL yang implicit-commit di MySQL.
            $this->line('  • Menghapus lots & consumptions lama…');
            DB::table('stok_lot_consumptions')->delete();
            DB::table('stok_lots')->delete();

            // Replay semua mutasi "aktif" (approved + transfer pending), belum cancelled.
            // Pending transfer ikut karena stok asal sudah berkurang saat store() —
            // FIFO harus mencerminkan itu lewat consumption records.
            $mutasis = StokMutasi::where(function ($q) {
                    $q->where('status', 'approved')
                      ->orWhere(function ($qq) {
                          $qq->where('tipe', 'transfer')->where('status', 'pending');
                      });
                })
                ->whereNull('cancelled_at')
                ->with(['items.barang:id,harga_beli,nama_barang'])
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();

            $this->line("  • Memproses {$mutasis->count()} mutasi…");
            $bar = $this->output->createProgressBar($mutasis->count());
            $bar->start();

            $stats = ['in' => 0, 'out' => 0, 'transfer' => 0, 'adjust' => 0];

            foreach ($mutasis as $mutasi) {
                foreach ($mutasi->items as $item) {
                    $bId = $item->barang_id;
                    $hargaBeliMaster = (float) ($item->barang->harga_beli ?? 0);

                    if ($mutasi->tipe === 'in') {
                        $hargaBeli = (float) ($item->harga_satuan ?? $hargaBeliMaster);
                        $lots->createLot(
                            barangId: $bId,
                            gudangId: $mutasi->gudang_id,
                            qty: $item->qty,
                            hargaBeli: $hargaBeli,
                            stokMutasiId: $mutasi->id,
                            stokMutasiItemId: $item->id,
                            tanggal: $mutasi->tanggal,
                            supplierId: $mutasi->supplier_id,
                        );
                        $stats['in']++;
                    } elseif ($mutasi->tipe === 'out') {
                        $lots->consumeFifo($bId, $mutasi->gudang_id, $item->qty, $item->id);
                        $stats['out']++;
                    } elseif ($mutasi->tipe === 'transfer') {
                        // Konsumsi dari asal (selalu, baik pending maupun approved)
                        $lots->consumeFifo($bId, $mutasi->gudang_id, $item->qty, $item->id);
                        // Bangun ulang lots di tujuan HANYA kalau sudah approved.
                        // Pending: stok belum sampai di tujuan, jadi tidak ada lot.
                        if ($mutasi->status === 'approved') {
                            $consumptions = StokLotConsumption::where('stok_mutasi_item_id', $item->id)
                                ->orderBy('id')
                                ->get();
                            foreach ($consumptions as $c) {
                                $lots->createLot(
                                    barangId: $bId,
                                    gudangId: $mutasi->gudang_tujuan_id,
                                    qty: $c->qty,
                                    hargaBeli: (float) $c->harga_beli,
                                    stokMutasiId: $mutasi->id,
                                    stokMutasiItemId: $item->id,
                                    tanggal: $mutasi->tanggal,
                                    supplierId: null,
                                );
                            }
                        }
                        $stats['transfer']++;
                    } elseif ($mutasi->tipe === 'adjust') {
                        $delta = $item->qty - $item->stok_sebelum;
                        if ($delta > 0) {
                            $hargaBeli = (float) ($item->harga_satuan ?? $hargaBeliMaster);
                            $lots->createLot(
                                barangId: $bId,
                                gudangId: $mutasi->gudang_id,
                                qty: $delta,
                                hargaBeli: $hargaBeli,
                                stokMutasiId: $mutasi->id,
                                stokMutasiItemId: $item->id,
                                tanggal: $mutasi->tanggal,
                                supplierId: null,
                            );
                        } elseif ($delta < 0) {
                            $lots->consumeFifo($bId, $mutasi->gudang_id, abs($delta), $item->id);
                        }
                        $stats['adjust']++;
                    }
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->table(
                ['Tipe', 'Jumlah item'],
                collect($stats)->map(fn ($v, $k) => [$k, $v])->values()->all()
            );

            $totalLots = StokLot::count();
            $totalConsumptions = StokLotConsumption::count();
            $this->info("Total lots dibuat: {$totalLots}");
            $this->info("Total consumption records: {$totalConsumptions}");

            if ($dry) {
                DB::rollBack();
                $this->warn('DRY RUN — perubahan di-rollback.');
            } else {
                DB::commit();
                $this->info('✓ Rebuild selesai.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
