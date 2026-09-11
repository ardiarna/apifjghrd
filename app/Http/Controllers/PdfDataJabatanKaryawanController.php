<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataJabatanKaryawanController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap() {
        $mpdf = new Mpdf([
            'format'        => 'A4-P',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $html = "<style>
            body { font-family: sans-serif; font-size: 8pt; }
            .title { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 2px; color: #0000FF; text-decoration: underline; }
            .subtitle { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 10px; color: #0000FF; text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; table-layout: fixed; }
            th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
            th { text-align: center; font-weight: bold; background-color: #FFC000; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .text-blue { color: #0000FF; font-weight: bold; }
        </style>";

        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);

        $details = [];
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }

        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];
        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');

        $html .= "<div class=\"title\">DATA KARYAWAN FJG</div>";
        $html .= "<div class=\"subtitle\">UPDATE : $bulanStr</div>";

        $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        // A=4.55, B=38, C=45
        // Proportions: ~10%, ~40%, ~50%
        $widths = [15, 75, 100]; 
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M A</th>";
        $html .= "<th>J A B A T A N</th>";
        $html .= "</tr>";

        $nomor = 1;
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $html .= "<tr><td></td><td colspan=\"2\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $html .= "<tr><td></td><td colspan=\"2\" class=\"al text-blue\">".$area." :</td></tr>";
                }

                foreach ($karyawans as $d) {
                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$d->nama."</td>";
                    $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                    $html .= "</tr>";
                    
                    $nomor++;
                }
            }
        }
        $html .= "</table>";
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_JABATAN_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
