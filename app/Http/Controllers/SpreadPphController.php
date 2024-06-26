<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\PayrollRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadPphController extends Controller
{
    protected $repoDetail, $repoArea;

    public function __construct(PayrollRepository $repoDetail, AreaRepository $repoArea) {
        $this->repoDetail = $repoDetail;
        $this->repoArea = $repoArea;
    }

    public function karyawan($karyawan_id, $tahun) {
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'AH';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoDetail->findAll([
            'karyawan_id' => $karyawan_id,
            'tahun' => $tahun,
            'pph21' => 'Y',
        ]);
        $details = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->bulan] = $dt;
            $dataKaryawan = $dt->karyawan;
        }

        $i = 0;
        foreach ($details as $keyTahun => $bulans) {
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
            $si->setCellValue('A'.$bar, $dataKaryawan->nama.'('.$dataKaryawan->jabatan->nama.' '.$dataKaryawan->area->nama.')');
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'BULAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'GAJI / UPAH IDR');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'U/MAKAN & TRANSPORTASI');
            $si->mergeCells('D'.$bar.':F'.$bar);
            $si->setCellValue('G'.$bar, 'TUNJANGAN LAIN');
            $si->mergeCells('G'.$bar.':N'.$bar);
            $si->setCellValue('O'.$bar, 'POTONGAN');
            $si->mergeCells('O'.$bar.':W'.$bar);
            $si->setCellValue('X'.$bar, 'TOTAL DITERIMA IDR');
            $si->mergeCells('X'.$bar.':X'.($bar+2));
            $si->setCellValue('Y'.$bar, 'BENEFIT LAINNYA');
            $si->mergeCells('Y'.$bar.':AC'.$bar);
            $si->setCellValue('AD'.$bar, 'PENGHITUNGAN PPh 21');
            $si->mergeCells('AD'.$bar.':AG'.$bar);
            $si->setCellValue('AH'.$bar, 'TOTAL IDR');
            $si->mergeCells('AH'.$bar.':AH'.($bar+2));
            $bar++;
            $si->setCellValue('D'.$bar, 'HR');
            $si->mergeCells('D'.$bar.':D'.($bar+1));
            $si->setCellValue('E'.$bar, '@ HARI IDR');
            $si->mergeCells('E'.$bar.':E'.($bar+1));
            $si->setCellValue('F'.$bar, 'JUMLAH IDR');
            $si->mergeCells('F'.$bar.':F'.($bar+1));
            $si->setCellValue('G'.$bar, 'OVERTIME');
            $si->mergeCells('G'.$bar.':H'.$bar);
            $si->setCellValue('I'.$bar, 'MEDICAL IDR');
            $si->mergeCells('I'.$bar.':I'.($bar+1));
            $si->setCellValue('J'.$bar, 'THR IDR');
            $si->mergeCells('J'.$bar.':J'.($bar+1));
            $si->setCellValue('K'.$bar, 'BONUS IDR');
            $si->mergeCells('K'.$bar.':K'.($bar+1));
            $si->setCellValue('L'.$bar, 'INSENTIF IDR');
            $si->mergeCells('L'.$bar.':L'.($bar+1));
            $si->setCellValue('M'.$bar, 'TELKOMSEL IDR');
            $si->mergeCells('M'.$bar.':M'.($bar+1));
            $si->setCellValue('N'.$bar, 'LAIN-LAIN IDR');
            $si->mergeCells('N'.$bar.':N'.($bar+1));
            $si->setCellValue('O'.$bar, '25%');
            $si->mergeCells('O'.$bar.':P'.$bar);
            $si->setCellValue('Q'.$bar, 'TELP. IDR');
            $si->mergeCells('Q'.$bar.':Q'.($bar+1));
            $si->setCellValue('R'.$bar, 'BENSIN IDR');
            $si->mergeCells('R'.$bar.':R'.($bar+1));
            $si->setCellValue('S'.$bar, 'PINJAMAN');
            $si->mergeCells('S'.$bar.':T'.$bar);
            $si->setCellValue('U'.$bar, 'BPJS (KIS) IDR');
            $si->mergeCells('U'.$bar.':U'.($bar+1));
            $si->setCellValue('V'.$bar, 'UNPAID LEAVE');
            $si->mergeCells('V'.$bar.':V'.($bar+1));
            $si->setCellValue('W'.$bar, 'LAIN-LAIN IDR');
            $si->mergeCells('W'.$bar.':W'.($bar+1));
            $si->setCellValue('Y'.$bar, 'JP 3%');
            $si->mergeCells('Y'.$bar.':Y'.($bar+1));
            $si->setCellValue('Z'.$bar, 'JHT 5.7%');
            $si->mergeCells('Z'.$bar.':Z'.($bar+1));
            $si->setCellValue('AA'.$bar, 'JKK 0,24%');
            $si->mergeCells('AA'.$bar.':AA'.($bar+1));
            $si->setCellValue('AB'.$bar, 'JKM 0,3%');
            $si->mergeCells('AB'.$bar.':AB'.($bar+1));
            $si->setCellValue('AC'.$bar, 'BPJS KESEHATAN');
            $si->mergeCells('AC'.$bar.':AC'.($bar+1));
            $si->setCellValue('AD'.$bar, 'PENGHASILAN BRUTO');
            $si->mergeCells('AD'.$bar.':AD'.($bar+1));
            $si->setCellValue('AE'.$bar, 'DPP');
            $si->mergeCells('AE'.$bar.':AE'.($bar+1));
            $si->setCellValue('AF'.$bar, 'TER '.$dataKaryawan->ptkp->ter);
            $si->mergeCells('AF'.$bar.':AF'.($bar+1));
            $si->setCellValue('AG'.$bar, 'PPh 21');
            $si->mergeCells('AG'.$bar.':AG'.($bar+1));
            $bar++;
            $si->setCellValue('G'.$bar, 'FRATEKINDO');
            $si->setCellValue('H'.$bar, 'CUSTOMER');
            $si->setCellValue('O'.$bar, 'HR');
            $si->setCellValue('P'.$bar, 'JUMLAH IDR');
            $si->setCellValue('S'.$bar, 'KAS');
            $si->setCellValue('T'.$bar, 'CICILAN');
            $bar++;
            $totPenghasilanBruto = 0;
            $totAccMonth = 0;
            $totPph21 = 0;
            for ($k=1; $k <= 12; $k++) {
                $si->setCellValue('A'.$bar, $k);
                $si->setCellValue('B'.$bar, $arrBulan[$k]);
                if(isset($bulans[$k])) {
                    $d = $bulans[$k];
                    $si->setCellValue('C'.$bar, ($d->gaji + $d->kenaikan_gaji));
                    $si->setCellValue('D'.$bar, $d->hari_makan);
                    $si->setCellValue('E'.$bar, $d->uang_makan_harian);
                    $si->setCellValue('F'.$bar, $d->uang_makan_jumlah);
                    $si->setCellValue('G'.$bar, $d->overtime_fjg);
                    $si->setCellValue('H'.$bar, $d->overtime_cus);
                    $si->setCellValue('I'.$bar, $d->medical);
                    $si->setCellValue('J'.$bar, $d->thr);
                    $si->setCellValue('K'.$bar, $d->bonus);
                    $si->setCellValue('L'.$bar, $d->insentif);
                    $si->setCellValue('M'.$bar, $d->telkomsel);
                    $si->setCellValue('N'.$bar, $d->lain);
                    $si->setCellValue('O'.$bar, $d->pot_25_hari);
                    $si->setCellValue('P'.$bar, $d->pot_25_jumlah);
                    $si->setCellValue('Q'.$bar, $d->pot_telepon);
                    $si->setCellValue('R'.$bar, $d->pot_bensin);
                    $si->setCellValue('S'.$bar, $d->pot_kas);
                    $si->setCellValue('T'.$bar, $d->pot_cicilan);
                    $si->setCellValue('U'.$bar, $d->pot_bpjs);
                    $si->setCellValue('V'.$bar, $d->pot_cuti);
                    $si->setCellValue('W'.$bar, $d->pot_lain);
                    $si->setCellValue('X'.$bar, $d->total_diterima);
                    $si->setCellValue('Y'.$bar, $d->kantor_jp);
                    $si->setCellValue('Z'.$bar, $d->kantor_jht);
                    $si->setCellValue('AA'.$bar, $d->kantor_jkk);
                    $si->setCellValue('AB'.$bar, $d->kantor_jkm);
                    $si->setCellValue('AC'.$bar, $d->kantor_bpjs);
                    $si->setCellValue('AD'.$bar, $d->penghasilan_bruto);
                    $si->setCellValue('AE'.$bar, $d->dpp);
                    $si->setCellValue('AF'.$bar, $d->ter_persen/100);
                    if($k != 12) {
                        $si->setCellValue('AG'.$bar, $d->pph21);
                        $totPph21 += $d->pph21;
                    }
                    $si->setCellValue('AH'.$bar, '=X'.$bar.'+Y'.$bar.'+Z'.$bar.'+AA'.$bar.'+AB'.$bar.'+AC'.$bar.'+AG'.$bar);
                    $totPenghasilanBruto += $d->penghasilan_bruto;
                    $totAccMonth += 1;
                } else {
                    for ($z=2; $z <= 28; $z++) {
                        $si->setCellValue($kol[$z].$bar, 0);
                    }
                }
                $bar++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':B'.$bar);
            $si->setCellValue('C'.$bar, '=SUM(C6:C'.($bar-1).')');
            $si->setCellValue('F'.$bar, '=SUM(F6:F'.($bar-1).')');
            for ($k = 6; $k <= 13; $k++) { // G s/d N
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            for ($k = 15; $k <= 30; $k++) { // P s/d AE
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            $si->setCellValue('AG'.$bar, '=SUM(AG6:AG'.($bar-1).')');
            $si->setCellValue('AH'.$bar, '=SUM(AH6:AH'.($bar-1).')');
            $si->getStyle('A1:A2')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('O3:W'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('X3:X'.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('AD3:AF'.$bar)->getFont()->getColor()->setARGB('FFAA00');
            $si->getStyle('A3:'.$kol_akhir.'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol_akhir.'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('C6:AE'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('AF6:AF'.$bar)->getNumberFormat()->setFormatCode('0.00%');
            $si->getStyle('AG6:AH'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $hitung = $this->hitungPPh21GrossUp($totPenghasilanBruto, $dataKaryawan->ptkp->jumlah, $totAccMonth);

            $bar+=2;
            $barAwal = $bar;  // 21
            $si->setCellValue('AC'.$bar, 'Penghitungan PPh Pasal 21 pada masa pajak terakhir (Desember)');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Bruto Setahun');
            $si->setCellValue('AH'.$bar, '=AD19');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Dasar Pengenaan Pajak (DPP)');
            $si->setCellValue('AH'.$bar, $hitung['dpp']);
            $bar++;
            $si->setCellValue('AC'.$bar, 'Pengurang:');
            $bar++;
            $si->setCellValue('AC'.$bar, '-');
            $si->setCellValue('AD'.$bar, 'Biaya Jabatan Setahun :');
            $bar++;
            $si->setCellValue('AD'.$bar, '5% x');
            $si->setCellValue('AE'.$bar, '=AH23');
            $bar++;
            $si->setCellValue('AD'.$bar, '(  max '.$totAccMonth.' x');
            $si->setCellValue('AE'.$bar, '500000');
            $si->setCellValue('AF'.$bar, ')');
            $si->setCellValue('AG'.$bar, $hitung['biaya_jabatan']);
            $bar++;
            $si->setCellValue('AC'.$bar, '-');
            $si->setCellValue('AD'.$bar, 'Iuran Pensiun & Hari Tua :');
            $si->setCellValue('AG'.$bar, '0');
            $bar++;
            $si->setCellValue('AC'.$bar, '-');
            $si->setCellValue('AD'.$bar, 'Zakat & Sumbangan :');
            $si->setCellValue('AG'.$bar, '0');
            $si->setCellValue('AH'.$bar, '+');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Total Pengurang');
            $si->setCellValue('AH'.$bar, '=SUM(AG27:AG29)');
            $si->setCellValue('AI'.$bar, '-');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Neto Setahun');
            $si->setCellValue('AH'.$bar, '=AH23-AH30');
            $bar++;
            $si->setCellValue('AC'.$bar, 'PTKP Setahun untuk '.$dataKaryawan->ptkp->kode);
            $si->setCellValue('AH'.$bar, $dataKaryawan->ptkp->jumlah);
            $si->setCellValue('AI'.$bar, '-');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Kena Pajak Setahun');
            $si->setCellValue('AH'.$bar, '=ROUNDDOWN(AH31-AH32,-3)');
            $bar++;
            $si->setCellValue('AC'.$bar, 'PPh Pasal 21 terutang setahun');
            $bar++;
            $barBreakdown = $bar;
            foreach ($hitung['breakdown'] as $item) {
                $si->setCellValue('AC'.$bar,  ($item['rate']*100).'% x');
                $si->setCellValue('AD'.$bar, $item['amount']);
                $si->setCellValue('AG'.$bar, $item['total']);
                $si->getStyle('AC'.$bar)->getAlignment()->setHorizontal('right');
                $bar++;
            }
            $si->setCellValue('AH'.($bar-1), '+');
            $si->setCellValue('AH'.$bar, '=SUM(AG'.$barBreakdown.':AG'.($bar-1).')');
            $bar++;
            $si->setCellValue('AC'.$bar, 'PPh Pasal 21 yang telah dipotong sampai November '.$keyTahun);
            $si->setCellValue('AH'.$bar, '=SUM(AG6:AG16)');
            $si->setCellValue('AI'.$bar, '-');
            $si->getStyle('AH'.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar++;
            if($hitung['pph']-$totPph21 >= 0) {
                $si->setCellValue('AC'.$bar, 'PPh Pasal 21 yang harus dipotong pada bulan Desember '.$keyTahun);
            } else {
                $si->setCellValue('AC'.$bar, 'PPh Pasal 21 yang lebih dipotong');
            }
            $si->setCellValue('AH'.$bar, '=AH'.($bar-2).'-AH'.($bar-1));

            $si->getStyle('AC'.$barAwal.':AH'.$bar)->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('AE'.$barAwal.':AH'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('AD'.$barBreakdown.':AD'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('AC25:AC29')->getAlignment()->setHorizontal('right');
            $si->getStyle('AD26:AD27')->getAlignment()->setHorizontal('right');
            $si->getStyle('AG29')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH30')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH32')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AG'.($barBreakdown+count($hitung['breakdown'])-1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH'.($barBreakdown+count($hitung['breakdown'])+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(100, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(100, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(30, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= 13; $k++) { // E s/d N
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('O')->setWidth(30, Dimension::UOM_PIXELS);
            for ($k = 15; $k <= 22; $k++) { // P s/d W
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('X')->setWidth(110, Dimension::UOM_PIXELS);
            for ($k = 24; $k <= 28; $k++) { // Y s/d AC
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('AD')->setWidth(110, Dimension::UOM_PIXELS);
            for ($k = 30; $k <= 32; $k++) { // AE s/d AG
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('AH')->setWidth(120, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="PAYROLL_'.str_replace(' ', '_', $dataKaryawan->nama).'_'.$tahun. '.xlsx"');
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

    public function rekap($jenis, $tahun, $area) {
        // jenis   =>   1.ENGINEER   2.STAFF   3.NON STAF    4.ALL
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'AE';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        if($area == 'all') {
            $namaArea = '';
        } else {
            $dArea = $this->repoArea->findById($area);
            $namaArea = $dArea->nama.'_';
        }

        $dataDetails = $this->repoDetail->findAll([
            'tahun' => $tahun,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
            'pph21' => 'Y',
        ]);
        $details = array();
        $dataKaryawan = array();
        $arrTotal = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->id][$dt->bulan] = $dt;
            if(isset($arrTotal[$dt->tahun][$dt->karyawan->id])) {
                $arrTotal[$dt->tahun][$dt->karyawan->id]['penghasilan_bruto'] += $dt->penghasilan_bruto;
            } else {
                $arrTotal[$dt->tahun][$dt->karyawan->id]['penghasilan_bruto'] = $dt->penghasilan_bruto;
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
            $si->setTitle('Data Gaji');
            $si->freezePane('E6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'PAYROLL JANUARI '.$keyTahun.' S/D DESEMBER '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'DIVISI : '.($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA'))).' '.$namaArea);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'BULAN');
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
            $si->setCellValue('AA'.$bar, 'BENEFIT LAINNYA');
            $si->mergeCells('AA'.$bar.':AE'.$bar);
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
            $si->setCellValue('AA'.$bar, 'JP 3%');
            $si->mergeCells('AA'.$bar.':AA'.($bar+1));
            $si->setCellValue('AB'.$bar, 'JHT 5.7%');
            $si->mergeCells('AB'.$bar.':AB'.($bar+1));
            $si->setCellValue('AC'.$bar, 'JKK 0,24%');
            $si->mergeCells('AC'.$bar.':AC'.($bar+1));
            $si->setCellValue('AD'.$bar, 'JKM 0,3%');
            $si->mergeCells('AD'.$bar.':AD'.($bar+1));
            $si->setCellValue('AE'.$bar, 'BPJS KESEHATAN');
            $si->mergeCells('AE'.$bar.':AE'.($bar+1));
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
                for ($k=1; $k <= 12; $k++) {
                    $si->setCellValue('D'.$bar, $arrBulan[$k]);
                    if(isset($bulans[$k])) {
                        $d = $bulans[$k];
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
                        $si->setCellValue('AA'.$bar, $d->kantor_jp);
                        $si->setCellValue('AB'.$bar, $d->kantor_jht);
                        $si->setCellValue('AC'.$bar, $d->kantor_jkk);
                        $si->setCellValue('AD'.$bar, $d->kantor_jkm);
                        $si->setCellValue('AE'.$bar, $d->kantor_bpjs);
                        } else {
                        for ($z=4; $z <= 30; $z++) {
                            $si->setCellValue($kol[$z].$bar, 0);
                        }
                    }
                    $bar++;
                }
                $barSub[] = $bar;
                $si->setCellValue('E'.$bar, '=SUM(E'.($bar-12).':E'.($bar-1).')');
                for ($k = 7; $k <= 15; $k++) { // H s/d P
                    $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].($bar-12).':'.$kol[$k].($bar-1).')');
                }
                for ($k = 17; $k <= 30; $k++) { // R s/d AE
                    $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].($bar-12).':'.$kol[$k].($bar-1).')');
                }
                $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFF00');
                $bar++;
                $nomor++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL PAYROLL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            $barSubKolom = array_map(function($num) {
                return 'E' . $num;
            }, $barSub);
            $si->setCellValue('E'.$bar, '=' . implode('+', $barSubKolom));
            for ($k = 7; $k <= 15; $k++) { // H s/d P
                $barSubKolom = array_map(function($num) use($kol, $k) {
                    return $kol[$k] . $num;
                }, $barSub);
                $si->setCellValue($kol[$k].$bar, '=' . implode('+', $barSubKolom));
            }
            for ($k = 17; $k <= 30; $k++) { // R s/d AE
                $barSubKolom = array_map(function($num) use($kol, $k) {
                    return $kol[$k] . $num;
                }, $barSub);
                $si->setCellValue($kol[$k].$bar, '=' . implode('+', $barSubKolom));
            }

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
            $si->getStyle('E6:AE'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(230, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(110, Dimension::UOM_PIXELS);
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
            for ($k = 26; $k <= 30; $k++) { // AA s/d AE
                $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $i++;
        }

        foreach ($details as $keyTahun => $karyawan_ids) {
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle('Jan - Nov');
            $si->freezePane('D6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'PENGHITUNGAN PPh 21 JANUARI '.$keyTahun.' S/D NOVEMBER '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'DIVISI : '.($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA'))).' '.$namaArea);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'NPWP');
            $si->mergeCells('D'.$bar.':D'.($bar+2));
            $kolnext = 4;
            for ($k=1; $k <= 11; $k++) {
                $si->setCellValue($kol[$kolnext].$bar, $arrBulan[$k]);
                $si->mergeCells($kol[$kolnext].$bar.':'.$kol[$kolnext+3].$bar);
                $kolnext += 4;
            }
            $bar++;
            $kolnext = 4;
            for ($k=1; $k <= 11; $k++) {
                $si->setCellValue($kol[$kolnext].$bar, 'PENGHASILAN BRUTO');
                $si->mergeCells($kol[$kolnext].$bar.':'.$kol[$kolnext].($bar+1));
                $si->setCellValue($kol[$kolnext+1].$bar, 'DPP');
                $si->mergeCells($kol[$kolnext+1].$bar.':'.$kol[$kolnext+1].($bar+1));
                $si->setCellValue($kol[$kolnext+2].$bar, 'TER');
                $si->mergeCells($kol[$kolnext+2].$bar.':'.$kol[$kolnext+2].($bar+1));
                $si->setCellValue($kol[$kolnext+3].$bar, 'PPh 21');
                $si->mergeCells($kol[$kolnext+3].$bar.':'.$kol[$kolnext+3].($bar+1));
                $kolnext += 4;
            }
            $bar+=3;
            $nomor = 1;
            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $dkaryawan = $dataKaryawan[$karyawan_id];
                $si->setCellValue('A'.$bar, $nomor);
                $si->setCellValue('B'.$bar, $jenis == '3' ? $dkaryawan->nama.' ('.$dkaryawan->area->nama.')' : $dkaryawan->nama);
                $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                $si->setCellValue('D'.$bar, $dkaryawan->nomor_pwp ? "'".$dkaryawan->nomor_pwp : "");
                $kolnext = 4;
                for ($k=1; $k <= 11; $k++) {
                    if(isset($bulans[$k])) {
                        $d = $bulans[$k];
                        $si->setCellValue($kol[$kolnext].$bar, $d->penghasilan_bruto);
                        $si->setCellValue($kol[$kolnext+1].$bar, $d->dpp);
                        $si->setCellValue($kol[$kolnext+2].$bar, $d->ter_persen/100);
                        $si->setCellValue($kol[$kolnext+3].$bar, $d->pph21);
                    } else {
                        $si->setCellValue($kol[$kolnext].$bar, 0);
                        $si->setCellValue($kol[$kolnext+1].$bar, 0);
                        $si->setCellValue($kol[$kolnext+2].$bar, 0);
                        $si->setCellValue($kol[$kolnext+3].$bar, 0);
                    }
                    $kolnext += 4;
                }
                $bar++;
                $nomor++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            $kolnext = 4;
            for ($k = 1; $k <= 11; $k++) {
                $si->setCellValue($kol[$kolnext].$bar, '=SUM('.$kol[$kolnext].'6:'.$kol[$kolnext].($bar-1).')');
                $si->setCellValue($kol[$kolnext+1].$bar, '=SUM('.$kol[$kolnext+1].'6:'.$kol[$kolnext+1].($bar-1).')');
                $si->setCellValue($kol[$kolnext+2].$bar, '=SUM('.$kol[$kolnext+2].'6:'.$kol[$kolnext+2].($bar-1).')');
                $si->setCellValue($kol[$kolnext+3].$bar, '=SUM('.$kol[$kolnext+3].'6:'.$kol[$kolnext+3].($bar-1).')');
                $kolnext += 4;
            }
            $si->getStyle('A1:A2')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('D6:D'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A3:'.$kol[$kolnext-1].'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol[$kolnext-1].'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol[$kolnext-1].($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol[$kolnext-1].($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol[$kolnext-1].($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('B6:C'.($bar-1))->getAlignment()->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':'.$kol[$kolnext-1].$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('E6:'.$kol[$kolnext-1].$bar)->getNumberFormat()->setFormatCode('#,##0');
            $kolnext = 4;
            for ($k = 1; $k <= 11; $k++) {
                $si->getStyle($kol[$kolnext+2].'6:'.$kol[$kolnext+2].$bar)->getNumberFormat()->setFormatCode('0.00%');
                $si->getStyle($kol[$kolnext+3].'3:'.$kol[$kolnext+3].$bar)->getFont()->getColor()->setARGB('0000FF');
                $kolnext += 4;
            }
            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(230, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(150, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= ($kolnext-1); $k++) {
                $si->getColumnDimension($kol[$k])->setWidth(110, Dimension::UOM_PIXELS);
            }
            $i++;
        }

        $kol_akhir = 'P';
        foreach ($details as $keyTahun => $karyawan_ids) {
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle('Hitung ( Des )');
            $si->freezePane('D6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'PENGHITUNGAN PPh 21 DESEMBER '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'DIVISI : '.($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA'))).' '.$namaArea);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'NPWP');
            $si->mergeCells('D'.$bar.':D'.($bar+2));
            $si->setCellValue('E'.$bar, 'STATUS PTKP');
            $si->mergeCells('E'.$bar.':E'.($bar+2));
            $si->setCellValue('F'.$bar, 'TER');
            $si->mergeCells('F'.$bar.':F'.($bar+2));
            $si->setCellValue('G'.$bar, 'PENGHASILAN BRUTO');
            $si->mergeCells('G'.$bar.':G'.($bar+2));
            $si->setCellValue('H'.$bar, 'DPP');
            $si->mergeCells('H'.$bar.':H'.($bar+2));
            $si->setCellValue('I'.$bar, 'BIAYA PENGURANG');
            $si->mergeCells('I'.$bar.':J'.$bar);
            $si->setCellValue('K'.$bar, 'JUMLAH PENGURANG');
            $si->mergeCells('K'.$bar.':K'.($bar+2));
            $si->setCellValue('L'.$bar, 'PENGHASILAN NETTO SETAHUN');
            $si->mergeCells('L'.$bar.':L'.($bar+2));
            $si->setCellValue('M'.$bar, 'PTKP & PKP KARYAWAN');
            $si->mergeCells('M'.$bar.':N'.$bar);
            $si->setCellValue('O'.$bar, 'PPH 21 SETAHUN / DISETAHUNKAN');
            $si->mergeCells('O'.$bar.':O'.($bar+2));
            $si->setCellValue('P'.$bar, 'KENAIKAN TARIF TANPA NPWP');
            $si->mergeCells('P'.$bar.':P'.($bar+1));
            $bar++;
            $si->setCellValue('I'.$bar, 'B. JABATAN');
            $si->mergeCells('I'.$bar.':I'.($bar+1));
            $si->setCellValue('J'.$bar, 'JHT 2% PEGAWAI');
            $si->mergeCells('J'.$bar.':J'.($bar+1));
            $si->setCellValue('M'.$bar, 'PTKP');
            $si->mergeCells('M'.$bar.':M'.($bar+1));
            $si->setCellValue('N'.$bar, 'PKP');
            $si->mergeCells('N'.$bar.':N'.($bar+1));
            $bar++;
            $si->setCellValue('P'.$bar, '1.2');
            $si->getStyle('P'.$bar)->getNumberFormat()->setFormatCode('0%');
            $bar+=2;
            $nomor = 1;
            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $dkaryawan = $dataKaryawan[$karyawan_id];
                if($dkaryawan->ptkp) {
                    $hitung = $this->hitungPPh21GrossUp($arrTotal[$keyTahun][$karyawan_id]['penghasilan_bruto'], $dkaryawan->ptkp->jumlah, count($bulans));
                } else {
                    $hitung = [
                        'dpp' => $arrTotal[$keyTahun][$karyawan_id]['penghasilan_bruto'],
                        'biaya_jabatan' => 0,
                        'netto' => 0,
                        'pkp' => 0,
                        'pph' => 0,
                        'breakdown' => 0,
                    ];
                }
                $si->setCellValue('A'.$bar, $nomor);
                $si->setCellValue('B'.$bar, $jenis == '3' ? $dkaryawan->nama.' ('.$dkaryawan->area->nama.')' : $dkaryawan->nama);
                $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                $si->setCellValue('D'.$bar, $dkaryawan->nomor_pwp ? "'".$dkaryawan->nomor_pwp : "");
                $si->setCellValue('E'.$bar, $dkaryawan->ptkp ? $dkaryawan->ptkp->kode : '');
                $si->setCellValue('F'.$bar, $dkaryawan->ptkp ? $dkaryawan->ptkp->ter : '');
                $si->setCellValue('G'.$bar, $arrTotal[$keyTahun][$karyawan_id]['penghasilan_bruto']);
                $si->setCellValue('H'.$bar, $hitung['dpp']);
                $si->setCellValue('I'.$bar, '=ROUND(MIN(5%*H'.$bar.',500000*'.count($bulans).'),0)');
                $si->setCellValue('J'.$bar, 0);
                $si->setCellValue('K'.$bar, '=SUM(I'.$bar.':J'.$bar.')');
                $si->setCellValue('L'.$bar, '=H'.$bar.'-K'.$bar.'');
                $si->setCellValue('M'.$bar, $dkaryawan->ptkp ? $dkaryawan->ptkp->jumlah : 0);
                $si->setCellValue('N'.$bar, '=IF(L'.$bar.'-M'.$bar.'<=0,0,ROUNDDOWN(L'.$bar.'-M'.$bar.',-3))');
                $si->setCellValue('O'.$bar, '=5%*IF(N'.$bar.'>60000000,60000000,N'.$bar.')+15%*IF(IF(N'.$bar.'-60000000>190000000,190000000,N'.$bar.'-60000000)<0,0,IF(N'.$bar.'-60000000>190000000,190000000,N'.$bar.'-60000000))+25%*IF(IF(N'.$bar.'-250000000>250000000,250000000,N'.$bar.'-250000000)<0,0,IF(N'.$bar.'-250000000>250000000,250000000,N'.$bar.'-250000000))+30%*IF(IF(N'.$bar.'-500000000>4500000000,4500000000,N'.$bar.'-500000000)<0,0,IF(N'.$bar.'-500000000>4500000000,4500000000,N'.$bar.'-500000000))+35%*IF(N'.$bar.'-5000000000<0,0,N'.$bar.'-5000000000)');
                $si->setCellValue('P'.$bar, '=IF(D'.$bar.'<>"",O'.$bar.',ROUND(O'.$bar.'*$P$5,0))');
                $bar++;
                $nomor++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            for ($k = 6; $k <= 15; $k++) { // G s/d P
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            $si->getStyle('A1:A2')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('D6:F'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A3:'.$kol_akhir.'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol_akhir.'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('B6:C'.($bar-1))->getAlignment()->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('G6:P'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(230, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(150, Dimension::UOM_PIXELS);
            $si->getColumnDimension('E')->setWidth(70, Dimension::UOM_PIXELS);
            $si->getColumnDimension('F')->setWidth(50, Dimension::UOM_PIXELS);
            $si->getColumnDimension('G')->setWidth(110, Dimension::UOM_PIXELS);
            $si->getColumnDimension('H')->setWidth(110, Dimension::UOM_PIXELS);
            for ($k = 8; $k <= 13; $k++) { // I s/d N
                $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('O')->setWidth(105, Dimension::UOM_PIXELS);
            $si->getColumnDimension('P')->setWidth(115, Dimension::UOM_PIXELS);
            $i++;
        }

        $kol_akhir = 'AC';
        foreach ($details as $keyTahun => $karyawan_ids) {
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle('Rekap PPh 21');
            $si->freezePane('E6');

            $bar = 1;
            $si->setCellValue('A'.$bar, 'REKAP PPh 21 JANUARI '.$keyTahun.' S/D DESEMBER '.$keyTahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'DIVISI : '.($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON STAF' : 'SEMUA'))).' '.$namaArea);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'JABATAN');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'NPWP');
            $si->mergeCells('D'.$bar.':D'.($bar+2));
            $si->setCellValue('E'.$bar, 'PEMBAYARAN PPH 21 JANUARI - NOVEMBER');
            $si->mergeCells('E'.$bar.':AA'.$bar);
            $si->setCellValue('AB'.$bar, 'DISETAHUNKAN');
            $si->mergeCells('AB'.$bar.':AB'.($bar+2));
            $si->setCellValue('AC'.$bar, 'DESEMBER         PPH 21 DIBAYARKAN');
            $si->mergeCells('AC'.$bar.':AC'.($bar+2));
            $bar++;
            $kolnext = 4;
            for ($k=1; $k <= 11; $k++) {
                $si->setCellValue($kol[$kolnext].$bar, $arrBulan[$k]);
                $si->mergeCells($kol[$kolnext].$bar.':'.$kol[$kolnext+1].$bar);
                $kolnext += 2;
            }
            $si->setCellValue('AA'.$bar, 'PPH 21 TERBAYAR');
            $si->mergeCells('AA'.$bar.':AA'.($bar+1));
            $bar++;
            $kolSub = array();
            $kolnext = 4;
            for ($k=1; $k <= 11; $k++) {
                $si->setCellValue($kol[$kolnext].$bar, 'PPh 21');
                $si->setCellValue($kol[$kolnext+1].$bar, 'PPh 21 Dibayarkan');
                $kolSub[] = $kolnext+1;
                $kolnext += 2;
            }
            $bar+=2;
            $nomor = 1;
            foreach ($karyawan_ids as $karyawan_id => $bulans) {
                $dkaryawan = $dataKaryawan[$karyawan_id];
                $si->setCellValue('A'.$bar, $nomor);
                $si->setCellValue('B'.$bar, $jenis == '3' ? $dkaryawan->nama.' ('.$dkaryawan->area->nama.')' : $dkaryawan->nama);
                $si->setCellValue('C'.$bar, $dkaryawan->jabatan->nama);
                $si->setCellValue('D'.$bar, $dkaryawan->nomor_pwp ? "'".$dkaryawan->nomor_pwp : "");
                $kolnext = 4;
                for ($k=1; $k <= 11; $k++) {
                    if(isset($bulans[$k])) {
                        $d = $bulans[$k];
                        $si->setCellValue($kol[$kolnext].$bar, $d->pph21);
                        $si->setCellValue($kol[$kolnext+1].$bar, '=IF(D'.$bar.'<>"",'.$kol[$kolnext].$bar.','.$kol[$kolnext].$bar.'*120%)');
                    } else {
                        $si->setCellValue($kol[$kolnext].$bar, 0);
                        $si->setCellValue($kol[$kolnext+1].$bar, 0);
                    }
                    $kolnext += 2;
                }
                $kolSubKolom = array_map(function($num) use ($kol, $bar) {
                    return $kol[$num].$bar;
                }, $kolSub);
                $si->setCellValue('AA'.$bar, '=' . implode('+', $kolSubKolom));
                $si->setCellValue('AB'.$bar, "='Hitung ( Des )'!P".$bar);
                $si->setCellValue('AC'.$bar, '=AB'.$bar.'-AA'.$bar);
                $bar++;
                $nomor++;
            }
            $bar++;
            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            for ($k = 4; $k <= 28; $k++) { // E s/d AC
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'6:'.$kol[$k].($bar-1).')');
            }
            $si->getStyle('A1:A2')->getFont()->setName('Algerian')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('D6:D'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A3:'.$kol_akhir.'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$kol_akhir.'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('B6:C'.($bar-1))->getAlignment()->setWrapText(TRUE);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('E6:'.$kol_akhir.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $kolnext = 4;
            for ($k = 1; $k <= 11; $k++) {
                $si->getStyle($kol[$kolnext+1].'5:'.$kol[$kolnext+1].$bar)->getFont()->getColor()->setARGB('0000FF');
                $kolnext += 2;
            }
            $si->getStyle('AC5:AC'.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(230, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(150, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= 28; $k++) { // E s/d AC
                $si->getColumnDimension($kol[$k])->setWidth(110, Dimension::UOM_PIXELS);
            }
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="HITUNG_PPH21_'.($jenis == '1' ? 'ENGINEERING' : ($jenis == '2' ? 'STAF' : ($jenis == '3' ? 'NON_STAF' : 'ALL'))).($namaArea == '' ? '' : '_'.str_replace(' ', '_', $namaArea)).'_'.substr($tahun, -2).'.xlsx"');
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

    function hitungPPh21($bruto, $ptkp, $accMonth) {
        $biayaJabatan = min($bruto/100*5, 500000*$accMonth);
        $biayaJabatan = round($biayaJabatan);
        $netto = $bruto - $biayaJabatan;
        $pkp = $netto - $ptkp;
        $pkp = floor($pkp/1000) * 1000;
        $pkpAwal = $pkp;
        $breakdown = [];
        $pph = 0;
        if ($pkp > 5000000000) {
            $rate = 0.35;
            $amount = $pkp - 5000000000;
            $total = $amount * $rate;
            $breakdown[] = ['rate' => $rate, 'amount' => $amount, 'total' => $total];
            $pph += $total;
            $pkp = 5000000000;
        }
        if ($pkp > 500000000) {
            $rate = 0.30;
            $amount = $pkp - 500000000;
            $total = $amount * $rate;
            $breakdown[] = ['rate' => $rate, 'amount' => $amount, 'total' => $total];
            $pph += $total;
            $pkp = 500000000;
        }
        if ($pkp > 250000000) {
            $rate = 0.25;
            $amount = $pkp - 250000000;
            $total = $amount * $rate;
            $breakdown[] = ['rate' => $rate, 'amount' => $amount, 'total' => $total];
            $pph += $total;
            $pkp = 250000000;
        }
        if ($pkp > 60000000) {
            $rate = 0.15;
            $amount = $pkp - 60000000;
            $total = $amount * $rate;
            $breakdown[] = ['rate' => $rate, 'amount' => $amount, 'total' => $total];
            $pph += $total;
            $pkp = 60000000;
        }
        if ($pkp > 0) {
            $rate = 0.05;
            $amount = $pkp;
            $total = $amount * $rate;
            $breakdown[] = ['rate' => $rate, 'amount' => $amount, 'total' => $total];
            $pph += $total;
        }
        // Membalik urutan $breakdown agar dari tarif terkecil ke terbesar
        $breakdown = array_reverse($breakdown);
        return [
            'dpp' => $bruto,
            'biaya_jabatan' => $biayaJabatan,
            'netto' => $netto,
            'pkp' => $pkpAwal,
            'pph' => $pph,
            'breakdown' => $breakdown,
        ];
    }

    function hitungPPh21GrossUp($bruto, $ptkp, $accMonth) {
        $pph = 0;
        $grossBruto = 0;
        $nextBruto = $bruto;
        do {
            $cari = $this->hitungPPh21($nextBruto, $ptkp, $accMonth);
            $pph = $cari['pph'];
            $grossBruto = $cari['dpp'];
            $nextBruto = $bruto + $pph;
        } while ($grossBruto - $pph != $bruto);
        return $cari;
    }

}
