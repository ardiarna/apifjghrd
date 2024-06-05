<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifEfektif extends Model
{

    protected $fillable = [
        'ter', 'penghasilan', 'persen'
    ];

}
