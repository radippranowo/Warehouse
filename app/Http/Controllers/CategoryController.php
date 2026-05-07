<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CategoryController extends Controller
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

        $query = Category::query()
            ->select(['id', 'kode_category', 'nama_category'])
            ->withCount('barangs');

        if ($search !== '') {
            $like = $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_category', 'like', $like)
                  ->orWhere('kode_category', 'like', $like);
            });
        }

        $categories = $query->latest('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters'    => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(CategoryRequest $request)
    {
        Category::create($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Kategori berhasil ditambah');
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        $this->flushMasters();
        return back()->with('success', 'Kategori berhasil diubah');
    }

    public function destroy(Category $category)
    {
        if ($category->barangs()->exists()) {
            return back()->with('error', "Kategori '{$category->nama_category}' masih digunakan barang.");
        }
        $category->delete();
        $this->flushMasters();
        return back()->with('success', 'Kategori berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $rows = Category::whereIn('id', $data['ids'])->withCount('barangs')->get();
        $deletable = $rows->where('barangs_count', 0);
        $blockedCount = $rows->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Semua kategori yang dipilih masih digunakan barang.');
        }

        $deleted = Category::whereIn('id', $deletable->pluck('id'))->delete();
        $this->flushMasters();
        $msg = "{$deleted} kategori dihapus"
            . ($blockedCount ? ", {$blockedCount} dilewati (masih digunakan)" : '');
        return back()->with('success', $msg);
    }
}
