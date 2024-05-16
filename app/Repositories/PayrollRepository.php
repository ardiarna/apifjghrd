<?php

namespace App\Repositories;

interface PayrollRepository {
    public function findById($karyawan_id, $id);
    public function findAll($inputs = []);
    public function create($header_id, array $listInputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deletesByHeaderId($header_id);
}
