<?php

namespace App\Http\Controllers;

use App\Repositories\DivisiRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(DivisiRepository $repo) {
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
            'kode' => 'required|size:2',
            'nama' => 'required',
            'urutan' => 'required|integer|min:1'
        ]);
        $inputs = $req->only(['kode', 'nama', 'urutan']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Divisi berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'kode' => 'required|size:2',
            'nama' => 'required',
            'urutan' => 'required|integer|min:1'
        ]);
        $inputs = $req->only(['kode', 'nama', 'urutan']);
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Divisi berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Divisi tidak ditemukan');
        }
        return $this->successResponse($data, 'Divisi berhasil dihapus');
    }

}
