<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_mutasi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_mutasi_id')->constrained('stok_mutasis')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();

            $table->integer('qty');
            $table->decimal('harga_satuan', 15, 2)->nullable();

            // Snapshot stok untuk audit per-row (nilai stok di gudang asal sebelum & sesudah).
            // Untuk transfer, ini stok di gudang asal; gudang tujuan tidak di-snapshot di sini.
            $table->integer('stok_sebelum')->default(0);
            $table->integer('stok_sesudah')->default(0);

            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('barang_id');
            $table->index('stok_mutasi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_mutasi_items');
    }
};
