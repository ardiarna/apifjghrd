<?php

namespace App\Http\Controllers;

use App\Repositories\PayrollPhkRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PayrollPhkController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(PayrollPhkRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $karyawan_id = request()->query('karyawan_id');
        $data = $this->repo->findById($karyawan_id, $id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'karyawan_id' => $req->query('karyawan_id'),
            'tahun'       => $req->query('tahun'),
            'bulan'       => $req->query('bulan'),
            'aktif'       => $req->query('aktif'),
            'staf'        => $req->query('staf'),
            'area'        => $req->query('area'),
            'engineer'    => $req->query('engineer'),
            'pph21'       => $req->query('pph21'),
            'sort_by'     => $req->query('sort_by'),
            'sort_order'  => $req->query('sort_order'),
        ]);
        return $this->successResponse($data);
    }

    public function findByKaryawanId(Request $req, $karyawan_id) {
        $data = $this->repo->findAll([
            'karyawan_id' => $karyawan_id,
            'tahun'       => $req->query('tahun'),
            'bulan'       => $req->query('bulan'),
            'pph21'       => $req->query('pph21'),
            'sort_by'     => $req->query('sort_by'),
            'sort_order'  => $req->query('sort_order'),
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id'           => 'required',
            'tahun'                 => 'required|integer',
            'bulan'                 => 'required|integer',
            'gaji'                  => 'required|numeric',
            'kenaikan_gaji'         => 'required|numeric',
            'makan_harian'          => 'required|in:Y,N',
            'hari_makan'            => 'required|numeric',
            'uang_makan_harian'     => 'required|numeric',
            'uang_makan_jumlah'     => 'required|numeric',
            'overtime_fjg'          => 'required|numeric',
            'overtime_cus'          => 'required|numeric',
            'medical'               => 'required|numeric',
            'thr'                   => 'required|numeric',
            'bonus'                 => 'required|numeric',
            'insentif'              => 'required|numeric',
            'telkomsel'             => 'required|numeric',
            'lain'                  => 'required|numeric',
            'pot_25_hari'           => 'required|numeric',
            'pot_25_jumlah'         => 'required|numeric',
            'pot_telepon'           => 'required|numeric',
            'pot_bensin'            => 'required|numeric',
            'pot_kas'               => 'required|numeric',
            'pot_cicilan'           => 'required|numeric',
            'pot_bpjs'              => 'required|numeric',
            'pot_cuti_hari'         => 'required|numeric',
            'pot_cuti_jumlah'       => 'required|numeric',
            'pot_kompensasi_jam'    => 'required|numeric',
            'pot_kompensasi_jumlah' => 'required|numeric',
            'pot_lain'              => 'required|numeric',
            'total_diterima'        => 'required|numeric',
        ]);
        $inputs = $req->only([
            'karyawan_id', 'tahun', 'bulan', 'gaji', 'kenaikan_gaji', 'makan_harian',
            'hari_makan', 'uang_makan_harian', 'uang_makan_jumlah', 'overtime_fjg', 'overtime_cus',
            'medical', 'thr', 'bonus', 'insentif', 'telkomsel', 'lain',
            'pot_25_hari', 'pot_25_jumlah', 'pot_telepon', 'pot_bensin', 'pot_kas', 'pot_cicilan', 'pot_bpjs',
            'pot_cuti_hari', 'pot_cuti_jumlah', 'pot_kompensasi_jam', 'pot_kompensasi_jumlah',
            'pot_lain', 'total_diterima',
        ]);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Payroll PHK berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id'           => 'required',
            'tahun'                 => 'required|integer',
            'bulan'                 => 'required|integer',
            'gaji'                  => 'numeric',
            'kenaikan_gaji'         => 'numeric',
            'makan_harian'          => 'in:Y,N',
            'hari_makan'            => 'numeric',
            'uang_makan_harian'     => 'numeric',
            'uang_makan_jumlah'     => 'numeric',
            'overtime_fjg'          => 'numeric',
            'overtime_cus'          => 'numeric',
            'medical'               => 'numeric',
            'thr'                   => 'numeric',
            'bonus'                 => 'numeric',
            'insentif'              => 'numeric',
            'telkomsel'             => 'numeric',
            'lain'                  => 'numeric',
            'pot_25_hari'           => 'numeric',
            'pot_25_jumlah'         => 'numeric',
            'pot_telepon'           => 'numeric',
            'pot_bensin'            => 'numeric',
            'pot_kas'               => 'numeric',
            'pot_cicilan'           => 'numeric',
            'pot_bpjs'              => 'numeric',
            'pot_cuti_hari'         => 'numeric',
            'pot_cuti_jumlah'       => 'numeric',
            'pot_kompensasi_jam'    => 'numeric',
            'pot_kompensasi_jumlah' => 'numeric',
            'pot_lain'              => 'numeric',
            'total_diterima'        => 'numeric',
        ]);
        $inputs['karyawan_id']           = $req->input('karyawan_id');
        $inputs['tahun']                 = $req->input('tahun');
        $inputs['bulan']                 = $req->input('bulan');
        $inputs['gaji']                  = $req->input('gaji');
        $inputs['kenaikan_gaji']         = $req->input('kenaikan_gaji');
        $inputs['makan_harian']          = $req->input('makan_harian');
        $inputs['hari_makan']            = $req->input('hari_makan');
        $inputs['uang_makan_harian']     = $req->input('uang_makan_harian');
        $inputs['uang_makan_jumlah']     = $req->input('uang_makan_jumlah');
        $inputs['overtime_fjg']          = $req->input('overtime_fjg');
        $inputs['overtime_cus']          = $req->input('overtime_cus');
        $inputs['medical']               = $req->input('medical');
        $inputs['thr']                   = $req->input('thr');
        $inputs['bonus']                 = $req->input('bonus');
        $inputs['insentif']              = $req->input('insentif');
        $inputs['telkomsel']             = $req->input('telkomsel');
        $inputs['lain']                  = $req->input('lain');
        $inputs['pot_25_hari']           = $req->input('pot_25_hari');
        $inputs['pot_25_jumlah']         = $req->input('pot_25_jumlah');
        $inputs['pot_telepon']           = $req->input('pot_telepon');
        $inputs['pot_bensin']            = $req->input('pot_bensin');
        $inputs['pot_kas']               = $req->input('pot_kas');
        $inputs['pot_cicilan']           = $req->input('pot_cicilan');
        $inputs['pot_bpjs']              = $req->input('pot_bpjs');
        $inputs['pot_cuti_hari']         = $req->input('pot_cuti_hari');
        $inputs['pot_cuti_jumlah']       = $req->input('pot_cuti_jumlah');
        $inputs['pot_kompensasi_jam']    = $req->input('pot_kompensasi_jam');
        $inputs['pot_kompensasi_jumlah'] = $req->input('pot_kompensasi_jumlah');
        $inputs['pot_lain']              = $req->input('pot_lain');
        $inputs['total_diterima']        = $req->input('total_diterima');
        $inputs['keterangan']            = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Payroll PHK berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Payroll PHK tidak ditemukan');
        }
        return $this->successResponse($data, 'Payroll PHK berhasil dihapus');
    }

}
