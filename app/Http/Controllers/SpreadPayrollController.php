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

class SpreadPayrollController extends Controller
{
    protected $repoHeader, $repoDetail, $repoOncall;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
    }

    public function rekapPerKaryawan($jenis, $tahun, $area) {
        // jenis   =>   1.ENGINEER   2.STAFF   3.NON STAF
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'AB';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoDetail->findAll([
            'tahun' => $tahun,
            'staf' => $jenis == '3' ? 'N' : 'Y',
            'area' => $jenis == '3' ? '' : $area,
            'engineer' => $jenis == '3' ? '' : ($jenis == '2' ? 'N' : 'Y'),
        ]);
        $details = array();
        $dataKaryawan = array();
        $namaArea = '';
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->id][$dt->bulan] = $dt;
            if($namaArea == '') {
                $namaArea = $dt->karyawan->area->nama;
            }
            if(!isset($dataKaryawan[$dt->karyawan->id])) {
                $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
            }
        }

        $i = 0;
        foreach ($details as $keyTahun => $karyawan_ids) {
            $barSub = array();
            if($i > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle($keyTahun);
            $si->freezePane('C6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'PAYROLL JANUARI '.$keyTahun.' S/D DESEMBER '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'DIVISI : '.($jenis == '3' ? 'NON STAF' : ($jenis == '2' ? 'STAF ' : 'ENGINEERING ').$namaArea));
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
            $si->setCellValue('AB'.$bar, 'BULAN DAN TAHUN');
            $si->mergeCells('AB'.$bar.':AB'.($bar+2));
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
            $nomor = 1;
            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $bar++;
                $dkaryawan = $dataKaryawan[$karyawan_id];
                $si->setCellValue('A'.$bar, $nomor);
                $si->setCellValue('B'.$bar, $jenis == '3' ? $dkaryawan->nama.' ('.$dkaryawan->area->nama.')' : $dkaryawan->nama);
                $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_masuk)));
                for ($k=1; $k <= 12; $k++) {
                    if(isset($bulans[$k])) {
                        $d = $bulans[$k];
                        $si->setCellValue('E'.$bar, $d->gaji);
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
                    } else {
                        for ($z=4; $z <= 25; $z++) {
                            $si->setCellValue($kol[$z].$bar, 0);
                        }
                    }
                    $si->setCellValue('AB'.$bar, $arrBulan[$k]."'".substr($keyTahun, -2));
                    $bar++;
                }
                $si->setCellValue('AB'.$bar, 'THR');
                $bar++;
                $barSub[] = $bar;
                $si->setCellValue('Z'.$bar, '=SUM(Z'.($bar-13).':Z'.($bar-1).')');
                $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFF00');
                $bar++;
                $nomor++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL PAYROLL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            // for ($k = 8; $k <= 15; $k++) { // I s/d P
            // }
            // for ($k = 17; $k <= 25; $k++) { // R s/d Z
            //     $barSubKolom = array_map(function($num) use($kol, $k) {
            //         return $kol[$k] . $num;
            //     }, $barSub);
            //     $si->setCellValue($kol[$k].$bar, '=' . implode('+', $barSubKolom));
            // }
            $barSubKolom = array_map(function($num) {
                return 'Z' . $num;
            }, $barSub);
            $si->setCellValue('Z'.$bar, '=' . implode('+', $barSubKolom));

            $si->getStyle('A1:A2')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('Q3:Y'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('Z3:Z'.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('A3:'.$kol_akhir.'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol_akhir.'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('B6:C'.($bar-1))->getAlignment()->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('D6:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E6:Z'.$bar)->getNumberFormat()->setFormatCode('#,##0');

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
            $si->getColumnDimension('AB')->setWidth(110, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="PAYROLL_KARYAWAN_'.($jenis == '3' ? 'NON_STAF_' : ($jenis == '2' ? 'STAF_' : 'ENGINEERING_').str_replace(' ', '_', $namaArea)).'_'.substr($tahun, -2). '.xlsx"');
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
