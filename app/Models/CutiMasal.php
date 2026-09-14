<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiMasal extends Model
{
    protected $guarded = [];

    public function cutis()
    {
        return $this->hasMany(Cuti::class, 'cuti_masal_id');
    }
    public function dates()
    {
        return $this->hasMany(CutiMasalDate::class, 'cuti_masal_id');
    }
}
