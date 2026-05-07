<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->string('sub_category_code')->nullable()->after('category_code');
            $table->index('sub_category_code');
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropIndex(['sub_category_code']);
            $table->dropColumn('sub_category_code');
        });
    }
};
