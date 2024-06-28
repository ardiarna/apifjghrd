<?php

namespace App\Repositories;

interface OvertimeRepository {
    public function findById($id);
    public function findAll($inputs = []);
    public function findRekapByKaryawanIdAndTahun($karyawan_id, $tahun);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
