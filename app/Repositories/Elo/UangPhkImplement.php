<?php

namespace App\Repositories\Elo;

use App\Repositories\UangPhkRepository;
use App\Models\UangPhk;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UangPhkImplement implements UangPhkRepository {

    protected $model;

    function __construct(UangPhk $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['karyawan.area', 'karyawan.jabatan']);
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('uang_phks.tahun', $inputs['tahun']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('uang_phks.karyawan_id', $inputs['karyawan_id']);
        } else {
            $hasil->select('uang_phks.*')
                ->join('karyawans', 'uang_phks.karyawan_id', '=', 'karyawans.id')
                ->join('areas', 'karyawans.area_id', '=', 'areas.id')
                ->orderBy('karyawans.staf')
                ->orderBy('areas.urutan')
                ->orderBy('karyawans.tanggal_masuk')
                ->orderBy('karyawans.id');
        }
        $hasil->orderBy('uang_phks.tahun');
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan tidak sesuai');
        }
        if(isset($inputs['tahun'])) {
            $model->tahun = $inputs['tahun'];
        }
        if(isset($inputs['kompensasi'])) {
            $model->kompensasi = $inputs['kompensasi'];
        }
        if(isset($inputs['uang_pisah'])) {
            $model->uang_pisah = $inputs['uang_pisah'];
        }
        if(isset($inputs['pesangon'])) {
            $model->pesangon = $inputs['pesangon'];
        }
        if(isset($inputs['masa_kerja'])) {
            $model->masa_kerja = $inputs['masa_kerja'];
        }
        if(isset($inputs['penggantian_hak'])) {
            $model->penggantian_hak = $inputs['penggantian_hak'];
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
