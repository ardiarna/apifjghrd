<?php

namespace App\Repositories\Elo;

use App\Repositories\AgamaRepository;
use App\Models\Agama;

class AgamaImplement implements AgamaRepository {

    protected $model;

    function __construct(Agama $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        if($inputs['sort_by']) {
            if(strtolower($inputs['sort_order']) == 'desc') {
                return $this->model->orderByDesc($inputs['sort_by'])->get();
            }
            return $this->model->orderBy($inputs['sort_by'])->get();
        }
        if(strtolower($inputs['sort_order']) == 'desc') {
            return $this->model->orderByDesc('nama')->get();
        }
        return $this->model->orderBy('nama')->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
