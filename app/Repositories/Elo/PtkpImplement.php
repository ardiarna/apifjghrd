<?php

namespace App\Repositories\Elo;

use App\Repositories\PtkpRepository;
use App\Models\Ptkp;

class PtkpImplement implements PtkpRepository {

    protected $model;

    function __construct(Ptkp $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findByKode($kode) {
        $model = $this->model->query()->where('kode', $kode)->first();
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        if(isset($inputs['ter']) && $inputs['ter'] != '') {
            $hasil->where('ter', $inputs['ter']);
        }
        if(isset($inputs['sort_by'])  && $inputs['sort_by'] != '') {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
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
        if($inputs['jumlah'] != null) {
            $model->jumlah = $inputs['jumlah'];
        }
        if($inputs['ter'] != null) {
            $model->ter = $inputs['ter'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
