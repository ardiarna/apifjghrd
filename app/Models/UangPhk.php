<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UangPhk extends Model
{

    protected $fillable = [
        'karyawan_id', 'tahun', 'kompensasi', 'uang_pisah', 'pesangon', 'masa_kerja', 'penggantian_hak', 'sisa_cuti_hari', 'sisa_cuti_jumlah', 'lain', 'pot_kas', 'pot_cuti_hari', 'pot_cuti_jumlah', 'pot_lain', 'keterangan'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
