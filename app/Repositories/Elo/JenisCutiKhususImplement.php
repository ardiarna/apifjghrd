<?php

namespace App\Repositories\Elo;

use App\Repositories\JenisCutiKhususRepository;
use App\Models\JenisCutiKhusus;

class JenisCutiKhususImplement implements JenisCutiKhususRepository
{
    protected $model;

    function __construct(JenisCutiKhusus $model)
    {
        $this->model = $model;
    }

    public function findAll($inputs = [])
    {
        return $this->model->orderBy('urutan')->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function create(array $inputs)
    {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = $this->model->findOrFail($id);

        if ($inputs['nama'] !== null) {
            $model->nama = $inputs['nama'];
        }
        if ($inputs['lama_hari'] !== null) {
            $model->lama_hari = $inputs['lama_hari'];
        }
        if ($inputs['satuan'] !== null) {
            $model->satuan = $inputs['satuan'];
        }
        if ($inputs['urutan'] !== null) {
            $model->urutan = $inputs['urutan'];
        }

        $model->save();
        return $model;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
