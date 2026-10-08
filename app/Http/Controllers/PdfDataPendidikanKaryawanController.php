<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataPendidikanKaryawanController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap() {
        $mpdf = new Mpdf([
            'format'        => 'A4-L',
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

        $html .= "<div class=\"title\">DATA PENDIDIKAN KARYAWAN PT.FRATEKINDO JAYA GEMILANG</div>";
        $html .= "<div class=\"subtitle\">UPDATE : $bulanStr</div>";

                $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $widths = [10, 60, 25, 25, 30, 30, 45, 52]; // 277mm approx width (A4 Landscape)
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M A</th>";
        $html .= "<th>TANGGAL GABUNG</th>";
        $html .= "<th>AGE (YEARS)</th>";
        $html .= "<th>YEARS OF SERVICE</th>";
        $html .= "<th>N I K</th>";
        $html .= "<th>J A B A T A N</th>";
        $html .= "<th>PENDIDIKAN TERAKHIR</th>";
        $html .= "</tr>";

        $nomor = 1;
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $html .= "<tr><td></td><td colspan=\"7\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $html .= "<tr><td></td><td colspan=\"7\" class=\"al text-blue\">".$area." :</td></tr>";
                }

                foreach ($karyawans as $d) {
                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$d->nama."</td>";
                    
                    $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $html .= "<td class=\"ac\">".$tglMasuk."</td>";
                    
                    $age = '';
                    if ($d->tanggal_lahir) {
                        $dt1 = date_create($d->tanggal_lahir);
                        $dt2 = date_create('today');
                        $age = date_diff($dt1, $dt2)->y;
                    }
                    $html .= "<td class=\"ac\">".$age."</td>";
                    
                    $service = '';
                    if ($d->tanggal_masuk) {
                        $dt1 = date_create($d->tanggal_masuk);
                        $dt2 = date_create('today');
                        $service = date_diff($dt1, $dt2)->y;
                    }
                    $html .= "<td class=\"ac\">".$service."</td>";

                    $html .= "<td class=\"ac\">".$d->nik."</td>";
                    $html .= "<td class=\"ac\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                    
                    $pendidikanFormat = '';
                    if ($d->pendidikan) {
                        $pendidikanFormat = $d->pendidikan->nama;
                        $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                        $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));

                        if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                        if ($jurusan) {
                            $pendidikanFormat .= ' , Jurusan: ' . $jurusan;
                        }
                    }
                    $html .= "<td class=\"ac\">".$pendidikanFormat."</td>";
                    
                    $html .= "</tr>";
                    
                    $nomor++;
                }
            }
        }
        $html .= "</table>";
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_PENDIDIKAN_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
