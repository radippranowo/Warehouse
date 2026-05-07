<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok_mutasis', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('approved')
                ->after('total_value');
            $table->foreignId('approved_by')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('stok_mutasis', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
