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
        $inputs['uang_phk_id'] = !empty($req->input('uang_phk_id')) ? $req->input('uang_phk_id') : null;
        $inputs['payroll_phk_id'] = !empty($req->input('payroll_phk_id')) ? $req->input('payroll_phk_id') : null;
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
        if ($req->has('uang_phk_id')) {
            $inputs['uang_phk_id'] = !empty($req->input('uang_phk_id')) ? $req->input('uang_phk_id') : null;
        }
        if ($req->has('payroll_phk_id')) {
            $inputs['payroll_phk_id'] = !empty($req->input('payroll_phk_id')) ? $req->input('payroll_phk_id') : null;
        }
        $phk = $this->repo->update($id, $inputs);
        if (!$req->input('is_history') && !$req->query('is_history')) {
            $karyawan = $this->karyawanRepo->findById($karyawan_id);
            if ($karyawan && $karyawan->phk_id == $id) {
                $this->karyawanRepo->setNonAktif($karyawan_id, $id, $inputs['tanggal_akhir']);
            }
        }
        return $this->successResponse($phk, 'PHK berhasil diubah');
    }

    public function delete($karyawan_id, $id) {
        $karyawan = $this->karyawanRepo->findById($karyawan_id);
        $isAktifPhk = ($karyawan && $karyawan->phk_id == $id);
        $destroy = request()->query('destroy') == '1' || request()->input('destroy') == 1;

        if (!$isAktifPhk || $destroy) {
            $this->repo->delete($id);
            if ($isAktifPhk) {
                $this->karyawanRepo->setAktif($karyawan_id);
            }
            return $this->successResponse(1, 'Data PHK berhasil dihapus');
        }

        $this->karyawanRepo->setAktif($karyawan_id);
        return $this->successResponse(1, 'Karyawan berhasil diaktifkan kembali');
    }

}
