<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\PotonganRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SpreadSlipGajiController extends Controller
{
    protected $repo, $rpArea, $rpPotongan;

    public function __construct(PayrollRepository $repo, AreaRepository $rpArea, PotonganRepository $rpPotongan) {
        $this->repo = $repo;
        $this->rpArea = $rpArea;
        $this->rpPotongan = $rpPotongan;
    }

    public function cetak($tahun, $bulan, $jenis, $area) {
        // jenis   =>   1.ENGINEER   2.STAFF   3.NON STAF    4.ALL
        $arrBulan = ['Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        // $px = 11/64;        //100 -> 103
        // $px = 10.5/64;      //100 -> 98
        $px = 10.7/64;      //100 -> 100;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Ebrima')->setSize(10)->setBold(TRUE);

        if($area == 'all') {
            $namaArea = '';
        } else {
            $dArea = $this->rpArea->findById($area);
            $namaArea = $dArea->nama.'_';
        }

        $dataDetails = $this->repo->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
        ]);

        $dataPotongans = $this->rpPotongan->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);
        $potongans = array();
        foreach ($dataPotongans as $d) {
            if($d->keterangan != '') {
                $potongans[$d->karyawan_id][$d->jenis][] = $d->keterangan;
            }
        }

        $sheetIndex = -1;
        foreach ($dataDetails as $i => $d) {
            if ($i % 2 == 0) {
                $sheetIndex++;
                if ($sheetIndex == 0) {
                    $si = $spreadsheet->getActiveSheet();
                } else {
                    $si = $spreadsheet->createSheet();
                }
                $start = str_pad($sheetIndex * 2 + 1, 2, "0", STR_PAD_LEFT);
                $end = str_pad($sheetIndex * 2 + 2, 2, "0", STR_PAD_LEFT);
                $si->setTitle("{$start}-{$end}");
                $spreadsheet->setActiveSheetIndex($sheetIndex);
                $si->setShowGridlines(false);
                $si->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $si->getPageSetup()->setFitToWidth(1);
                $si->getPageSetup()->setFitToHeight(0);
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
            }
            $bar = ($i % 2 == 0) ? 1 : 35;
            $si->setCellValue('B'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('B'.$bar.':O'.$bar);
            $si->getStyle('B'.$bar.':O'.$bar)->getFont()->setName('Narkisim')->setSize(12)->getColor()->setARGB('0070C0');
            $si->getRowDimension($bar)->setRowHeight(21);

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/logo_fjg.jpg'));
            $drawing->setHeight(44.16);
            $drawing->setCoordinates('N'.$bar);
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(16);
            $drawing->setWorksheet($si);

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
            $si->setCellValue('H'.$bar, $d->kenaikan_gaji > 0 ? $d->kenaikan_gaji : '');
            $si->setCellValue('J'.$bar, 'Pemakaian Telepon/Telkomsel');
            if(isset($potongans[$d->karyawan->id]['TP'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['TP']).')');
            }
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
            if(isset($potongans[$d->karyawan->id]['KS'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['KS']).')');
            }
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
            if(isset($potongans[$d->karyawan->id]['CC'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['CC']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cicilan > 0 ? $d->pot_cicilan : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Lembur/Overtime');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, ($d->overtime_fjg+$d->overtime_cus) > 0 ? ($d->overtime_fjg+$d->overtime_cus) : '');
            $si->setCellValue('J'.$bar, 'BPJS Kesehatan');
            if(isset($potongans[$d->karyawan->id]['BP'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['BP']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bpjs > 0 ? $d->pot_bpjs : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Reimbursement Medical');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->medical > 0 ? $d->medical : '');
            $si->setCellValue('J'.$bar, 'Pemakaian Bensin');
            if(isset($potongans[$d->karyawan->id]['BN'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['BN']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bensin > 0 ? $d->pot_bensin : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Tunjangan Hari Raya');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->thr > 0 ? $d->thr : '');
            $si->setCellValue('J'.$bar, 'Unpaid Leave / Cuti Bersama');
            if(isset($potongans[$d->karyawan->id]['UL'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['UL']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cuti_jumlah > 0 ? $d->pot_cuti_jumlah : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Insentif');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->insentif > 0 ? $d->insentif : '');
            if(isset($potongans[$d->karyawan->id]['KJ'])) {
                $si->setCellValue('J'.$bar, 'Kompensasi ('.implode(', ', $potongans[$d->karyawan->id]['KJ']).')');
            } else {
                $si->setCellValue('J'.$bar, 'Kompensasi');
            }
            if( $d->pot_kompensasi_jam > 0) {
                $si->setCellValue('N'.$bar, $d->pot_kompensasi_jam.' JM');
                $si->getStyle('N'.$bar)->getAlignment()->setHorizontal('right');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_kompensasi_jumlah > 0 ? $d->pot_kompensasi_jumlah : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Telkomsel');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->telkomsel > 0 ? $d->telkomsel : '');
            $si->setCellValue('J'.$bar, 'Lain-lain');
            if(isset($potongans[$d->karyawan->id]['LL'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['LL']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_lain > 0 ? $d->pot_lain : '');
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

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/stempel_fjg.png'));
            $drawing->setHeight(61,44);
            $drawing->setCoordinates('K'.$bar);
            $drawing->setOffsetX(31);
            $drawing->setOffsetY(15);
            $drawing->setWorksheet($si);

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/stempel_ttd.png'));
            $drawing->setHeight(74,88);
            $drawing->setCoordinates('K'.$bar);
            $drawing->setOffsetX(-27);
            $drawing->setOffsetY(7);
            $drawing->setWorksheet($si);

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
            $si->getStyle('C1:C'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('H1:H'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('J1:K'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('P1:P'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="SLIP_GAJI_'.($jenis == '1' ? 'ENGINEERING_' : ($jenis == '2' ? 'STAF_' : ($jenis == '3' ? 'NON_STAF_' : ''))).str_replace(' ', '_', $namaArea).strtoupper($arrBulan[$bulan]).'_'.substr($tahun, -2). '.xlsx"');
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

    public function perKaryawan($karyawan_id, $tahun, $bulans) {
        $bulans = explode('-', $bulans);
        $arrBulan = ['Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $px = 10.7/64;      //100 -> 100;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Ebrima')->setSize(10)->setBold(TRUE);


        $dataDetails = $this->repo->findAll([
            'tahun' => $tahun,
            'karyawan_id' => $karyawan_id,
        ]);

        $dataPotongans = $this->rpPotongan->findAll([
            'tahun' => $tahun,
            'karyawan_id' => $karyawan_id,
        ]);
        $potongans = array();
        foreach ($dataPotongans as $d) {
            if($d->keterangan != '') {
                $potongans[$d->bulan][$d->jenis][] = $d->keterangan;
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle($tahun);
        $bar = 1;
        $namaKaryawan = '';
        foreach ($dataDetails as $d) {
            if (!in_array($d->bulan, $bulans)) {
                continue;
            }
            $namaKaryawan = $d->karyawan->nama;
            $bar+=2;
            $si->setCellValue('B'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('B'.$bar.':O'.$bar);
            $si->getStyle('B'.$bar.':O'.$bar)->getFont()->setName('Narkisim')->setSize(12)->getColor()->setARGB('0070C0');
            $si->getRowDimension($bar)->setRowHeight(21);

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/logo_fjg.jpg'));
            $drawing->setHeight(44.16);
            $drawing->setCoordinates('N'.$bar);
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(16);
            $drawing->setWorksheet($si);

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
            $si->setCellValue('A'.$bar, 'Periode : 26 '.$arrBulan[$d->bulan-1].' '.($d->bulan == '1' ? $tahun-1 : $tahun).' - 25 '.$arrBulan[$d->bulan].' '.$tahun);
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
            $si->setCellValue('H'.$bar, $d->kenaikan_gaji > 0 ? $d->kenaikan_gaji : '');
            $si->setCellValue('J'.$bar, 'Pemakaian Telepon/Telkomsel');
            if(isset($potongans[$d->bulan]['TP'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['TP']).')');
            }
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
            if(isset($potongans[$d->bulan]['KS'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['KS']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_kas > 0 ? $d->pot_kas : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            if ($d->makan_harian == 'Y') {
                $ttgl = explode('-', $d->tanggal_awal);
                $ttgm = explode('-', $d->tanggal_akhir);
                $si->setCellValue('A'.$bar, '(Per: '.$ttgl[2]." ".$arrBulan[intval($ttgl[1])]."'".substr($ttgl[0], -2)." s/d ".$ttgm[2]." ".$arrBulan[intval($ttgm[1])]."'".substr($ttgm[0], -2).')');
            } else {
                $si->setCellValue('A'.$bar, 'Bulan '.$arrBulan[$d->bulan].' '.$tahun);
            }
            $si->setCellValue('J'.$bar, 'Pinjaman / Cicilan');
            if(isset($potongans[$d->bulan]['CC'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['CC']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cicilan > 0 ? $d->pot_cicilan : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Lembur/Overtime');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, ($d->overtime_fjg+$d->overtime_cus) > 0 ? ($d->overtime_fjg+$d->overtime_cus) : '');
            $si->setCellValue('J'.$bar, 'BPJS Kesehatan');
            if(isset($potongans[$d->bulan]['BP'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['BP']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bpjs > 0 ? $d->pot_bpjs : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Reimbursement Medical');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->medical > 0 ? $d->medical : '');
            $si->setCellValue('J'.$bar, 'Pemakaian Bensin');
            if(isset($potongans[$d->bulan]['BN'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['BN']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_bensin > 0 ? $d->pot_bensin : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Tunjangan Hari Raya');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->thr > 0 ? $d->thr : '');
            $si->setCellValue('J'.$bar, 'Unpaid Leave / Cuti Bersama');
            if(isset($potongans[$d->bulan]['UL'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->bulan]['UL']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_cuti_jumlah > 0 ? $d->pot_cuti_jumlah : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Insentif');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->insentif > 0 ? $d->insentif : '');
            if(isset($potongans[$d->bulan]['KJ'])) {
                $si->setCellValue('J'.$bar, 'Kompensasi ('.implode(', ', $potongans[$d->bulan]['KJ']).')');
            } else {
                $si->setCellValue('J'.$bar, 'Kompensasi');
            }
            if( $d->pot_kompensasi_jam > 0) {
                $si->setCellValue('N'.$bar, $d->pot_kompensasi_jam.' JM');
                $si->getStyle('N'.$bar)->getAlignment()->setHorizontal('right');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_kompensasi_jumlah > 0 ? $d->pot_kompensasi_jumlah : '');
            $si->getRowDimension($bar)->setRowHeight(17);
            $bar++;
            $si->setCellValue('A'.$bar, 'Telkomsel');
            $si->setCellValue('G'.$bar, '=');
            $si->setCellValue('H'.$bar, $d->telkomsel > 0 ? $d->telkomsel : '');
            $si->setCellValue('J'.$bar, 'Lain-lain');
            if(isset($potongans[$d->karyawan->id]['LL'])) {
                $si->setCellValue('K'.$bar, '('.implode(', ', $potongans[$d->karyawan->id]['LL']).')');
            }
            $si->setCellValue('O'.$bar, '=');
            $si->setCellValue('P'.$bar, $d->pot_lain > 0 ? $d->pot_lain : '');
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

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/stempel_fjg.png'));
            $drawing->setHeight(61,44);
            $drawing->setCoordinates('K'.$bar);
            $drawing->setOffsetX(31);
            $drawing->setOffsetY(15);
            $drawing->setWorksheet($si);

            $drawing = new Drawing();
            $drawing->setPath(public_path('images/stempel_ttd.png'));
            $drawing->setHeight(74,88);
            $drawing->setCoordinates('K'.$bar);
            $drawing->setOffsetX(-27);
            $drawing->setOffsetY(7);
            $drawing->setWorksheet($si);

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
        header('Content-Disposition: attachment;filename="SLIP_GAJI_'.substr($tahun, -2).'_'.str_replace(' ', '_', $namaKaryawan). '.xlsx"');
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
