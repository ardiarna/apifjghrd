<?php

namespace App\Http\Controllers;

use App\Models\CicKaryawan;
use App\Models\CicCutiDate;
use App\Models\CicCutiDetail;
use App\Models\CicJatahCutiTahunan;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfCicListCutiController extends Controller
{
    use AFhelper;

    public function listCutiSingle($tahun) {
        return $this->listCuti($tahun, $tahun);
    }

    public function listCuti($tahunAwal, $tahunAkhir)
    {
        $karyawans_raw = CicKaryawan::with('jabatan', 'area')->where('aktif', 'Y')->orderBy('id')->get();
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);

        $bulans = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOP', 'DES'];

        $css = "<style>
            body { font-family: calibri, arial, sans-serif; font-size: 6pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            td, th { border: 1pt solid #000; padding: 2px 2px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 13pt; color: #0000FF; font-weight: bold; text-align: center; font-family: 'Malgun Gothic', sans-serif; }
            .subtitle { font-size: 11pt; color: #0000FF; font-weight: bold; text-align: center; font-family: 'Malgun Gothic', sans-serif; }
            .text-blue { color: #0000FF; }
            .text-red { color: #FF0000; }
            .header-cell { font-weight: bold; text-align: center; background-color: #FFFFC0; border: 1pt solid #000; }
            .area-row td { border-bottom: 1pt solid #000; }
            .blank-row td { border-left: none; border-right: none; height: 3pt; }
            .border-hair { border: 1px solid #CCC; } /* simulate HAIR border */
            .border-thin { border: 1pt solid #000; }
        </style>";

        $html = $css;
        $firstPage = true;

        for ($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            if (!$firstPage) { $html .= "<pagebreak />"; }
            $firstPage = false;

            $html .= "<div class=\"title\">CUTI KARYAWAN CIC</div>";
            $html .= "<div class=\"subtitle\" style=\"margin-bottom: 10pt;\">PERIODE : JANUARI S/D DESEMBER ".$tahun."</div>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";

            // Dummy row for widths (total 24 cols, 281mm approx)
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:6mm; padding:0; border:none; height:0;\"></td>"; // NO
            $html .= "<td style=\"width:35mm; padding:0; border:none; height:0;\"></td>"; // NAMA
            $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>"; // MASA KERJA
            $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>";  // JML CUTI
            $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>";  // THN LALU +
            $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>";  // THN LALU -
            $html .= "<td style=\"width:12mm; padding:0; border:none; height:0;\"></td>"; // TOTAL CUTI
            for ($b = 0; $b < 12; $b++) {
                $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>"; // JAN-DES
            }
            $html .= "<td style=\"width:11mm; padding:0; border:none; height:0;\"></td>"; // SISA TAHUNAN
            $html .= "<td style=\"width:11mm; padding:0; border:none; height:0;\"></td>"; // BERSAMA
            $html .= "<td style=\"width:10mm; padding:0; border:none; height:0;\"></td>"; // IJIN
            $html .= "<td style=\"width:11mm; padding:0; border:none; height:0;\"></td>"; // SISA
            $html .= "<td style=\"width:49mm; padding:0; border:none; height:0;\"></td>"; // KETERANGAN
            $html .= "</tr>";

            // Header Row 1
            $html .= "<tr>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">NO</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">MASA<br/>KERJA</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">JML<br/>CUTI</th>";
            $html .= "<th class=\"header-cell\" colspan=\"2\">THN LALU</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">TOTAL<br/>CUTI</th>";
            $html .= "<th class=\"header-cell\" colspan=\"12\">CUTI TAHUNAN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">SISA CUTI<br/>TAHUNAN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">JML CUTI<br/>BERSAMA</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">JML<br/>IJIN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\" style=\"overflow:hidden;\">SISA<br/>CUTI</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">KETERANGAN</th>";
            $html .= "</tr>";

            // Header Row 2
            $html .= "<tr>";
            $html .= "<th class=\"header-cell\">+</th>";
            $html .= "<th class=\"header-cell\">-</th>";
            foreach ($bulans as $b) {
                $html .= "<th class=\"header-cell\">".$b."</th>";
            }
            $html .= "</tr>";

            $idx = 1;
            foreach ($details as $staf => $areas) {
                if ($staf == 'N') {
                    $html .= "<tr class=\"area-row\"><td class=\"ac border-thin\"></td><td colspan=\"23\" class=\"al text-blue border-thin\">NON STAF :</td></tr>";
                }
                foreach ($areas as $area => $karyawans) {
                    if ($staf == 'Y') {
                        $html .= "<tr class=\"area-row\"><td class=\"ac border-thin\"></td><td colspan=\"23\" class=\"al text-blue border-thin\">".$area." :</td></tr>";
                    }

                    foreach ($karyawans as $k) {
                        $detailsKet = CicCutiDetail::with(['dates', 'cicCuti', 'cicJenisCutiKhusus'])
                            ->whereIn('kategori', ['IJIN', 'CUTI_MASAL', 'GANTI_HARI_LIBUR'])
                            ->whereHas('cicCuti', function($q) use ($k, $tahun) {
                                $q->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                            })
                            ->get();

                        $lines = [];
                        foreach ($detailsKet as $det) {
                            if ($det->dates->count() == 0) continue;

                            $satuan = $det->cicJenisCutiKhusus ? strtolower($det->cicJenisCutiKhusus->satuan) : 'hari';
                            $lama = (int) $det->lama_hari;

                            if ($lama > 5 || $satuan == 'bulan') {
                                $months = ['Jan'=>'Jan', 'Feb'=>'Peb', 'Mar'=>'Mar', 'Apr'=>'Apr', 'May'=>'Mei', 'Jun'=>'Jun', 'Jul'=>'Jul', 'Aug'=>'Ags', 'Sep'=>'Sep', 'Oct'=>'Okt', 'Nov'=>'Nop', 'Dec'=>'Des'];
                                $formatDate = function($tanggal) use ($months) {
                                    $engMon = date('M', strtotime($tanggal));
                                    $indMon = $months[$engMon] ?? $engMon;
                                    return ltrim(date('d', strtotime($tanggal)), '0') . ' ' . $indMon . ' \'' . date('y', strtotime($tanggal));
                                };

                                $dates = $det->dates()->orderBy('tanggal')->get();
                                if ($dates->count() == 1) {
                                    $dateStr = $formatDate($dates->first()->tanggal);
                                } else {
                                    $dateStr = $formatDate($dates->first()->tanggal) . ' s/d ' . $formatDate($dates->last()->tanggal);
                                }
                            } else {
                                $groupedDates = [];
                                foreach ($det->dates()->orderBy('tanggal')->get() as $d) {
                                    $months = ['Jan'=>'Jan', 'Feb'=>'Peb', 'Mar'=>'Mar', 'Apr'=>'Apr', 'May'=>'Mei', 'Jun'=>'Jun', 'Jul'=>'Jul', 'Aug'=>'Ags', 'Sep'=>'Sep', 'Oct'=>'Okt', 'Nov'=>'Nop', 'Dec'=>'Des'];
                                    $engMon = date('M', strtotime($d->tanggal));
                                    $indMon = $months[$engMon] ?? $engMon;
                                    $my = $indMon . ' \'' . date('y', strtotime($d->tanggal));
                                    $day = date('d', strtotime($d->tanggal));
                                    $groupedDates[$my][] = ltrim($day, '0');
                                }
                                $dateStrings = [];
                                foreach ($groupedDates as $my => $days) {
                                    $dateStrings[] = implode(', ', $days) . ' ' . $my;
                                }
                                $dateStr = implode(', ', $dateStrings);
                            }
                            $ket = $det->keterangan ?? ($det->kategori == 'CUTI_MASAL' ? 'Cutber' : ($det->kategori == 'GANTI_HARI_LIBUR' ? 'Ganti Hari Libur' : 'Ijin'));
                            $lines[] = "Tgl " . $dateStr . " = " . $ket;
                        }

                        $maxRows = max(1, count($lines));
                        for ($i = 0; $i < $maxRows; $i++) {
                            // Only draw borders on the first row or if it's the only row, but actually Excel renders hair borders inside.
                            // In mPDF, we can just print a row.
                            $html .= "<tr>";
                            if ($i == 0) {
                                $html .= "<td class=\"ac border-thin\">".$idx++."</td>";
                                $html .= "<td class=\"al border-thin\">".$k->nama."</td>";
                                $tgl_masuk = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
                                $html .= "<td class=\"ac border-thin\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";

                                $jatah = CicJatahCutiTahunan::where('cic_karyawan_id', $k->id)->where('tahun', $tahun)->first();
                                $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
                                $plus = $jatah ? $jatah->plus_tahun_lalu : 0;
                                $min = $jatah ? $jatah->min_tahun_lalu : 0;
                                $totalHak = $jmlCuti + $plus - $min;

                                $html .= "<td class=\"ac border-thin\">".($jmlCuti != 0 ? $jmlCuti : '')."</td>";
                                $html .= "<td class=\"ac border-thin\">".($plus != 0 ? $plus : '')."</td>";
                                $html .= "<td class=\"ac border-thin\">".($min != 0 ? $min : '')."</td>";
                                $html .= "<td class=\"ac border-thin text-red\">".($totalHak != 0 ? $totalHak : '')."</td>";

                                $totalDiambilTahunan = 0;
                                for ($m = 1; $m <= 12; $m++) {
                                    $diambilBulan = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($k, $tahun) {
                                        $q->where('kategori', 'TAHUNAN')
                                          ->whereHas('cicCuti', function($q2) use ($k, $tahun) {
                                              $q2->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                                          });
                                    })->whereMonth('tanggal', $m)->count();
                                    $totalDiambilTahunan += $diambilBulan;
                                    $html .= "<td class=\"ac border-thin\">".($diambilBulan != 0 ? $diambilBulan : '')."</td>";
                                }

                                $sisaTahunan = $totalHak - $totalDiambilTahunan;
                                $valSisaTahunan = $jatah ? $sisaTahunan : ($sisaTahunan != 0 ? $sisaTahunan : '');
                                $html .= "<td class=\"ac border-thin text-red\">".$valSisaTahunan."</td>";

                                $cutiBersama = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($k, $tahun) {
                                    $q->where('kategori', 'CUTI_MASAL')
                                      ->whereHas('cicCuti', function($q2) use ($k, $tahun) {
                                          $q2->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                                      });
                                })->count();
                                $html .= "<td class=\"ac border-thin\">".($cutiBersama != 0 ? $cutiBersama : '')."</td>";

                                $jmlIjin = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($k, $tahun) {
                                    $q->where('kategori', 'IJIN')
                                      ->whereHas('cicCuti', function($q2) use ($k, $tahun) {
                                          $q2->where('cic_karyawan_id', $k->id)->where('tahun', $tahun);
                                      });
                                })->count();
                                $html .= "<td class=\"ac border-thin\">".($jmlIjin != 0 ? $jmlIjin : '')."</td>";

                                $sisaCuti = $sisaTahunan - $cutiBersama - $jmlIjin;
                                $valSisaCuti = $jatah ? $sisaCuti : ($sisaCuti != 0 ? $sisaCuti : '');
                                $html .= "<td class=\"ac border-thin text-red\">".$valSisaCuti."</td>";
                            } else {
                                // Draw empty cells with borders to maintain the table structure across rows for the same employee
                                for ($empty = 0; $empty < 23; $empty++) {
                                    $html .= "<td class=\"border-thin\"></td>";
                                }
                            }

                            $ketVal = $lines[$i] ?? '';
                            $html .= "<td class=\"al border-thin\">".$ketVal."</td>";
                            $html .= "</tr>";
                        }
                    }
                }
            }
            $html .= "<tr><td colspan=\"24\" style=\"border-top:1pt solid #000; border-bottom:none; border-left:none; border-right:none; height:1pt;\"></td></tr>";
            $html .= "</table>";
        }

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle('List Cuti CIC ' . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir));
        $mpdf->WriteHTML($html);
        $mpdf->Output('LIST_CUTI_'.($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir).'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
