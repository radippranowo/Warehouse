<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMutasiRequest;
use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\Gudang;
use App\Models\StokMutasi;
use App\Models\StokMutasiItem;
use App\Models\Supplier;
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
            'supplier:id,kode_supplier,nama_supplier',
            'user:id,name',
            'approver:id,name',
            'canceller:id,name',
            'items.barang:id,kode_barang,nama_barang,satuan',
        ]);
        
        return Inertia::render('Transaksi/Detail', [
            'mutasi' => $mutasi,
        ]);
    }

    public function print(StokMutasi $mutasi)
    {
        $mutasi->load([
            'gudang:id,kode_gudang,nama_gudang,alamat',
            'gudangTujuan:id,kode_gudang,nama_gudang,alamat',
            'supplier:id,kode_supplier,nama_supplier',
            'user:id,name',
            'approver:id,name',
            'canceller:id,name',
            'items.barang:id,kode_barang,nama_barang,satuan',
        ]);
        return view('mutasi.print', ['mutasi' => $mutasi]);
    }

    public function printList(Request $request)
    {
        $tipe = $request->input('tipe', 'in'); // in, out, transfer, adjust
        $gudangId = $request->input('gudang_id', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');
        $search = trim((string) $request->input('search', ''));

        $query = StokMutasi::query()
            ->with([
                'gudang:id,kode_gudang,nama_gudang',
                'gudangTujuan:id,kode_gudang,nama_gudang',
                'supplier:id,kode_supplier,nama_supplier',
                'user:id,name',
                'items.barang:id,kode_barang,nama_barang,satuan',
            ])
            ->where('tipe', $tipe)
            ->whereNull('cancelled_at')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($gudangId) {
            $query->where('gudang_id', $gudangId);
        }

        if ($dateFrom) {
            $query->whereDate('tanggal', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('tanggal', '<=', $dateTo);
        }

        if ($search !== '') {
            $prefix = $search . '%';
            $query->where(function ($q) use ($search, $prefix) {
                $q->where('nomor_mutasi', 'like', $prefix)
                  ->orWhere('referensi', 'like', $prefix);
            });
        }

        $mutasis = $query->get();
        $gudang = $gudangId ? Gudang::find($gudangId) : null;

        return view('mutasi.print-list', [
            'mutasis' => $mutasis,
            'tipe' => $tipe,
            'gudang' => $gudang,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function create()
    {
        $masters = Cache::remember('mutasi.masters', now()->addHour(), function () {
            return [
                'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get()
                    ->toArray(),
                'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get()
                    ->toArray(),
            ];
        });

        return Inertia::render('Mutasi/Create', $masters);
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

    public function destroy(Request $request, StokMutasi $mutasi)
    {
        // Cek apakah sudah dibatalkan
        if ($mutasi->cancelled_at) {
            return back()->with('error', 'Transaksi sudah dibatalkan sebelumnya');
        }

        $reason = trim((string) $request->input('cancellation_reason', ''));
        if ($reason === '') {
            $reason = 'Dibatalkan oleh user';
        }

        DB::transaction(function () use ($mutasi, $request, $reason) {
            // Lock mutasi
            $locked = StokMutasi::whereKey($mutasi->id)->lockForUpdate()->first();

            // Rollback stok berdasarkan tipe mutasi
            foreach ($locked->items as $item) {
                $barangId = $item->barang_id;
                
                if ($locked->tipe === 'in') {
                    // Pemasukan: kurangi stok
                    $stok = BarangStok::lockForUpdate()
                        ->where('barang_id', $barangId)
                        ->where('gudang_id', $locked->gudang_id)
                        ->first();
                    if ($stok) {
                        $stok->decrement('stok', $item->qty);
                    }
                } elseif ($locked->tipe === 'out') {
                    // Pengeluaran: kembalikan stok
                    $stok = BarangStok::lockForUpdate()
                        ->firstOrCreate(
                            ['barang_id' => $barangId, 'gudang_id' => $locked->gudang_id],
                            ['stok' => 0]
                        );
                    $stok->increment('stok', $item->qty);
                } elseif ($locked->tipe === 'transfer') {
                    // Transfer: kembalikan ke gudang asal jika sudah approved
                    if ($locked->status === 'approved') {
                        // Kurangi dari gudang tujuan
                        $stokTujuan = BarangStok::lockForUpdate()
                            ->where('barang_id', $barangId)
                            ->where('gudang_id', $locked->gudang_tujuan_id)
                            ->first();
                        if ($stokTujuan) {
                            $stokTujuan->decrement('stok', $item->qty);
                        }
                    }
                    // Kembalikan ke gudang asal
                    $stokAsal = BarangStok::lockForUpdate()
                        ->firstOrCreate(
                            ['barang_id' => $barangId, 'gudang_id' => $locked->gudang_id],
                            ['stok' => 0]
                        );
                    $stokAsal->increment('stok', $item->qty);
                } elseif ($locked->tipe === 'adjust') {
                    // Penyesuaian: kembalikan ke stok sebelumnya
                    $stok = BarangStok::lockForUpdate()
                        ->where('barang_id', $barangId)
                        ->where('gudang_id', $locked->gudang_id)
                        ->first();
                    if ($stok && $item->stok_sebelum !== null) {
                        $stok->update(['stok' => $item->stok_sebelum]);
                    }
                }
            }

            // Tandai sebagai dibatalkan (soft delete)
            $locked->update([
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()?->id,
                'cancellation_reason' => $reason,
            ]);
        });

        // Clear cache
        Cache::forget("stok.summary.{$mutasi->gudang_id}");
        if ($mutasi->gudang_tujuan_id) {
            Cache::forget("stok.summary.{$mutasi->gudang_tujuan_id}");
        }

        return back()->with('success', 'Transaksi berhasil dibatalkan dan stok telah disesuaikan. Data tetap tersimpan untuk audit.');
    }

    // Pengeluaran
    public function pengeluaran()
    {
        $masters = Cache::remember('mutasi.masters', now()->addHour(), function () {
            return [
                'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get()
                    ->toArray(),
                'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get()
                    ->toArray(),
            ];
        });

        return Inertia::render('Transaksi/BarangKeluar', $masters);
    }

    // Pemasukan
    public function pemasukan()
    {
        // Clear cache untuk memastikan data fresh
        Cache::forget('mutasi.masters');
        
        $masters = Cache::remember('mutasi.masters', now()->addHour(), function () {
            return [
                'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get()
                    ->toArray(),
                'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get()
                    ->toArray(),
                'suppliers' => Supplier::select('id', 'kode_supplier', 'nama_supplier', 'kontak', 'telepon')
                    ->where('is_active', true)
                    ->orderBy('nama_supplier')
                    ->get()
                    ->toArray(),
            ];
        });

        return Inertia::render('Transaksi/BarangMasuk', $masters);
    }

    // Transfer
    public function transfer()
    {
        $masters = Cache::remember('mutasi.masters', now()->addHour(), function () {
            return [
                'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get()
                    ->toArray(),
                'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get()
                    ->toArray(),
            ];
        });

        return Inertia::render('Transaksi/Transfer', $masters);
    }

    // Penyesuaian
    public function penyesuaian()
    {
        $masters = Cache::remember('mutasi.masters', now()->addHour(), function () {
            return [
                'barangs' => Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
                    ->where('is_active', true)
                    ->orderBy('nama_barang')
                    ->get()
                    ->toArray(),
                'gudangs' => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get()
                    ->toArray(),
            ];
        });

        return Inertia::render('Transaksi/Penyesuaian', $masters);
    }

    // Riwayat - Semua (termasuk yang dibatalkan untuk audit)
    public function riwayatSemua(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));
        $gudang  = $request->input('gudang_id', '');
        $status  = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $tipe    = $request->input('tipe', '');

        $query = StokMutasi::query()
            ->with([
                'gudang:id,kode_gudang,nama_gudang',
                'gudangTujuan:id,kode_gudang,nama_gudang',
                'supplier:id,kode_supplier,nama_supplier',
                'user:id,name',
                'approver:id,name',
                'canceller:id,name', // Tambahkan relasi canceller
            ])
            ->withCount('items');
        // TIDAK filter cancelled_at - tampilkan semua untuk audit

        // Filter tipe
        if (in_array($tipe, ['in', 'out', 'transfer', 'adjust'], true)) {
            $query->where('tipe', $tipe);
        }

        // Filter status
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        // Filter gudang
        if ($gudang) {
            $query->where(function ($q) use ($gudang) {
                $q->where('gudang_id', $gudang)->orWhere('gudang_tujuan_id', $gudang);
            });
        }

        // Filter tanggal
        if ($dateFrom) {
            $query->whereDate('tanggal', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('tanggal', '<=', $dateTo);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_mutasi', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $mutasis = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $gudangs = Gudang::select('id', 'kode_gudang', 'nama_gudang')
            ->where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();

        return Inertia::render('Riwayat/Semua', [
            'mutasis' => $mutasis,
            'gudangs' => $gudangs,
            'filters' => [
                'search'    => $search,
                'gudang_id' => $gudang,
                'status'    => $status,
                'tipe'      => $tipe,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'perPage'   => $perPage,
            ],
        ]);
    }

    // Riwayat - Pemasukan
    public function riwayatPemasukan(Request $request)
    {
        return $this->riwayatBase($request, 'in', 'Riwayat/Pemasukan');
    }

    // Riwayat - Pengeluaran
    public function riwayatPengeluaran(Request $request)
    {
        return $this->riwayatBase($request, 'out', 'Riwayat/Pengeluaran');
    }

    // Riwayat - Transfer
    public function riwayatTransfer(Request $request)
    {
        return $this->riwayatBase($request, 'transfer', 'Riwayat/Transfer');
    }

    // Riwayat - Penyesuaian
    public function riwayatPenyesuaian(Request $request)
    {
        return $this->riwayatBase($request, 'adjust', 'Riwayat/Penyesuaian');
    }

    private function riwayatBase(Request $request, ?string $tipe, string $view)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));
        $gudang  = $request->input('gudang_id', '');
        $status  = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');

        $query = StokMutasi::query()
            ->with([
                'gudang:id,kode_gudang,nama_gudang',
                'gudangTujuan:id,kode_gudang,nama_gudang',
                'supplier:id,kode_supplier,nama_supplier',
                'user:id,name',
                'approver:id,name',
            ])
            ->withCount('items')
            ->whereNull('cancelled_at'); // Filter transaksi yang tidak dibatalkan

        // Filter tipe
        if ($tipe) {
            $query->where('tipe', $tipe);
        }

        // Filter status
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        // Filter gudang
        if ($gudang) {
            $query->where(function ($q) use ($gudang) {
                $q->where('gudang_id', $gudang)->orWhere('gudang_tujuan_id', $gudang);
            });
        }

        // Filter tanggal
        if ($dateFrom) {
            $query->whereDate('tanggal', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('tanggal', '<=', $dateTo);
        }

        // Search
        if ($search !== '') {
            $prefix = $search . '%';
            $query->where(function ($q) use ($search, $prefix) {
                $q->where('nomor_mutasi', 'like', $prefix)
                  ->orWhere('referensi', 'like', $prefix)
                  ->orWhereHas('supplier', function ($qq) use ($search, $prefix) {
                      $qq->where('nama_supplier', 'like', '%' . $search . '%')
                         ->orWhere('kode_supplier', 'like', $prefix);
                  });
            });
        }

        $mutasis = $query->latest('tanggal')->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render($view, [
            'mutasis' => $mutasis,
            'filters' => [
                'search'    => $search,
                'perPage'   => $perPage,
                'gudang_id' => $gudang,
                'status'    => $status,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'gudangs' => fn () => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                ->where('is_active', true)
                ->orderBy('nama_gudang')
                ->get(),
        ]);
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
