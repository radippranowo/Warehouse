<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangStok;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StokApiController extends Controller
{
    /**
     * Get stok per gudang dengan optimasi
     * GET /api/v1/stok?gudang_id=1
     */
    public function index(Request $request)
    {
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;
        $search = trim($request->input('search', ''));
        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $lowOnly = $request->boolean('low_only', false);

        if (!$gudangId) {
            $gudangId = Gudang::where('is_active', true)->orderBy('id')->value('id');
        }

        $query = DB::table('barangs')
            ->select([
                'barangs.id',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.satuan',
                'barangs.harga_jual',
                'barangs.min_stok',
                DB::raw('COALESCE(barang_stoks.stok, 0) as stok'),
                DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok_efektif'),
            ])
            ->leftJoin('barang_stoks', function($join) use ($gudangId) {
                $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                     ->where('barang_stoks.gudang_id', '=', $gudangId);
            })
            ->where('barangs.is_active', true);

        // Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('barangs.kode_barang', 'like', $search . '%')
                  ->orWhere('barangs.nama_barang', 'like', '%' . $search . '%');
            });
        }

        // Filter low stock
        if ($lowOnly) {
            $query->havingRaw('stok <= min_stok_efektif');
        }

        $items = $query->orderBy('barangs.nama_barang')->paginate($perPage);

        // Get gudang info
        $gudang = Gudang::select('id', 'kode_gudang', 'nama_gudang')->find($gudangId);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'gudang' => $gudang,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Get summary stok per gudang (cached)
     * GET /api/v1/stok/summary?gudang_id=1
     */
    public function summary(Request $request)
    {
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;

        if (!$gudangId) {
            return response()->json([
                'success' => false,
                'message' => 'gudang_id is required'
            ], 400);
        }

        $cacheKey = "stok.summary.{$gudangId}";
        
        $summary = Cache::remember($cacheKey, 300, function() use ($gudangId) {
            $stats = DB::table('barangs')
                ->select([
                    DB::raw('COUNT(DISTINCT barangs.id) as total_sku'),
                    DB::raw('SUM(COALESCE(barang_stoks.stok, 0)) as total_stok'),
                    DB::raw('SUM(CASE WHEN COALESCE(barang_stoks.stok, 0) = 0 THEN 1 ELSE 0 END) as stok_kosong'),
                    DB::raw('SUM(CASE WHEN COALESCE(barang_stoks.stok, 0) > 0 AND COALESCE(barang_stoks.stok, 0) <= COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) THEN 1 ELSE 0 END) as stok_rendah'),
                    DB::raw('SUM(COALESCE(barang_stoks.stok, 0) * barangs.harga_beli) as nilai_stok'),
                ])
                ->leftJoin('barang_stoks', function($join) use ($gudangId) {
                    $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                         ->where('barang_stoks.gudang_id', '=', $gudangId);
                })
                ->where('barangs.is_active', true)
                ->first();

            return [
                'total_sku' => (int) ($stats->total_sku ?? 0),
                'total_stok' => (int) ($stats->total_stok ?? 0),
                'stok_kosong' => (int) ($stats->stok_kosong ?? 0),
                'stok_rendah' => (int) ($stats->stok_rendah ?? 0),
                'stok_aman' => (int) ($stats->total_sku ?? 0) - (int) ($stats->stok_kosong ?? 0) - (int) ($stats->stok_rendah ?? 0),
                'nilai_stok' => (float) ($stats->nilai_stok ?? 0),
            ];
        });

        $gudang = Gudang::select('id', 'kode_gudang', 'nama_gudang')->find($gudangId);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'gudang' => $gudang
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Get barang dengan stok rendah
     * GET /api/v1/stok/low-stock?gudang_id=1
     */
    public function lowStock(Request $request)
    {
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;
        $limit = min(max((int) $request->input('limit', 20), 1), 100);

        if (!$gudangId) {
            return response()->json([
                'success' => false,
                'message' => 'gudang_id is required'
            ], 400);
        }

        $items = DB::table('barangs')
            ->select([
                'barangs.id',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.satuan',
                DB::raw('COALESCE(barang_stoks.stok, 0) as stok'),
                DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok'),
            ])
            ->leftJoin('barang_stoks', function($join) use ($gudangId) {
                $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                     ->where('barang_stoks.gudang_id', '=', $gudangId);
            })
            ->where('barangs.is_active', true)
            ->havingRaw('stok <= min_stok')
            ->orderBy('stok', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $items->count()
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
