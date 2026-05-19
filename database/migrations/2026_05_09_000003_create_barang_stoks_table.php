<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->integer('stok')->default(0);
            $table->integer('min_stok')->nullable();
            $table->timestamps();

            $table->unique(['barang_id', 'gudang_id']);
            $table->index('gudang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_stoks');
    }
};
