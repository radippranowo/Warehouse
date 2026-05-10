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
        // Indexes untuk tabel barangs (untuk search & filter)
        Schema::table('barangs', function (Blueprint $table) {
            // Cek apakah index sudah ada, jika belum baru tambahkan
            if (!$this->indexExists('barangs', 'idx_barangs_kode')) {
                $table->index('kode_barang', 'idx_barangs_kode');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_nama')) {
                $table->index('nama_barang', 'idx_barangs_nama');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_part_number')) {
                $table->index('part_number', 'idx_barangs_part_number');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_category')) {
                $table->index('category_code', 'idx_barangs_category');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_merk')) {
                $table->index('merk_code', 'idx_barangs_merk');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_group')) {
                $table->index('group_code', 'idx_barangs_group');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_sub_category')) {
                $table->index('sub_category_code', 'idx_barangs_sub_category');
            }
            if (!$this->indexExists('barangs', 'idx_barangs_cat_merk')) {
                $table->index(['category_code', 'merk_code'], 'idx_barangs_cat_merk');
            }
        });

        // Indexes untuk tabel barang_stoks
        Schema::table('barang_stoks', function (Blueprint $table) {
            if (!$this->indexExists('barang_stoks', 'idx_stoks_barang_gudang')) {
                $table->index(['barang_id', 'gudang_id'], 'idx_stoks_barang_gudang');
            }
            if (!$this->indexExists('barang_stoks', 'idx_stoks_gudang')) {
                $table->index('gudang_id', 'idx_stoks_gudang');
            }
        });

        // Indexes untuk tabel stok_mutasis
        Schema::table('stok_mutasis', function (Blueprint $table) {
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_nomor')) {
                $table->index('nomor_mutasi', 'idx_mutasis_nomor');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_tipe')) {
                $table->index('tipe', 'idx_mutasis_tipe');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_tanggal')) {
                $table->index('tanggal', 'idx_mutasis_tanggal');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_gudang')) {
                $table->index('gudang_id', 'idx_mutasis_gudang');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_supplier')) {
                $table->index('supplier_id', 'idx_mutasis_supplier');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_status')) {
                $table->index('status', 'idx_mutasis_status');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_tipe_tanggal')) {
                $table->index(['tipe', 'tanggal'], 'idx_mutasis_tipe_tanggal');
            }
            if (!$this->indexExists('stok_mutasis', 'idx_mutasis_gudang_status')) {
                $table->index(['gudang_id', 'status'], 'idx_mutasis_gudang_status');
            }
        });

        // Indexes untuk tabel stok_mutasi_items
        Schema::table('stok_mutasi_items', function (Blueprint $table) {
            if (!$this->indexExists('stok_mutasi_items', 'idx_mutasi_items_mutasi_barang')) {
                $table->index(['stok_mutasi_id', 'barang_id'], 'idx_mutasi_items_mutasi_barang');
            }
            if (!$this->indexExists('stok_mutasi_items', 'idx_mutasi_items_barang')) {
                $table->index('barang_id', 'idx_mutasi_items_barang');
            }
        });

        // Indexes untuk tabel suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            if (!$this->indexExists('suppliers', 'idx_suppliers_kode')) {
                $table->index('kode_supplier', 'idx_suppliers_kode');
            }
            if (!$this->indexExists('suppliers', 'idx_suppliers_nama')) {
                $table->index('nama_supplier', 'idx_suppliers_nama');
            }
        });

        // Indexes untuk tabel gudangs
        Schema::table('gudangs', function (Blueprint $table) {
            if (!$this->indexExists('gudangs', 'idx_gudangs_kode')) {
                $table->index('kode_gudang', 'idx_gudangs_kode');
            }
            if (!$this->indexExists('gudangs', 'idx_gudangs_nama')) {
                $table->index('nama_gudang', 'idx_gudangs_nama');
            }
        });

        // Indexes untuk tabel categories
        Schema::table('categories', function (Blueprint $table) {
            if (!$this->indexExists('categories', 'idx_categorys_nama')) {
                $table->index('nama_category', 'idx_categorys_nama');
            }
        });

        // Indexes untuk tabel merks
        Schema::table('merks', function (Blueprint $table) {
            if (!$this->indexExists('merks', 'idx_merks_nama')) {
                $table->index('nama_merk', 'idx_merks_nama');
            }
        });

        // Indexes untuk tabel groups
        Schema::table('groups', function (Blueprint $table) {
            if (!$this->indexExists('groups', 'idx_groups_nama')) {
                $table->index('nama_group', 'idx_groups_nama');
            }
        });

        // Indexes untuk tabel sub_categories
        Schema::table('sub_categories', function (Blueprint $table) {
            if (!$this->indexExists('sub_categories', 'idx_sub_categories_nama')) {
                $table->index('nama_sub_category', 'idx_sub_categories_nama');
            }
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $index]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropIndex('idx_barangs_kode');
            $table->dropIndex('idx_barangs_nama');
            $table->dropIndex('idx_barangs_part_number');
            $table->dropIndex('idx_barangs_category');
            $table->dropIndex('idx_barangs_merk');
            $table->dropIndex('idx_barangs_group');
            $table->dropIndex('idx_barangs_sub_category');
            $table->dropIndex('idx_barangs_cat_merk');
        });

        Schema::table('barang_stoks', function (Blueprint $table) {
            $table->dropIndex('idx_stoks_barang_gudang');
            $table->dropIndex('idx_stoks_gudang');
        });

        Schema::table('stok_mutasis', function (Blueprint $table) {
            $table->dropIndex('idx_mutasis_nomor');
            $table->dropIndex('idx_mutasis_tipe');
            $table->dropIndex('idx_mutasis_tanggal');
            $table->dropIndex('idx_mutasis_gudang');
            $table->dropIndex('idx_mutasis_supplier');
            $table->dropIndex('idx_mutasis_status');
            $table->dropIndex('idx_mutasis_tipe_tanggal');
            $table->dropIndex('idx_mutasis_gudang_status');
        });

        Schema::table('stok_mutasi_items', function (Blueprint $table) {
            $table->dropIndex('idx_mutasi_items_mutasi_barang');
            $table->dropIndex('idx_mutasi_items_barang');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_kode');
            $table->dropIndex('idx_suppliers_nama');
        });

        Schema::table('gudangs', function (Blueprint $table) {
            $table->dropIndex('idx_gudangs_kode');
            $table->dropIndex('idx_gudangs_nama');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categorys_nama');
        });

        Schema::table('merks', function (Blueprint $table) {
            $table->dropIndex('idx_merks_nama');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex('idx_groups_nama');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropIndex('idx_sub_categories_nama');
        });
    }
};
