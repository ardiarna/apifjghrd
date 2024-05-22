<?php

namespace App\Repositories\Elo;

use App\Repositories\MedicalRekapRepository;
use App\Models\MedicalRekap;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MedicalRekapImplement implements MedicalRekapRepository {

    protected $model;

    function __construct(MedicalRekap $model) {
        $this->model = $model;
    }

    public function findByKaryawanIdAndTahun($karyawan_id, $tahun) {
        $model = $this->model->query()->with('karyawan')
            ->where('karyawan_id', $karyawan_id)
            ->where('tahun', $tahun)
            ->first();
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('medical_rekaps.tahun', $inputs['tahun']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('medical_rekaps.karyawan_id', $inputs['karyawan_id']);
        } else {
            $hasil->join('karyawans', 'medical_rekaps.karyawan_id', '=', 'karyawans.id')
                ->join('areas', 'karyawans.area_id', '=', 'areas.id')
                ->orderBy('karyawans.staf')
                ->orderBy('areas.urutan')
                ->orderBy('karyawans.tanggal_masuk')
                ->orderBy('karyawans.id');
        }
        $hasil->orderBy('medical_rekaps.tahun');
        return $hasil->get();
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->refresh()->load('karyawan');
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan tidak sesuai');
        }
        if($model->tahun != $inputs['tahun']) {
            throw new HttpException(403, 'Tahun tidak sesuai');
        }
        if(isset($inputs['bln_1'])) {
            $model->bln_1 = $inputs['bln_1'];
        }
        if(isset($inputs['bln_2'])) {
            $model->bln_2 = $inputs['bln_2'];
        }
        if(isset($inputs['bln_3'])) {
            $model->bln_3 = $inputs['bln_3'];
        }
        if(isset($inputs['bln_4'])) {
            $model->bln_4 = $inputs['bln_4'];
        }
        if(isset($inputs['bln_5'])) {
            $model->bln_5 = $inputs['bln_5'];
        }
        if(isset($inputs['bln_6'])) {
            $model->bln_6 = $inputs['bln_6'];
        }
        if(isset($inputs['bln_7'])) {
            $model->bln_7 = $inputs['bln_7'];
        }
        if(isset($inputs['bln_8'])) {
            $model->bln_8 = $inputs['bln_8'];
        }
        if(isset($inputs['bln_9'])) {
            $model->bln_9 = $inputs['bln_9'];
        }
        if(isset($inputs['bln_10'])) {
            $model->bln_10 = $inputs['bln_10'];
        }
        if(isset($inputs['bln_11'])) {
            $model->bln_11 = $inputs['bln_11'];
        }
        if(isset($inputs['bln_12'])) {
            $model->bln_12 = $inputs['bln_12'];
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
