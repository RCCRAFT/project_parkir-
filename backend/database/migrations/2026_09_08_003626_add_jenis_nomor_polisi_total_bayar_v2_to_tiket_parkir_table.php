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
        Schema::table('tiket_parkir', function (Blueprint $table) {
            $table->string('jenis')->nullable()->after('status');
            $table->string('nomor_polisi')->nullable()->after('jenis');
            $table->decimal('total_bayar', 12, 2)->nullable()->after('nomor_polisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'nomor_polisi', 'total_bayar']);
        });
    }
};