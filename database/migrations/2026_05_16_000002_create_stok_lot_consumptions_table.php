<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_lot_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_lot_id')->constrained('stok_lots')->cascadeOnDelete();
            $table->foreignId('stok_mutasi_item_id')->constrained('stok_mutasi_items')->cascadeOnDelete();

            $table->integer('qty');
            $table->decimal('harga_beli', 15, 2); // locked dari lot saat dikonsumsi

            $table->timestamps();

            $table->index('stok_mutasi_item_id');
            $table->index('stok_lot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_lot_consumptions');
    }
};
