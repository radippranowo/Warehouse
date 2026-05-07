<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubCategoryRequest;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SubCategoryController extends Controller
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

        $query = SubCategory::query()
            ->select(['id', 'kode_sub_category', 'nama_sub_category'])
            ->withCount('barangs');

        if ($search !== '') {
            $like = $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_sub_category', 'like', $like)
                  ->orWhere('kode_sub_category', 'like', $like);
            });
        }

        $subCategories = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('SubCategory/Index', [
            'subCategories' => $subCategories,
            'filters'       => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(SubCategoryRequest $request)
    {
        SubCategory::create($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Sub Kategori berhasil ditambah');
    }

    public function update(SubCategoryRequest $request, SubCategory $subCategory)
    {
        $subCategory->update($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Sub Kategori berhasil diubah');
    }

    public function destroy(SubCategory $subCategory)
    {
        if ($subCategory->barangs()->exists()) {
            return back()->with('error', "Sub Kategori '{$subCategory->nama_sub_category}' masih digunakan barang.");
        }
        $subCategory->delete();
        $this->flushMasters();
        return back()->with('success', 'Sub Kategori berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:sub_categories,id'],
        ]);

        $rows = SubCategory::whereIn('id', $data['ids'])->withCount('barangs')->get();
        $deletable = $rows->where('barangs_count', 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua sub kategori yang dipilih masih digunakan barang.');
        }

        $deleted = SubCategory::whereIn('id', $deletable->pluck('id'))->delete();
        $this->flushMasters();
        $msg = "{$deleted} sub kategori dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih digunakan)" : '');
        return back()->with('success', $msg);
    }
}
