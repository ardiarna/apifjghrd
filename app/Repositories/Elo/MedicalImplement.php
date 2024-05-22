<?php

namespace App\Repositories\Elo;

use App\Repositories\MedicalRepository;
use App\Models\Medical;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MedicalImplement implements MedicalRepository {

    protected $model;

    function __construct(Medical $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['karyawan.area', 'karyawan.jabatan'])
            ->orderBy('medicals.jenis');

        if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '' && isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->whereBetween('medicals.tanggal', [$inputs['tanggal_awal'], $inputs['tanggal_akhir']]);
        } else if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '') {
            $hasil->where('medicals.tanggal', '>=', $inputs['tanggal_awal']);
        } else if(isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->where('medicals.tanggal', '<=', $inputs['tanggal_akhir']);
        }
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('medicals.tahun', $inputs['tahun']);
        }
        if(isset($inputs['bulan']) && $inputs['bulan'] != '') {
            $hasil->where('medicals.bulan', $inputs['bulan']);
        }
        if(isset($inputs['jenis']) && $inputs['jenis'] != '') {
            $hasil->where('medicals.jenis', $inputs['jenis']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('medicals.karyawan_id', $inputs['karyawan_id']);
        } else {
            $hasil->select('medicals.*')
                ->join('karyawans', 'medicals.karyawan_id', '=', 'karyawans.id')
                ->join('areas', 'karyawans.area_id', '=', 'areas.id')
                ->orderBy('karyawans.staf')
                ->orderBy('areas.urutan')
                ->orderBy('karyawans.tanggal_masuk')
                ->orderBy('karyawans.id');
        }
        $hasil->orderBy('medicals.tanggal');
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {

    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
