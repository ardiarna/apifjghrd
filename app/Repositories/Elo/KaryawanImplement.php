<?php

namespace App\Repositories\Elo;

use App\Repositories\KaryawanRepository;
use App\Models\Karyawan;

class KaryawanImplement implements KaryawanRepository {

    protected $model;

    function __construct(Karyawan $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        if($inputs['sort_by']) {
            if(strtolower($inputs['sort_order']) == 'desc') {
                return $this->model->orderByDesc($inputs['sort_by'])->get();
            }
            return $this->model->orderBy($inputs['sort_by'])->get();
        }
        if(strtolower($inputs['sort_order']) == 'desc') {
            return $this->model->orderByDesc('nama')->get();
        }
        return $this->model->orderBy('nama')->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($inputs['nama'] != null) {
            $model->nama = $inputs['nama'];
        }
        if($inputs['nik'] != null) {
            $model->nik = $inputs['nik'];
        }
        if($inputs['no_ktp'] != null) {
            $model->no_ktp = $inputs['no_ktp'];
        }
        if($inputs['tanggal_masuk'] != null) {
            $model->tanggal_masuk = $inputs['tanggal_masuk'];
        }
        if($inputs['agama_id'] != null) {
            $model->agama_id = $inputs['agama_id'];
        }
        if($inputs['jabatan_id'] != null) {
            $model->jabatan_id = $inputs['jabatan_id'];
        }
        if($inputs['divisi_id'] != null) {
            $model->divisi_id = $inputs['divisi_id'];
        }
        if($inputs['tempat_lahir'] != null) {
            $model->tempat_lahir = $inputs['tempat_lahir'];
        }
        if($inputs['tanggal_lahir'] != null) {
            $model->tanggal_lahir = $inputs['tanggal_lahir'];
        }
        if($inputs['alamat_ktp'] != null) {
            $model->alamat_ktp = $inputs['alamat_ktp'];
        }
        if($inputs['alamat_tinggal'] != null) {
            $model->alamat_tinggal = $inputs['alamat_tinggal'];
        }
        if($inputs['telepon'] != null) {
            $model->telepon = $inputs['telepon'];
        }
        if($inputs['email'] != null) {
            $model->email = $inputs['email'];
        }
        if($inputs['kawin'] != null) {
            $model->kawin = $inputs['kawin'];
        }
        if($inputs['status_kerja_id'] != null) {
            $model->status_kerja_id = $inputs['status_kerja_id'];
        }
        if($inputs['pendidikan_id'] != null) {
            $model->pendidikan_id = $inputs['pendidikan_id'];
        }
        if($inputs['pendidikan_almamater'] != null) {
            $model->pendidikan_almamater = $inputs['pendidikan_almamater'];
        }
        if($inputs['pendidikan_jurusan'] != null) {
            $model->pendidikan_jurusan = $inputs['pendidikan_jurusan'];
        }
        if($inputs['aktif'] != null) {
            $model->aktif = $inputs['aktif'];
        }
        if($inputs['no_kk'] != null) {
            $model->no_kk = $inputs['no_kk'];
        }
        if($inputs['no_paspor'] != null) {
            $model->no_paspor = $inputs['no_paspor'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
