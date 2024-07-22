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
        $arrkol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");

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
            $headers[$dh->bulan]['thr'] = $dh->thr;
            $headers[$dh->bulan]['bonus'] = $dh->bonus;
            $headers[$dh->bulan]['insentif'] = $dh->insentif;
            $headers[$dh->bulan]['telkomsel'] = $dh->telkomsel;
            $headers[$dh->bulan]['lain'] = $dh->lain;
            $headers[$dh->bulan]['pot_telepon'] = $dh->pot_telepon;
            $headers[$dh->bulan]['pot_bensin'] = $dh->pot_bensin;
            $headers[$dh->bulan]['pot_bpjs'] = $dh->pot_bpjs;
            $headers[$dh->bulan]['pot_cuti'] = $dh->pot_cuti;
            $headers[$dh->bulan]['pot_lain'] = $dh->pot_lain;
        }
        $i = 0;

        $dataOncalls = $this->repoOncall->findAll(['tahun' => $dh->tahun]);
        $oncallJumlahs = [];
        foreach ($dataOncalls as $r) {
            $dOncalls[$r->bulan][$r->id] = $r;
            if(isset($oncallJumlahs[$r->bulan])) {
                $oncallJumlahs[$r->bulan] += $r->jumlah;
            } else {
                $oncallJumlahs[$r->bulan] = $r->jumlah;
            }
        }

        foreach ($dataHeaders as $dh) {
            $kolTun = 2;
            $kolPot = 3;
            $adaThr = false;
            $adaBonus = false;
            $adaInsentif = false;
            $adaTelkomsel = false;
            $adaLain = false;
            $adaPotTelepon = false;
            $adaPotBensin = false;
            $adaPotBpjs = false;
            $adaPotCuti = false;
            $adaPotLain = false;
            if(isset($headers[$dh->bulan])) {
                if($headers[$dh->bulan]['thr'] > 0) {
                    $adaThr = true;
                    $kolTun++;
                }
                if($headers[$dh->bulan]['bonus'] > 0) {
                    $adaBonus = true;
                    $kolTun++;
                }
                if($headers[$dh->bulan]['insentif'] > 0) {
                    $adaInsentif = true;
                    $kolTun++;
                }
                if($headers[$dh->bulan]['telkomsel'] > 0) {
                    $adaTelkomsel = true;
                    $kolTun++;
                }
                if($headers[$dh->bulan]['lain'] > 0) {
                    $adaLain = true;
                    $kolTun++;
                }
                if($headers[$dh->bulan]['pot_telepon'] > 0) {
                    $adaPotTelepon = true;
                    $kolPot++;
                }
                if($headers[$dh->bulan]['pot_bensin'] > 0) {
                    $adaPotBensin = true;
                    $kolPot++;
                }
                if($headers[$dh->bulan]['pot_bpjs'] > 0) {
                    $adaPotBpjs = true;
                    $kolPot++;
                }
                if($headers[$dh->bulan]['pot_cuti'] > 0) {
                    $adaPotCuti = true;
                    $kolPot++;
                }
                if($headers[$dh->bulan]['pot_lain'] > 0) {
                    $adaPotLain = true;
                    $kolPot++;
                }
            }
            $kolTotal = 11 + $kolTun + $kolPot;
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
            $si->mergeCells('A'.$bar.':'.$arrkol[$kolTotal].$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':'.$arrkol[$kolTotal].$bar);
            $bar++;
            $kolom = 0; // A
            $si->setCellValue($arrkol[$kolom].$bar, 'NO');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++; // 1.B
            $si->setCellValue($arrkol[$kolom].$bar, 'NAMA KARYAWAN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++; // 2.C
            $si->setCellValue($arrkol[$kolom].$bar, 'JABATAN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++;  // 3.D
            $si->setCellValue($arrkol[$kolom].$bar, 'MASA KERJA');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++; // 4.E
            $si->setCellValue($arrkol[$kolom].$bar, 'GAJI / UPAH IDR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++; // 5.F
            $si->setCellValue($arrkol[$kolom].$bar, 'U/MAKAN & TRANSPORTASI');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom+=2].$bar); // F-H
            $kolom++; // 8.I
            $si->setCellValue($arrkol[$kolom].$bar, 'TUNJANGAN LAIN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom+=$kolTun].$bar);
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'POTONGAN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom+=$kolPot].$bar);
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'TOTAL DITERIMA IDR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'KETERANGAN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+2));
            $bar++;
            $kolom = 5; // F
            $si->setCellValue($arrkol[$kolom].$bar, 'HR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
            $kolom++; // 6.G
            $si->setCellValue($arrkol[$kolom].$bar, '@ HARI IDR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
            $kolom++; // 7.H
            $si->setCellValue($arrkol[$kolom].$bar, 'JUMLAH IDR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
            $kolom++; // 8.I
            $si->setCellValue($arrkol[$kolom].$bar, 'OVERTIME');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[++$kolom].$bar); // I-J
            $kolom++; // K
            $si->setCellValue($arrkol[$kolom].$bar, 'MEDICAL IDR');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
            $kolom++; // L
            if($adaThr) {
                $si->setCellValue($arrkol[$kolom].$bar, 'THR IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaBonus) {
                $si->setCellValue($arrkol[$kolom].$bar, 'BONUS IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaInsentif) {
                $si->setCellValue($arrkol[$kolom].$bar, 'INSENTIF IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaTelkomsel) {
                $si->setCellValue($arrkol[$kolom].$bar, 'TELKOMSEL IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaLain) {
                $si->setCellValue($arrkol[$kolom].$bar, 'LAIN-LAIN IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            $si->setCellValue($arrkol[$kolom].$bar, '25%');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[++$kolom].$bar);
            $kolom++;
            if($adaPotTelepon) {
                $si->setCellValue($arrkol[$kolom].$bar, 'TELP. IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaPotBensin) {
                $si->setCellValue($arrkol[$kolom].$bar, 'BENSIN IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            $si->setCellValue($arrkol[$kolom].$bar, 'PINJAMAN');
            $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[++$kolom].$bar);
            $kolom++;
            if($adaPotBpjs) {
                $si->setCellValue($arrkol[$kolom].$bar, 'BPJS (KIS) IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaPotCuti) {
                $si->setCellValue($arrkol[$kolom].$bar, 'UNPAID LEAVE');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            if($adaPotLain) {
                $si->setCellValue($arrkol[$kolom].$bar, 'LAIN-LAIN IDR');
                $si->mergeCells($arrkol[$kolom].$bar.':'.$arrkol[$kolom].($bar+1));
                $kolom++;
            }
            $bar++;
            $kolom = 8; // I
            $si->setCellValue($arrkol[$kolom].$bar, 'FRATEKINDO');
            $kolom++; // 9.J
            $si->setCellValue($arrkol[$kolom].$bar, 'CUSTOMER');
            $kolom+=$kolTun;
            $si->setCellValue($arrkol[$kolom].$bar, 'HR');
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'JUMLAH IDR');
            $kolom++;
            if($adaPotTelepon) $kolom++;
            if($adaPotBensin) $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'KAS');
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, 'CICILAN');
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
                        $kolom = 0; // A
                        $si->setCellValue($arrkol[$kolom].$bar, $nomor);
                        $kolom++; // 1.B
                        $si->setCellValue($arrkol[$kolom].$bar, $d->karyawan->nama);
                        $kolom++; // 2.C
                        $si->setCellValue($arrkol[$kolom].$bar, $d->karyawan->jabatan->nama);
                        $kolom++; // 3.D
                        $si->setCellValue($arrkol[$kolom].$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_masuk)));
                        $kolom++; // 4.E
                        $si->setCellValue($arrkol[$kolom].$bar, ($d->gaji + $d->kenaikan_gaji));
                        $kolom++; // 5.F
                        $si->setCellValue($arrkol[$kolom].$bar, $d->hari_makan);
                        $kolom++; // 6.G
                        $si->setCellValue($arrkol[$kolom].$bar, $d->uang_makan_harian);
                        $kolom++; // 7.H
                        $si->setCellValue($arrkol[$kolom].$bar, $d->uang_makan_jumlah);
                        $kolom++; // 8.I
                        $si->setCellValue($arrkol[$kolom].$bar, $d->overtime_fjg);
                        $kolom++; // 9.J
                        $si->setCellValue($arrkol[$kolom].$bar, $d->overtime_cus);
                        $kolom++; // 10.K
                        $si->setCellValue($arrkol[$kolom].$bar, $d->medical);
                        $kolom++; // 11.L
                        if($adaThr) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->thr);
                            $kolom++;
                        }
                        if($adaBonus) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->bonus);
                            $kolom++;
                        }
                        if($adaInsentif) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->insentif);
                            $kolom++;
                        }
                        if($adaTelkomsel) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->telkomsel);
                            $kolom++;
                        }
                        if($adaLain) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->lain);
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_25_hari);
                        $kolom++;
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_25_jumlah);
                        $kolom++;
                        if($adaPotTelepon) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_telepon);
                            $kolom++;
                        }
                        if($adaPotBensin) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_bensin);
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_kas);
                        $kolom++;
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_cicilan);
                        $kolom++;
                        if($adaPotBpjs) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_bpjs);
                            $kolom++;
                        }
                        if($adaPotCuti) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_cuti);
                            $kolom++;
                        }
                        if($adaPotLain) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_lain);
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->total_diterima);
                        $kolom++;
                        $si->setCellValue($arrkol[$kolom].$bar, $d->keterangan);
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $si->getStyle('A1:A2')->getFont()->setName('Arial')->setSize(14)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle($arrkol[9+$kolTun].'3:Y'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle($arrkol[$kolTotal-1].'3:'.$arrkol[$kolTotal-1].$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('A3:'.$arrkol[$kolTotal].'5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A3:'.$arrkol[$kolTotal].'5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$arrkol[$kolTotal].($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$arrkol[$kolTotal].($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A6:'.$arrkol[$kolTotal].($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);

            $si->setCellValue('A'.$bar, 'TOTAL PAYROLL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            $si->setCellValue('E'.$bar, '=SUM(E6:E'.($bar-1).')');
            $si->setCellValue('H'.$bar, '=SUM(H6:H'.($bar-1).')');
            for ($k = 8; $k <= 10; $k++) { // I s/d K
                $si->setCellValue($arrkol[$k].$bar, '=SUM('.$arrkol[$k].'6:'.$arrkol[$k].($bar-1).')');
            }
            $kolom = 11; // L
            if($adaThr) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaBonus) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaInsentif) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaTelkomsel) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaLain) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')'); // 25% jumlah
            $kolom++;
            if($adaPotTelepon) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaPotBensin) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')'); // pot_kas
            $kolom++;
            $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')'); // pot_cicilan
            $kolom++;
            if($adaPotBpjs) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaPotCuti) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            if($adaPotLain) {
                $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')');
                $kolom++;
            }
            $si->setCellValue($arrkol[$kolom].$bar, '=SUM('.$arrkol[$kolom].'6:'.$arrkol[$kolom].($bar-1).')'); // total_diterima
            $si->getStyle('A'.$bar.':'.$arrkol[$kolTotal].$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('D6:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E6:'.$arrkol[$kolTotal-1].$bar)->getNumberFormat()->setFormatCode('#,##0');
            $barOT = $bar;
            $barTTD = $bar;
            $barKet = $bar;

            $barOT += 2;
            $si->setCellValue('E'.$barOT, 'OVERTIME & ON CALL CUSTOMERS :');
            $si->mergeCells('E'.$barOT.':H'.$barOT);
            $si->setCellValue('I'.$barOT, 'OVERTIME & MEDICAL :');
            $si->mergeCells('I'.$barOT.':K'.$barOT);
            $si->getStyle('E'.$barOT.':K'.$barOT)->getAlignment()->setHorizontal('center')->setVertical('center');
            $si->getStyle('E'.$barOT.':K'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $barOT++;
            $barOTAwal = $barOT;
            if(isset($dOncalls[$dh->bulan])) {
                foreach ($dOncalls[$dh->bulan] as $r) {
                    $si->setCellValue('E'.$barOT, $r->customer->nama);
                    $si->mergeCells('E'.$barOT.':G'.$barOT);
                    $si->setCellValue('H'.$barOT, $r->jumlah);
                    $barOT++;
                }
            }
            $barOM = $barOTAwal;
            $now = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['overtime'] : 0;
            $before = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['overtime'] : 0;
            if(isset($oncallJumlahs[$dh->bulan])) $now -=$oncallJumlahs[$dh->bulan];
            if(isset($oncallJumlahs[($dh->bulan-1)])) $before -=$oncallJumlahs[($dh->bulan-1)];
            if($now == 0 && $before == 0) {
                $statusOT = 'TETAP';
                $persenOT = 0;
            } else if($before == 0) {
                if($now > 0) {
                    $statusOT = 'NAIK';
                    $persenOT = 100;
                } else {
                    $statusOT = 'TURUN';
                    $persenOT = 100;
                }
            } else {
                if($now == 0) {
                    $statusOT = 'TURUN';
                    $persenOT = 100;
                } else if($now > 0) {
                    $persenOT = ($now - $before) / $before * 100;
                    if($persenOT > 0) {
                        $statusOT = 'NAIK';
                    } else {
                        $statusOT = 'TURUN';
                    }
                } else {
                    $statusOT = 'TERCOVER';
                    $persenOT = 0;
                }
            }
            $nowMed = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['medical'] : 0;
            $beforeMed = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['medical'] : 0;
            if($nowMed == 0 && $beforeMed == 0) {
                $statusMed = 'TETAP';
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
            $si->setCellValue('I'.$barOM, 'OVERTIME');
            $si->setCellValue('J'.$barOM, $statusOT);
            $si->setCellValue('K'.$barOM, number_format(abs($persenOT),2).'%');
            $barOM++;
            $si->setCellValue('I'.$barOM, 'MEDICAL');
            $si->setCellValue('J'.$barOM, $statusMed);
            $si->setCellValue('K'.$barOM, number_format(abs($persenMed),2).'%');
            $si->getStyle('K'.($barOM-1).':K'.$barOM)->getAlignment()->setHorizontal('right');
            $barOM++;
            if($barOT < $barOM) {
                for ($k = $barOT; $k < $barOM; $k++) {
                    $si->setCellValue('E'.$k, '');
                    $si->mergeCells('E'.$k.':G'.$k);
                    $si->setCellValue('H'.$k, '');
                }
                $barOT = $k;
            }
            $si->setCellValue('E'.$barOT, 'JUMLAH');
            $si->mergeCells('E'.$barOT.':G'.$barOT);
            $si->setCellValue('H'.$barOT, '=SUM(H'.$barOTAwal.':H'.($barOT-1).')');
            if( $statusOT == 'TERCOVER') {
                $si->setCellValue('I'.$barOT, 'Tercover On Call Customer');
            }
            $si->getStyle('E'.$barOTAwal.':'.'H'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('E'.$barOTAwal.':'.'H'.($barOT-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('E'.$barOTAwal.':'.'H'.($barOT-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('I'.$barOTAwal.':'.'K'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('E'.$barOT.':'.'H'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('I'.$barOT.':'.'K'.$barOT)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('E'.$barOT.':'.'H'.$barOT)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('H'.$barOTAwal.':'.'H'.$barOT)->getNumberFormat()->setFormatCode('#,##0');

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
            $si->setCellValue($arrkol[$kolTotal-4].$barTTD, 'Diajukan Oleh :');
            $si->setCellValue($arrkol[$kolTotal-2].$barTTD, 'Disetujui Oleh : ');
            $si->setCellValue($arrkol[$kolTotal].$barTTD, 'Diterima Oleh : ');
            $si->getStyle($arrkol[$kolTotal-4].$barTTD.':'.$arrkol[$kolTotal].$barTTD)->getAlignment()->setHorizontal('center');
            $barTTD += 3;
            $si->setCellValue($arrkol[$kolTotal-4].$barTTD, 'Sri Erni.S');
            $si->setCellValue($arrkol[$kolTotal-2].$barTTD, 'Alain Pierre Mignon');
            $si->setCellValue($arrkol[$kolTotal].$barTTD, 'Harti Susilowati');
            $si->getStyle($arrkol[$kolTotal-4].$barTTD.':'.$arrkol[$kolTotal].$barTTD)->getAlignment()->setHorizontal('center');
            $si->getStyle($arrkol[$kolTotal-4].$barTTD.':'.$arrkol[$kolTotal].$barTTD)->getFont()->setUnderline(true);
            $barTTD++;
            $si->setCellValue($arrkol[$kolTotal-4].$barTTD, 'Head of HRD');
            $si->setCellValue($arrkol[$kolTotal-2].$barTTD, 'Presiden Direktur');
            $si->setCellValue($arrkol[$kolTotal].$barTTD, 'Finance');
            $si->getStyle($arrkol[$kolTotal-4].$barTTD.':'.$arrkol[$kolTotal].$barTTD)->getAlignment()->setHorizontal('center');
            $si->getStyle($arrkol[$kolTotal-4].$barTTD.':'.$arrkol[$kolTotal].$barTTD)->getFont()->setBold(false);

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(200, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(210, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(80, Dimension::UOM_PIXELS);
            $si->getColumnDimension('E')->setWidth(100, Dimension::UOM_PIXELS);
            $si->getColumnDimension('F')->setWidth(30, Dimension::UOM_PIXELS);
            $si->getColumnDimension('G')->setWidth(75, Dimension::UOM_PIXELS);
            for ($k = 7; $k <= 10; $k++) { // H s/d K
                $si->getColumnDimension($arrkol[$k])->setWidth(85, Dimension::UOM_PIXELS);
            }
            $kolom = 11; // L
            if($adaThr) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaBonus) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaInsentif) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaTelkomsel) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaLain) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            $si->getColumnDimension($arrkol[$kolom])->setWidth(30, Dimension::UOM_PIXELS); // 25% hr
            $kolom++;
            $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS); // 25% jumlah
            $kolom++;
            if($adaPotTelepon) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaPotBensin) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS); // pot_kas
            $kolom++;
            $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS); // pot_cicilan
            $kolom++;
            if($adaPotBpjs) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaPotCuti) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            if($adaPotLain) {
                $si->getColumnDimension($arrkol[$kolom])->setWidth(85, Dimension::UOM_PIXELS);
                $kolom++;
            }
            $si->getColumnDimension($arrkol[$kolom])->setWidth(110, Dimension::UOM_PIXELS); // total_diterima
            $kolom++;
            $si->getColumnDimension($arrkol[$kolom])->setWidth(150, Dimension::UOM_PIXELS); // keterangan
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
