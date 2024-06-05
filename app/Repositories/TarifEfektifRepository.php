<?php

namespace App\Repositories;

interface TarifEfektifRepository {
    public function findById($id);
    public function findByTerAndPenghasilan($ter, $penghasilan);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
