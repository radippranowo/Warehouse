<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BarangApiController extends Controller
{
    /**
     * Get list barang dengan optimasi
     * GET /api/barang
     */
    public function index(Request $request)
    {
        // Validation
        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $search = trim($request->input('search', ''));
        $withRelations = $request->boolean('with_relations', false);

        $query = Barang::select([
            'id',
            'kode_barang',
            'part_number',
            'nama_barang',
            'category_code',
            'merk_code',
            'satuan',
            'harga_beli',
            'harga_jual',
            'min_stok',
        ])
        ->where('is_active', true);

        // Eager loading hanya jika diminta
        if ($withRelations) {
            $query->with([
                'kategori:kode_category,nama_category',
                'merk:kode_merk,nama_merk',
            ]);
        }

        // Search optimization
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode_barang', 'like', $search . '%')
                  ->orWhere('part_number', 'like', $search . '%')
                  ->orWhere('nama_barang', 'like', '%' . $search . '%');
            });
        }

        $barangs = $query->orderBy('nama_barang')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $barangs->items(),
            'meta' => [
                'current_page' => $barangs->currentPage(),
                'last_page' => $barangs->lastPage(),
                'per_page' => $barangs->perPage(),
                'total' => $barangs->total(),
            ]
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Get detail barang
     * GET /api/barang/{id}
     */
    public function show($id)
    {
        try {
            $barang = Barang::select([
            'id',
            'kode_barang',
            'part_number',
            'nama_barang',
            'category_code',
            'sub_category_code',
            'merk_code',
            'group_code',
            'satuan',
            'harga_beli',
            'harga_jual',
            'min_stok',
        ])
        ->with([
            'kategori:kode_category,nama_category',
            'subKategori:kode_sub_category,nama_sub_category',
            'merk:kode_merk,nama_merk',
            'group:kode_group,nama_group',
            'stoks:barang_id,gudang_id,stok',
            'stoks.gudang:id,nama_gudang',
        ])
        ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $barang->id,
                'kode_barang' => $barang->kode_barang,
                'part_number' => $barang->part_number,
                'nama_barang' => $barang->nama_barang,
                'kategori' => $barang->kategori?->nama_category,
                'sub_kategori' => $barang->subKategori?->nama_sub_category,
                'merk' => $barang->merk?->nama_merk,
                'group' => $barang->group?->nama_group,
                'satuan' => $barang->satuan,
                'harga_beli' => $barang->harga_beli,
                'harga_jual' => $barang->harga_jual,
                'min_stok' => $barang->min_stok,
                'stok_per_gudang' => $barang->stoks->map(function($stok) {
                    return [
                        'gudang' => $stok->gudang->nama_gudang,
                        'stok' => $stok->stok,
                    ];
                }),
                'total_stok' => $barang->stoks->sum('stok'),
            ]
        ])->header('Cache-Control', 'public, max-age=600');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Search barang (autocomplete)
     * GET /api/barang/search
     */
    public function search(Request $request)
    {
        $search = trim($request->input('q', ''));
        $limit = min(max((int) $request->input('limit', 20), 1), 100);

        if (strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Cache hasil search untuk 5 menit
        $cacheKey = 'barang.search.' . md5($search . '.' . $limit);
        
        $results = Cache::remember($cacheKey, 300, function() use ($search, $limit) {
            return Barang::select('id', 'kode_barang', 'nama_barang', 'satuan', 'harga_jual')
                ->where('is_active', true)
                ->where(function($q) use ($search) {
                    $q->where('kode_barang', 'like', $search . '%')
                      ->orWhere('nama_barang', 'like', '%' . $search . '%');
                })
                ->limit($limit)
                ->get()
                ->map(function($barang) {
                    return [
                        'id' => $barang->id,
                        'label' => "{$barang->kode_barang} - {$barang->nama_barang}",
                        'kode' => $barang->kode_barang,
                        'nama' => $barang->nama_barang,
                        'satuan' => $barang->satuan,
                        'harga' => $barang->harga_jual,
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'data' => $results
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Get stok barang per gudang
     * GET /api/barang/{id}/stok
     */
    public function stok($id, Request $request)
    {
        $gudangId = $request->input('gudang_id') ? (int) $request->input('gudang_id') : null;

        $query = DB::table('barang_stoks')
            ->select([
                'barang_stoks.gudang_id',
                'gudangs.nama_gudang',
                'barang_stoks.stok',
                'barang_stoks.min_stok',
            ])
            ->join('gudangs', 'barang_stoks.gudang_id', '=', 'gudangs.id')
            ->where('barang_stoks.barang_id', $id)
            ->where('gudangs.is_active', true);

        if ($gudangId) {
            $query->where('barang_stoks.gudang_id', $gudangId);
        }

        $stoks = $query->get();

        return response()->json([
            'success' => true,
            'data' => $stoks,
            'total_stok' => $stoks->sum('stok')
        ])->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Get master data (cached)
     * GET /api/barang/masters
     */
    public function masters()
    {
        $masters = Cache::remember('api.barang.masters', 3600, function() {
            return [
                'categories' => DB::table('categories')
                    ->select('kode_category as value', 'nama_category as label')
                    ->orderBy('nama_category')
                    ->get(),
                'merks' => DB::table('merks')
                    ->select('kode_merk as value', 'nama_merk as label')
                    ->orderBy('nama_merk')
                    ->get(),
                'groups' => DB::table('groups')
                    ->select('kode_group as value', 'nama_group as label')
                    ->orderBy('nama_group')
                    ->get(),
                'gudangs' => DB::table('gudangs')
                    ->select('id as value', 'nama_gudang as label')
                    ->where('is_active', true)
                    ->orderBy('nama_gudang')
                    ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $masters
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
