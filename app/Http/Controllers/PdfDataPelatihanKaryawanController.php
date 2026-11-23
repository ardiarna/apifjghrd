<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataPelatihanKaryawanController extends Controller
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
            .text-blue { color: #0000FF; font-weight: bold; }
        </style>";

        // Fetch Data - only active employees with training
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y', 'has_training' => true]);
        $dataKaryawan->load([
            'trainingKaryawans' => function($q) {
                $q->with('training')->orderBy('tanggal_awal', 'asc');
            }
        ]);

        $details = [];
        $activeAreas = [];

        foreach ($dataKaryawan as $d) {
            if ($d->trainingKaryawans->isEmpty()) {
                continue;
            }
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $activeAreas[$kodeArea] = strtoupper($d->area ? $d->area->nama : 'Lainnya');
            $details[$staf][$area][] = $d;
        }

        $activeAreasSorted = [];
        $dbAreasModels = $this->repoArea->findAll();
        foreach ($dbAreasModels as $a) {
            if (isset($activeAreas[$a->kode])) {
                $activeAreasSorted[] = strtoupper($a->nama);
            }
        }
        if (isset($activeAreas['Lainnya'])) {
            $activeAreasSorted[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreasSorted);

        $html .= "<div class=\"title\">DATA PELATIHAN KARYAWAN PT.FRATEKINDO JAYA GEMILANG</div>";
        $html .= "<div class=\"subtitle\">$areaString</div>";

        $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $widths = [10, 50, 25, 25, 15, 20, 50, 45, 50]; 
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M E</th>";
        $html .= "<th>DATE OF BIRTH</th>";
        $html .= "<th>START WORKING</th>";
        $html .= "<th>AGE<br/>(YEARS)</th>";
        $html .= "<th>YEARS OF<br/>SERVICE</th>";
        $html .= "<th>POSITION</th>";
        $html .= "<th>LAST EDUCATION</th>";
        $html .= "<th>TRAINING - MEETING - PAMERAN - TUGAS LN</th>";
        $html .= "</tr>";

        $nomor = 1;
        $today = date_create('today');
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $html .= "<tr><td></td><td colspan=\"8\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $html .= "<tr><td></td><td colspan=\"8\" class=\"al text-blue\">".$area." :</td></tr>";
                }

                foreach ($karyawans as $d) {
                    $trainings = $d->trainingKaryawans;
                    $numTrainings = $trainings->count();
                    $maxRows = max(1, $numTrainings);

                    $pendidikanFormat = '';
                    $pendidikanJurusan = '';
                    if ($d->pendidikan) {
                        $pendidikanFormat = $d->pendidikan->nama;
                        $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                        $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));
                        if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                        if ($jurusan) {
                            if ($maxRows >= 2) {
                                $pendidikanJurusan = 'Jurusan: ' . $jurusan;
                            } else {
                                $pendidikanFormat .= ', Jurusan: ' . $jurusan;
                            }
                        }
                    }

                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $html .= "<tr>";
                            
                            $html .= "<td class=\"ac\">".$nomor."</td>";
                            $html .= "<td class=\"al\">".$d->nama."</td>";
                            
                            $tglLahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                            $html .= "<td class=\"ac\">".$tglLahir."</td>";
                            
                            $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                            $html .= "<td class=\"ac\">".$tglMasuk."</td>";
                            
                            $age = '';
                            if ($d->tanggal_lahir) {
                                $dt1 = date_create($d->tanggal_lahir);
                                $age = date_diff($dt1, $today)->y;
                            }
                            $html .= "<td class=\"ac\">".$age."</td>";
                            
                            $service = '';
                            if ($d->tanggal_masuk) {
                                $dt1 = date_create($d->tanggal_masuk);
                                $service = date_diff($dt1, $today)->y;
                            }
                            $html .= "<td class=\"ac\">".$service."</td>";
                            
                            $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                            $html .= "<td class=\"al\">".$pendidikanFormat."</td>";
                        } else {
                            $html .= "<tr>";
                            $html .= "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
                            if ($i == 1 && $pendidikanJurusan != '') {
                                $html .= "<td class=\"al\">".$pendidikanJurusan."</td>";
                            } else {
                                $html .= "<td></td>";
                            }
                        }

                        // Training column
                        if ($i < $numTrainings) {
                            $tk = $trainings[$i];
                            $tName = $tk->training ? $tk->training->nama : ('Training ID: ' . $tk->training_id);

                            $tInfo = $tName;
                            $tglAwal = $tk->tanggal_awal ? date('d-m-Y', strtotime($tk->tanggal_awal)) : '';
                            $tglAkhir = $tk->tanggal_akhir ? date('d-m-Y', strtotime($tk->tanggal_akhir)) : '';
                            $tglRange = $tglAwal;
                            if ($tglAkhir && $tglAkhir !== $tglAwal) {
                                $tglRange .= ($tglRange ? ' s/d ' : '') . $tglAkhir;
                            }
                            if ($tglRange) {
                                $tInfo .= " : " . $tglRange;
                            }
                            if ($tk->keterangan) {
                                $tInfo .= "<br/>" . nl2br(htmlspecialchars(trim($tk->keterangan)));
                            }

                            $html .= "<td class=\"al\">".$tInfo."</td>";
                        } else {
                            $html .= "<td></td>";
                        }
                        
                        $html .= "</tr>";
                    }
                    $nomor++;
                }
            }
        }
        $html .= "</table>";

        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_PELATIHAN_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
