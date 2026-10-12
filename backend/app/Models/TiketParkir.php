<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiketParkir extends Model
{
    protected $table = 'tiket_parkir';

    protected $fillable = [
        'kode_tiket',
        'qr_code',
        'status',
        'waktu_masuk',
        'waktu_keluar',
        'jenis',
        'nomor_polisi',
        'total_bayar',
    ];
}