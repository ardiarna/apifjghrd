<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollHeader extends Model
{

    protected $fillable = [
        'tanggal_awal', 'tanggal_akhir', 'tanggal_makan_awal', 'tanggal_makan_akhir', 'gaji', 'uang_makan_jumlah', 'overtime_fjg', 'overtime_cus', 'medical', 'thr', 'bonus', 'insentif', 'telkomsel', 'lain', 'pot_25_hari', 'pot_25_jumlah', 'pot_telepon', 'pot_bensin', 'pot_kas', 'pot_cicilan', 'pot_bpjs', 'pot_cuti', 'pot_lain', 'total_diterima', 'dikunci', 'keterangan'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
