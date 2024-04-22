<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phk extends Model
{

    protected $fillable = [
        'karyawan_id', 'tanggal_awal', 'tanggal_akhir', 'status_kerja_id', 'status_phk_id', 'keterangan'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

    public function statusKerja(): BelongsTo {
        return $this->belongsTo(StatusKerja::class);
    }

    public function statusPhk(): BelongsTo {
        return $this->belongsTo(StatusPhk::class);
    }

}
