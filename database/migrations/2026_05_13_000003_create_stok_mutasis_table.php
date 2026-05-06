<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header: 1 transaksi = 1 row di sini, 1..n barang di stok_mutasi_items.
        Schema::create('stok_mutasis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_mutasi')->unique();
            $table->dateTime('tanggal');
            $table->enum('tipe', ['in', 'out', 'transfer', 'adjust']);

            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('gudang_tujuan_id')->nullable()->constrained('gudangs')->nullOnDelete();

            $table->string('referensi')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Denormalized agregat untuk list view (hindari subquery di index).
            $table->integer('total_qty')->default(0);
            $table->decimal('total_value', 18, 2)->default(0);

            $table->timestamps();

            $table->index('tanggal');
            $table->index('tipe');
            $table->index('gudang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_mutasis');
    }
};
