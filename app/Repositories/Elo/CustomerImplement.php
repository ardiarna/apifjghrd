<?php

namespace App\Repositories\Elo;

use App\Repositories\CustomerRepository;
use App\Models\Customer;

class CustomerImplement implements CustomerRepository {

    protected $model;
    protected static $cachedData = null;

    function __construct(Customer $model) {
        $this->model = $model;
    }

    protected function getCachedData() {
        if (self::$cachedData === null) {
            self::$cachedData = $this->model->query()->orderBy('nama')->get();
        }
        return self::$cachedData;
    }

    public function findById($id) {
        $cached = $this->getCachedData();
        return $cached->firstWhere('id', $id) ?? $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasSearch = !empty($inputs['search_by']) && !empty($inputs['value']);
        $hasCustomSort = !empty($inputs['sort_by']) && $inputs['sort_by'] !== 'nama';

        if (!$hasSearch && !$hasCustomSort) {
            $cached = $this->getCachedData();
            if (!empty($inputs['sort_order']) && strtolower($inputs['sort_order']) === 'desc') {
                return $cached->sortByDesc('nama')->values();
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
            $hasil->orderBy('nama');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        self::$cachedData = null;
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if (isset($inputs['nama'])) {
            $model->nama = $inputs['nama'];
        }
        if (isset($inputs['alamat'])) {
            $model->alamat = $inputs['alamat'];
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
