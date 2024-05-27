<?php

namespace App\Repositories\Elo;

use App\Repositories\OvertimeRekapRepository;
use App\Models\OvertimeRekap;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OvertimeRekapImplement implements OvertimeRekapRepository {

    protected $model;

    function __construct(OvertimeRekap $model) {
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
            $hasil->where('overtime_rekaps.tahun', $inputs['tahun']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('overtime_rekaps.karyawan_id', $inputs['karyawan_id']);
        } else {
            $hasil->join('karyawans', 'overtime_rekaps.karyawan_id', '=', 'karyawans.id')
                ->join('areas', 'karyawans.area_id', '=', 'areas.id')
                ->orderBy('karyawans.staf')
                ->orderBy('areas.urutan')
                ->orderBy('karyawans.tanggal_masuk')
                ->orderBy('karyawans.id');
        }
        $hasil->orderBy('overtime_rekaps.tahun');
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
        if(isset($inputs['fjg_1'])) {
            $model->fjg_1 = $inputs['fjg_1'];
        }
        if(isset($inputs['fjg_2'])) {
            $model->fjg_2 = $inputs['fjg_2'];
        }
        if(isset($inputs['fjg_3'])) {
            $model->fjg_3 = $inputs['fjg_3'];
        }
        if(isset($inputs['fjg_4'])) {
            $model->fjg_4 = $inputs['fjg_4'];
        }
        if(isset($inputs['fjg_5'])) {
            $model->fjg_5 = $inputs['fjg_5'];
        }
        if(isset($inputs['fjg_6'])) {
            $model->fjg_6 = $inputs['fjg_6'];
        }
        if(isset($inputs['fjg_7'])) {
            $model->fjg_7 = $inputs['fjg_7'];
        }
        if(isset($inputs['fjg_8'])) {
            $model->fjg_8 = $inputs['fjg_8'];
        }
        if(isset($inputs['fjg_9'])) {
            $model->fjg_9 = $inputs['fjg_9'];
        }
        if(isset($inputs['fjg_10'])) {
            $model->fjg_10 = $inputs['fjg_10'];
        }
        if(isset($inputs['fjg_11'])) {
            $model->fjg_11 = $inputs['fjg_11'];
        }
        if(isset($inputs['fjg_12'])) {
            $model->fjg_12 = $inputs['fjg_12'];
        }
        if(isset($inputs['cus_1'])) {
            $model->cus_1 = $inputs['cus_1'];
        }
        if(isset($inputs['cus_2'])) {
            $model->cus_2 = $inputs['cus_2'];
        }
        if(isset($inputs['cus_3'])) {
            $model->cus_3 = $inputs['cus_3'];
        }
        if(isset($inputs['cus_4'])) {
            $model->cus_4 = $inputs['cus_4'];
        }
        if(isset($inputs['cus_5'])) {
            $model->cus_5 = $inputs['cus_5'];
        }
        if(isset($inputs['cus_6'])) {
            $model->cus_6 = $inputs['cus_6'];
        }
        if(isset($inputs['cus_7'])) {
            $model->cus_7 = $inputs['cus_7'];
        }
        if(isset($inputs['cus_8'])) {
            $model->cus_8 = $inputs['cus_8'];
        }
        if(isset($inputs['cus_9'])) {
            $model->cus_9 = $inputs['cus_9'];
        }
        if(isset($inputs['cus_10'])) {
            $model->cus_10 = $inputs['cus_10'];
        }
        if(isset($inputs['cus_11'])) {
            $model->cus_11 = $inputs['cus_11'];
        }
        if(isset($inputs['cus_12'])) {
            $model->cus_12 = $inputs['cus_12'];
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
