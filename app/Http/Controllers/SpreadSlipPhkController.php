<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Phk;
use App\Models\UangPhk;
use App\Models\PayrollPhk;
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
    public function cetak($karyawan_id) {
        $karyawan = Karyawan::with(['phk.uangPhk', 'divisi', 'jabatan', 'area'])->find($karyawan_id);
        if (!$karyawan) {
            return response()->json(['status' => false, 'message' => 'Data Karyawan tidak ditemukan']);
        }

        if (!$karyawan->phk_id) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak memiliki data PHK aktif']);
        }

        $phk = Phk::with('uangPhk')->find($karyawan->phk_id);
        $uangPhk = ($phk && $phk->uangPhk) ? $phk->uangPhk : null;

        $payrollPhk = PayrollPhk::where('karyawan_id', $karyawan_id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        if (!$uangPhk) {
            return response()->json(['status' => false, 'message' => 'Data Uang PHK tidak ditemukan']);
        }

        $spreadsheet = new Spreadsheet();
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);

        $si->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $si->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $si->getPageSetup()->setFitToPage(true);
        $si->getPageSetup()->setFitToWidth(1);
        $si->getPageSetup()->setFitToHeight(0);

        // Default Font: Ebrima 10 Bold
        $spreadsheet->getDefaultStyle()->getFont()->setName('Ebrima')->setSize(10)->setBold(true);

        // Column Dimensions (exact pixels requested)
        $si->getColumnDimension('A')->setWidth(12);
        $si->getColumnDimension('B')->setWidth(9.196875);
        $si->getColumnDimension('C')->setWidth(18.3984375);
        $si->getColumnDimension('D')->setWidth(3.196875);
        $si->getColumnDimension('E')->setWidth(15.796875);
        $si->getColumnDimension('F')->setWidth(8.596875);
        $si->getColumnDimension('G')->setWidth(40.2);
        $si->getColumnDimension('H')->setWidth(3.9984375);
        $si->getColumnDimension('I')->setWidth(21.796875);

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

        $gajiTunjangan = ($payrollPhk ? $payrollPhk->gaji : 0);
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

        $arrBulan = array("", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
        $prevBulan = $bulan == 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan == 1 ? $tahun - 1 : $tahun;

        $labelGaji = "Gaji Pokok Per : 26 " . $arrBulan[$prevBulan] . "'" . substr((string)$prevTahun, -2) . " - 25 " . $arrBulan[$bulan] . "'" . substr((string)$tahun, -2);

        if ($payrollPhk && $payrollPhk->tanggal_awal && $payrollPhk->tanggal_akhir) {
            $ttgl = explode('-', date('Y-m-d', strtotime($payrollPhk->tanggal_awal)));
            $ttgm = explode('-', date('Y-m-d', strtotime($payrollPhk->tanggal_akhir)));
            $labelMakan = '(Per: ' . $ttgl[2] . ' ' . $arrBulan[intval($ttgl[1])] . "'" . substr($ttgl[0], -2) . ' s/d ' . $ttgm[2] . ' ' . $arrBulan[intval($ttgm[1])] . "'" . substr($ttgm[0], -2) . ')';
        } else {
            $labelMakan = "(Per: 18 " . $arrBulan[$prevBulan] . "'" . substr((string)$prevTahun, -2) . " s/d 17 " . $arrBulan[$bulan] . "'" . substr((string)$tahun, -2) . ")";
        }

        $bar = 13;
        // Row 13: Gaji Pokok & Pinjaman Kas
        $si->setCellValue('A' . $bar, $labelGaji);
        $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('E' . $bar, ($payrollPhk && $payrollPhk->gaji > 0) ? $payrollPhk->gaji : 0);

        $si->setCellValue('G' . $bar, 'Pinjaman Kas');
        $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('I' . $bar, ($uangPhk && $uangPhk->pot_kas > 0) ? $uangPhk->pot_kas : 0);
        $si->getRowDimension($bar)->setRowHeight(16.75);

        $bar++; // 14
        $si->getRowDimension($bar)->setRowHeight(16.75);

        $bar++; // 15: U/Makan & Transportasi & Pinjaman Cicilan
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
        $si->setCellValue('I' . $bar, ($uangPhk && $uangPhk->pot_cicilan > 0) ? $uangPhk->pot_cicilan : 0);
        $si->getRowDimension($bar)->setRowHeight(19.75);

        $bar++; // 16: Sub-text U/Makan
        $si->setCellValue('A' . $bar, $labelMakan);
        $si->getRowDimension($bar)->setRowHeight(14.0);

        $bar++; // 17: spacer
        $si->getRowDimension($bar)->setRowHeight(14.0);

        $bar++; // 18: Uang Pesangon & Potongan Kehadiran / Cuti
        $si->setCellValue('A' . $bar, 'Uang Pesangon');
        $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
        $si->setCellValue('E' . $bar, ($uangPhk && $uangPhk->pesangon > 0) ? $uangPhk->pesangon : 0);

        if ($uangPhk && $uangPhk->pot_cuti_jumlah > 0) {
            $si->setCellValue('G' . $bar, 'Potongan Cuti ' . ($uangPhk->pot_cuti_hari > 0 ? $uangPhk->pot_cuti_hari . ' HR' : ''));
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, $uangPhk->pot_cuti_jumlah);
        } else {
            $si->setCellValue('G' . $bar, 'Keterlambatan Kehadiran');
            $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('I' . $bar, 0);
        }
        $si->getRowDimension($bar)->setRowHeight(14.0);

        $bar++; // 19: Sub-text Pesangon
        if ($uangPhk && $uangPhk->keterangan) {
            $si->setCellValue('A' . $bar, '(' . $uangPhk->keterangan . ')');
        }
        $si->getRowDimension($bar)->setRowHeight(14.0);

        $bar++; // 20: spacer
        $si->getRowDimension($bar)->setRowHeight(14.0);

        // Slot 4 onwards: Penghargaan Masa Kerja, Kompensasi, UANG PISAH, Penggantian Hak, Lain-lain
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
        // Uang Penggantian Hak
        $subtextCuti = ($uangPhk && $uangPhk->sisa_cuti_hari > 0) ? '(Sisa Cuti = ' . $uangPhk->sisa_cuti_hari . ' hari)' : null;
        $itemsLeft[] = [
            'label' => 'Uang Penggantian Hak',
            'nominal' => ($uangPhk && $uangPhk->sisa_cuti_jumlah > 0) ? $uangPhk->sisa_cuti_jumlah : 0,
            'subtext' => $subtextCuti,
        ];
        // Lain-lain di bawah Uang Penggantian Hak (sembunyikan jika nilainya 0)
        if ($uangPhk && $uangPhk->lain > 0) {
            $ketLainStr = !empty($uangPhk->ket_lain) ? ' (' . $uangPhk->ket_lain . ')' : '';
            $itemsLeft[] = [
                'label' => 'Lain-lain' . $ketLainStr,
                'nominal' => $uangPhk->lain,
                'subtext' => null,
            ];
        }

        // Potongan Lain-lain
        $potLainLabel = ($uangPhk && $uangPhk->pot_lain > 0 && !empty($uangPhk->ket_pot_lain))
            ? 'Lain-lain (' . $uangPhk->ket_pot_lain . ')'
            : 'Lain-lain';
        $potLainVal = ($uangPhk && $uangPhk->pot_lain > 0) ? $uangPhk->pot_lain : 0;
        $potLainRendered = false;

        foreach ($itemsLeft as $idx => $item) {
            if ($idx > 0) {
                $bar++; // 1 baris kosong pemisah
                $si->getRowDimension($bar)->setRowHeight(14.0);
            }

            $bar++;
            $si->setCellValue('A' . $bar, $item['label']);
            $si->setCellValueExplicit('D' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->getStyle('D' . $bar)->getAlignment()->setHorizontal('center');
            $si->setCellValue('E' . $bar, $item['nominal']);
            $si->getRowDimension($bar)->setRowHeight(14.0);

            if (!$potLainRendered) {
                $si->setCellValue('G' . $bar, $potLainLabel);
                $si->setCellValueExplicit('H' . $bar, '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $si->getStyle('H' . $bar)->getAlignment()->setHorizontal('center');
                $si->setCellValue('I' . $bar, $potLainVal);
                $potLainRendered = true;
            }

            if ($item['subtext']) {
                $bar++;
                $si->setCellValue('A' . $bar, $item['subtext']);
                $si->getRowDimension($bar)->setRowHeight(14.0);
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
        $si->getStyle('A' . $noteBar)->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true)->setUnderline(true);

        $si->setCellValue('I' . $noteBar, 'Head of HR Dept.');
        $si->getStyle('I' . $noteBar)->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I' . $noteBar)->getAlignment()->setHorizontal('center');
        $si->getRowDimension($noteBar)->setRowHeight(16.75);

        $si->setCellValue('A' . ($noteBar + 1), 'Hak Karyawan sesuai dengan Peraturan Pemerintah No.35 Tahun 2021 yang berlaku &');
        $si->getStyle('A' . ($noteBar + 1))->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);
        $si->getRowDimension($noteBar + 1)->setRowHeight(15.0);

        $si->setCellValue('A' . ($noteBar + 2), 'tercantum dalam Peraturan Perusahaan (PP) Pasal 31 (tiga puluh satu) tentang Hak Karyawan');
        $si->getStyle('A' . ($noteBar + 2))->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);
        $si->getRowDimension($noteBar + 2)->setRowHeight(15.0);

        $si->setCellValue('A' . ($noteBar + 3), 'serta Kompensasi yang sudah dapat Pengesahan dari Kementrian Ketenagakerjaan RI');
        $si->getStyle('A' . ($noteBar + 3))->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);

        $si->setCellValue('I' . ($noteBar + 3), 'Sri Erni.S');
        $si->getStyle('I' . ($noteBar + 3))->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I' . ($noteBar + 3))->getAlignment()->setHorizontal('center');
        $si->getRowDimension($noteBar + 3)->setRowHeight(15.0);

        $lastBoxRow = $noteBar + 4;
        $si->getRowDimension($lastBoxRow)->setRowHeight(15.0);
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