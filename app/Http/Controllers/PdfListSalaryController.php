<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;
use App\Models\Upah;
use App\Models\Area;

class PdfListSalaryController extends Controller
{
    protected $repoKaryawan;

    public function __construct(KaryawanRepository $repoKaryawan) {
        $this->repoKaryawan = $repoKaryawan;
    }

    public function listSalary() {
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $upahsData = Upah::all()->keyBy('karyawan_id');
        $details = [];
        $totalKaryawanPerStatus = [];
        $totalKaryawanPerStatusPerArea = [];
        $totalKaryawanPerArea = [];
        $totalKaryawan = 0;
        $statusNamaToId = [];

        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;

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
        krsort($details);

        $css = '<style>
            body { font-family: calibri, sans-serif; font-size: 7pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 0.5pt solid #000; padding: 2px 4px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 13pt; color: #0000FF; text-decoration: underline; text-align: center; border: none; font-weight: bold; }
            .header-bg { background-color: #FFC000; }

            /* Status colors */
            .bg-2 { background-color: #BBDEFB; }
            .bg-3 { background-color: #FFCC80; }
            .bg-4 { background-color: #EA80FC; }
            .bg-5 { background-color: #B9F6CA; }
            .bg-other { background-color: #F44336; }

            .text-blue { color: #0000FF; }
            .text-red { color: #FF0000; }
        </style>';

        $isBasic = request()->query('type') === 'basic';
        $maxColSpan = $isBasic ? 7 : 12;

        $html = $css;
        $html .= '<table>
            <colgroup>
                <col style="width: 5mm;">
                <col style="width: 37mm;">
                <col style="width: 12mm;">
                <col style="width: 16mm;">
                <col style="width: 12mm;">
                <col style="width: 50mm;">
                <col style="width: 20mm;">';
        
        if (!$isBasic) {
            $html .= '<col style="width: 20mm;">
                <col style="width: 20mm;">
                <col style="width: 12mm;">
                <col style="width: 20mm;">
                <col style="width: 48mm;">';
        }

        $html .= '</colgroup>
            <thead>
                <tr>
                    <td colspan="'.$maxColSpan.'" class="title">SALARY PT.FRATEKINDO JAYA GEMILANG</td>
                </tr>
                <tr><td colspan="'.$maxColSpan.'" class="no-border"></td></tr>
                <tr class="header-bg ac">
                    <th rowspan="2">NO</th>
                    <th rowspan="2">NAMA KARYAWAN</th>
                    <th rowspan="2">MASA KERJA</th>
                    <th rowspan="2">NIK</th>
                    <th rowspan="2">TGL LAHIR</th>
                    <th rowspan="2">JABATAN</th>
                    <th rowspan="2">GAJI</th>';
                    
        if (!$isBasic) {
            $html .= '<th colspan="2">U/MAKAN & TRANSPORTASI</th>
                    <th rowspan="2">STATUS OVERTIME</th>
                    <th colspan="2">STATUS KARYAWAN</th>';
        }
        $html .= '</tr>
                <tr class="header-bg ac">';
        
        if (!$isBasic) {
            $html .= '<th>TETAP</th>
                    <th>TDK TETAP</th>
                    <th>TETAP / PKWTT</th>
                    <th>KONTRAK / PKWT / PERCOBAAN</th>';
        }
        $html .= '</tr>
            </thead>
            <tbody>';

        $nomor = 1;
        $sumGaji = 0;
        $sumMakanTetap = 0;
        $sumMakanTdkTetap = 0;

        foreach ($details as $staf => $areas) {
            if ($staf == 'N') {
                $html .= '<tr>
                    <td style="border-left: 0.5pt solid #000; border-right: none; border-top: none; border-bottom: none;"></td>
                    <td colspan="'.($maxColSpan - 1).'" class="al text-blue" style="border-left: none; border-right: 0.5pt solid #000; border-top: none; border-bottom: none;">NON STAF :</td>
                </tr>';
            }
            foreach ($areas as $area => $karyawans) {
                if ($staf == 'Y') {
                    $html .= '<tr>
                        <td style="border-left: 0.5pt solid #000; border-right: none; border-top: none; border-bottom: none;"></td>
                        <td colspan="'.($maxColSpan - 1).'" class="al text-blue" style="border-left: none; border-right: 0.5pt solid #000; border-top: none; border-bottom: none;">' . htmlspecialchars($area) . ' :</td>
                    </tr>';
                }

                foreach ($karyawans as $d) {
                    $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $nik = $d->nik ? htmlspecialchars($d->nik) : '';
                    $tgl_lahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                    $jabatan = $d->jabatan ? htmlspecialchars($d->jabatan->nama) : '';

                    $upah = isset($upahsData[$d->id]) ? $upahsData[$d->id] : null;
                    $gaji = $upah ? (float)$upah->gaji : 0;
                    $sumGaji += $gaji;

                    $uang_makan = $upah ? (float)$upah->uang_makan : 0;
                    $makanHarian = $upah ? $upah->makan_harian : 'N';

                    $makanTetap = '';
                    $makanTdkTetap = '';
                    if ($makanHarian == 'Y') {
                        $makanTdkTetap = $uang_makan;
                        $sumMakanTdkTetap += $uang_makan;
                    } else {
                        $makanTetap = $uang_makan;
                        $sumMakanTetap += $uang_makan;
                    }

                    $overtime = ($upah && $upah->overtime == 'Y') ? 'OT' : 'NON OT';

                    $statusNamaRaw = $d->statusKerja ? strtolower($d->statusKerja->nama) : '';
                    $statusTetap = '';
                    $statusKontrak = '';

                    if (strpos($statusNamaRaw, 'tetap') !== false || strpos($statusNamaRaw, 'pkwtt') !== false) {
                        $statusTetap = 'TETAP/PKWTT';
                    } else if (strpos($statusNamaRaw, 'kontrak') !== false || strpos($statusNamaRaw, 'pkwt') !== false || strpos($statusNamaRaw, 'percobaan') !== false) {
                        $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                        if ($perjanjians->count() > 0) {
                            $latest = $perjanjians->last();
                            $bulanMap = [
                                1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR',
                                5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGUSTUS',
                                9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
                            ];
                            $awal = strtotime($latest->tanggal_awal);
                            $strAwal = date('d', $awal) . ' ' . $bulanMap[(int)date('n', $awal)] . date('\'y', $awal);
                            $strAkhir = '';
                            if ($latest->tanggal_akhir) {
                                $akhir = strtotime($latest->tanggal_akhir);
                                $strAkhir = ' S/D ' . date('d', $akhir) . ' ' . $bulanMap[(int)date('n', $akhir)] . date('\'y', $akhir);
                            }
                            $statusKontrak = 'PER : ' . $strAwal . $strAkhir;
                        }
                    } else {
                        if ($statusNamaRaw) {
                            $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                            if ($perjanjians->count() > 0) {
                                $latest = $perjanjians->last();
                                $bulanMap = [
                                    1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR',
                                    5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGUSTUS',
                                    9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
                                ];
                                $awal = strtotime($latest->tanggal_awal);
                                $strAwal = date('d', $awal) . ' ' . $bulanMap[(int)date('n', $awal)] . date('\'y', $awal);
                                $strAkhir = '';
                                if ($latest->tanggal_akhir) {
                                    $akhir = strtotime($latest->tanggal_akhir);
                                    $strAkhir = ' S/D ' . date('d', $akhir) . ' ' . $bulanMap[(int)date('n', $akhir)] . date('\'y', $akhir);
                                }
                                $statusKontrak = 'PER : ' . $strAwal . $strAkhir;
                            }
                        }
                    }

                    $statusId = $d->status_kerja_id;
                    $bgClass = '';
                    if ($statusId == '2') $bgClass = 'bg-2';
                    elseif ($statusId == '3') $bgClass = 'bg-3';
                    elseif ($statusId == '4') $bgClass = 'bg-4';
                    elseif ($statusId == '5') $bgClass = 'bg-5';
                    elseif ($statusId && $statusId != '1') $bgClass = 'bg-other';

                    $html .= '<tr class="' . $bgClass . '">
                        <td class="ac">' . $nomor . '</td>
                        <td class="al">' . htmlspecialchars($d->nama) . '</td>
                        <td class="ac">' . $tgl_masuk . '</td>
                        <td class="ac">' . $nik . '</td>
                        <td class="ac">' . $tgl_lahir . '</td>
                        <td class="al">' . $jabatan . '</td>
                        <td class="ar">' . ($gaji ? number_format($gaji, 0, ',', '.') : '0') . '</td>';
                        
                    if (!$isBasic) {
                        $html .= '<td class="ar">' . ($makanTetap !== '' ? number_format($makanTetap, 0, ',', '.') : '') . '</td>
                        <td class="ar">' . ($makanTdkTetap !== '' ? number_format($makanTdkTetap, 0, ',', '.') : '') . '</td>
                        <td class="ac">' . $overtime . '</td>
                        <td class="ac">' . $statusTetap . '</td>
                        <td class="ac">' . $statusKontrak . '</td>';
                    }
                    $html .= '</tr>';
                    $nomor++;
                }

                // Add blank row after each area, similar to Excel, but retain outer borders
                if ($staf == 'Y') {
                    $html .= '<tr>
                        <td style="border-left: 0.5pt solid #000; border-right: none; border-top: none; border-bottom: none;"></td>
                        <td colspan="'.($maxColSpan - 1).'" style="border-left: none; border-right: 0.5pt solid #000; border-top: none; border-bottom: none;"></td>
                    </tr>';
                }
            }
        }

        // Totals
        $html .= '<tr class="text-red">
            <td colspan="6" class="ac">TOTAL GAJI POKOK KARYAWAN</td>
            <td class="ar">' . number_format($sumGaji, 0, ',', '.') . '</td>';
            
        if (!$isBasic) {
            $html .= '<td class="ar">' . number_format($sumMakanTetap, 0, ',', '.') . '</td>
            <td class="ar">' . number_format($sumMakanTdkTetap, 0, ',', '.') . '</td>
            <td colspan="3"></td>';
        }
        $html .= '</tr>';
        $html .= '</tbody></table>';

        $html .= '<br><br>';

        // Footer Summary: KETERANGAN STATUS KARYAWAN
        $dbAreas = Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
        $allAreas = $dbAreas;
        foreach (array_keys($totalKaryawanPerArea) as $a) {
            if (!in_array($a, $allAreas)) {
                $allAreas[] = $a;
            }
        }

        $html .= '<table style="width: 50%; font-size: 7pt;">
            <tr>
                <td colspan="2" class="no-border" style="font-weight: bold;">KETERANGAN STATUS KARYAWAN</td>';

        foreach ($allAreas as $a) {
            $html .= '<td class="ac no-border" style="font-weight: bold;">' . htmlspecialchars($a) . '</td>';
        }
        $html .= '<td class="ac no-border" style="font-weight: bold;">TOTAL</td></tr>';

        foreach ($totalKaryawanPerStatus as $statusNama => $total) {
            $statusId = isset($statusNamaToId[$statusNama]) ? $statusNamaToId[$statusNama] : null;
            $textColor = '#000000';
            if ($statusId == '2') $textColor = '#2196F3';
            elseif ($statusId == '3') $textColor = '#FF9800';
            elseif ($statusId == '4') $textColor = '#9C27B0';
            elseif ($statusId == '5') $textColor = '#4CAF50';
            elseif ($statusId && $statusId != '1') $textColor = '#F44336';

            $html .= '<tr>
                <td class="no-border" style="color: ' . $textColor . ';">' . htmlspecialchars($statusNama) . '</td>
                <td class="no-border" style="color: ' . $textColor . ';">: ' . $total . '</td>';

            $rowTotal = 0;
            foreach ($allAreas as $a) {
                $val = isset($totalKaryawanPerStatusPerArea[$statusNama][$a]) ? $totalKaryawanPerStatusPerArea[$statusNama][$a] : 0;
                $rowTotal += $val;
                $html .= '<td class="ac no-border" style="color: ' . $textColor . ';">' . ($val > 0 ? $val : '') . '</td>';
            }
            $html .= '<td class="ac no-border" style="border-left: 1px solid #000 !important; color: ' . $textColor . ';">' . $rowTotal . '</td></tr>';
        }

        $html .= '<tr class="text-red">
            <td class="no-border" style="font-weight: bold; color: #FF0000;">TOTAL KARYAWAN</td>
            <td class="no-border" style="font-weight: bold; color: #FF0000;">: ' . $totalKaryawan . '</td>';
        $grandTotal = 0;
        foreach ($allAreas as $a) {
            $val = isset($totalKaryawanPerArea[$a]) ? $totalKaryawanPerArea[$a] : 0;
            $grandTotal += $val;
            $html .= '<td class="ac no-border" style="border-top: 1px solid #000 !important; font-weight: bold; color: #FF0000;">' . ($val > 0 ? $val : '') . '</td>';
        }
        $html .= '<td class="ac no-border" style="border-top: 1px solid #000 !important; border-left: 1px solid #000 !important; font-weight: bold; color: #FF0000;">' . $grandTotal . '</td></tr>';

        $html .= '</table>';

        $mpdf = new Mpdf([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 5,
        ]);

        $title = $isBasic ? 'List Salary' : 'List Salary & Tunjangan';
        $filename = $isBasic ? 'LIST_SALARY.pdf' : 'LIST_SALARY_&_TUNJANGAN.pdf';
        
        $mpdf->SetTitle($title);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
