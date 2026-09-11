<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataStatusKaryawanController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap() {
        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $html = "<style>
            body { font-family: sans-serif; font-size: 8pt; }
            .title { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 2px; color: #0000FF; text-decoration: underline; }
            .subtitle { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 10px; color: #0000FF; text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; table-layout: fixed; }
            th, td { border: 1px solid black; padding: 2px 4px; vertical-align: top; }
            th { text-align: center; font-weight: bold; background-color: #FFC000; vertical-align: middle; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .bg-blue { background-color: #BBDEFB; }
            .bg-orange { background-color: #FFCC80; }
            .bg-purple { background-color: #EA80FC; }
            .bg-green { background-color: #B9F6CA; }
            .bg-red { background-color: #F44336; }
            .text-blue { color: #0000FF; font-weight: bold; }
            .legend-table td { border: none !important; }
        </style>";

        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);

        $details = [];
        $totalKaryawanPerStatus = [];
        $totalKaryawanPerStatusPerArea = [];
        $totalKaryawanPerArea = [];
        $totalKaryawan = 0;
        $statusNamaToId = [];
        
        $activeAreas = [];

        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;
            $activeAreas[$kodeArea] = strtoupper($d->area ? $d->area->nama : 'Lainnya');

            $statusNama = $d->statusKerja ? $d->statusKerja->nama : 'Lain-lain';

            if (!isset($totalKaryawanPerStatus[$statusNama])) {
                $totalKaryawanPerStatus[$statusNama] = 0;
                $statusNamaToId[$statusNama] = $d->status_kerja_id;
            }
            $totalKaryawanPerStatus[$statusNama]++;

            if (!isset($totalKaryawanPerArea[$kodeArea])) $totalKaryawanPerArea[$kodeArea] = 0;
            $totalKaryawanPerArea[$kodeArea]++;

            if (!isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea])) $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] = 0;
            $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]++;

            $totalKaryawan++;
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

        $html .= "<div class=\"title\">STATUS KARYAWAN PT.FRATEKINDO JAYA GEMILANG</div>";
        $html .= "<div class=\"subtitle\">$areaString</div>";
        $html .= "<div class=\"subtitle\" style=\"text-decoration:none;\"><u>UPDATE : $bulanStr</u></div>";

        $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        // Widths: A=4.55, B=38.00, C=30.00, D=15.00, E=40.14, F=45.00, G=45.00 => Total ~217
        $widths = [15, 60, 45, 25, 60, 60, 60]; 
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M A</th>";
        $html .= "<th>TEMPAT & TGL LAHIR</th>";
        $html .= "<th>MASA KERJA</th>";
        $html .= "<th>J A B A T A N</th>";
        $html .= "<th>PENDIDIKAN TERAKHIR</th>";
        $html .= "<th>STATUS KARYAWAN PKWT / KONTRAK</th>";
        $html .= "</tr>";

        $nomor = 1;
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $html .= "<tr><td></td><td colspan=\"6\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $html .= "<tr><td></td><td colspan=\"6\" class=\"al text-blue\">".$area." :</td></tr>";
                }

                foreach ($karyawans as $d) {
                    $statusId = $d->status_kerja_id;
                    $bgClass = "";
                    if ($statusId == '2') $bgClass = 'bg-blue';
                    elseif ($statusId == '3') $bgClass = 'bg-orange';
                    elseif ($statusId == '4') $bgClass = 'bg-purple';
                    elseif ($statusId == '5') $bgClass = 'bg-green';
                    elseif ($statusId != '1' && $statusId != null) $bgClass = 'bg-red';

                    $html .= "<tr class=\"$bgClass\">";
                    
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$d->nama."</td>";
                    $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                    $html .= "<td class=\"al\">".$ttl."</td>";
                    
                    $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $html .= "<td class=\"ac\">".$tgl_masuk."</td>";
                    
                    $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";

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
                    $html .= "<td class=\"al\">".$pendidikanFormat."</td>";

                    $statusNamaCell = $d->statusKerja ? $d->statusKerja->nama : '';
                    $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                    $numPerjanjian = $perjanjians->count();

                    if ($numPerjanjian > 0) {
                        $latestPerjanjian = $perjanjians[$numPerjanjian - 1];
                        $bulanMapSingkat = [
                            1 => "JAN", 2 => "PEB", 3 => "MAR", 4 => "APR", 5 => "MEI", 6 => "JUN",
                            7 => "JUL", 8 => "AGUSTUS", 9 => "SEP", 10 => "OKT", 11 => "NOP", 12 => "DES"
                        ];

                        if ($statusId == '1') {
                            $awl = strtotime($latestPerjanjian->tanggal_awal);
                            $tglAwal = date('d ', $awl) . $bulanMapSingkat[(int)date('n', $awl)] . date('\'y', $awl);
                            $statusNamaCell .= " (Per: " . $tglAwal . ")";
                        } else {
                            $awl = strtotime($latestPerjanjian->tanggal_awal);
                            $tglAwal = date('d ', $awl) . $bulanMapSingkat[(int)date('n', $awl)] . date('\'y', $awl);
                            $tglAkhir = '';
                            if ($latestPerjanjian->tanggal_akhir) {
                                $akr = strtotime($latestPerjanjian->tanggal_akhir);
                                $tglAkhir = date('d ', $akr) . $bulanMapSingkat[(int)date('n', $akr)] . date('\'y', $akr);
                            }
                            $statusNamaCell .= " (PER : " . $tglAwal . ($tglAkhir ? " S/D " . $tglAkhir : "") . ")";
                        }
                    }
                    $html .= "<td class=\"al\">".$statusNamaCell."</td>";
                    
                    $html .= "</tr>";
                    $nomor++;
                }
            }
        }
        $html .= "</table>";

        // Legend
        $dbAreas = \App\Models\Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
        $allAreas = $dbAreas;
        foreach (array_keys($totalKaryawanPerArea) as $a) {
            if (!in_array($a, $allAreas)) {
                $allAreas[] = $a;
            }
        }

        $html .= "<br/><br/><table class=\"legend-table\" style=\"width:50%; table-layout:auto; font-weight:bold; border:none;\">";
        $html .= "<tr><td colspan=\"".(2 + count($allAreas))."\" style=\"border:none; font-size:10pt;\">KETERANGAN STATUS KARYAWAN</td></tr>";

        foreach ($totalKaryawanPerStatus as $statusNama => $total) {
            $statusId = isset($statusNamaToId[$statusNama]) ? $statusNamaToId[$statusNama] : null;
            $textStyle = "";
            if ($statusId == '2') $textStyle = "color: #2196F3;"; // Darker blue
            elseif ($statusId == '3') $textStyle = "color: #FF9800;"; // Darker orange
            elseif ($statusId == '4') $textStyle = "color: #9C27B0;"; // Darker purple
            elseif ($statusId == '5') $textStyle = "color: #4CAF50;"; // Darker green
            elseif ($statusId != '1' && $statusId != null) $textStyle = "color: #F44336;"; // Red

            $html .= "<tr>";
            $html .= "<td style=\"border:none; $textStyle\">".$statusNama."</td>";
            $html .= "<td style=\"border:none; $textStyle\">: ".$total."</td>";
            
            foreach ($allAreas as $kodeArea) {
                $nilai = isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]) ? $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] : 0;
                $html .= "<td style=\"border:none; padding-left:20px; $textStyle\">".$kodeArea.": ".$nilai."</td>";
            }
            $html .= "</tr>";
        }
        
        $html .= "<tr>";
        $html .= "<td style=\"border:none; color: #FF0000;\">TOTAL KARYAWAN</td>";
        $html .= "<td style=\"border:none; color: #FF0000;\">: ".$totalKaryawan."</td>";
        foreach ($allAreas as $kodeArea) {
            $nilai = isset($totalKaryawanPerArea[$kodeArea]) ? $totalKaryawanPerArea[$kodeArea] : 0;
            $html .= "<td style=\"border:none; padding-left:20px; color: #FF0000;\">".$kodeArea.": ".$nilai."</td>";
        }
        $html .= "</tr>";
        $html .= "</table>";
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_STATUS_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
