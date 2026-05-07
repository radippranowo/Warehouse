<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMutasiRequest;
use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\Gudang;
use App\Models\StokMutasi;
use App\Models\StokMutasiItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MutasiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));
        $tipe    = $request->input('tipe', '');
        $gudang  = $request->input('gudang_id', '');
        $status  = $request->input('status', '');

        $query = StokMutasi::query()
            ->with([
                'gudang:id,kode_gudang,nama_gudang',
                'gudangTujuan:id,kode_gudang,nama_gudang',
                'user:id,name',
                'approver:id,name',
                'items:id,stok_mutasi_id,barang_id,qty,harga_satuan,stok_sebelum,stok_sesudah,keterangan',
                'items.barang:id,kode_barang,nama_barang,satuan',
            ])
            ->withCount('items');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $prefix = $search . '%';
            $query->where(function ($q) use ($search, $prefix) {
                $q->where('nomor_mutasi', 'like', $prefix)
                  ->orWhere('referensi', 'like', $prefix)
                  ->orWhereHas('items.barang', function ($qq) use ($search, $prefix) {
                      $qq->where('kode_barang', 'like', $prefix);
                      if (mb_strlen($search) >= 3) {
                          $qq->orWhereFullText('nama_barang', $search);
                      } else {
                          $qq->orWhere('nama_barang', 'like', $prefix);
                      }
                  });
            });
        }

        if (in_array($tipe, ['in', 'out', 'transfer', 'adjust'], true)) {
            $query->where('tipe', $tipe);
        }
        if ($gudang) {
            $query->where(function ($q) use ($gudang) {
                $q->where('gudang_id', $gudang)->orWhere('gudang_tujuan_id', $gudang);
            });
        }

        $mutasis = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Mutasi/Index', [
            'mutasis' => $mutasis,
            'filters' => [
                'search'    => $search,
                'perPage'   => $perPage,
                'tipe'      => $tipe,
                'gudang_id' => $gudang,
                'status'    => $status,
            ],
            // Closure → cuma di-evaluate kalau 'gudangs' diminta via `only` (full
            // visit awal). Partial reload (search/filter/paginate) skip total.
            'gudangs' => fn () => Gudang::select('id', 'kode_gudang', 'nama_gudang')->orderBy('nama_gudang')->get(),
        ]);
    }

    public function show(StokMutasi $mutasi)
    {
        $mutasi->load([
            'gudang:id,kode_gudang,nama_gudang',
            'gudangTujuan:id,kode_gudang,nama_gudang',
            'user:id,name',
            'items.barang:id,kode_barang,nama_barang,satuan',
        ]);
        return response()->json($mutasi);
    }

    public function print(StokMutasi $mutasi)
    {
        $mutasi->load([
            'gudang:id,kode_gudang,nama_gudang,alamat',
            'gudangTujuan:id,kode_gudang,nama_gudang,alamat',
            'user:id,name',
            'items.barang:id,kode_barang,nama_barang,satuan',
        ]);
        return view('mutasi.print', ['mutasi' => $mutasi]);
    }

    public function create()
    {
        return Inertia::render('Mutasi/Create', [
            'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                ->where('is_active', true)
                ->orderBy('nama_barang')
                ->get(),
            'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                ->where('is_active', true)
                ->orderBy('nama_gudang')
                ->get(),
        ]);
    }

    public function store(StoreMutasiRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $tipe   = $data['tipe'];
            $gId    = (int) $data['gudang_id'];
            $tujuan = $data['gudang_tujuan_id'] ?? null;

            $isTransfer = $tipe === 'transfer';
            $header = StokMutasi::create([
                'nomor_mutasi'     => $this->generateNomor(),
                'tanggal'          => $data['tanggal'],
                'tipe'             => $tipe,
                'gudang_id'        => $gId,
                'gudang_tujuan_id' => $tujuan,
                'referensi'        => $data['referensi'] ?? null,
                'keterangan'       => $data['keterangan'] ?? null,
                'user_id'          => $request->user()?->id,
                'total_qty'        => 0,
                'total_value'      => 0,
                'status'           => $isTransfer ? 'pending' : 'approved',
                'approved_by'      => $isTransfer ? null : $request->user()?->id,
                'approved_at'      => $isTransfer ? null : now(),
            ]);

            $totalQty   = 0;
            $totalValue = 0;

            foreach ($data['items'] as $i => $row) {
                $bId = (int) $row['barang_id'];
                $qty = (int) $row['qty'];
                $harga = $row['harga_satuan'] ?? null;

                $source = BarangStok::lockForUpdate()
                    ->firstOrCreate(['barang_id' => $bId, 'gudang_id' => $gId], ['stok' => 0]);

                $stokSebelum = (int) $source->stok;
                $stokSesudah = $stokSebelum;
                $itemKet = $row['keterangan'] ?? null;

                if ($tipe === 'in') {
                    $source->increment('stok', $qty);
                    $stokSesudah = $stokSebelum + $qty;
                } elseif ($tipe === 'out') {
                    if ($stokSebelum < $qty) {
                        throw ValidationException::withMessages([
                            "items.$i.qty" => "Stok tidak cukup. Tersedia: {$stokSebelum}",
                        ]);
                    }
                    $source->decrement('stok', $qty);
                    $stokSesudah = $stokSebelum - $qty;
                } elseif ($tipe === 'transfer') {
                    if ($stokSebelum < $qty) {
                        throw ValidationException::withMessages([
                            "items.$i.qty" => "Stok asal tidak cukup. Tersedia: {$stokSebelum}",
                        ]);
                    }
                    // In-transit: kurangi dari gudang asal sekarang, baru tambah ke
                    // gudang tujuan setelah penerima approve.
                    $source->decrement('stok', $qty);
                    $stokSesudah = $stokSebelum - $qty;
                } elseif ($tipe === 'adjust') {
                    $source->update(['stok' => $qty]);
                    $stokSesudah = $qty;
                    $itemKet = trim(($itemKet ? $itemKet . ' | ' : '')
                        . "Penyesuaian: {$stokSebelum} → {$qty}");
                }

                StokMutasiItem::create([
                    'stok_mutasi_id' => $header->id,
                    'barang_id'      => $bId,
                    'qty'            => $qty,
                    'harga_satuan'   => $harga,
                    'stok_sebelum'   => $stokSebelum,
                    'stok_sesudah'   => $stokSesudah,
                    'keterangan'     => $itemKet,
                ]);

                $totalQty   += $qty;
                $totalValue += $qty * (float) ($harga ?? 0);
            }

            $header->update([
                'total_qty'   => $totalQty,
                'total_value' => $totalValue,
            ]);
        });

        // Invalidate cached summary for affected warehouses (StokController).
        Cache::forget("stok.summary.{$data['gudang_id']}");
        if (!empty($data['gudang_tujuan_id'])) {
            Cache::forget("stok.summary.{$data['gudang_tujuan_id']}");
        }

        return redirect('/mutasi')->with('success', 'Mutasi tersimpan');
    }

    public function approve(Request $request, StokMutasi $mutasi)
    {
        if ($mutasi->tipe !== 'transfer') {
            return back()->withErrors(['mutasi' => 'Hanya tipe transfer yang perlu approval.']);
        }

        DB::transaction(function () use ($mutasi, $request) {
            // Lock row mutasi & re-check status agar aman dari double-approve
            // (TOCTOU pada dua request bersamaan).
            $locked = StokMutasi::whereKey($mutasi->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'mutasi' => "Mutasi sudah {$locked->status}.",
                ]);
            }

            $tujuan = $locked->gudang_tujuan_id;
            foreach ($locked->items()->get() as $item) {
                $dest = BarangStok::lockForUpdate()
                    ->firstOrCreate(
                        ['barang_id' => $item->barang_id, 'gudang_id' => $tujuan],
                        ['stok' => 0]
                    );
                $dest->increment('stok', $item->qty);
            }

            $locked->update([
                'status'      => 'approved',
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
            ]);
        });

        Cache::forget("stok.summary.{$mutasi->gudang_tujuan_id}");

        return back()->with('success', "Transfer {$mutasi->nomor_mutasi} disetujui — barang masuk ke gudang tujuan.");
    }

    public function reject(Request $request, StokMutasi $mutasi)
    {
        if ($mutasi->tipe !== 'transfer') {
            return back()->withErrors(['mutasi' => 'Hanya tipe transfer yang bisa di-reject.']);
        }

        $reason = trim((string) $request->input('rejection_reason', ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($mutasi, $request, $reason) {
            $locked = StokMutasi::whereKey($mutasi->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'mutasi' => "Mutasi sudah {$locked->status}.",
                ]);
            }

            // Kembalikan stok ke gudang asal (rollback in-transit).
            $asal = $locked->gudang_id;
            foreach ($locked->items()->get() as $item) {
                $src = BarangStok::lockForUpdate()
                    ->firstOrCreate(
                        ['barang_id' => $item->barang_id, 'gudang_id' => $asal],
                        ['stok' => 0]
                    );
                $src->increment('stok', $item->qty);
            }

            $locked->update([
                'status'           => 'rejected',
                'approved_by'      => $request->user()?->id,
                'approved_at'      => now(),
                'rejection_reason' => $reason,
            ]);
        });

        Cache::forget("stok.summary.{$mutasi->gudang_id}");

        return back()->with('success', "Transfer {$mutasi->nomor_mutasi} ditolak — stok dikembalikan ke gudang asal.");
    }

    private function generateNomor(): string
    {
        $prefix = 'MUT-' . now()->format('Ymd') . '-';
        $last = StokMutasi::where('nomor_mutasi', 'like', $prefix . '%')
            ->orderByDesc('nomor_mutasi')
            ->value('nomor_mutasi');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
