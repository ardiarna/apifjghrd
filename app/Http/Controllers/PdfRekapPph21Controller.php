<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\AreaRepository;
use App\Repositories\TarifEfektifRepository;
use Mpdf\Mpdf;

class PdfRekapPph21Controller extends Controller
{
    protected $repoDetail, $repoArea, $repoPhk, $repoTER;

    public function __construct(PayrollRepository $repoDetail, AreaRepository $repoArea, PayrollPhkRepository $repoPhk, TarifEfektifRepository $repoTER) {
        $this->repoDetail = $repoDetail;
        $this->repoArea = $repoArea;
        $this->repoPhk = $repoPhk;
        $this->repoTER = $repoTER;
    }

    function hitungPPh21($bruto, $ptkp, $accMonth) {
        $biayaJabatan = min($bruto/100*5, 500000*$accMonth);
        $biayaJabatan = round($biayaJabatan);
        $netto = $bruto - $biayaJabatan;
        $pkp = $netto - $ptkp;
        $pkp = floor($pkp/1000) * 1000;
        $pkpAwal = $pkp;
        $pph = 0;
        if ($pkp > 5000000000) {
            $amount = $pkp - 5000000000;
            $pph += $amount * 0.35;
            $pkp = 5000000000;
        }
        if ($pkp > 500000000) {
            $amount = $pkp - 500000000;
            $pph += $amount * 0.30;
            $pkp = 500000000;
        }
        if ($pkp > 250000000) {
            $amount = $pkp - 250000000;
            $pph += $amount * 0.25;
            $pkp = 250000000;
        }
        if ($pkp > 60000000) {
            $amount = $pkp - 60000000;
            $pph += $amount * 0.15;
            $pkp = 60000000;
        }
        if ($pkp > 0) {
            $amount = $pkp;
            $pph += $amount * 0.05;
        }
        return [
            "dpp" => $bruto,
            "biaya_jabatan" => $biayaJabatan,
            "netto" => $netto,
            "pkp" => $pkpAwal,
            "pph" => $pph
        ];
    }

    function hitungPPh21GrossUp($bruto, $ptkp, $accMonth) {
        $pph = 0;
        $grossBruto = 0;
        $nextBruto = $bruto;
        do {
            $cari = $this->hitungPPh21($nextBruto, $ptkp, $accMonth);
            $pph = $cari["pph"];
            $grossBruto = $cari["dpp"];
            $nextBruto = $bruto + $pph;
        } while ($grossBruto - $pph != $bruto);
        return $cari;
    }

    private function afAbbreviateName($name) {
        $parts = explode(' ', $name);
        if(count($parts) <= 2) return $name;
        $res = $parts[0] . ' ' . $parts[1];
        for($i=2; $i<count($parts); $i++) {
            $res .= ' ' . substr($parts[$i], 0, 1) . '.';
        }
        return $res;
    }

    public function rekap($jenis, $tahun, $area) {
            $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];

        if($area == 'all') {
            $namaArea = '';
        } else {
            $dArea = $this->repoArea->findById($area);
            $namaArea = $dArea->nama.'_';
        }

        $dataDetails = $this->repoDetail->findAll([
            'tahun' => $tahun,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
            'pph21' => 'Y',
        ]);
        $dataPhk = $this->repoPhk->findAll([
            'tahun' => $tahun,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
            'pph21' => 'Y',
        ]);
        
        $merged = $dataDetails->merge($dataPhk);
        $details = array();
        $dataKaryawan = array();
        $arrTotal = array();
        foreach ($merged as $dt) {
            if(isset($details[$dt->tahun][$dt->karyawan->id][$dt->bulan])) {
                $existing = $details[$dt->tahun][$dt->karyawan->id][$dt->bulan];
                $existing->gaji += $dt->gaji;
                $existing->kenaikan_gaji += $dt->kenaikan_gaji;
                $existing->hari_makan += $dt->hari_makan;
                $existing->uang_makan_harian += $dt->uang_makan_harian;
                $existing->uang_makan_jumlah += $dt->uang_makan_jumlah;
                $existing->overtime_fjg += $dt->overtime_fjg;
                $existing->overtime_cus += $dt->overtime_cus;
                $existing->medical += $dt->medical;
                $existing->thr += $dt->thr;
                $existing->bonus += $dt->bonus;
                $existing->insentif += $dt->insentif;
                $existing->telkomsel += $dt->telkomsel;
                $existing->lain += $dt->lain;
                $existing->pot_25_hari += $dt->pot_25_hari;
                $existing->pot_25_jumlah += $dt->pot_25_jumlah;
                $existing->pot_telepon += $dt->pot_telepon;
                $existing->pot_bensin += $dt->pot_bensin;
                $existing->pot_kas += $dt->pot_kas;
                $existing->pot_cicilan += $dt->pot_cicilan;
                $existing->pot_bpjs += $dt->pot_bpjs;
                $existing->pot_cuti_hari += $dt->pot_cuti_hari;
                $existing->pot_cuti_jumlah += $dt->pot_cuti_jumlah;
                $existing->pot_kompensasi_jam += $dt->pot_kompensasi_jam;
                $existing->pot_kompensasi_jumlah += $dt->pot_kompensasi_jumlah;
                $existing->pot_lain += $dt->pot_lain;
                $existing->total_diterima += $dt->total_diterima;
                $existing->kantor_jp += $dt->kantor_jp;
                $existing->kantor_jht += $dt->kantor_jht;
                $existing->kantor_jkk += $dt->kantor_jkk;
                $existing->kantor_jkm += $dt->kantor_jkm;
                $existing->kantor_bpjs += $dt->kantor_bpjs;
                $existing->penghasilan_bruto += $dt->penghasilan_bruto;
                $existing->dpp += $dt->dpp;
                $existing->pph21 += $dt->pph21;
            } else {
                $details[$dt->tahun][$dt->karyawan->id][$dt->bulan] = $dt;
            }
            if(isset($arrTotal[$dt->tahun][$dt->karyawan->id])) {
                $arrTotal[$dt->tahun][$dt->karyawan->id]['penghasilan_bruto'] += $dt->penghasilan_bruto;
            } else {
                $arrTotal[$dt->tahun][$dt->karyawan->id]['penghasilan_bruto'] = $dt->penghasilan_bruto;
            }
            if(!isset($dataKaryawan[$dt->karyawan->id])) {
                $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            }
        }

        $css = "<style>
            body { font-family: calibri, arial, sans-serif; font-size: 6pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            td, th { border: 1pt solid #000; padding: 2px 2px; vertical-align: middle; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 14pt; color: #0000FF; font-weight: bold; text-align: center; font-family: 'Algerian', sans-serif; text-decoration: underline; margin-bottom: 15pt; }
            .text-red { color: #FF0000; }
            .text-blue { color: #0000FF; }
            .bg-yellow { background-color: #FFFF00; }
        </style>";

        $html = $css;
        
        $mpdf = new Mpdf([
            'format'        => 'A3-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->SetTitle('Rekap PPh 21');
        $firstPage = true;
        foreach ($details as $keyTahun => $karyawan_ids) {
            if (!$firstPage) { $html .= "<pagebreak />"; }
            $firstPage = false;
            
            // SHEET 4: REKAP PPH 21 (Summary)
            $html .= "<div class=\"title\">REKAP PPh 21 JANUARI ".$keyTahun." S/D DESEMBER ".$keyTahun."<br/>";
            $html .= "DIVISI : ".($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA')))." ".$namaArea."</div>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $widthsSheet4 = [5, 30, 25, 25, 12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12,12, 20, 20, 20];
            foreach ($widthsSheet4 as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<th class=\"ac\" rowspan=\"3\">NO</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">NAMA KARYAWAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">JABATAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">NPWP</th>";
            $html .= "<th class=\"ac\" colspan=\"23\">PEMBAYARAN PPH 21 JANUARI - NOVEMBER</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">DISETAHUNKAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">DESEMBER<br/>PPH 21 DIBAYARKAN</th>";
            $html .= "</tr>";

            $html .= "<tr>";
            for ($k=1; $k <= 11; $k++) $html .= "<th class=\"ac\" colspan=\"2\">".$arrBulan[$k]."</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">PPH 21 TERBAYAR</th>";
            $html .= "</tr>";

            $html .= "<tr>";
            for ($k=1; $k <= 11; $k++) {
                $html .= "<th class=\"ac\">PPh 21</th>";
                $html .= "<th class=\"ac\">PPh 21 Dibayarkan</th>";
            }
            $html .= "</tr>";

            $nomor = 1;
            $totalsSheet4 = array_fill(1, 25, 0); // For storing totals

            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $dkaryawan = $dataKaryawan[$karyawan_id];
                $html .= "<tr>";
                $html .= "<td class=\"ac\">".$nomor."</td>";
                $html .= "<td class=\"al\">".($jenis == '3' ? $this->afAbbreviateName($dkaryawan->nama).' ('.$dkaryawan->area->nama.')' : $this->afAbbreviateName($dkaryawan->nama))."</td>";
                $html .= "<td class=\"al\">".$dkaryawan->jabatan->nama."</td>";
                $html .= "<td class=\"ac\">".($dkaryawan->nomor_pwp ? $dkaryawan->nomor_pwp : "")."</td>";

                $sumTerbayar = 0;
                $idxTot = 1;
                for ($k=1; $k <= 11; $k++) {
                    if(isset($bulans[$k])) {
                        $d = $bulans[$k];
                        $pph = $d->pph21;
                        $dibayarkan = $dkaryawan->nomor_pwp ? $pph : $pph * 1.2;
                    } else {
                        $pph = 0;
                        $dibayarkan = 0;
                    }
                    $html .= "<td class=\"ar\">".($pph != 0 ? number_format($pph) : '0')."</td>";
                    $html .= "<td class=\"ar\">".($dibayarkan != 0 ? number_format($dibayarkan) : '0')."</td>";
                    $sumTerbayar += $dibayarkan;
                    $totalsSheet4[$idxTot++] += $pph;
                    $totalsSheet4[$idxTot++] += $dibayarkan;
                }
                $html .= "<td class=\"ar\">".number_format($sumTerbayar)."</td>";
                $totalsSheet4[$idxTot++] += $sumTerbayar;

                // For DISETAHUNKAN (from Hitung Des)
                $disetahunkand = 0;
                if($dkaryawan->ptkp) {
                    $hitung = $this->hitungPPh21GrossUp($arrTotal[$keyTahun][$karyawan_id]['penghasilan_bruto'], $dkaryawan->ptkp->jumlah, count($bulans));
                    $disetahunkand = $hitung['pph'];
                } else {
                    $hitung = $this->hitungPPh21($arrTotal[$keyTahun][$karyawan_id]['penghasilan_bruto'], 0, count($bulans));
                    $disetahunkand = $hitung['pph'];
                }
                
                $disetahunkand_final = $dkaryawan->nomor_pwp ? $disetahunkand : round($disetahunkand * 1.2);
                $html .= "<td class=\"ar\">".number_format($disetahunkand_final)."</td>";
                $totalsSheet4[$idxTot++] += $disetahunkand_final;

                $desemberDibayarkan = $disetahunkand_final - $sumTerbayar;
                $html .= "<td class=\"ar\">".number_format($desemberDibayarkan)."</td>";
                $totalsSheet4[$idxTot++] += $desemberDibayarkan;

                $html .= "</tr>";
                $nomor++;
            }

            $html .= "<tr class=\"bg-yellow\">";
            $html .= "<td colspan=\"4\" class=\"ac\">TOTAL</td>";
            for ($x = 1; $x <= 25; $x++) {
                $html .= "<td class=\"ar\">".number_format($totalsSheet4[$x])."</td>";
            }
            $html .= "</tr>";
            $html .= "</table>";
        }
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('REKAP_PPH_21_'.$tahun.'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
