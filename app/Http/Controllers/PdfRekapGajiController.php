<?php

namespace App\Http\Controllers;

use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfRekapGajiController extends Controller
{
    use AFhelper;

    protected $repoDetail;
    protected $repoPhk;

    public function __construct(PayrollRepository $repoDetail, PayrollPhkRepository $repoPhk) {
        $this->repoDetail = $repoDetail;
        $this->repoPhk    = $repoPhk;
    }

    public function rekapGaji($tahun) {
        $arrBulan = [1=>'JANUARI',2=>'FEBRUARI',3=>'MARET',4=>'APRIL',5=>'MEI',6=>'JUNI',
                     7=>'JULI',8=>'AGUSTUS',9=>'SEPTEMBER',10=>'OKTOBER',11=>'NOVEMBER',12=>'DESEMBER'];

        $dataDetails = $this->repoDetail->findAll(['tahun' => $tahun]);
        $details = [];
        $dataKaryawan = [];
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id][$dt->bulan] = ($dt->gaji + $dt->kenaikan_gaji);
            $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
        }

        // Merge payroll_phks data
        $dataPhks = $this->repoPhk->findAll(['tahun' => $tahun]);
        foreach ($dataPhks as $dp) {
            $staf  = $dp->karyawan->staf;
            $area  = $dp->karyawan->area->nama;
            $kid   = $dp->karyawan->id;
            $bulan = $dp->bulan;
            if (!isset($dataKaryawan[$kid])) {
                $dataKaryawan[$kid] = $dp->karyawan;
            }
            if (!isset($details[$dp->tahun][$staf][$area][$kid][$bulan])) {
                $details[$dp->tahun][$staf][$area][$kid][$bulan] = ($dp->gaji + $dp->kenaikan_gaji);
            }
        }

        $css = "<style>
            body { font-family: arial, sans-serif; font-size: 6pt; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1pt solid #000; padding: 2px 3px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .bold { font-weight: bold; }
            .title { font-size: 13pt; color: #0000FF; text-decoration: underline; font-weight: bold; }
            .text-blue { color: #0000FF; }
            .bg-yellow { background-color: #FFFF00; }
            .header th { font-weight: bold; text-align: center; background-color: #FFFF00; border: 1pt solid #000; }
            .data-row td { border: 1pt solid #000; }
            .total-row td { font-weight: bold; color: #0000FF; border: 1pt solid #000; }
            .area-row td { border: 1pt solid #000; }
        </style>";

        $html = $css;
        $first = true;

        foreach ($details as $tahunData => $stafs) {
            if (!$first) {
                $html .= "<pagebreak />";
            }
            $first = false;

            // Title
            $html .= "<table style=\"border-collapse:collapse; width:100%; margin-bottom:6pt;\">";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"17\">REKAPITULASI GAJI PT.FRATEKINDO JAYA GEMILANG</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"17\">PERIODE : TAHUN ".$tahunData."</td></tr>";
            $html .= "<tr><td class=\"no-border\" colspan=\"17\">&nbsp;</td></tr>";
            $html .= "</table>";

            // Main table - use explicit width on th (colgroup is ignored by mPDF)
            $html .= "<table style=\"width:100%; table-layout:fixed;\">";
            // Dummy row: forces mPDF to respect column widths. height:0 + overflow:hidden + font-size:0 = invisible
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:6mm; padding:0; border:none; height:0; overflow:hidden;\"></td>";
            $html .= "<td style=\"width:38mm; padding:0; border:none; height:0; overflow:hidden;\"></td>";
            $html .= "<td style=\"width:16mm; padding:0; border:none; height:0; overflow:hidden;\"></td>";
            $html .= "<td style=\"width:16mm; padding:0; border:none; height:0; overflow:hidden;\"></td>";
            for ($m = 0; $m < 12; $m++) { $html .= "<td style=\"width:15mm; padding:0; border:none; height:0; overflow:hidden;\"></td>"; }
            $html .= "<td style=\"width:19mm; padding:0; border:none; height:0; overflow:hidden;\"></td>";
            $html .= "</tr>";
            // Header row 1
            $html .= "<tr class=\"header\">";
            $html .= "<th rowspan=\"2\">NO</th>";
            $html .= "<th rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">MASA<br/>KERJA</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">TANGGAL<br/>LAHIR</th>";
            $html .= "<th colspan=\"12\">B U L A N</th>";
            $html .= "<th rowspan=\"2\">TOTAL IDR</th>";
            $html .= "</tr>";
            // Header row 2 - month names
            $html .= "<tr class=\"header\">";
            foreach ($arrBulan as $bln) {
                $html .= "<th style=\"overflow:hidden;\">".$bln."</th>";
            }
            $html .= "</tr>";

            $nomor = 1;
            $grandTotal = array_fill(1, 12, 0);
            $grandTotalAll = 0;

            foreach ($stafs as $staf => $areas) {
                if ($staf == 'N') {
                    $html .= "<tr class=\"area-row\">";
                    $html .= "<td class=\"ac\"></td>";
                    $html .= "<td colspan=\"16\" class=\"al text-blue bold\">NON STAF :</td>";
                    $html .= "</tr>";
                }
                foreach ($areas as $area => $karyawan_ids) {
                    if ($staf == 'Y') {
                        $html .= "<tr class=\"area-row\">";
                        $html .= "<td class=\"ac\"></td>";
                        $html .= "<td colspan=\"16\" class=\"al text-blue bold\">".$area." :</td>";
                        $html .= "</tr>";
                    }
                    foreach ($karyawan_ids as $karyawan_id => $bulans) {
                        $dkaryawan = $dataKaryawan[$karyawan_id];
                        $tgl_masuk = $dkaryawan->tanggal_masuk ? date('d-m-y', strtotime($dkaryawan->tanggal_masuk)) : '';
                        $tgl_lahir = $dkaryawan->tanggal_lahir ? date('d-m-y', strtotime($dkaryawan->tanggal_lahir)) : '';

                        $rowTotal = 0;
                        $html .= "<tr class=\"data-row\">";
                        $html .= "<td class=\"ac\">".$nomor."</td>";
                        $html .= "<td class=\"al\">".$this->afAbbreviateName($dkaryawan->nama)."</td>";
                        $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";
                        $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_lahir."</td>";
                        for ($k = 1; $k <= 12; $k++) {
                            $val = isset($bulans[$k]) && $bulans[$k] > 0 ? $bulans[$k] : 0;
                            $grandTotal[$k] += $val;
                            $rowTotal += $val;
                            $html .= "<td class=\"ar\">".($val > 0 ? number_format($val, 0, ',', '.') : "")."</td>";
                        }
                        $grandTotalAll += $rowTotal;
                        $html .= "<td class=\"ar\">".($rowTotal > 0 ? number_format($rowTotal, 0, ',', '.') : "")."</td>";
                        $html .= "</tr>";
                        $nomor++;
                    }
                }
            }

            // Blank spacer row
            $html .= "<tr><td colspan=\"17\" style=\"border:none; height:6pt;\"></td></tr>";

            // TOTAL row
            $html .= "<tr class=\"total-row\">";
            $html .= "<td colspan=\"4\" class=\"ac\">TOTAL</td>";
            for ($k = 1; $k <= 12; $k++) {
                $html .= "<td class=\"ar\">".($grandTotal[$k] > 0 ? number_format($grandTotal[$k], 0, ',', '.') : "")."</td>";
            }
            $html .= "<td class=\"ar\">".($grandTotalAll > 0 ? number_format($grandTotalAll, 0, ',', '.') : "")."</td>";
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

        $mpdf->SetTitle('Rekap Gaji '.$tahun);
        $mpdf->WriteHTML($html);
        $mpdf->Output('REKAP_GAJI_'.substr($tahun, -2).'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
