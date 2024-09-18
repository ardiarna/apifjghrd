<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(AreaRepository $repo) {
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
            'kode' => 'required|size:3',
            'nama' => 'required',
            'urutan' => 'required|integer|min:1'
        ]);
        $inputs = $req->only(['kode', 'nama', 'urutan']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Area berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'kode' => 'size:3',
            'urutan' => 'integer|min:1'
        ]);
        $inputs['kode'] = $req->input('kode');
        $inputs['nama'] = $req->input('nama');
        $inputs['urutan'] = $req->input('urutan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Area berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Area tidak ditemukan');
        }
        return $this->successResponse($data, 'Area berhasil dihapus');
    }

}
