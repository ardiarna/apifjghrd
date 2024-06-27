<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadsheetController extends Controller
{
    protected $repoHeader, $repoDetail, $repoOncall;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
    }

    public function listPayroll($tahun) {
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'AA';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataTahunLalu = $this->repoHeader->findAll(['tahun' => ($tahun-1), 'bulan' => '12']);
        if($dataTahunLalu->isEmpty()) {
            $headers[0]['overtime'] = 0;
            $headers[0]['medical'] = 0;
        } else {
            $headers[0]['overtime'] = $dataTahunLalu[0]->overtime_fjg + $dataTahunLalu[0]->overtime_cus;
            $headers[0]['medical'] = $dataTahunLalu[0]->medical;
        }
        $dataHeaders = $this->repoHeader->findAll(['tahun' => $tahun]);
        foreach ($dataHeaders as $dh) {
            $headers[$dh->bulan]['overtime'] = $dh->overtime_fjg + $dh->overtime_cus;
            $headers[$dh->bulan]['medical'] = $dh->medical;
        }
        $i = 0;
        foreach ($dataHeaders as $dh) {
            if($i > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle($arrBulan[$dh->bulan]."'".substr($dh->tahun, -2));
            $si->freezePane('C6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'PAYROLL '.$arrBulan[$dh->bulan].' '.$dh->tahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'MASA KERJA');
            $si->mergeCells('D'.$bar.':D'.($bar+2));
            $si->setCellValue('E'.$bar, 'GAJI / UPAH IDR');
            $si->mergeCells('E'.$bar.':E'.($bar+2));
            $si->setCellValue('F'.$bar, 'U/MAKAN & TRANSPORTASI');
            $si->mergeCells('F'.$bar.':H'.$bar);
            $si->setCellValue('I'.$bar, 'TUNJANGAN LAIN');
            $si->mergeCells('I'.$bar.':P'.$bar);
            $si->setCellValue('Q'.$bar, 'POTONGAN');
            $si->mergeCells('Q'.$bar.':Y'.$bar);
            $si->setCellValue('Z'.$bar, 'TOTAL DITERIMA IDR');
            $si->mergeCells('Z'.$bar.':Z'.($bar+2));
            $si->setCellValue('AA'.$bar, 'KETERANGAN');
            $si->mergeCells('AA'.$bar.':AA'.($bar+2));
            $bar++;
            $si->setCellValue('F'.$bar, 'HR');
            $si->mergeCells('F'.$bar.':F'.($bar+1));
            $si->setCellValue('G'.$bar, '@ HARI IDR');
            $si->mergeCells('G'.$bar.':G'.($bar+1));
            $si->setCellValue('H'.$bar, 'JUMLAH IDR');
            $si->mergeCells('H'.$bar.':H'.($bar+1));
            $si->setCellValue('I'.$bar, 'OVERTIME');
            $si->mergeCells('I'.$bar.':J'.$bar);
            $si->setCellValue('K'.$bar, 'MEDICAL IDR');
            $si->mergeCells('K'.$bar.':K'.($bar+1));
            $si->setCellValue('L'.$bar, 'THR IDR');
            $si->mergeCells('L'.$bar.':L'.($bar+1));
            $si->setCellValue('M'.$bar, 'BONUS IDR');
            $si->mergeCells('M'.$bar.':M'.($bar+1));
            $si->setCellValue('N'.$bar, 'INSENTIF IDR');
            $si->mergeCells('N'.$bar.':N'.($bar+1));
            $si->setCellValue('O'.$bar, 'TELKOMSEL IDR');
            $si->mergeCells('O'.$bar.':O'.($bar+1));
            $si->setCellValue('P'.$bar, 'LAIN-LAIN IDR');
            $si->mergeCells('P'.$bar.':P'.($bar+1));
            $si->setCellValue('Q'.$bar, '25%');
            $si->mergeCells('Q'.$bar.':R'.$bar);
            $si->setCellValue('S'.$bar, 'TELP. IDR');
            $si->mergeCells('S'.$bar.':S'.($bar+1));
            $si->setCellValue('T'.$bar, 'BENSIN IDR');
            $si->mergeCells('T'.$bar.':T'.($bar+1));
            $si->setCellValue('U'.$bar, 'PINJAMAN');
            $si->mergeCells('U'.$bar.':V'.$bar);
            $si->setCellValue('W'.$bar, 'BPJS (KIS) IDR');
            $si->mergeCells('W'.$bar.':W'.($bar+1));
            $si->setCellValue('X'.$bar, 'UNPAID LEAVE');
            $si->mergeCells('X'.$bar.':X'.($bar+1));
            $si->setCellValue('Y'.$bar, 'LAIN-LAIN IDR');
            $si->mergeCells('Y'.$bar.':Y'.($bar+1));
            $bar++;
            $si->setCellValue('I'.$bar, 'FRATEKINDO');
            $si->setCellValue('J'.$bar, 'CUSTOMER');
            $si->setCellValue('Q'.$bar, 'HR');
            $si->setCellValue('R'.$bar, 'JUMLAH IDR');
            $si->setCellValue('U'.$bar, 'KAS');
            $si->setCellValue('V'.$bar, 'CICILAN');
            $bar++;
            $dataDetails = $this->repoDetail->findAll(['header_id' => $dh->id]);
            $details = array();
            foreach ($dataDetails as $dt) {
                $details[$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->id] = $dt;
            }
            $nomor = 1;
            foreach ($details as $staf => $areas) {
                if($staf == 'N') {
                    $si->setCellValue('B'.$bar, 'NON STAF :');
                    $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                    $bar++;
                }
                foreach ($areas as $area => $ids) {
                    if($staf == 'Y') {
                        $si->setCellValue('B'.$bar, $area.' :');
                        $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                        $bar++;
                    }
                    foreach ($ids as $id => $d) {
                        $si->setCellValue('A'.$bar, $nomor);
                        $si->setCellValue('B'.$bar, $d->karyawan->nama);
                        $si->setCellValue('C'.$bar, $d->karyawan->jabatan->nama);
                        $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_masuk)));
                        $si->setCellValue('E'.$bar, ($d->gaji + $d->kenaikan_gaji));
                        $si->setCellValue('F'.$bar, $d->hari_makan);
                        $si->setCellValue('G'.$bar, $d->uang_makan_harian);
                        $si->setCellValue('H'.$bar, $d->uang_makan_jumlah);
                        $si->setCellValue('I'.$bar, $d->overtime_fjg);
                        $si->setCellValue('J'.$bar, $d->overtime_cus);
                        $si->setCellValue('K'.$bar, $d->medical);
                        $si->setCellValue('L'.$bar, $d->thr);
                        $si->setCellValue('M'.$bar, $d->bonus);
                        $si->setCellValue('N'.$bar, $d->insentif);
                        $si->setCellValue('O'.$bar, $d->telkomsel);
                        $si->setCellValue('P'.$bar, $d->lain);
                        $si->setCellValue('Q'.$bar, $d->pot_25_hari);
                        $si->setCellValue('R'.$bar, $d->pot_25_jumlah);
                        $si->setCellValue('S'.$bar, $d->pot_telepon);
                        $si->setCellValue('T'.$bar, $d->pot_bensin);
                        $si->setCellValue('U'.$bar, $d->pot_kas);
                        $si->setCellValue('V'.$bar, $d->pot_cicilan);
                        $si->setCellValue('W'.$bar, $d->pot_bpjs);
                        $si->setCellValue('X'.$bar, $d->pot_cuti);
                        $si->setCellValue('Y'.$bar, $d->pot_lain);
                        $si->setCellValue('Z'.$bar, $d->total_diterima);
                        $si->setCellValue('AA'.$bar, $d->keterangan);
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $si->getStyle('A1:A2')->getFont()->setName('Arial')->setSize(14)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('Q3:Y'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('Z3:Z'.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('A3:'.$kol_akhir.'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol_akhir.'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);

            $si->setCellValue('A'.$bar, 'TOTAL PAYROLL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            $si->setCellValue('E'.$bar, '=SUM(E6:E'.($bar-1).')');
            $si->setCellValue('H'.$bar, '=SUM(H6:H'.($bar-1).')');
            for ($k = 8; $k <= 15; $k++) { // I s/d P
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            for ($k = 17; $k <= 25; $k++) { // R s/d Z
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('D6:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E6:Z'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $barOT = $bar;
            $barTTD = $bar;
            $barKet = $bar;

            $barOT += 2;
            $si->setCellValue('F'.$barOT, 'OVERTIME & ON CALL CUSTOMERS :');
            $si->mergeCells('F'.$barOT.':I'.$barOT);
            $si->setCellValue('J'.$barOT, 'OVERTIME & MEDICAL :');
            $si->mergeCells('J'.$barOT.':L'.$barOT);
            $si->getStyle('F'.$barOT.':L'.$barOT)->getAlignment()->setHorizontal('center')->setVertical('center');
            $si->getStyle('F'.$barOT.':L'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $barOT++;
            $barOTAwal = $barOT;
            $dataOncalls = $this->repoOncall->findAll(['tahun' => $dh->tahun, 'bulan' => $dh->bulan]);
            foreach ($dataOncalls as $r) {
                $si->setCellValue('F'.$barOT, $r->customer->nama);
                $si->mergeCells('F'.$barOT.':H'.$barOT);
                $si->setCellValue('I'.$barOT, $r->jumlah);
                $barOT++;
            }
            $barOM = $barOTAwal;
            $now = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['overtime'] : 0;
            $before = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['overtime'] : 0;
            if($now == 0 && $before == 0) {
                $statusOT = 'TURUN';
                $persenOT = 0;
            } else if($before == 0) {
                $statusOT = 'NAIK';
                $persenOT = 100;
            } else {
                $persenOT = ($now - $before) / $before * 100;
                if($persenOT > 0) {
                    $statusOT = 'NAIK';
                } else {
                    $statusOT = 'TURUN';
                }
            }
            $nowMed = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['medical'] : 0;
            $beforeMed = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['medical'] : 0;
            if($nowMed == 0 && $beforeMed == 0) {
                $statusMed = 'TURUN';
                $persenMed = 0;
            } else if($beforeMed == 0) {
                $statusMed = 'NAIK';
                $persenMed = 100;
            } else {
                $persenMed = ($nowMed - $beforeMed) / $beforeMed * 100;
                if($persenMed > 0) {
                    $statusMed = 'NAIK';
                } else {
                    $statusMed = 'TURUN';
                }
            }
            $si->setCellValue('J'.$barOM, 'OVERTIME');
            $si->setCellValue('K'.$barOM, $statusOT);
            $si->setCellValue('L'.$barOM, number_format(abs($persenOT),2).'%');
            $barOM++;
            $si->setCellValue('J'.$barOM, 'MEDICAL');
            $si->setCellValue('K'.$barOM, $statusMed);
            $si->setCellValue('L'.$barOM, number_format(abs($persenMed),2).'%');
            $si->getStyle('L'.($barOM-1).':L'.$barOM)->getAlignment()->setHorizontal('right');
            $barOM++;
            if($barOT < $barOM) {
                for ($k = $barOT; $k < $barOM; $k++) {
                    $si->setCellValue('F'.$k, '');
                    $si->mergeCells('F'.$k.':H'.$k);
                    $si->setCellValue('I'.$k, '');
                }
                $barOT = $k;
            }
            $si->setCellValue('F'.$barOT, 'JUMLAH');
            $si->mergeCells('F'.$barOT.':H'.$barOT);
            $si->setCellValue('I'.$barOT, '=SUM(I'.$barOTAwal.':I'.($barOT-1).')');
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('J'.$barOTAwal.':'.'L'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOT.':'.'I'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('J'.$barOT.':'.'L'.$barOT)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOT.':'.'I'.$barOT)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('I'.$barOTAwal.':'.'I'.$barOT)->getNumberFormat()->setFormatCode('#,##0');

            $barKet++;
            $si->setCellValue('B'.$barKet, 'U/MAKAN & TRANSPORTASI :');
            $si->mergeCells('B'.$barKet.':D'.$barKet);
            $si->getStyle('B'.$barKet)->getFont()->setUnderline(true)->setItalic(true);
            $si->getStyle('B'.$barKet)->getAlignment()->setHorizontal('center');
            $barKet++;
            $ttgl = explode('-', $dh->tanggal_awal);
            $ttgm = explode('-', $dh->tanggal_akhir);
            foreach ($details as $staf => $areas) {
                foreach ($areas as $area => $ids) {
                    if($staf == 'Y') {
                        $si->setCellValue('B'.$barKet, $area);
                        $si->setCellValue('C'.$barKet, "'= ".$ttgl[2]." ".substr($arrBulan[intval($ttgl[1])], 0, 3)."'".substr($ttgl[0], -2)." - ".$ttgm[2]." ".substr($arrBulan[intval($ttgm[1])], 0, 3)."'".substr($ttgm[0], -2));
                        $si->setCellValue('D'.$barKet, "'= STAFF");
                        $barKet++;
                    }
                }
            }

            $barTTD++;
            $si->setCellValue('P'.$barTTD, 'Diajukan Oleh :');
            $si->setCellValue('T'.$barTTD, 'Disetujui Oleh : ');
            $si->setCellValue('W'.$barTTD, 'Diterima Oleh : ');
            $si->getStyle('P'.$barTTD.':W'.$barTTD)->getAlignment()->setHorizontal('center');
            $barTTD += 3;
            $si->setCellValue('P'.$barTTD, 'Sri Erni.S');
            $si->setCellValue('T'.$barTTD, 'Alain Pierre Mignon');
            $si->setCellValue('W'.$barTTD, 'Harti Susilowati');
            $si->getStyle('P'.$barTTD.':W'.$barTTD)->getAlignment()->setHorizontal('center');
            $si->getStyle('P'.$barTTD.':W'.$barTTD)->getFont()->setUnderline(true);
            $barTTD++;
            $si->setCellValue('P'.$barTTD, 'Head of HRD');
            $si->setCellValue('T'.$barTTD, 'Presiden Direktur');
            $si->setCellValue('W'.$barTTD, 'Finance');
            $si->getStyle('P'.$barTTD.':W'.$barTTD)->getAlignment()->setHorizontal('center');
            $si->getStyle('P'.$barTTD.':W'.$barTTD)->getFont()->setBold(false);

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(210, Dimension::UOM_PIXELS);
            $si->getColumnDimension('E')->setWidth(100, Dimension::UOM_PIXELS);
            $si->getColumnDimension('F')->setWidth(30, Dimension::UOM_PIXELS);
            for ($k = 6; $k <= 15; $k++) { // G s/d P
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('Q')->setWidth(30, Dimension::UOM_PIXELS);
            for ($k = 17; $k <= 24; $k++) { // R s/d Y
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('Z')->setWidth(110, Dimension::UOM_PIXELS);
            $si->getColumnDimension('AA')->setWidth(110, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="LIST_PAYROLL_'.substr($tahun, -2). '.xlsx"');
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

    public function rekapGaji($tahun) {
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'X';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoDetail->findAll(['tahun' => $tahun]);
        $details = array();
        $dataKaryawan = array();
        $dataTHR = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id][$dt->bulan] = ($dt->gaji + $dt->kenaikan_gaji) - $dt->thr;
            $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            if($dt->thr > 0) {
                $dataTHR[$dt->tahun][$dt->karyawan->id] = $dt->thr;
            }
        }

        $i = 0;
        foreach ($details as $tahun => $stafs) {
            if($i > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle('TAHUN '.$tahun);
            $si->freezePane('C7');

            $bar = 2;
            $si->setCellValue('A'.$bar, 'REKAPITULASI GAJI PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PERIODE : TAHUN '.$tahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar +=2;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+1));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+1));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+1));
            $si->setCellValue('D'.$bar, 'MASA KERJA');
            $si->mergeCells('D'.$bar.':D'.($bar+1));
            $si->setCellValue('E'.$bar, 'B U L A N');
            $si->mergeCells('E'.$bar.':P'.$bar);
            $si->setCellValue('Q'.$bar, 'BONUS');
            $si->mergeCells('Q'.$bar.':Q'.($bar+1));
            $si->setCellValue('R'.$bar, 'THR IDR');
            $si->mergeCells('R'.$bar.':R'.($bar+1));
            $si->setCellValue('S'.$bar, 'P H K');
            $si->mergeCells('S'.$bar.':W'.$bar);
            $si->setCellValue('X'.$bar, 'TOTAL IDR');
            $si->mergeCells('X'.$bar.':X'.($bar+1));
            $bar++;
            $si->setCellValue('E'.$bar, 'JANUARI');
            $si->setCellValue('F'.$bar, 'FEBRUARI');
            $si->setCellValue('G'.$bar, 'MARET');
            $si->setCellValue('H'.$bar, 'APRIL');
            $si->setCellValue('I'.$bar, 'MEI');
            $si->setCellValue('J'.$bar, 'JUNI');
            $si->setCellValue('K'.$bar, 'JULI');
            $si->setCellValue('L'.$bar, 'AGUSTUS');
            $si->setCellValue('M'.$bar, 'SEPTEMBER');
            $si->setCellValue('N'.$bar, 'OKTOBER');
            $si->setCellValue('O'.$bar, 'NOVEMBER');
            $si->setCellValue('P'.$bar, 'DESEMBER');
            $si->setCellValue('S'.$bar, 'KOMPENSASI');
            $si->setCellValue('T'.$bar, 'PESANGON');
            $si->setCellValue('U'.$bar, 'MASA KERJA');
            $si->setCellValue('V'.$bar, 'UANG PISAH');
            $si->setCellValue('W'.$bar, 'SISA CUTI');
            $bar++;
            $nomor = 1;
            foreach ($stafs as $staf => $areas) {
                if($staf == 'N') {
                    $si->setCellValue('B'.$bar, 'NON STAF :');
                    $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                    $bar++;
                }
                foreach ($areas as $area => $karyawan_ids) {
                    if($staf == 'Y') {
                        $si->setCellValue('B'.$bar, $area.' :');
                        $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                        $bar++;
                    }
                    foreach ($karyawan_ids as $karyawan_id => $bulans) {
                        $dkaryawan = $dataKaryawan[$karyawan_id];
                        $si->setCellValue('A'.$bar, $nomor);
                        $si->setCellValue('B'.$bar, $dkaryawan->nama);
                        $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                        $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_masuk)));
                        for ($k=1; $k <= 12; $k++) {
                            $si->setCellValue($kol[$k+3].$bar, isset($bulans[$k]) ? $bulans[$k] : 0);
                        }
                        $si->setCellValue('R'.$bar, isset($dataTHR[$tahun][$karyawan_id]) ? $dataTHR[$tahun][$karyawan_id] : 0);
                        $si->setCellValue('X'.$bar, '=SUM(E'.$bar.':W'.$bar.')');
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $bar+=2;

            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            for ($k = 4; $k <= 23; $k++) { // E s/d X
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'7:'.$kol[$k].($bar-1).')');
            }

            $si->getStyle('A2:A3')->getFont()->setName('Arial')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A5:'.$kol_akhir.'6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A5:'.$kol_akhir.'6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A5:'.$kol_akhir.'6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFF00');
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getFont()->getColor()->setARGB('0000FF');

            $si->getStyle('D7:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E7:X'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(210, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= 22; $k++) { // E s/d W
                $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('X')->setWidth(130, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="REKAP_GAJI_'.substr($tahun, -2). '.xlsx"');
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
