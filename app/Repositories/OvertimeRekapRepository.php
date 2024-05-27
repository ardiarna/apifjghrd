<?php

namespace App\Repositories;

interface OvertimeRekapRepository {
    public function findByKaryawanIdAndTahun($karyawan_id, $tahun);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deletesByKaryawanId($karyawan_id);
}
