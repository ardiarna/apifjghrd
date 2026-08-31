<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicCuti extends Model {
    protected $table = 'cic_cutis';
    protected $guarded = [];
    public function cicKaryawan() { return $this->belongsTo(CicKaryawan::class, 'cic_karyawan_id'); }
    public function details() { return $this->hasMany(CicCutiDetail::class, 'cic_cuti_id'); }
    public function approveUser() { return $this->belongsTo(User::class, 'approve_user_id'); }
}
