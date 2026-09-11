<?php

namespace App\Http\Controllers;

use App\Repositories\UangPhkRepository;
use App\Traits\AFhelper;
use Mpdf\Mpdf;

class PdfListPhkController extends Controller
{
    use AFhelper;

    protected $repoUangPhk;

    public function __construct(UangPhkRepository $repoUangPhk) {
        $this->repoUangPhk = $repoUangPhk;
    }

    public function listPHK($tahun_awal, $tahun_akhir) {
        $dataRepoUangPhk = $this->repoUangPhk->findAll(['tahun_awal' => $tahun_awal, 'tahun_akhir' => $tahun_akhir]);
        $dataUangPhk = array();
        $dataKaryawan = array();
        foreach ($dataRepoUangPhk as $dt) {
            $dataUangPhk[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id] = $dt;
            $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
        }
        ksort($dataUangPhk);

        $css = "<style>
            body { font-family: arial, sans-serif; font-size: 7pt; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 0.5pt solid #000; padding: 3px 4px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: center; }
            .ar { text-align: right; }
            .title { font-size: 16pt; color: #0000FF; text-decoration: underline; text-align: center; border: none; font-weight: bold; }
            .text-blue { color: #0000FF; }
            .text-red { color: #FF0000; }
            .bold { font-weight: bold; }
            .bg-yellow { background-color: #FFFF00; }
            .header th { font-weight: bold; text-align: center; background-color: #FFFF00; }
        </style>";

        $html = $css;
        $i = 0;

        foreach ($dataUangPhk as $tahun => $stafs) {
            if ($i > 0) {
                $html .= "<pagebreak />";
            }

            $html .= "<table>
            <thead>
                <tr>
                    <td colspan=\"17\" class=\"title ac\" style=\"border: none;\">LIST PHK PT.FRATEKINDO JAYA GEMILANG</td>
                </tr>
                <tr>
                    <td colspan=\"17\" class=\"title ac\" style=\"border: none;\">PERIODE : TAHUN ".$tahun."</td>
                </tr>
                <tr><td colspan=\"17\" class=\"no-border\"></td></tr>
                <tr class=\"header\">
                    <th rowspan=\"3\">NO</th>
                    <th rowspan=\"3\">NAMA KARYAWAN</th>
                    <th rowspan=\"3\">MASA KERJA</th>
                    <th rowspan=\"3\">TANGGAL LAHIR</th>
                    <th colspan=\"7\">K A L K U L A S I</th>
                    <th colspan=\"4\" class=\"text-red\">P O T O N G A N</th>
                    <th rowspan=\"3\">JUMLAH IDR</th>
                    <th rowspan=\"3\">KETERANGAN</th>
                </tr>
                <tr class=\"header\">
                    <th rowspan=\"2\">KOMPENSASI</th>
                    <th rowspan=\"2\">PESANGON</th>
                    <th rowspan=\"2\">MASA KERJA</th>
                    <th rowspan=\"2\">UANG PISAH</th>
                    <th colspan=\"2\">SISA CUTI</th>
                    <th rowspan=\"2\">LAIN-LAIN</th>
                    <th rowspan=\"2\" class=\"text-red\">KAS/CICILAN</th>
                    <th colspan=\"2\" class=\"text-red\">UNPAID LEAVE</th>
                    <th rowspan=\"2\" class=\"text-red\">LAIN-LAIN</th>
                </tr>
                <tr class=\"header\">
                    <th>HARI</th>
                    <th>IDR</th>
                    <th class=\"text-red\">HARI</th>
                    <th class=\"text-red\">IDR</th>
                </tr>
            </thead>
            <tbody>";

            $nomor = 1;
            
            $sum = array_fill_keys([
                "kompensasi", "pesangon", "masa_kerja", "uang_pisah", 
                "sisa_cuti_hari", "sisa_cuti_jumlah", "lain",
                "pot_kas", "pot_cuti_hari", "pot_cuti_jumlah", "pot_lain", "jumlah"
            ], 0);

            foreach ($stafs as $staf => $areas) {
                if($staf == 'N') {
                    $html .= "<tr><td></td><td class=\"al text-blue bold\" colspan=\"3\" style=\"text-align: left;\">NON STAF :</td>";
                    for ($c = 4; $c < 17; $c++) { $html .= "<td></td>"; }
                    $html .= "</tr>";
                }
                foreach ($areas as $area => $karyawan_ids) {
                    if($staf == 'Y') {
                        $html .= "<tr><td></td><td class=\"al text-blue bold\" colspan=\"3\" style=\"text-align: left;\">".$area." :</td>";
                        for ($c = 4; $c < 17; $c++) { $html .= "<td></td>"; }
                        $html .= "</tr>";
                    }
                    foreach ($karyawan_ids as $karyawan_id => $uangPhk) {
                        $dkaryawan = $dataKaryawan[$karyawan_id];
                        
                        $tgl_masuk = $dkaryawan->tanggal_masuk ? date('d-m-Y', strtotime($dkaryawan->tanggal_masuk)) : '';
                        $tgl_keluar = $dkaryawan->tanggal_keluar ? date('d-m-Y', strtotime($dkaryawan->tanggal_keluar)) : '';
                        $masa_kerja_str = $tgl_masuk . ' s/d ' . $tgl_keluar;
                        $tgl_lahir = $dkaryawan->tanggal_lahir ? date('d-m-y', strtotime($dkaryawan->tanggal_lahir)) : '';
                        
                        $valE = $uangPhk->kompensasi > 0 ? $uangPhk->kompensasi : 0;
                        $valF = $uangPhk->pesangon > 0 ? $uangPhk->pesangon : 0;
                        $valG = $uangPhk->masa_kerja > 0 ? $uangPhk->masa_kerja : 0;
                        $valH = $uangPhk->uang_pisah > 0 ? $uangPhk->uang_pisah : 0;
                        $valI = $uangPhk->sisa_cuti_hari > 0 ? $uangPhk->sisa_cuti_hari : 0;
                        $valJ = $uangPhk->sisa_cuti_jumlah > 0 ? $uangPhk->sisa_cuti_jumlah : 0;
                        $valK = $uangPhk->lain > 0 ? $uangPhk->lain : 0;
                        $valL = $uangPhk->pot_kas > 0 ? $uangPhk->pot_kas : 0;
                        $valM = $uangPhk->pot_cuti_hari > 0 ? $uangPhk->pot_cuti_hari : 0;
                        $valN = $uangPhk->pot_cuti_jumlah > 0 ? $uangPhk->pot_cuti_jumlah : 0;
                        $valO = $uangPhk->pot_lain > 0 ? $uangPhk->pot_lain : 0;
                        $valP = ($valE + $valF + $valG + $valH + $valJ + $valK) - ($valL + $valN + $valO);

                        $sum["kompensasi"] += $valE;
                        $sum["pesangon"] += $valF;
                        $sum["masa_kerja"] += $valG;
                        $sum["uang_pisah"] += $valH;
                        $sum["sisa_cuti_hari"] += $valI;
                        $sum["sisa_cuti_jumlah"] += $valJ;
                        $sum["lain"] += $valK;
                        $sum["pot_kas"] += $valL;
                        $sum["pot_cuti_hari"] += $valM;
                        $sum["pot_cuti_jumlah"] += $valN;
                        $sum["pot_lain"] += $valO;
                        $sum["jumlah"] += $valP;

                        $kets = [];
                        if (!empty($uangPhk->keterangan)) $kets[] = $uangPhk->keterangan;
                        if (!empty($uangPhk->ket_lain)) $kets[] = $uangPhk->ket_lain;
                        if (!empty($uangPhk->ket_pot_lain)) $kets[] = $uangPhk->ket_pot_lain;
                        $keterangan = implode('; ', $kets);

                        $html .= "<tr>";
                        $html .= "<td class=\"ac\">".$nomor."</td>";
                        $html .= "<td class=\"al\" style=\"text-align: left;\">".$this->afAbbreviateName($dkaryawan->nama)."</td>";
                        $html .= "<td class=\"ac\">".$masa_kerja_str."</td>";
                        $html .= "<td class=\"ac\">".$tgl_lahir."</td>";
                        $html .= "<td class=\"ar\">".($valE > 0 ? number_format($valE, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($valF > 0 ? number_format($valF, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($valG > 0 ? number_format($valG, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($valH > 0 ? number_format($valH, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ac\">".($valI > 0 ? $valI : "")."</td>";
                        $html .= "<td class=\"ar\">".($valJ > 0 ? number_format($valJ, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($valK > 0 ? number_format($valK, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($valL > 0 ? number_format($valL, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ac text-red\">".($valM > 0 ? $valM : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($valN > 0 ? number_format($valN, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($valO > 0 ? number_format($valO, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($valP != 0 ? number_format($valP, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"al\">".$keterangan."</td>";
                        $html .= "</tr>";
                        
                        $nomor++;
                    }
                }
            }

            // Total row
            $html .= "<tr class=\"bold text-blue\">";
            $html .= "<td colspan=\"4\" class=\"ac\">TOTAL</td>";
            $html .= "<td class=\"ar\">".($sum["kompensasi"] > 0 ? number_format($sum["kompensasi"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["pesangon"] > 0 ? number_format($sum["pesangon"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["masa_kerja"] > 0 ? number_format($sum["masa_kerja"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["uang_pisah"] > 0 ? number_format($sum["uang_pisah"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ac\">".($sum["sisa_cuti_hari"] > 0 ? $sum["sisa_cuti_hari"] : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["sisa_cuti_jumlah"] > 0 ? number_format($sum["sisa_cuti_jumlah"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["lain"] > 0 ? number_format($sum["lain"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_kas"] > 0 ? number_format($sum["pot_kas"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ac text-red\">".($sum["pot_cuti_hari"] > 0 ? $sum["pot_cuti_hari"] : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_cuti_jumlah"] > 0 ? number_format($sum["pot_cuti_jumlah"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_lain"] > 0 ? number_format($sum["pot_lain"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["jumlah"] != 0 ? number_format($sum["jumlah"], 0, ",", ".") : "")."</td>";
            $html .= "<td></td>";
            $html .= "</tr>";

            $html .= "</tbody></table>";
            $i++;
        }

        $mpdf = new Mpdf([
            "format" => "A4-L",
            "margin_left" => 5,
            "margin_right" => 5,
            "margin_top" => 10,
            "margin_bottom" => 10,
        ]);

        $mpdf->SetTitle("List PHK");
        $mpdf->WriteHTML($html);
        $mpdf->Output("LIST_PHK_".$tahun_awal."-".$tahun_akhir.".pdf", \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
