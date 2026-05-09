<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\ImportStokController;
use App\Http\Controllers\MerkController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Auth;

// =========================================================================
// INERTIA ROUTES
// =========================================================================

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Master Data — Category
Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
Route::put('/category/{category}', [CategoryController::class, 'update'])->name('category.update');
Route::delete('/category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');

// Master Data — Sub Category
Route::get('/sub-category', [SubCategoryController::class, 'index'])->name('sub-category.index');
Route::post('/sub-category', [SubCategoryController::class, 'store'])->name('sub-category.store');
Route::put('/sub-category/{subCategory}', [SubCategoryController::class, 'update'])->name('sub-category.update');
Route::delete('/sub-category/{subCategory}', [SubCategoryController::class, 'destroy'])->name('sub-category.destroy');

// Master Data — Merk
Route::get('/merk', [MerkController::class, 'index'])->name('merk.index');
Route::post('/merk', [MerkController::class, 'store'])->name('merk.store');
Route::put('/merk/{merk}', [MerkController::class, 'update'])->name('merk.update');
Route::delete('/merk/{merk}', [MerkController::class, 'destroy'])->name('merk.destroy');

// Master Data — Group
Route::get('/group', [GroupController::class, 'index'])->name('group.index');
Route::post('/group', [GroupController::class, 'store'])->name('group.store');
Route::put('/group/{group}', [GroupController::class, 'update'])->name('group.update');
Route::delete('/group/{group}', [GroupController::class, 'destroy'])->name('group.destroy');

// Gudang
Route::get('/gudang', [GudangController::class, 'index'])->name('gudang.index');
Route::post('/gudang', [GudangController::class, 'store'])->name('gudang.store');
Route::put('/gudang/{gudang}', [GudangController::class, 'update'])->name('gudang.update');
Route::delete('/gudang/{gudang}', [GudangController::class, 'destroy'])->name('gudang.destroy');
Route::delete('/gudang/bulk', [GudangController::class, 'bulkDestroy'])->name('gudang.bulk-destroy');

// Supplier
Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('supplier.update');
Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('supplier.destroy');
Route::delete('/supplier/bulk', [SupplierController::class, 'bulkDestroy'])->name('supplier.bulk-destroy');
Route::patch('/supplier/{supplier}/toggle', [SupplierController::class, 'toggleStatus'])->name('supplier.toggle');

// Modul Barang
Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
Route::get('/barang/create', [BarangController::class, 'create'])->name('barang.create');
Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
Route::get('/barang/import', [BarangController::class, 'import'])->name('barang.import');
Route::post('/barang/import', [BarangController::class, 'processImport'])->name('barang.import.process');
Route::get('/barang/{barang}', [BarangController::class, 'show'])->name('barang.show');
Route::put('/barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');

// Transaksi - Barang Masuk
Route::get('/barang-masuk', [MutasiController::class, 'pemasukan'])->name('barang-masuk.form');

// Transaksi - Barang Keluar
Route::get('/barang-keluar', [MutasiController::class, 'pengeluaran'])->name('barang-keluar.form');

// Transaksi - Transfer (hidden)
Route::get('/transfer-gudang', [MutasiController::class, 'transfer'])->name('transfer-gudang.form');

// Transaksi - Penyesuaian Stok
Route::get('/penyesuaian-stok', [MutasiController::class, 'penyesuaian'])->name('penyesuaian-stok.form');

Route::post('/mutasi', [MutasiController::class, 'store'])->name('mutasi.store');
Route::get('/transaksi/{mutasi}', [MutasiController::class, 'show'])->name('transaksi.show');
Route::delete('/transaksi/{mutasi}', [MutasiController::class, 'destroy'])->name('transaksi.destroy');
Route::get('/transaksi/{mutasi}/print', [MutasiController::class, 'print'])->name('transaksi.print');
Route::post('/transaksi/{mutasi}/approve', [MutasiController::class, 'approve'])->name('transaksi.approve');
Route::post('/transaksi/{mutasi}/reject', [MutasiController::class, 'reject'])->name('transaksi.reject');

// Riwayat Mutasi
Route::get('/riwayat/print', [MutasiController::class, 'printList'])->name('riwayat.print');
Route::get('/riwayat/semua', [MutasiController::class, 'riwayatSemua'])->name('riwayat.semua');
Route::get('/riwayat/barang-masuk', [MutasiController::class, 'riwayatPemasukan'])->name('riwayat.barang-masuk');
Route::get('/riwayat/barang-keluar', [MutasiController::class, 'riwayatPengeluaran'])->name('riwayat.barang-keluar');
Route::get('/riwayat/transfer', [MutasiController::class, 'riwayatTransfer'])->name('riwayat.transfer');
Route::get('/riwayat/penyesuaian', [MutasiController::class, 'riwayatPenyesuaian'])->name('riwayat.penyesuaian');

// Laporan Stok per Gudang
Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
Route::get('/stok/{barang}', [StokController::class, 'show'])->name('stok.show');
Route::post('/stok/import', [ImportStokController::class, 'processImport'])->name('stok.import.process');
Route::post('/stok/clear', [ImportStokController::class, 'clearStok'])->name('stok.clear');

// Stok Export & Print
Route::get('/stok/print', function () {
    return redirect()->route('stok.index');
})->name('stok.print');
Route::get('/stok/export', function () {
    $gudangId = request('gudang_id');
    $search   = request('search');
    $lowOnly  = request('low_only');
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\StokGudangExport($gudangId, $search, $lowOnly),
        'stok-gudang.xlsx'
    );
})->name('stok.export');

// Logout
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/dashboard');
})->name('logout');

// Redirect root to dashboard
Route::redirect('/', '/dashboard');

