<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use App\Models\Category;
use App\Models\Group;
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
            ->select(['id', 'kode_barang', 'part_number', 'nama_barang',
                'category_code', 'merk_code', 'group_code', 'stok', 'harga'])
            ->with([
                'kategori:kode_category,nama_category',
                'merk:kode_merk,nama_merk',
                'group:kode_group,nama_group',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                // Prefix LIKE → pakai B-tree index unique di kode_barang & part_number.
                // (Wildcard di belakang saja bisa pakai index, di depan tidak bisa.)
                $q->where('kode_barang', 'like', $search . '%')
                  ->orWhere('part_number', 'like', $search . '%');

                // FULLTEXT index pada nama_barang → cepat untuk dataset besar.
                // Minimum 3 karakter (innodb_ft_min_token_size default).
                // Untuk search pendek, fallback substring LIKE.
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

    // Cache dropdown options. toArray() dipakai supaya tidak unserialize
    // sebagai __PHP_Incomplete_Class. Cache di-flush oleh booted() di model.
    private function mastersData(): array
    {
        return Cache::remember('barang.masters', now()->addHour(), function () {
            return [
                'categories' => Category::select('kode_category', 'nama_category')->orderBy('nama_category')->get()->toArray(),
                'merks'      => Merk::select('kode_merk', 'nama_merk')->orderBy('nama_merk')->get()->toArray(),
                'groups'     => Group::select('kode_group', 'nama_group')->orderBy('nama_group')->get()->toArray(),
            ];
        });
    }

    public function store(StoreBarangRequest $request)
    {
        $data = $request->validated();
        $items = $data['items'];
        $now = now();

        foreach ($items as &$row) {
            $row['stok']  = $row['stok']  ?? 0;
            $row['harga'] = $row['harga'] ?? 0;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::transaction(fn () => Barang::insert($items));

        return redirect('/barang');
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
