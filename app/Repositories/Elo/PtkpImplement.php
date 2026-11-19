<?php

namespace App\Repositories\Elo;

use App\Repositories\PtkpRepository;
use App\Models\Ptkp;

class PtkpImplement implements PtkpRepository {

    protected $model;
    protected static $cachedData = null;

    function __construct(Ptkp $model) {
        $this->model = $model;
    }

    protected function getCachedData() {
        if (self::$cachedData === null) {
            self::$cachedData = $this->model->query()->orderBy('id')->get();
        }
        return self::$cachedData;
    }

    public function findById($id) {
        $cached = $this->getCachedData();
        return $cached->firstWhere('id', $id) ?? $this->model->find($id);
    }

    public function findByKode($kode) {
        $cached = $this->getCachedData();
        return $cached->firstWhere('kode', $kode) ?? $this->model->query()->where('kode', $kode)->first();
    }

    public function findAll($inputs = []) {
        $hasTer = isset($inputs['ter']) && $inputs['ter'] !== '' && $inputs['ter'] !== null;
        $hasSort = !empty($inputs['sort_by']);

        if (!$hasSort) {
            $cached = $this->getCachedData();
            if ($hasTer) {
                return $cached->where('ter', $inputs['ter'])->values();
            }
            return $cached;
        }

        $hasil = $this->model->query();
        if ($hasTer) {
            $hasil->where('ter', $inputs['ter']);
        }
        if ($hasSort) {
            $sort_order = !empty($inputs['sort_order']) ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        self::$cachedData = null;
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if (isset($inputs['kode']) && $inputs['kode'] !== null) {
            $model->kode = $inputs['kode'];
        }
        if (isset($inputs['jumlah']) && $inputs['jumlah'] !== null) {
            $model->jumlah = $inputs['jumlah'];
        }
        if (isset($inputs['ter']) && $inputs['ter'] !== null) {
            $model->ter = $inputs['ter'];
        }
        $model->save();
        self::$cachedData = null;
        return $model;
    }

    public function delete($id) {
        self::$cachedData = null;
        return $this->model->destroy($id);
    }

}
