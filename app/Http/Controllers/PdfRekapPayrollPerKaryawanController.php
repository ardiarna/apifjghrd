<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\AreaRepository;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PdfRekapPayrollPerKaryawanController extends Controller
{
    protected $repoDetail, $repoArea, $repoPhk;

    public function __construct(PayrollRepository $repoDetail, AreaRepository $repoArea, PayrollPhkRepository $repoPhk) {
        $this->repoDetail = $repoDetail;
        $this->repoArea = $repoArea;
        $this->repoPhk = $repoPhk;
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

    public function rekapPerKaryawan($jenis, $tahun, $area) {
        // jenis   =>   1.ENGINEER   2.STAFF   3.NON STAF    4.ALL
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
        ]);
        $details = array();
        $dataKaryawan = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->id][$dt->bulan] = $dt;
            if(!isset($dataKaryawan[$dt->karyawan->id])) {
                $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            }
        }

        $dataPhks = $this->repoPhk->findAll([
            'tahun' => $tahun,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
        ]);
        $detailsPhk = array();
        foreach ($dataPhks as $dt) {
            $detailsPhk[$dt->tahun][$dt->karyawan->id][$dt->bulan] = $dt;
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
        $firstPage = true;

        foreach ($details as $keyTahun => $karyawan_ids) {
            if (!$firstPage) { $html .= "<pagebreak />"; }
            $firstPage = false;

            $html .= "<div class=\"title\">PAYROLL JANUARI ".$keyTahun." S/D DESEMBER ".$keyTahun."<br/>";
            $html .= "DIVISI : ".($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA')))." ".$namaArea."</div>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";

            // Dummy row for widths (29 cols)
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $widths = [5, 30, 25, 15, 16, 6, 13, 14, 14, 14, 12, 12, 12, 12, 12, 12, 6, 12, 12, 12, 12, 12, 12, 12, 12, 12, 16, 35, 15];
            foreach ($widths as $w) {
                $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
            }
            $html .= "</tr>";

            // Header 1
            $html .= "<tr>";
            $html .= "<th class=\"ac\" rowspan=\"3\">NO</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">NAMA KARYAWAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">JABATAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">TANGGAL<br/>GABUNG</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">GAJI / UPAH IDR</th>";
            $html .= "<th class=\"ac\" colspan=\"3\">U/MAKAN & TRANSPORTASI</th>";
            $html .= "<th class=\"ac\" colspan=\"8\">TUNJANGAN LAIN</th>";
            $html .= "<th class=\"ac text-red\" colspan=\"10\">POTONGAN</th>";
            $html .= "<th class=\"ac text-blue\" rowspan=\"3\">TOTAL DITERIMA IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">KETERANGAN</th>";
            $html .= "<th class=\"ac\" rowspan=\"3\">BULAN DAN TAHUN</th>";
            $html .= "</tr>";

            // Header 2
            $html .= "<tr>";
            $html .= "<th class=\"ac\" rowspan=\"2\">HR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">@ HARI IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">JUMLAH IDR</th>";
            $html .= "<th class=\"ac\" colspan=\"2\">OVERTIME</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">MEDICAL IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">THR IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">BONUS IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">INSENTIF IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">TELKOMSEL IDR</th>";
            $html .= "<th class=\"ac\" rowspan=\"2\">LAIN-LAIN IDR</th>";
            $html .= "<th class=\"ac text-red\" colspan=\"2\">25%</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">TELP.<br/>IDR</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">BENSIN<br/>IDR</th>";
            $html .= "<th class=\"ac text-red\" colspan=\"2\">PINJAMAN</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">BPJS (KIS) IDR</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">UNPAID LEAVE</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">KOMPENSASI IDR</th>";
            $html .= "<th class=\"ac text-red\" rowspan=\"2\">LAIN-LAIN IDR</th>";
            $html .= "</tr>";

            // Header 3
            $html .= "<tr>";
            $html .= "<th class=\"ac\">FRATEKINDO</th>";
            $html .= "<th class=\"ac\">CUSTOMER</th>";
            $html .= "<th class=\"ac text-red\">HR</th>";
            $html .= "<th class=\"ac text-red\">JUMLAH IDR</th>";
            $html .= "<th class=\"ac text-red\">KAS</th>";
            $html .= "<th class=\"ac text-red\">CICILAN</th>";
            $html .= "</tr>";

            $nomor = 1;
            $allKaryawanIds = array_keys($karyawan_ids);
            if (isset($detailsPhk[$keyTahun])) {
                foreach ($detailsPhk[$keyTahun] as $phkKaryawanId => $phkBulans) {
                    if (!in_array($phkKaryawanId, $allKaryawanIds)) {
                        $allKaryawanIds[] = $phkKaryawanId;
                        $karyawan_ids[$phkKaryawanId] = [];
                    }
                }
            }
            
            $grandTotalPayroll = 0;

            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $dkaryawan = $dataKaryawan[$karyawan_id];
                $nama = $jenis == '3' ? $this->afAbbreviateName($dkaryawan->nama).' ('.$dkaryawan->area->nama.')' : $this->afAbbreviateName($dkaryawan->nama);
                $jabatan = $dkaryawan->jabatan->nama;
                $tglMasuk = $dkaryawan->tanggal_masuk ? date('d-m-y', strtotime($dkaryawan->tanggal_masuk)) : '';
                
                $firstRow = true;
                $totalKaryawan = 0;

                for ($k = 1; $k <= 13; $k++) {
                    $d = null;
                    if (isset($bulans[$k])) {
                        $d = $bulans[$k];
                    } elseif (isset($detailsPhk[$keyTahun][$karyawan_id][$k])) {
                        $d = $detailsPhk[$keyTahun][$karyawan_id][$k];
                    }

                    if ($d) {
                        $html .= "<tr>";
                        if ($firstRow) {
                            $html .= "<td class=\"ac\">".$nomor."</td>";
                            $html .= "<td class=\"al\" style=\"white-space:nowrap;\">".$nama."</td>";
                            $html .= "<td class=\"al\">".$jabatan."</td>";
                            $html .= "<td class=\"ac\">".$tglMasuk."</td>";
                            $firstRow = false;
                        } else {
                            $html .= "<td></td>";
                            $html .= "<td></td>";
                            $html .= "<td></td>";
                            $html .= "<td></td>";
                        }

                        $html .= "<td class=\"ar\">".(($d->gaji + $d->kenaikan_gaji) > 0 ? number_format($d->gaji + $d->kenaikan_gaji) : '')."</td>";
                        $html .= "<td class=\"ac\">".($d->hari_makan > 0 ? number_format($d->hari_makan) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->uang_makan_harian > 0 ? number_format($d->uang_makan_harian) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->uang_makan_jumlah > 0 ? number_format($d->uang_makan_jumlah) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->overtime_fjg > 0 ? number_format($d->overtime_fjg) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->overtime_cus > 0 ? number_format($d->overtime_cus) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->medical != 0 ? number_format($d->medical) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->thr > 0 ? number_format($d->thr) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->bonus > 0 ? number_format($d->bonus) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->insentif > 0 ? number_format($d->insentif) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->telkomsel > 0 ? number_format($d->telkomsel) : '')."</td>";
                        $html .= "<td class=\"ar\">".($d->lain > 0 ? number_format($d->lain) : '')."</td>";
                        $html .= "<td class=\"ac text-red\">".($d->pot_25_hari > 0 ? number_format($d->pot_25_hari) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_25_jumlah > 0 ? number_format($d->pot_25_jumlah) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_telepon > 0 ? number_format($d->pot_telepon) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_bensin > 0 ? number_format($d->pot_bensin) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_kas > 0 ? number_format($d->pot_kas) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_cicilan > 0 ? number_format($d->pot_cicilan) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_bpjs > 0 ? number_format($d->pot_bpjs) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_cuti_jumlah > 0 ? number_format($d->pot_cuti_jumlah) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_kompensasi_jumlah > 0 ? number_format($d->pot_kompensasi_jumlah) : '')."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_lain > 0 ? number_format($d->pot_lain) : '')."</td>";
                        $html .= "<td class=\"ar text-blue\">".($d->total_diterima > 0 ? number_format($d->total_diterima) : '')."</td>";
                        $html .= "<td class=\"al\">".$d->keterangan."</td>";
                        
                        $bulanText = ($k == 13) ? 'THR' : $arrBulan[$k]."'".substr($keyTahun, -2);
                        $html .= "<td class=\"ac\">".$bulanText."</td>";
                        $html .= "</tr>";

                        $totalKaryawan += $d->total_diterima;
                    }
                }

                if (!$firstRow) {
                    $grandTotalPayroll += $totalKaryawan;
                    $html .= "<tr class=\"bg-yellow\">";
                    for ($x = 1; $x <= 26; $x++) $html .= "<td></td>";
                    $html .= "<td class=\"ar text-blue\">".($totalKaryawan > 0 ? number_format($totalKaryawan) : '0')."</td>";
                    $html .= "<td></td><td></td>";
                    $html .= "</tr>";
                    $nomor++;
                }
            }

            $html .= "<tr><td colspan=\"29\" style=\"border-top:none; border-bottom:none; border-left:none; border-right:none; height:10pt;\"></td></tr>";
            $html .= "<tr>";
            $html .= "<td colspan=\"4\" class=\"al\">TOTAL PAYROLL</td>";
            for ($x = 1; $x <= 22; $x++) $html .= "<td></td>";
            $html .= "<td class=\"ar text-blue\">".($grandTotalPayroll > 0 ? number_format($grandTotalPayroll) : '0')."</td>";
            $html .= "<td></td><td></td>";
            $html .= "</tr>";

            $html .= "</table>";
        }

        $mpdf = new Mpdf([
            'format'        => 'A3-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle('Rekap Payroll Per Karyawan');
        $mpdf->WriteHTML($html);
        $mpdf->Output('REKAP_PAYROLL_PER_KARYAWAN_'.$tahun.'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
