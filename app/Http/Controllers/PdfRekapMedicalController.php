<?php

namespace App\Http\Controllers;

use App\Repositories\UpahRepository;
use App\Repositories\MedicalRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfRekapMedicalController extends Controller
{
    use AFhelper;

    protected $repoKaryawan, $repoMedical, $repoPayrollHeader;

    public function __construct(UpahRepository $repoKaryawan, MedicalRepository $repoMedical, PayrollHeaderRepository $repoPayrollHeader) {
        $this->repoKaryawan       = $repoKaryawan;
        $this->repoMedical        = $repoMedical;
        $this->repoPayrollHeader  = $repoPayrollHeader;
    }

    public function rekap($tahun) {
        $arrBulanPendek  = ['','JAN','FEB','MAR','APR','MEI','JUN','JUL','AGU','SEP','OKT','NOV','DES'];
        $arrBulanPanjang = ['','JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

        // --- Ambil data ---
        $dataDetails = $this->repoKaryawan->findAll(['aktif' => 'Y', 'sort_by' => 'tanggal_masuk']);
        $details = [];
        foreach ($dataDetails as $dt) {
            $details[$dt->staf][$dt->area->nama][$dt->id] = $dt;
        }

        $dataGajis = $this->repoPayrollHeader->findUpahsByTahun($tahun);
        $gajis = [];
        foreach ($dataGajis as $d) {
            $gajis[$d->karyawan_id] = $d->gaji;
        }

        $rawatJalans = [];
        foreach ($this->repoMedical->findRekapsRawatJalan($tahun) as $d) {
            $rawatJalans[$d->karyawan_id] = $d;
        }

        $kacamatas = [];
        foreach ($this->repoMedical->findAll(['jenis' => 'K']) as $d) {
            $kacamatas[$d->tahun][$d->karyawan_id][$d->id] = $d;
        }

        $lahirs = [];
        foreach ($this->repoMedical->findAll(['jenis' => 'I']) as $d) {
            $lahirs[$d->tahun][$d->karyawan_id][$d->id] = $d;
        }

        // --- CSS ---
        $css = "<style>
            body { font-family: arial, sans-serif; font-size: 6pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1pt solid #000; padding: 2px 3px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 11pt; color: #0000FF; text-decoration: underline; font-weight: bold; }
            .text-blue { color: #0000FF; }
            .header th { font-weight: bold; text-align: center; background-color: #FFFF00; border: 1pt solid #000; }
            .total-row td { font-weight: bold; color: #0000FF; border: 1pt solid #000; }
            .area-row td { border: 1pt solid #000; }
        </style>";

        $html = $css;

        // ====================== 1. RAWAT JALAN ======================
        $html .= "<table style=\"width:100%; border-collapse:collapse;\"><tr>";
        $html .= "<td class=\"title ac no-border\" colspan=\"22\">REKAPITULASI MEDICAL KARYAWAN PT.FRATEKINDO JAYA GEMILANG</td></tr>";
        $html .= "<tr><td class=\"title ac no-border\" colspan=\"22\">PERIODE : JANUARI S/D DESEMBER ".$tahun."</td></tr>";
        $html .= "<tr><td class=\"title ac no-border\" colspan=\"22\">KELAS : RAWAT JALAN</td></tr>";
        $html .= "<tr><td class=\"no-border\" colspan=\"22\">&nbsp;</td></tr></table>";

        // Rawat Jalan: cols: NO,NAMA,JABATAN,MASA KERJA,TGL LAHIR,GAJI,TUN1,TUN2,JAN..DES,SISA,JUMLAH KLAIM
        $html .= "<table style=\"width:100%; table-layout:fixed;\">";
        // dummy row for column widths: 22 cols
        // NO=5,NAMA=30,JAB=25,MASAKER=13,TGLLHR=13,GAJI=16,TUN1=16,TUN2=16,12×bulan=12,SISA=16,JMLKLAIM=16  total=5+30+25+13+13+16+16+16+(12×12)+16+16=262mm
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $html .= "<td style=\"width:5mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:30mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:25mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:13mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:13mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>";
        for ($m = 0; $m < 12; $m++) { $html .= "<td style=\"width:12mm; padding:0; border:none; height:0;\"></td>"; }
        $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>";
        $html .= "<td style=\"width:16mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr class=\"header\">";
        $html .= "<th rowspan=\"2\">NO</th>";
        $html .= "<th rowspan=\"2\">NAMA KARYAWAN</th>";
        $html .= "<th rowspan=\"2\">JABATAN</th>";
        $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">MASA<br/>KERJA</th>";
        $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">TGL<br/>LAHIR</th>";
        $html .= "<th rowspan=\"2\">GAJI</th>";
        $html .= "<th rowspan=\"2\">TUNJANGAN 1</th>";
        $html .= "<th rowspan=\"2\">TUNJANGAN 2</th>";
        $html .= "<th colspan=\"12\">B U L A N</th>";
        $html .= "<th rowspan=\"2\">SISA IDR</th>";
        $html .= "<th rowspan=\"2\">JUMLAH KLAIM</th>";
        $html .= "</tr>";
        $html .= "<tr class=\"header\">";
        for ($k = 1; $k <= 12; $k++) { $html .= "<th style=\"overflow:hidden;\">".$arrBulanPanjang[$k]."</th>"; }
        $html .= "</tr>";

        $nomor = 1;
        $grandGaji = $grandTun1 = $grandTun2 = $grandSisa = $grandKlaim = 0;
        $grandBln = array_fill(1, 12, 0);

        foreach ($details as $staf => $areas) {
            if ($staf == 'N') {
                $html .= "<tr class=\"area-row\"><td class=\"ac\"></td><td colspan=\"21\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawan_ids) {
                if ($staf == 'Y') {
                    $html .= "<tr class=\"area-row\"><td class=\"ac\"></td><td colspan=\"21\" class=\"al text-blue\">".$area." :</td></tr>";
                }
                foreach ($karyawan_ids as $karyawan_id => $dkaryawan) {
                    $tgl_masuk = $dkaryawan->tanggal_masuk ? date('d-m-y', strtotime($dkaryawan->tanggal_masuk)) : '';
                    $tgl_lahir = $dkaryawan->tanggal_lahir ? date('d-m-y', strtotime($dkaryawan->tanggal_lahir)) : '';
                    $gaji = isset($gajis[$karyawan_id]) ? $gajis[$karyawan_id] : (isset($dkaryawan->gaji) ? $dkaryawan->gaji : 0);
                    $tunjangan1 = $dkaryawan->kelamin == 'P' ? $gaji : 0;
                    $tunjangan2 = $dkaryawan->kelamin == 'L' ? $gaji * 2 : 0;
                    $jumlahKlaim = 0;
                    for ($k = 1; $k <= 12; $k++) {
                        $jumlahKlaim += isset($rawatJalans[$karyawan_id]) ? ($rawatJalans[$karyawan_id]->{'bln_'.$k} ?? 0) : 0;
                    }
                    $sisa = ($dkaryawan->kelamin == 'P' ? $tunjangan1 : $tunjangan2) - $jumlahKlaim;

                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$this->afAbbreviateName($dkaryawan->nama)."</td>";
                    $html .= "<td class=\"al\">".$dkaryawan->jabatan->nama."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_lahir."</td>";
                    $html .= "<td class=\"ar\">".($gaji > 0 ? number_format($gaji, 0, ',', '.') : '')."</td>";
                    $html .= "<td class=\"ar\">".($tunjangan1 > 0 ? number_format($tunjangan1, 0, ',', '.') : '')."</td>";
                    $html .= "<td class=\"ar\">".($tunjangan2 > 0 ? number_format($tunjangan2, 0, ',', '.') : '')."</td>";
                    for ($k = 1; $k <= 12; $k++) {
                        $val = isset($rawatJalans[$karyawan_id]) ? ($rawatJalans[$karyawan_id]->{'bln_'.$k} ?? 0) : 0;
                        $grandBln[$k] += $val;
                        $html .= "<td class=\"ar\">".($val != 0 ? number_format($val, 0, ',', '.') : '')."</td>";
                    }
                    $html .= "<td class=\"ar text-blue\">".number_format($sisa, 0, ',', '.')."</td>";
                    $html .= "<td class=\"ar text-blue\">".number_format($jumlahKlaim, 0, ',', '.')."</td>";
                    $html .= "</tr>";

                    $grandGaji += $gaji; $grandTun1 += $tunjangan1; $grandTun2 += $tunjangan2;
                    $grandSisa += $sisa; $grandKlaim += $jumlahKlaim;
                    $nomor++;
                }
            }
        }

        // blank spacer
        $html .= "<tr><td colspan=\"22\" style=\"border:none; height:5pt;\"></td></tr>";
        // TOTAL row
        $html .= "<tr class=\"total-row\">";
        $html .= "<td colspan=\"5\" class=\"ac\">TOTAL</td>";
        $html .= "<td class=\"ar\">".number_format($grandGaji, 0, ',', '.')."</td>";
        $html .= "<td class=\"ar\">".number_format($grandTun1, 0, ',', '.')."</td>";
        $html .= "<td class=\"ar\">".number_format($grandTun2, 0, ',', '.')."</td>";
        for ($k = 1; $k <= 12; $k++) {
            $html .= "<td class=\"ar\">".($grandBln[$k] > 0 ? number_format($grandBln[$k], 0, ',', '.') : '')."</td>";
        }
        $html .= "<td class=\"ar\">".number_format($grandSisa, 0, ',', '.')."</td>";
        $html .= "<td class=\"ar\">".number_format($grandKlaim, 0, ',', '.')."</td>";
        $html .= "</tr>";
        $html .= "</table>";

        // ====================== 2. KACAMATA ======================
        $firstKacamata = true;
        foreach ($kacamatas as $keyTahun => $karyawan_ids) {
            $html .= "<pagebreak />";

            $html .= "<table style=\"width:100%; border-collapse:collapse;\"><tr>";
            $html .= "<td class=\"title ac no-border\" colspan=\"7\">KLAIM KACAMATA KARYAWAN</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"7\">PT.FRATEKINDO JAYA GEMILANG</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"7\">PERIODE : TAHUN ".$keyTahun."</td></tr>";
            $html .= "<tr><td class=\"no-border\" colspan=\"7\">&nbsp;</td></tr></table>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:55mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:18mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:18mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:60mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:20mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:28mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";
            $html .= "<tr class=\"header\">";
            $html .= "<th rowspan=\"2\">NO</th>";
            $html .= "<th rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">MASA<br/>KERJA</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">TGL<br/>LAHIR</th>";
            $html .= "<th rowspan=\"2\">JABATAN</th>";
            $html .= "<th rowspan=\"2\">BULAN &amp; TAHUN</th>";
            $html .= "<th rowspan=\"2\">JUMLAH IDR</th>";
            $html .= "</tr><tr class=\"header\"></tr>";

            $nomor = 1; $total = 0;
            foreach ($karyawan_ids as $karyawan_id => $ids) {
                foreach ($ids as $id => $d) {
                    $tgl_masuk = $d->karyawan->tanggal_masuk ? date('d-m-y', strtotime($d->karyawan->tanggal_masuk)) : '';
                    $tgl_lahir = $d->karyawan->tanggal_lahir ? date('d-m-y', strtotime($d->karyawan->tanggal_lahir)) : '';
                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$this->afAbbreviateName($d->karyawan->nama)."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_lahir."</td>";
                    $html .= "<td class=\"al\">".$d->karyawan->jabatan->nama."</td>";
                    $html .= "<td class=\"ac\">".$arrBulanPendek[$d->bulan]."'".$d->tahun."</td>";
                    $html .= "<td class=\"ar\">".number_format($d->jumlah, 0, ',', '.')."</td>";
                    $html .= "</tr>";
                    $total += $d->jumlah;
                    $nomor++;
                }
            }
            $html .= "<tr class=\"total-row\">";
            $html .= "<td colspan=\"6\" class=\"ac\">TOTAL KLAIM</td>";
            $html .= "<td class=\"ar\">".number_format($total, 0, ',', '.')."</td>";
            $html .= "</tr></table>";
        }

        // ====================== 3. MELAHIRKAN ======================
        foreach ($lahirs as $keyTahun => $karyawan_ids) {
            $html .= "<pagebreak />";

            $html .= "<table style=\"width:100%; border-collapse:collapse;\"><tr>";
            $html .= "<td class=\"title ac no-border\" colspan=\"7\">KLAIM MELAHIRKAN / GUGUR KANDUNGAN KARYAWAN</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"7\">PT.FRATEKINDO JAYA GEMILANG</td></tr>";
            $html .= "<tr><td class=\"title ac no-border\" colspan=\"7\">PERIODE : TAHUN ".$keyTahun."</td></tr>";
            $html .= "<tr><td class=\"no-border\" colspan=\"7\">&nbsp;</td></tr></table>";

            $html .= "<table style=\"width:100%; table-layout:fixed;\">";
            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $html .= "<td style=\"width:8mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:55mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:18mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:18mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:60mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:20mm; padding:0; border:none; height:0;\"></td>";
            $html .= "<td style=\"width:28mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";
            $html .= "<tr class=\"header\">";
            $html .= "<th rowspan=\"2\">NO</th>";
            $html .= "<th rowspan=\"2\">NAMA KARYAWAN</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">MASA<br/>KERJA</th>";
            $html .= "<th rowspan=\"2\" style=\"overflow:hidden;\">TGL<br/>LAHIR</th>";
            $html .= "<th rowspan=\"2\">JABATAN</th>";
            $html .= "<th rowspan=\"2\">BULAN &amp; TAHUN</th>";
            $html .= "<th rowspan=\"2\">JUMLAH IDR</th>";
            $html .= "</tr><tr class=\"header\"></tr>";

            $nomor = 1; $total = 0;
            foreach ($karyawan_ids as $karyawan_id => $ids) {
                foreach ($ids as $id => $d) {
                    $tgl_masuk = $d->karyawan->tanggal_masuk ? date('d-m-y', strtotime($d->karyawan->tanggal_masuk)) : '';
                    $tgl_lahir = $d->karyawan->tanggal_lahir ? date('d-m-y', strtotime($d->karyawan->tanggal_lahir)) : '';
                    $html .= "<tr>";
                    $html .= "<td class=\"ac\">".$nomor."</td>";
                    $html .= "<td class=\"al\">".$this->afAbbreviateName($d->karyawan->nama)."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_masuk."</td>";
                    $html .= "<td class=\"ac\" style=\"white-space:nowrap;\">".$tgl_lahir."</td>";
                    $html .= "<td class=\"al\">".$d->karyawan->jabatan->nama."</td>";
                    $html .= "<td class=\"ac\">".$arrBulanPendek[$d->bulan]."'".$d->tahun."</td>";
                    $html .= "<td class=\"ar\">".number_format($d->jumlah, 0, ',', '.')."</td>";
                    $html .= "</tr>";
                    $total += $d->jumlah;
                    $nomor++;
                }
            }
            $html .= "<tr class=\"total-row\">";
            $html .= "<td colspan=\"6\" class=\"ac\">TOTAL KLAIM</td>";
            $html .= "<td class=\"ar\">".number_format($total, 0, ',', '.')."</td>";
            $html .= "</tr></table>";
        }

        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle('Rekap Medical '.$tahun);
        $mpdf->WriteHTML($html);
        $mpdf->Output('REKAP_MEDIKAL_'.substr($tahun, -2).'.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
