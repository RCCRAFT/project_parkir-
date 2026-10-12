<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberParkirLog extends Model
{
    protected $fillable = [
        'member_id', 'waktu_masuk', 'waktu_keluar',
        'nomor_polisi', 'jenis_kendaraan',
    ];

    protected $casts = [
        'waktu_masuk'  => 'datetime',
        'waktu_keluar' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}