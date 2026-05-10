<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokMutasi;
use App\Models\StokMutasiItem;
use App\Models\Barang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MutasiApiController extends Controller
{
    /**
     * Get list transaksi/mutasi
     * GET /api/v1/mutasi?tipe=out&status=approved
     */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $tipe = $request->input('tipe'); // in, out, transfer, adjust
        $status = $request->input('status'); // draft, approved, rejected
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = trim($request->input('search', ''));

        $query = StokMutasi::select([
            'id',
            'nomor_mutasi',
            'tanggal',
            'tipe',
            'status',
            'gudang_id',
            'gudang_tujuan_id',
            'referensi',
            'total_qty',
            'total_value',
            'user_id',
            'created_at',
        ])
        ->with([
            'gudang:id,nama_gudang',
            'gudangTujuan:id,nama_gudang',
            'user:id,name',
        ]);

        // Filters
        if ($tipe) {
            $query->where('tipe', $tipe);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($gudangId) {
            $query->where('gudang_id', $gudangId);
        }
        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_mutasi', 'like', '%' . $search . '%')
                  ->orWhere('referensi', 'like', '%' . $search . '%');
            });
        }

        $mutasis = $query->latest('tanggal')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $mutasis->items(),
            'meta' => [
                'current_page' => $mutasis->currentPage(),
                'last_page' => $mutasis->lastPage(),
                'per_page' => $mutasis->perPage(),
                'total' => $mutasis->total(),
            ]
        ])->header('Cache-Control', 'no-cache, must-revalidate');
    }

    /**
     * Get detail transaksi
     * GET /api/v1/mutasi/{id}
     */
    public function show($id)
    {
        try {
            $mutasi = StokMutasi::with([
            'gudang:id,kode_gudang,nama_gudang',
            'gudangTujuan:id,kode_gudang,nama_gudang',
            'user:id,name',
            'items' => function($query) {
                $query->select([
                    'id',
                    'stok_mutasi_id',
                    'barang_id',
                    'qty',
                    'harga_satuan',
                    'subtotal',
                ]);
            },
            'items.barang:id,kode_barang,nama_barang,satuan',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $mutasi->id,
                'nomor_mutasi' => $mutasi->nomor_mutasi,
                'tanggal' => $mutasi->tanggal,
                'tipe' => $mutasi->tipe,
                'status' => $mutasi->status,
                'gudang' => $mutasi->gudang,
                'gudang_tujuan' => $mutasi->gudangTujuan,
                'referensi' => $mutasi->referensi,
                'keterangan' => $mutasi->keterangan,
                'total_qty' => $mutasi->total_qty,
                'total_value' => $mutasi->total_value,
                'user' => $mutasi->user,
                'items' => $mutasi->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'barang_id' => $item->barang_id,
                        'kode_barang' => $item->barang->kode_barang,
                        'nama_barang' => $item->barang->nama_barang,
                        'satuan' => $item->barang->satuan,
                        'qty' => $item->qty,
                        'harga_satuan' => $item->harga_satuan,
                        'subtotal' => $item->subtotal,
                    ];
                }),
                'created_at' => $mutasi->created_at,
                'updated_at' => $mutasi->updated_at,
            ]
        ])->header('Cache-Control', 'no-cache, must-revalidate');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Create transaksi baru
     * POST /api/v1/mutasi
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'tipe' => 'required|in:in,out,transfer,adjust',
            'gudang_id' => 'required|exists:gudangs,id',
            'gudang_tujuan_id' => 'required_if:tipe,transfer|exists:gudangs,id',
            'referensi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate nomor mutasi
            $prefix = strtoupper(substr($request->tipe, 0, 2));
            $lastNumber = StokMutasi::where('nomor_mutasi', 'like', $prefix . date('Ymd') . '%')
                ->orderBy('id', 'desc')
                ->value('nomor_mutasi');
            
            $sequence = $lastNumber ? intval(substr($lastNumber, -4)) + 1 : 1;
            $nomorMutasi = $prefix . date('Ymd') . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Create mutasi
            $mutasi = StokMutasi::create([
                'nomor_mutasi' => $nomorMutasi,
                'tanggal' => $request->tanggal,
                'tipe' => $request->tipe,
                'status' => 'draft',
                'gudang_id' => $request->gudang_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'referensi' => $request->referensi,
                'keterangan' => $request->keterangan,
                'user_id' => Auth::id() ?? 1,
            ]);

            // Create items
            $totalQty = 0;
            $totalValue = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['harga_satuan'];
                
                StokMutasiItem::create([
                    'stok_mutasi_id' => $mutasi->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $subtotal,
                ]);

                $totalQty += $item['qty'];
                $totalValue += $subtotal;
            }

            // Update totals
            $mutasi->update([
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibuat',
                'data' => [
                    'id' => $mutasi->id,
                    'nomor_mutasi' => $mutasi->nomor_mutasi,
                    'status' => $mutasi->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve transaksi
     * PUT /api/v1/mutasi/{id}/approve
     */
    public function approve($id)
    {
        try {
            $mutasi = StokMutasi::findOrFail($id);

            if ($mutasi->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi draft yang bisa di-approve'
                ], 400);
            }

            DB::beginTransaction();

            // Update status
            $mutasi->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id() ?? 1,
            ]);

            // Update stok
            $this->updateStok($mutasi);

            // Clear cache
            Cache::forget('api.dashboard.stats');
            Cache::forget("stok.summary.{$mutasi->gudang_id}");
            if ($mutasi->gudang_tujuan_id) {
                Cache::forget("stok.summary.{$mutasi->gudang_tujuan_id}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-approve',
                'data' => [
                    'id' => $mutasi->id,
                    'status' => $mutasi->status,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject transaksi
     * PUT /api/v1/mutasi/{id}/reject
     */
    public function reject($id, Request $request)
    {
        $mutasi = StokMutasi::findOrFail($id);

        if ($mutasi->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya transaksi draft yang bisa di-reject'
            ], 400);
        }

        $mutasi->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id() ?? 1,
            'reject_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil di-reject',
            'data' => [
                'id' => $mutasi->id,
                'status' => $mutasi->status,
            ]
        ]);
    }

    /**
     * Update stok setelah approve
     */
    private function updateStok(StokMutasi $mutasi)
    {
        foreach ($mutasi->items as $item) {
            $qty = (float) $item->qty;
            
            if ($mutasi->tipe === 'in') {
                // Barang masuk - tambah stok
                DB::table('barang_stoks')
                    ->updateOrInsert(
                        [
                            'barang_id' => $item->barang_id,
                            'gudang_id' => $mutasi->gudang_id,
                        ],
                        [
                            'stok' => DB::raw("stok + " . $qty),
                            'updated_at' => now(),
                        ]
                    );
            } elseif ($mutasi->tipe === 'out') {
                // Barang keluar - kurangi stok
                DB::table('barang_stoks')
                    ->where('barang_id', $item->barang_id)
                    ->where('gudang_id', $mutasi->gudang_id)
                    ->update([
                        'stok' => DB::raw("GREATEST(0, stok - " . $qty . ")"),
                        'updated_at' => now(),
                    ]);
            } elseif ($mutasi->tipe === 'transfer') {
                // Transfer - kurangi dari gudang asal, tambah ke gudang tujuan
                DB::table('barang_stoks')
                    ->where('barang_id', $item->barang_id)
                    ->where('gudang_id', $mutasi->gudang_id)
                    ->update([
                        'stok' => DB::raw("GREATEST(0, stok - " . $qty . ")"),
                        'updated_at' => now(),
                    ]);

                DB::table('barang_stoks')
                    ->updateOrInsert(
                        [
                            'barang_id' => $item->barang_id,
                            'gudang_id' => $mutasi->gudang_tujuan_id,
                        ],
                        [
                            'stok' => DB::raw("stok + " . $qty),
                            'updated_at' => now(),
                        ]
                    );
            } elseif ($mutasi->tipe === 'adjust') {
                // Penyesuaian - set stok langsung
                DB::table('barang_stoks')
                    ->updateOrInsert(
                        [
                            'barang_id' => $item->barang_id,
                            'gudang_id' => $mutasi->gudang_id,
                        ],
                        [
                            'stok' => $qty,
                            'updated_at' => now(),
                        ]
                    );
            }
        }
    }
}
