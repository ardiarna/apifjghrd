<?php

namespace App\Http\Controllers;

use App\Repositories\CustomerRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(CustomerRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required'
        ]);
        $inputs = $req->only(['nama']);
        $inputs['alamat'] = $req->input('alamat');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Customer berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $inputs['nama'] = $req->input('nama');
        $inputs['alamat'] = $req->input('alamat');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Customer berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Customer tidak ditemukan');
        }
        return $this->successResponse($data, 'Customer berhasil dihapus');
    }

}
