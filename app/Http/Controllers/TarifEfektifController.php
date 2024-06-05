<?php

namespace App\Http\Controllers;

use App\Repositories\TarifEfektifRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class TarifEfektifController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(TarifEfektifRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findByTerAndPenghasilan(Request $req) {
        $this->validate($req, [
            'ter' => 'required|in:A,B,C',
            'penghasilan' => 'required|integer',
        ]);
        $ter = $req->query('ter');
        $penghasilan = $req->query('penghasilan');
        $data = $this->repo->findByTerAndPenghasilan($ter, $penghasilan);
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
            'ter' => 'required|in:A,B,C',
            'penghasilan' => 'required|integer',
            'persen' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',

        ]);
        $inputs = $req->only(['ter', 'penghasilan', 'persen']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Tarif Efektif berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'ter' => 'in:A,B,C',
            'penghasilan' => 'integer',
            'persen' => 'numeric|regex:/^\d+(\.\d{1,2})?$/',

        ]);
        $inputs['ter'] = $req->input('ter');
        $inputs['penghasilan'] = $req->input('penghasilan');
        $inputs['persen'] = $req->input('persen');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Tarif Efektif berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Tarif Efektif tidak ditemukan');
        }
        return $this->successResponse($data, 'Tarif Efektif berhasil dihapus');
    }

}
