<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataDivisiController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap($id) {
        $namaDivisi = '';
        if ($id == 'PS') {
            $namaDivisi = 'PROFESSIONAL SERVICE';
        } else {
            $divisi = \App\Models\Divisi::find($id);
            if (!$divisi) return response()->json(['success' => false, 'message' => 'Divisi not found']);
            $namaDivisi = $divisi->nama;
        }

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

        $all = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $dataKaryawan = [];
        foreach ($all as $d) {
            if ($id == 'PS') {
                if ($d->jabatan && strtoupper($d->jabatan->nama) == 'PROFESSIONAL SERVICE') {
                    $dataKaryawan[] = $d;
                }
            } else {
                if ($d->divisi_id == $id) {
                    $dataKaryawan[] = $d;
                }
            }
        }

        $details = [];
        $totalKaryawanPerArea = [];

        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';

            $details[$staf][$area][] = $d;

            if (!isset($totalKaryawanPerArea[$kodeArea])) {
                $totalKaryawanPerArea[$kodeArea] = 0;
            }
            $totalKaryawanPerArea[$kodeArea]++;
        }

        $activeAreas = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($totalKaryawanPerArea[$a->kode])) {
                $activeAreas[] = strtoupper($a->nama);
            }
        }
        if (isset($totalKaryawanPerArea['Lainnya'])) {
            $activeAreas[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreas);

        $judul = ($id == 'PS') ? 'LIST PROFESSIONAL SERVICE' : 'LIST DIVISION ' . strtoupper($namaDivisi);
        $html .= "<div class=\"title\">$judul</div>";
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
        $html .= "<th>TRAINING</th>";
        $html .= "</tr>";

        $nomor = 1;
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
                    $trainings = $d->trainingKaryawans()->get();
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
                            $tName = '';
                            if ($tk->training) {
                                $tName = $tk->training->nama;
                            } else {
                                $tName = 'Training ID: ' . $tk->training_id;
                            }

                            $tInfo = $tName;
                            if ($tk->tanggal) {
                                $tInfo .= " - " . date('d-m-Y', strtotime($tk->tanggal));
                            }
                            if ($tk->keterangan) {
                                $tInfo .= "<br/>" . trim($tk->keterangan);
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
        $fileName = 'DATA_' . strtoupper(str_replace(' ', '_', $namaDivisi)) . '.pdf';
        $mpdf->Output($fileName, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
