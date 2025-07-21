<?php

namespace App\Http\Controllers;

use App\Repositories\PhkRepository;
use App\Repositories\KaryawanRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PhkController extends Controller
{
    use ApiResponser;

    protected $repo, $karyawanRepo;

    public function __construct(PhkRepository $repo, KaryawanRepository $karyawanRepo) {
        $this->repo = $repo;
        $this->karyawanRepo = $karyawanRepo;
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
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'status_phk_id' => 'required'
        ]);
        $inputs = $req->only(['tanggal_awal', 'tanggal_akhir', 'status_phk_id']);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['keterangan'] = $req->input('keterangan');
        $phk = $this->repo->create($inputs);
        $this->karyawanRepo->setNonAktif($karyawan_id, $phk->id, $inputs['tanggal_akhir']);
        return $this->createdResponse($phk, 'PHK berhasil dibuat');
    }

    public function update(Request $req, $karyawan_id, $id) {
        $this->validate($req, [
            'tanggal_awal' => 'date',
            'tanggal_akhir' => 'date'
        ]);
        $inputs['karyawan_id'] = $karyawan_id;
        $inputs['tanggal_awal'] = $req->input('tanggal_awal');
        $inputs['tanggal_akhir'] = $req->input('tanggal_akhir');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['status_phk_id'] = $req->input('status_phk_id');
        $inputs['keterangan'] = $req->input('keterangan');
        $phk = $this->repo->update($id, $inputs);
        $this->karyawanRepo->setNonAktif($karyawan_id, $id, $inputs['tanggal_akhir']);
        return $this->successResponse($phk, 'PHK berhasil diubah');
    }

    public function delete($karyawan_id, $id) {
        if($id == 'all') {
            $data = $this->repo->deletesByKaryawanId($karyawan_id);
        } else {
            $data = $this->repo->delete($id);
        }
        if($data == 0) {
            return $this->failRespNotFound('PHK tidak ditemukan');
        }
        return $this->successResponse($data, 'PHK berhasil dihapus');
    }

}
