<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('part_number')->nullable()->unique();
            $table->string('nama_barang');

            $table->string('category_code');
            $table->foreign('category_code')->references('kode_category')->on('categories');

            $table->string('merk_code');
            $table->foreign('merk_code')->references('kode_merk')->on('merks');

            $table->string('group_code');
            $table->foreign('group_code')->references('kode_group')->on('groups');

            $table->string('satuan', 20)->default('pcs');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->integer('min_stok')->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->fullText('nama_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
