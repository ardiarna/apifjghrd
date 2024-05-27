<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRekap extends Model
{

    protected $fillable = [
        'karyawan_id', 'tahun', 'fjg_1', 'fjg_2', 'fjg_3', 'fjg_4', 'fjg_5', 'fjg_6', 'fjg_7', 'fjg_8', 'fjg_9', 'fjg_10', 'fjg_11', 'fjg_12', 'cus_1', 'cus_2', 'cus_3', 'cus_4', 'cus_5', 'cus_6', 'cus_7', 'cus_8', 'cus_9', 'cus_10', 'cus_11', 'cus_12'
    ];

    public function karyawan(): BelongsTo {
        return $this->belongsTo(Karyawan::class);
    }

}
