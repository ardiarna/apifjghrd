<?php

namespace App\Repositories\Elo;

use App\Repositories\JenisCutiKhususRepository;
use App\Models\JenisCutiKhusus;

class JenisCutiKhususImplement implements JenisCutiKhususRepository
{
    protected $model;
    protected static $cachedData = null;

    function __construct(JenisCutiKhusus $model)
    {
        $this->model = $model;
    }

    protected function getCachedData()
    {
        if (self::$cachedData === null) {
            self::$cachedData = $this->model->orderBy('urutan')->get();
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
        return $cached->firstWhere('id', $id) ?? $this->model->find($id);
    }

    public function create(array $inputs)
    {
        self::$cachedData = null;
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = $this->model->findOrFail($id);

        if (isset($inputs['nama']) && $inputs['nama'] !== null) {
            $model->nama = $inputs['nama'];
        }
        if (isset($inputs['lama_hari']) && $inputs['lama_hari'] !== null) {
            $model->lama_hari = $inputs['lama_hari'];
        }
        if (isset($inputs['satuan']) && $inputs['satuan'] !== null) {
            $model->satuan = $inputs['satuan'];
        }
        if (isset($inputs['urutan']) && $inputs['urutan'] !== null) {
            $model->urutan = $inputs['urutan'];
        }

        $model->save();
        self::$cachedData = null;
        return $model;
    }

    public function delete($id)
    {
        self::$cachedData = null;
        return $this->model->destroy($id);
    }
}
