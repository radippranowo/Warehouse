<?php

namespace App\Http\Controllers;

use App\Http\Requests\GudangRequest;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GudangController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));

        $query = Gudang::query()
            ->select(['id', 'kode_gudang', 'nama_gudang', 'alamat', 'penanggung_jawab', 'is_active'])
            ->withCount('stoks');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_gudang', 'like', $like)
                  ->orWhere('kode_gudang', 'like', $like)
                  ->orWhere('penanggung_jawab', 'like', $like);
            });
        }

        $gudangs = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Gudang/Index', [
            'gudangs' => $gudangs,
            'filters' => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(GudangRequest $request)
    {
        Gudang::create($request->validated());
        Cache::forget('barang.masters');
        return back()->with('success', 'Gudang berhasil ditambah');
    }

    public function update(GudangRequest $request, Gudang $gudang)
    {
        $gudang->update($request->validated());
        Cache::forget('barang.masters');
        return back()->with('success', 'Gudang berhasil diubah');
    }

    public function destroy(Gudang $gudang)
    {
        if ($gudang->stoks()->where('stok', '>', 0)->exists()) {
            return back()->with('error', "Gudang '{$gudang->nama_gudang}' masih punya stok barang.");
        }
        $gudang->delete();
        Cache::forget('barang.masters');
        return back()->with('success', 'Gudang berhasil dihapus');
    }
}
