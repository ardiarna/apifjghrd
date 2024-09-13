<?php

namespace App\Http\Controllers;

use App\Repositories\UangPhkRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UangPhkController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(UangPhkRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Uang PHK dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'tahun' => $req->query('tahun'),
            'karyawan_id' => $req->query('karyawan_id')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'tahun' => 'required|integer',
            'kompensasi' => 'numeric',
            'uang_pisah' => 'numeric',
            'pesangon' => 'numeric',
            'masa_kerja' => 'numeric',
            'penggantian_hak' => 'numeric'
        ]);
        $inputs = $req->only(['karyawan_id', 'tahun']);
        $inputs['kompensasi'] = $req->input('kompensasi');
        $inputs['uang_pisah'] = $req->input('uang_pisah');
        $inputs['pesangon'] = $req->input('pesangon');
        $inputs['masa_kerja'] = $req->input('masa_kerja');
        $inputs['penggantian_hak'] = $req->input('penggantian_hak');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Uang PHK berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'tahun' => 'required|integer',
            'kompensasi' => 'numeric',
            'uang_pisah' => 'numeric',
            'pesangon' => 'numeric',
            'masa_kerja' => 'numeric',
            'penggantian_hak' => 'numeric'
        ]);
        $inputs = $req->only(['karyawan_id', 'tahun']);
        $inputs['kompensasi'] = $req->input('kompensasi');
        $inputs['uang_pisah'] = $req->input('uang_pisah');
        $inputs['pesangon'] = $req->input('pesangon');
        $inputs['masa_kerja'] = $req->input('masa_kerja');
        $inputs['penggantian_hak'] = $req->input('penggantian_hak');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Uang PHK berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Uang PHK dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Uang PHK berhasil dihapus');
    }

}
