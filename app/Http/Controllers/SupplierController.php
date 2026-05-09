<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SupplierController extends Controller
{
    private function flushMasters(): void
    {
        Cache::forget('mutasi.masters');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $search  = trim((string) $request->input('search', ''));

        $query = Supplier::query()
            ->select(['id', 'kode_supplier', 'nama_supplier', 'kontak', 'telepon', 'alamat', 'keterangan', 'is_active']);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nama_supplier', 'like', $like)
                  ->orWhere('kode_supplier', 'like', $like)
                  ->orWhere('kontak', 'like', $like)
                  ->orWhere('telepon', 'like', $like);
            });
        }

        return Inertia::render('Supplier/Index', [
            'suppliers' => $query->latest('id')->paginate($perPage)->withQueryString(),
            'filters'   => ['search' => $search, 'perPage' => $perPage],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|unique:suppliers,kode_supplier',
            'nama_supplier' => 'required|string|max:255',
            'kontak'        => 'nullable|string|max:255',
            'telepon'       => 'nullable|string|max:255',
            'alamat'        => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        Supplier::create($validated);
        $this->flushMasters();
        return back()->with('success', 'Supplier berhasil ditambahkan');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|unique:suppliers,kode_supplier,' . $supplier->id,
            'nama_supplier' => 'required|string|max:255',
            'kontak'        => 'nullable|string|max:255',
            'telepon'       => 'nullable|string|max:255',
            'alamat'        => 'nullable|string',
            'keterangan'    => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $supplier->update($validated);
        $this->flushMasters();
        return back()->with('success', 'Supplier berhasil diupdate');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->stokMutasis()->exists()) {
            return back()->with('error', "Supplier '{$supplier->nama_supplier}' masih digunakan di transaksi.");
        }
        $supplier->delete();
        $this->flushMasters();
        return back()->with('success', 'Supplier berhasil dihapus');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada supplier yang dipilih');
        }

        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $supplier = Supplier::find($id);
            if (!$supplier) {
                continue;
            }
            if ($supplier->stokMutasis()->exists()) {
                $skipped++;
                continue;
            }
            $supplier->delete();
            $deleted++;
        }

        $this->flushMasters();

        if ($deleted > 0 && $skipped === 0) {
            return back()->with('success', "{$deleted} supplier berhasil dihapus");
        } elseif ($deleted > 0 && $skipped > 0) {
            return back()->with('success', "{$deleted} supplier dihapus, {$skipped} dilewati (masih digunakan)");
        } else {
            return back()->with('error', 'Semua supplier yang dipilih masih digunakan di transaksi');
        }
    }

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->is_active = !$supplier->is_active;
        $supplier->save();
        $this->flushMasters();
        return back()->with('success', 'Status supplier berhasil diubah');
    }
}

