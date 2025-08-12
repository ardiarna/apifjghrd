<?php

namespace App\Http\Controllers;

use App\Repositories\OvertimeRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(OvertimeRepository $repo) {
        $this->repo = $repo;
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
        $data = $this->repo->findRekapByKaryawanIdAndTahun($karyawan_id, $tahun);
        $data->tahun = $tahun;
        return $this->successResponse($data);
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
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Overtime berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Overtime dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Overtime berhasil dihapus');
    }

    public function deleteAll(Request $req) {
        $tahun = $req->query('tahun');
        $bulan = $req->query('bulan');
        $jenis = $req->query('jenis');
        if (empty($tahun) || empty($bulan)) {
            return $this->failRespBadReq('Tahun dan bulan wajib diisi');
        }
        $data = $this->repo->deleteAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'jenis' => $jenis,
        ]);
        if($data == 0) {
            return $this->failRespNotFound("Overtime tahun {$tahun} bulan {$bulan} tidak ditemukan");
        }
        return $this->successResponse($data, "Overtime tahun {$tahun} bulan {$bulan} sebanyak {$data} data berhasil dihapus");
    }

}
