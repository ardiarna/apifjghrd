<?php

namespace App\Repositories\Elo;

use App\Repositories\JatahCutiTahunanRepository;
use App\Models\JatahCutiTahunan;

class JatahCutiTahunanImplement implements JatahCutiTahunanRepository
{
    protected $model;

    function __construct(JatahCutiTahunan $model)
    {
        $this->model = $model;
    }

    public function findAll($inputs = [])
    {
        $query = $this->model->query()->with('karyawan');

        if (!empty($inputs['karyawan_id'])) {
            $query->where('karyawan_id', $inputs['karyawan_id']);
        }

        if (!empty($inputs['tahun'])) {
            $query->where('tahun', $inputs['tahun']);
        }

        $query->join('karyawans', 'jatah_cuti_tahunans.karyawan_id', '=', 'karyawans.id')
              ->orderBy('karyawans.nama')
              ->select('jatah_cuti_tahunans.*');

        return $query->get();
    }

    public function findById($id)
    {
        return $this->model->with('karyawan')->find($id);
    }

    public function create(array $inputs)
    {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = $this->model->findOrFail($id);

        if ($inputs['jumlah_cuti'] !== null) {
            $model->jumlah_cuti = $inputs['jumlah_cuti'];
        }
        if (isset($inputs['plus_tahun_lalu']) && $inputs['plus_tahun_lalu'] !== null) {
            $model->plus_tahun_lalu = $inputs['plus_tahun_lalu'];
        }
        if (isset($inputs['min_tahun_lalu']) && $inputs['min_tahun_lalu'] !== null) {
            $model->min_tahun_lalu = $inputs['min_tahun_lalu'];
        }
        if (isset($inputs['total_cuti']) && $inputs['total_cuti'] !== null) {
            $model->total_cuti = $inputs['total_cuti'];
        }

        $model->save();
        return $model;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
