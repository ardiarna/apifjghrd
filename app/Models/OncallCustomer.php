<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OncallCustomer extends Model
{

    protected $fillable = [
        'customer_id', 'tanggal', 'tahun', 'bulan', 'jumlah', 'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

}
