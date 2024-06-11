<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SpreadPphController extends Controller
{
    protected $repoHeader, $repoDetail, $repoOncall;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
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
                    $si->setCellValue('C'.$bar, $d->gaji);
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

            $bar+=2;
            $barAwal = $bar;  // 21
            $si->setCellValue('AC'.$bar, 'Penghitungan PPh Pasal 21 pada masa pajak terakhir (Desember)');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Bruto Setahun');
            $si->setCellValue('AH'.$bar, '=AD19');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Pengurang:');
            $bar++;
            $si->setCellValue('AC'.$bar, '-');
            $si->setCellValue('AD'.$bar, 'Biaya Jabatan Setahun :');
            $bar++;
            $si->setCellValue('AD'.$bar, '5% x');
            $si->setCellValue('AE'.$bar, '=AH22');
            $bar++;
            $si->setCellValue('AD'.$bar, '="(  max "&COUNTIF(C6:C17, ">0") &" x"');
            $si->setCellValue('AE'.$bar, '500000');
            $si->setCellValue('AF'.$bar, ')');
            $si->setCellValue('AG'.$bar, '=ROUND(MIN(5%*AH22,500000*COUNTIF(C6:C17, ">0")),0)');
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
            $si->setCellValue('AH'.$bar, '=SUM(AG26:AG28)');
            $si->setCellValue('AI'.$bar, '-');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Neto Setahun');
            $si->setCellValue('AH'.$bar, '=AH22-AH29');
            $bar++;
            $si->setCellValue('AC'.$bar, 'PTKP Setahun untuk '.$dataKaryawan->ptkp->kode);
            $si->setCellValue('AH'.$bar, $dataKaryawan->ptkp->jumlah);
            $si->setCellValue('AI'.$bar, '-');
            $bar++;
            $si->setCellValue('AC'.$bar, 'Penghasilan Kena Pajak Setahun');
            $si->setCellValue('AH'.$bar, '=AH30-AH31');
            $bar++;
            $si->setCellValue('AC'.$bar, 'PPh Pasal 21 terutang setahun');
            $bar++;
            $pengurangBiayaJabatan = $totPenghasilanBruto / 100 * 5;
            if($pengurangBiayaJabatan > ($totAccMonth * 500000)) $pengurangBiayaJabatan = $totAccMonth * 500000;
            $penghasilanNetto = $totPenghasilanBruto - $pengurangBiayaJabatan;
            $pkpSetahun = $penghasilanNetto  - $dataKaryawan->ptkp->jumlah;
            list($pphTerutang, $breakdown) = $this->hitungPPh21($pkpSetahun);
            $barBreakdown = $bar;
            foreach ($breakdown as $item) {
                $si->setCellValue('AC'.$bar,  ($item['rate']*100).'% x');
                $si->setCellValue('AD'.$bar, $item['amount']);
                $si->setCellValue('AG'.$bar, ($item['amount'] * $item['rate']));
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
            if($pphTerutang-$totPph21 >= 0) {
                $si->setCellValue('AC'.$bar, 'PPh Pasal 21 yang harus dipotong pada bulan Desember '.$keyTahun);
            } else {
                $si->setCellValue('AC'.$bar, 'PPh Pasal 21 yang lebih dipotong');
            }
            $si->setCellValue('AH'.$bar, '=AH'.($bar-2).'-AH'.($bar-1));

            $si->getStyle('AC'.$barAwal.':AH'.$bar)->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('AE'.$barAwal.':AH'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('AD'.$barBreakdown.':AD'.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle('AC24:AC28')->getAlignment()->setHorizontal('right');
            $si->getStyle('AD25:AD26')->getAlignment()->setHorizontal('right');
            $si->getStyle('AG28')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH29')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH31')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AG'.($barBreakdown+count($breakdown)-1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('AH'.($barBreakdown+count($breakdown)+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

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

    function hitungPPh21($pkp) {
        $pph = 0;
        $breakdown = [];

        if ($pkp > 5000000000) {
            $breakdown[] = ['rate' => 0.35, 'amount' => $pkp - 5000000000];
            $pph += ($pkp - 5000000000) * 0.35;
            $pkp = 5000000000;
        }
        if ($pkp > 500000000) {
            $breakdown[] = ['rate' => 0.30, 'amount' => $pkp - 500000000];
            $pph += ($pkp - 500000000) * 0.30;
            $pkp = 500000000;
        }
        if ($pkp > 250000000) {
            $breakdown[] = ['rate' => 0.25, 'amount' => $pkp - 250000000];
            $pph += ($pkp - 250000000) * 0.25;
            $pkp = 250000000;
        }
        if ($pkp > 60000000) {
            $breakdown[] = ['rate' => 0.15, 'amount' => $pkp - 60000000];
            $pph += ($pkp - 60000000) * 0.15;
            $pkp = 60000000;
        }
        if ($pkp > 0) {
            $breakdown[] = ['rate' => 0.05, 'amount' => $pkp];
            $pph += $pkp * 0.05;
        }
        // Membalik urutan $breakdown agar dari tarif terkecil ke terbesar
        $breakdown = array_reverse($breakdown);
        return [$pph, $breakdown];
    }

}
