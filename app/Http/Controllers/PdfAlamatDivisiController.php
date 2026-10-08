<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfAlamatDivisiController extends Controller
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

        $html .= "<div class=\"title\">DATA/ALAMAT " . strtoupper($namaDivisi) . "</div>";
        $html .= "<div class=\"subtitle\">$areaString</div>";

        $html .= "<table>";
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        // 'A'=>4.55, 'B'=>38.00, 'C'=>30.00, 'D'=>10.77, 'E'=>40.14, 'F'=>56.00, 'G'=>57.10
        // Tot = ~ 236.
        $widths = [10, 45, 35, 15, 45, 65, 65]; 
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NO</th>";
        $html .= "<th>N A M A</th>";
        $html .= "<th>TEMPAT & TGL LAHIR</th>";
        $html .= "<th>TANGGAL GABUNG</th>";
        $html .= "<th>JABATAN</th>";
        $html .= "<th>ALAMAT SESUAI KTP</th>";
        $html .= "<th>ALAMAT TINGGAL SEKARANG</th>";
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
                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$d->nama."</td>";
                    
                    $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                    $html .= "<td class=\"ac\">".$ttl."</td>";
                    
                    $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $html .= "<td class=\"ac\">".$tglMasuk."</td>";
                    
                    $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                    
                    $html .= "<td class=\"al\">".$d->alamat_ktp."</td>";
                    $html .= "<td class=\"al\">".$d->alamat_tinggal."</td>";
                    
                    $html .= "</tr>";
                    
                    $nomor++;
                }
            }
        }
        $html .= "</table>";
        
        $mpdf->WriteHTML($html);
        $fileName = 'ALAMAT_' . strtoupper(str_replace(' ', '_', $namaDivisi)) . '.pdf';
        $mpdf->Output($fileName, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
