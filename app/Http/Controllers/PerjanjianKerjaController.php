<?php

namespace App\Http\Controllers;

use App\Repositories\PerjanjianKerjaRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PerjanjianKerjaController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(PerjanjianKerjaRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($karyawan_id, $id) {
        $data = $this->repo->findById($karyawan_id, $id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req, $karyawan_id) {
        $data = $this->repo->findAll([
            'karyawan_id' => $karyawan_id,
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req, $karyawan_id) {
        $this->validate($req, [
            'nomor' => 'required',
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'date',
            'status_kerja_id' => 'required'
        ]);
        $inputs = $req->only(['nomor', 'tanggal_awal', 'status_kerja_id']);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['tanggal_akhir'] = $req->input('tanggal_akhir');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Perjanjian Kerja berhasil dibuat');
    }

    public function update(Request $req, $karyawan_id, $id) {
        $this->validate($req, [
            'tanggal_awal' => 'date',
            'tanggal_akhir' => 'date'
        ]);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['nomor'] = $req->input('nomor');
        $inputs['tanggal_awal'] = $req->input('tanggal_awal');
        $inputs['tanggal_akhir'] = $req->input('tanggal_akhir');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Perjanjian Kerja berhasil diubah');
    }

    public function delete($karyawan_id, $id) {
        if($id == 'all') {
            $data = $this->repo->deletesByKaryawanId($karyawan_id);
        } else {
            $data = $this->repo->delete($id);
        }
        if($data == 0) {
            return $this->failRespNotFound('Perjanjian Kerja tidak ditemukan');
        }
        return $this->successResponse($data, 'Perjanjian Kerja berhasil dihapus');
    }

}
