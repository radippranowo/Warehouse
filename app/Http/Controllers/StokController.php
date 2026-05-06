<?php

namespace App\Http\Controllers;

use App\Exports\StokGudangExport;
use App\Models\Barang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));
        $gudangId = $request->input('gudang_id');
        $lowOnly  = $request->boolean('low_only');

        // Pilih gudang default = pertama yang aktif kalau belum ada filter.
        if (!$gudangId) {
            $gudangId = Gudang::where('is_active', true)->orderBy('id')->value('id');
        }

        $gudang = $gudangId ? Gudang::find($gudangId) : null;

        // LEFT JOIN barang_stoks untuk gudang terpilih → semua barang muncul,
        // walau belum pernah ada stok di gudang ini (stok=0 default).
        $query = Barang::query()
            ->select([
                'barangs.id',
                'barangs.kode_barang',
                'barangs.part_number',
                'barangs.nama_barang',
                'barangs.category_code',
                'barangs.merk_code',
                'barangs.satuan',
                'barangs.harga_jual',
                'barangs.min_stok',
                DB::raw('COALESCE(barang_stoks.stok, 0) as stok_gudang'),
                DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok_efektif'),
            ])
            ->leftJoin('barang_stoks', function ($join) use ($gudangId) {
                $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                     ->where('barang_stoks.gudang_id', '=', $gudangId);
            })
            ->where('barangs.is_active', true)
            ->with([
                'kategori:kode_category,nama_category',
                'merk:kode_merk,nama_merk',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('barangs.kode_barang', 'like', $search . '%')
                  ->orWhere('barangs.part_number', 'like', $search . '%')
                  ->orWhere('barangs.nama_barang', 'like', '%' . $search . '%');
            });
        }

        if ($lowOnly) {
            // Stok efektif ≤ min stok efektif. Pakai HAVING karena alias.
            $query->havingRaw('stok_gudang <= min_stok_efektif');
        }

        $rows = $query->orderBy('barangs.nama_barang')->paginate($perPage)->withQueryString();

        // Eager-load stok di SEMUA gudang per barang yang ada di halaman ini.
        // Tujuan: modal detail di /stok bisa render seketika tanpa HTTP call,
        // sehingga klik "lihat detail" tidak meninggalkan halaman /stok
        // (sidebar tetap highlight "Stok per Gudang").
        $barangIds = collect($rows->items())->pluck('id');
        $stoksByBarang = DB::table('barang_stoks')
            ->select('barang_stoks.barang_id', 'barang_stoks.stok', 'barang_stoks.min_stok',
                'gudangs.id as gudang_id', 'gudangs.kode_gudang', 'gudangs.nama_gudang')
            ->join('gudangs', 'gudangs.id', '=', 'barang_stoks.gudang_id')
            ->whereIn('barang_stoks.barang_id', $barangIds)
            ->orderBy('gudangs.nama_gudang')
            ->get()
            ->groupBy('barang_id');

        $rows->getCollection()->transform(function ($r) use ($stoksByBarang) {
            $r->all_stoks = $stoksByBarang->get($r->id, collect())->values();
            return $r;
        });

        // Summary cards
        $summary = [
            'total_sku'    => 0,
            'low_count'    => 0,
            'zero_count'   => 0,
            'total_value'  => 0,
        ];
        if ($gudangId) {
            $agg = DB::table('barangs')
                ->leftJoin('barang_stoks', function ($j) use ($gudangId) {
                    $j->on('barang_stoks.barang_id', '=', 'barangs.id')
                      ->where('barang_stoks.gudang_id', '=', $gudangId);
                })
                ->where('barangs.is_active', true)
                ->selectRaw('
                    COUNT(*) as total_sku,
                    SUM(CASE WHEN COALESCE(barang_stoks.stok,0) = 0 THEN 1 ELSE 0 END) as zero_count,
                    SUM(CASE WHEN COALESCE(barang_stoks.stok,0) <= COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) THEN 1 ELSE 0 END) as low_count,
                    SUM(COALESCE(barang_stoks.stok,0) * barangs.harga_jual) as total_value
                ')
                ->first();
            $summary = [
                'total_sku'   => (int) $agg->total_sku,
                'low_count'   => (int) $agg->low_count,
                'zero_count'  => (int) $agg->zero_count,
                'total_value' => (float) $agg->total_value,
            ];
        }

        if ($request->boolean('export')) {
            $g = Gudang::find($gudangId);
            $name = $g ? str($g->nama_gudang)->slug() : 'stok';
            return Excel::download(
                new StokGudangExport($gudangId, $search ?: null, $lowOnly),
                "stok-{$name}-" . now()->format('Ymd-His') . '.xlsx'
            );
        }

        if ($request->boolean('print')) {
            $printQuery = Barang::query()
                ->select([
                    'barangs.id',
                    'barangs.kode_barang',
                    'barangs.part_number',
                    'barangs.nama_barang',
                    'barangs.merk_code',
                    'barangs.satuan',
                    'barangs.harga_jual',
                    'barangs.min_stok',
                    DB::raw('COALESCE(barang_stoks.stok, 0) as stok_gudang'),
                    DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok_efektif'),
                ])
                ->leftJoin('barang_stoks', function ($join) use ($gudangId) {
                    $join->on('barang_stoks.barang_id', '=', 'barangs.id')
                         ->where('barang_stoks.gudang_id', '=', $gudangId);
                })
                ->where('barangs.is_active', true)
                ->with([
                    'kategori:kode_category,nama_category',
                    'merk:kode_merk,nama_merk',
                ]);
            if ($search !== '') {
                $printQuery->where(function ($q) use ($search) {
                    $q->where('barangs.kode_barang', 'like', $search . '%')
                      ->orWhere('barangs.part_number', 'like', $search . '%')
                      ->orWhere('barangs.nama_barang', 'like', '%' . $search . '%');
                });
            }
            if ($lowOnly) {
                $printQuery->havingRaw('stok_gudang <= min_stok_efektif');
            }
            $items = $printQuery->orderBy('barangs.nama_barang')->get();

            return view('stok.print', [
                'gudang'  => $gudang,
                'items'   => $items,
                'summary' => $summary,
                'filters' => [
                    'search'   => $search,
                    'low_only' => $lowOnly,
                ],
            ]);
        }

        return Inertia::render('Stok/Index', [
            'rows'     => $rows,
            'gudangs'  => Gudang::select('id', 'kode_gudang', 'nama_gudang')
                ->where('is_active', true)
                ->orderBy('nama_gudang')
                ->get(),
            'gudang'   => $gudang,
            'summary'  => $summary,
            'filters'  => [
                'search'    => $search,
                'perPage'   => $perPage,
                'gudang_id' => (int) $gudangId,
                'low_only'  => $lowOnly,
            ],
        ]);
    }
}
