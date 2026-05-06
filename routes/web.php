<?php

use Illuminate\Support\Facades\Route;

// Inertia imports (dinonaktifkan — diganti Livewire)
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MerkController;
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
 Route::put('/category/{category}',    [CategoryController::class, 'update'])->name('category.update');
 Route::delete('/category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
 Route::get('/merk',           [MerkController::class, 'index'])->name('merk.index');
 Route::post('/merk',          [MerkController::class, 'store'])->name('merk.store');
 Route::put('/merk/{merk}',    [MerkController::class, 'update'])->name('merk.update');
 Route::delete('/merk/{merk}', [MerkController::class, 'destroy'])->name('merk.destroy');
 Route::get('/group',            [GroupController::class, 'index'])->name('group.index');
 Route::post('/group',           [GroupController::class, 'store'])->name('group.store');
 Route::put('/group/{group}',    [GroupController::class, 'update'])->name('group.update');
 Route::delete('/group/{group}', [GroupController::class, 'destroy'])->name('group.destroy');
//  Smoke test
 Route::get('/ping-inertia', fn () => Inertia::render('Ping', ['message' => 'Inertia + Vue 3 hidup!']));
//  Modul Barang — Inertia + Vue
 Route::get('/barang',             [BarangController::class, 'index'])->name('barang.index');
 Route::get('/barang/create',      [BarangController::class, 'create'])->name('barang.create');
 Route::get('/barang/masters',     [BarangController::class, 'masters'])->name('barang.masters');
 Route::post('/barang/validate',   [BarangController::class, 'validateLive'])->name('barang.validate');
 Route::post('/barang',            [BarangController::class, 'store'])->name('barang.store');
 Route::put('/barang/{barang}',    [BarangController::class, 'update'])->name('barang.update');
 Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');

// Logout (login flow belum aktif — endpoint disediakan agar tombol Logout tidak 404)
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/dashboard');
})->name('logout');

