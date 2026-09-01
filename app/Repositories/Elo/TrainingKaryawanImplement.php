<?php
namespace App\Repositories\Elo;

use App\Models\TrainingKaryawan;
use App\Repositories\TrainingKaryawanRepository;

class TrainingKaryawanImplement implements TrainingKaryawanRepository
{
    protected $model;

    public function __construct(TrainingKaryawan $model)
    {
        $this->model = $model;
    }

    public function findAll($inputs = [])
    {
        $query = $this->model->newQuery();
        if (isset($inputs['karyawan_id'])) {
            $query->where('karyawan_id', $inputs['karyawan_id']);
        }
        return $query->with('training')->orderBy('tanggal', 'desc')->get();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $inputs)
    {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs)
    {
        $data = $this->findById($id);
        $data->update($inputs);
        return $data;
    }

    public function delete($id)
    {
        $data = $this->findById($id);
        $data->delete();
        return $data;
    }
}
