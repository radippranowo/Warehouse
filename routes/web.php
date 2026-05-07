<?php

use Illuminate\Support\Facades\Route;

// Inertia imports (dinonaktifkan — diganti Livewire)
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
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Route::redirect('/', '/dashboard');

// =========================================================================
// LIVEWIRE ROUTES (AKTIF)
// =========================================================================

// Dashboard
// Route::livewire('/dashboard', 'dashboard.index')->name('dashboard.index');

// // Master data
// Route::livewire('/category', 'category.index')->name('category.index');
// Route::livewire('/merk',     'merk.index')->name('merk.index');
// Route::livewire('/group',    'group.index')->name('group.index');

// // Modul Barang
// Route::livewire('/barang',        'barang.index')->name('barang.index');
// Route::livewire('/barang/create', 'barang.create')->name('barang.create');
// Route::livewire('/barang/import', 'barang.import')->name('barang.import');

// // Transaksi & PR
// Route::prefix('barangmasuk')->group(function () {
//     Route::livewire('/',       'barangmasuk.index')->name('barangmasuk.index');
//     Route::livewire('/create', 'barangmasuk.create')->name('barangmasuk.create');
// });

// Route::prefix('barangkeluar')->group(function () {
//     Route::livewire('/',       'barangkeluar.index')->name('barangkeluar.index');
//     Route::livewire('/create', 'barangkeluar.create')->name('barangkeluar.create');
// });

// Route::prefix('pr')->group(function () {
//     Route::livewire('/',       'pr.index')->name('pr.index');
//     Route::livewire('/create', 'pr.create')->name('pr.create');
// });



//  Dashboard
 Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
//  Master data
 Route::get('/category',               [CategoryController::class, 'index'])->name('category.index');
 Route::post('/category',              [CategoryController::class, 'store'])->name('category.store');
 Route::delete('/category/bulk',       [CategoryController::class, 'bulkDestroy'])->name('category.bulk-destroy');
 Route::put('/category/{category}',    [CategoryController::class, 'update'])->name('category.update');
 Route::delete('/category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
 Route::get('/sub-category',                       [SubCategoryController::class, 'index'])->name('sub-category.index');
 Route::post('/sub-category',                      [SubCategoryController::class, 'store'])->name('sub-category.store');
 Route::delete('/sub-category/bulk',               [SubCategoryController::class, 'bulkDestroy'])->name('sub-category.bulk-destroy');
 Route::put('/sub-category/{sub_category}',        [SubCategoryController::class, 'update'])->name('sub-category.update');
 Route::delete('/sub-category/{sub_category}',     [SubCategoryController::class, 'destroy'])->name('sub-category.destroy');
 Route::get('/merk',           [MerkController::class, 'index'])->name('merk.index');
 Route::post('/merk',          [MerkController::class, 'store'])->name('merk.store');
 Route::delete('/merk/bulk',   [MerkController::class, 'bulkDestroy'])->name('merk.bulk-destroy');
 Route::put('/merk/{merk}',    [MerkController::class, 'update'])->name('merk.update');
 Route::delete('/merk/{merk}', [MerkController::class, 'destroy'])->name('merk.destroy');
 Route::get('/group',            [GroupController::class, 'index'])->name('group.index');
 Route::post('/group',           [GroupController::class, 'store'])->name('group.store');
 Route::delete('/group/bulk',    [GroupController::class, 'bulkDestroy'])->name('group.bulk-destroy');
 Route::put('/group/{group}',    [GroupController::class, 'update'])->name('group.update');
 Route::delete('/group/{group}', [GroupController::class, 'destroy'])->name('group.destroy');
//  Smoke test
 Route::get('/ping-inertia', fn () => Inertia::render('Ping', ['message' => 'Inertia + Vue 3 hidup!']));
//  Modul Barang — Inertia + Vue
 Route::get('/barang',             [BarangController::class, 'index'])->name('barang.index');
 Route::get('/barang/create',      [BarangController::class, 'create'])->name('barang.create');
 Route::get('/barang/import',      [BarangController::class, 'showImport'])->name('barang.import');
 Route::post('/barang/import',     [BarangController::class, 'processImport'])->name('barang.import.process');
 Route::post('/barang/clear',      [BarangController::class, 'clearAll'])->name('barang.clear');
 Route::get('/barang/masters',     [BarangController::class, 'masters'])->name('barang.masters');
 Route::post('/barang/validate',   [BarangController::class, 'validateLive'])->name('barang.validate');
 Route::post('/barang',            [BarangController::class, 'store'])->name('barang.store');
 Route::delete('/barang/bulk',     [BarangController::class, 'bulkDestroy'])->name('barang.bulk-destroy');
 Route::get('/barang/{barang}',    [BarangController::class, 'show'])->name('barang.show');
 Route::put('/barang/{barang}',    [BarangController::class, 'update'])->name('barang.update');
 Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');

// Gudang
 Route::get('/gudang',            [GudangController::class, 'index'])->name('gudang.index');
 Route::post('/gudang',           [GudangController::class, 'store'])->name('gudang.store');
 Route::delete('/gudang/bulk',    [GudangController::class, 'bulkDestroy'])->name('gudang.bulk-destroy');
 Route::put('/gudang/{gudang}',   [GudangController::class, 'update'])->name('gudang.update');
 Route::delete('/gudang/{gudang}',[GudangController::class, 'destroy'])->name('gudang.destroy');

// Mutasi Stok
 Route::get('/mutasi',            [MutasiController::class, 'index'])->name('mutasi.index');
 Route::get('/mutasi/create',     [MutasiController::class, 'create'])->name('mutasi.create');
 Route::post('/mutasi',           [MutasiController::class, 'store'])->name('mutasi.store');
 Route::get('/mutasi/{mutasi}',          [MutasiController::class, 'show'])->name('mutasi.show');
 Route::get('/mutasi/{mutasi}/print',    [MutasiController::class, 'print'])->name('mutasi.print');
 Route::post('/mutasi/{mutasi}/approve', [MutasiController::class, 'approve'])->name('mutasi.approve');
 Route::post('/mutasi/{mutasi}/reject',  [MutasiController::class, 'reject'])->name('mutasi.reject');

// Laporan Stok per Gudang
 Route::get('/stok',              [StokController::class, 'index'])->name('stok.index');
 Route::post('/stok/import',      [ImportStokController::class, 'processImport'])->name('stok.import.process');
 Route::post('/stok/clear',       [ImportStokController::class, 'clearStok'])->name('stok.clear');
 Route::get('/stok/{barang}',     [BarangController::class, 'show'])->name('stok.show');

// Logout (login flow belum aktif — endpoint disediakan agar tombol Logout tidak 404)
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/dashboard');
})->name('logout');

