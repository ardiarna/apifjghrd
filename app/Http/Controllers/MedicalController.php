<?php

namespace App\Http\Controllers;

use App\Repositories\MedicalRepository;
use App\Repositories\MedicalRekapRepository;
use App\Repositories\UpahRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class MedicalController extends Controller
{
    use ApiResponser;

    protected $repo, $repoRekap;

    public function __construct(MedicalRepository $repo, MedicalRekapRepository $repoRekap) {
        $this->repo = $repo;
        $this->repoRekap = $repoRekap;
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

    public function findRekapByKaryawanIdAndTahun(UpahRepository $upahRepo, $karyawan_id, $tahun) {
        $data = $this->repoRekap->findByKaryawanIdAndTahun($karyawan_id, $tahun);
        if($data) {
            // $data->tunjangan = $data->karyawan->kelamin == 'L' ? $data->gaji*2 : $data->gaji;
            // $data->klaim = $data->bln_1 + $data->bln_2 + $data->bln_3 + $data->bln_4 + $data->bln_5 + $data->bln_6 + $data->bln_7 + $data->bln_8 + $data->bln_9 + $data->bln_20 + $data->bln_11 + $data->bln_12;
            // $data->sisa = $data->tunjangan - $data->klaim;
            return $this->successResponse($data);
        } else {
            $upah = $upahRepo->findByKaryawanId($karyawan_id);
            $data = $this->repoRekap->create([
                "karyawan_id" => $karyawan_id,
                "tahun" => $tahun,
                'gaji' => $upah->gaji
            ]);
            return $this->successResponse($data);
        }
    }

    public function create(Request $req, UpahRepository $upahRepo) {
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
        if($data->jenis == 'R') {
            $this->updateRekap($upahRepo, $data);
        }
        return $this->createdResponse($data, 'Medical berhasil dibuat');
    }

    public function update(Request $req, UpahRepository $upahRepo, $id) {
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
        $dataLama = $this->repo->findById($id);
        if($dataLama == null) {
            return $this->failRespNotFound('Medical dengan id '.$id.' tidak ditemukan');
        }
        if($dataLama->jenis == 'R') {
            $rekap = $this->repoRekap->findByKaryawanIdAndTahun($dataLama->karyawan_id, $dataLama->tahun);
            $m = $dataLama->bulan;
            if($rekap) {
                $this->repoRekap->update($rekap->id, [
                    "karyawan_id" => $rekap->karyawan_id,
                    "tahun" => $rekap->tahun,
                    "bln_".$m => ($rekap->{"bln_".$m}-$dataLama->jumlah)
                ]);
            }
        }
        $data = $this->repo->update($id, $inputs);
        if($data->jenis == 'R') {
            $this->updateRekap($upahRepo, $data);
        }
        return $this->successResponse($data, 'Medical berhasil diubah');
    }

    public function delete($id) {
        $dataLama = $this->repo->findById($id);
        if($dataLama == null) {
            return $this->failRespNotFound('Medical dengan id '.$id.' tidak ditemukan');
        }
        if($dataLama->jenis == 'R') {
            $rekap = $this->repoRekap->findByKaryawanIdAndTahun($dataLama->karyawan_id, $dataLama->tahun);
            $m = $dataLama->bulan;
            if($rekap) {
                $this->repoRekap->update($rekap->id, [
                    "karyawan_id" => $rekap->karyawan_id,
                    "tahun" => $rekap->tahun,
                    "bln_".$m => ($rekap->{"bln_".$m}-$dataLama->jumlah)
                ]);
            }
        }
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Medical dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Medical berhasil dihapus');
    }

    public function updateRekap(UpahRepository $upahRepo, $data) {
        $rekap = $this->repoRekap->findByKaryawanIdAndTahun($data->karyawan_id, $data->tahun);
        $m = $data->bulan;
        if($rekap) {
            $this->repoRekap->update($rekap->id, [
                "karyawan_id" => $rekap->karyawan_id,
                "tahun" => $rekap->tahun,
                "bln_".$m => ($rekap->{"bln_".$m}+$data->jumlah)
            ]);
        } else {
            $upah = $upahRepo->findByKaryawanId($data->karyawan_id);
            $this->repoRekap->create([
                "karyawan_id" => $data->karyawan_id,
                "tahun" => $data->tahun,
                "bln_".$m => $data->jumlah,
                'gaji' => $upah->gaji
            ]);
        }
    }

}
