<?php

namespace App\Http\Controllers;

use App\Repositories\PotonganRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PotonganController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(PotonganRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Potongan dengan id '.$id.' tidak ditemukan');
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

    public function findRekapByKaryawanIdAndTahun($karyawan_id, $tahun) {
        // $data = $this->repoRekap->findByKaryawanIdAndTahun($karyawan_id, $tahun);
        // if($data) {
        //     return $this->successResponse($data);
        // } else {
        //     $data = $this->repoRekap->create([
        //         "karyawan_id" => $karyawan_id,
        //         "tahun" => $tahun
        //     ]);
        //     return $this->successResponse($data);
        // }
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:TB,TP,BN,KS,CC,BP,UL,KJ,LL',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'hari' => 'numeric',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['hari'] = $req->input('hari');
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Potongan berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'jenis' => 'required|in:TB,TP,BN,KS,CC,BP,UL,KJ,LL',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'hari' => 'numeric',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['karyawan_id', 'jenis', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['hari'] = $req->input('hari');
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Potongan berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Potongan dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Potongan berhasil dihapus');
    }

}
