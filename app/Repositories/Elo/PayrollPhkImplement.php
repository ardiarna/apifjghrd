<?php

namespace App\Repositories\Elo;

use App\Models\PayrollPhk;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\TarifEfektifRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayrollPhkImplement implements PayrollPhkRepository {

    protected $model, $repoTER;

    function __construct(PayrollPhk $model, TarifEfektifRepository $repoTER) {
        $this->model = $model;
        $this->repoTER = $repoTER;
    }

    public function findById($karyawan_id, $id) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $karyawan_id) {
            throw new HttpException(403, 'Karyawan dan payroll PHK tidak sesuai');
        }
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['karyawan.area', 'karyawan.jabatan', 'karyawan.divisi', 'karyawan.ptkp'])
            ->join('karyawans', 'payroll_phks.karyawan_id', '=', 'karyawans.id')
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->select('payroll_phks.*')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan');
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('payroll_phks.karyawan_id', $inputs['karyawan_id']);
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
            $hasil->where('payroll_phks.tahun', $inputs['tahun']);
        }
        if(isset($inputs['bulan']) && $inputs['bulan'] != '') {
            $hasil->where('payroll_phks.bulan', $inputs['bulan']);
        }
        if(isset($inputs['sort_by']) && $inputs['sort_by'] != '') {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        $hasil->orderBy('payroll_phks.karyawan_id');
        $hasil->orderBy('payroll_phks.tahun');
        $hasil->orderBy('payroll_phks.bulan');
        if(isset($inputs['pph21']) && ($inputs['pph21'] == 'Y' || $inputs['pph21'] == 'y')) {
            $hasil = $hasil->get();
            foreach ($hasil as $h) {
                $h->kantor_jp = round(($h->gaji+$h->kenaikan_gaji) / 100 * 3);
                $h->kantor_jht = round(($h->gaji+$h->kenaikan_gaji) / 100 * 5.7);
                $h->kantor_jkk = round(($h->gaji+$h->kenaikan_gaji) / 100 * 0.24);
                $h->kantor_jkm = round(($h->gaji+$h->kenaikan_gaji) / 100 * 0.3);
                $h->kantor_bpjs = round(($h->gaji+$h->kenaikan_gaji) / 100 * 5);
                if($h->kantor_bpjs > 600000) $h->kantor_bpjs = 600000;
                $penghasilan_bruto = $h->gaji + $h->kenaikan_gaji + $h->uang_makan_jumlah + $h->overtime_fjg + $h->overtime_cus + $h->medical + $h->thr + $h->bonus + $h->insentif + $h->telkomsel + $h->lain + $h->kantor_jkk + $h->kantor_jkm + $h->kantor_bpjs - $h->pot_25_jumlah - $h->pot_cuti_jumlah - $h->pot_kompensasi_jumlah;
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

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan payroll PHK tidak sesuai');
        }
        if(isset($inputs['tanggal_awal'])) {
            $model->tanggal_awal = $inputs['tanggal_awal'];
        }
        if(isset($inputs['tanggal_akhir'])) {
            $model->tanggal_akhir = $inputs['tanggal_akhir'];
        }
        if(isset($inputs['tahun'])) {
            $model->tahun = $inputs['tahun'];
        }
        if(isset($inputs['bulan'])) {
            $model->bulan = $inputs['bulan'];
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
        if(isset($inputs['pot_cuti_hari'])) {
            $model->pot_cuti_hari = $inputs['pot_cuti_hari'];
        }
        if(isset($inputs['pot_cuti_jumlah'])) {
            $model->pot_cuti_jumlah = $inputs['pot_cuti_jumlah'];
        }
        if(isset($inputs['pot_kompensasi_jam'])) {
            $model->pot_kompensasi_jam = $inputs['pot_kompensasi_jam'];
        }
        if(isset($inputs['pot_kompensasi_jumlah'])) {
            $model->pot_kompensasi_jumlah = $inputs['pot_kompensasi_jumlah'];
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

    public function deleteByKaryawanId($karyawan_id) {
        return $this->model->where('karyawan_id', $karyawan_id)->delete();
    }

    public function findByKaryawanId($karyawan_id) {
        return $this->model->where('karyawan_id', $karyawan_id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();
    }

    public function updateOrCreate(array $inputs) {
        $model = $this->model->updateOrCreate(
            [
                'karyawan_id' => $inputs['karyawan_id'],
                'tahun' => $inputs['tahun'],
                'bulan' => $inputs['bulan']
            ],
            $inputs
        );
        return $model;
    }

}
