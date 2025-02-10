<?php

namespace App\Http\Controllers;

use App\Repositories\UpahRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UpahController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(UpahRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findByKaryawanId($karyawan_id) {
        $data = $this->repo->findByKaryawanId($karyawan_id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'aktif' => $req->query('aktif'),
            'staf' => $req->query('staf'),
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function updateOrCreate(Request $req, $karyawan_id) {
        $this->validate($req, [
            'gaji' => 'numeric',
            'uang_makan' => 'numeric',
            'makan_harian' => 'in:Y,N',
            'overtime' => 'in:Y,N'
        ]);
        $inputs = $req->only(['gaji', 'uang_makan', 'makan_harian', 'overtime']);
        $data = $this->repo->updateOrCreate($karyawan_id, $inputs);
        return $this->createdResponse($data, 'Upah berhasil diperbaharui');
    }

    public function create(Request $req, $karyawan_id) {
        $this->validate($req, [
            'uang_makan' => 'required|numeric',
            'makan_harian' => 'required|in:Y,N',
            'overtime' => 'required|in:Y,N'
        ]);
        $inputs = $req->only(['uang_makan', 'makan_harian', 'overtime']);
        $inputs['karyawan_id'] = $karyawan_id;
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Upah berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'uang_makan' => 'numeric',
            'makan_harian' => 'in:Y,N',
            'overtime' => 'in:Y,N'
        ]);
        $inputs = $req->only(['karyawan_id']);
        $inputs['uang_makan'] = $req->input('uang_makan');
        $inputs['makan_harian'] = $req->input('makan_harian');
        $inputs['overtime'] = $req->input('overtime');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Upah berhasil diubah');
    }

    public function updateByKaryawanId(Request $req, $karyawan_id) {
        $this->validate($req, [
            'uang_makan' => 'numeric',
            'makan_harian' => 'in:Y,N',
            'overtime' => 'in:Y,N'
        ]);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['uang_makan'] = $req->input('uang_makan');
        $inputs['makan_harian'] = $req->input('makan_harian');
        $inputs['overtime'] = $req->input('overtime');
        $data = $this->repo->updateByKaryawanId($karyawan_id, $inputs);
        return $this->successResponse($data, 'Upah berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Upah tidak ditemukan');
        }
        return $this->successResponse($data, 'Upah berhasil dihapus');
    }

    public function deleteByKaryawanId($karyawan_id) {
        $data = $this->repo->deleteByKaryawanId($karyawan_id);
        if($data == 0) {
            return $this->failRespNotFound('Upah tidak ditemukan');
        }
        return $this->successResponse($data, 'Upah berhasil dihapus');
    }

}
