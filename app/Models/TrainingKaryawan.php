<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingKaryawan extends Model
{
    protected $table = 'training_karyawans';
    protected $fillable = [
        'karyawan_id', 'training_id', 'tanggal_awal', 'tanggal_akhir', 'keterangan'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

    public function training(): BelongsTo {
        return $this->belongsTo(Training::class);
    }
}
