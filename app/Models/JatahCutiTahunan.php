<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JatahCutiTahunan extends Model
{
    protected $table = 'jatah_cuti_tahunans';

    protected $fillable = [
        'karyawan_id',
        'tahun',
        'jumlah_cuti',
        'plus_tahun_lalu',
        'min_tahun_lalu',
        'total_cuti',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
