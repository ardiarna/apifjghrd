<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\CutiDate;
use App\Models\CutiDetail;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfCutiTanpaPotonganController extends Controller
{
    use AFhelper;

    public function tanpaPotonganSingle($tahun) {
        return $this->tanpaPotongan($tahun, $tahun);
    }

    public function tanpaPotongan($tahunAwal, $tahunAkhir)
    {
        $bulans = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUNI', 'JULI', 'AUG', 'SEPT', 'OKT', 'NOP', 'DES'];

        $css = "<style>
            body { font-family: calibri, arial, sans-serif; font-size: 7pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            td, th { border: 1pt solid #000; padding: 2px 2px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 13pt; color: #0000FF; font-weight: bold; text-align: center; font-family: 'Malgun Gothic', sans-serif; }
            .subtitle { font-size: 11pt; color: #0000FF; font-weight: bold; text-align: center; font-family: 'Malgun Gothic', sans-serif; margin-bottom: 15pt; }
            .text-blue { color: #0000FF; }
            .header-cell { font-weight: bold; text-align: center; background-color: #FFFFC0; border: 1pt solid #000; }
            .area-row td { border-bottom: 1pt solid #000; }
            .border-thin { border: 1pt solid #000; }
            .bg-green { background-color: #99CC00; }
        </style>";

        $html = $css;
        $firstPage = true;

        for ($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            if (!$firstPage) { $html .= "<pagebreak />"; }
            $firstPage = false;

            $karyawans_raw = Karyawan::with('jabatan', 'area')
                ->where('aktif', 'Y')
                ->whereHas('cutis', function($q) use ($tahun) {
                    $q->where('tahun', $tahun)->whereHas('details', function($q2) {
                        $q2->where('kategori', 'KHUSUS');
                    });
                })
                ->orderBy('id')
                ->get();
                
            $details = [];
            foreach ($karyawans_raw as $d) {
                $staf = $d->staf;
                $area = $d->area ? $d->area->nama : 'Lainnya';
                $details[$staf][$area][] = $d;
            }
            krsort($details);

            $html .= "<div class=\"title\">CUTI/IJIN TANPA MENGURANGI HAK KARYAWAN</div>";
            $html .= "<div class=\"subtitle\">PERIODE : JANUARI S/D DESEMBER ".$tahun."</div>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";

            // Dummy row for widths (total 18 cols, 287mm approx)
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:6mm; padding:0; border:none; height:0;\"></td>"; // NO
            $html .= "<td style=\"width:38mm; padding:0; border:none; height:0;\"></td>"; // NAMA
            $html .= "<td style=\"width:18mm; padding:0; border:none; height:0;\"></td>"; // MASA KERJA
            for ($b = 0; $b < 12; $b++) {
                $html .= "<td style=\"width:9mm; padding:0; border:none; height:0;\"></td>"; // JAN-DES
            }
            $html .= "<td style=\"width:15mm; padding:0; border:none; height:0;\"></td>"; // JML HARI
            $html .= "<td style=\"width:15mm; padding:0; border:none; height:0;\"></td>"; // JML BULAN
            $html .= "<td style=\"width:87mm; padding:0; border:none; height:0;\"></td>"; // KETERANGAN
            $html .= "</tr>";

            // Header Row 1
            $html .= "<tr>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">NO</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">MASA<br/>KERJA</th>";
            $html .= "<th class=\"header-cell\" colspan=\"12\">BULAN</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">JUMLAH<br/>(HARI)</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">JUMLAH<br/>(BULAN)</th>";
            $html .= "<th class=\"header-cell\" rowspan=\"2\">KETERANGAN</th>";
            $html .= "</tr>";

            // Header Row 2
            $html .= "<tr>";
            foreach ($bulans as $b) {
                $html .= "<th class=\"header-cell\">".$b."</th>";
            }
            $html .= "</tr>";

            $idx = 1;
            foreach ($details as $staf => $areas) {
                if ($staf == 'N') {
                    $html .= "<tr class=\"area-row\"><td class=\"ac border-thin\"></td><td colspan=\"17\" class=\"al text-blue border-thin\">NON STAF :</td></tr>";
                }
                foreach ($areas as $area => $karyawans) {
                    if ($staf == 'Y') {
                        $html .= "<tr class=\"area-row\"><td class=\"ac border-thin\"></td><td colspan=\"17\" class=\"al text-blue border-thin\">".$area." :</td></tr>";
                    }

                    foreach ($karyawans as $k) {
                        $detailsKet = CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
                            ->where('kategori', 'KHUSUS')
                            ->whereHas('cuti', function($q) use ($k, $tahun) {
                                $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
                            })
                            ->get();

                        $lines = [];
                        foreach ($detailsKet as $det) {
                            if ($det->dates->count() == 0) continue;

                            $satuan = $det->jenisKhusus ? strtolower($det->jenisKhusus->satuan) : 'hari';
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
                            $ket = $det->keterangan ?? 'Cuti Khusus';
                            $lines[] = "Tgl " . $dateStr . " = " . $ket;
                        }

                        $maxRows = max(1, count($lines));
                        for ($i = 0; $i < $maxRows; $i++) {
                            $html .= "<tr>";
                            if ($i == 0) {
                                $html .= "<td class=\"ac border-thin\">".$idx++."</td>";
                                $html .= "<td class=\"al border-thin\">".$k->nama."</td>";
                                $tgl_masuk = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
                                $html .= "<td class=\"ac border-thin\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";

                                $totalHari = 0;
                                $totalBulan = 0;
                                foreach ($detailsKet as $det) {
                                    $sat = strtolower($det->jenisKhusus->satuan ?? 'hari');
                                    if ($sat == 'bulan') $totalBulan += $det->lama_hari;
                                    else $totalHari += $det->lama_hari;
                                }

                                for ($m = 1; $m <= 12; $m++) {
                                    $hariInMonth = 0;
                                    $isBulan = false;

                                    foreach ($detailsKet as $det) {
                                        $sat = strtolower($det->jenisKhusus->satuan ?? 'hari');
                                        $datesInMonth = $det->dates->filter(function($d) use ($m) {
                                            return (int)date('m', strtotime($d->tanggal)) == $m;
                                        });

                                        if ($datesInMonth->count() > 0) {
                                            if ($sat == 'bulan') {
                                                $isBulan = true;
                                            } else {
                                                $hariInMonth += $datesInMonth->count();
                                            }
                                        }
                                    }

                                    $class = "ac border-thin";
                                    if ($isBulan) {
                                        $class .= " bg-green";
                                    }
                                    
                                    $html .= "<td class=\"".$class."\">".($hariInMonth > 0 ? $hariInMonth : '')."</td>";
                                }

                                $html .= "<td class=\"ac border-thin\">".($totalHari != 0 ? $totalHari : '')."</td>";
                                $html .= "<td class=\"ac border-thin\">".($totalBulan != 0 ? $totalBulan : '')."</td>";
                            } else {
                                // Draw empty cells with borders to maintain the table structure across rows for the same employee
                                for ($empty = 0; $empty < 17; $empty++) {
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
            if ($idx == 1) {
                 $html .= "<tr><td colspan=\"18\" class=\"ac border-thin\">Tidak ada data cuti khusus.</td></tr>";
            }
            $html .= "</table>";
        }

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle('Cuti Tanpa Potongan ' . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir));
        $mpdf->WriteHTML($html);
        $mpdf->Output('CUTI_TANPA_POTONGAN_'.($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir).'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
