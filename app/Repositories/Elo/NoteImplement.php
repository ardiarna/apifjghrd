<?php

namespace App\Repositories\Elo;

use App\Repositories\NoteRepository;
use App\Models\Note;

class NoteImplement implements NoteRepository {

    protected $model;

    function __construct(Note $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findByKode($kode) {
        return $this->model->where('kode', $kode)->first();
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query();
        if(isset($inputs['search_by']) && isset($inputs['value']) && $inputs['search_by'] && $inputs['value']) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if(isset($inputs['kode']) && $inputs['kode']) {
            $hasil->where('kode', $inputs['kode']);
        }
        if(isset($inputs['sort_by']) && $inputs['sort_by']) {
            $sort_order = (isset($inputs['sort_order']) && $inputs['sort_order']) ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('id');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if(isset($inputs['nama'])) {
            $model->nama = $inputs['nama'];
        }
        if(isset($inputs['teks'])) {
            $model->teks = $inputs['teks'];
        }
        if(isset($inputs['kode'])) {
            $model->kode = $inputs['kode'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
