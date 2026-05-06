<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add unique constraint pada kolom nama_* di tabel master.
     *
     * Catatan: kalau ada data duplikat saat ini, migration ini akan GAGAL.
     * Pastikan duplikat di-clean dulu sebelum migrate.
     */
    public function up(): void
    {
        Schema::table('merks', function (Blueprint $table) {
            $table->unique('nama_merk');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->unique('nama_group');
        });
    }

    public function down(): void
    {
        Schema::table('merks', function (Blueprint $table) {
            $table->dropUnique(['nama_merk']);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique(['nama_group']);
        });
    }
};
