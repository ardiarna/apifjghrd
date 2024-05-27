<?php

namespace App\Http\Controllers;

use App\Repositories\OvertimeRepository;
use App\Repositories\OvertimeRekapRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use ApiResponser;

    protected $repo, $repoRekap;

    public function __construct(OvertimeRepository $repo, OvertimeRekapRepository $repoRekap) {
        $this->repo = $repo;
        $this->repoRekap = $repoRekap;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Overtime dengan id '.$id.' tidak ditemukan');
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

    public function findRekapByKaryawanIdAndTahun($karyawan_id, $tahun) {
        $data = $this->repoRekap->findByKaryawanIdAndTahun($karyawan_id, $tahun);
        if($data) {
            return $this->successResponse($data);
        } else {
            $data = $this->repoRekap->create([
                "karyawan_id" => $karyawan_id,
                "tahun" => $tahun
            ]);
            return $this->successResponse($data);
        }
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:F,C',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->create($inputs);
        $this->updateRekap($data);
        return $this->createdResponse($data, 'Overtime berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:F,C',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $dataLama = $this->repo->findById($id);
        if($dataLama == null) {
            return $this->failRespNotFound('Overtime dengan id '.$id.' tidak ditemukan');
        }
        $rekap = $this->repoRekap->findByKaryawanIdAndTahun($dataLama->karyawan_id, $dataLama->tahun);
        if($rekap) {
            $m = $dataLama->bulan;
            $jenis = $dataLama->jenis == 'F' ? 'fjg_' : 'cus_';
            $this->repoRekap->update($rekap->id, [
                "karyawan_id" => $rekap->karyawan_id,
                "tahun" => $rekap->tahun,
                $jenis.$m => ($rekap->{$jenis.$m}-$dataLama->jumlah)
            ]);
        }
        $data = $this->repo->update($id, $inputs);
        $this->updateRekap($data);
        return $this->successResponse($data, 'Overtime berhasil diubah');
    }

    public function delete($id) {
        $dataLama = $this->repo->findById($id);
        if($dataLama == null) {
            return $this->failRespNotFound('Overtime dengan id '.$id.' tidak ditemukan');
        }
        $rekap = $this->repoRekap->findByKaryawanIdAndTahun($dataLama->karyawan_id, $dataLama->tahun);
        if($rekap) {
            $m = $dataLama->bulan;
            $jenis = $dataLama->jenis == 'F' ? 'fjg_' : 'cus_';
            $this->repoRekap->update($rekap->id, [
                "karyawan_id" => $rekap->karyawan_id,
                "tahun" => $rekap->tahun,
                $jenis.$m => ($rekap->{$jenis.$m}-$dataLama->jumlah)
            ]);
        }
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Overtime dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Overtime berhasil dihapus');
    }

    public function updateRekap($data) {
        $rekap = $this->repoRekap->findByKaryawanIdAndTahun($data->karyawan_id, $data->tahun);
        $m = $data->bulan;
        $jenis = $data->jenis == 'F' ? 'fjg_' : 'cus_';
        if($rekap) {
            $this->repoRekap->update($rekap->id, [
                "karyawan_id" => $rekap->karyawan_id,
                "tahun" => $rekap->tahun,
                $jenis.$m => ($rekap->{$jenis.$m}+$data->jumlah)
            ]);
        } else {
            $this->repoRekap->create([
                "karyawan_id" => $data->karyawan_id,
                "tahun" => $data->tahun,
                $jenis.$m => $data->jumlah
            ]);
        }
    }

}
