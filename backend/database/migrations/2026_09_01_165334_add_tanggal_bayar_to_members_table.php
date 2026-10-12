<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('members', 'tanggal_bayar')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dateTime('tanggal_bayar')
                    ->nullable()
                    ->after('jumlah_bayar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('members', 'tanggal_bayar')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('tanggal_bayar');
            });
        }
    }
};