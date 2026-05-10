<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokMutasi;
use App\Models\StokMutasiItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaporanApiController extends Controller
{
    /**
     * Laporan Keuntungan (Optimized)
     * GET /api/laporan/keuntungan
     */
    public function keuntungan(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Subquery untuk harga beli dari transaksi terakhir
        $hargaBeliSubquery = "(
            SELECT smi_in.harga_satuan
            FROM stok_mutasi_items as smi_in
            INNER JOIN stok_mutasis as sm_in ON smi_in.stok_mutasi_id = sm_in.id
            WHERE smi_in.barang_id = stok_mutasi_items.barang_id
                AND sm_in.gudang_id = stok_mutasis.gudang_id
                AND sm_in.tipe = 'in'
                AND sm_in.status = 'approved'
                AND sm_in.tanggal <= stok_mutasis.tanggal
            ORDER BY sm_in.tanggal DESC, sm_in.id DESC
            LIMIT 1
        )";

        $query = StokMutasiItem::select([
            'stok_mutasi_items.id',
            'stok_mutasi_items.barang_id',
            'stok_mutasi_items.qty',
            'stok_mutasi_items.harga_satuan as harga_jual',
            'barangs.kode_barang',
            'barangs.nama_barang',
            'barangs.satuan',
            'stok_mutasis.nomor_mutasi',
            'stok_mutasis.tanggal',
            'gudangs.nama_gudang',
            DB::raw("COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0) as harga_beli"),
            DB::raw("(stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) as keuntungan_per_unit"),
            DB::raw("((stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) * stok_mutasi_items.qty) as total_keuntungan"),
            DB::raw("(COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0) * stok_mutasi_items.qty) as total_modal"),
            DB::raw('(stok_mutasi_items.harga_satuan * stok_mutasi_items.qty) as total_penjualan')
        ])
        ->join('stok_mutasis', 'stok_mutasi_items.stok_mutasi_id', '=', 'stok_mutasis.id')
        ->join('barangs', 'stok_mutasi_items.barang_id', '=', 'barangs.id')
        ->join('gudangs', 'stok_mutasis.gudang_id', '=', 'gudangs.id')
        ->where('stok_mutasis.tipe', 'out')
        ->where('stok_mutasis.status', 'approved');

        // Apply filters
        if ($gudangId) {
            $query->where('stok_mutasis.gudang_id', $gudangId);
        }
        if ($startDate) {
            $query->whereDate('stok_mutasis.tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('stok_mutasis.tanggal', '<=', $endDate);
        }

        $items = $query->orderBy('stok_mutasis.tanggal', 'desc')
            ->paginate($perPage);

        // Summary dengan cache
        $cacheKey = 'laporan.keuntungan.summary.' . md5(($gudangId ?? 'all') . '.' . ($startDate ?? '') . '.' . ($endDate ?? ''));
        $summary = Cache::remember($cacheKey, 300, function() use ($gudangId, $startDate, $endDate, $hargaBeliSubquery) {
            $query = StokMutasiItem::select([
                DB::raw('SUM(stok_mutasi_items.qty) as total_qty'),
                DB::raw("SUM(stok_mutasi_items.qty * COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) as total_modal"),
                DB::raw('SUM(stok_mutasi_items.qty * stok_mutasi_items.harga_satuan) as total_penjualan'),
                DB::raw("SUM((stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) * stok_mutasi_items.qty) as total_keuntungan")
            ])
            ->join('stok_mutasis', 'stok_mutasi_items.stok_mutasi_id', '=', 'stok_mutasis.id')
            ->join('barangs', 'stok_mutasi_items.barang_id', '=', 'barangs.id')
            ->where('stok_mutasis.tipe', 'out')
            ->where('stok_mutasis.status', 'approved');

            if ($gudangId) {
                $query->where('stok_mutasis.gudang_id', $gudangId);
            }
            if ($startDate) {
                $query->whereDate('stok_mutasis.tanggal', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('stok_mutasis.tanggal', '<=', $endDate);
            }

            return $query->first();
        });

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'summary' => [
                'total_qty' => (int) ($summary->total_qty ?? 0),
                'total_modal' => (float) ($summary->total_modal ?? 0),
                'total_penjualan' => (float) ($summary->total_penjualan ?? 0),
                'total_keuntungan' => (float) ($summary->total_keuntungan ?? 0),
                'margin' => $summary->total_penjualan > 0 
                    ? round(($summary->total_keuntungan / $summary->total_penjualan) * 100, 2) 
                    : 0,
            ],
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Laporan Stok per Gudang
     * GET /api/laporan/stok
     */
    public function stok(Request $request)
    {
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;
        $lowOnly = $request->boolean('low_only', false);

        if (!$gudangId) {
            return response()->json([
                'success' => false,
                'message' => 'gudang_id is required'
            ], 400);
        }

        $query = DB::table('barangs')
            ->select([
                'barangs.id',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.satuan',
                'barangs.min_stok',
                DB::raw('COALESCE(barang_stoks.stok, 0) as stok'),
                DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok_efektif'),
            ])
            ->leftJoin('barang_stoks', function($join) use ($gudangId) {
                $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                     ->where('barang_stoks.gudang_id', '=', $gudangId);
            })
            ->where('barangs.is_active', true);

        if ($lowOnly) {
            $query->havingRaw('stok <= min_stok_efektif');
        }

        $items = $query->orderBy('barangs.nama_barang')->get();

        // Summary
        $summary = [
            'total_sku' => $items->count(),
            'total_stok' => $items->sum('stok'),
            'stok_kosong' => $items->where('stok', 0)->count(),
            'stok_rendah' => $items->filter(function($item) {
                return $item->stok > 0 && $item->stok <= $item->min_stok_efektif;
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $items,
            'summary' => $summary
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Dashboard Stats (Cached)
     * GET /api/dashboard/stats
     */
    public function dashboardStats()
    {
        $stats = Cache::remember('api.dashboard.stats', 60, function() {
            // Basic stats
            $basicStats = [
                'barang' => DB::table('barangs')->where('is_active', true)->count(),
                'category' => DB::table('categories')->count(),
                'merk' => DB::table('merks')->count(),
                'gudang' => DB::table('gudangs')->where('is_active', true)->count(),
            ];

            // Stok stats
            $stokStats = DB::table('barang_stoks')
                ->select([
                    DB::raw('COUNT(DISTINCT barang_id) as total_sku'),
                    DB::raw('SUM(stok) as total_stok'),
                    DB::raw('SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as stok_kosong'),
                ])
                ->first();

            // Transaksi bulan ini
            $startOfMonth = now()->startOfMonth();
            $transaksiStats = DB::table('stok_mutasis')
                ->where('tanggal', '>=', $startOfMonth)
                ->where('status', 'approved')
                ->select([
                    DB::raw('COUNT(CASE WHEN tipe = "in" THEN 1 END) as transaksi_masuk'),
                    DB::raw('COUNT(CASE WHEN tipe = "out" THEN 1 END) as transaksi_keluar'),
                    DB::raw('SUM(CASE WHEN tipe = "in" THEN total_value ELSE 0 END) as nilai_masuk'),
                    DB::raw('SUM(CASE WHEN tipe = "out" THEN total_value ELSE 0 END) as nilai_keluar'),
                ])
                ->first();

            return array_merge($basicStats, [
                'stok_total_sku' => (int) ($stokStats->total_sku ?? 0),
                'stok_total' => (int) ($stokStats->total_stok ?? 0),
                'stok_kosong' => (int) ($stokStats->stok_kosong ?? 0),
                'transaksi_masuk_bulan_ini' => (int) ($transaksiStats->transaksi_masuk ?? 0),
                'transaksi_keluar_bulan_ini' => (int) ($transaksiStats->transaksi_keluar ?? 0),
                'nilai_masuk_bulan_ini' => (float) ($transaksiStats->nilai_masuk ?? 0),
                'nilai_keluar_bulan_ini' => (float) ($transaksiStats->nilai_keluar ?? 0),
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ])->header('Cache-Control', 'public, max-age=60');
    }
}
