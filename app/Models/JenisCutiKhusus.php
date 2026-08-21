<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisCutiKhusus extends Model
{
    protected $table = 'jenis_cuti_khususs';

    protected $fillable = [
        'nama',
        'lama_hari',
        'satuan',
        'urutan',
    ];
}
