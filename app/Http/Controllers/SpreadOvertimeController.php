<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadOvertimeController extends Controller
{
    protected $repoHeader, $repoDetail, $repoOncall;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
    }

    public function rekap($tahun) {
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'O';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoDetail->findAll(['tahun' => $tahun]);
        $details = array();
        $dataKaryawan = array();
        foreach ($dataDetails as $dt) {
            if($dt->overtime_fjg > 0 || $dt->overtime_cus > 0) {
                $details[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id][$dt->bulan] = $dt->overtime_fjg + $dt->overtime_cus;
                $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            }
        }

        $dataOncalls = $this->repoOncall->findAll(['tahun' => $tahun]);
        $oncalls = array();
        foreach ($dataOncalls as $d) {
            if(isset($oncalls[$d->tahun][$d->bulan])) {
                $oncalls[$d->tahun][$d->bulan] += $d->jumlah;
            } else {
                $oncalls[$d->tahun][$d->bulan] = $d->jumlah;
            }
        }

        $i = 0;
        foreach ($details as $keyTahun => $stafs) {
            if($i > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle('TAHUN '.$keyTahun);
            $si->freezePane('C7');

            $bar = 2;
            $si->setCellValue('A'.$bar, 'REKAPITULASI OVERTIME PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PERIODE : TAHUN '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar +=2;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+1));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+1));
            $si->setCellValue('C'.$bar, 'B U L A N');
            $si->mergeCells('C'.$bar.':N'.$bar);
            $si->setCellValue('O'.$bar, 'TOTAL IDR');
            $si->mergeCells('O'.$bar.':O'.($bar+1));
            $bar++;
            for ($k=1; $k <= 12; $k++) {
                $m = $k+1; // kol C s/d N
                $si->setCellValue($kol[$m].$bar, $arrBulan[$k]);
            }
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
                        for ($k=1; $k <= 12; $k++) {
                            $si->setCellValue($kol[$k+1].$bar, isset($bulans[$k]) ? $bulans[$k] : 0);
                        }
                        $si->setCellValue('O'.$bar, '=SUM(C'.$bar.':N'.$bar.')');
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':B'.$bar);
            for ($k = 2; $k <= 14; $k++) { // C s/d O
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'7:'.$kol[$k].($bar-1).')');
            }
            $si->getStyle('A2:A3')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A5:'.$kol_akhir.'6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A5:'.$kol_akhir.'6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A5:'.$kol_akhir.'6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFC354');
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('C7:O'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $bar++;
            $si->setCellValue('M'.$bar, 'OT DIBAYAR CUSTOMER :');
            $si->mergeCells('M'.$bar.':N'.$bar);
            $si->getStyle('M'.$bar.':N'.$bar)->getFont()->setUnderline(true)->setItalic(true)->getColor()->setARGB('FF0000');
            $bar++;
            $m = 1;
            for ($k = $bar; $k < ($bar+12); $k++) {
                $si->setCellValue('M'.$k, $arrBulan[$m].' '.$keyTahun);
                $si->mergeCells('M'.$k.':N'.$k);
                $si->setCellValue('O'.$k, isset($oncalls[$keyTahun][$m]) ? $oncalls[$keyTahun][$m] : 0);
                $m++;
            }
            $bar = $k;
            $si->setCellValue('M'.$bar, 'SISA OT DIBAYAR FJG');
            $si->mergeCells('M'.$bar.':N'.$bar);
            $si->setCellValue('O'.$bar, '=O'.($bar-14).'-SUM(O'.($bar-12).':O'.($bar-1).')');
            $si->getStyle('M'.$bar.':N'.$bar)->getFont()->setItalic(true)->getColor()->setARGB('FF0000');
            $si->getStyle('O'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('O'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
            $si->getStyle('O'.$bar)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('O'.($bar-12).':O'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            for ($k = 2; $k <= 13; $k++) { // C s/d N
                $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('O')->setWidth(130, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="REKAP_OVERTIME_'.substr($tahun, -2). '.xlsx"');
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
