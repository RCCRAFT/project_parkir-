<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('members', 'nomor_polisi')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('nomor_polisi', 20)->nullable()->after('jenis_kendaraan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'nomor_polisi')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('nomor_polisi');
            });
        }
    }
};