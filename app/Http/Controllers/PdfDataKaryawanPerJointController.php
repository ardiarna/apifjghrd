<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfDataKaryawanPerJointController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap($tahunAwal, $tahunAkhir, $includeEx = 0) {
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
            .subtitle { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 10px; color: #0000FF; text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; table-layout: fixed; }
            th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
            th { text-align: center; font-weight: bold; background-color: #FFC000; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .text-blue { color: #0000FF; font-weight: bold; }
            .bg-pink { background-color: #FFC0CB; }
        </style>";

        if ($includeEx == 1) {
            $dataKaryawan = $this->repoKaryawan->findAll([]);
        } else {
            $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        }

        $idxSheet = 0;
        $hasData = false;

        for ($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            $karyawanTahunIni = [];
            foreach ($dataKaryawan as $d) {
                if ($d->tanggal_masuk) {
                    if (date('Y', strtotime($d->tanggal_masuk)) == $tahun) {
                        $karyawanTahunIni[] = $d;
                    }
                }
            }

            if (empty($karyawanTahunIni) && $tahun != $tahunAwal) {
                // We can skip empty years if we want, but let's follow excel which creates it anyway.
                // Or wait, excel creates a sheet even if empty.
            }

            if ($idxSheet > 0) {
                $html .= "<pagebreak />";
            }
            $idxSheet++;
            $hasData = true;

            $details = [];
            $activeAreas = [];
            foreach ($karyawanTahunIni as $d) {
                $staf = $d->staf;
                $area = $d->area ? $d->area->nama : 'Lainnya';
                $details[$staf][$area][] = $d;
                $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
                $activeAreas[$kodeArea] = strtoupper($area);
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

            $html .= "<div class=\"title\">DATA KARYAWAN PT.FRATEKINDO JAYA GEMILANG (JOINT PER : $tahun)</div>";
            $html .= "<div class=\"subtitle\" style=\"text-decoration:none;\"><u>$areaString</u></div>";

            $html .= "<table>";
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $widths = [15, 65, 45, 65];
            foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<th>NO</th>";
            $html .= "<th>N A M A</th>";
            $html .= "<th>MASA KERJA</th>";
            $html .= "<th>J A B A T A N</th>";
            $html .= "</tr>";

            $nomor = 1;
            krsort($details);
            foreach ($details as $staf => $areas) {
                if($staf == 'N') {
                    $html .= "<tr><td></td><td colspan=\"3\" class=\"al text-blue\">NON STAF :</td></tr>";
                }
                foreach ($areas as $area => $karyawans) {
                    if($staf == 'Y') {
                        $html .= "<tr><td></td><td colspan=\"3\" class=\"al text-blue\">".$area." :</td></tr>";
                    }

                    foreach ($karyawans as $d) {
                        $bgClass = $d->aktif == 'N' ? 'bg-pink' : '';

                        $html .= "<tr class=\"$bgClass\">";
                        $html .= "<td class=\"ac\">".$nomor."</td>";
                        $html .= "<td class=\"al\">".$d->nama."</td>";

                        $masaKerja = '';
                        if ($d->tanggal_masuk) {
                            $masaKerja = date('d-m-Y', strtotime($d->tanggal_masuk));
                            if ($d->aktif == 'N' && $d->tanggal_keluar) {
                                $masaKerja .= ' s/d ' . date('d-m-Y', strtotime($d->tanggal_keluar));
                            }
                        }

                        $html .= "<td class=\"ac\">".$masaKerja."</td>";
                        $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                        $html .= "</tr>";

                        $nomor++;
                    }
                }
            }
            $html .= "</table>";

            $html .= "<br/><br/><table style=\"width:30%; table-layout:fixed; border:none;\">";
            $html .= "<tr>";
            $html .= "<td style=\"width: 20px; border:none;\" class=\"bg-pink\"></td>";
            $html .= "<td style=\"border:none; padding-left: 10px; font-weight:bold;\">= EX KARYAWAN</td>";
            $html .= "</tr>";
            $html .= "</table>";
        }

        if (!$hasData) {
            $html .= "<div class=\"title\">Kosong</div>";
        }

        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_KARYAWAN_PERJOINT_' . $tahunAwal . '_' . $tahunAkhir . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
