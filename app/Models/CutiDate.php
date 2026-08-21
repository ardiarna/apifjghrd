<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiDate extends Model
{
    protected $guarded = ['id'];

    public function cutiDetail()
    {
        return $this->belongsTo(CutiDetail::class);
    }
}
