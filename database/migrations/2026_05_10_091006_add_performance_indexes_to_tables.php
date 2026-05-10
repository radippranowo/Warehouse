<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Priority 1 - CRITICAL: Indexes untuk Laporan Keuntungan
        Schema::table('stok_mutasis', function (Blueprint $table) {
            // Composite index untuk query laporan keuntungan
            $table->index(['tipe', 'status', 'tanggal', 'gudang_id'], 'idx_profit_report');
            
            // Index untuk riwayat/list dengan sorting
            $table->index(['status', 'tanggal', 'created_at'], 'idx_list_sorting');
        });

        Schema::table('stok_mutasi_items', function (Blueprint $table) {
            // Index untuk join dengan barang
            $table->index(['barang_id', 'stok_mutasi_id'], 'idx_barang_mutasi');
        });

        // Priority 2 - HIGH: Indexes untuk Stok Queries
        Schema::table('barang_stoks', function (Blueprint $table) {
            // Composite index untuk query stok per gudang
            $table->index(['gudang_id', 'barang_id', 'stok'], 'idx_gudang_barang_stok');
        });

        // Priority 3 - MEDIUM: Indexes untuk Dashboard & Master Data
        Schema::table('barangs', function (Blueprint $table) {
            // Index untuk filter is_active
            $table->index('is_active', 'idx_barang_active');
            
            // Index untuk search by kode
            $table->index('kode_barang', 'idx_barang_kode');
        });

        Schema::table('gudangs', function (Blueprint $table) {
            // Index untuk filter is_active
            $table->index('is_active', 'idx_gudang_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_mutasis', function (Blueprint $table) {
            $table->dropIndex('idx_profit_report');
            $table->dropIndex('idx_list_sorting');
        });

        Schema::table('stok_mutasi_items', function (Blueprint $table) {
            $table->dropIndex('idx_barang_mutasi');
        });

        Schema::table('barang_stoks', function (Blueprint $table) {
            $table->dropIndex('idx_gudang_barang_stok');
        });

        Schema::table('barangs', function (Blueprint $table) {
            $table->dropIndex('idx_barang_active');
            $table->dropIndex('idx_barang_kode');
        });

        Schema::table('gudangs', function (Blueprint $table) {
            $table->dropIndex('idx_gudang_active');
        });
    }
};
