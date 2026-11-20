<?php

namespace App\Repositories\Elo;

use App\Repositories\NoteRepository;
use App\Models\Note;

class NoteImplement implements NoteRepository {

    protected $model;
    protected static $cachedData = null;

    function __construct(Note $model) {
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
        return $cached->firstWhere('kode', $kode) ?? $this->model->where('kode', $kode)->first();
    }

    public function findAll($inputs = []) {
        $hasSearch = !empty($inputs['search_by']) && !empty($inputs['value']);
        $hasKode = !empty($inputs['kode']);
        $hasCustomSort = !empty($inputs['sort_by']) && $inputs['sort_by'] !== 'id';

        if (!$hasSearch && !$hasKode && !$hasCustomSort) {
            $cached = $this->getCachedData();
            if (!empty($inputs['sort_order']) && strtolower($inputs['sort_order']) === 'desc') {
                return $cached->sortByDesc('id')->values();
            }
            return $cached;
        }

        $hasil = $this->model->query();
        if ($hasSearch) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if ($hasKode) {
            $hasil->where('kode', $inputs['kode']);
        }
        if (!empty($inputs['sort_by'])) {
            $sort_order = !empty($inputs['sort_order']) ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('id');
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
        if (isset($inputs['teks'])) {
            $model->teks = $inputs['teks'];
        }
        if (isset($inputs['kode'])) {
            $model->kode = $inputs['kode'];
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
