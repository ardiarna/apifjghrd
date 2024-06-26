<?php

namespace App\Repositories;

interface UpahRepository {
    public function findById($id);
    public function findByKaryawanId($karyawan_id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function updateByKaryawanId($karyawan_id, array $inputs);
    public function upsert(array $data, array $keys, array $updated);
    public function delete($id);
    public function deleteByKaryawanId($karyawan_id);
}
