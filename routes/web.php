<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\ImportStokController;
use App\Http\Controllers\LaporanKeuntunganController;
use App\Http\Controllers\MerkController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

// =========================================================================
// INERTIA ROUTES WITH RATE LIMITING & AUTH
// =========================================================================

// Wrap all protected routes with auth middleware
Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('dashboard.index');

    // Master Data — Category
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
        Route::put('/category/{category}', [CategoryController::class, 'update'])->name('category.update');
        Route::delete('/category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
    });

    // Master Data — Sub Category
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/sub-category', [SubCategoryController::class, 'index'])->name('sub-category.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/sub-category', [SubCategoryController::class, 'store'])->name('sub-category.store');
        Route::put('/sub-category/{subCategory}', [SubCategoryController::class, 'update'])->name('sub-category.update');
        Route::delete('/sub-category/{subCategory}', [SubCategoryController::class, 'destroy'])->name('sub-category.destroy');
    });

    // Master Data — Merk
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/merk', [MerkController::class, 'index'])->name('merk.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/merk', [MerkController::class, 'store'])->name('merk.store');
        Route::put('/merk/{merk}', [MerkController::class, 'update'])->name('merk.update');
        Route::delete('/merk/{merk}', [MerkController::class, 'destroy'])->name('merk.destroy');
    });

    // Master Data — Group
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/group', [GroupController::class, 'index'])->name('group.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/group', [GroupController::class, 'store'])->name('group.store');
        Route::put('/group/{group}', [GroupController::class, 'update'])->name('group.update');
        Route::delete('/group/{group}', [GroupController::class, 'destroy'])->name('group.destroy');
    });

    // Gudang
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/gudang', [GudangController::class, 'index'])->name('gudang.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/gudang', [GudangController::class, 'store'])->name('gudang.store');
        Route::put('/gudang/{gudang}', [GudangController::class, 'update'])->name('gudang.update');
        Route::delete('/gudang/{gudang}', [GudangController::class, 'destroy'])->name('gudang.destroy');
        Route::delete('/gudang/bulk', [GudangController::class, 'bulkDestroy'])->name('gudang.bulk-destroy');
    });

    // Supplier
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
        Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('supplier.destroy');
        Route::delete('/supplier/bulk', [SupplierController::class, 'bulkDestroy'])->name('supplier.bulk-destroy');
        Route::patch('/supplier/{supplier}/toggle', [SupplierController::class, 'toggleStatus'])->name('supplier.toggle');
    });

    // Modul Barang
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::get('/barang/import', [BarangController::class, 'showImport'])->name('barang.import');
        Route::get('/barang/{barang}', [BarangController::class, 'show'])->name('barang.show');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
        Route::put('/barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
        Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
        Route::delete('/barang/bulk', [BarangController::class, 'bulkDestroy'])->name('barang.bulk-destroy');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/barang/import', [BarangController::class, 'processImport'])->name('barang.import.process');
        Route::post('/barang/clear', [BarangController::class, 'clearAll'])->name('barang.clear');
    });

    // Transaksi - Forms
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/barang-masuk', [MutasiController::class, 'pemasukan'])->name('barang-masuk.form');
        Route::get('/barang-keluar', [MutasiController::class, 'pengeluaran'])->name('barang-keluar.form');
        Route::get('/transfer-gudang', [MutasiController::class, 'transfer'])->name('transfer-gudang.form');
        Route::get('/penyesuaian-stok', [MutasiController::class, 'penyesuaian'])->name('penyesuaian-stok.form');
    });

    // Transaksi - Actions
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/mutasi', [MutasiController::class, 'store'])->name('mutasi.store');
        Route::get('/transaksi/{mutasi}', [MutasiController::class, 'show'])->name('transaksi.show');
        Route::delete('/transaksi/{mutasi}', [MutasiController::class, 'destroy'])->name('transaksi.destroy');
        Route::post('/transaksi/{mutasi}/approve', [MutasiController::class, 'approve'])->name('transaksi.approve');
        Route::post('/transaksi/{mutasi}/reject', [MutasiController::class, 'reject'])->name('transaksi.reject');
    });
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/transaksi/{mutasi}/print', [MutasiController::class, 'print'])->name('transaksi.print');
    });

    // Riwayat Mutasi
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/riwayat/semua', [MutasiController::class, 'riwayatSemua'])->name('riwayat.semua');
        Route::get('/riwayat/barang-masuk', [MutasiController::class, 'riwayatPemasukan'])->name('riwayat.barang-masuk');
        Route::get('/riwayat/barang-keluar', [MutasiController::class, 'riwayatPengeluaran'])->name('riwayat.barang-keluar');
        Route::get('/riwayat/transfer', [MutasiController::class, 'riwayatTransfer'])->name('riwayat.transfer');
        Route::get('/riwayat/penyesuaian', [MutasiController::class, 'riwayatPenyesuaian'])->name('riwayat.penyesuaian');
    });
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/riwayat/print', [MutasiController::class, 'printList'])->name('riwayat.print');
    });

    // Laporan Stok per Gudang
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
        Route::get('/stok/{barang}', [StokController::class, 'show'])->name('stok.show');
    });
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/stok/import', [ImportStokController::class, 'processImport'])->name('stok.import.process');
        Route::post('/stok/clear', [ImportStokController::class, 'clearStok'])->name('stok.clear');
        Route::get('/stok/export', function () {
            $gudangId = request('gudang_id');
            $search   = request('search');
            $lowOnly  = request('low_only');
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StokGudangExport($gudangId, $search, $lowOnly),
                'stok-gudang.xlsx'
            );
        })->name('stok.export');
    });

    // Stok Print (redirect)
    Route::get('/stok/print', function () {
        return redirect()->route('stok.index');
    })->name('stok.print');

    // Laporan Keuntungan
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/laporan-keuntungan', [LaporanKeuntunganController::class, 'index'])->name('laporan-keuntungan.index');
    });

    // Logout
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

    // =========================================================================
    // USER & ROLE MANAGEMENT (Admin Only)
    // =========================================================================

    // User Management
    Route::middleware('permission:user.view')->group(function () {
        Route::get('/user', [UserController::class, 'index'])->name('user.index');
    });

    Route::middleware('permission:user.create')->group(function () {
        Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
    });

    Route::middleware('permission:user.edit')->group(function () {
        Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::post('/user/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('user.toggle-status');
    });

    Route::middleware('permission:user.delete')->group(function () {
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
    });

    // Role Management
    Route::middleware('permission:role.view')->group(function () {
        Route::get('/role', [RoleController::class, 'index'])->name('role.index');
    });

    Route::middleware('permission:role.create')->group(function () {
        Route::get('/role/create', [RoleController::class, 'create'])->name('role.create');
        Route::post('/role', [RoleController::class, 'store'])->name('role.store');
    });

    Route::middleware('permission:role.edit')->group(function () {
        Route::get('/role/{role}/edit', [RoleController::class, 'edit'])->name('role.edit');
        Route::put('/role/{role}', [RoleController::class, 'update'])->name('role.update');
    });

    Route::middleware('permission:role.delete')->group(function () {
        Route::delete('/role/{role}', [RoleController::class, 'destroy'])->name('role.destroy');
    });
    
}); // End of auth middleware group

// Redirect root to login or dashboard
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard.index') : redirect()->route('login');
});

// Include authentication routes
require __DIR__.'/auth.php';

