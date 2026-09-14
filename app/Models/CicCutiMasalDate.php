<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicCutiMasalDate extends Model
{
    protected $guarded = [];

    public function cicCutiMasal()
    {
        return $this->belongsTo(CicCutiMasal::class, 'cic_cuti_masal_id');
    }
}
