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
            ->withCount(['stoks as stoks_with_qty' => fn ($q) => $q->where('stok', '>', 0)]);

        if ($search !== '') {
            $like = $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_gudang', 'like', $like)
                  ->orWhere('kode_gudang', 'like', $like)
                  ->orWhere('penanggung_jawab', 'like', $like);
            });
        }

        return Inertia::render('Gudang/Index', [
            'gudangs' => $query->latest('id')->paginate($perPage)->withQueryString(),
            'filters' => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(GudangRequest $request)
    {
        Gudang::create($request->validated());
        Cache::forget('barang.masters');
        Cache::forget('mutasi.masters');
        return back()->with('success', 'Gudang berhasil ditambah');
    }

    public function update(GudangRequest $request, Gudang $gudang)
    {
        $gudang->update($request->validated());
        Cache::forget('barang.masters');
        Cache::forget('mutasi.masters');
        return back()->with('success', 'Gudang berhasil diubah');
    }

    public function destroy(Gudang $gudang)
    {
        if ($gudang->stoks()->where('stok', '>', 0)->exists()) {
            return back()->with('error', "Gudang '{$gudang->nama_gudang}' masih punya stok barang.");
        }
        $gudang->delete();
        Cache::forget('barang.masters');
        Cache::forget('mutasi.masters');
        return back()->with('success', 'Gudang berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:gudangs,id'],
        ]);

        $rows = Gudang::whereIn('id', $data['ids'])
            ->withCount(['stoks as stoks_with_qty' => fn ($q) => $q->where('stok', '>', 0)])
            ->get();
        $deletable    = $rows->where('stoks_with_qty', 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua gudang yang dipilih masih punya stok.');
        }

        $deleted = Gudang::whereIn('id', $deletable->pluck('id'))->delete();
        Cache::forget('barang.masters');
        Cache::forget('mutasi.masters');
        $msg = "{$deleted} gudang dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih punya stok)" : '');
        return back()->with('success', $msg);
    }
}
