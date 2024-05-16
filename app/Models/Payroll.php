<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{

    protected $fillable = [
        'payroll_header_id', 'karyawan_id', 'gaji', 'makan_harian', 'hari_makan', 'uang_makan_harian', 'uang_makan_jumlah', 'overtime_fjg', 'overtime_cus', 'medical', 'thr', 'bonus', 'insentif', 'telkomsel', 'lain', 'pot_25_hari', 'pot_25_jumlah', 'pot_telepon', 'pot_bensin', 'pot_kas', 'pot_cicilan', 'pot_bpjs', 'pot_cuti', 'pot_lain', 'total_diterima', 'keterangan'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
