<?php
namespace App\Repositories\Elo;

use App\Models\Training;
use App\Repositories\TrainingRepository;

class TrainingImplement implements TrainingRepository
{
    protected $model;
    protected static $cachedData = null;

    public function __construct(Training $model)
    {
        $this->model = $model;
    }

    protected function getCachedData()
    {
        if (self::$cachedData === null) {
            self::$cachedData = $this->model->newQuery()->orderBy('urutan', 'asc')->get();
        }
        return self::$cachedData;
    }

    public function findAll($inputs = [])
    {
        return $this->getCachedData();
    }

    public function findById($id)
    {
        $cached = $this->getCachedData();
        return $cached->firstWhere('id', $id) ?? $this->model->findOrFail($id);
    }

    public function create(array $inputs)
    {
        self::$cachedData = null;
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs)
    {
        $data = $this->findById($id);
        $data->update($inputs);
        self::$cachedData = null;
        return $data;
    }

    public function delete($id)
    {
        $data = $this->findById($id);
        $data->delete();
        self::$cachedData = null;
        return $data;
    }
}
