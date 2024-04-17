<?php

namespace App\Http\Controllers;

use App\Repositories\AgamaRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class AgamaController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(AgamaRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll(['sort_by' => $req->query('sort_by'), 'sort_order' => $req->query('sort_order')]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required'
        ]);
        $inputs = $req->only(['nama']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Agama berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama']);
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Agama berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Agama tidak ditemukan');
        }
        return $this->successResponse($data, 'Agama berhasil dihapus');
    }

}
