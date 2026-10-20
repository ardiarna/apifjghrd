<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Phk;
use App\Models\UangPhk;
use App\Models\PayrollPhk;
use App\Models\Upah;
use App\Models\Note;
use Mpdf\Mpdf;

class PdfSlipPhkController extends Controller
{
    private function fmt($val): string
    {
        return number_format((float)$val, 0, ',', '.');
    }

    private function imgB64(string $path, string $mime): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function cetak($karyawan_id)
    {
        $karyawan = Karyawan::with(['phk.uangPhk', 'phk.payrollPhk', 'divisi', 'jabatan', 'area', 'upah'])->find($karyawan_id);
        if (!$karyawan) {
            return response()->json(['status' => false, 'message' => 'Data Karyawan tidak ditemukan']);
        }

        if (!$karyawan->phk_id) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak memiliki data PHK aktif']);
        }

        $phk = Phk::with(['uangPhk', 'payrollPhk'])->find($karyawan->phk_id);
        $uangPhk = ($phk && $phk->uangPhk) ? $phk->uangPhk : null;

        $payrollPhk = ($phk && $phk->payrollPhk)
            ? $phk->payrollPhk
            : PayrollPhk::where('karyawan_id', $karyawan_id)
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->first();

        if (!$uangPhk) {
            return response()->json(['status' => false, 'message' => 'Data Uang PHK tidak ditemukan']);
        }

        $upah = $karyawan->upah ?: Upah::where('karyawan_id', $karyawan_id)->first();
        $gajiTunjangan = 0;
        if ($upah) {
            $gajiTunjangan = (strtoupper((string)$upah->makan_harian) === 'N')
                ? ($upah->gaji + $upah->uang_makan)
                : $upah->gaji;
        }

        $rawTglMasuk = ($phk && $phk->tanggal_awal) ? $phk->tanggal_awal : $karyawan->tanggal_masuk;
        $rawTglKeluar = ($phk && $phk->tanggal_akhir) ? $phk->tanggal_akhir : ($karyawan->tanggal_keluar ?: date('Y-m-d'));

        $tglMasuk = $rawTglMasuk ? date('d-m-Y', strtotime($rawTglMasuk)) : '';
        $tglKeluar = $rawTglKeluar ? date('d-m-Y', strtotime($rawTglKeluar)) : '';

        $masaKerjaStr = '0 Bulan';
        if ($rawTglMasuk && $rawTglKeluar) {
            $d1 = new \DateTime($rawTglMasuk);
            $d2 = new \DateTime($rawTglKeluar);
            if ($d1 <= $d2) {
                $diff = $d1->diff($d2);
                $y = $diff->y;
                $m = $diff->m;
                if ($diff->d >= 15) {
                    $m++;
                    if ($m >= 12) {
                        $y++;
                        $m -= 12;
                    }
                }
                $parts = [];
                if ($y > 0) $parts[] = $y . ' Tahun';
                if ($m > 0) $parts[] = $m . ' Bulan';
                $masaKerjaStr = count($parts) > 0 ? implode(' ', $parts) : '0 Bulan';
            }
        }

        $arrBulan = array("", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des");
        $bulan = ($payrollPhk && $payrollPhk->bulan) ? (int)$payrollPhk->bulan : null;
        $tahun = ($payrollPhk && $payrollPhk->tahun) ? (int)$payrollPhk->tahun : null;

        if (!$bulan || !$tahun) {
            $rawDate = ($phk && $phk->tanggal_akhir) ? $phk->tanggal_akhir : ($karyawan->tanggal_keluar ?: date('Y-m-d'));
            $bulan = (int)date('m', strtotime($rawDate));
            $tahun = (int)date('Y', strtotime($rawDate));
        }

        $prevBulan = $bulan == 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan == 1 ? $tahun - 1 : $tahun;

        if ($payrollPhk && $payrollPhk->tgl_gaji_awal && $payrollPhk->tgl_gaji_akhir) {
            $tg1 = explode('-', date('Y-m-d', strtotime($payrollPhk->tgl_gaji_awal)));
            $tg2 = explode('-', date('Y-m-d', strtotime($payrollPhk->tgl_gaji_akhir)));
            $labelGaji = "Gaji Pokok Per : " . $tg1[2] . " " . $arrBulan[intval($tg1[1])] . "'" . substr($tg1[0], -2) . " - " . $tg2[2] . " " . $arrBulan[intval($tg2[1])] . "'" . substr($tg2[0], -2);
        } else {
            $labelGaji = "Gaji Pokok Per : 26 " . $arrBulan[$prevBulan] . "'" . substr((string)$prevTahun, -2) . " - 25 " . $arrBulan[$bulan] . "'" . substr((string)$tahun, -2);
        }

        if ($payrollPhk && $payrollPhk->tanggal_awal && $payrollPhk->tanggal_akhir) {
            $ttgl = explode('-', date('Y-m-d', strtotime($payrollPhk->tanggal_awal)));
            $ttgm = explode('-', date('Y-m-d', strtotime($payrollPhk->tanggal_akhir)));
            $labelMakan = '(Per: ' . $ttgl[2] . ' ' . $arrBulan[intval($ttgl[1])] . "'" . substr($ttgl[0], -2) . ' s/d ' . $ttgm[2] . ' ' . $arrBulan[intval($ttgm[1])] . "'" . substr($ttgm[0], -2) . ')';
        } else {
            $labelMakan = "(Per: 18 " . $arrBulan[$prevBulan] . "'" . substr((string)$prevTahun, -2) . " s/d 17 " . $arrBulan[$bulan] . "'" . substr((string)$tahun, -2) . ")";
        }

        // Additional Items Left
        $itemsLeft = [];
        if ($uangPhk && $uangPhk->masa_kerja > 0) {
            $itemsLeft[] = [
                'label' => 'Uang Penghargaan Masa Kerja',
                'nominal' => $uangPhk->masa_kerja,
                'subtext' => null,
            ];
        }
        if ($uangPhk && $uangPhk->kompensasi > 0) {
            $itemsLeft[] = [
                'label' => 'Uang Kompensasi',
                'nominal' => $uangPhk->kompensasi,
                'subtext' => null,
            ];
        }
        if ($uangPhk && $uangPhk->uang_pisah > 0) {
            $itemsLeft[] = [
                'label' => 'Uang Pisah',
                'nominal' => $uangPhk->uang_pisah,
                'subtext' => null,
            ];
        }
        $nominalPenggantianHak = ($uangPhk && $uangPhk->sisa_cuti_jumlah > 0)
            ? $uangPhk->sisa_cuti_jumlah
            : (($uangPhk && $uangPhk->penggantian_hak > 0) ? $uangPhk->penggantian_hak : 0);
        if ($nominalPenggantianHak > 0) {
            $subtextCuti = ($uangPhk && $uangPhk->sisa_cuti_hari > 0) ? '(Sisa Cuti = ' . $uangPhk->sisa_cuti_hari . ' hari)' : null;
            $itemsLeft[] = [
                'label' => 'Uang Penggantian Hak',
                'nominal' => $nominalPenggantianHak,
                'subtext' => $subtextCuti,
            ];
        }

        $valOvertime = $payrollPhk ? ((float)$payrollPhk->overtime_fjg + (float)$payrollPhk->overtime_cus) : 0;
        if ($valOvertime > 0) {
            $itemsLeft[] = [
                'label' => 'Lembur/Overtime',
                'nominal' => $valOvertime,
                'subtext' => null,
            ];
        }

        $valMedical = $payrollPhk ? (float)$payrollPhk->medical : 0;
        if ($valMedical > 0) {
            $itemsLeft[] = [
                'label' => 'Reimbursement Medical',
                'nominal' => $valMedical,
                'subtext' => null,
            ];
        }

        $valThr = $payrollPhk ? (float)$payrollPhk->thr : 0;
        if ($valThr > 0) {
            $itemsLeft[] = [
                'label' => 'Tunjangan Hari Raya',
                'nominal' => $valThr,
                'subtext' => null,
            ];
        }

        // Bonus & Insentif
        $valBonus = $payrollPhk ? (float)$payrollPhk->bonus : 0;
        $valInsentif = $payrollPhk ? (float)$payrollPhk->insentif : 0;
        if ($valBonus > 0 && $valInsentif > 0) {
            $itemsLeft[] = [
                'label' => 'Bonus & Insentif',
                'nominal' => $valBonus + $valInsentif,
                'subtext' => null,
            ];
        } elseif ($valBonus > 0) {
            $itemsLeft[] = [
                'label' => 'Bonus',
                'nominal' => $valBonus,
                'subtext' => null,
            ];
        } elseif ($valInsentif > 0) {
            $itemsLeft[] = [
                'label' => 'Insentif',
                'nominal' => $valInsentif,
                'subtext' => null,
            ];
        }

        // Padukan Lain-Lain ($ketLain) dengan Telkomsel
        $valTelkomsel = $payrollPhk ? (float)$payrollPhk->telkomsel : 0;
        $valLain = ($uangPhk && (float)$uangPhk->lain > 0) ? (float)$uangPhk->lain : (($payrollPhk && (float)$payrollPhk->lain > 0) ? (float)$payrollPhk->lain : 0);
        $ketLain = ($uangPhk && !empty($uangPhk->ket_lain)) ? $uangPhk->ket_lain : '';
        $ketLainStr = !empty($ketLain) ? ' (' . $ketLain . ')' : '';

        if ($valTelkomsel > 0 && $valLain > 0) {
            $itemsLeft[] = [
                'label' => 'Telkomsel & Lain-lain' . $ketLainStr,
                'nominal' => $valTelkomsel + $valLain,
                'subtext' => null,
            ];
        } elseif ($valTelkomsel > 0) {
            $itemsLeft[] = [
                'label' => 'Telkomsel',
                'nominal' => $valTelkomsel,
                'subtext' => null,
            ];
        } elseif ($valLain > 0) {
            $itemsLeft[] = [
                'label' => 'Lain-lain' . $ketLainStr,
                'nominal' => $valLain,
                'subtext' => null,
            ];
        }

        // Additional Items Right
        $itemsRight = [];
        $valPot25Jumlah = $payrollPhk ? (float)$payrollPhk->pot_25_jumlah : 0;
        $valPot25Hari = $payrollPhk ? (float)$payrollPhk->pot_25_hari : 0;
        if ($valPot25Jumlah > 0) {
            $hari25Str = ((float)$valPot25Hari == (int)$valPot25Hari) ? (int)$valPot25Hari : (float)$valPot25Hari;
            $itemsRight[] = [
                'label' => 'Keterlambatan Kehadiran 25%' . ($valPot25Hari > 0 ? ' x ' . $hari25Str . ' HR' : ''),
                'nominal' => $valPot25Jumlah,
            ];
        }

        $valPotTelepon = $payrollPhk ? (float)$payrollPhk->pot_telepon : 0;
        if ($valPotTelepon > 0) {
            $itemsRight[] = [
                'label' => 'Pemakaian Telepon/Telkomsel',
                'nominal' => $valPotTelepon,
            ];
        }

        $valPotBpjs = $payrollPhk ? (float)$payrollPhk->pot_bpjs : 0;
        if ($valPotBpjs > 0) {
            $itemsRight[] = [
                'label' => 'BPJS Kesehatan',
                'nominal' => $valPotBpjs,
            ];
        }

        $valPotBensin = $payrollPhk ? (float)$payrollPhk->pot_bensin : 0;
        if ($valPotBensin > 0) {
            $itemsRight[] = [
                'label' => 'Pemakaian Bensin',
                'nominal' => $valPotBensin,
            ];
        }

        $valPotKompensasiJumlah = $payrollPhk ? (float)$payrollPhk->pot_kompensasi_jumlah : 0;
        $valPotKompensasiJam = $payrollPhk ? (float)$payrollPhk->pot_kompensasi_jam : 0;
        if ($valPotKompensasiJumlah > 0) {
            $jamStr = ((float)$valPotKompensasiJam == (int)$valPotKompensasiJam) ? (int)$valPotKompensasiJam : (float)$valPotKompensasiJam;
            $itemsRight[] = [
                'label' => 'Kompensasi Hadir' . ($valPotKompensasiJam > 0 ? ' x ' . $jamStr . ' Jam' : ''),
                'nominal' => $valPotKompensasiJumlah,
            ];
        }

        $valPotLain = ($uangPhk && (float)$uangPhk->pot_lain > 0) ? (float)$uangPhk->pot_lain : (($payrollPhk && (float)$payrollPhk->pot_lain > 0) ? (float)$payrollPhk->pot_lain : 0);
        $ketPotLain = ($uangPhk && !empty($uangPhk->ket_pot_lain)) ? $uangPhk->ket_pot_lain : '';
        if ($valPotLain > 0) {
            $potLainLabel = !empty($ketPotLain) ? 'Lain-lain (' . $ketPotLain . ')' : 'Lain-lain';
            $itemsRight[] = [
                'label' => $potLainLabel,
                'nominal' => $valPotLain,
            ];
        }

        $valPotKas = ($uangPhk && (float)$uangPhk->pot_kas > 0) ? (float)$uangPhk->pot_kas : (($payrollPhk && (float)$payrollPhk->pot_kas > 0) ? (float)$payrollPhk->pot_kas : 0);
        $valPotCicilan = ($uangPhk && (float)$uangPhk->pot_cicilan > 0) ? (float)$uangPhk->pot_cicilan : (($payrollPhk && (float)$payrollPhk->pot_cicilan > 0) ? (float)$payrollPhk->pot_cicilan : 0);
        $valPotCutiHari = ($uangPhk && (float)$uangPhk->pot_cuti_hari > 0) ? (float)$uangPhk->pot_cuti_hari : (($payrollPhk && (float)$payrollPhk->pot_cuti_hari > 0) ? (float)$payrollPhk->pot_cuti_hari : 0);
        $valPotCutiJumlah = ($uangPhk && (float)$uangPhk->pot_cuti_jumlah > 0) ? (float)$uangPhk->pot_cuti_jumlah : (($payrollPhk && (float)$payrollPhk->pot_cuti_jumlah > 0) ? (float)$payrollPhk->pot_cuti_jumlah : 0);
        $hariCutiStr = ((float)$valPotCutiHari == (int)$valPotCutiHari) ? (int)$valPotCutiHari : (float)$valPotCutiHari;
        $labelUnpaidLeave = 'Unpaid Leave / Cuti Bersama' . ($valPotCutiHari > 0 ? ' x ' . $hariCutiStr . ' HR' : '');

        $valKenaikanGaji = $payrollPhk ? (float)$payrollPhk->kenaikan_gaji : 0;

        // Calculate Totals
        $totalA = ($payrollPhk ? (float)$payrollPhk->gaji : 0);
        if ($valKenaikanGaji > 0) {
            $totalA += $valKenaikanGaji;
        }
        $totalA += ($payrollPhk ? (float)$payrollPhk->uang_makan_jumlah : 0);
        if ($uangPhk && $uangPhk->pesangon > 0) {
            $totalA += (float)$uangPhk->pesangon;
        }
        foreach ($itemsLeft as $itL) {
            $totalA += (float)$itL['nominal'];
        }

        $totalB = (float)$valPotKas + (float)$valPotCicilan + (float)$valPotCutiJumlah;
        foreach ($itemsRight as $itR) {
            $totalB += (float)$itR['nominal'];
        }

        $bersih = $totalA - $totalB;

        // Images
        $logoB64 = $this->imgB64(public_path('images/logo_fjg.jpg'), 'jpeg');
        $stempelB64 = $this->imgB64(public_path('images/stempel_full.png'), 'png');

        // Note content
        $dbNote = Note::where('kode', 'NOTE_SLIP_PHK')->first();
        $defaultNote = "Hak Karyawan sesuai dengan Peraturan Pemerintah No.35 Tahun 2021 yang berlaku &\n" .
            "tercantum dalam Peraturan Perusahaan (PP) Pasal 31 (tiga puluh satu) tentang Hak Karyawan\n" .
            "serta Kompensasi yang sudah dapat Pengesahan dari Kementrian Ketenagakerjaan RI";
        $noteContent = nl2br(htmlspecialchars(($dbNote && !empty($dbNote->teks)) ? $dbNote->teks : $defaultNote));

        // Assemble rows matching layout
        $rows = [];
        $rows[] = [
            'left_label' => $labelGaji, 'left_calc' => '', 'left_val' => ($payrollPhk ? $payrollPhk->gaji : 0),
            'right_label' => 'Pinjaman Kas', 'right_val' => ($valPotKas > 0 ? $valPotKas : 0),
        ];

        if ($valKenaikanGaji > 0) {
            $rows[] = ['spacer' => true];
            $rows[] = [
                'left_label' => 'Kenaikan Gaji', 'left_calc' => '', 'left_val' => $valKenaikanGaji,
                'right_label' => 'Pinjaman Cicilan', 'right_val' => ($valPotCicilan > 0 ? $valPotCicilan : 0),
            ];
            $rows[] = ['spacer' => true];
            $harianStr = ($payrollPhk && $payrollPhk->hari_makan > 0) ? ($this->fmt($payrollPhk->uang_makan_harian) . ' x ' . $payrollPhk->hari_makan . ' HR') : '';
            $rows[] = [
                'left_label' => 'U/Makan & Transportasi', 'left_calc' => $harianStr, 'left_val' => ($payrollPhk && $payrollPhk->uang_makan_jumlah > 0 ? $payrollPhk->uang_makan_jumlah : 0),
                'right_label' => $labelUnpaidLeave, 'right_val' => ($valPotCutiJumlah > 0 ? $valPotCutiJumlah : 0),
            ];
            $rows[] = [
                'left_label' => '<span style="font-size:7pt;">' . $labelMakan . '</span>', 'left_calc' => '', 'left_val' => null,
                'right_label' => '', 'right_val' => null,
            ];
            $rows[] = ['spacer' => true];
            $firstR = (count($itemsRight) > 0) ? array_shift($itemsRight) : null;
            $rows[] = [
                'left_label' => 'Uang Pesangon', 'left_calc' => '', 'left_val' => ($uangPhk && $uangPhk->pesangon > 0 ? $uangPhk->pesangon : 0),
                'right_label' => $firstR ? $firstR['label'] : '', 'right_val' => $firstR ? $firstR['nominal'] : null,
            ];
            if ($uangPhk && !empty(trim($uangPhk->ket_pesangon ?? ''))) {
                $rows[] = [
                    'left_label' => '<span style="font-size:7pt;">(' . htmlspecialchars(trim($uangPhk->ket_pesangon)) . ')</span>', 'left_calc' => '', 'left_val' => null,
                    'right_label' => '', 'right_val' => null,
                ];
            }
        } else {
            $rows[] = ['spacer' => true];
            $harianStr = ($payrollPhk && $payrollPhk->hari_makan > 0) ? ($this->fmt($payrollPhk->uang_makan_harian) . ' x ' . $payrollPhk->hari_makan . ' HR') : '';
            $rows[] = [
                'left_label' => 'U/Makan & Transportasi', 'left_calc' => $harianStr, 'left_val' => ($payrollPhk && $payrollPhk->uang_makan_jumlah > 0 ? $payrollPhk->uang_makan_jumlah : 0),
                'right_label' => 'Pinjaman Cicilan', 'right_val' => ($valPotCicilan > 0 ? $valPotCicilan : 0),
            ];
            $rows[] = [
                'left_label' => '<span style="font-size:7pt;">' . $labelMakan . '</span>', 'left_calc' => '', 'left_val' => null,
                'right_label' => '', 'right_val' => null,
            ];
            $rows[] = ['spacer' => true];
            $rows[] = [
                'left_label' => 'Uang Pesangon', 'left_calc' => '', 'left_val' => ($uangPhk && $uangPhk->pesangon > 0 ? $uangPhk->pesangon : 0),
                'right_label' => $labelUnpaidLeave, 'right_val' => ($valPotCutiJumlah > 0 ? $valPotCutiJumlah : 0),
            ];
            if ($uangPhk && !empty(trim($uangPhk->ket_pesangon ?? ''))) {
                $rows[] = [
                    'left_label' => '<span style="font-size:7pt;">(' . htmlspecialchars(trim($uangPhk->ket_pesangon)) . ')</span>', 'left_calc' => '', 'left_val' => null,
                    'right_label' => '', 'right_val' => null,
                ];
            }
        }

        $numSlots = max(count($itemsLeft), count($itemsRight));
        for ($idx = 0; $idx < $numSlots; $idx++) {
            $rows[] = ['spacer' => true];
            $itL = isset($itemsLeft[$idx]) ? $itemsLeft[$idx] : null;
            $itR = isset($itemsRight[$idx]) ? $itemsRight[$idx] : null;
            $rows[] = [
                'left_label' => $itL ? $itL['label'] : '', 'left_calc' => '', 'left_val' => $itL ? $itL['nominal'] : null,
                'right_label' => $itR ? $itR['label'] : '', 'right_val' => $itR ? $itR['nominal'] : null,
            ];
            if ($itL && !empty($itL['subtext'])) {
                $rows[] = [
                    'left_label' => '<span style="font-size:7pt;">' . htmlspecialchars($itL['subtext']) . '</span>', 'left_calc' => '', 'left_val' => null,
                    'right_label' => '', 'right_val' => null,
                ];
            }
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, Helvetica, sans-serif; font-size: 8pt; font-weight: bold; margin: 0; padding: 0; }
.main-box { border: 0.8pt solid #000; width: 100%; }
.table-header { width: 100%; border-bottom: 0.8pt solid #000; border-collapse: collapse; }
.table-header td { padding: 1.5mm 2mm; vertical-align: middle; }
.comp-name { font-family: Arial, sans-serif; font-size: 11.5pt; font-weight: bold; color: #0070C0; text-align: center; }
.comp-sub { font-size: 9.5pt; font-weight: bold; text-align: center; }
.comp-addr { font-size: 8.5pt; font-weight: bold; text-align: center; }
.banner-gray { background-color: #D9D9D9; text-align: center; font-weight: bold; padding: 1.8mm 0; }
.title-slip { font-size: 11pt; text-decoration: underline; }
.masa-kerja { font-size: 8.5pt; margin-top: 1mm; }
.info-table { width: 100%; border-bottom: 0.8pt solid #000; border-collapse: collapse; }
.info-table td { padding: 0.8mm 2mm; font-size: 8.5pt; font-weight: bold; vertical-align: top; }
.calc-table { width: 100%; border-collapse: collapse; }
.calc-table td { padding: 0.7mm 0.8mm; font-size: 7.2pt; font-weight: bold; vertical-align: middle; }
.sec-title { font-size: 8.5pt; font-weight: bold; text-decoration: underline; color: #0070C0; }
.cred { color: #FF0000; }
.bersih-bar { background-color: #BFBFBF; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; padding: 1.5mm 2mm; }
.bottom-table { width: 100%; border-collapse: collapse; }
.bottom-table td { padding: 2mm 2.5mm; vertical-align: top; }
</style>
</head>
<body>
<div class="main-box">
  <!-- Header Company -->
  <table class="table-header">
    <tr>
      <td style="width: 25mm;"></td>
      <td style="text-align: center; padding-top: 3mm; padding-bottom: 2mm;">
        <div class="comp-name">PT.FRATEKINDO JAYA GEMILANG</div>
        <div class="comp-sub" style="margin-top: 1mm;">SOVEREIGN PLAZA</div>
        <div class="comp-addr" style="margin-top: 0.8mm;">TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430</div>
      </td>
      <td style="width: 45mm; text-align: right; padding-right: 3mm; vertical-align: middle;">
        ' . ($logoB64 ? '<img src="' . $logoB64 . '" style="height: 10mm; display: inline-block;">' : '') . '
      </td>
    </tr>
  </table>

  <!-- Banner SLIP PESANGON -->
  <div class="banner-gray">
    <div class="title-slip">SLIP PESANGON</div>
    <div class="masa-kerja">Masa Kerja : ' . $tglMasuk . ' s/d ' . $tglKeluar . ' (' . $masaKerjaStr . ')</div>
  </div>

  <!-- Employee Info -->
  <table class="info-table">
    <tr>
      <td style="width: 34.2mm;">Nama</td>
      <td style="width: 3mm;">:</td>
      <td>' . strtoupper($karyawan->nama) . '</td>
    </tr>
    <tr>
      <td>Jabatan</td>
      <td>:</td>
      <td>' . ($karyawan->jabatan ? $karyawan->jabatan->nama : '') . '</td>
    </tr>
    <tr>
      <td>Divisi</td>
      <td>:</td>
      <td>' . ($karyawan->divisi ? $karyawan->divisi->nama : '') . '</td>
    </tr>
    <tr>
      <td>Gaji &amp; Tunjangan</td>
      <td>:</td>
      <td>Rp. ' . $this->fmt($gajiTunjangan) . '</td>
    </tr>
  </table>

  <!-- Calculation Items Table -->
  <table class="calc-table" style="margin-top: 1.5mm;">
    <!-- Sizing row to enforce exact column widths in mPDF -->
    <tr style="height: 0; line-height: 0; font-size: 0;">
      <td style="width: 34.2mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 24.8mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 5.4mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 29.3mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 11.5mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 54.1mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 5.4mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
      <td style="width: 29.3mm; height: 0; padding: 0; margin: 0; border: none; font-size: 0; line-height: 0;"></td>
    </tr>
    <!-- Section Headers: Penghasilan & Potongan -->
    <tr>
      <td colspan="4" style="padding-left: 2mm;" class="sec-title">A. PENGHASILAN :</td>
      <td></td>
      <td colspan="3" style="padding-left: 2mm;" class="sec-title">B. POTONGAN :</td>
    </tr>';

        foreach ($rows as $r) {
            if (!empty($r['spacer'])) {
                $html .= '<tr><td colspan="8" style="height: 2.2mm;"></td></tr>';
                continue;
            }
            $lValStr = ($r['left_val'] !== null) ? $this->fmt($r['left_val']) : '';
            $lEq = ($r['left_val'] !== null) ? '=' : '';
            $rValStr = ($r['right_val'] !== null) ? $this->fmt($r['right_val']) : '';
            $rEq = ($r['right_val'] !== null) ? '=' : '';

            $calcCol = '';
            if (!empty($r['left_calc'])) {
                $calcCol = '<td style="padding-left: 1.5mm; white-space: nowrap;">' . $r['left_label'] . '</td>
      <td style="text-align: right; white-space: nowrap;">' . $r['left_calc'] . '</td>';
            } else {
                $lblStyle = (strlen(strip_tags($r['left_label'])) > 36) ? 'padding-left: 1.5mm; font-size: 6.8pt;' : 'padding-left: 1.5mm;';
                $calcCol = '<td colspan="2" style="' . $lblStyle . '">' . $r['left_label'] . '</td>';
            }

            $rLblStyle = (strlen(strip_tags($r['right_label'])) > 33) ? 'padding-left: 1.5mm; font-size: 6.8pt;' : 'padding-left: 1.5mm;';

            $html .= '<tr>
      ' . $calcCol . '
      <td style="text-align: center;">' . $lEq . '</td>
      <td style="text-align: right;">' . $lValStr . '</td>
      <td></td>
      <td style="' . $rLblStyle . '">' . $r['right_label'] . '</td>
      <td style="text-align: center;">' . $rEq . '</td>
      <td style="text-align: right;">' . $rValStr . '</td>
    </tr>';
        }

        // Total Row
        $html .= '<tr>
      <td colspan="2" style="text-align: right; padding-right: 2mm;">Total A</td>
      <td style="text-align: center;">=</td>
      <td style="text-align: right; border-top: 0.5pt solid #000;">' . $this->fmt($totalA) . '</td>
      <td></td>
      <td style="text-align: right; padding-right: 2mm;">Total B</td>
      <td style="text-align: center;">=</td>
      <td style="text-align: right; border-top: 0.5pt solid #000;">' . $this->fmt($totalB) . '</td>
    </tr>';

        // Row Space before Penerimaan Bersih
        $html .= '<tr><td colspan="8" style="height: 3mm;"></td></tr>';

        // Penerimaan Bersih (A - B)
        $html .= '<tr style="background-color: #BFBFBF;">
      <td colspan="5" style="background-color: #BFBFBF; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; height: 5.5mm;">&nbsp;</td>
      <td style="background-color: #BFBFBF; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; text-align: right; padding-right: 2mm; font-size: 8.5pt; font-weight: bold; white-space: nowrap;">PENERIMAAN BERSIH (A-B)</td>
      <td style="background-color: #BFBFBF; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; text-align: center; font-size: 8.5pt; font-weight: bold;">=</td>
      <td style="background-color: #BFBFBF; border-top: 0.8pt solid #000; border-bottom: 0.8pt solid #000; text-align: right; font-size: 9pt; font-weight: bold; color: #FF0000;">' . $this->fmt($bersih) . '</td>
    </tr>
  </table>

  <!-- Note & Signatures -->
  <table class="bottom-table">
    <tr>
      <td style="width: 159.3mm; padding-top: 2.5mm; padding-left: 2.5mm;">
        <div style="font-family: Arial, sans-serif; font-size: 8.5pt; font-weight: bold; text-decoration: underline; color: #0070C0; margin-bottom: 2mm;">Note :</div>
        <div style="font-family: Arial, sans-serif; font-size: 7.8pt; font-weight: bold; line-height: 1.4; color: #0070C0;">' . $noteContent . '</div>
      </td>
      <td style="width: 34.7mm; text-align: center; vertical-align: top; padding-top: 2.5mm; padding-right: 1mm;">
        <div style="font-family: Arial, sans-serif; font-size: 8.5pt; font-weight: bold; text-align: center;">Head of HR Dept.</div>
        <div style="text-align: center; margin: 1mm 0;">
          ' . ($stempelB64 ? '<img src="' . $stempelB64 . '" height="40" style="display: inline-block;">' : '') . '
        </div>
        <div style="font-family: Arial, sans-serif; font-size: 8.5pt; font-weight: bold; text-align: center;">Sri Erni.S</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>';

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
            'default_font' => 'dejavusanscondensed',
            'default_font_size' => 8,
        ]);

        /* Password protection — 128-bit RC4
         * user_password  : tanggal lahir DDMMYYYY (wajib untuk membuka)
         * owner_password : random kuat (tidak diketahui siapapun) */
        $password = '';
        if (!empty($karyawan->tanggal_lahir)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $karyawan->tanggal_lahir);
            if ($dt) {
                $password = $dt->format('dmY');  // DDMMYYYY
            } else {
                $password = date('dmY', strtotime($karyawan->tanggal_lahir));
            }
        }
        if ($password !== '') {
            $ownerPass = bin2hex(random_bytes(16));
            $mpdf->SetProtection(['print', 'print-highres'], $password, $ownerPass, 128);
        }

        $mpdf->SetTitle('Slip PHK - ' . $karyawan->nama);
        $mpdf->WriteHTML($html);
        $namaFile = 'SLIP_PHK_' . str_replace(' ', '_', $karyawan->nama) . '.pdf';
        $mpdf->Output($namaFile, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
