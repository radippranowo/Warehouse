<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Imports\BarangImport;
use App\Models\Barang;
use App\Models\Category;
use App\Models\Group;
use App\Models\Gudang;
use App\Models\Merk;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        // Cache-buster `?_=...` setelah import/reset → paksa drop master cache.
        if ($request->has('_')) {
            Cache::forget('barang.masters');
        }

        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search = trim((string) $request->input('search', ''));

        $query = Barang::query()
            ->select([
                'id', 'kode_barang', 'part_number', 'nama_barang',
                'category_code', 'sub_category_code', 'merk_code', 'group_code',
                'satuan', 'harga_beli', 'harga_jual', 'min_stok',
            ])
            ->withSum('stoks as stok_total', 'stok')
            ->with([
                'kategori:kode_category,nama_category',
                'subKategori:kode_sub_category,nama_sub_category',
                'merk:kode_merk,nama_merk',
                'group:kode_group,nama_group',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', $search . '%')
                  ->orWhere('part_number', 'like', $search . '%');

                if (mb_strlen($search) >= 3) {
                    $q->orWhereFullText('nama_barang', $search);
                } else {
                    $q->orWhere('nama_barang', 'like', $search . '%');
                }
            });
        }

        $barangs = $query->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Barang/Index', [
            'barangs' => $barangs,
            'filters' => [
                'search'  => $search,
                'perPage' => $perPage,
            ],
            // Closure → cuma evaluate pada full visit / kalau 'masters' diminta
            // via `only`. Partial reload (search/pagination) skip total.
            'masters' => fn () => $this->mastersData(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Barang/Create', [
            'masters' => $this->mastersData(),
        ]);
    }

    private function mastersData(): array
    {
        return Cache::remember('barang.masters', now()->addHour(), function () {
            return [
                'categories'    => Category::select('kode_category', 'nama_category')->orderBy('nama_category')->get()->toArray(),
                'subCategories' => SubCategory::select('kode_sub_category', 'nama_sub_category')->orderBy('nama_sub_category')->get()->toArray(),
                'merks'         => Merk::select('kode_merk', 'nama_merk')->orderBy('nama_merk')->get()->toArray(),
                'groups'        => Group::select('kode_group', 'nama_group')->orderBy('nama_group')->get()->toArray(),
                'gudangs'       => Gudang::select('id', 'kode_gudang', 'nama_gudang')->where('is_active', true)->orderBy('nama_gudang')->get()->toArray(),
            ];
        });
    }

    public function store(StoreBarangRequest $request)
    {
        $data = $request->validated();
        $items = $data['items'];
        $now = now();

        foreach ($items as &$row) {
            $row['satuan']     = $row['satuan']     ?? 'pcs';
            $row['harga_beli'] = $row['harga_beli'] ?? 0;
            $row['harga_jual'] = $row['harga_jual'] ?? 0;
            $row['min_stok']   = $row['min_stok']   ?? 0;
            $row['is_active']  = $row['is_active']  ?? true;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::transaction(fn () => Barang::insert($items));

        return redirect('/barang');
    }

    public function show(Barang $barang)
    {
        $barang->load([
            'kategori:kode_category,nama_category',
            'subKategori:kode_sub_category,nama_sub_category',
            'merk:kode_merk,nama_merk',
            'group:kode_group,nama_group',
            'stoks.gudang:id,kode_gudang,nama_gudang',
        ]);

        $mutasis = $barang->mutasiItems()
            ->with([
                'mutasi:id,nomor_mutasi,tanggal,tipe,gudang_id,gudang_tujuan_id,referensi,user_id',
                'mutasi.gudang:id,nama_gudang',
                'mutasi.gudangTujuan:id,nama_gudang',
                'mutasi.user:id,name',
            ])
            ->latest('id')
            ->limit(50)
            ->get();

        // Deteksi konteks: kalau user datang dari /stok/{id}, tombol Kembali
        // balik ke /stok supaya alur navigasi tidak putus.
        $backUrl = request()->route()?->getName() === 'stok.show' ? '/stok' : '/barang';

        return Inertia::render('Barang/Show', [
            'barang'   => $barang,
            'mutasis'  => $mutasis,
            'back_url' => $backUrl,
        ]);
    }

    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $barang->update($request->validated());

        return back();
    }

    public function destroy(Barang $barang)
    {
        if ($barang->stoks()->where('stok', '>', 0)->exists()) {
            return back()->with('error', "Barang '{$barang->nama_barang}' masih punya stok di gudang.");
        }
        if ($barang->mutasiItems()->exists()) {
            return back()->with('error', "Barang '{$barang->nama_barang}' punya riwayat mutasi, tidak bisa dihapus.");
        }

        $barang->delete();

        return back()->with('success', 'Barang dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:barangs,id'],
        ]);

        $rows = Barang::whereIn('id', $data['ids'])
            ->withCount(['stoks as stoks_with_qty' => fn ($q) => $q->where('stok', '>', 0)])
            ->withCount('mutasiItems')
            ->get();

        $deletable = $rows->filter(fn ($b) => $b->stoks_with_qty == 0 && $b->mutasi_items_count == 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua barang yang dipilih masih punya stok atau riwayat mutasi.');
        }

        $deleted = DB::transaction(fn () => Barang::whereIn('id', $deletable->pluck('id'))->delete());

        $msg = "{$deleted} barang dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih punya stok / riwayat mutasi)" : '');
        return back()->with('success', $msg);
    }

    public function validateLive(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            StoreBarangRequest::rulesArray(),
            StoreBarangRequest::messagesArray()
        );

        $validator->after(function ($v) use ($request) {
            StoreBarangRequest::checkExistingDb((array) $request->input('items', []), $v);
        });

        return response()->json([
            'errors' => $validator->errors()->toArray(),
        ]);
    }

    public function masters()
    {
        return $this->mastersData();
    }

    public function clearAll(Request $request)
    {
        $request->validate([
            'confirm'   => ['required', 'string', 'in:HAPUS'],
            'targets'   => ['required', 'array', 'min:1'],
            'targets.*' => ['string', 'in:barang,mutasi,category,sub_category,merk,group,gudang'],
        ], [
            'confirm.in'       => "Ketik 'HAPUS' untuk konfirmasi",
            'targets.required' => 'Pilih minimal satu data yang akan dihapus',
        ]);

        $targets = array_unique($request->input('targets'));
        $deleted = [];

        DB::transaction(function () use ($targets, &$deleted) {
            $clearMutasi = in_array('mutasi', $targets) || in_array('barang', $targets);
            $clearBarang = in_array('barang', $targets);
            $clearGudang = in_array('gudang', $targets);

            if ($clearMutasi) {
                $deleted['mutasi_items'] = DB::table('stok_mutasi_items')->count();
                $deleted['mutasi']       = DB::table('stok_mutasis')->count();
                DB::table('stok_mutasi_items')->delete();
                DB::table('stok_mutasis')->delete();
            }
            if ($clearBarang || $clearGudang) {
                $deleted['barang_stoks'] = DB::table('barang_stoks')->count();
                DB::table('barang_stoks')->delete();
            }
            if ($clearBarang) {
                $deleted['barang'] = DB::table('barangs')->count();
                DB::table('barangs')->delete();
            }
            if (in_array('sub_category', $targets)) {
                $deleted['sub_category'] = DB::table('sub_categories')->count();
                DB::table('sub_categories')->delete();
            }
            if (in_array('category', $targets)) {
                $deleted['category'] = DB::table('categories')->count();
                DB::table('categories')->delete();
            }
            if (in_array('merk', $targets)) {
                $deleted['merk'] = DB::table('merks')->count();
                DB::table('merks')->delete();
            }
            if (in_array('group', $targets)) {
                $deleted['group'] = DB::table('groups')->count();
                DB::table('groups')->delete();
            }
            if ($clearGudang) {
                $deleted['gudang'] = DB::table('gudangs')->count();
                DB::table('gudangs')->delete();
            }
        });

        Cache::forget('barang.masters');
        DB::table('gudangs')->pluck('id')->each(fn ($id) => Cache::forget("stok.summary.{$id}"));

        $labels = [
            'barang'       => 'barang',
            'mutasi'       => 'mutasi',
            'mutasi_items' => null,
            'barang_stoks' => null,
            'category'     => 'kategori',
            'sub_category' => 'sub kategori',
            'merk'         => 'merk',
            'group'        => 'group',
            'gudang'       => 'gudang',
        ];
        $parts = [];
        foreach ($deleted as $key => $count) {
            if ($labels[$key] === null) continue;
            $parts[] = "{$count} {$labels[$key]}";
        }
        $msg = $parts ? 'Dihapus: ' . implode(', ', $parts) : 'Tidak ada data dihapus';

        return back()->with('import_result', [
            'status'   => 'success',
            'message'  => $msg,
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => [],
        ])->with('success', $msg);
    }

    public function showImport(Request $request)
    {
        return Inertia::render('Barang/Import', [
            'result' => fn () => $request->session()->get('import_result'),
        ]);
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [
            'file.required' => 'File Excel wajib dipilih',
            'file.mimes'    => 'Format harus .xlsx / .xls / .csv',
            'file.max'      => 'Ukuran file maksimal 20MB',
        ]);

        $importer = new BarangImport();

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $rows = [];
            foreach ($e->failures() as $f) {
                $rows[] = "Baris {$f->row()}: " . implode(', ', $f->errors());
            }
            return back()->with('import_result', [
                'status'   => 'error',
                'message'  => 'Validasi Excel gagal',
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => $rows,
            ]);
        } catch (\Throwable $e) {
            return back()->with('import_result', [
                'status'   => 'error',
                'message'  => 'Import gagal: ' . $e->getMessage(),
                'imported' => $importer->imported,
                'skipped'  => $importer->skipped,
                'errors'   => $importer->errors,
            ]);
        }

        Cache::forget('barang.masters');

        $hasIssue = count($importer->errors) > 0 || $importer->skipped > 0;
        return back()->with('import_result', [
            'status'   => $hasIssue ? 'partial' : 'success',
            'message'  => $hasIssue
                ? "Import selesai dgn catatan — {$importer->imported} berhasil, {$importer->skipped} dilewati"
                : "{$importer->imported} barang ter-import",
            'imported' => $importer->imported,
            'skipped'  => $importer->skipped,
            'errors'   => $importer->errors,
        ]);
    }
}
