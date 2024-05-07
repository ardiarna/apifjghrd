<?php

namespace App\Repositories;

interface PerjanjianKerjaRepository {
    public function findById($karyawan_id, $id);
    public function findAll($inputs = []);
    public function timelineMasaKerja($karyawan_id);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deletesByKaryawanId($karyawan_id);
}
