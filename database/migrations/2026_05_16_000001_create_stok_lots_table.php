<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('stok_mutasi_id')->constrained('stok_mutasis')->cascadeOnDelete();
            $table->foreignId('stok_mutasi_item_id')->constrained('stok_mutasi_items')->cascadeOnDelete();

            $table->dateTime('tanggal');
            $table->integer('qty_in');
            $table->integer('qty_sisa');
            $table->decimal('harga_beli', 15, 2);

            $table->timestamps();

            // FIFO retrieval: cari lots dengan sisa > 0 untuk barang+gudang, urut tanggal lalu id.
            $table->index(['barang_id', 'gudang_id', 'qty_sisa', 'tanggal', 'id'], 'idx_fifo');
            $table->index('stok_mutasi_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_lots');
    }
};
