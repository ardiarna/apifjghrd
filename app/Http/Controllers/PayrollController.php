<?php

namespace App\Http\Controllers;

use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollRepository;
use App\Traits\ApiResponser;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use ApiResponser;

    protected $repoHeader, $repo;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repo) {
        $this->repoHeader = $repoHeader;
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repoHeader->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repoHeader->findAll([
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function findDetail(Request $req, $header_id) {
        $data = $this->repo->findAll([
            'header_id' => $header_id,
            'karyawan_id' => $req->query('karyawan_id'),
            'aktif' => $req->query('aktif'),
            'staf' => $req->query('staf'),
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function findDetailByKaryawanId(Request $req, $karyawan_id) {
        $data = $this->repo->findAll([
            'karyawan_id' => $karyawan_id,
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'pph21' => $req->query('pph21'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'payrolls' => 'required|array', // Memastikan bahwa data yang dikirimkan adalah array
            'payrolls.*.karyawan_id' => 'required', // Memastikan bahwa setiap karyawan_id ada dalam tabel karyawan
            'payrolls.*.gaji' => 'required|numeric',
            'payrolls.*.makan_harian' => 'required|in:Y,N',
            'payrolls.*.hari_makan' => 'required|numeric',
            'payrolls.*.uang_makan_harian' => 'required|numeric',
            'payrolls.*.uang_makan_jumlah' => 'required|numeric',
            'payrolls.*.overtime_fjg' => 'required|numeric',
            'payrolls.*.overtime_cus' => 'required|numeric',
            'payrolls.*.medical' => 'required|numeric',
            'payrolls.*.thr' => 'required|numeric',
            'payrolls.*.bonus' => 'required|numeric',
            'payrolls.*.insentif' => 'required|numeric',
            'payrolls.*.telkomsel' => 'required|numeric',
            'payrolls.*.lain' => 'required|numeric',
            'payrolls.*.pot_25_hari' => 'required|numeric',
            'payrolls.*.pot_25_jumlah' => 'required|numeric',
            'payrolls.*.pot_telepon' => 'required|numeric',
            'payrolls.*.pot_bensin' => 'required|numeric',
            'payrolls.*.pot_kas' => 'required|numeric',
            'payrolls.*.pot_cicilan' => 'required|numeric',
            'payrolls.*.pot_bpjs' => 'required|numeric',
            'payrolls.*.pot_cuti' => 'required|numeric',
            'payrolls.*.pot_lain' => 'required|numeric',
            'payrolls.*.total_diterima' => 'required|numeric',
        ]);
        $inputs = $req->only(['tanggal_awal', 'tanggal_akhir', 'tahun', 'bulan']);
        $inputs['keterangan'] = $req->input('keterangan');
        $header = $this->repoHeader->create($inputs);
        $listInputPayroll = $req->input('payrolls');
        $detail = $this->repo->create($header->id, $listInputPayroll);
        if($detail) {
            $this->repoHeader->updateSummary($header->id);
            $header->refresh();
        }
        return $this->createdResponse($header, 'Payroll berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
        ]);
        $inputs = $req->only(['tanggal_awal', 'tanggal_akhir', 'tahun', 'bulan']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repoHeader->update($id, $inputs);
        return $this->createdResponse($data, 'Payroll berhasil diubah');
    }

    public function kunciPayroll($id) {
        $data = $this->repoHeader->kunciPayroll($id);
        return $this->successResponse($data, 'Payroll untuk periode '.Carbon::parse($data->tanggal_akhir)->format('M Y').' berhasil dikunci');
    }

    public function delete($id) {
        $detils = $this->repo->deletesByHeaderId($id);
        $data = $this->repoHeader->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Payroll tidak ditemukan');
        }
        return $this->successResponse([$data, $detils], 'Payroll berhasil dihapus');
    }

    public function updateDetail(Request $req, $header_id, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'gaji' => 'numeric',
            'makan_harian' => 'in:Y,N',
            'hari_makan' => 'numeric',
            'uang_makan_harian' => 'numeric',
            'uang_makan_jumlah' => 'numeric',
            'overtime_fjg' => 'numeric',
            'overtime_cus' => 'numeric',
            'medical' => 'numeric',
            'thr' => 'numeric',
            'bonus' => 'numeric',
            'insentif' => 'numeric',
            'telkomsel' => 'numeric',
            'lain' => 'numeric',
            'pot_25_hari' => 'numeric',
            'pot_25_jumlah' => 'numeric',
            'pot_telepon' => 'numeric',
            'pot_bensin' => 'numeric',
            'pot_kas' => 'numeric',
            'pot_cicilan' => 'numeric',
            'pot_bpjs' => 'numeric',
            'pot_cuti' => 'numeric',
            'pot_lain' => 'numeric',
            'total_diterima' => 'numeric',
        ]);
        $inputs['payroll_header_id'] = $header_id;
        $inputs['karyawan_id'] = $req->input('karyawan_id');
        $inputs['gaji'] = $req->input('gaji');
        $inputs['makan_harian'] = $req->input('makan_harian');
        $inputs['hari_makan'] = $req->input('hari_makan');
        $inputs['uang_makan_harian'] = $req->input('uang_makan_harian');
        $inputs['uang_makan_jumlah'] = $req->input('uang_makan_jumlah');
        $inputs['overtime_fjg'] = $req->input('overtime_fjg');
        $inputs['overtime_cus'] = $req->input('overtime_cus');
        $inputs['medical'] = $req->input('medical');
        $inputs['thr'] = $req->input('thr');
        $inputs['bonus'] = $req->input('bonus');
        $inputs['insentif'] = $req->input('insentif');
        $inputs['telkomsel'] = $req->input('telkomsel');
        $inputs['lain'] = $req->input('lain');
        $inputs['pot_25_hari'] = $req->input('pot_25_hari');
        $inputs['pot_25_jumlah'] = $req->input('pot_25_jumlah');
        $inputs['pot_telepon'] = $req->input('pot_telepon');
        $inputs['pot_bensin'] = $req->input('pot_bensin');
        $inputs['pot_kas'] = $req->input('pot_kas');
        $inputs['pot_cicilan'] = $req->input('pot_cicilan');
        $inputs['pot_bpjs'] = $req->input('pot_bpjs');
        $inputs['pot_cuti'] = $req->input('pot_cuti');
        $inputs['pot_lain'] = $req->input('pot_lain');
        $inputs['total_diterima'] = $req->input('total_diterima');
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        if($data) {
            $this->repoHeader->updateSummary($header_id);
        }
        return $this->successResponse($data, 'Payroll berhasil diubah');
    }

}
