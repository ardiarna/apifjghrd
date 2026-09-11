<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollRepository;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfRekapOvertimeController extends Controller
{
    use AFhelper;

    protected $repoDetail, $repoOncall;

    public function __construct(PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall) {
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
    }

    public function rekap($tahun) {
        $arrBulan = ['','JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                     'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

        // --- Ambil data ---
        $dataDetails = $this->repoDetail->findAll(['tahun' => $tahun]);
        $details = [];
        $dataKaryawan = [];
        foreach ($dataDetails as $dt) {
            if ($dt->overtime_fjg > 0 || $dt->overtime_cus > 0) {
                $details[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id][$dt->bulan]
                    = $dt->overtime_fjg + $dt->overtime_cus;
                $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            }
        }

        $oncalls = [];
        foreach ($this->repoOncall->findAll(['tahun' => $tahun]) as $d) {
            $oncalls[$d->tahun][$d->bulan] = ($oncalls[$d->tahun][$d->bulan] ?? 0) + $d->jumlah;
        }

        // --- CSS ---
        $css = "<style>
            body { font-family: arial, sans-serif; font-size: 6pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1pt solid #000; padding: 2px 3px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 11pt; color: #0000FF; text-decoration: underline; font-weight: bold; }
            .text-blue { color: #0000FF; }
            .text-red { color: #FF0000; }
            .bg-orange { background-color: #FFC354; }
            .header th { font-weight: bold; text-align: center; background-color: #FFC354; border: 1pt solid #000; }
            .total-row td { font-weight: bold; color: #0000FF; border: 1pt solid #000; }
            .area-row td { border: 1pt solid #000; }
        </style>";

        $html = $css;
        $first = true;

        foreach ($details as $keyTahun => $stafs) {
            if (!$first) { $html .= "<pagebreak />"; }
            $first = false;

            // Title
            $html .= "<table style=\"width:100%; border-collapse:collapse;\">";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"15\">REKAPITULASI OVERTIME PT.FRATEKINDO JAYA GEMILANG</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"15\">PERIODE : TAHUN ".$keyTahun."</td></tr>";
            $html .= "<tr><td class=\"no-border\" colspan=\"15\">&nbsp;</td></tr>";
            $html .= "</table>";

            // Main table: NO, NAMA KARYAWAN, 12 bulan, TOTAL IDR = 15 cols
            $html .= "<table style=\"width:100%; table-layout:fixed;\">";
            // dummy row: NO=6, NAMA=40, 12×bulan=16, TOTAL=20 → 6+40+(12×16)+20=258mm
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:6mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:40mm; padding:0; border:none; height:0;\"></td>";
            for ($m = 0; $m < 12; $m++) { $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>"; }
            $html .= "<td style=\"width:20mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";

            // Header row 1
            $html .= "<tr class=\"header\">";
            $html .= "<th rowspan=\"2\">NO</th>";
            $html .= "<th rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th colspan=\"12\">B U L A N</th>";
            $html .= "<th rowspan=\"2\">TOTAL IDR</th>";
            $html .= "</tr>";
            // Header row 2
            $html .= "<tr class=\"header\">";
            for ($k = 1; $k <= 12; $k++) {
                $html .= "<th style=\"overflow:hidden;\">".$arrBulan[$k]."</th>";
            }
            $html .= "</tr>";

            $nomor = 1;
            $grandTotal = array_fill(1, 12, 0);
            $grandTotalAll = 0;

            foreach ($stafs as $staf => $areas) {
                if ($staf == 'N') {
                    $html .= "<tr class=\"area-row\">";
                    $html .= "<td class=\"ac\"></td>";
                    $html .= "<td colspan=\"14\" class=\"al text-blue\">NON STAF :</td>";
                    $html .= "</tr>";
                }
                foreach ($areas as $area => $karyawan_ids) {
                    if ($staf == 'Y') {
                        $html .= "<tr class=\"area-row\">";
                        $html .= "<td class=\"ac\"></td>";
                        $html .= "<td colspan=\"14\" class=\"al text-blue\">".$area." :</td>";
                        $html .= "</tr>";
                    }
                    foreach ($karyawan_ids as $karyawan_id => $bulans) {
                        $dkaryawan = $dataKaryawan[$karyawan_id];
                        $rowTotal = 0;
                        $html .= "<tr>";
                        $html .= "<td class=\"ac\">".$nomor."</td>";
                        $html .= "<td class=\"al\">".$this->afAbbreviateName($dkaryawan->nama)."</td>";
                        for ($k = 1; $k <= 12; $k++) {
                            $val = isset($bulans[$k]) && $bulans[$k] > 0 ? $bulans[$k] : 0;
                            $grandTotal[$k] += $val;
                            $rowTotal += $val;
                            $html .= "<td class=\"ar\">".($val > 0 ? number_format($val, 0, ',', '.') : '')."</td>";
                        }
                        $grandTotalAll += $rowTotal;
                        $html .= "<td class=\"ar\">".($rowTotal > 0 ? number_format($rowTotal, 0, ',', '.') : '')."</td>";
                        $html .= "</tr>";
                        $nomor++;
                    }
                }
            }

            // Blank spacer
            $html .= "<tr><td colspan=\"15\" style=\"border:none; height:5pt;\"></td></tr>";

            // TOTAL row
            $html .= "<tr class=\"total-row\">";
            $html .= "<td colspan=\"2\" class=\"ac\">TOTAL</td>";
            for ($k = 1; $k <= 12; $k++) {
                $html .= "<td class=\"ar\">".($grandTotal[$k] > 0 ? number_format($grandTotal[$k], 0, ',', '.') : '')."</td>";
            }
            $html .= "<td class=\"ar\">".($grandTotalAll > 0 ? number_format($grandTotalAll, 0, ',', '.') : '')."</td>";
            $html .= "</tr>";
            $html .= "</table>";

            // --- OT DIBAYAR CUSTOMER section ---
            $html .= "<br/>";
            $html .= "<table style=\"width:100%; table-layout:fixed; margin-top:6pt;\">";
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            for ($m = 0; $m < 13; $m++) { $html .= "<td style=\"width:19.8mm; padding:0; border:none; height:0;\"></td>"; }
            $html .= "</tr>";
            // Label header
            $html .= "<tr>";
            for ($m = 0; $m < 11; $m++) { $html .= "<td class=\"no-border\"></td>"; }
            $html .= "<td colspan=\"2\" class=\"ac text-red\" style=\"border: 1pt solid #000; font-style:italic; text-decoration:underline;\">OT DIBAYAR CUSTOMER :</td>";
            $html .= "</tr>";
            // 12 bulan
            for ($k = 1; $k <= 12; $k++) {
                $val = isset($oncalls[$keyTahun][$k]) ? $oncalls[$keyTahun][$k] : 0;
                $html .= "<tr>";
                for ($m = 0; $m < 11; $m++) { $html .= "<td class=\"no-border\"></td>"; }
                $html .= "<td class=\"ac\" style=\"border: 1pt solid #000;\">".$arrBulan[$k]." ".$keyTahun."</td>";
                $html .= "<td class=\"ar\" style=\"border: 1pt solid #000;\">".number_format($val, 0, ',', '.')."</td>";
                $html .= "</tr>";
            }
            // SISA OT DIBAYAR FJG
            $totalOncall = 0;
            foreach ($oncalls[$keyTahun] ?? [] as $v) { $totalOncall += $v; }
            $sisaOT = $grandTotalAll - $totalOncall;
            $html .= "<tr>";
            for ($m = 0; $m < 11; $m++) { $html .= "<td class=\"no-border\"></td>"; }
            $html .= "<td class=\"ac text-red\" style=\"border: 1pt solid #000; font-style:italic;\">SISA OT DIBAYAR FJG</td>";
            $html .= "<td class=\"ar text-red\" style=\"border: 1pt solid #000; border-bottom: 3pt double #000;\">".number_format($sisaOT, 0, ',', '.')."</td>";
            $html .= "</tr>";
            $html .= "</table>";
        }

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle('Rekap Overtime '.$tahun);
        $mpdf->WriteHTML($html);
        $mpdf->Output('REKAP_OVERTIME_'.substr($tahun, -2).'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
