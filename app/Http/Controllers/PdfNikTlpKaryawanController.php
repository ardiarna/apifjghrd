<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfNikTlpKaryawanController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap() {
        // We only have 6 columns, A4 Portrait is perfect.
        $mpdf = new Mpdf([
            'format'        => 'A4-P',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $html = "<style>
            body { font-family: sans-serif; font-size: 8pt; }
            .title { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 2px; color: #0000FF; text-decoration: underline; }
            .subtitle { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 10px; color: #0000FF; text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; table-layout: fixed; }
            th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
            th { text-align: center; font-weight: bold; background-color: #FFC000; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .text-blue { color: #0000FF; font-weight: bold; }
        </style>";

        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);

        $details = [];
        $activeAreas = [];
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;
            $activeAreas[$kodeArea] = strtoupper($d->area ? $d->area->nama : 'Lainnya');
        }

        $activeAreasSorted = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($activeAreas[$a->kode])) {
                $activeAreasSorted[] = strtoupper($a->nama);
            }
        }
        if (isset($activeAreas['Lainnya'])) {
            $activeAreasSorted[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreasSorted);

        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];
        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');

        $html .= "<div class=\"title\">LIST NIK KARYAWAN PT.FRATEKINDO JAYA GEMILANG</div>";
        $html .= "<div class=\"subtitle\">UPDATE : $bulanStr</div>";

        $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $widths = [10, 50, 20, 20, 30, 25, 20, 25]; // 200mm approx
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M A</th>";
        $html .= "<th>TGL LAHIR</th>";
        $html .= "<th>TANGGAL GABUNG</th>";
        $html .= "<th>N I K</th>";
        $html .= "<th>NO TLP</th>";
        $html .= "<th>AGE (YEARS)</th>";
        $html .= "<th>YEARS OF SERVICE</th>";
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
                    $tglLahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                    $html .= "<td class=\"ac\">".$tglLahir."</td>";
                    $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $html .= "<td class=\"ac\">".$tglMasuk."</td>";
                    $html .= "<td class=\"ac\">".$d->nik."</td>";
                    $html .= "<td class=\"ac\">".$d->telepon."</td>";
                    
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
                    
                    $html .= "</tr>";
                    
                    $nomor++;
                }
            }
        }
        $html .= "</table>";

        $html .= "<br/><br/><div style=\"padding-left: 10mm;\"><table style=\"width:35%; table-layout:fixed; border-collapse:collapse;\">";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $html .= "<td style=\"width:70%; padding:0; border:none;\"></td>";
        $html .= "<td style=\"width:30%; padding:0; border:none;\"></td>";
        $html .= "</tr>";
        
        $divisis = \App\Models\Divisi::orderBy('nama', 'asc')->get();
        foreach ($divisis as $div) {
            $html .= "<tr>";
            $html .= "<td class=\"al\">".$div->nama."</td>";
            $html .= "<td class=\"ac\">".$div->kode."</td>";
            $html .= "</tr>";
        }
        $html .= "</table></div>";
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('NIK_TLP_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
