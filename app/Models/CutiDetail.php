<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiDetail extends Model
{
    protected $guarded = ['id'];

    public function cuti()
    {
        return $this->belongsTo(Cuti::class);
    }

    public function dates()
    {
        return $this->hasMany(CutiDate::class);
    }

    public function jenisKhusus()
    {
        return $this->belongsTo(JenisCutiKhusus::class, 'jenis_cuti_khusus_id');
    }
}
