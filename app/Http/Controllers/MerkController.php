<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerkRequest;
use App\Models\Merk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MerkController extends Controller
{
    private function flushMasters(): void
    {
        Cache::forget('barang.masters');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));

        $query = Merk::query()
            ->select(['id', 'kode_merk', 'nama_merk'])
            ->withCount('barangs');

        if ($search !== '') {
            $like = $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_merk', 'like', $like)
                  ->orWhere('kode_merk', 'like', $like);
            });
        }

        $merks = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Merk/Index', [
            'merks'   => $merks,
            'filters' => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(MerkRequest $request)
    {
        Merk::create($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Merk berhasil ditambah');
    }

    public function update(MerkRequest $request, Merk $merk)
    {
        $merk->update($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Merk berhasil diubah');
    }

    public function destroy(Merk $merk)
    {
        if ($merk->barangs()->exists()) {
            return back()->with('error', "Merk '{$merk->nama_merk}' masih digunakan barang.");
        }
        $merk->delete();
        $this->flushMasters();
        return back()->with('success', 'Merk berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:merks,id'],
        ]);

        $rows = Merk::whereIn('id', $data['ids'])->withCount('barangs')->get();
        $deletable = $rows->where('barangs_count', 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua merk yang dipilih masih digunakan barang.');
        }

        $deleted = Merk::whereIn('id', $deletable->pluck('id'))->delete();
        $this->flushMasters();
        $msg = "{$deleted} merk dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih digunakan)" : '');
        return back()->with('success', $msg);
    }
}
