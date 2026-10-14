<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phk extends Model
{

    protected $fillable = [
        'karyawan_id', 'tanggal_awal', 'tanggal_akhir', 'status_kerja_id', 'status_phk_id', 'uang_phk_id', 'payroll_phk_id', 'keterangan'
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

    public function uangPhk(): BelongsTo {
        return $this->belongsTo(UangPhk::class, 'uang_phk_id');
    }

    public function payrollPhk(): BelongsTo {
        return $this->belongsTo(PayrollPhk::class, 'payroll_phk_id');
    }

}
