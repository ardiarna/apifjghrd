<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicCutiDate extends Model {
    protected $table = 'cic_cuti_dates';
    protected $guarded = [];
    public function cicCutiDetail() { return $this->belongsTo(CicCutiDetail::class, 'cic_cuti_detail_id'); }
}
