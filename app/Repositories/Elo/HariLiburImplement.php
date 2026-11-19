<?php

namespace App\Repositories\Elo;

use App\Repositories\HariLiburRepository;
use App\Models\HariLibur;

class HariLiburImplement implements HariLiburRepository {

    protected $model;

    function __construct(HariLibur $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        if (!empty($inputs['tahun'])) {
            $tahun = $inputs['tahun'];
            $hasil->whereBetween('tanggal', ["$tahun-01-01", "$tahun-12-31"]);
        }
        if (!empty($inputs['search_by']) && !empty($inputs['value'])) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if (!empty($inputs['sort_by'])) {
            $sort_order = !empty($inputs['sort_order']) ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('tanggal');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($inputs['nama'] != null) {
            $model->nama = $inputs['nama'];
        }
        if($inputs['tanggal'] != null) {
            $model->tanggal = $inputs['tanggal'];
        }
        if(isset($inputs['iscutber'])) {
            $model->iscutber = $inputs['iscutber'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
