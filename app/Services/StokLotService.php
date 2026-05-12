<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\StokLot;
use App\Models\StokLotConsumption;
use App\Models\StokMutasiItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FIFO lot accounting untuk persediaan.
 *
 * - barang masuk / adjust positif / transfer-in => createLot()
 * - barang keluar / adjust negatif / transfer-out => consumeFifo()
 * - cancel/reject => restoreConsumption() & deleteLotsForItem()
 *
 * Caller wajib menjalankan ini di dalam DB::transaction.
 */
class StokLotService
{
    /**
     * Buat satu lot baru. Dipakai untuk barang masuk, adjust positif, dan
     * sisi penerima transfer (saat approve).
     */
    public function createLot(
        int $barangId,
        int $gudangId,
        int $qty,
        float $hargaBeli,
        int $stokMutasiId,
        int $stokMutasiItemId,
        string $tanggal,
        ?int $supplierId = null,
    ): StokLot {
        return StokLot::create([
            'barang_id'           => $barangId,
            'gudang_id'           => $gudangId,
            'supplier_id'         => $supplierId,
            'stok_mutasi_id'      => $stokMutasiId,
            'stok_mutasi_item_id' => $stokMutasiItemId,
            'tanggal'             => $tanggal,
            'qty_in'              => $qty,
            'qty_sisa'            => $qty,
            'harga_beli'          => $hargaBeli,
        ]);
    }

    /**
     * Konsumsi qty secara FIFO dari lots barang+gudang. Catat consumption
     * dengan harga beli yang di-lock dari lot. Return total modal (qty * harga).
     *
     * Throws ValidationException kalau lots tidak cukup (defense — kontroler
     * sudah cek BarangStok lebih dulu, ini guard tambahan).
     */
    public function consumeFifo(
        int $barangId,
        int $gudangId,
        int $qty,
        int $stokMutasiItemId,
    ): float {
        $sisa = $qty;
        $totalModal = 0.0;

        $lots = StokLot::where('barang_id', $barangId)
            ->where('gudang_id', $gudangId)
            ->where('qty_sisa', '>', 0)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($sisa <= 0) break;

            $ambil = min($lot->qty_sisa, $sisa);
            $hargaBeli = (float) $lot->harga_beli;

            StokLotConsumption::create([
                'stok_lot_id'         => $lot->id,
                'stok_mutasi_item_id' => $stokMutasiItemId,
                'qty'                 => $ambil,
                'harga_beli'          => $hargaBeli,
            ]);

            $lot->decrement('qty_sisa', $ambil);

            $totalModal += $ambil * $hargaBeli;
            $sisa -= $ambil;
        }

        if ($sisa > 0) {
            // Inconsistent state: BarangStok bilang cukup tapi lots tidak.
            // Bisa terjadi kalau ada legacy data sebelum FIFO aktif.
            // Fallback: pakai harga_beli master barang untuk sisa, dan buat
            // "virtual consumption" yang tidak terkait lot.
            $barang = Barang::find($barangId);
            $hargaBeli = (float) ($barang->harga_beli ?? 0);

            StokLotConsumption::create([
                'stok_lot_id'         => $this->ensureFallbackLot($barangId, $gudangId, $sisa, $hargaBeli, $stokMutasiItemId),
                'stok_mutasi_item_id' => $stokMutasiItemId,
                'qty'                 => $sisa,
                'harga_beli'          => $hargaBeli,
            ]);

            $totalModal += $sisa * $hargaBeli;
        }

        return $totalModal;
    }

    /**
     * Saat consumeFifo kekurangan lot (legacy data), buat shadow lot
     * dengan qty_sisa=0 supaya consumption tetap punya FK valid.
     */
    private function ensureFallbackLot(
        int $barangId,
        int $gudangId,
        int $qty,
        float $hargaBeli,
        int $stokMutasiItemId,
    ): int {
        $item = StokMutasiItem::find($stokMutasiItemId);
        $lot = StokLot::create([
            'barang_id'           => $barangId,
            'gudang_id'           => $gudangId,
            'supplier_id'         => null,
            'stok_mutasi_id'      => $item->stok_mutasi_id,
            'stok_mutasi_item_id' => $stokMutasiItemId,
            'tanggal'             => now(),
            'qty_in'              => $qty,
            'qty_sisa'            => 0,
            'harga_beli'          => $hargaBeli,
        ]);
        return $lot->id;
    }

    /**
     * Restore qty_sisa ke lot-lot yang dikonsumsi oleh item ini, lalu hapus
     * consumption record. Dipakai saat cancel barang keluar / reject transfer.
     */
    public function restoreConsumption(int $stokMutasiItemId): void
    {
        $consumptions = StokLotConsumption::where('stok_mutasi_item_id', $stokMutasiItemId)
            ->lockForUpdate()
            ->get();

        foreach ($consumptions as $c) {
            StokLot::whereKey($c->stok_lot_id)->lockForUpdate()->increment('qty_sisa', $c->qty);
            $c->delete();
        }
    }

    /**
     * Hapus lot-lot yang dibuat oleh item ini. Hanya boleh kalau lot belum
     * tersentuh (qty_sisa == qty_in). Dipakai saat cancel barang masuk / adjust positif.
     *
     * Throws ValidationException kalau ada lot yang sudah sebagian dipakai.
     */
    public function deleteLotsForItem(int $stokMutasiItemId, ?string $namaBarang = null): void
    {
        $lots = StokLot::where('stok_mutasi_item_id', $stokMutasiItemId)
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($lot->qty_sisa < $lot->qty_in) {
                $terpakai = $lot->qty_in - $lot->qty_sisa;
                $label = $namaBarang ? "\"{$namaBarang}\"" : "ID {$lot->barang_id}";
                throw ValidationException::withMessages([
                    'mutasi' => "Tidak dapat membatalkan: lot barang {$label} sudah terpakai {$terpakai} unit di transaksi keluar lain.",
                ]);
            }
            $lot->delete();
        }
    }
}
