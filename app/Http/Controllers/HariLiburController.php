<?php

namespace App\Http\Controllers;

use App\Repositories\HariLiburRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(HariLiburRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'tahun' => $req->query('tahun'),
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required|date'
        ]);
        $inputs = $req->only(['nama', 'tanggal']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Hari libur berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'tanggal' => 'date'
        ]);
        $inputs['nama'] = $req->input('nama');
        $inputs['tanggal'] = $req->input('tanggal');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Hari libur berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Hari libur tidak ditemukan');
        }
        return $this->successResponse($data, 'Hari libur berhasil dihapus');
    }

}
