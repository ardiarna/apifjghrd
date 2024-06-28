<?php

namespace App\Http\Controllers;

use App\Repositories\MedicalRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class MedicalController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(MedicalRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Medical dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'tanggal_awal' => $req->query('tanggal_awal'),
            'tanggal_akhir' => $req->query('tanggal_akhir'),
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'jenis' => $req->query('jenis'),
            'karyawan_id' => $req->query('karyawan_id')
        ]);
        return $this->successResponse($data);
    }

    public function findRekapAll(Request $req) {
        $data = $this->repo->findAll([
            'tahun' => $req->query('tahun'),
            'karyawan_id' => $req->query('karyawan_id')
        ]);
        return $this->successResponse($data);
    }

    public function findRekapByKaryawanIdAndTahun(PayrollHeaderRepository $payrollRepo, $karyawan_id, $tahun) {
        $data = $this->repo->findRekapByKaryawanIdAndTahun($karyawan_id, $tahun);
        $data->gaji = $payrollRepo->findUpahByKaryawanIdAndTahun($karyawan_id, $tahun);
        $data->tahun = $tahun;
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:I,K,R',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Medical berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:I,K,R',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Medical berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Medical dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Medical berhasil dihapus');
    }

}
