<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_parkir_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_keluar')->nullable();
            $table->string('nomor_polisi')->nullable();
            $table->string('jenis_kendaraan')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'waktu_keluar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_parkir_logs');
    }
};