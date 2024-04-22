<?php

namespace App\Repositories\Elo;

use App\Repositories\PerjanjianKerjaRepository;
use App\Models\PerjanjianKerja;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PerjanjianKerjaImplement implements PerjanjianKerjaRepository {

    protected $model;

    function __construct(PerjanjianKerja $model) {
        $this->model = $model;
    }

    public function findById($karyawan_id, $id) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $karyawan_id) {
            throw new HttpException(403, 'Karyawan dan perjanjian kerja tidak sesuai');
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
            $hasil->orderBy('nomor');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan perjanjian kerja tidak sesuai');
        }
        if($inputs['nomor'] != null) {
            $model->nomor = $inputs['nomor'];
        }
        if($inputs['tanggal_awal'] != null) {
            $model->tanggal_awal = $inputs['tanggal_awal'];
        }
        if($inputs['tanggal_akhir'] != null) {
            $model->tanggal_akhir = $inputs['tanggal_akhir'];
        }
        if($inputs['status_kerja_id'] != null) {
            $model->status_kerja_id = $inputs['status_kerja_id'];
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
