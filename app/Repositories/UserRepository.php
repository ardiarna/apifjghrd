<?php

namespace App\Repositories;

interface UserRepository {
    public function findById($id);
    public function findByEmail(string $email);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function editPassword($id, string $password);
    public function tokenPush($id, string $token_push);
    public function photo($id, string $namafoto);
    public function delete($id);
}
