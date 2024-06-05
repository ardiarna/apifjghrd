<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{

    protected $fillable = [
        'nama', 'nik', 'nomor_ktp', 'tanggal_masuk', 'tanggal_keluar', 'agama_id', 'area_id', 'jabatan_id', 'divisi_id', 'tempat_lahir', 'tanggal_lahir', 'alamat_ktp', 'alamat_tinggal', 'telepon', 'email', 'kawin', 'kelamin', 'status_kerja_id', 'pendidikan_id', 'pendidikan_almamater', 'pendidikan_jurusan', 'aktif', 'staf', 'nomor_kk', 'nomor_paspor', 'phk_id', 'ptkp_id'
    ];

    public function agama(): BelongsTo {
        return $this->belongsTo(Agama::class);
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class);
    }

    public function jabatan(): BelongsTo {
        return $this->belongsTo(Jabatan::class);
    }

    public function divisi(): BelongsTo {
        return $this->belongsTo(Divisi::class);
    }

    public function statusKerja(): BelongsTo {
        return $this->belongsTo(StatusKerja::class);
    }

    public function pendidikan(): BelongsTo {
        return $this->belongsTo(Pendidikan::class);
    }

    public function phk(): BelongsTo {
        return $this->belongsTo(Phk::class);
    }

    public function ptkp(): BelongsTo {
        return $this->belongsTo(Ptkp::class);
    }

    public function keluarga(): HasMany {
        return $this->hasMany(KeluargaKaryawan::class);
    }

    public function keluargaKontak(): HasMany {
        return $this->hasMany(KeluargaKontak::class);
    }

    public function perjanjianKerja(): HasMany {
        return $this->hasMany(PerjanjianKerja::class);
    }

    public function phkAll(): HasMany {
        return $this->hasMany(Phk::class);
    }

}
