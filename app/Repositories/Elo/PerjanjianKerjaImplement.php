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
        $hasil = $this->model->query()->with('statusKerja');
        $hasil->where('karyawan_id', $inputs['karyawan_id']);
        if($inputs['search_by'] && $inputs['value']) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if($inputs['sort_by']) {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('tanggal_awal');
        }
        return $hasil->get();
    }

    public function timelineMasaKerja($karyawan_id) {
        $hasil = $this->model->query()
            ->selectRaw('status_kerjas.id as id,
                status_kerjas.nama as nama,
                MIN(perjanjian_kerjas.tanggal_awal) as tanggal_awal,
                COALESCE(MAX(perjanjian_kerjas.tanggal_akhir), DATE_FORMAT(NOW(), "%Y-%m-%d")) as tanggal_akhir,
                DATEDIFF(COALESCE(MAX(perjanjian_kerjas.tanggal_akhir), NOW()), MIN(perjanjian_kerjas.tanggal_awal)) as masa_kerja,
                TIMESTAMPDIFF(YEAR, MIN(perjanjian_kerjas.tanggal_awal), COALESCE(MAX(perjanjian_kerjas.tanggal_akhir), NOW())) as tahun,
                TIMESTAMPDIFF(MONTH, MIN(perjanjian_kerjas.tanggal_awal), COALESCE(MAX(perjanjian_kerjas.tanggal_akhir), NOW())) % 12 as bulan')
            ->join('status_kerjas', 'perjanjian_kerjas.status_kerja_id', '=', 'status_kerjas.id')
            ->where('perjanjian_kerjas.karyawan_id', $karyawan_id)
            ->groupBy('status_kerjas.id', 'status_kerjas.nama')
            ->orderBy('tanggal_awal')
            ->get();
        return $hasil;
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
