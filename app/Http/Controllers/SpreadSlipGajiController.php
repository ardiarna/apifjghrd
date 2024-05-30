<?php

namespace App\Http\Controllers;

use App\Repositories\PayrollRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadSlipGajiController extends Controller
{
    protected $repo;

    public function __construct(PayrollRepository $repo) {
        $this->repo = $repo;
    }

    public function cetak($tahun, $bulan, $jenis, $area) {
        // jenis   =>   1.ENGINEER   2.STAFF   3.NON STAF    4.ALL
        $arrBulan = ['Dessember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        // $px = 11/64;        //100 -> 103
        // $px = 10.5/64;      //100 -> 98
        $px = 10.7/64;      //100 -> 100;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Ebrima')->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repo->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
        ]);

        $spreadsheet->setActiveSheetIndex(0);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle($arrBulan[$bulan].' '.$tahun);
        $bar = 1;
        foreach ($dataDetails as $d) {
            $bar+=2;
            $si->setCellValue('B'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('B'.$bar.':O'.$bar);
            $si->getStyle('B'.$bar.':O'.$bar)->getFont()->setName('Narkisim')->setSize(12)->getColor()->setARGB('0070C0');
            $si->getRowDimension($bar)->setRowHeight(21);
            $bar++;
            $si->setCellValue('B'.$bar, 'SOVEREIGN PLAZA');
            $si->mergeCells('B'.$bar.':O'.$bar);
            $si->getStyle('B'.$bar.':O'.$bar)->getFont()->setSize(11);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('B'.$bar, 'TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430');
            $si->mergeCells('B'.$bar.':O'.$bar);
            $si->getStyle('A'.$bar.':P'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'SLIP GAJI KARYAWAN');
            $si->mergeCells('A'.$bar.':P'.$bar);
            $si->getStyle('A'.$bar.':P'.$bar)->getFont()->setSize(11)->setUnderline(TRUE);
            $si->getRowDimension($bar)->setRowHeight(21);
            $bar++;
            $si->setCellValue('A'.$bar, 'Periode : 26 '.$arrBulan[$bulan-1].' '.($bulan == '1' ? $tahun-1 : $tahun).' - 25 '.$arrBulan[$bulan].' '.$tahun);
            $si->mergeCells('A'.$bar.':P'.$bar);
            $si->getStyle('A'.($bar-1).':P'.$bar)->getFill()->setFillType('solid')->getStartColor()->setARGB('D9D9D9');
            $si->getStyle('A'.($bar-4).':O'.$bar)->getAlignment()->setHorizontal('center');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Nama');
            $si->setCellValue('B'.$bar, ': '.$d->karyawan->nama);
            $si->getRowDimension($bar)->setRowHeight(21);
            $bar++;
            $si->setCellValue('A'.$bar, 'Jabatan');
            $si->setCellValue('B'.$bar, ': '.$d->karyawan->jabatan->nama);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Divisi');
            $si->setCellValue('B'.$bar, ': '.$d->karyawan->divisi->nama);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->getStyle('A'.$bar.':P'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(12);
            $bar++;
            $si->setCellValue('A'.$bar, 'A. PENGHASILAN :');
            $si->setCellValue('J'.$bar, 'B. POTONGAN :');
            $si->getStyle('A'.$bar.':P'.$bar)->getFont()->setUnderline(TRUE)->getColor()->setARGB('0070C0');
            $si->getRowDimension($bar)->setRowHeight(21);
            $bar++;
            $si->setCellValue('A'.$bar, 'Gaji Pokok');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->gaji);
            $si->setCellValue('J'.$bar, 'Keterlambatan Kehadiran 25%');
            $si->setCellValue('K'.$bar, $d->pot_25_hari > 0 ? $d->uang_makan_harian/4 : '');
            $si->setCellValue('L'.$bar, 'x');
            $si->setCellValue('M'.$bar, $d->pot_25_hari > 0 ? $d->pot_25_hari : '');
            $si->setCellValue('N'.$bar, 'HR');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_25_jumlah > 0 ? $d->pot_25_jumlah : '');
            $si->getStyle('N'.$bar)->getAlignment()->setHorizontal('center');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Kenaikan Gaji');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, '');
            $si->setCellValue('J'.$bar, 'Pemakaian Telepon/Telkomsel');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_telepon > 0 ? $d->pot_telepon : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, $d->makan_harian == 'Y' ? 'U/makan & Transport' : 'Tunjangan U/makan & Transportasi');
            if ($d->makan_harian == 'Y') {
                $si->setCellValue('C'.$bar, $d->uang_makan_harian);
                $si->setCellValue('D'.$bar, 'x');
                $si->setCellValue('E'.$bar, $d->hari_makan);
                $si->setCellValue('F'.$bar, 'HR');
            }
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->uang_makan_jumlah);
            $si->setCellValue('J'.$bar, 'Pinjaman Kas');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_kas > 0 ? $d->pot_kas : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            if ($d->makan_harian == 'Y') {
                $ttgl = explode('-', $d->tanggal_awal);
                $ttgm = explode('-', $d->tanggal_akhir);
                $si->setCellValue('A'.$bar, '(Per: '.$ttgl[2]." ".$arrBulan[intval($ttgl[1])]."'".substr($ttgl[0], -2)." s/d ".$ttgm[2]." ".$arrBulan[intval($ttgm[1])]."'".substr($ttgm[0], -2).')');
            } else {
                $si->setCellValue('A'.$bar, 'Bulan '.$arrBulan[$bulan].' '.$tahun);
            }
            $si->setCellValue('J'.$bar, 'Pinjaman / Cicilan');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cicilan > 0 ? $d->pot_cicilan : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Lembur/Overtime');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, ($d->overtime_fjg+$d->overtime_cus) > 0 ? ($d->overtime_fjg+$d->overtime_cus) : '');
            $si->setCellValue('J'.$bar, 'BPJS Kesehatan');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bpjs > 0 ? $d->pot_bpjs : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Reimbursement Medical');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->medical > 0 ? $d->medical : '');
            $si->setCellValue('J'.$bar, 'Pemakaian Bensin');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bensin > 0 ? $d->pot_bensin : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Tunjangan Hari Raya');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->thr > 0 ? $d->thr : '');
            $si->setCellValue('J'.$bar, 'Unpaid Leave / Cuti Bersama');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cuti > 0 ? $d->pot_cuti : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Insentif');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->insentif > 0 ? $d->insentif : '');
            $si->setCellValue('J'.$bar, 'Lain-lain');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_lain > 0 ? $d->pot_lain : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Telkomsel');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->telkomsel > 0 ? $d->telkomsel : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('E'.$bar, 'Total A');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, '=SUM(H'.($bar-9).':H'.($bar-1).')');
            $si->setCellValue('L'.$bar, 'Total B');
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, '=SUM(P'.($bar-9).':P'.($bar-1).')');
            $si->getStyle('H'.$bar)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('P'.$bar)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->getStyle('A'.$bar.':P'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'PENERIMAAN BERSIH (A-B)');
            $si->mergeCells('A'.$bar.':G'.$bar);
            $si->getStyle('G'.($bar-11).':G'.($bar-1))->getAlignment()->setHorizontal('center');
            $si->getStyle('A'.$bar.':G'.$bar)->getAlignment()->setHorizontal('right');
            $si->setCellValue('I'.$bar, 'Rp');
            $si->setCellValue('J'.$bar, '=H'.($bar-2).'-P'.($bar-2));
            $si->getStyle('I'.$bar.':J'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('A'.$bar.':P'.$bar)->getFill()->setFillType('solid')->getStartColor()->setARGB('D9D9D9');
            $si->getStyle('A'.$bar.':P'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('K'.$bar, 'Head of HR Dept.');
            $si->mergeCells('K'.$bar.':O'.$bar);
            $si->getStyle('K'.$bar.':O'.$bar)->getFont()->setName('Gisha');
            $si->getStyle('K'.$bar.':O'.$bar)->getAlignment()->setHorizontal('center');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('K'.$bar, 'Sri Erni.S');
            $si->mergeCells('K'.$bar.':O'.$bar);
            $si->getStyle('K'.$bar.':O'.$bar)->getFont()->setName('Gisha');
            $si->getStyle('K'.$bar.':O'.$bar)->getAlignment()->setHorizontal('center');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->getStyle('A'.($bar-26).':P'.$bar)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
        }
        $si->getStyle('C1:C'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('H1:H'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('J1:K'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('P1:P'.$bar)->getNumberFormat()->setFormatCode('#,##0');

        $si->getColumnDimension('A')->setWidth(60*$px);
        $si->getColumnDimension('B')->setWidth(63*$px);
        $si->getColumnDimension('C')->setWidth(57*$px);
        $si->getColumnDimension('D')->setWidth(13*$px);
        $si->getColumnDimension('E')->setWidth(23*$px);
        $si->getColumnDimension('F')->setWidth(27*$px);
        $si->getColumnDimension('G')->setWidth(21*$px);
        $si->getColumnDimension('H')->setWidth(83*$px);
        $si->getColumnDimension('I')->setWidth(19*$px);
        $si->getColumnDimension('J')->setWidth(172*$px);
        $si->getColumnDimension('K')->setWidth(52*$px);
        $si->getColumnDimension('L')->setWidth(18*$px);
        $si->getColumnDimension('M')->setWidth(18*$px);
        $si->getColumnDimension('N')->setWidth(16*$px);
        $si->getColumnDimension('O')->setWidth(25*$px);
        $si->getColumnDimension('P')->setWidth(103*$px);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="SLIP_GAJI_'.strtoupper($arrBulan[$bulan]).'_'.substr($tahun, -2). '.xlsx"');
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
