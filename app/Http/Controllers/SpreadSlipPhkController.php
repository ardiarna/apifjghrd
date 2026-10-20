<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Phk;
use App\Models\UangPhk;
use App\Models\PayrollPhk;
use App\Models\Upah;
use App\Models\Note;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SpreadSlipPhkController extends Controller
{
    public function buildSpreadsheet($karyawan_id) {
        $karyawan = Karyawan::with(['phk.uangPhk', 'phk.payrollPhk', 'divisi', 'jabatan', 'area', 'upah'])->find($karyawan_id);
        if (!$karyawan) {
            return ['status' => false, 'message' => 'Data Karyawan tidak ditemukan'];
        }

        if (!$karyawan->phk_id) {
            return ['status' => false, 'message' => 'Karyawan tidak memiliki data PHK aktif'];
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
            return ['status' => false, 'message' => 'Data Uang PHK tidak ditemukan'];
        }

        $spreadsheet = new Spreadsheet();
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);

        $si->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $si->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $si->getPageSetup()->setFitToPage(true);
        $si->getPageSetup()->setFitToWidth(1);
        $si->getPageSetup()->setFitToHeight(0);

        $si->getPageMargins()->setTop(0.15748);
        $si->getPageMargins()->setBottom(0.15748);
        $si->getPageMargins()->setLeft(0.3937);
        $si->getPageMargins()->setRight(0.3937);

        // Default Font: Ebrima 10 Bold
        $spreadsheet->getDefaultStyle()->getFont()->setName('Ebrima')->setSize(10)->setBold(true);

        // Column Dimensions (exact pixels requested)
        $si->getColumnDimension('A')->setWidth(12);
        $si->getColumnDimension('B')->setWidth(9.196875);
        $si->getColumnDimension('C')->setWidth(15.33203125);
        $si->getColumnDimension('D')->setWidth(3.33203125);
        $si->getColumnDimension('E')->setWidth(18.1640625);
        $si->getColumnDimension('F')->setWidth(7.1640625);
        $si->getColumnDimension('G')->setWidth(33.5);
        $si->getColumnDimension('H')->setWidth(3.33203125);
        $si->getColumnDimension('I')->setWidth(18.1640625);

        // Row Heights
        $rowHeights = [
            1 => 17.0, 2 => 14.5, 3 => 22.5, 4 => 16.0, 5 => 14.0,
            6 => 20.25, 7 => 14.0, 8 => 24.0, 9 => 15.5, 10 => 14.5,
            11 => 17.5, 12 => 16.75
        ];
        foreach ($rowHeights as $r => $h) {
            $si->getRowDimension($r)->setRowHeight($h);
        }

        // Merged Cells
        $si->mergeCells('B3:G3');
        $si->mergeCells('B4:G4');
        $si->mergeCells('B5:G5');
        $si->mergeCells('A6:I6');
        $si->mergeCells('A7:I7');
        $si->mergeCells('A8:B8');
        $si->mergeCells('A9:B9');
        $si->mergeCells('A10:B10');
        $si->mergeCells('A11:B11');

        // Header Company Info
        $si->setCellValue('B3', 'PT.FRATEKINDO JAYA GEMILANG');
        $si->getStyle('B3')->getFont()->setName('Narkisim')->setSize(13)->setBold(true)->getColor()->setARGB('0070C0');
        $si->getStyle('B3')->getAlignment()->setHorizontal('center');

        $si->setCellValue('B4', 'SOVEREIGN PLAZA');
        $si->getStyle('B4')->getFont()->setName('Ebrima')->setSize(11)->setBold(true);
        $si->getStyle('B4')->getAlignment()->setHorizontal('center');

        $si->setCellValue('B5', 'TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430');
        $si->getStyle('B5')->getFont()->setName('Ebrima')->setSize(10)->setBold(true);
        $si->getStyle('B5')->getAlignment()->setHorizontal('center');

        // Row 6: SLIP PESANGON (Background #D9D9D9)
        $si->setCellValue('A6', 'SLIP PESANGON');
        $si->getStyle('A6')->getFont()->setName('Ebrima')->setSize(12)->setBold(true)->setUnderline(true);
        $si->getStyle('A6')->getAlignment()->setHorizontal('center');
        $si->getStyle('A6:I6')->getFill()->setFillType('solid')->getStartColor()->setARGB('D9D9D9');

        // Row 7: Masa Kerja (Background #D9D9D9)
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

        $si->setCellValue('A7', 'Masa Kerja : ' . $tglMasuk . ' s/d ' . $tglKeluar . ' (' . $masaKerjaStr . ')');
        $si->getStyle('A7')->getFont()->setName('Ebrima')->setSize(10)->setBold(true);
        $si->getStyle('A7')->getAlignment()->setHorizontal('center');
        $si->getStyle('A7:I7')->getFill()->setFillType('solid')->getStartColor()->setARGB('D9D9D9');

        // Rows 8-11: Karyawan Info
        $si->setCellValue('A8', 'Nama');
        $si->setCellValue('C8', ': ' . strtoupper($karyawan->nama));

        $si->setCellValue('A9', 'Jabatan');
        $si->setCellValue('C9', ': ' . ($karyawan->jabatan ? $karyawan->jabatan->nama : ''));

        $si->setCellValue('A10', 'Divisi');
        $si->setCellValue('C10', ': ' . ($karyawan->divisi ? $karyawan->divisi->nama : ''));

        $upah = $karyawan->upah ?: Upah::where('karyawan_id', $karyawan_id)->first();
        $gajiTunjangan = 0;
        if ($upah) {
            $gajiTunjangan = (strtoupper((string)$upah->makan_harian) === 'N')
                ? ($upah->gaji + $upah->uang_makan)
                : $upah->gaji;
        }

        $si->setCellValue('A11', 'Gaji & Tunjangan');
        $si->setCellValue('C11', ': Rp. ' . number_format($gajiTunjangan, 0, ',', '.'));

        // Row 12: Headers Penghasilan & Potongan
        $si->setCellValue('A12', 'A. PENGHASILAN :');
        $si->getStyle('A12')->getFont()->setName('Ebrima')->setSize(10)->setBold(true)->setUnderline(true)->getColor()->setARGB('0070C0');

        $si->setCellValue('G12', 'B. POTONGAN :');
        $si->getStyle('G12')->getFont()->setName('Ebrima')->setSize(10)->setBold(true)->setUnderline(true)->getColor()->setARGB('0070C0');

        // Periods for Gaji Pokok & U/Makan
        $bulan = ($payrollPhk && $payrollPhk->bulan) ? (int)$payrollPhk->bulan : null;
        $tahun = ($payrollPhk && $payrollPhk->tahun) ? (int)$payrollPhk->tahun : null;

        if (!$bulan || !$tahun) {
            $rawDate = ($phk && $phk->tanggal_akhir) ? $phk->tanggal_akhir : ($karyawan->tanggal_keluar ?: date('Y-m-d'));
            $bulan = (int)date('m', strtotime($rawDate));
            $tahun = (int)date('Y', strtotime($rawDate));
        }

        $arrBulan = array("", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des");
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

        // Slot lanjutan A. Penghasilan: Penghargaan Masa Kerja, Kompensasi, UANG PISAH, Penggantian Hak, Lembur, Medical, THR, Bonus, Insentif, Telkomsel & Lain-lain
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
        // Uang Penggantian Hak (muncul jika memiliki nilai > 0)
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

        // Lembur/Overtime (di bawah Uang Penggantian Hak)
        $valOvertime = $payrollPhk ? ((float)$payrollPhk->overtime_fjg + (float)$payrollPhk->overtime_cus) : 0;
        if ($valOvertime > 0) {
            $itemsLeft[] = [
                'label' => 'Lembur/Overtime',
                'nominal' => $valOvertime,
                'subtext' => null,
            ];
        }

        // Reimbursement Medical
        $valMedical = $payrollPhk ? (float)$payrollPhk->medical : 0;
        if ($valMedical > 0) {
            $itemsLeft[] = [
                'label' => 'Reimbursement Medical',
                'nominal' => $valMedical,
                'subtext' => null,
            ];
        }

        // Tunjangan Hari Raya
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

        // Padukan Lain-Lain ($keterangan) dengan Telkomsel
        $valTelkomsel = $payrollPhk ? (float)$payrollPhk->telkomsel : 0;
        $valLain = ($uangPhk && (float)$uangPhk->lain > 0) ? (float)$uangPhk->lain : (($payrollPhk && (float)$payrollPhk->lain > 0) ? (float)$payrollPhk->lain : 0);
        $ketLain = ($uangPhk && !empty($uangPhk->ket_lain)) ? $uangPhk->ket_lain : (($payrollPhk && !empty($payrollPhk->keterangan)) ? $payrollPhk->keterangan : '');
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

        // Slot lanjutan B. Potongan: Keterlambatan Kehadiran 25%, Pemakaian Telepon/Telkomsel, BPJS Kesehatan, Pemakaian Bensin, Kompensasi Hadir, Lain-lain
        $itemsRight = [];

        // Keterlambatan Kehadiran 25%
        $valPot25Jumlah = $payrollPhk ? (float)$payrollPhk->pot_25_jumlah : 0;
        $valPot25Hari = $payrollPhk ? (float)$payrollPhk->pot_25_hari : 0;
        if ($valPot25Jumlah > 0) {
            $hari25Str = ((float)$valPot25Hari == (int)$valPot25Hari) ? (int)$valPot25Hari : (float)$valPot25Hari;
            $itemsRight[] = [
                'label' => 'Keterlambatan Kehadiran 25%' . ($valPot25Hari > 0 ? ' x ' . $hari25Str . ' HR' : ''),
                'nominal' => $valPot25Jumlah,
            ];
        }

        // Pemakaian Telepon/Telkomsel
        $valPotTelepon = $payrollPhk ? (float)$payrollPhk->pot_telepon : 0;
        if ($valPotTelepon > 0) {
            $itemsRight[] = [
                'label' => 'Pemakaian Telepon/Telkomsel',
                'nominal' => $valPotTelepon,
            ];
        }

        // BPJS Kesehatan
        $valPotBpjs = $payrollPhk ? (float)$payrollPhk->pot_bpjs : 0;
        if ($valPotBpjs > 0) {
            $itemsRight[] = [
                'label' => 'BPJS Kesehatan',
                'nominal' => $valPotBpjs,
            ];
        }

        // Pemakaian Bensin
        $valPotBensin = $payrollPhk ? (float)$payrollPhk->pot_bensin : 0;
        if ($valPotBensin > 0) {
            $itemsRight[] = [
                'label' => 'Pemakaian Bensin',
                'nominal' => $valPotBensin,
            ];
        }

        // Kompensasi Hadir
        $valPotKompensasiJumlah = $payrollPhk ? (float)$payrollPhk->pot_kompensasi_jumlah : 0;
        $valPotKompensasiJam = $payrollPhk ? (float)$payrollPhk->pot_kompensasi_jam : 0;
        if ($valPotKompensasiJumlah > 0) {
            $jamStr = ((float)$valPotKompensasiJam == (int)$valPotKompensasiJam) ? (int)$valPotKompensasiJam : (float)$valPotKompensasiJam;
            $itemsRight[] = [
                'label' => 'Kompensasi Hadir' . ($valPotKompensasiJam > 0 ? ' x ' . $jamStr . ' Jam' : ''),
                'nominal' => $valPotKompensasiJumlah,
            ];
        }

        // Potongan Lain-lain
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

        $bar = 13;
        // Row 13: Gaji Pokok & Pinjaman Kas
        $si->setCellValue('A' . $bar, $labelGaji);
        $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('E' . $bar, ($payrollPhk && $payrollPhk->gaji > 0) ? $payrollPhk->gaji : 0);

        $si->setCellValue('G' . $bar, 'Pinjaman Kas');
        $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('I' . $bar, $valPotKas > 0 ? $valPotKas : 0);
        $si->getRowDimension($bar)->setRowHeight(16.75);

        // Kenaikan Gaji (dibawah Gaji Pokok, muncul jika memiliki nilai > 0)
        $valKenaikanGaji = $payrollPhk ? (float)$payrollPhk->kenaikan_gaji : 0;
        if ($valKenaikanGaji > 0) {
            $bar++; // space kosong antara Gaji Pokok dan Kenaikan Gaji (Row 14)
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // Row 15: Kenaikan Gaji & Pinjaman Cicilan
            $si->setCellValue('A' . $bar, 'Kenaikan Gaji');
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, $valKenaikanGaji);

            $si->setCellValue('G' . $bar, 'Pinjaman Cicilan');
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, $valPotCicilan > 0 ? $valPotCicilan : 0);
            $si->getRowDimension($bar)->setRowHeight(16.75);

            $bar++; // space kosong sebelum U/Makan & Transportasi (Row 16)
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // Row 17: U/Makan & Transportasi & Unpaid Leave / Cuti Bersama
            $si->setCellValue('A' . $bar, 'U/Makan & Transportasi');
            if ($payrollPhk && $payrollPhk->hari_makan > 0) {
                $harianFormatted = number_format($payrollPhk->uang_makan_harian, 0, ',', '.');
                $si->setCellValue('C' . $bar, $harianFormatted . ' x ' . $payrollPhk->hari_makan . ' HR');
                $si->getStyle('C' . $bar)->getAlignment()->setHorizontal('right');
            }
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, ($payrollPhk && $payrollPhk->uang_makan_jumlah > 0) ? $payrollPhk->uang_makan_jumlah : 0);

            $si->setCellValue('G' . $bar, $labelUnpaidLeave);
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, $valPotCutiJumlah > 0 ? $valPotCutiJumlah : 0);
            $si->getRowDimension($bar)->setRowHeight(19.75);

            $bar++; // Sub-text U/Makan (Row 18)
            $si->setCellValue('A' . $bar, $labelMakan);
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // spacer (Row 19)
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // Row 20: Uang Pesangon & potongan lanjutan pertama
            $si->setCellValue('A' . $bar, 'Uang Pesangon');
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, ($uangPhk && $uangPhk->pesangon > 0) ? $uangPhk->pesangon : 0);

            if (count($itemsRight) > 0) {
                $firstR = array_shift($itemsRight);
                $si->setCellValue('G' . $bar, $firstR['label']);
                $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
                $si->setCellValue('I' . $bar, $firstR['nominal']);
            }
            $si->getRowDimension($bar)->setRowHeight(14.0);

            if ($uangPhk && !empty(trim($uangPhk->ket_pesangon ?? ''))) {
                $bar++; // Sub-text Pesangon
                $si->setCellValue('A' . $bar, '(' . trim($uangPhk->ket_pesangon) . ')');
                $si->getRowDimension($bar)->setRowHeight(14.0);
            }
        } else {
            // Tanpa Kenaikan Gaji
            $bar++; // space kosong sebelum U/Makan & Transportasi (Row 14)
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // Row 15: U/Makan & Transportasi & Pinjaman Cicilan
            $si->setCellValue('A' . $bar, 'U/Makan & Transportasi');
            if ($payrollPhk && $payrollPhk->hari_makan > 0) {
                $harianFormatted = number_format($payrollPhk->uang_makan_harian, 0, ',', '.');
                $si->setCellValue('C' . $bar, $harianFormatted . ' x ' . $payrollPhk->hari_makan . ' HR');
                $si->getStyle('C' . $bar)->getAlignment()->setHorizontal('right');
            }
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, ($payrollPhk && $payrollPhk->uang_makan_jumlah > 0) ? $payrollPhk->uang_makan_jumlah : 0);

            $si->setCellValue('G' . $bar, 'Pinjaman Cicilan');
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, $valPotCicilan > 0 ? $valPotCicilan : 0);
            $si->getRowDimension($bar)->setRowHeight(19.75);

            $bar++; // Sub-text U/Makan (Row 16)
            $si->setCellValue('A' . $bar, $labelMakan);
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // spacer (Row 17)
            $si->getRowDimension($bar)->setRowHeight(14.0);

            $bar++; // Row 18: Uang Pesangon & Unpaid Leave / Cuti Bersama
            $si->setCellValue('A' . $bar, 'Uang Pesangon');
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, ($uangPhk && $uangPhk->pesangon > 0) ? $uangPhk->pesangon : 0);

            $si->setCellValue('G' . $bar, $labelUnpaidLeave);
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, $valPotCutiJumlah > 0 ? $valPotCutiJumlah : 0);
            $si->getRowDimension($bar)->setRowHeight(14.0);

            if ($uangPhk && !empty(trim($uangPhk->ket_pesangon ?? ''))) {
                $bar++; // Sub-text Pesangon
                $si->setCellValue('A' . $bar, '(' . trim($uangPhk->ket_pesangon) . ')');
                $si->getRowDimension($bar)->setRowHeight(14.0);
            }
        }

        $numSlots = max(count($itemsLeft), count($itemsRight));
        if ($numSlots > 0) {
            for ($idx = 0; $idx < $numSlots; $idx++) {
                $bar++; // 1 baris kosong pemisah antar item
                $si->getRowDimension($bar)->setRowHeight(14.0);

                $bar++;
                $si->getRowDimension($bar)->setRowHeight(14.0);

                if (isset($itemsLeft[$idx])) {
                    $itemL = $itemsLeft[$idx];
                    $si->setCellValue('A' . $bar, $itemL['label']);
                    $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
                    $si->setCellValue('E' . $bar, $itemL['nominal']);
                }

                if (isset($itemsRight[$idx])) {
                    $itemR = $itemsRight[$idx];
                    $si->setCellValue('G' . $bar, $itemR['label']);
                    $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
                    $si->setCellValue('I' . $bar, $itemR['nominal']);
                }

                if (isset($itemsLeft[$idx]) && !empty($itemsLeft[$idx]['subtext'])) {
                    $bar++;
                    $si->setCellValue('A' . $bar, $itemsLeft[$idx]['subtext']);
                    $si->getRowDimension($bar)->setRowHeight(14.0);
                }
            }
        }

        $lastDataBar = $bar;
        $si->getStyle('E' . $lastDataBar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('I' . $lastDataBar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Row Total A & Total B
        $totalBar = $lastDataBar + 1;
        $si->setCellValue('C' . $totalBar, 'Total A');
        $si->getStyle('C' . $totalBar)->getAlignment()->setHorizontal('right');
        $si->setCellValueExplicit('D' . $totalBar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('D' . $totalBar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('E' . $totalBar, '=SUM(E13:E' . $lastDataBar . ')');

        $si->setCellValue('G' . $totalBar, 'Total B');
        $si->getStyle('G' . $totalBar)->getAlignment()->setHorizontal('right');
        $si->setCellValueExplicit('H' . $totalBar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H' . $totalBar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('I' . $totalBar, '=SUM(I13:I' . $lastDataBar . ')');
        $si->getRowDimension($totalBar)->setRowHeight(14.0);

        // Separator line before Penerimaan Bersih
        $sepBar = $totalBar + 1;
        $si->getStyle('A' . $sepBar . ':I' . $sepBar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getRowDimension($sepBar)->setRowHeight(14.0);

        // Row PENERIMAAN BERSIH (A-B)
        $bersihBar = $sepBar + 1;
        $si->setCellValue('G' . $bersihBar, 'PENERIMAAN BERSIH (A-B)');
        $si->getStyle('G' . $bersihBar)->getFont()->setName('Ebrima')->setSize(11)->setBold(true);
        $si->getStyle('G' . $bersihBar)->getAlignment()->setHorizontal('right');

        $si->setCellValueExplicit('H' . $bersihBar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H' . $bersihBar)->getFont()->setName('Ebrima')->setSize(11)->setBold(true);
        $si->getStyle('H' . $bersihBar)->getAlignment()->setHorizontal('center');

        $si->setCellValue('I' . $bersihBar, '=E' . $totalBar . '-I' . $totalBar);
        $si->getStyle('I' . $bersihBar)->getFont()->setName('Ebrima')->setSize(11)->setBold(true)->getColor()->setARGB('FF0000');

        $si->getStyle('A' . $bersihBar . ':I' . $bersihBar)->getFill()->setFillType('solid')->getStartColor()->setARGB('BFBFBF');
        $si->getStyle('A' . $bersihBar . ':I' . $bersihBar)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A' . $bersihBar . ':I' . $bersihBar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getRowDimension($bersihBar)->setRowHeight(16.0);

        // Note & Signatures
        $noteBar = $bersihBar + 1;
        $si->setCellValue('A' . $noteBar, 'Note :');
        $si->getStyle('A' . $noteBar)->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true)->setUnderline(true)->getColor()->setARGB('0070C0');

        $si->setCellValue('I' . $noteBar, 'Head of HR Dept.');
        $si->getStyle('I' . $noteBar)->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I' . $noteBar)->getAlignment()->setHorizontal('center');
        $si->getRowDimension($noteBar)->setRowHeight(16.75);

        $startNoteRow = $noteBar + 1;
        $endNoteRow = $noteBar + 4;
        $lastBoxRow = $endNoteRow;

        $dbNote = Note::where('kode', 'NOTE_SLIP_PHK')->first();
        $defaultNote = "Hak Karyawan sesuai dengan Peraturan Pemerintah No.35 Tahun 2021 yang berlaku &\n" .
            "tercantum dalam Peraturan Perusahaan (PP) Pasal 31 (tiga puluh satu) tentang Hak Karyawan\n" .
            "serta Kompensasi yang sudah dapat Pengesahan dari Kementrian Ketenagakerjaan RI";
        $noteContent = ($dbNote && !empty($dbNote->teks)) ? $dbNote->teks : $defaultNote;

        $si->mergeCells('A' . $startNoteRow . ':G' . $endNoteRow);
        $si->setCellValue('A' . $startNoteRow, $noteContent);
        $si->getStyle('A' . $startNoteRow)->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true)->getColor()->setARGB('0070C0');
        $si->getStyle('A' . $startNoteRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        $si->getRowDimension($startNoteRow)->setRowHeight(15.0);
        $si->getRowDimension($startNoteRow + 1)->setRowHeight(15.0);
        $si->getRowDimension($startNoteRow + 2)->setRowHeight(15.0);
        $si->getRowDimension($startNoteRow + 3)->setRowHeight(15.0);

        $si->setCellValue('I' . ($noteBar + 3), 'Sri Erni.S');
        $si->getStyle('I' . ($noteBar + 3))->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I' . ($noteBar + 3))->getAlignment()->setHorizontal('center');

        $si->getStyle('A' . $lastBoxRow . ':I' . $lastBoxRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Outer borders for the table box
        $si->getStyle('A3:I3')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A5:I5')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A11:I11')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        for ($r = 3; $r <= $lastBoxRow; $r++) {
            $si->getStyle('A'.$r)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('I'.$r)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        }

        // Number Formats
        $si->getStyle('E13:E' . $totalBar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('I13:I' . $bersihBar)->getNumberFormat()->setFormatCode('#,##0');

        // Merge cells for clean rendering in both Excel and PDF
        $si->mergeCells('C8:I8');
        $si->mergeCells('C9:I9');
        $si->mergeCells('C10:I10');
        $si->mergeCells('C11:I11');
        $si->mergeCells('A12:E12');
        $si->mergeCells('G12:I12');

        for ($r = 13; $r < $totalBar; $r++) {
            $valA = $si->getCell('A' . $r)->getValue();
            $valC = $si->getCell('C' . $r)->getValue();
            $valD = $si->getCell('D' . $r)->getValue();
            if (!empty($valA) && empty($valC) && ($valD === '=' || empty($valD))) {
                $si->mergeCells('A' . $r . ':C' . $r);
            } elseif (!empty($valA) && !empty($valC)) {
                $si->mergeCells('A' . $r . ':B' . $r);
            }
        }

        // Drawings
        if (file_exists(public_path('images/logo_fjg.jpg'))) {
            $drawing1 = new Drawing();
            $drawing1->setPath(public_path('images/logo_fjg.jpg'));
            $drawing1->setHeight(44.16);
            $drawing1->setCoordinates('H3');
            $drawing1->setOffsetX(8);
            $drawing1->setOffsetY(16);
            $drawing1->setWorksheet($si);
        }

        if (file_exists(public_path('images/stempel_fjg.png'))) {
            $drawing2 = new Drawing();
            $drawing2->setPath(public_path('images/stempel_fjg.png'));
            $drawing2->setHeight(61.44);
            $drawing2->setCoordinates('I' . $noteBar);
            $drawing2->setOffsetX(40);
            $drawing2->setOffsetY(15);
            $drawing2->setWorksheet($si);
        }

        if (file_exists(public_path('images/stempel_ttd.png'))) {
            $drawing3 = new Drawing();
            $drawing3->setPath(public_path('images/stempel_ttd.png'));
            $drawing3->setHeight(74.88);
            $drawing3->setCoordinates('I' . $noteBar);
            $drawing3->setOffsetX(-15);
            $drawing3->setOffsetY(7);
            $drawing3->setWorksheet($si);
        }

        return [
            'status' => true,
            'spreadsheet' => $spreadsheet,
            'karyawan' => $karyawan
        ];
    }

    public function cetak($karyawan_id) {
        $result = $this->buildSpreadsheet($karyawan_id);
        if (!$result['status']) {
            return response()->json(['status' => false, 'message' => $result['message']]);
        }

        $karyawan = $result['karyawan'];
        $spreadsheet = $result['spreadsheet'];

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="SLIP_PHK_'.str_replace(' ', '_', $karyawan->nama). '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
