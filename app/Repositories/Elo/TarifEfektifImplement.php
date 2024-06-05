<?php

namespace App\Repositories\Elo;

use App\Repositories\TarifEfektifRepository;
use App\Models\TarifEfektif;

class TarifEfektifImplement implements TarifEfektifRepository {

    protected $model;

    function __construct(TarifEfektif $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findByTerAndPenghasilan($ter, $penghasilan) {
        $model = $this->model->query()
            ->where('ter', $ter)
            ->where('penghasilan', '<=', $penghasilan)
            ->orderBy('penghasilan', 'desc')
            ->first();
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
        if($inputs['ter'] != null) {
            $model->ter = $inputs['ter'];
        }
        if($inputs['penghasilan'] != null) {
            $model->penghasilan = $inputs['penghasilan'];
        }
        if($inputs['persen'] != null) {
            $model->persen = $inputs['persen'];
        }

        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
