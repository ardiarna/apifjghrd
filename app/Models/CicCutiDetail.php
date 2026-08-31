<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicCutiDetail extends Model {
    protected $table = 'cic_cuti_details';
    protected $guarded = [];
    public function cicCuti() { return $this->belongsTo(CicCuti::class, 'cic_cuti_id'); }
    public function cicJenisCutiKhusus() { return $this->belongsTo(CicJenisCutiKhusus::class, 'jenis_cuti_khusus_id'); }
    public function dates() { return $this->hasMany(CicCutiDate::class, 'cic_cuti_detail_id'); }
}
