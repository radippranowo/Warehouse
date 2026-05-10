<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BarangApiController;
use App\Http\Controllers\Api\LaporanApiController;
use App\Http\Controllers\Api\StokApiController;
use App\Http\Controllers\Api\MutasiApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Optimized Endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Barang Endpoints
    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangApiController::class, 'index']); // List barang
        Route::get('/search', [BarangApiController::class, 'search']); // Autocomplete search
        Route::get('/masters', [BarangApiController::class, 'masters']); // Master data (cached)
        Route::get('/{id}', [BarangApiController::class, 'show']); // Detail barang
        Route::get('/{id}/stok', [BarangApiController::class, 'stok']); // Stok per gudang
    });

    // Stok Endpoints
    Route::prefix('stok')->group(function () {
        Route::get('/', [StokApiController::class, 'index']); // List stok per gudang
        Route::get('/summary', [StokApiController::class, 'summary']); // Summary stok
        Route::get('/low-stock', [StokApiController::class, 'lowStock']); // Stok rendah
    });

    // Mutasi/Transaksi Endpoints
    Route::prefix('mutasi')->group(function () {
        Route::get('/', [MutasiApiController::class, 'index']); // List transaksi
        Route::get('/{id}', [MutasiApiController::class, 'show']); // Detail transaksi
        Route::post('/', [MutasiApiController::class, 'store']); // Create transaksi
        Route::put('/{id}/approve', [MutasiApiController::class, 'approve']); // Approve
        Route::put('/{id}/reject', [MutasiApiController::class, 'reject']); // Reject
    });

    // Laporan Endpoints
    Route::prefix('laporan')->group(function () {
        Route::get('/keuntungan', [LaporanApiController::class, 'keuntungan']); // Laporan keuntungan
        Route::get('/stok', [LaporanApiController::class, 'stok']); // Laporan stok
        Route::get('/mutasi', [LaporanApiController::class, 'mutasi']); // Laporan mutasi
    });

    // Dashboard Endpoints
    Route::get('/dashboard/stats', [LaporanApiController::class, 'dashboardStats']); // Dashboard stats
});

// Legacy endpoint (backward compatibility)
Route::get('/barangs', [BarangApiController::class, 'index']);
