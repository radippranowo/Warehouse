<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management service
 * Menghindari duplikasi cache invalidation logic di controllers
 */
class CacheService
{
    /**
     * Cache keys constants
     */
    const BARANG_MASTERS = 'barang.masters';
    const MUTASI_MASTERS = 'mutasi.masters';
    const MUTASI_MASTERS_PEMASUKAN = 'mutasi.masters.pemasukan';
    const MUTASI_MASTERS_PENGELUARAN = 'mutasi.masters';
    const MUTASI_MASTERS_TRANSFER = 'mutasi.masters.transfer';
    const MUTASI_MASTERS_PENYESUAIAN = 'mutasi.masters.penyesuaian';
    
    /**
     * Cache TTL (Time To Live) in seconds
     */
    const TTL_MASTERS = 3600; // 1 hour
    const TTL_SHORT = 300;    // 5 minutes
    const TTL_LONG = 86400;   // 24 hours

    /**
     * Flush barang masters cache
     * Dipanggil saat ada perubahan pada: Category, Merk, Group, SubCategory, Barang
     */
    public static function flushBarangMasters(): void
    {
        Cache::forget(self::BARANG_MASTERS);
        // Flush juga cache transaksi karena barang berubah
        self::flushMutasiMasters();
    }

    /**
     * Flush mutasi masters cache
     * Dipanggil saat ada perubahan pada: Gudang, Supplier, StokMutasi
     */
    public static function flushMutasiMasters(): void
    {
        Cache::forget(self::MUTASI_MASTERS);
        Cache::forget(self::MUTASI_MASTERS_PEMASUKAN);
        Cache::forget(self::MUTASI_MASTERS_PENGELUARAN);
        Cache::forget(self::MUTASI_MASTERS_TRANSFER);
        Cache::forget(self::MUTASI_MASTERS_PENYESUAIAN);
    }

    /**
     * Flush all masters cache
     * Dipanggil saat ada perubahan yang mempengaruhi semua master data
     * Termasuk saat import/reset data
     */
    public static function flushAllMasters(): void
    {
        self::flushBarangMasters();
        self::flushMutasiMasters();
    }

    /**
     * Flush all transaction cache
     * Alias untuk flushMutasiMasters untuk clarity
     */
    public static function flushTransactionCache(): void
    {
        self::flushMutasiMasters();
    }

    /**
     * Get or set barang masters cache
     */
    public static function getBarangMasters(callable $callback): mixed
    {
        return Cache::remember(self::BARANG_MASTERS, self::TTL_MASTERS, $callback);
    }

    /**
     * Get or set mutasi masters cache
     */
    public static function getMutasiMasters(callable $callback): mixed
    {
        return Cache::remember(self::MUTASI_MASTERS, self::TTL_MASTERS, $callback);
    }

    /**
     * Flush cache by pattern
     * Useful untuk flush cache dengan prefix tertentu
     */
    public static function flushByPattern(string $pattern): void
    {
        // Note: Ini hanya work untuk cache driver yang support tags (Redis, Memcached)
        // Untuk file cache, perlu implementasi berbeda
        if (config('cache.default') === 'redis') {
            Cache::tags($pattern)->flush();
        }
    }

    /**
     * Clear all application cache
     * HATI-HATI: Ini akan clear semua cache!
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }
}
