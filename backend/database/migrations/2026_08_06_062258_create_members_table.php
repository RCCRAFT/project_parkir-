<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('kode_member')->unique();
            $table->string('token')->unique();

            $table->string('nama_member');
            $table->string('nama_perusahaan')->nullable();

            $table->decimal('total_harga', 15, 2)->default(0);

            $table->string('keterangan')->nullable();

            $table->string('status')->default('aktif');

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_expired')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};