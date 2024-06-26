<?php

namespace App\Repositories\Elo;

use App\Models\Karyawan;
use App\Models\Upah;
use App\Repositories\UpahRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpahImplement implements UpahRepository {

    protected $model, $karyawan;

    function __construct(Upah $model, Karyawan $karyawan) {
        $this->model = $model;
        $this->karyawan = $karyawan;
    }

    public function findById($id) {
        $model = $this->model->findOrFail($id);
        return $model;
    }

    public function findByKaryawanId($karyawan_id) {
        $model = $this->model->query()->with('karyawan')
            ->where('karyawan_id', $karyawan_id)
            ->firstOrFail();
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->karyawan->query()->with(['area','jabatan'])
            ->select('karyawans.*', 'upahs.id AS upah_id', 'upahs.gaji', 'upahs.uang_makan', 'upahs.makan_harian', 'upahs.overtime')
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->leftJoin('upahs', 'karyawans.id', '=', 'upahs.karyawan_id')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan');
        if(isset($inputs['aktif']) && $inputs['aktif'] != '') {
            $hasil->where('karyawans.aktif', $inputs['aktif']);
        }
        if(isset($inputs['staf']) && $inputs['staf'] != '') {
            $hasil->where('karyawans.staf', $inputs['staf']);
        }
        if(isset($inputs['search_by']) && isset($inputs['value']) && $inputs['search_by'] != '' && $inputs['value'] != '') {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if(isset($inputs['sort_by']) && $inputs['sort_by'] != '') {
            $sort_order = (isset($inputs['sort_order']) && $inputs['sort_order'] != '') ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        $hasil->orderBy('karyawans.id');
        $hasil = $hasil->get();
        return $hasil;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan Upah tidak sesuai');
        }
        if(isset($inputs['gaji'])) {
            $model->gaji = $inputs['gaji'];
        }
        if(isset($inputs['uang_makan'])) {
            $model->uang_makan = $inputs['uang_makan'];
        }
        if(isset($inputs['makan_harian'])) {
            $model->makan_harian = $inputs['makan_harian'];
        }
        if(isset($inputs['overtime'])) {
            $model->overtime = $inputs['overtime'];
        }
        $model->save();
        return $model;
    }

    public function updateByKaryawanId($karyawan_id, array $inputs) {
        $model = $this->model->query()
            ->where('karyawan_id', $karyawan_id)
            ->firstOrFail();
        if(isset($inputs['gaji'])) {
            $model->gaji = $inputs['gaji'];
        }
        if(isset($inputs['uang_makan'])) {
            $model->uang_makan = $inputs['uang_makan'];
        }
        if(isset($inputs['makan_harian'])) {
            $model->makan_harian = $inputs['makan_harian'];
        }
        if(isset($inputs['overtime'])) {
            $model->overtime = $inputs['overtime'];
        }
        $model->save();
        return $model;
    }

    public function upsert(array $data, array $keys, array $updated) {
        return $this->model->upsert($data, $keys, $updated);
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deleteByKaryawanId($karyawan_id) {
        return $this->model->where('karyawan_id', $karyawan_id)->delete();
    }

}
