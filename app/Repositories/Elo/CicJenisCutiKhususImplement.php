<?php

namespace App\Repositories\Elo;

use App\Repositories\CicJenisCutiKhususRepository;
use App\Models\CicJenisCutiKhusus;

class CicJenisCutiKhususImplement implements CicJenisCutiKhususRepository
{
    public function findAll($inputs = [])
    {
        return CicJenisCutiKhusus::orderBy('urutan', 'asc')->get();
    }

    public function findById($id)
    {
        return CicJenisCutiKhusus::find($id);
    }

    public function create(array $inputs)
    {
        return CicJenisCutiKhusus::create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = CicJenisCutiKhusus::find($id);
        if ($model) {
            $model->update($inputs);
            return $model;
        }
        return null;
    }

    public function delete($id)
    {
        return CicJenisCutiKhusus::destroy($id);
    }
}
