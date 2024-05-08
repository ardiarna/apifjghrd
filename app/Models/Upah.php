<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upah extends Model
{

    protected $fillable = [
        'karyawan_id', 'gaji', 'uang_makan', 'makan_harian', 'overtime'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
