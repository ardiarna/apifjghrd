<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjanjianKerja extends Model
{

    protected $fillable = [
        'karyawan_id', 'nomor', 'tanggal_awal', 'tanggal_akhir', 'status_kerja_id'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

    public function statusKerja(): BelongsTo {
        return $this->belongsTo(StatusKerja::class);
    }

}
