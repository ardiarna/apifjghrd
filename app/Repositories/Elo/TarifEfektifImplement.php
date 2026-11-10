<?php

namespace App\Repositories\Elo;

use App\Repositories\TarifEfektifRepository;
use App\Models\TarifEfektif;

class TarifEfektifImplement implements TarifEfektifRepository {

    protected $model;
    protected static $cachedTarif = null;

    function __construct(TarifEfektif $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    protected function getCachedTarif() {
        if (self::$cachedTarif === null) {
            self::$cachedTarif = $this->model->query()
                ->orderBy('penghasilan', 'desc')
                ->get()
                ->groupBy('ter');
        }
        return self::$cachedTarif;
    }

    public function findByTerAndPenghasilan($ter, $penghasilan) {
        $cached = $this->getCachedTarif();
        if (isset($cached[$ter])) {
            return $cached[$ter]->first(function($item) use ($penghasilan) {
                return $item->penghasilan <= $penghasilan;
            });
        }
        return null;
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
        $hasil->orderBy('ter')->orderBy('penghasilan');
        return $hasil->get();
    }

    public function create(array $inputs) {
        self::$cachedTarif = null;
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
        self::$cachedTarif = null;
        return $model;
    }

    public function delete($id) {
        self::$cachedTarif = null;
        return $this->model->destroy($id);
    }

}
