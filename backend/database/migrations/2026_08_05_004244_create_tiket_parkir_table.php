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
        Schema::create('tiket_parkir', function (Blueprint $table) {
            $table->id();

            $table->string('kode_tiket')->unique();

            $table->string('qr_code')->unique();

            $table->enum('status', ['masuk', 'keluar'])
                  ->default('masuk');

            $table->timestamp('waktu_masuk')->nullable();

            $table->timestamp('waktu_keluar')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiket_parkir');
    }
};