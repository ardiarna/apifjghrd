<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicCutiMasal extends Model
{
    protected $guarded = [];

    public function cutis()
    {
        return $this->hasMany(CicCuti::class, 'cic_cuti_masal_id');
    }

    public function dates()
    {
        return $this->hasMany(CicCutiMasalDate::class, 'cic_cuti_masal_id');
    }
}
