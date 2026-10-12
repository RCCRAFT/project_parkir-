<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_hp', 20)->nullable()->after('email');
            $table->string('shift', 20)->nullable()->after('role');
            $table->string('status', 20)->default('aktif')->after('shift');
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_hp',
                'shift',
                'status',
            ]);
        });
    }
};