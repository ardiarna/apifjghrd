<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiMasalDate extends Model
{
    protected $guarded = [];

    public function cutiMasal()
    {
        return $this->belongsTo(CutiMasal::class, 'cuti_masal_id');
    }
}
