<?php

namespace App\Repositories;

interface UangPhkRepository {
    public function findById($id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deletesByKaryawanId($karyawan_id);
}
