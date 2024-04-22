<?php

namespace App\Repositories;

interface StatusPhkRepository {
    public function findById($id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
