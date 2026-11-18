<?php

namespace App\Repositories\Elo;

use App\Repositories\AreaRepository;
use App\Models\Area;

class AreaImplement implements AreaRepository {

    protected $model;
    protected static $cachedData = null;

    function __construct(Area $model) {
        $this->model = $model;
    }

    protected function getCachedData() {
        if (self::$cachedData === null) {
            self::$cachedData = $this->model->query()->orderBy('urutan')->get();
        }
        return self::$cachedData;
    }

    public function findById($id) {
        $cached = $this->getCachedData();
        return $cached->firstWhere('id', $id) ?? $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasSearch = !empty($inputs['search_by']) && !empty($inputs['value']);
        $hasCustomSort = !empty($inputs['sort_by']) && $inputs['sort_by'] !== 'urutan';

        if (!$hasSearch && !$hasCustomSort) {
            $cached = $this->getCachedData();
            if (!empty($inputs['sort_order']) && strtolower($inputs['sort_order']) === 'desc') {
                return $cached->sortByDesc('urutan')->values();
            }
            return $cached;
        }

        $hasil = $this->model->query();
        if ($hasSearch) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if (!empty($inputs['sort_by'])) {
            $sort_order = !empty($inputs['sort_order']) ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('urutan');
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
        if (isset($inputs['nama']) && $inputs['nama'] !== null) {
            $model->nama = $inputs['nama'];
        }
        if (isset($inputs['urutan']) && $inputs['urutan'] !== null) {
            $model->urutan = $inputs['urutan'];
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
