<?php

namespace App\Http\Controllers;

use App\Repositories\KeluargaKontakRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class KeluargaKontakController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(KeluargaKontakRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($karyawan_id, $id) {
        $data = $this->repo->findById($karyawan_id, $id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req, $karyawan_id) {
        $data = $this->repo->findAll(['karyawan_id' => $karyawan_id, 'search_by' => $req->query('search_by'), 'value' => $req->query('value'), 'sort_by' => $req->query('sort_by'), 'sort_order' => $req->query('sort_order')]);
        return $this->successResponse($data);
    }

    public function create(Request $req, $karyawan_id) {
        $this->validate($req, [
            'nama' => 'required',
            'telepon' => 'required|numeric',
            'email' => 'email'
        ]);
        $inputs = $req->only(['nama', 'telepon']);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['email'] = $req->input('email');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Kontak Keluarga berhasil dibuat');
    }

    public function update(Request $req, $karyawan_id, $id) {
        $this->validate($req, [
            'telepon' => 'numeric',
            'email' => 'email'
        ]);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['nama'] = $req->input('nama');
        $inputs['telepon'] = $req->input('telepon');
        $inputs['email'] = $req->input('email');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Kontak Keluarga berhasil diubah');
    }

    public function delete($karyawan_id, $id) {
        if($id == 'all') {
            $data = $this->repo->deletesByKaryawanId($karyawan_id);
        } else {
            $data = $this->repo->delete($id);
        }
        if($data == 0) {
            return $this->failRespNotFound('Kontak Keluarga tidak ditemukan');
        }
        return $this->successResponse($data, 'Kontak Keluarga berhasil dihapus');
    }

}
