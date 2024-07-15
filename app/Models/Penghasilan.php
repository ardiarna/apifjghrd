<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penghasilan extends Model
{

    protected $fillable = [
        'karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah', 'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
