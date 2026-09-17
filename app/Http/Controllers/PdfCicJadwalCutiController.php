<?php

namespace App\Http\Controllers;

use App\Models\CicKaryawan;
use App\Models\CicCutiDate;
use App\Models\HariLibur;
use App\Models\CicJatahCutiTahunan;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfCicJadwalCutiController extends Controller
{
    use AFhelper;

    private $arrBulanPendek = [
        1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN',
        7 => 'JUL', 8 => 'AGS', 9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
    ];

    private $arrBulanPanjang = [
        '', 'JANUARI', 'PEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
        'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOPEMBER', 'DESEMBER'
    ];

    public function jadwal($tahun)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $karyawans_raw = CicKaryawan::with('jabatan', 'area')->where('aktif', 'Y')->orderBy('id')->get();
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);

        $css = "<style>
            body { font-family: calibri, arial, sans-serif; font-size: 6pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            td, th { border: 1pt solid #000; padding: 1px 2px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 12pt; color: #0000FF; font-weight: bold; text-align: center; }
            .subtitle { font-size: 10pt; color: #0000FF; font-weight: bold; text-align: center; }
            .text-blue { color: #0000FF; }
            .bg-orange { background-color: #FFC000; }
            .bg-red { background-color: #FF0000; color: #FFFFFF; }
            .bg-green { background-color: #92D050; color: #FFFFFF; }
            .header-cell { font-weight: bold; text-align: center; background-color: #FFC000; border: 1pt solid #000; }
            .leg-red { background-color: #FF0000; width: 8mm; height: 4mm; display: inline-block; }
            .leg-green { background-color: #92D050; width: 8mm; height: 4mm; display: inline-block; }
        </style>";

        $html = $css;
        $firstPage = true;

        for ($m = 1; $m <= 12; $m++) {
            if (!$firstPage) { $html .= "<pagebreak />"; }
            $firstPage = false;

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $tahun);

            $hariLiburs       = HariLibur::whereYear('tanggal', $tahun)->whereMonth('tanggal', $m)->orderBy('tanggal')->get();
            $holidayDatesRed  = $hariLiburs->where('iscutber', 'N')->pluck('tanggal')->toArray();
            $holidayDatesGreen = $hariLiburs->where('iscutber', 'Y')->pluck('tanggal')->toArray();

            // Determine red/green day indices (1-31)
            $redDays   = [];
            $greenDays = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $m, $d);
                $dayOfWeek = date('N', strtotime($dateStr));
                if ($dayOfWeek == 6 || $dayOfWeek == 7 || in_array($dateStr, $holidayDatesRed)) {
                    $redDays[] = $d;
                } elseif (in_array($dateStr, $holidayDatesGreen)) {
                    $greenDays[] = $d;
                }
            }

            // Draw two tables per month: STAF then MANAJEMEN
            foreach ([false, true] as $isManajemen) {
                $divisiLabel = $isManajemen ? 'MANAJEMEN' : 'STAF';

                // Title
                $html .= "<p class=\"title\" style=\"margin:0 0 2px 0;\">LIST CUTI CIC ".$this->arrBulanPanjang[$m]." ".$tahun."</p>";
                $html .= "<p class=\"subtitle\" style=\"margin:0 0 4px 0;\">DIVISI : ".$divisiLabel."</p>";

                // Table
                // Columns: NO(4mm), NAMA(35mm), D1..D31(each 6.5mm), SISA CUTI(10mm), UNPAID(10mm), GANTI(12mm), KHUSUS(20mm)
                // Total: 4+35+(31×6.5)+10+10+12+20 = 4+35+201.5+52 = 292.5mm  → too wide for A4-L
                // Reduce: NAMA=28mm, days=5.8mm each → 4+28+(31×5.8)+10+10+12+20=4+28+179.8+52=263.8mm OK
                $html .= "<table style=\"width:100%; table-layout:fixed;\">";

                // dummy row for widths
                $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
                $html .= "<td style=\"width:4mm; padding:0; border:none; height:0;\"></td>";
                $html .= "<td style=\"width:28mm; padding:0; border:none; height:0;\"></td>";
                for ($d = 1; $d <= 31; $d++) {
                    $html .= "<td style=\"width:5.8mm; padding:0; border:none; height:0;\"></td>";
                }
                $html .= "<td style=\"width:10mm; padding:0; border:none; height:0;\"></td>";
                $html .= "<td style=\"width:10mm; padding:0; border:none; height:0;\"></td>";
                $html .= "<td style=\"width:12mm; padding:0; border:none; height:0;\"></td>";
                $html .= "<td style=\"width:20mm; padding:0; border:none; height:0;\"></td>";
                $html .= "</tr>";

                // Header row 1: NO, NAMA, TANGGAL(colspan31), SISA CUTI, UNPAID LEAVE, GANTI HARI LIBUR, CUTI KHUSUS
                $html .= "<tr>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\">NO</th>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\">NAMA KARYAWAN</th>";
                $html .= "<th class=\"header-cell\" colspan=\"31\">TANGGAL</th>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">SISA CUTI</th>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">UNPAID LEAVE</th>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">GANTI HARI LIBUR</th>";
                $html .= "<th class=\"header-cell\" rowspan=\"2\">CUTI KHUSUS</th>";
                $html .= "</tr>";

                // Header row 2: day numbers 1-31
                $html .= "<tr>";
                for ($d = 1; $d <= 31; $d++) {
                    $dayClass = "header-cell";
                    $extra = "";
                    if ($d <= $daysInMonth) {
                        if (in_array($d, $redDays)) {
                            $extra = " style=\"background-color:#FF0000; color:#FFFFFF; border:1pt solid #000;\"";
                        } elseif (in_array($d, $greenDays)) {
                            $extra = " style=\"background-color:#92D050; color:#FFFFFF; border:1pt solid #000;\"";
                        }
                        $html .= "<th class=\"header-cell\"".$extra.">".$d."</th>";
                    } else {
                        $html .= "<th class=\"header-cell\"></th>";
                    }
                }
                $html .= "</tr>";

                // Data rows
                $hasData = false;
                $idx = 1;
                foreach ($details as $staf => $areas) {
                    $hasPrintedStafLabel = false;
                    foreach ($areas as $area => $karyawansGrp) {
                        $hasPrintedAreaLabel = false;
                        foreach ($karyawansGrp as $k) {
                            $man = $k->manajemen ?? 'N';
                            if ($isManajemen && $man != 'Y') continue;
                            if (!$isManajemen && $man == 'Y') continue;

                            $cutiDatesMonth = CicCutiDate::with('cicCutiDetail')
                                ->whereMonth('tanggal', $m)
                                ->whereHas('cicCutiDetail.cicCuti', function ($q) use ($k, $tahun) {
                                    $q->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                                })->get();

                            if (!$isManajemen && $cutiDatesMonth->count() == 0) continue;

                            // Print area/staf label
                            if ($staf == 'N' && !$hasPrintedStafLabel) {
                                $html .= "<tr><td class=\"ac\"></td>";
                                $html .= "<td colspan=\"36\" class=\"al text-blue\">NON STAF :</td></tr>";
                                $hasPrintedStafLabel = true;
                            }
                            if ($staf == 'Y' && !$hasPrintedAreaLabel) {
                                $html .= "<tr><td class=\"ac\"></td>";
                                $html .= "<td colspan=\"36\" class=\"al text-blue\">".$area." :</td></tr>";
                                $hasPrintedAreaLabel = true;
                            }

                            // Build cuti date map
                            $tglMap = [];
                            $cntUnpaid = 0;
                            $cntGanti  = 0;
                            $ketKhususArr = [];
                            foreach ($cutiDatesMonth as $cd) {
                                $day = (int) date('d', strtotime($cd->tanggal));
                                $tglMap[$day] = true;
                                $kat = optional($cd->cicCutiDetail)->kategori ?? '';
                                if ($kat == 'UNPAID') {
                                    $cntUnpaid++;
                                } elseif ($kat == 'GANTI_HARI_LIBUR') {
                                    $cntGanti++;
                                    $ket = trim(optional($cd->cicCutiDetail)->keterangan ?? '');
                                    if ($ket && !in_array($ket, $ketKhususArr)) $ketKhususArr[] = $ket;
                                } elseif ($kat == 'KHUSUS') {
                                    $ket = trim(optional($cd->cicCutiDetail)->keterangan ?? '');
                                    if ($ket && !in_array($ket, $ketKhususArr)) $ketKhususArr[] = $ket;
                                }
                            }
                            $ketKhususStr = implode(', ', $ketKhususArr);

                            // Sisa cuti
                            $jatah = CicJatahCutiTahunan::where('cic_karyawan_id', $k->id)->where('tahun', $tahun)->first();
                            $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
                            $sisaTahunLalu = $jatah ? ($jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
                            $totalHak = $jmlCuti + $sisaTahunLalu;
                            $diambil  = CicCutiDate::whereHas('cicCutiDetail', function ($q) use ($k, $tahun) {
                                $q->whereIn('kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
                                  ->whereHas('cicCuti', function ($q2) use ($k, $tahun) {
                                      $q2->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                                  });
                            })->whereMonth('tanggal', '<=', $m)->count();
                            $sisaCuti = $totalHak - $diambil;

                            $html .= "<tr>";
                            $html .= "<td class=\"ac\">".$idx."</td>";
                            $html .= "<td class=\"al\">".$k->nama."</td>";
                            for ($d = 1; $d <= 31; $d++) {
                                $cellBg = "";
                                if ($d <= $daysInMonth) {
                                    if (in_array($d, $redDays))   $cellBg = " style=\"background-color:#FF0000; color:#FFFFFF;\"";
                                    elseif (in_array($d, $greenDays)) $cellBg = " style=\"background-color:#92D050; color:#FFFFFF;\"";
                                }
                                $mark = ($d <= $daysInMonth && isset($tglMap[$d])) ? "X" : "";
                                $html .= "<td class=\"ac\"".$cellBg.">".$mark."</td>";
                            }
                            $html .= "<td class=\"ac\">".$sisaCuti."</td>";
                            $html .= "<td class=\"ac\">".($cntUnpaid > 0 ? $cntUnpaid : '')."</td>";
                            $html .= "<td class=\"ac\">".($cntGanti > 0 ? $cntGanti : '')."</td>";
                            $html .= "<td class=\"al\">".$ketKhususStr."</td>";
                            $html .= "</tr>";

                            $hasData = true;
                            $idx++;
                        }
                    }
                }

                if (!$hasData) {
                    $html .= "<tr><td colspan=\"37\" class=\"ac\">Tidak ada data</td></tr>";
                }

                $html .= "</table>";

                // Gap between STAF and MANAJEMEN tables
                if (!$isManajemen) {
                    $html .= "<br/><br/><br/><br/><br/><br/>";
                }
            }

            // Legend - Red holidays
            $holidaysRed   = $hariLiburs->where('iscutber', 'N');
            $holidaysGreen = $hariLiburs->where('iscutber', 'Y');

            if ($holidaysRed->count() > 0 || $holidaysGreen->count() > 0) {
                $html .= "<br/>";
            }

            foreach ($holidaysRed as $hl) {
                $dt     = \Carbon\Carbon::parse($hl->tanggal);
                $tglStr = "TGL " . $dt->format('d') . " " . strtoupper($this->arrBulanPanjang[(int)$dt->format('n')]) . " '" . $dt->format('y');
                $html .= "<table style=\"width:100%; border-collapse:collapse; margin-bottom:2pt;\">";
                $html .= "<tr>";
                $html .= "<td style=\"background-color:#FF0000; width:6mm; height:4mm; border:none; padding:0;\"></td>";
                $html .= "<td style=\"border:none; padding:0 0 0 4px; width:150mm;\">".$tglStr." = ".strtoupper($hl->nama)."</td>";
                $html .= "<td style=\"border:none;\"></td>";
                $html .= "</tr>";
                $html .= "</table>";
            }

            if ($holidaysGreen->count() > 0) {
                $html .= "<br/>";
                foreach ($holidaysGreen as $hl) {
                    $dt     = \Carbon\Carbon::parse($hl->tanggal);
                    $tglStr = "TGL " . $dt->format('d') . " " . strtoupper($this->arrBulanPanjang[(int)$dt->format('n')]) . " '" . $dt->format('y');
                    $html .= "<table style=\"width:100%; border-collapse:collapse; margin-bottom:2pt;\">";
                    $html .= "<tr>";
                    $html .= "<td style=\"background-color:#92D050; width:6mm; height:4mm; border:none; padding:0;\"></td>";
                    $html .= "<td style=\"border:none; padding:0 0 0 4px; width:150mm;\">".$tglStr." = ".strtoupper($hl->nama)."</td>";
                    $html .= "<td style=\"border:none;\"></td>";
                    $html .= "</tr>";
                    $html .= "</table>";
                }
            }
        } // end for month

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);

        $mpdf->SetTitle('Jadwal Cuti CIC '.$tahun);
        $mpdf->WriteHTML($html);
        $mpdf->Output('JADWAL_CUTI_'.$tahun.'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
