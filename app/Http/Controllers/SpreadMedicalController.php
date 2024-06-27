<?php

namespace App\Http\Controllers;

use App\Repositories\UpahRepository;
use App\Repositories\MedicalRepository;
use App\Repositories\PayrollHeaderRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadMedicalController extends Controller
{
    protected $repoKaryawan, $repoMedical, $repoPayrollHeader;

    public function __construct(UpahRepository $repoKaryawan, MedicalRepository $repoMedical, PayrollHeaderRepository $repoPayrollHeader) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoMedical = $repoMedical;
        $this->repoPayrollHeader = $repoPayrollHeader;
    }

    public function rekap($tahun) {

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoKaryawan->findAll(['aktif' => 'Y', 'sort_by' => 'tanggal_masuk']);
        $details = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->staf][$dt->area->nama][$dt->id] = $dt;
        }

        $dataGajis = $this->repoPayrollHeader->findUpahsByTahun($tahun);
        $gajis = array();
        foreach ($dataGajis as $d) {
            $gajis[$d->karyawan_id] = $d->gaji;
        }

        $dataRekapRawatJalans = $this->repoMedical->findRekapsRawatJalan($tahun);
        $rawatJalans = array();
        foreach ($dataRekapRawatJalans as $d) {
            $rawatJalans[$d->karyawan_id] = $d;
        }

        $dataMedKacamatas = $this->repoMedical->findAll(['jenis' => 'K']);
        $kacamatas = array();
        foreach ($dataMedKacamatas as $d) {
            $kacamatas[$d->tahun][$d->karyawan_id][$d->id] = $d;
        }

        $dataMedLahirs = $this->repoMedical->findAll(['jenis' => 'I']);
        $lahirs = array();
        foreach ($dataMedLahirs as $d) {
            $lahirs[$d->tahun][$d->karyawan_id][$d->id] = $d;
        }

        $i = 0;
        $arrBulan = ['', 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];

        //=========================================== SHEET MELAHIRKAN ===========================================
        $spreadsheet->setActiveSheetIndex($i);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('MELAHIRKAN');
        $si->getTabColor()->setRGB('FF0000');
        $bar = 1;
        foreach($lahirs as $keyTahun => $karyawan_ids) {
            $bar++;
            $si->setCellValue('A'.$bar, 'KLAIM MELAHIRKAN / GUGUR KANDUNGAN KARYAWAN');
            $si->mergeCells('A'.$bar.':G'.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':G'.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PERIODE : TAHUN '.$keyTahun);
            $si->mergeCells('A'.$bar.':G'.$bar);
            $si->getStyle('A'.($bar-2).':A'.$bar)->getFont()->setName('Arial')->setSize(12)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A'.($bar-2).':A'.$bar)->getAlignment()->setHorizontal('center');
            $bar+=2;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+1));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+1));
            $si->setCellValue('C'.$bar, 'MASA KERJA');
            $si->mergeCells('C'.$bar.':C'.($bar+1));
            $si->setCellValue('D'.$bar, 'TANGGAL LAHIR');
            $si->mergeCells('D'.$bar.':D'.($bar+1));
            $si->setCellValue('E'.$bar, 'JABATAN');
            $si->mergeCells('E'.$bar.':E'.($bar+1));
            $si->setCellValue('F'.$bar, 'BULAN & TAHUN');
            $si->mergeCells('F'.$bar.':F'.($bar+1));
            $si->setCellValue('G'.$bar, 'JUMLAH IDR');
            $si->mergeCells('G'.$bar.':G'.($bar+1));
            $si->getStyle('A'.$bar.':G'.($bar+1))->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':G'.($bar+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar+=3;
            $nomor = 1;
            $barAwal = $bar;
            foreach ($karyawan_ids as $karyawan_id => $ids) {
                foreach ($ids as $id => $d) {
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->karyawan->nama);
                    $si->setCellValue('C'.$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_masuk)));
                    $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_lahir)));
                    $si->setCellValue('E'.$bar, $d->karyawan->jabatan->nama);
                    $si->setCellValue('F'.$bar, $arrBulan[$d->bulan]."'".substr($d->tahun, -2));
                    $si->setCellValue('G'.$bar, $d->jumlah);
                    $bar+=2;
                    $nomor++;
                }
            }
            $si->setCellValue('A'.$bar, 'TOTAL KLAIM');
            $si->mergeCells('A'.$bar.':F'.$bar);
            $si->setCellValue('G'.$bar, '=SUM(G'.$barAwal.':G'.($bar-1).')');
            $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':G'.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar+=2;
        }
        $si->getStyle('C1:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
        $si->getStyle('G1:G'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('C1:D'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('F1:F'.$bar)->getAlignment()->setHorizontal('center');
        $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
        $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
        $si->getColumnDimension('C')->setWidth(75, Dimension::UOM_PIXELS);
        $si->getColumnDimension('D')->setWidth(75, Dimension::UOM_PIXELS);
        $si->getColumnDimension('E')->setWidth(210, Dimension::UOM_PIXELS);
        $si->getColumnDimension('F')->setWidth(100, Dimension::UOM_PIXELS);
        $si->getColumnDimension('G')->setWidth(100, Dimension::UOM_PIXELS);

        //=========================================== SHEET KACAMATA ===========================================
        $i++;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($i);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('KACAMATA');
        $si->getTabColor()->setRGB('FF0000');
        $bar = 1;
        foreach($kacamatas as $keyTahun => $karyawan_ids) {
            $bar++;
            $si->setCellValue('A'.$bar, 'KLAIM KACAMATA KARYAWAN');
            $si->mergeCells('A'.$bar.':G'.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':G'.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PERIODE : TAHUN '.$keyTahun);
            $si->mergeCells('A'.$bar.':G'.$bar);
            $si->getStyle('A'.($bar-2).':A'.$bar)->getFont()->setName('Arial')->setSize(12)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A'.($bar-2).':A'.$bar)->getAlignment()->setHorizontal('center');
            $bar+=2;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+1));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+1));
            $si->setCellValue('C'.$bar, 'MASA KERJA');
            $si->mergeCells('C'.$bar.':C'.($bar+1));
            $si->setCellValue('D'.$bar, 'TANGGAL LAHIR');
            $si->mergeCells('D'.$bar.':D'.($bar+1));
            $si->setCellValue('E'.$bar, 'JABATAN');
            $si->mergeCells('E'.$bar.':E'.($bar+1));
            $si->setCellValue('F'.$bar, 'BULAN & TAHUN');
            $si->mergeCells('F'.$bar.':F'.($bar+1));
            $si->setCellValue('G'.$bar, 'JUMLAH IDR');
            $si->mergeCells('G'.$bar.':G'.($bar+1));
            $si->getStyle('A'.$bar.':G'.($bar+1))->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':G'.($bar+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar+=3;
            $nomor = 1;
            $barAwal = $bar;
            foreach ($karyawan_ids as $karyawan_id => $ids) {
                foreach ($ids as $id => $d) {
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->karyawan->nama);
                    $si->setCellValue('C'.$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_masuk)));
                    $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_lahir)));
                    $si->setCellValue('E'.$bar, $d->karyawan->jabatan->nama);
                    $si->setCellValue('F'.$bar, $arrBulan[$d->bulan]."'".substr($d->tahun, -2));
                    $si->setCellValue('G'.$bar, $d->jumlah);
                    $bar+=2;
                    $nomor++;
                }
            }
            $si->setCellValue('A'.$bar, 'TOTAL KLAIM');
            $si->mergeCells('A'.$bar.':F'.$bar);
            $si->setCellValue('G'.$bar, '=SUM(G'.$barAwal.':G'.($bar-1).')');
            $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.($barAwal-1).':G'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':G'.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar+=2;
        }
        $si->getStyle('C1:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
        $si->getStyle('G1:G'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('C1:D'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('F1:F'.$bar)->getAlignment()->setHorizontal('center');
        $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
        $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
        $si->getColumnDimension('C')->setWidth(75, Dimension::UOM_PIXELS);
        $si->getColumnDimension('D')->setWidth(75, Dimension::UOM_PIXELS);
        $si->getColumnDimension('E')->setWidth(210, Dimension::UOM_PIXELS);
        $si->getColumnDimension('F')->setWidth(100, Dimension::UOM_PIXELS);
        $si->getColumnDimension('G')->setWidth(100, Dimension::UOM_PIXELS);

        //=========================================== SHEET RAWAT JALAN ===========================================
        $i++;
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'V';
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($i);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('RAWAT JLN');
        $si->getTabColor()->setRGB('FF0000');
        $si->freezePane('C7');
        $bar = 1;
        $si->setCellValue('A'.$bar, 'REKAPITULASI MEDICAL KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
        $bar++;
        $si->setCellValue('A'.$bar, 'PERIODE : JANUARI S/D DESEMBER '.$tahun);
        $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
        $bar++;
        $si->setCellValue('A'.$bar, 'KELAS : RAWAT JALAN');
        $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
        $bar+=2;
        $si->setCellValue('A'.$bar, 'NO');
        $si->mergeCells('A'.$bar.':A'.($bar+1));
        $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
        $si->mergeCells('B'.$bar.':B'.($bar+1));
        $si->setCellValue('C'.$bar, 'JABATAN');
        $si->mergeCells('C'.$bar.':C'.($bar+1));
        $si->setCellValue('D'.$bar, 'MASA KERJA');
        $si->mergeCells('D'.$bar.':D'.($bar+1));
        $si->setCellValue('E'.$bar, 'TANGGAL LAHIR');
        $si->mergeCells('E'.$bar.':E'.($bar+1));
        $si->setCellValue('F'.$bar, 'GAJI');
        $si->mergeCells('F'.$bar.':F'.($bar+1));
        $si->setCellValue('G'.$bar, 'TUNJANGAN 1');
        $si->mergeCells('G'.$bar.':G'.($bar+1));
        $si->setCellValue('H'.$bar, 'TUNJANGAN 2');
        $si->mergeCells('H'.$bar.':H'.($bar+1));
        $si->setCellValue('I'.$bar, 'B U L A N');
        $si->mergeCells('I'.$bar.':T'.$bar);
        $si->setCellValue('U'.$bar, 'SISA IDR');
        $si->mergeCells('U'.$bar.':U'.($bar+1));
        $si->setCellValue('V'.$bar, 'JUMLAH KLAIM');
        $si->mergeCells('V'.$bar.':V'.($bar+1));
        $bar++;
        for ($k=1; $k <= 12; $k++) {
            $m = $k+7; // kol I s/d T
            $si->setCellValue($kol[$m].$bar, $arrBulan[$k]);
        }
        $bar++;
        $nomor = 1;
        foreach ($details as $staf => $areas) {
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
                foreach ($karyawan_ids as $karyawan_id => $dkaryawan) {
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $dkaryawan->nama);
                    $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                    $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_masuk)));
                    $si->setCellValue('E'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_lahir)));
                    $gaji = isset($gajis[$karyawan_id])
                        ? $gajis[$karyawan_id]
                        : (isset($dkaryawan->gaji)
                            ? $dkaryawan->gaji
                            : 0);
                    $tunjangan1 = $dkaryawan->kelamin == 'P' ? $gaji : 0;
                    $tunjangan2 = $dkaryawan->kelamin == 'L' ? $gaji*2 : 0;
                    $si->setCellValue('F'.$bar, $gaji);
                    $si->setCellValue('G'.$bar, $tunjangan1);
                    $si->setCellValue('H'.$bar, $tunjangan2);
                    for ($k=1; $k <= 12; $k++) {
                        $m = $k+7; // kol I s/d T
                        $si->setCellValue($kol[$m].$bar, isset($rawatJalans[$karyawan_id]) ? $rawatJalans[$karyawan_id]->{'bln_'.$k} : 0);
                    }
                    $si->setCellValue('U'.$bar, '='.($dkaryawan->kelamin == 'P' ? 'G' : 'H').$bar.'-I'.$bar.'-J'.$bar.'-K'.$bar.'-L'.$bar.'-M'.$bar.'-N'.$bar.'-O'.$bar.'-P'.$bar.'-Q'.$bar.'-R'.$bar.'-S'.$bar.'-T'.$bar);
                    $si->setCellValue('V'.$bar, '='.'I'.$bar.'+J'.$bar.'+K'.$bar.'+L'.$bar.'+M'.$bar.'+N'.$bar.'+O'.$bar.'+P'.$bar.'+Q'.$bar.'+R'.$bar.'+S'.$bar.'+T'.$bar);
                    $bar++;
                    $nomor++;
                }
            }
        }
        $bar+=2;
        $si->setCellValue('A'.$bar, 'TOTAL');
        $si->mergeCells('A'.$bar.':E'.$bar);
        for ($k = 5; $k <= 21; $k++) { // F s/d V
            $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'7:'.$kol[$k].($bar-1).')');
        }
        $si->getStyle('A1:A3')->getFont()->setName('Arial')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
        $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('A5:'.$kol_akhir.'6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
        $si->getStyle('A5:'.$kol_akhir.'6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A5:'.$kol_akhir.'6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFF00');
        $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A7:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
        $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('I'.$bar.':'.$kol_akhir.$bar)->getFont()->getColor()->setARGB('0000FF');
        $si->getStyle($kol_akhir.'7'.':'.$kol_akhir.$bar)->getFont()->getColor()->setARGB('0000FF');
        $si->getStyle('D7:E'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
        $si->getStyle('F7:V'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        $si->getStyle('D7:E'.$bar)->getAlignment()->setHorizontal('center');
        $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
        $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
        $si->getColumnDimension('C')->setWidth(210, Dimension::UOM_PIXELS);
        $si->getColumnDimension('D')->setWidth(75, Dimension::UOM_PIXELS);
        $si->getColumnDimension('E')->setWidth(75, Dimension::UOM_PIXELS);
        for ($k = 5; $k <= 21; $k++) { // F s/d V
            $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
        }

        //======================================================================================
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="REKAP_MEDIKAL_'.substr($tahun, -2). '.xlsx"');
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
