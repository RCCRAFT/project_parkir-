<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('status_parkir')->default('di_luar')->after('status');
            $table->timestamp('waktu_masuk_terakhir')->nullable()->after('status_parkir');
            $table->timestamp('waktu_keluar_terakhir')->nullable()->after('waktu_masuk_terakhir');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['status_parkir', 'waktu_masuk_terakhir', 'waktu_keluar_terakhir']);
        });
    }
};