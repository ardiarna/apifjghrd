<?php

namespace App\Repositories;

interface PayrollHeaderRepository {
    public function findById($id);
    public function findAll($inputs = []);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function updateSummary($id);
    public function kunciPayroll($id);
    public function delete($id);
}
