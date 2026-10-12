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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();

            // Relasi ke tiket parkir
            $table->string('kode_tiket');

            // Data kendaraan
            $table->string('jenis_kendaraan');
            $table->string('nomor_polisi');

            // Waktu parkir
            $table->timestamp('waktu_masuk');
            $table->timestamp('waktu_keluar')->nullable();

            // Durasi parkir dalam menit
            $table->integer('durasi')->nullable();

            // Total pembayaran
            $table->integer('total_bayar')->default(0);

            // Status transaksi
            $table->enum('status', [
                'masuk',
                'selesai',
                'lunas'
            ])->default('masuk');

            $table->timestamps();

            // Foreign key ke tiket_parkir.kode_tiket
            $table->foreign('kode_tiket')
                  ->references('kode_tiket')
                  ->on('tiket_parkir')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};