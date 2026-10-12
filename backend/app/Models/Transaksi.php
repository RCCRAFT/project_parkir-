<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'kode_tiket',
        'jenis_kendaraan',
        'nomor_polisi',
        'waktu_masuk',
        'waktu_keluar',
        'durasi',
        'total_bayar',
        'status',
    ];

    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_keluar' => 'datetime',
        'total_bayar' => 'integer',
    ];

    public function tiket()
    {
        return $this->belongsTo(
            TiketParkir::class,
            'kode_tiket',
            'kode_tiket'
        );
    }
}