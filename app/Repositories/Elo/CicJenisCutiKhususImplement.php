<?php

namespace App\Repositories\Elo;

use App\Repositories\CicJenisCutiKhususRepository;
use App\Models\CicJenisCutiKhusus;

class CicJenisCutiKhususImplement implements CicJenisCutiKhususRepository
{
    protected static $cachedData = null;

    protected function getCachedData()
    {
        if (self::$cachedData === null) {
            self::$cachedData = CicJenisCutiKhusus::orderBy('urutan', 'asc')->get();
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
        return $cached->firstWhere('id', $id) ?? CicJenisCutiKhusus::find($id);
    }

    public function create(array $inputs)
    {
        self::$cachedData = null;
        return CicJenisCutiKhusus::create($inputs);
    }

    public function update($id, array $inputs)
    {
        $model = CicJenisCutiKhusus::find($id);
        if ($model) {
            $model->update($inputs);
            self::$cachedData = null;
            return $model;
        }
        return null;
    }

    public function delete($id)
    {
        self::$cachedData = null;
        return CicJenisCutiKhusus::destroy($id);
    }
}
