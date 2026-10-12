<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [

        'kode_member',

        'token',

        'nama_member',

        'nama_perusahaan',

        'total_harga',

        'jumlah_bayar',

        'kembalian',

        'status',

        'status_parkir',

        'jenis_kendaraan',

        'waktu_masuk_terakhir',

        'waktu_keluar_terakhir',

        'bulan_tagihan',

        'tanggal_mulai',

        'tanggal_expired',

        'tanggal_bayar',

    ];

    protected $casts = [

        'total_harga' => 'float',

        'jumlah_bayar' => 'float',

        'kembalian' => 'float',

        'tanggal_mulai' => 'date',

        'tanggal_expired' => 'date',

        'tanggal_bayar' => 'datetime',

        'waktu_masuk_terakhir' => 'datetime',

        'waktu_keluar_terakhir' => 'datetime',

    ];
}