<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\UangPhkRepository;
use App\Repositories\KaryawanRepository;
use App\Traits\AFhelper;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PdfListPayrollController extends Controller
{
    use AFhelper;

    protected $repoHeader;
    protected $repoDetail;
    protected $repoOncall;
    protected $repoUangPhk;
    protected $repoPhk;
    protected $repoKaryawan;

    public function __construct(
        PayrollHeaderRepository $repoHeader,
        PayrollRepository $repoDetail,
        OncallCustomerRepository $repoOncall,
        UangPhkRepository $repoUangPhk,
        PayrollPhkRepository $repoPhk,
        KaryawanRepository $repoKaryawan
    ) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
        $this->repoUangPhk = $repoUangPhk;
        $this->repoPhk = $repoPhk;
        $this->repoKaryawan = $repoKaryawan;
    }

    public function listPayroll($tahun, $bulans = null) {
        $arrBulan = ["", "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"];

        $dataTahunLalu = $this->repoHeader->findAll(["tahun" => ($tahun-1), "bulan" => "12"]);
        if($dataTahunLalu->isEmpty()) {
            $headers[0]["overtime"] = 0;
            $headers[0]["overtime_fjg"] = 0;
            $headers[0]["overtime_cus"] = 0;
            $headers[0]["medical"] = 0;
        } else {
            $headers[0]["overtime"] = $dataTahunLalu[0]->overtime_fjg + $dataTahunLalu[0]->overtime_cus;
            $headers[0]["overtime_fjg"] = $dataTahunLalu[0]->overtime_fjg;
            $headers[0]["overtime_cus"] = $dataTahunLalu[0]->overtime_cus;
            $headers[0]["medical"] = $dataTahunLalu[0]->medical;
        }
        $dataHeaders = $this->repoHeader->findAll(["tahun" => $tahun]);
        if ($bulans != null) {
            $arrBulanReq = explode("-", $bulans);
            $dataHeaders = $dataHeaders->whereIn("bulan", $arrBulanReq);
        }

        foreach ($dataHeaders as $dh) {
            $headers[$dh->bulan]["overtime"] = $dh->overtime_fjg + $dh->overtime_cus;
            $headers[$dh->bulan]["overtime_fjg"] = $dh->overtime_fjg;
            $headers[$dh->bulan]["overtime_cus"] = $dh->overtime_cus;
            $headers[$dh->bulan]["medical"] = $dh->medical;
            $headers[$dh->bulan]["thr"] = $dh->thr;
            $headers[$dh->bulan]["bonus"] = $dh->bonus;
            $headers[$dh->bulan]["insentif"] = $dh->insentif;
            $headers[$dh->bulan]["telkomsel"] = $dh->telkomsel;
            $headers[$dh->bulan]["lain"] = $dh->lain;
            $headers[$dh->bulan]["pot_telepon"] = $dh->pot_telepon;
            $headers[$dh->bulan]["pot_bensin"] = $dh->pot_bensin;
            $headers[$dh->bulan]["pot_bpjs"] = $dh->pot_bpjs;
            $headers[$dh->bulan]["pot_cuti_jumlah"] = $dh->pot_cuti_jumlah;
            $headers[$dh->bulan]["pot_kompensasi_jumlah"] = $dh->pot_kompensasi_jumlah;
            $headers[$dh->bulan]["pot_lain"] = $dh->pot_lain;
        }

        $dataOncalls = $this->repoOncall->findAll(["tahun" => $tahun]);
        $oncallJumlahs = [];
        $dOncalls = [];
        foreach ($dataOncalls as $r) {
            $dOncalls[$r->bulan][$r->id] = $r;
            if(isset($oncallJumlahs[$r->bulan])) {
                $oncallJumlahs[$r->bulan] += $r->jumlah;
            } else {
                $oncallJumlahs[$r->bulan] = $r->jumlah;
            }
        }

        $css = "<style>
            body { font-family: arial, sans-serif; font-size: 7pt; font-weight: bold; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 0.5pt solid #000; padding: 2px 4px; vertical-align: middle; }
            .no-border { border: none !important; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .title { font-size: 14pt; color: #0000FF; text-decoration: underline; text-align: center; border: none; font-weight: bold; }
            .subtitle { font-size: 14pt; color: #0000FF; text-decoration: underline; text-align: center; border: none; font-weight: bold; }
            .text-blue { color: #0000FF; }
            .text-red { color: #FF0000; }
            .bold { font-weight: bold; }
            .header th { font-weight: bold; text-align: center; }
        </style>";

        $html = $css;
        $i = 0;

        foreach ($dataHeaders as $dh) {
            $dataDetails = $this->repoDetail->findAll(["header_id" => $dh->id]);
            $details = [];
            foreach ($dataDetails as $dt) {
                $details[$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->id] = $dt;
            }

            if (empty($details)) continue;

            $kolTun = 2;
            $kolPot = 3;
            $adaThr = false;
            $adaBonus = false;
            $adaInsentif = false;
            $adaTelkomsel = false;
            $adaLain = false;
            $adaPotTelepon = false;
            $adaPotBensin = false;
            $adaPotBpjs = false;
            $adaPotCuti = false;
            $adaPotKompensasi = false;
            $adaPotLain = false;

            if(isset($headers[$dh->bulan])) {
                if($headers[$dh->bulan]["thr"] > 0) { $adaThr = true; $kolTun++; }
                if($headers[$dh->bulan]["bonus"] > 0) { $adaBonus = true; $kolTun++; }
                if($headers[$dh->bulan]["insentif"] > 0) { $adaInsentif = true; $kolTun++; }
                if($headers[$dh->bulan]["telkomsel"] > 0) { $adaTelkomsel = true; $kolTun++; }
                if($headers[$dh->bulan]["lain"] > 0) { $adaLain = true; $kolTun++; }
                if($headers[$dh->bulan]["pot_telepon"] > 0) { $adaPotTelepon = true; $kolPot++; }
                if($headers[$dh->bulan]["pot_bensin"] > 0) { $adaPotBensin = true; $kolPot++; }
                if($headers[$dh->bulan]["pot_bpjs"] > 0) { $adaPotBpjs = true; $kolPot++; }
                if($headers[$dh->bulan]["pot_cuti_jumlah"] > 0) { $adaPotCuti = true; $kolPot++; }
                if($headers[$dh->bulan]["pot_kompensasi_jumlah"] > 0) { $adaPotKompensasi = true; $kolPot++; }
                if($headers[$dh->bulan]["pot_lain"] > 0) { $adaPotLain = true; $kolPot++; }
            }
            $kolTotal = 11 + $kolTun + $kolPot;

            if ($i > 0) {
                $html .= "<pagebreak />";
            }

            $html .= "<table>
            <thead>
                <tr>
                    <td colspan=\"".$kolTotal."\" class=\"title\">PAYROLL ".$arrBulan[$dh->bulan]." ".$dh->tahun."</td>
                </tr>
                <tr><td colspan=\"".$kolTotal."\" class=\"subtitle\">PT.FRATEKINDO JAYA GEMILANG</td></tr>
                <tr><td colspan=\"".$kolTotal."\" class=\"no-border\"></td></tr>
                <tr class=\"header\">
                    <th rowspan=\"3\">NO</th>
                    <th rowspan=\"3\">NAMA KARYAWAN</th>
                    <th rowspan=\"3\">JABATAN</th>
                    <th rowspan=\"3\">MASA KERJA</th>
                    <th rowspan=\"3\">GAJI / UPAH IDR</th>
                    <th colspan=\"3\">U/MAKAN & TRANSPORTASI</th>
                    <th colspan=\"".($kolTun + 1)."\">TUNJANGAN LAIN</th>
                    <th colspan=\"".($kolPot + 1)."\" class=\"text-red\">POTONGAN</th>
                    <th rowspan=\"3\" class=\"text-blue\">TOTAL DITERIMA IDR</th>
                    <th rowspan=\"3\" class=\"text-red\">KETERANGAN</th>
                </tr>
                <tr class=\"header\">
                    <th rowspan=\"2\">HR</th>
                    <th rowspan=\"2\">@ HARI IDR</th>
                    <th rowspan=\"2\">JUMLAH IDR</th>
                    <th colspan=\"2\">OVERTIME</th>
                    <th rowspan=\"2\">MEDICAL IDR</th>";
            if($adaThr) $html .= "<th rowspan=\"2\">THR IDR</th>";
            if($adaBonus) $html .= "<th rowspan=\"2\">BONUS IDR</th>";
            if($adaInsentif) $html .= "<th rowspan=\"2\">INSENTIF IDR</th>";
            if($adaTelkomsel) $html .= "<th rowspan=\"2\">TELKOMSEL IDR</th>";
            if($adaLain) $html .= "<th rowspan=\"2\">LAIN-LAIN IDR</th>";
            $html .= "<th colspan=\"2\" class=\"text-red\">25%</th>";
            if($adaPotTelepon) $html .= "<th rowspan=\"2\" class=\"text-red\">TELP. IDR</th>";
            if($adaPotBensin) $html .= "<th rowspan=\"2\" class=\"text-red\">BENSIN IDR</th>";
            $html .= "<th colspan=\"2\" class=\"text-red\">PINJAMAN</th>";
            if($adaPotBpjs) $html .= "<th rowspan=\"2\" class=\"text-red\">BPJS (KIS) IDR</th>";
            if($adaPotCuti) $html .= "<th rowspan=\"2\" class=\"text-red\">UNPAID LEAVE</th>";
            if($adaPotKompensasi) $html .= "<th rowspan=\"2\" class=\"text-red\">COMPENSATED ABS.</th>";
            if($adaPotLain) $html .= "<th rowspan=\"2\" class=\"text-red\">LAIN-LAIN IDR</th>";
            $html .= "
                </tr>
                <tr class=\"header\">
                    <th>FJG</th>
                    <th>CUS</th>
                    <th class=\"text-red\">HR</th>
                    <th class=\"text-red\">JUMLAH IDR</th>
                    <th class=\"text-red\">KAS</th>
                    <th class=\"text-red\">CICILAN</th>
                </tr>
            </thead>
            <tbody>";

            $nomor = 1;
            $sum = array_fill_keys([
                "gaji", "hari_makan", "uang_makan_harian", "uang_makan_jumlah",
                "overtime_fjg", "overtime_cus", "medical", "thr", "bonus", "insentif", "telkomsel", "lain",
                "pot_25_hari", "pot_25_jumlah", "pot_telepon", "pot_bensin", "pot_kas", "pot_cicilan",
                "pot_bpjs", "pot_cuti_jumlah", "pot_kompensasi_jumlah", "pot_lain", "total_diterima"
            ], 0);

            foreach ($details as $staf => $areas) {
                if($staf == "N") {
                    $html .= "<tr><td></td><td colspan=\"3\" class=\"al text-blue bold\">NON STAF :</td>";
                    for ($c = 4; $c <= $kolTotal; $c++) { $html .= "<td></td>"; }
                    $html .= "</tr>";
                }
                foreach ($areas as $area => $ids) {
                    if($staf == "Y") {
                        $html .= "<tr><td></td><td colspan=\"3\" class=\"al text-blue bold\">".$area." :</td>";
                        for ($c = 4; $c <= $kolTotal; $c++) { $html .= "<td></td>"; }
                        $html .= "</tr>";
                    }
                    foreach ($ids as $id => $d) {
                        $sum["gaji"] += ($d->gaji + $d->kenaikan_gaji);
                        $sum["hari_makan"] += $d->hari_makan;
                        $sum["uang_makan_harian"] += $d->uang_makan_harian;
                        $sum["uang_makan_jumlah"] += $d->uang_makan_jumlah;
                        $sum["overtime_fjg"] += $d->overtime_fjg;
                        $sum["overtime_cus"] += $d->overtime_cus;
                        $sum["medical"] += $d->medical;
                        $sum["thr"] += $d->thr;
                        $sum["bonus"] += $d->bonus;
                        $sum["insentif"] += $d->insentif;
                        $sum["telkomsel"] += $d->telkomsel;
                        $sum["lain"] += $d->lain;
                        $sum["pot_25_hari"] += $d->pot_25_hari;
                        $sum["pot_25_jumlah"] += $d->pot_25_jumlah;
                        $sum["pot_telepon"] += $d->pot_telepon;
                        $sum["pot_bensin"] += $d->pot_bensin;
                        $sum["pot_kas"] += $d->pot_kas;
                        $sum["pot_cicilan"] += $d->pot_cicilan;
                        $sum["pot_bpjs"] += $d->pot_bpjs;
                        $sum["pot_cuti_jumlah"] += $d->pot_cuti_jumlah;
                        $sum["pot_kompensasi_jumlah"] += $d->pot_kompensasi_jumlah;
                        $sum["pot_lain"] += $d->pot_lain;
                        $sum["total_diterima"] += $d->total_diterima;

                        $html .= "<tr>";
                        $html .= "<td class=\"ac\">".$nomor."</td>";
                        $html .= "<td class=\"al\">".$this->afAbbreviateName($d->karyawan->nama)."</td>";
                        $html .= "<td class=\"al\">".$d->karyawan->jabatan->nama."</td>";
                        $html .= "<td class=\"ac\">".date("d-m-Y", strtotime($d->karyawan->tanggal_masuk))."</td>";
                        $html .= "<td class=\"ar\">".(($d->gaji + $d->kenaikan_gaji) > 0 ? number_format($d->gaji + $d->kenaikan_gaji, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ac\">".($d->hari_makan > 0 ? $d->hari_makan : "")."</td>";
                        $html .= "<td class=\"ar\">".($d->uang_makan_harian > 0 ? number_format($d->uang_makan_harian, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($d->uang_makan_jumlah > 0 ? number_format($d->uang_makan_jumlah, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($d->overtime_fjg > 0 ? number_format($d->overtime_fjg, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($d->overtime_cus > 0 ? number_format($d->overtime_cus, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar\">".($d->medical != 0 ? number_format($d->medical, 0, ",", ".") : "")."</td>";
                        if($adaThr) $html .= "<td class=\"ar\">".($d->thr > 0 ? number_format($d->thr, 0, ",", ".") : "")."</td>";
                        if($adaBonus) $html .= "<td class=\"ar\">".($d->bonus > 0 ? number_format($d->bonus, 0, ",", ".") : "")."</td>";
                        if($adaInsentif) $html .= "<td class=\"ar\">".($d->insentif > 0 ? number_format($d->insentif, 0, ",", ".") : "")."</td>";
                        if($adaTelkomsel) $html .= "<td class=\"ar\">".($d->telkomsel > 0 ? number_format($d->telkomsel, 0, ",", ".") : "")."</td>";
                        if($adaLain) $html .= "<td class=\"ar\">".($d->lain > 0 ? number_format($d->lain, 0, ",", ".") : "")."</td>";

                        $html .= "<td class=\"ac text-red\">".($d->pot_25_hari > 0 ? $d->pot_25_hari : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_25_jumlah > 0 ? number_format($d->pot_25_jumlah, 0, ",", ".") : "")."</td>";
                        if($adaPotTelepon) $html .= "<td class=\"ar text-red\">".($d->pot_telepon > 0 ? number_format($d->pot_telepon, 0, ",", ".") : "")."</td>";
                        if($adaPotBensin) $html .= "<td class=\"ar text-red\">".($d->pot_bensin > 0 ? number_format($d->pot_bensin, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_kas > 0 ? number_format($d->pot_kas, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"ar text-red\">".($d->pot_cicilan > 0 ? number_format($d->pot_cicilan, 0, ",", ".") : "")."</td>";
                        if($adaPotBpjs) $html .= "<td class=\"ar text-red\">".($d->pot_bpjs > 0 ? number_format($d->pot_bpjs, 0, ",", ".") : "")."</td>";
                        if($adaPotCuti) $html .= "<td class=\"ar text-red\">".($d->pot_cuti_jumlah > 0 ? number_format($d->pot_cuti_jumlah, 0, ",", ".") : "")."</td>";
                        if($adaPotKompensasi) $html .= "<td class=\"ar text-red\">".($d->pot_kompensasi_jumlah > 0 ? number_format($d->pot_kompensasi_jumlah, 0, ",", ".") : "")."</td>";
                        if($adaPotLain) $html .= "<td class=\"ar text-red\">".($d->pot_lain > 0 ? number_format($d->pot_lain, 0, ",", ".") : "")."</td>";

                        $html .= "<td class=\"ar text-blue\">".($d->total_diterima > 0 ? number_format($d->total_diterima, 0, ",", ".") : "")."</td>";
                        $html .= "<td class=\"al text-red\">".$d->keterangan."</td>";
                        $html .= "</tr>";
                        $nomor++;
                    }
                }
            }

            // Totals Row
            $html .= "<tr class=\"bold\">";
            $html .= "<td colspan=\"4\" class=\"ac\">TOTAL PAYROLL</td>";
            $html .= "<td class=\"ar\">".($sum["gaji"] > 0 ? number_format($sum["gaji"], 0, ",", ".") : "")."</td>";
            $html .= "<td></td>";
            $html .= "<td></td>";
            $html .= "<td class=\"ar\">".($sum["uang_makan_jumlah"] > 0 ? number_format($sum["uang_makan_jumlah"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["overtime_fjg"] > 0 ? number_format($sum["overtime_fjg"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["overtime_cus"] > 0 ? number_format($sum["overtime_cus"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar\">".($sum["medical"] != 0 ? number_format($sum["medical"], 0, ",", ".") : "")."</td>";
            if($adaThr) $html .= "<td class=\"ar\">".($sum["thr"] > 0 ? number_format($sum["thr"], 0, ",", ".") : "")."</td>";
            if($adaBonus) $html .= "<td class=\"ar\">".($sum["bonus"] > 0 ? number_format($sum["bonus"], 0, ",", ".") : "")."</td>";
            if($adaInsentif) $html .= "<td class=\"ar\">".($sum["insentif"] > 0 ? number_format($sum["insentif"], 0, ",", ".") : "")."</td>";
            if($adaTelkomsel) $html .= "<td class=\"ar\">".($sum["telkomsel"] > 0 ? number_format($sum["telkomsel"], 0, ",", ".") : "")."</td>";
            if($adaLain) $html .= "<td class=\"ar\">".($sum["lain"] > 0 ? number_format($sum["lain"], 0, ",", ".") : "")."</td>";

            $html .= "<td class=\"ac text-red\">".($sum["pot_25_hari"] > 0 ? $sum["pot_25_hari"] : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_25_jumlah"] > 0 ? number_format($sum["pot_25_jumlah"], 0, ",", ".") : "")."</td>";
            if($adaPotTelepon) $html .= "<td class=\"ar text-red\">".($sum["pot_telepon"] > 0 ? number_format($sum["pot_telepon"], 0, ",", ".") : "")."</td>";
            if($adaPotBensin) $html .= "<td class=\"ar text-red\">".($sum["pot_bensin"] > 0 ? number_format($sum["pot_bensin"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_kas"] > 0 ? number_format($sum["pot_kas"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"ar text-red\">".($sum["pot_cicilan"] > 0 ? number_format($sum["pot_cicilan"], 0, ",", ".") : "")."</td>";
            if($adaPotBpjs) $html .= "<td class=\"ar text-red\">".($sum["pot_bpjs"] > 0 ? number_format($sum["pot_bpjs"], 0, ",", ".") : "")."</td>";
            if($adaPotCuti) $html .= "<td class=\"ar text-red\">".($sum["pot_cuti_jumlah"] > 0 ? number_format($sum["pot_cuti_jumlah"], 0, ",", ".") : "")."</td>";
            if($adaPotKompensasi) $html .= "<td class=\"ar text-red\">".($sum["pot_kompensasi_jumlah"] > 0 ? number_format($sum["pot_kompensasi_jumlah"], 0, ",", ".") : "")."</td>";
            if($adaPotLain) $html .= "<td class=\"ar text-red\">".($sum["pot_lain"] > 0 ? number_format($sum["pot_lain"], 0, ",", ".") : "")."</td>";

            $html .= "<td class=\"ar text-blue\">".($sum["total_diterima"] > 0 ? number_format($sum["total_diterima"], 0, ",", ".") : "")."</td>";
            $html .= "<td class=\"text-red\"></td>";
            $html .= "</tr>";

            $html .= "</tbody></table>";
            $html .= "<br>"; // Spacer

            // Bottom sections
            $ttgl = explode("-", $dh->tanggal_awal);
            $ttgm = explode("-", $dh->tanggal_akhir);
            $str_aw = intval($ttgl[2]) . " " . $arrBulan[intval($ttgl[1])] . "'" . substr($ttgl[0], -2);
            $str_ak = intval($ttgm[2]) . " " . $arrBulan[intval($ttgm[1])] . "'" . substr($ttgm[0], -2);

            // 1. Makan & Transportasi (Top Left)
            $html .= "<table style=\"border: 1px solid #000; width: 33%;\">
                        <tr><td class=\"text-blue\" style=\"border: none;\">U/MAKAN & TRANSPORTASI</td><td class=\"text-blue\" colspan=\"3\" style=\"border: none;\">'= ".$str_aw." - ".$str_ak."</td></tr>";

            foreach ($dataDetails as $dt) {
                if ($dt->makan_tgl_awal != null && $dt->makan_tgl_akhir != null) {
                    $namaDepan = explode(" ", $dt->karyawan->nama)[0];
                    $c_aw = explode("-", $dt->makan_tgl_awal);
                    $c_ak = explode("-", $dt->makan_tgl_akhir);
                    $cstr_aw = intval($c_aw[2]) . " " . $arrBulan[intval($c_aw[1])] . "'" . substr($c_aw[0], -2);
                    $cstr_ak = intval($c_ak[2]) . " " . $arrBulan[intval($c_ak[1])] . "'" . substr($c_ak[0], -2);
                    $html .= "<tr><td class=\"text-blue\" style=\"border: none;\">".$namaDepan." (".$dt->karyawan->area->kode.")</td><td class=\"text-blue\" colspan=\"3\" style=\"border: none;\">'= ".$cstr_aw." - ".$cstr_ak."</td></tr>";
                }
            }
            $html .= "</table><br>";

            // Prepare Data for POTONGAN and OVERTIME
            $potRows = [];
            foreach ($dataDetails as $dt) {
                if ($dt->pot_cuti_jumlah > 0) {
                    $namaDepan = explode(" ", $dt->karyawan->nama)[0];
                    $potRows[] = [
                        $namaDepan." (".$dt->karyawan->area->kode.")",
                        $dt->pot_cuti_keterangan,
                        "'= ".$dt->pot_cuti_hari." HR"
                    ];
                }
            }

            $nowMed = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]["medical"] : 0;
            $beforeMed = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]["medical"] : 0;
            $statusMed = ""; $persenMed = 0;
            if($nowMed == 0 && $beforeMed == 0) {
                $statusMed = "TETAP"; $persenMed = 0;
            } else if($beforeMed == 0) {
                $statusMed = "NAIK"; $persenMed = 100;
            } else {
                $persenMed = ($nowMed - $beforeMed) / $beforeMed * 100;
                $statusMed = $persenMed > 0 ? "NAIK" : "TURUN";
            }

            $oncallsHTML = [];
            if(isset($dOncalls[$dh->bulan])) {
                foreach ($dOncalls[$dh->bulan] as $r) {
                    $oncallsHTML[] = ["name" => $r->customer->nama, "jumlah" => $r->jumlah];
                }
            }

            $medRows = [
                ["MEDICAL", $statusMed, number_format(abs($persenMed),2,",",".")."%"],
                ["", "", ""],
                ["OVERTIME :", "", ""],
            ];

            $nowFjg = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]["overtime_fjg"] : 0;
            $beforeFjg = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]["overtime_fjg"] : 0;
            if($nowFjg == 0 && $beforeFjg == 0) { $statusFjg = "TETAP"; $persenFjg = 0; }
            else if($beforeFjg == 0) { $statusFjg = $nowFjg > 0 ? "NAIK" : "TURUN"; $persenFjg = 100; }
            else {
                if($nowFjg == 0) { $statusFjg = "TURUN"; $persenFjg = 100; }
                else if($nowFjg > 0) { $persenFjg = ($nowFjg - $beforeFjg) / $beforeFjg * 100; $statusFjg = $persenFjg > 0 ? "NAIK" : "TURUN"; }
                else { $statusFjg = "TERCOVER"; $persenFjg = 0; }
            }
            $medRows[] = ["- FRATEKINDO", $statusFjg, number_format(abs($persenFjg),2,",",".")."%"];

            $nowCus = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]["overtime_cus"] : 0;
            $beforeCus = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]["overtime_cus"] : 0;
            if(isset($oncallJumlahs[$dh->bulan])) $nowCus -= $oncallJumlahs[$dh->bulan];
            if(isset($oncallJumlahs[($dh->bulan-1)])) $beforeCus -= $oncallJumlahs[($dh->bulan-1)];

            if($nowCus == 0 && $beforeCus == 0) { $statusCus = "TETAP"; $persenCus = 0; }
            else if($beforeCus == 0) { $statusCus = $nowCus > 0 ? "NAIK" : "TURUN"; $persenCus = 100; }
            else {
                if($nowCus == 0) { $statusCus = "TURUN"; $persenCus = 100; }
                else if($nowCus > 0) { $persenCus = ($nowCus - $beforeCus) / $beforeCus * 100; $statusCus = $persenCus > 0 ? "NAIK" : "TURUN"; }
                else { $statusCus = "TERCOVER"; $persenCus = 0; }
            }
            $statusOT = $statusCus;
            $medRows[] = ["- CUSTOMER", $statusCus, number_format(abs($persenCus),2,",",".")."%"];

            $otDataCount = max(count($oncallsHTML), count($medRows));

            $targetPotDataCount = $otDataCount + 1; // because 1+target = $otDataCount+2
            $targetOtDataCount = max($otDataCount, count($potRows) - 1);
            if ($targetOtDataCount > $otDataCount) {
                $targetPotDataCount = count($potRows);
            } else {
                $targetPotDataCount = $otDataCount + 1;
            }

            $html .= "<table class=\"no-border\" style=\"width: 100%;\"><tr>
                <td style=\"width: 33%; vertical-align: top;\" class=\"no-border\">
                    <!-- POTONGAN Table -->
                    <table style=\"border: 1px solid #000; width: 100%;\">
                        <tr><td class=\"ac text-red\" colspan=\"4\" style=\"border-bottom: 1px solid #000;\">POTONGAN/KOMPENSASI IJIN /UNPAID LEAVE</td></tr>";
            for ($k = 0; $k < $targetPotDataCount; $k++) {
                if ($k < count($potRows)) {
                    $html .= "<tr><td style=\"width: 40%;\">".$potRows[$k][0]."</td><td colspan=\"2\" style=\"width: 45%;\">".$potRows[$k][1]."</td><td style=\"width: 15%;\">".$potRows[$k][2]."</td></tr>";
                } else {
                    $html .= "<tr><td style=\"width: 40%;\"><br></td><td colspan=\"2\" style=\"width: 45%;\"></td><td style=\"width: 15%;\"></td></tr>";
                }
            }
            $html .= "      </table>
                </td>
                <td style=\"width: 2%;\" class=\"no-border\"></td>
                <td style=\"width: 38%; vertical-align: top;\" class=\"no-border\">
                    <!-- OVERTIME Table -->
                    <table style=\"border: 1px solid #000; width: 100%;\">
                        <tr>
                            <td class=\"ac\" colspan=\"4\" style=\"border-bottom: 1px solid #000; border-right: 1px solid #000;\">OVERTIME & ON CALL CUSTOMERS :</td>
                            <td class=\"ac\" colspan=\"3\" style=\"border-bottom: 1px solid #000;\">OVERTIME & MEDICAL :</td>
                        </tr>";

            $sumOncalls = 0;
            for ($k = 0; $k < $targetOtDataCount; $k++) {
                $html .= "<tr>";
                if ($k < count($oncallsHTML)) {
                    $html .= "<td colspan=\"3\" style=\"width: 40%;\">".$oncallsHTML[$k]["name"]."</td>";
                    $html .= "<td class=\"ar\" style=\"width: 15%;\">".number_format($oncallsHTML[$k]["jumlah"],0,",",".")."</td>";
                    $sumOncalls += $oncallsHTML[$k]["jumlah"];
                } else {
                    $html .= "<td colspan=\"3\" style=\"width: 40%;\"><br></td>";
                    $html .= "<td style=\"width: 15%;\"></td>";
                }

                if ($k < count($medRows)) {
                    $html .= "<td style=\"width: 20%;\">".$medRows[$k][0]."</td>";
                    $html .= "<td style=\"width: 10%;\">".$medRows[$k][1]."</td>";
                    $html .= "<td class=\"ar\" style=\"width: 15%;\">".$medRows[$k][2]."</td>";
                } else {
                    $html .= "<td style=\"width: 20%;\"></td><td style=\"width: 10%;\"></td><td style=\"width: 15%;\"></td>";
                }
                $html .= "</tr>";
            }

            // Total Row
            $html .= "<tr>
                <td colspan=\"3\" style=\"border: 1px solid #000;\">JUMLAH</td>
                <td class=\"ar\" style=\"border: 1px solid #000;\">".number_format($sumOncalls,0,",",".")."</td>";

            if ($statusOT == "TERCOVER") {
                $html .= "<td colspan=\"3\" style=\"border: 1px solid #000;\">Tercover On Call Customer</td>";
            } else {
                $html .= "<td colspan=\"3\" style=\"border: 1px solid #000;\"></td>";
            }
            $html .= "</tr>";
            $html .= "      </table>
                </td>
                <td style=\"width: 27%; vertical-align: top; text-align: center;\" class=\"no-border\">
                    <!-- Signatures -->
                    <table class=\"no-border\" style=\"width: 100%; text-align: center;\">
                        <tr>
                            <td class=\"no-border\">Diajukan Oleh :</td>
                            <td class=\"no-border\">Disetujui Oleh : </td>
                            <td class=\"no-border\">Diterima Oleh : </td>
                        </tr>
                        <tr><td colspan=\"3\" class=\"no-border\" style=\"height: 50px;\"></td></tr>
                        <tr>
                            <td class=\"no-border\" style=\"text-decoration: underline;\">Sri Erni.S</td>
                            <td class=\"no-border\" style=\"text-decoration: underline;\">Alain Pierre Mignon</td>
                            <td class=\"no-border\" style=\"text-decoration: underline;\">Harti Susilowati</td>
                        </tr>
                        <tr>
                            <td class=\"no-border\" style=\"font-weight: normal;\">Head of HRD</td>
                            <td class=\"no-border\" style=\"font-weight: normal;\">Presiden Direktur</td>
                            <td class=\"no-border\" style=\"font-weight: normal;\">Finance</td>
                        </tr>
                    </table>
                </td>
            </tr></table>";

            $i++;
        }

        $mpdf = new Mpdf([
            "format" => "A3-L",
            "margin_left" => 5,
            "margin_right" => 5,
            "margin_top" => 10,
            "margin_bottom" => 10,
        ]);

        $mpdf->SetTitle("List Payroll");
        $mpdf->WriteHTML($html);
        $mpdf->Output("LIST_PAYROLL_".substr($tahun, -2).".pdf", \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
