<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicJatahCutiTahunan extends Model {
    protected $table = 'cic_jatah_cuti_tahunans';
    protected $guarded = [];
    public function cicKaryawan() { return $this->belongsTo(CicKaryawan::class, 'cic_karyawan_id'); }
}
