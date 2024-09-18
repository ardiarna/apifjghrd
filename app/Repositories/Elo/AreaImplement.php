<?php

namespace App\Repositories\Elo;

use App\Repositories\AreaRepository;
use App\Models\Area;

class AreaImplement implements AreaRepository {

    protected $model;

    function __construct(Area $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        if($inputs['search_by'] && $inputs['value']) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if($inputs['sort_by']) {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('urutan');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($inputs['kode'] != null) {
            $model->kode = $inputs['kode'];
        }
        if($inputs['nama'] != null) {
            $model->nama = $inputs['nama'];
        }
        if($inputs['urutan'] != null) {
            $model->urutan = $inputs['urutan'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
