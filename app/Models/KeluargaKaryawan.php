<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeluargaKaryawan extends Model
{

    protected $fillable = [
        'karyawan_id', 'nama', 'nomor_ktp', 'hubungan', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'email'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
