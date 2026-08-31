<?php

namespace App\Repositories\Elo;

use App\Repositories\CicJatahCutiTahunanRepository;
use App\Models\CicJatahCutiTahunan;

class CicJatahCutiTahunanImplement implements CicJatahCutiTahunanRepository
{
    public function findAll($inputs = [])
    {
        $q = CicJatahCutiTahunan::with('cicKaryawan');
        if (!empty($inputs['cic_karyawan_id'])) {
            $q->where('cic_karyawan_id', $inputs['cic_karyawan_id']);
        }
        if (!empty($inputs['tahun'])) {
            $q->where('tahun', $inputs['tahun']);
        }
        $q->join('cic_karyawans', 'cic_karyawans.id', '=', 'cic_jatah_cuti_tahunans.cic_karyawan_id')
          ->orderBy('cic_karyawans.nama', 'asc')
          ->select('cic_jatah_cuti_tahunans.*');
        return $q->get();
    }

    public function findById($id)
    {
        return CicJatahCutiTahunan::find($id);
    }

    public function create(array $inputs)
    {
        return CicJatahCutiTahunan::create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = CicJatahCutiTahunan::find($id);
        if ($model) {
            $model->update($inputs);
            return $model;
        }
        return null;
    }

    public function delete($id)
    {
        return CicJatahCutiTahunan::destroy($id);
    }
}
