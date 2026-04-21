<?php

namespace App\Repositories;

interface PayrollPhkRepository {
    public function findById($karyawan_id, $id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deleteByKaryawanId($karyawan_id);
}
