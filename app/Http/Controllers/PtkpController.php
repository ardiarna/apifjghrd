<?php

namespace App\Http\Controllers;

use App\Repositories\PtkpRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PtkpController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(PtkpRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findByKode(Request $req) {
        $kode = $req->input('kode');
        $data = $this->repo->findByKode($kode);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'ter' => $req->query('ter'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'kode' => 'required',
            'jumlah' => 'required|integer',
            'ter' => 'required|in:A,B,C'
        ]);
        $inputs = $req->only(['kode', 'jumlah', 'ter']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'PTKP berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'jumlah' => 'integer',
            'ter' => 'in:A,B,C'
        ]);
        $inputs['kode'] = $req->input('kode');
        $inputs['jumlah'] = $req->input('jumlah');
        $inputs['ter'] = $req->input('ter');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'PTKP berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('PTKP tidak ditemukan');
        }
        return $this->successResponse($data, 'PTKP berhasil dihapus');
    }

}
