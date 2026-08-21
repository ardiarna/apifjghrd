<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $guarded = ['id'];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function details()
    {
        return $this->hasMany(CutiDetail::class);
    }
}
