<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRekap extends Model
{

    protected $fillable = [
        'karyawan_id', 'tahun', 'gaji', 'bln_1', 'bln_2', 'bln_3', 'bln_4', 'bln_5', 'bln_6', 'bln_7', 'bln_8', 'bln_9', 'bln_10', 'bln_11', 'bln_12'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
