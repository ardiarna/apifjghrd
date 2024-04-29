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
        $hasil = $this->model->query();
        if($inputs['search_by'] && $inputs['value']) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if($inputs['sort_by']) {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('nama');
        }
        $hasil = $hasil->get();
        foreach ($hasil as $h) {
            $h->agama;
            $h->divisi;
            $h->jabatan;
            $h->pendidikan;
            $h->statusKerja;
        }
        return $hasil;
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
        if($inputs['nomor_ktp'] != null) {
            $model->nomor_ktp = $inputs['nomor_ktp'];
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
        if($inputs['nomor_kk'] != null) {
            $model->nomor_kk = $inputs['nomor_kk'];
        }
        if($inputs['nomor_paspor'] != null) {
            $model->nomor_paspor = $inputs['nomor_paspor'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
