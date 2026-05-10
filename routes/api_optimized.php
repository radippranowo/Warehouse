<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BarangApiController;
use App\Http\Controllers\Api\LaporanApiController;

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

    // Laporan Endpoints
    Route::prefix('laporan')->group(function () {
        Route::get('/keuntungan', [LaporanApiController::class, 'keuntungan']); // Laporan keuntungan
        Route::get('/stok', [LaporanApiController::class, 'stok']); // Laporan stok
    });

    // Dashboard Endpoints
    Route::get('/dashboard/stats', [LaporanApiController::class, 'dashboardStats']); // Dashboard stats
});

/*
|--------------------------------------------------------------------------
| API Documentation
|--------------------------------------------------------------------------
|
| GET /api/v1/barang
| - Query params: per_page, search, with_relations
| - Response: Paginated list of barang
| - Cache: No
| - Performance: ~100-200ms
|
| GET /api/v1/barang/search?q=ABC
| - Query params: q (search term), limit
| - Response: Array of barang for autocomplete
| - Cache: 5 minutes
| - Performance: ~50ms (cached), ~100ms (uncached)
|
| GET /api/v1/barang/masters
| - Response: Categories, merks, groups, gudangs
| - Cache: 1 hour
| - Performance: ~10ms (cached), ~150ms (uncached)
|
| GET /api/v1/barang/{id}
| - Response: Detail barang with stok per gudang
| - Cache: No
| - Performance: ~80-150ms
|
| GET /api/v1/barang/{id}/stok
| - Query params: gudang_id (optional)
| - Response: Stok per gudang
| - Cache: No
| - Performance: ~50-100ms
|
| GET /api/v1/laporan/keuntungan
| - Query params: per_page, gudang_id, start_date, end_date
| - Response: Paginated profit report with summary
| - Cache: Summary cached for 5 minutes
| - Performance: ~200-500ms (with indexes)
|
| GET /api/v1/laporan/stok
| - Query params: gudang_id (required), low_only
| - Response: Stock report per warehouse
| - Cache: No
| - Performance: ~150-300ms
|
| GET /api/v1/dashboard/stats
| - Response: Dashboard statistics
| - Cache: 1 minute
| - Performance: ~20ms (cached), ~200ms (uncached)
|
*/
