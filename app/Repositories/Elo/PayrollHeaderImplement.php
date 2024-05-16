<?php

namespace App\Repositories\Elo;

use App\Repositories\PayrollHeaderRepository;
use App\Models\PayrollHeader;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollHeaderImplement implements PayrollHeaderRepository {

    protected $model, $detil;

    function __construct(PayrollHeader $model, Payroll $detil) {
        $this->model = $model;
        $this->detil = $detil;
    }

    public function findById($id) {
        $model = $this->model->findOrFail($id);
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()
            ->select(
                'payroll_headers.*',
                DB::raw('MONTH(payroll_headers.tanggal_akhir) as bulan'),
                DB::raw('YEAR(payroll_headers.tanggal_akhir) as tahun')
            );
        if($inputs['tahun']) {
            $hasil->whereYear('tanggal_akhir', '=', $inputs['tahun']);
        }
        if($inputs['bulan']) {
            $hasil->whereMonth('tanggal_akhir', '=', $inputs['bulan']);
        }
        if($inputs['sort_by']) {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        $hasil->orderBy('tanggal_akhir');
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if(isset($inputs['tanggal_awal'])) {
            $model->tanggal_awal = $inputs['tanggal_awal'];
        }
        if(isset($inputs['tanggal_akhir'])) {
            $model->tanggal_akhir = $inputs['tanggal_akhir'];
        }
        if(isset($inputs['tanggal_makan_awal'])) {
            $model->tanggal_makan_awal = $inputs['tanggal_makan_awal'];
        }
        if(isset($inputs['tanggal_makan_akhir'])) {
            $model->tanggal_makan_akhir = $inputs['tanggal_makan_akhir'];
        }
        if(isset($inputs['gaji'])) {
            $model->gaji = $inputs['gaji'];
        }
        if(isset($inputs['uang_makan_jumlah'])) {
            $model->uang_makan_jumlah = $inputs['uang_makan_jumlah'];
        }
        if(isset($inputs['overtime_fjg'])) {
            $model->overtime_fjg = $inputs['overtime_fjg'];
        }
        if(isset($inputs['overtime_cus'])) {
            $model->overtime_cus = $inputs['overtime_cus'];
        }
        if(isset($inputs['medical'])) {
            $model->medical = $inputs['medical'];
        }
        if(isset($inputs['thr'])) {
            $model->thr = $inputs['thr'];
        }
        if(isset($inputs['bonus'])) {
            $model->bonus = $inputs['bonus'];
        }
        if(isset($inputs['insentif'])) {
            $model->insentif = $inputs['insentif'];
        }
        if(isset($inputs['telkomsel'])) {
            $model->telkomsel = $inputs['telkomsel'];
        }
        if(isset($inputs['lain'])) {
            $model->lain = $inputs['lain'];
        }
        if(isset($inputs['pot_25_hari'])) {
            $model->pot_25_hari = $inputs['pot_25_hari'];
        }
        if(isset($inputs['pot_25_jumlah'])) {
            $model->pot_25_jumlah = $inputs['pot_25_jumlah'];
        }
        if(isset($inputs['pot_telepon'])) {
            $model->pot_telepon = $inputs['pot_telepon'];
        }
        if(isset($inputs['pot_bensin'])) {
            $model->pot_bensin = $inputs['pot_bensin'];
        }
        if(isset($inputs['pot_kas'])) {
            $model->pot_kas = $inputs['pot_kas'];
        }
        if(isset($inputs['pot_cicilan'])) {
            $model->pot_cicilan = $inputs['pot_cicilan'];
        }
        if(isset($inputs['pot_bpjs'])) {
            $model->pot_bpjs = $inputs['pot_bpjs'];
        }
        if(isset($inputs['pot_cuti'])) {
            $model->pot_cuti = $inputs['pot_cuti'];
        }
        if(isset($inputs['pot_lain'])) {
            $model->pot_lain = $inputs['pot_lain'];
        }
        if(isset($inputs['total_diterima'])) {
            $model->total_diterima = $inputs['total_diterima'];
        }
        if(isset($inputs['dikunci'])) {
            $model->dikunci = $inputs['dikunci'];
        }
        if(isset($inputs['keterangan'])) {
            $model->keterangan = $inputs['keterangan'];
        }
        $model->save();
        return $model;
    }

    public function updateSummary($id) {
        $data = $this->detil->query()
            ->where('payroll_header_id', $id)
            ->select(
                DB::raw('SUM(gaji) as gaji'),
                DB::raw('SUM(uang_makan_jumlah) as uang_makan_jumlah'),
                DB::raw('SUM(overtime_fjg) as overtime_fjg'),
                DB::raw('SUM(overtime_cus) as overtime_cus'),
                DB::raw('SUM(medical) as medical'),
                DB::raw('SUM(thr) as thr'),
                DB::raw('SUM(bonus) as bonus'),
                DB::raw('SUM(insentif) as insentif'),
                DB::raw('SUM(telkomsel) as telkomsel'),
                DB::raw('SUM(lain) as lain'),
                DB::raw('SUM(pot_25_hari) as pot_25_hari'),
                DB::raw('SUM(pot_25_jumlah) as pot_25_jumlah'),
                DB::raw('SUM(pot_telepon) as pot_telepon'),
                DB::raw('SUM(pot_bensin) as pot_bensin'),
                DB::raw('SUM(pot_kas) as pot_kas'),
                DB::raw('SUM(pot_cicilan) as pot_cicilan'),
                DB::raw('SUM(pot_bpjs) as pot_bpjs'),
                DB::raw('SUM(pot_cuti) as pot_cuti'),
                DB::raw('SUM(pot_lain) as pot_lain'),
                DB::raw('SUM(total_diterima) as total_diterima')
            )->first();
        if($data) {
            $this->update($id, [
                'gaji' => $data->gaji,
                'uang_makan_jumlah' => $data->uang_makan_jumlah,
                'overtime_fjg' => $data->overtime_fjg,
                'overtime_cus' => $data->overtime_cus,
                'medical' => $data->medical,
                'thr' => $data->thr,
                'bonus' => $data->bonus,
                'insentif' => $data->insentif,
                'telkomsel' => $data->telkomsel,
                'lain' => $data->lain,
                'pot_25_hari' => $data->pot_25_hari,
                'pot_25_jumlah' => $data->pot_25_jumlah,
                'pot_telepon' => $data->pot_telepon,
                'pot_bensin' => $data->pot_bensin,
                'pot_kas' => $data->pot_kas,
                'pot_cicilan' => $data->pot_cicilan,
                'pot_bpjs' => $data->pot_bpjs,
                'pot_cuti' => $data->pot_cuti,
                'pot_lain' => $data->pot_lain,
                'total_diterima' => $data->total_diterima,
            ]);
        }
    }

    public function kunciPayroll($id) {
        return $this->update($id, ['dikunci' => 'Y']);
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
