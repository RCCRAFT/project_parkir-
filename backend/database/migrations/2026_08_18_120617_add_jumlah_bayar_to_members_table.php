<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'jumlah_bayar')) {
                $table->decimal('jumlah_bayar', 15, 2)
                    ->default(0)
                    ->after('total_harga');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'jumlah_bayar')) {
                $table->dropColumn('jumlah_bayar');
            }
        });
    }
};