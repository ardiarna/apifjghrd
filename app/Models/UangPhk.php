<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UangPhk extends Model
{

    protected $fillable = [
        'karyawan_id', 'tahun', 'kompensasi', 'uang_pisah', 'pesangon', 'masa_kerja', 'penggantian_hak'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
