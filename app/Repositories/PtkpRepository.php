<?php

namespace App\Repositories;

interface PtkpRepository {
    public function findById($id);
    public function findByKode($kode);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
