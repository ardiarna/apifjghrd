<?php

namespace App\Repositories\Elo;

use App\Repositories\KeluargaKontakRepository;
use App\Models\KeluargaKontak;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KeluargaKontakImplement implements KeluargaKontakRepository {

    protected $model;

    function __construct(KeluargaKontak $model) {
        $this->model = $model;
    }

    public function findById($karyawan_id, $id) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $karyawan_id) {
            throw new HttpException(403, 'Karyawan dan kontak keluarga tidak sesuai');
        }
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        $hasil->where('karyawan_id', $inputs['karyawan_id']);
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
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan kontak keluarga tidak sesuai');
        }
        if($inputs['nama'] != null) {
            $model->nama = $inputs['nama'];
        }
        if($inputs['telepon'] != null) {
            $model->telepon = $inputs['telepon'];
        }
        if($inputs['email'] != null) {
            $model->email = $inputs['email'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByKaryawanId($karyawan_id) {
        return $this->model->where('karyawan_id', $karyawan_id)->delete();
    }

}
