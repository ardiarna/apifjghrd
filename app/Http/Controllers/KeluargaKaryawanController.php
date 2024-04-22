<?php

namespace App\Http\Controllers;

use App\Repositories\KeluargaKaryawanRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class KeluargaKaryawanController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(KeluargaKaryawanRepository $repo) {
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
            'nomor_ktp' => 'digits:16',
            'hubungan' => 'required|in:S,I,A',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'telepon' => 'numeric',
            'email' => 'email'
        ]);
        $inputs = $req->only(['nama', 'hubungan', 'tempat_lahir', 'tanggal_lahir']);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['nomor_ktp'] = $req->input('nomor_ktp');
        $inputs['telepon'] = $req->input('telepon');
        $inputs['email'] = $req->input('email');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Keluarga Karyawan berhasil dibuat');
    }

    public function update(Request $req, $karyawan_id, $id) {
        $this->validate($req, [
            'nomor_ktp' => 'digits:16',
            'hubungan' => 'in:S,I,A',
            'tanggal_lahir' => 'date',
            'telepon' => 'numeric',
            'email' => 'email'
        ]);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['nama'] = $req->input('nama');
        $inputs['nomor_ktp'] = $req->input('nomor_ktp');
        $inputs['hubungan'] = $req->input('hubungan');
        $inputs['tempat_lahir'] = $req->input('tempat_lahir');
        $inputs['tanggal_lahir'] = $req->input('tanggal_lahir');
        $inputs['telepon'] = $req->input('telepon');
        $inputs['email'] = $req->input('email');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Keluarga Karyawan berhasil diubah');
    }

    public function delete($karyawan_id, $id) {
        if($id == 'all') {
            $data = $this->repo->deletesByKaryawanId($karyawan_id);
        } else {
            $data = $this->repo->delete($id);
        }
        if($data == 0) {
            return $this->failRespNotFound('Keluarga Karyawan tidak ditemukan');
        }
        return $this->successResponse($data, 'Keluarga Karyawan berhasil dihapus');
    }

}
