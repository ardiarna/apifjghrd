<?php

namespace App\Repositories\Elo;

use App\Repositories\PayrollRepository;
use App\Models\Payroll;
use App\Repositories\TarifEfektifRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayrollImplement implements PayrollRepository {

    protected $model;
    protected $repoTER;

    function __construct(Payroll $model, TarifEfektifRepository $repoTER) {
        $this->model = $model;
        $this->repoTER = $repoTER;
    }

    public function findById($karyawan_id, $id) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $karyawan_id) {
            throw new HttpException(403, 'Karyawan dan payroll tidak sesuai');
        }
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['karyawan.area', 'karyawan.jabatan', 'karyawan.divisi', 'karyawan.ptkp'])
            ->select('payrolls.*', 'payroll_headers.tanggal_awal', 'payroll_headers.tanggal_akhir', 'payroll_headers.tahun', 'payroll_headers.bulan')
            ->join('payroll_headers', 'payrolls.payroll_header_id', '=', 'payroll_headers.id')
            ->join('karyawans', 'payrolls.karyawan_id', '=', 'karyawans.id')
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan');
        if(isset($inputs['header_id']) && $inputs['header_id'] != '') {
            $hasil->where('payrolls.payroll_header_id', $inputs['header_id']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('payrolls.karyawan_id', $inputs['karyawan_id']);
        }
        if(isset($inputs['aktif']) && $inputs['aktif'] != '') {
            $hasil->where('karyawans.aktif', $inputs['aktif']);
        }
        if(isset($inputs['staf']) && $inputs['staf'] != '') {
            $hasil->where('karyawans.staf', $inputs['staf']);
        }
        if(isset($inputs['area']) && $inputs['area'] != '') {
            $hasil->where('karyawans.area_id', $inputs['area']);
        }
        if(isset($inputs['engineer']) && $inputs['engineer'] != '') {
            if($inputs['engineer'] == 'Y') {
                $hasil->where('karyawans.divisi_id', '6');
            } else if($inputs['engineer'] == 'N') {
                $hasil->where('karyawans.divisi_id', '<>', '6');
            }
        }
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('payroll_headers.tahun', $inputs['tahun']);
        }
        if(isset($inputs['bulan']) && $inputs['bulan'] != '') {
            $hasil->where('payroll_headers.bulan', $inputs['bulan']);
        }
        if(isset($inputs['sort_by']) && $inputs['sort_by'] != '') {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        $hasil->orderBy('payrolls.karyawan_id');
        $hasil->orderBy('payroll_headers.tahun');
        $hasil->orderBy('payroll_headers.bulan');
        if(isset($inputs['pph21']) && ($inputs['pph21'] == 'Y' || $inputs['pph21'] == 'y')) {
            $hasil = $hasil->get();
            foreach ($hasil as $h) {
                $h->kantor_jp = round(($h->gaji+$h->kenaikan_gaji) / 100 * 3);
                $h->kantor_jht = round(($h->gaji+$h->kenaikan_gaji) / 100 * 5.7);
                $h->kantor_jkk = round(($h->gaji+$h->kenaikan_gaji) / 100 * 0.24);
                $h->kantor_jkm = round(($h->gaji+$h->kenaikan_gaji) / 100 * 0.3);
                $h->kantor_bpjs = round(($h->gaji+$h->kenaikan_gaji) / 100 * 5);
                if($h->kantor_bpjs > 600000) $h->kantor_bpjs = 600000;
                $penghasilan_bruto = $h->gaji + $h->kenaikan_gaji + $h->uang_makan_jumlah + $h->overtime_fjg + $h->overtime_cus + $h->medical + $h->thr + $h->bonus + $h->insentif + $h->telkomsel + $h->lain + $h->kantor_jkk + $h->kantor_jkm + $h->kantor_bpjs - $h->pot_25_jumlah - $h->pot_cuti;
                if($h->karyawan->ptkp) {
                    $ter = $this->repoTER->findByTerAndPenghasilan($h->karyawan->ptkp->ter, $penghasilan_bruto);
                    $terSatu = $ter->persen/100;
                    $dppSatu = $penghasilan_bruto/(1-$terSatu);
                    $ter = $this->repoTER->findByTerAndPenghasilan($h->karyawan->ptkp->ter, $dppSatu);
                    $terDua = $ter->persen/100;
                    $dppDua = $penghasilan_bruto/(1-$terDua);
                    $ter = $this->repoTER->findByTerAndPenghasilan($h->karyawan->ptkp->ter, $dppDua);
                    $terTiga = $ter->persen/100;
                    $dppFinal = $penghasilan_bruto/(1-$terTiga);
                    $ter = $this->repoTER->findByTerAndPenghasilan($h->karyawan->ptkp->ter, $dppFinal);
                    $terFinal = $ter->persen/100;
                    $pph21 = $dppFinal*$terFinal;
                    $h->penghasilan_bruto = round($penghasilan_bruto);
                    $h->ter_persen = $ter->persen;
                    $h->dpp = floor($dppFinal);
                    $h->pph21 = floor($pph21);
                } else {
                    $h->penghasilan_bruto = round($penghasilan_bruto);
                    $h->ter_persen = 0;
                    $h->dpp = 0;
                    $h->pph21 = 0;
                }

            }
            return $hasil;
        }
        return $hasil->get();
    }

    public function create($header_id, array $listInputs) {
        DB::beginTransaction();
        try {
            foreach ($listInputs as $inputs) {
                $inputs['payroll_header_id'] = $header_id;
                $this->model->create($inputs);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new HttpException(500, 'Terjadi kesalahan: '.$e->getMessage());
            return false;
        }
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->payroll_header_id != $inputs['payroll_header_id']) {
            throw new HttpException(403, 'payroll header tidak sesuai');
        }
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan payroll tidak sesuai');
        }
        if(isset($inputs['gaji'])) {
            $model->gaji = $inputs['gaji'];
        }
        if(isset($inputs['kenaikan_gaji'])) {
            $model->kenaikan_gaji = $inputs['kenaikan_gaji'];
        }
        if(isset($inputs['makan_harian'])) {
            $model->makan_harian = $inputs['makan_harian'];
        }
        if(isset($inputs['hari_makan'])) {
            $model->hari_makan = $inputs['hari_makan'];
        }
        if(isset($inputs['uang_makan_harian'])) {
            $model->uang_makan_harian = $inputs['uang_makan_harian'];
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
        if(isset($inputs['keterangan'])) {
            $model->keterangan = $inputs['keterangan'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByHeaderId($header_id) {
        return $this->model->where('payroll_header_id', $header_id)->delete();
    }

}
