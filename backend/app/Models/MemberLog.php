<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberLog extends Model
{
    protected $table = 'member_logs';

    protected $fillable = [
        'member_id',
        'nomor_polisi',
        'masuk',
        'keluar',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}