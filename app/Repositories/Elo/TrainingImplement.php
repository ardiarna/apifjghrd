<?php
namespace App\Repositories\Elo;

use App\Models\Training;
use App\Repositories\TrainingRepository;

class TrainingImplement implements TrainingRepository
{
    protected $model;

    public function __construct(Training $model)
    {
        $this->model = $model;
    }

    public function findAll($inputs = [])
    {
        $query = $this->model->newQuery();
        return $query->orderBy('urutan', 'asc')->get();
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
