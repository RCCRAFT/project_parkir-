<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom tanggal_mulai dan tanggal_expired
        // sudah dibuat di create_members_table.
        // Tidak perlu menambahkan kolom lagi.
    }

    public function down(): void
    {
        // Tidak ada perubahan yang perlu dibatalkan.
    }
};