<?php

namespace App\Repositories;

interface KaryawanRepository {
    public function findById($id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function setNonAktif($id, $phk_id, $tanggal_keluar);
}
