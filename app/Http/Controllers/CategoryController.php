<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));

        $query = Category::query()
            ->select(['id', 'kode_category', 'nama_category'])
            ->withCount('barangs');

        if ($search !== '') {
            $like = '%' . $search . '%';
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
        return back()->with('success', 'Kategori berhasil ditambah');
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return back()->with('success', 'Kategori berhasil diubah');
    }

    public function destroy(Category $category)
    {
        if ($category->barangs()->exists()) {
            return back()->with('error', "Kategori '{$category->nama_category}' masih digunakan barang.");
        }
        $category->delete();
        return back()->with('success', 'Kategori berhasil dihapus');
    }
}
