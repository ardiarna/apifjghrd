<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CicKaryawan extends Model {
    protected $table = 'cic_karyawans';
    protected $guarded = [];
    public function agama() { return $this->belongsTo(Agama::class); }
    public function area() { return $this->belongsTo(Area::class); }
    public function jabatan() { return $this->belongsTo(Jabatan::class); }
    public function divisi() { return $this->belongsTo(Divisi::class); }
    public function statusKerja() { return $this->belongsTo(StatusKerja::class); }
    public function pendidikan() { return $this->belongsTo(Pendidikan::class); }
}
