<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use App\Models\Category;
use App\Models\Group;
use App\Models\Gudang;
use App\Models\Merk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search = trim((string) $request->input('search', ''));

        $query = Barang::query()
            ->select([
                'id', 'kode_barang', 'part_number', 'nama_barang',
                'category_code', 'merk_code', 'group_code',
                'satuan', 'harga_beli', 'harga_jual', 'min_stok',
            ])
            ->withSum('stoks as stok_total', 'stok')
            ->with([
                'kategori:kode_category,nama_category',
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
            'masters' => $this->mastersData(),
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
                'categories' => Category::select('kode_category', 'nama_category')->orderBy('nama_category')->get()->toArray(),
                'merks'      => Merk::select('kode_merk', 'nama_merk')->orderBy('nama_merk')->get()->toArray(),
                'groups'     => Group::select('kode_group', 'nama_group')->orderBy('nama_group')->get()->toArray(),
                'gudangs'    => Gudang::select('id', 'kode_gudang', 'nama_gudang')->where('is_active', true)->orderBy('nama_gudang')->get()->toArray(),
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

        return Inertia::render('Barang/Show', [
            'barang'  => $barang,
            'mutasis' => $mutasis,
        ]);
    }

    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $barang->update($request->validated());

        return back();
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();

        return back();
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
}
