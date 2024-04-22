<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{

    protected $fillable = [
        'nama', 'nik', 'nomor_ktp', 'tanggal_masuk', 'agama_id', 'jabatan_id', 'divisi_id', 'tempat_lahir', 'tanggal_lahir', 'alamat_ktp', 'alamat_tinggal', 'telepon', 'email', 'kawin', 'status_kerja_id', 'pendidikan_id', 'pendidikan_almamater', 'pendidikan_jurusan', 'aktif', 'nomor_kk', 'nomor_paspor'
    ];

    public function agama(): BelongsTo {
        return $this->belongsTo(Agama::class);
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

    public function keluarga(): HasMany {
        return $this->hasMany(KeluargaKaryawan::class);
    }

}
