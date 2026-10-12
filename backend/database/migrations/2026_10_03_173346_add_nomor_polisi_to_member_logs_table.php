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
        if (!Schema::hasColumn('member_logs', 'nomor_polisi')) {
            Schema::table('member_logs', function (Blueprint $table) {
                $table->string('nomor_polisi', 20)->nullable()->after('member_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('member_logs', 'nomor_polisi')) {
            Schema::table('member_logs', function (Blueprint $table) {
                $table->dropColumn('nomor_polisi');
            });
        }
    }
};