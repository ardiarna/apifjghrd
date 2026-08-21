<?php

namespace App\Repositories;

interface JenisCutiKhususRepository
{
    public function findAll($inputs = []);
    public function findById($id);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
