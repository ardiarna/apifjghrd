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
        $karyawan = Karyawan::with(['phk', 'uangPhk', 'divisi', 'jabatan', 'area'])->find($karyawan_id);
        if (!$karyawan) {
            return response()->json(['status' => false, 'message' => 'Data Karyawan tidak ditemukan']);
        }

        $uangPhk = UangPhk::where('karyawan_id', $karyawan_id)->first();
        $payrollPhk = PayrollPhk::where('karyawan_id', $karyawan_id)->first();
        $phk = Phk::where('karyawan_id', $karyawan_id)->first();

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
            11 => 17.5, 12 => 16.75, 13 => 16.75, 14 => 16.75, 15 => 19.75,
            16 => 14.0, 17 => 14.0, 18 => 14.0, 19 => 14.0, 20 => 14.0,
            21 => 14.0, 22 => 14.0, 23 => 14.0, 24 => 14.0, 25 => 14.0,
            26 => 14.0, 27 => 16.0, 28 => 16.75, 29 => 15.0, 30 => 15.0,
            31 => 15.0, 32 => 15.0
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

        // Row 13: Gaji Pokok & Pinjaman Kas
        $periodGaji = ($payrollPhk && $payrollPhk->keterangan) ? ' Per : ' . $payrollPhk->keterangan : '';
        $si->setCellValue('A13', 'Gaji Pokok' . $periodGaji);
        $si->setCellValueExplicit('D13', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('E13', ($payrollPhk && $payrollPhk->gaji > 0) ? $payrollPhk->gaji : 0);

        $si->setCellValue('G13', 'Pinjaman Kas');
        $si->setCellValueExplicit('H13', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('I13', ($uangPhk && $uangPhk->pot_kas > 0) ? $uangPhk->pot_kas : 0);

        // Row 15: U/Makan & Transportasi | Pinjaman Jangka Panjang
        $si->setCellValue('A15', 'U/Makan & Transportasi');
        $si->setCellValueExplicit('D15', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('E15', ($payrollPhk && $payrollPhk->uang_makan_jumlah > 0) ? $payrollPhk->uang_makan_jumlah : 0);

        $si->setCellValue('G15', 'Pinjaman Jangka Panjang');
        $si->setCellValueExplicit('H15', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('I15', ($uangPhk && $uangPhk->pot_cicilan > 0) ? $uangPhk->pot_cicilan : 0);

        // Row 16: Sub-text U/Makan
        if ($payrollPhk && $payrollPhk->hari_makan > 0) {
            $si->setCellValue('A16', '(' . $payrollPhk->hari_makan . ' x ' . $payrollPhk->uang_makan_harian . ')');
        }

        // Row 18: Uang Pesangon | Potongan Kehadiran / Cuti
        $si->setCellValue('A18', 'Uang Pesangon');
        $si->setCellValueExplicit('D18', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('E18', ($uangPhk && $uangPhk->pesangon > 0) ? $uangPhk->pesangon : 0);

        if ($uangPhk && $uangPhk->pot_cuti_jumlah > 0) {
            $si->setCellValue('G18', 'Potongan Cuti ' . ($uangPhk->pot_cuti_hari > 0 ? $uangPhk->pot_cuti_hari . ' HR' : ''));
            $si->setCellValueExplicit('H18', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('I18', $uangPhk->pot_cuti_jumlah);
        } else {
            $si->setCellValue('G18', 'Keterlambatan Kehadiran');
            $si->setCellValueExplicit('H18', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('I18', 0);
        }

        // Row 19: Sub-text Pesangon
        if ($uangPhk && $uangPhk->keterangan) {
            $si->setCellValue('A19', '(' . $uangPhk->keterangan . ')');
        }

        // Row 21: Penghargaan Masa Kerja / Kompensasi / Uang Pisah / Lain | Lain-lain
        $nominalMasaKerja = ($uangPhk && $uangPhk->masa_kerja > 0) ? $uangPhk->masa_kerja : 0;
        $nominalKompensasi = ($uangPhk && $uangPhk->kompensasi > 0) ? $uangPhk->kompensasi : 0;
        $nominalUangPisah = ($uangPhk && $uangPhk->uang_pisah > 0) ? $uangPhk->uang_pisah : 0;
        $totalPenghargaan = $nominalMasaKerja + $nominalKompensasi + $nominalUangPisah;

        if ($totalPenghargaan > 0) {
            $labels = [];
            if ($nominalMasaKerja > 0) $labels[] = 'Uang Penghargaan Masa Kerja';
            if ($nominalKompensasi > 0) $labels[] = 'Kompensasi';
            if ($nominalUangPisah > 0) $labels[] = 'Uang Pisah';
            $si->setCellValue('A21', implode(' / ', $labels));
            $si->setCellValueExplicit('D21', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('E21', $totalPenghargaan);
        } elseif ($uangPhk && $uangPhk->lain > 0) {
            $si->setCellValue('A21', 'Lain-lain' . ($uangPhk->ket_lain ? ' (' . $uangPhk->ket_lain . ')' : ''));
            $si->setCellValueExplicit('D21', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('E21', $uangPhk->lain);
        } else {
            $si->setCellValue('A21', 'Uang Penghargaan Masa Kerja');
            $si->setCellValueExplicit('D21', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('E21', 0);
        }

        if ($uangPhk && $uangPhk->pot_lain > 0) {
            $si->setCellValue('G21', 'Lain-lain (' . $uangPhk->ket_pot_lain . ')');
            $si->setCellValueExplicit('H21', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('I21', $uangPhk->pot_lain);
        } else {
            $si->setCellValue('G21', 'Lain-lain');
            $si->setCellValueExplicit('H21', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $si->setCellValue('I21', 0);
        }

        // Row 23: Uang Penggantian Hak
        $si->setCellValue('A23', 'Uang Penggantian Hak');
        $si->setCellValueExplicit('D23', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->setCellValue('E23', ($uangPhk && $uangPhk->sisa_cuti_jumlah > 0) ? $uangPhk->sisa_cuti_jumlah : 0);

        // Row 24: Sub-text Penggantian Hak
        if ($uangPhk && $uangPhk->sisa_cuti_hari > 0) {
            $si->setCellValue('A24', '(Sisa Cuti = ' . $uangPhk->sisa_cuti_hari . ' hari)');
        }
        $si->getStyle('E24')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('I24')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Row 25: Total A & Total B
        $si->setCellValue('C25', 'Total A');
        $si->getStyle('C25')->getAlignment()->setHorizontal('right');
        $si->setCellValueExplicit('D25', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('D25')->getAlignment()->setHorizontal('center');
        $si->setCellValue('E25', '=SUM(E13:E24)');

        $si->setCellValue('G25', 'Total B');
        $si->getStyle('G25')->getAlignment()->setHorizontal('right');
        $si->setCellValueExplicit('H25', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H25')->getAlignment()->setHorizontal('center');
        $si->setCellValue('I25', '=SUM(I13:I24)');

        // Row 26: Bottom line before Penerimaan Bersih
        $si->getStyle('A26:I26')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Row 27: PENERIMAAN BERSIH (A-B) (Background #BFBFBF)
        $si->setCellValue('G27', 'PENERIMAAN BERSIH (A-B)');
        $si->getStyle('G27')->getFont()->setName('Ebrima')->setSize(11)->setBold(true);
        $si->getStyle('G27')->getAlignment()->setHorizontal('right');

        $si->setCellValueExplicit('H27', '=', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $si->getStyle('H27')->getFont()->setName('Ebrima')->setSize(11)->setBold(true);
        $si->getStyle('H27')->getAlignment()->setHorizontal('center');

        $si->setCellValue('I27', '=E25-I25');
        $si->getStyle('I27')->getFont()->setName('Ebrima')->setSize(11)->setBold(true)->getColor()->setARGB('FF0000');

        $si->getStyle('A27:I27')->getFill()->setFillType('solid')->getStartColor()->setARGB('BFBFBF');
        $si->getStyle('A27:I27')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A27:I27')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Row 28-31: Note & Signatures
        $si->setCellValue('A28', 'Note :');
        $si->getStyle('A28')->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true)->setUnderline(true);

        $si->setCellValue('I28', 'Head of HR Dept.');
        $si->getStyle('I28')->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I28')->getAlignment()->setHorizontal('center');

        $si->setCellValue('A29', 'Hak Karyawan sesuai dengan Peraturan Pemerintah No.35 Tahun 2021 yang berlaku &');
        $si->getStyle('A29')->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);

        $si->setCellValue('A30', 'tercantum dalam Peraturan Perusahaan (PP) Pasal 31 (tiga puluh satu) tentang Hak Karyawan');
        $si->getStyle('A30')->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);

        $si->setCellValue('A31', 'serta Kompensasi yang sudah dapat Pengesahan dari Kementrian Ketenagakerjaan RI');
        $si->getStyle('A31')->getFont()->setName('Malgun Gothic')->setSize(10)->setBold(true);

        $si->setCellValue('I31', 'Sri Erni.S');
        $si->getStyle('I31')->getFont()->setName('Gisha')->setSize(10)->setBold(true);
        $si->getStyle('I31')->getAlignment()->setHorizontal('center');

        // Outer borders for the table box (Col A left, Col I right, Row 3 top, Row 5 bottom, Row 11 bottom, Row 32 bottom)
        $si->getStyle('A3:I3')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A5:I5')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A11:I11')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $si->getStyle('A32:I32')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        for ($r = 3; $r <= 32; $r++) {
            $si->getStyle('A'.$r)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('I'.$r)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        }

        // Center equal signs
        foreach ([13, 15, 18, 21, 23] as $r) {
            $si->getStyle('D'.$r)->getAlignment()->setHorizontal('center');
        }
        foreach ([13, 15, 18, 21] as $r) {
            $si->getStyle('H'.$r)->getAlignment()->setHorizontal('center');
        }

        // Number Formats
        $si->getStyle('E13:E25')->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('I13:I27')->getNumberFormat()->setFormatCode('#,##0');

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
            $drawing2->setCoordinates('I28');
            $drawing2->setOffsetX(40);
            $drawing2->setOffsetY(15);
            $drawing2->setWorksheet($si);
        }

        if (file_exists(public_path('images/stempel_ttd.png'))) {
            $drawing3 = new Drawing();
            $drawing3->setPath(public_path('images/stempel_ttd.png'));
            $drawing3->setHeight(74.88);
            $drawing3->setCoordinates('I28');
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