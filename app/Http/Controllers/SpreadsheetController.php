<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Repositories\PayrollHeaderRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\UangPhkRepository;
use App\Repositories\KaryawanRepository;
use App\Traits\AFhelper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Area;
use App\Models\Phk;

class SpreadsheetController extends Controller
{
    use AFhelper;

    protected $repoHeader, $repoDetail, $repoOncall, $repoUangPhk, $repoPhk, $repoKaryawan;

    public function __construct(PayrollHeaderRepository $repoHeader, PayrollRepository $repoDetail, OncallCustomerRepository $repoOncall, UangPhkRepository $repoUangPhk, PayrollPhkRepository $repoPhk, KaryawanRepository $repoKaryawan) {
        $this->repoHeader = $repoHeader;
        $this->repoDetail = $repoDetail;
        $this->repoOncall = $repoOncall;
        $this->repoUangPhk = $repoUangPhk;
        $this->repoPhk = $repoPhk;
        $this->repoKaryawan = $repoKaryawan;
    }

    public function listPayroll($tahun) {
        $arrBulan = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $arrkol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataTahunLalu = $this->repoHeader->findAll(['tahun' => ($tahun-1), 'bulan' => '12']);
        if($dataTahunLalu->isEmpty()) {
            $headers[0]['overtime'] = 0;
            $headers[0]['overtime_fjg'] = 0;
            $headers[0]['overtime_cus'] = 0;
            $headers[0]['medical'] = 0;
        } else {
            $headers[0]['overtime'] = $dataTahunLalu[0]->overtime_fjg + $dataTahunLalu[0]->overtime_cus;
            $headers[0]['overtime_fjg'] = $dataTahunLalu[0]->overtime_fjg;
            $headers[0]['overtime_cus'] = $dataTahunLalu[0]->overtime_cus;
            $headers[0]['medical'] = $dataTahunLalu[0]->medical;
        }
        $dataHeaders = $this->repoHeader->findAll(['tahun' => $tahun]);
        foreach ($dataHeaders as $dh) {
            $headers[$dh->bulan]['overtime'] = $dh->overtime_fjg + $dh->overtime_cus;
            $headers[$dh->bulan]['overtime_fjg'] = $dh->overtime_fjg;
            $headers[$dh->bulan]['overtime_cus'] = $dh->overtime_cus;
            $headers[$dh->bulan]['medical'] = $dh->medical;
            $headers[$dh->bulan]['thr'] = $dh->thr;
            $headers[$dh->bulan]['bonus'] = $dh->bonus;
            $headers[$dh->bulan]['insentif'] = $dh->insentif;
            $headers[$dh->bulan]['telkomsel'] = $dh->telkomsel;
            $headers[$dh->bulan]['lain'] = $dh->lain;
            $headers[$dh->bulan]['pot_telepon'] = $dh->pot_telepon;
            $headers[$dh->bulan]['pot_bensin'] = $dh->pot_bensin;
            $headers[$dh->bulan]['pot_bpjs'] = $dh->pot_bpjs;
            $headers[$dh->bulan]['pot_cuti_jumlah'] = $dh->pot_cuti_jumlah;
            $headers[$dh->bulan]['pot_kompensasi_jumlah'] = $dh->pot_kompensasi_jumlah;
            $headers[$dh->bulan]['pot_lain'] = $dh->pot_lain;
        }
        $i = 0;

        $dataOncalls = $this->repoOncall->findAll(['tahun' => $tahun]);
        $oncallJumlahs = [];
        $dOncalls = [];
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
            $adaPotKompensasi = false;
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
                if($headers[$dh->bulan]['pot_cuti_jumlah'] > 0) {
                    $adaPotCuti = true;
                    $kolPot++;
                }
                if($headers[$dh->bulan]['pot_kompensasi_jumlah'] > 0) {
                    $adaPotKompensasi = true;
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
            if($adaPotKompensasi) {
                $si->setCellValue($arrkol[$kolom].$bar, 'KOMPENSASI IDR');
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
                        $si->setCellValue($arrkol[$kolom].$bar, $this->afAbbreviateName($d->karyawan->nama));
                        $kolom++; // 2.C
                        $si->setCellValue($arrkol[$kolom].$bar, $d->karyawan->jabatan->nama);
                        $kolom++; // 3.D
                        $si->setCellValue($arrkol[$kolom].$bar, Date::PHPToExcel(strtotime($d->karyawan->tanggal_masuk)));
                        $kolom++; // 4.E
                        $si->setCellValue($arrkol[$kolom].$bar, ($d->gaji + $d->kenaikan_gaji) > 0 ? ($d->gaji + $d->kenaikan_gaji) : '');
                        $kolom++; // 5.F
                        $si->setCellValue($arrkol[$kolom].$bar, $d->hari_makan > 0 ? $d->hari_makan : ' ');
                        $kolom++; // 6.G
                        $si->setCellValue($arrkol[$kolom].$bar, $d->uang_makan_harian > 0 ? $d->uang_makan_harian : ' ');
                        $kolom++; // 7.H
                        $si->setCellValue($arrkol[$kolom].$bar, $d->uang_makan_jumlah > 0 ? $d->uang_makan_jumlah : ' ');
                        $kolom++; // 8.I
                        $si->setCellValue($arrkol[$kolom].$bar, $d->overtime_fjg > 0 ? $d->overtime_fjg : ' ');
                        $kolom++; // 9.J
                        $si->setCellValue($arrkol[$kolom].$bar, $d->overtime_cus > 0 ? $d->overtime_cus : ' ');
                        $kolom++; // 10.K
                        $si->setCellValue($arrkol[$kolom].$bar, $d->medical != 0 ? $d->medical : ' ');
                        $kolom++; // 11.L
                        if($adaThr) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->thr > 0 ? $d->thr : ' ');
                            $kolom++;
                        }
                        if($adaBonus) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->bonus > 0 ? $d->bonus : ' ');
                            $kolom++;
                        }
                        if($adaInsentif) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->insentif > 0 ? $d->insentif : ' ');
                            $kolom++;
                        }
                        if($adaTelkomsel) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->telkomsel > 0 ? $d->telkomsel : ' ');
                            $kolom++;
                        }
                        if($adaLain) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->lain > 0 ? $d->lain : ' ');
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_25_hari > 0 ? $d->pot_25_hari : ' ');
                        $kolom++;
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_25_jumlah > 0 ? $d->pot_25_jumlah : ' ');
                        $kolom++;
                        if($adaPotTelepon) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_telepon > 0 ? $d->pot_telepon : ' ');
                            $kolom++;
                        }
                        if($adaPotBensin) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_bensin > 0 ? $d->pot_bensin : ' ');
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_kas > 0 ? $d->pot_kas : ' ');
                        $kolom++;
                        $si->setCellValue($arrkol[$kolom].$bar, $d->pot_cicilan > 0 ? $d->pot_cicilan : ' ');
                        $kolom++;
                        if($adaPotBpjs) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_bpjs > 0 ? $d->pot_bpjs : ' ');
                            $kolom++;
                        }
                        if($adaPotCuti) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_cuti_jumlah > 0 ? $d->pot_cuti_jumlah : ' ');
                            $kolom++;
                        }
                        if($adaPotKompensasi) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_kompensasi_jumlah > 0 ? $d->pot_kompensasi_jumlah : ' ');
                            $kolom++;
                        }
                        if($adaPotLain) {
                            $si->setCellValue($arrkol[$kolom].$bar, $d->pot_lain > 0 ? $d->pot_lain : ' ');
                            $kolom++;
                        }
                        $si->setCellValue($arrkol[$kolom].$bar, $d->total_diterima > 0 ? $d->total_diterima : ' ');
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
            if($adaPotKompensasi) {
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

            $barMakan = $bar + 2;
            $si->setCellValue('B'.$barMakan, 'U/MAKAN & TRANSPORTASI');
            $ttgl = explode('-', $dh->tanggal_awal);
            $ttgm = explode('-', $dh->tanggal_akhir);
            $str_aw = intval($ttgl[2]) . " " . $arrBulan[intval($ttgl[1])] . "'" . substr($ttgl[0], -2);
            $str_ak = intval($ttgm[2]) . " " . $arrBulan[intval($ttgm[1])] . "'" . substr($ttgm[0], -2);
            $si->setCellValue('C'.$barMakan, "'= ".$str_aw." - ".$str_ak);
            $si->mergeCells('C'.$barMakan.':E'.$barMakan);
            $barMakan++;
            $barMakanAwal = $barMakan - 1;

            foreach ($dataDetails as $dt) {
                if ($dt->makan_tgl_awal != null && $dt->makan_tgl_akhir != null) {
                    $namaDepan = explode(' ', $dt->karyawan->nama)[0];
                    $si->setCellValue('B'.$barMakan, $namaDepan . " (" . $dt->karyawan->area->kode . ")");
                    $c_aw = explode('-', $dt->makan_tgl_awal);
                    $c_ak = explode('-', $dt->makan_tgl_akhir);
                    $cstr_aw = intval($c_aw[2]) . " " . $arrBulan[intval($c_aw[1])] . "'" . substr($c_aw[0], -2);
                    $cstr_ak = intval($c_ak[2]) . " " . $arrBulan[intval($c_ak[1])] . "'" . substr($c_ak[0], -2);
                    $si->setCellValue('C'.$barMakan, "'= ".$cstr_aw." - ".$cstr_ak);
                    $si->mergeCells('C'.$barMakan.':E'.$barMakan);
                    $barMakan++;
                }
            }
            $si->getStyle('B'.$barMakanAwal.':E'.($barMakan-1))->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('B'.$barMakanAwal.':E'.($barMakan-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

            $barOT = $barMakan + 1;
            $barPot = $barOT;
            
            $si->setCellValue('F'.$barOT, 'OVERTIME & ON CALL CUSTOMERS :');
            $si->mergeCells('F'.$barOT.':I'.$barOT);
            $si->setCellValue('J'.$barOT, 'OVERTIME & MEDICAL :');
            $si->mergeCells('J'.$barOT.':L'.$barOT);
            $si->getStyle('F'.$barOT.':L'.$barOT)->getAlignment()->setHorizontal('center')->setVertical('center');
            $si->getStyle('F'.$barOT.':L'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $barOT++;
            $barOTAwal = $barOT;
            if(isset($dOncalls[$dh->bulan])) {
                foreach ($dOncalls[$dh->bulan] as $r) {
                    $si->setCellValue('F'.$barOT, $r->customer->nama);
                    $si->mergeCells('F'.$barOT.':H'.$barOT);
                    $si->setCellValue('I'.$barOT, $r->jumlah);
                    $barOT++;
                }
            }
            $barOM = $barOTAwal;
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
            $si->setCellValue('J'.$barOM, 'MEDICAL');
            $si->setCellValue('K'.$barOM, $statusMed);
            $si->setCellValue('L'.$barOM, number_format(abs($persenMed),2,',','.').'%');
            $si->getStyle('L'.$barOM)->getAlignment()->setHorizontal('right');
            $barOM++;
            
            $si->setCellValue('J'.$barOM, ''); // Empty row
            $si->setCellValue('K'.$barOM, '');
            $si->setCellValue('L'.$barOM, '');
            $barOM++;
            
            $si->setCellValue('J'.$barOM, 'OVERTIME :');
            $si->setCellValue('K'.$barOM, '');
            $si->setCellValue('L'.$barOM, '');
            $barOM++;
            
            $nowFjg = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['overtime_fjg'] : 0;
            $beforeFjg = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['overtime_fjg'] : 0;
            if($nowFjg == 0 && $beforeFjg == 0) {
                $statusFjg = 'TETAP';
                $persenFjg = 0;
            } else if($beforeFjg == 0) {
                $statusFjg = $nowFjg > 0 ? 'NAIK' : 'TURUN';
                $persenFjg = 100;
            } else {
                if($nowFjg == 0) {
                    $statusFjg = 'TURUN';
                    $persenFjg = 100;
                } else if($nowFjg > 0) {
                    $persenFjg = ($nowFjg - $beforeFjg) / $beforeFjg * 100;
                    $statusFjg = $persenFjg > 0 ? 'NAIK' : 'TURUN';
                } else {
                    $statusFjg = 'TERCOVER';
                    $persenFjg = 0;
                }
            }
            $si->setCellValue('J'.$barOM, '- FRATEKINDO');
            $si->setCellValue('K'.$barOM, $statusFjg);
            $si->setCellValue('L'.$barOM, number_format(abs($persenFjg),2,',','.').'%');
            $si->getStyle('L'.$barOM)->getAlignment()->setHorizontal('right');
            $barOM++;
            
            $nowCus = isset($headers[$dh->bulan]) ? $headers[$dh->bulan]['overtime_cus'] : 0;
            $beforeCus = isset($headers[($dh->bulan-1)]) ? $headers[($dh->bulan-1)]['overtime_cus'] : 0;
            if(isset($oncallJumlahs[$dh->bulan])) $nowCus -= $oncallJumlahs[$dh->bulan];
            if(isset($oncallJumlahs[($dh->bulan-1)])) $beforeCus -= $oncallJumlahs[($dh->bulan-1)];
            if($nowCus == 0 && $beforeCus == 0) {
                $statusCus = 'TETAP';
                $persenCus = 0;
            } else if($beforeCus == 0) {
                $statusCus = $nowCus > 0 ? 'NAIK' : 'TURUN';
                $persenCus = 100;
            } else {
                if($nowCus == 0) {
                    $statusCus = 'TURUN';
                    $persenCus = 100;
                } else if($nowCus > 0) {
                    $persenCus = ($nowCus - $beforeCus) / $beforeCus * 100;
                    $statusCus = $persenCus > 0 ? 'NAIK' : 'TURUN';
                } else {
                    $statusCus = 'TERCOVER';
                    $persenCus = 0;
                }
            }
            $statusOT = $statusCus; // Fallback for Tercover logic later
            $si->setCellValue('J'.$barOM, '- CUSTOMER');
            $si->setCellValue('K'.$barOM, $statusCus);
            $si->setCellValue('L'.$barOM, number_format(abs($persenCus),2,',','.').'%');
            $si->getStyle('L'.$barOM)->getAlignment()->setHorizontal('right');
            $barOM++;
            $countPot = 0;
            foreach ($dataDetails as $dt) {
                if ($dt->pot_cuti_jumlah > 0) $countPot++;
            }
            $maxBar = max($barOT, $barOM, $barOTAwal + $countPot);

            if($barOT < $maxBar) {
                for ($k = $barOT; $k < $maxBar; $k++) {
                    $si->setCellValue('F'.$k, '');
                    $si->mergeCells('F'.$k.':H'.$k);
                    $si->setCellValue('I'.$k, '');
                }
                $barOT = $maxBar;
            }
            if($barOM <= $maxBar) {
                for ($k = $barOM; $k <= $maxBar; $k++) {
                    $si->setCellValue('J'.$k, '');
                    $si->setCellValue('K'.$k, '');
                    $si->setCellValue('L'.$k, '');
                }
                $barOM = $maxBar;
            }
            $si->setCellValue('F'.$barOT, 'JUMLAH');
            $si->mergeCells('F'.$barOT.':H'.$barOT);
            $si->setCellValue('I'.$barOT, '=SUM(I'.$barOTAwal.':I'.($barOT-1).')');
            if( $statusOT == 'TERCOVER') {
                $si->setCellValue('J'.$barOT, 'Tercover On Call Customer');
            }
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOTAwal.':'.'I'.($barOT-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('J'.$barOTAwal.':'.'L'.($barOT-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOT.':'.'I'.$barOT)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('J'.$barOT.':'.'L'.$barOT)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('F'.$barOT.':'.'I'.$barOT)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('I'.$barOTAwal.':'.'I'.$barOT)->getNumberFormat()->setFormatCode('#,##0');

            $si->setCellValue('B'.$barPot, 'POTONGAN/KOMPENSASI IJIN /UNPAID LEAVE');
            $si->mergeCells('B'.$barPot.':E'.$barPot);
            $si->getStyle('B'.$barPot.':E'.$barPot)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('B'.$barPot.':E'.$barPot)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('B'.$barPot.':E'.$barPot)->getAlignment()->setHorizontal('center')->setVertical('center');
            $barPotAwal = $barPot + 1;
            $k = $barPotAwal;
            foreach ($dataDetails as $dt) {
                if ($dt->pot_cuti_jumlah > 0) {
                    $namaDepan = explode(' ', $dt->karyawan->nama)[0];
                    $si->setCellValue('B'.$k, $namaDepan . " (" . $dt->karyawan->area->kode . ")");
                    $si->setCellValue('C'.$k, $dt->pot_cuti_keterangan);
                    $si->mergeCells('C'.$k.':D'.$k);
                    $si->setCellValue('E'.$k, "'= " . $dt->pot_cuti_hari . " HR");
                    $k++;
                }
            }
            if ($k <= $barOT) {
                for (; $k <= $barOT; $k++) {
                    $si->setCellValue('C'.$k, '');
                    $si->mergeCells('C'.$k.':D'.$k);
                }
            } else {
                $barOT = $k - 1;
            }
            $si->getStyle('B'.$barPotAwal.':'.'E'.$barOT)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('B'.$barPotAwal.':'.'E'.$barOT)->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('B'.$barPotAwal.':'.'E'.$barOT)->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('B'.$barPot.':E'.$barOT)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            
            $barTTD = $barPot - 1; // Align horizontally with OVERTIME header!
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
                if($k == 8 || $k == 9) { // I and J
                    $si->getColumnDimension($arrkol[$k])->setWidth(92, Dimension::UOM_PIXELS);
                } else {
                    $si->getColumnDimension($arrkol[$k])->setWidth(85, Dimension::UOM_PIXELS);
                }
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
            if($adaPotKompensasi) {
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'list_payroll_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpFile);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="LIST_PAYROLL_'.substr($tahun, -2). '.xlsx"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: public');

        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    public function rekapGaji($tahun) {
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'Q';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataDetails = $this->repoDetail->findAll(['tahun' => $tahun]);
        $details = array();
        $dataKaryawan = array();
        foreach ($dataDetails as $dt) {
            $details[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id][$dt->bulan] = ($dt->gaji + $dt->kenaikan_gaji);
            $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
        }

        // Merge data payroll_phks: isi slot bulan yang tidak ada di payroll biasa
        $dataPhks = $this->repoPhk->findAll(['tahun' => $tahun]);
        foreach ($dataPhks as $dp) {
            $staf  = $dp->karyawan->staf;
            $area  = $dp->karyawan->area->nama;
            $kid   = $dp->karyawan->id;
            $bulan = $dp->bulan;
            if(!isset($dataKaryawan[$kid])) {
                $dataKaryawan[$kid] = $dp->karyawan;
            }
            // Hanya isi jika bulan tersebut belum ada dari payroll biasa
            if(!isset($details[$dp->tahun][$staf][$area][$kid][$bulan])) {
                $details[$dp->tahun][$staf][$area][$kid][$bulan] = ($dp->gaji + $dp->kenaikan_gaji);
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
            $si->setCellValue('C'.$bar, 'MASA KERJA');
            $si->mergeCells('C'.$bar.':C'.($bar+1));
            $si->setCellValue('D'.$bar, 'TANGGAL LAHIR');
            $si->mergeCells('D'.$bar.':D'.($bar+1));
            $si->setCellValue('E'.$bar, 'B U L A N');
            $si->mergeCells('E'.$bar.':P'.$bar);
            $si->setCellValue('Q'.$bar, 'TOTAL IDR');
            $si->mergeCells('Q'.$bar.':Q'.($bar+1));
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
                        $si->setCellValue('B'.$bar, $this->afAbbreviateName($dkaryawan->nama));
                        $si->setCellValue('C'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_masuk)));
                        $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_lahir)));
                        for ($k=1; $k <= 12; $k++) {
                            $si->setCellValue($kol[$k+3].$bar, isset($bulans[$k]) ? ($bulans[$k] > 0 ? $bulans[$k] : '') : '');
                        }
                        $si->setCellValue('Q'.$bar, '=SUM(E'.$bar.':P'.$bar.')');
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $bar+=2;

            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            for ($k = 4; $k <= 16; $k++) { // E s/d Q
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
            $si->getStyle('C7:D'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('C7:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E7:'.$kol_akhir.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(210, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(80, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(80, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= 15; $k++) { // E s/d P
                $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('Q')->setWidth(130, Dimension::UOM_PIXELS);
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

    public function listPHK($tahun_awal, $tahun_akhir) {
        $kol = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ");
        $kol_akhir = 'Q';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10)->setBold(TRUE);

        $dataRepoUangPhk = $this->repoUangPhk->findAll(['tahun_awal' => $tahun_awal, 'tahun_akhir' => $tahun_akhir]);
        $dataUangPhk = array();
        $dataKaryawan = array();
        foreach ($dataRepoUangPhk as $dt) {
            $dataUangPhk[$dt->tahun][$dt->karyawan->staf][$dt->karyawan->area->nama][$dt->karyawan->id] = $dt;
            $dataKaryawan[$dt->karyawan->id] = $dt->karyawan;
        }
        ksort($dataUangPhk);

        $i = 0;
        foreach ($dataUangPhk as $tahun => $stafs) {
            if($i > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($i);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle((string)$tahun);
            $si->freezePane('C8');

            $bar = 2;
            $si->setCellValue('A'.$bar, 'LIST PHK PT.FRATEKINDO JAYA GEMILANG');
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar++;
            $si->setCellValue('A'.$bar, 'PERIODE : TAHUN '.$tahun);
            $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
            $bar +=2;
            $si->setCellValue('A'.$bar, 'NO');
            $si->mergeCells('A'.$bar.':A'.($bar+2));
            $si->setCellValue('B'.$bar, 'NAMA KARYAWAN');
            $si->mergeCells('B'.$bar.':B'.($bar+2));
            $si->setCellValue('C'.$bar, 'MASA KERJA');
            $si->mergeCells('C'.$bar.':C'.($bar+2));
            $si->setCellValue('D'.$bar, 'TANGGAL LAHIR');
            $si->mergeCells('D'.$bar.':D'.($bar+2));
            $si->setCellValue('E'.$bar, 'K A L K U L A S I');
            $si->mergeCells('E'.$bar.':K'.$bar);
            $si->setCellValue('L'.$bar, 'P O T O N G A N');
            $si->mergeCells('L'.$bar.':O'.$bar);
            $si->setCellValue('P'.$bar, 'JUMLAH IDR');
            $si->mergeCells('P'.$bar.':P'.($bar+2));
            $si->setCellValue('Q'.$bar, 'KETERANGAN');
            $si->mergeCells('Q'.$bar.':Q'.($bar+2));
            $bar++;
            $si->setCellValue('E'.$bar, 'KOMPENSASI');
            $si->mergeCells('E'.$bar.':E'.($bar+1));
            $si->setCellValue('F'.$bar, 'PESANGON');
            $si->mergeCells('F'.$bar.':F'.($bar+1));
            $si->setCellValue('G'.$bar, 'MASA KERJA');
            $si->mergeCells('G'.$bar.':G'.($bar+1));
            $si->setCellValue('H'.$bar, 'UANG PISAH');
            $si->mergeCells('H'.$bar.':H'.($bar+1));
            $si->setCellValue('I'.$bar, 'SISA CUTI');
            $si->mergeCells('I'.$bar.':J'.$bar);
            $si->setCellValue('K'.$bar, 'LAIN-LAIN');
            $si->mergeCells('K'.$bar.':K'.($bar+1));
            $si->setCellValue('L'.$bar, 'KAS/CICILAN');
            $si->mergeCells('L'.$bar.':L'.($bar+1));
            $si->setCellValue('M'.$bar, 'UNPAID LEAVE');
            $si->mergeCells('M'.$bar.':N'.$bar);
            $si->setCellValue('O'.$bar, 'LAIN-LAIN');
            $si->mergeCells('O'.$bar.':O'.($bar+1));
            $bar++;
            $si->setCellValue('I'.$bar, 'HARI');
            $si->setCellValue('J'.$bar, 'IDR');
            $si->setCellValue('M'.$bar, 'HARI');
            $si->setCellValue('N'.$bar, 'IDR');
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
                    foreach ($karyawan_ids as $karyawan_id => $uangPhk) {
                        $dkaryawan = $dataKaryawan[$karyawan_id];
                        $si->setCellValue('A'.$bar, $nomor);
                        $si->setCellValue('B'.$bar, $this->afAbbreviateName($dkaryawan->nama));
                        
                        $tgl_masuk = $dkaryawan->tanggal_masuk ? date('d-m-Y', strtotime($dkaryawan->tanggal_masuk)) : '';
                        $tgl_keluar = $dkaryawan->tanggal_keluar ? date('d-m-Y', strtotime($dkaryawan->tanggal_keluar)) : '';
                        $si->setCellValue('C'.$bar, $tgl_masuk . ' s/d ' . $tgl_keluar);
                        
                        $si->setCellValue('D'.$bar, Date::PHPToExcel(strtotime($dkaryawan->tanggal_lahir)));
                        $si->setCellValue('E'.$bar, $uangPhk->kompensasi > 0 ? $uangPhk->kompensasi : '');
                        $si->setCellValue('F'.$bar, $uangPhk->pesangon > 0 ? $uangPhk->pesangon : '');
                        $si->setCellValue('G'.$bar, $uangPhk->masa_kerja > 0 ? $uangPhk->masa_kerja : '');
                        $si->setCellValue('H'.$bar, $uangPhk->uang_pisah > 0 ? $uangPhk->uang_pisah : '');
                        $si->setCellValue('I'.$bar, $uangPhk->sisa_cuti_hari > 0 ? $uangPhk->sisa_cuti_hari : '');
                        $si->setCellValue('J'.$bar, $uangPhk->sisa_cuti_jumlah > 0 ? $uangPhk->sisa_cuti_jumlah : '');
                        $si->setCellValue('K'.$bar, $uangPhk->lain > 0 ? $uangPhk->lain : '');
                        $si->setCellValue('L'.$bar, $uangPhk->pot_kas > 0 ? $uangPhk->pot_kas : '');
                        $si->setCellValue('M'.$bar, $uangPhk->pot_cuti_hari > 0 ? $uangPhk->pot_cuti_hari : '');
                        $si->setCellValue('N'.$bar, $uangPhk->pot_cuti_jumlah > 0 ? $uangPhk->pot_cuti_jumlah : '');
                        $si->setCellValue('O'.$bar, $uangPhk->pot_lain > 0 ? $uangPhk->pot_lain : '');
                        $si->setCellValue('P'.$bar, '=(E'.$bar.'+F'.$bar.'+G'.$bar.'+H'.$bar.'+J'.$bar.'+K'.$bar.')-(L'.$bar.'+N'.$bar.'+O'.$bar.')');
                        $kets = [];
                        if (!empty($uangPhk->keterangan)) $kets[] = $uangPhk->keterangan;
                        if (!empty($uangPhk->ket_lain)) $kets[] = $uangPhk->ket_lain;
                        if (!empty($uangPhk->ket_pot_lain)) $kets[] = $uangPhk->ket_pot_lain;
                        $si->setCellValue('Q'.$bar, implode('; ', $kets));
                        $bar++;
                        $nomor++;
                    }
                }
            }
            $bar+=2;

            $si->setCellValue('A'.$bar, 'TOTAL');
            $si->mergeCells('A'.$bar.':D'.$bar);
            for ($k = 4; $k <= 15; $k++) { // E s/d P
                $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].'8:'.$kol[$k].($bar-1).')');
            }

            $si->getStyle('A2:A3')->getFont()->setName('Arial')->setSize(16)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
            $si->getStyle('A1:A'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('A5:'.$kol_akhir.'7')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
            $si->getStyle('A5:'.$kol_akhir.'7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A5:'.$kol_akhir.'7')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFF00');
            $si->getStyle('A8:'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A8:'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A8:'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getFont()->getColor()->setARGB('0000FF');
            $si->getStyle('L5:O'.$bar)->getFont()->getColor()->setARGB('FF0000');
            $si->getStyle('C8:D'.$bar)->getAlignment()->setHorizontal('center');
            $si->getStyle('D8:D'.$bar)->getNumberFormat()->setFormatCode('dd-mm-yy');
            $si->getStyle('E8:P'.$bar)->getNumberFormat()->setFormatCode('#,##0');

            $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
            $si->getColumnDimension('B')->setWidth(210, Dimension::UOM_PIXELS);
            $si->getColumnDimension('C')->setWidth(176, Dimension::UOM_PIXELS);
            $si->getColumnDimension('D')->setWidth(80, Dimension::UOM_PIXELS);
            for ($k = 4; $k <= 14; $k++) { // E s/d O
                $si->getColumnDimension($kol[$k])->setWidth(100, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension('P')->setWidth(130, Dimension::UOM_PIXELS);
            $si->getColumnDimension('Q')->setWidth(350, Dimension::UOM_PIXELS);
            $i++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="LIST_PHK.xlsx"');
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

    
    public function listSalary() {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('LIST SLRY FJG');
        
        // Title
        $si->setCellValue('A2', 'SALARY PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A2:K2');
        $si->getStyle('A2')->getFont()->setSize(14)->getColor()->setARGB('0000FF');
        $si->getStyle('A2')->getFont()->setUnderline(true);
        $si->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        // Headers
        $si->setCellValue('A4', 'NO'); $si->mergeCells('A4:A5');
        $si->setCellValue('B4', 'NAMA KARYAWAN'); $si->mergeCells('B4:B5');
        $si->setCellValue('C4', 'MASA KERJA'); $si->mergeCells('C4:C5');
        $si->setCellValue('D4', 'TGL LAHIR'); $si->mergeCells('D4:D5');
        $si->setCellValue('E4', 'JABATAN'); $si->mergeCells('E4:E5');
        $si->setCellValue('F4', 'GAJI'); $si->mergeCells('F4:F5');
        
        $si->setCellValue('G4', 'TUNJANGAN TETAP & TDK TETAP'); $si->mergeCells('G4:H4');
        $si->setCellValue('G5', 'U/MAKAN & TRANSP');
        $si->setCellValue('H5', 'XXX');
        
        $si->setCellValue('I4', 'STATUS OVERTIME'); $si->mergeCells('I4:I5');
        
        $si->setCellValue('J4', 'STATUS KARYAWAN'); $si->mergeCells('J4:K4');
        $si->setCellValue('J5', 'TETAP / PKWTT');
        $si->setCellValue('K5', 'KONTRAK / PKWT / PERCOBAAN');
        
        $si->getStyle('A4:K5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A4:K5')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A4:K5')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $si->getRowDimension(4)->setRowHeight(30);
        $si->getRowDimension(5)->setRowHeight(30);
        
        // Column Widths
        $widths = ['A'=>5, 'B'=>37, 'C'=>12, 'D'=>12, 'E'=>50, 'F'=>20, 'G'=>20, 'H'=>20, 'I'=>12, 'J'=>20, 'K'=>48];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }
        
        // Data
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $upahsData = \App\Models\Upah::all()->keyBy('karyawan_id');
        $details = [];
        $totalKaryawanPerStatus = [];
        $totalKaryawanPerStatusPerArea = [];
        $totalKaryawanPerArea = [];
        $totalKaryawan = 0;
        $statusNamaToId = [];
        
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;
            
            $statusNama = $d->statusKerja ? $d->statusKerja->nama : 'Lain-lain';
            
            if (!isset($totalKaryawanPerStatus[$statusNama])) {
                $totalKaryawanPerStatus[$statusNama] = 0;
                $statusNamaToId[$statusNama] = $d->status_kerja_id;
            }
            $totalKaryawanPerStatus[$statusNama]++;
            
            if (!isset($totalKaryawanPerArea[$kodeArea])) $totalKaryawanPerArea[$kodeArea] = 0;
            $totalKaryawanPerArea[$kodeArea]++;
            
            if (!isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea])) $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] = 0;
            $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]++;
            
            $totalKaryawan++;
        }

        $si->freezePane('C6');
        $bar = 6;
        $nomor = 1;
        
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $si->setCellValue('B'.$bar, 'NON STAF :');
                $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                $bar++;
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $si->setCellValue('B'.$bar, $area.' :');
                    $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                    $bar++;
                }
                
                foreach ($karyawans as $d) {
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->nama);
                    
                    $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $si->setCellValue('C'.$bar, $tgl_masuk);
                    
                    $tgl_lahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                    $si->setCellValue('D'.$bar, $tgl_lahir);
                    
                    $si->setCellValue('E'.$bar, $d->jabatan ? $d->jabatan->nama : '');
                    
                    $upah = isset($upahsData[$d->id]) ? $upahsData[$d->id] : null;
                    $gaji = $upah ? $upah->gaji : 0;
                    $si->setCellValue('F'.$bar, $gaji);
                    $si->getStyle('F'.$bar)->getNumberFormat()->setFormatCode('#,##0');
                    
                    $uang_makan = $upah ? $upah->uang_makan : 0;
                    $si->setCellValue('G'.$bar, $uang_makan);
                    $si->getStyle('G'.$bar)->getNumberFormat()->setFormatCode('#,##0');
                    
                    $si->setCellValue('H'.$bar, '-');
                    
                    $overtime = ($upah && $upah->overtime == 'Y') ? 'OT' : 'NON OT';
                    $si->setCellValue('I'.$bar, $overtime);
                    
                    $statusNama = $d->statusKerja ? strtolower($d->statusKerja->nama) : '';
                    if (strpos($statusNama, 'tetap') !== false || strpos($statusNama, 'pkwtt') !== false) {
                        $si->setCellValue('J'.$bar, 'TETAP/PKWTT');
                    } else if (strpos($statusNama, 'kontrak') !== false || strpos($statusNama, 'pkwt') !== false || strpos($statusNama, 'percobaan') !== false) {
                        $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                        if ($perjanjians->count() > 0) {
                            $latest = $perjanjians->last();
                            $bulanMap = [
                                1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR', 
                                5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGUSTUS', 
                                9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
                            ];
                            $awal = strtotime($latest->tanggal_awal);
                            $strAwal = date('d', $awal) . ' ' . $bulanMap[(int)date('n', $awal)] . date('\'y', $awal);
                            
                            $strAkhir = '';
                            if ($latest->tanggal_akhir) {
                                $akhir = strtotime($latest->tanggal_akhir);
                                $strAkhir = ' S/D ' . date('d', $akhir) . ' ' . $bulanMap[(int)date('n', $akhir)] . date('\'y', $akhir);
                            }
                            $si->setCellValue('K'.$bar, 'PER : ' . $strAwal . $strAkhir);
                        } else {
                            $si->setCellValue('K'.$bar, ''); // biarkan kosong jika tidak ada data
                        }
                    } else {
                        // default to K if not Tetap
                        if ($statusNama) {
                            $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                            if ($perjanjians->count() > 0) {
                                $latest = $perjanjians->last();
                                $bulanMap = [
                                    1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR', 
                                    5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGUSTUS', 
                                    9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
                                ];
                                $awal = strtotime($latest->tanggal_awal);
                                $strAwal = date('d', $awal) . ' ' . $bulanMap[(int)date('n', $awal)] . date('\'y', $awal);
                                $strAkhir = '';
                                if ($latest->tanggal_akhir) {
                                    $akhir = strtotime($latest->tanggal_akhir);
                                    $strAkhir = ' S/D ' . date('d', $akhir) . ' ' . $bulanMap[(int)date('n', $akhir)] . date('\'y', $akhir);
                                }
                                $si->setCellValue('K'.$bar, 'PER : ' . $strAwal . $strAkhir);
                            } else {
                                $si->setCellValue('K'.$bar, ''); 
                            }
                        }
                    }
                    
                    $si->getStyle('A'.$bar.':K'.$bar)->getAlignment()->setVertical('center');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar.':D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('H'.$bar.':K'.$bar)->getAlignment()->setHorizontal('center');
                    
                    $statusId = $d->status_kerja_id;
                    $bgColor = null;
                    if ($statusId == '2') $bgColor = 'FFBBDEFB';
                    elseif ($statusId == '3') $bgColor = 'FFFFCC80';
                    elseif ($statusId == '4') $bgColor = 'FFEA80FC';
                    elseif ($statusId == '5') $bgColor = 'FFB9F6CA';
                    elseif ($statusId && $statusId != '1') $bgColor = 'FFF44336';
                    if ($bgColor) {
                        $si->getStyle('A'.$bar.':K'.$bar)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                    }
                    
                    $bar++;
                    $nomor++;
                }
                
                // 1 blank row after each area
                $bar++;
            }
        }
        
        $lastDataRow = $bar - 1;
        $si->setCellValue('A'.$bar, 'TOTAL GAJI POKOK KARYAWAN');
        $si->mergeCells('A'.$bar.':E'.$bar);
        $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
        
        $si->setCellValue('F'.$bar, '=SUM(F6:F'.$lastDataRow.')');
        $si->setCellValue('G'.$bar, '=SUM(G6:G'.$lastDataRow.')');
        $si->setCellValue('H'.$bar, '=SUM(H6:H'.$lastDataRow.')');
        
        $si->getStyle('A'.$bar.':H'.$bar)->getFont()->getColor()->setARGB('FFFF0000');
        $si->getStyle('A'.$bar.':H'.$bar)->getFont()->setBold(true);
        $si->getStyle('F'.$bar.':H'.$bar)->getNumberFormat()->setFormatCode('#,##0');
        
        if ($bar > 6) {
            $si->getStyle('A4:K'.$bar)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        
        $bar += 2;
        $dbAreas = \App\Models\Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
        $allAreas = $dbAreas;
        foreach (array_keys($totalKaryawanPerArea) as $a) {
            if (!in_array($a, $allAreas)) {
                $allAreas[] = $a;
            }
        }
        
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + count($allAreas));
        
        $si->setCellValue('B'.$bar, 'KETERANGAN STATUS KARYAWAN');
        $si->getStyle('B'.$bar)->getFont()->setBold(true);
        $bar++;
        
        foreach ($totalKaryawanPerStatus as $statusNama => $total) {
            $statusId = isset($statusNamaToId[$statusNama]) ? $statusNamaToId[$statusNama] : null;
            $textColor = 'FF000000'; // Default black
            if ($statusId == '2') $textColor = 'FF2196F3';
            elseif ($statusId == '3') $textColor = 'FFFF9800';
            elseif ($statusId == '4') $textColor = 'FF9C27B0';
            elseif ($statusId == '5') $textColor = 'FF4CAF50';
            elseif ($statusId && $statusId != '1') $textColor = 'FFF44336';
            
            $si->setCellValue('B'.$bar, $statusNama);
            $si->setCellValue('C'.$bar, ': ' . $total);
            
            $colIdx = 4; // Column D
            foreach ($allAreas as $kodeArea) {
                $nilai = isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]) ? $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] : 0;
                $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $si->setCellValue($cellName.$bar, $kodeArea . ': ' . $nilai);
                $colIdx++;
            }
            
            $si->getStyle('B'.$bar.':'.$lastCol.$bar)->getFont()->getColor()->setARGB($textColor);
            
            $bar++;
        }
        
        $si->setCellValue('B'.$bar, 'TOTAL KARYAWAN');
        $si->setCellValue('C'.$bar, ': ' . $totalKaryawan);
        
        $colIdx = 4;
        foreach ($allAreas as $kodeArea) {
            $nilaiArea = isset($totalKaryawanPerArea[$kodeArea]) ? $totalKaryawanPerArea[$kodeArea] : 0;
            $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $si->setCellValue($cellName.$bar, $kodeArea . ': ' . $nilaiArea);
            $colIdx++;
        }
        $si->getStyle('B'.$bar.':'.$lastCol.$bar)->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
        
        $spreadsheet->setActiveSheetIndex(0);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="LIST_SALARY.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function listKaryawan() {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('DATA KARYAWAN');
        
        // Title
        $si->setCellValue('A3', 'LIST DATA KARYAWAN');
        $si->mergeCells('A3:R3');
        $si->getStyle('A3')->getFont()->setName('Malgun Gothic')->setSize(13);
        $si->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $si->getRowDimension(3)->setRowHeight(18);
        
        // Headers
        $si->setCellValue('A5', 'NO'); $si->mergeCells('A5:A6');
        $si->setCellValue('B5', 'N A M A'); $si->mergeCells('B5:B6');
        $si->setCellValue('C5', 'MASA KERJA'); $si->mergeCells('C5:C6');
        $si->setCellValue('D5', 'AGAMA'); $si->mergeCells('D5:D6');
        $si->setCellValue('E5', 'J A B A T A N'); $si->mergeCells('E5:E6');
        
        $si->setCellValue('F5', 'DOKUMEN KARYAWAN'); $si->mergeCells('F5:I5');
        $si->setCellValue('F6', 'NOMOR KK');
        $si->setCellValue('G6', 'NO.NIK/PASSEPORT');
        $si->setCellValue('H6', 'NAMA KARYAWAN & KELUARGA');
        $si->setCellValue('I6', 'TEMPAT & TGL LAHIR');
        
        $si->setCellValue('J5', 'ALAMAT SESUAI K T P'); $si->mergeCells('J5:J6');
        $si->setCellValue('K5', 'ALAMAT TINGGAL SEKARANG'); $si->mergeCells('K5:K6');
        $si->setCellValue('L5', 'NO.TLP'); $si->mergeCells('L5:L6');
        $si->setCellValue('M5', 'NO.TLP KELUARGA'); $si->mergeCells('M5:M6');
        $si->setCellValue('N5', 'STATUS'); $si->mergeCells('N5:N6');
        $si->setCellValue('O5', 'PENDIDIKAN TERAKHIR'); $si->mergeCells('O5:O6');
        $si->setCellValue('P5', 'NOMOR PERJANJIAN KERJA'); $si->mergeCells('P5:P6');
        $si->setCellValue('Q5', 'EMAIL PRIBADI'); $si->mergeCells('Q5:Q6');
        $si->setCellValue('R5', 'STATUS KARYAWAN PKWT / KONTRAK'); $si->mergeCells('R5:R6');
        
        $si->getStyle('A5:R6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:R6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:R6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $si->getRowDimension(5)->setRowHeight(18);
        $si->getRowDimension(6)->setRowHeight(15);
        
        // Column Widths
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>10.77, 'D'=>10.77, 'E'=>40.14, 'F'=>18.10, 'G'=>21.33, 'H'=>48.00, 'I'=>30.00, 'J'=>56, 'K'=>57.10, 'L'=>18.00, 'M'=>25.00, 'N'=>15.66, 'O'=>45.00, 'P'=>28.44, 'Q'=>28.55, 'R'=>40.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }
        
        // Data
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $upahsData = \App\Models\Upah::all()->keyBy('karyawan_id');
        $details = [];
        $totalKaryawanPerStatus = [];
        $totalKaryawanPerStatusPerArea = [];
        $totalKaryawanPerArea = [];
        $totalKaryawan = 0;
        $statusNamaToId = [];
        
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;
            
            $statusNama = $d->statusKerja ? $d->statusKerja->nama : 'Lain-lain';
            
            if (!isset($totalKaryawanPerStatus[$statusNama])) {
                $totalKaryawanPerStatus[$statusNama] = 0;
                $statusNamaToId[$statusNama] = $d->status_kerja_id;
            }
            $totalKaryawanPerStatus[$statusNama]++;
            
            if (!isset($totalKaryawanPerArea[$kodeArea])) $totalKaryawanPerArea[$kodeArea] = 0;
            $totalKaryawanPerArea[$kodeArea]++;
            
            if (!isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea])) $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] = 0;
            $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]++;
            
            $totalKaryawan++;
        }

        $si->freezePane('C7');
        $bar = 7;
        $nomor = 1;
        
        krsort($details);
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $si->setCellValue('B'.$bar, 'NON STAF :');
                $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                
                $bar++;
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $si->setCellValue('B'.$bar, $area.' :');
                    $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                    
                    $bar++;
                }
                
                foreach ($karyawans as $d) {
                    $keluargas = $d->keluargas()->get();
                    $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                    
                    $numKeluarga = $keluargas->count();
                    $numPerjanjian = $perjanjians->count();
                    $maxRows = max(1 + $numKeluarga, $numPerjanjian);
                    if ($maxRows < 1) $maxRows = 1;

                    $pendidikanFormat = '';
                    $pendidikanJurusan = '';
                    if ($d->pendidikan) {
                        $pendidikanFormat = $d->pendidikan->nama;
                        $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                        $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));
                        
                        if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                        if ($jurusan) {
                            if ($maxRows >= 2) {
                                $pendidikanJurusan = 'Jurusan: ' . $jurusan;
                            } else {
                                $pendidikanFormat .= ' , Jurusan: ' . $jurusan;
                            }
                        }
                    }

                    $startBar = $bar;
                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $si->setCellValue('A'.$bar, $nomor);
                            $si->setCellValue('B'.$bar, $d->nama);
                            
                            $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                            $si->setCellValue('C'.$bar, $tgl_masuk); // Masa Kerja
                            
                            $si->setCellValue('D'.$bar, $d->agama ? $d->agama->nama : '');
                            $si->setCellValue('E'.$bar, $d->jabatan ? $d->jabatan->nama : '');
                            
                            $si->setCellValue('F'.$bar, $d->nomor_kk ? "'".$d->nomor_kk : '');
                            $ktp_or_paspor = $d->nomor_ktp ? $d->nomor_ktp : $d->nomor_paspor;
                            $si->setCellValue('G'.$bar, $ktp_or_paspor ? "'".$ktp_or_paspor : ''); 
                            $si->setCellValue('H'.$bar, $d->nama); // Nama Karyawan & Keluarga
                            
                            $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                            $si->setCellValue('I'.$bar, $ttl);
                            
                            $alamat_ktp = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_ktp));
                            $alamat_tinggal = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_tinggal));
                            $si->setCellValue('J'.$bar, $alamat_ktp);
                            $si->setCellValue('K'.$bar, $alamat_tinggal);
                            $si->setCellValue('L'.$bar, $d->telepon ? "'".$d->telepon : '');
                            $si->setCellValue('M'.$bar, ''); // No TLP Keluarga
                            
                            $kawinStatus = $d->kawin == 'Y' ? 'Kawin' : ($d->kawin == 'N' ? 'Single' : 'Single Parent');
                            $jumlahAnak = $d->jumlahAnak();
                            $kawinFormat = $kawinStatus . ($jumlahAnak > 0 ? ' / ' . $jumlahAnak : '');
                            $si->setCellValue('N'.$bar, $kawinFormat); 
                            
                            $si->setCellValue('O'.$bar, $pendidikanFormat);
                            
                            $si->setCellValue('Q'.$bar, $d->email);
                            $statusNamaCell = $d->statusKerja ? $d->statusKerja->nama : '';
                            if ($numPerjanjian > 0) {
                                $latestPerjanjian = $perjanjians[$numPerjanjian - 1];
                                $statusId = $d->status_kerja_id;
                                if ($statusId == '1') {
                                    $tglAwal = date('j M\'y', strtotime($latestPerjanjian->tanggal_awal));
                                    $statusNamaCell .= " (Per: " . $tglAwal . ")";
                                } else {
                                    $tglAwal = strtoupper(date('j M\'y', strtotime($latestPerjanjian->tanggal_awal)));
                                    $tglAkhir = $latestPerjanjian->tanggal_akhir ? strtoupper(date('j M\'y', strtotime($latestPerjanjian->tanggal_akhir))) : '';
                                    $statusNamaCell .= " (" . $tglAwal . ($tglAkhir ? " - " . $tglAkhir : "") . ")";
                                }
                            }
                            $si->setCellValue('R'.$bar, $statusNamaCell);
                            
                            $statusId = $d->status_kerja_id;
                            $bgColor = null;
                            if ($statusId == '2') $bgColor = 'FFBBDEFB';
                            elseif ($statusId == '3') $bgColor = 'FFFFCC80';
                            elseif ($statusId == '4') $bgColor = 'FFEA80FC';
                            elseif ($statusId == '5') $bgColor = 'FFB9F6CA';
                            elseif ($statusId != '1') $bgColor = 'FFF44336';
                            if ($bgColor) {
                                $si->getStyle('A'.$bar.':R'.$bar)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                            }
                        } else {
                            // Family rows (i >= 1)
                            if ($i <= $numKeluarga) {
                                $keluarga = $keluargas[$i - 1];
                                $si->setCellValue('G'.$bar, $keluarga->nomor_ktp ? "'".$keluarga->nomor_ktp : '');
                                $si->setCellValue('H'.$bar, $keluarga->nama);
                                $ttlKeluarga = $keluarga->tempat_lahir . ', ' . ($keluarga->tanggal_lahir ? date('d-m-Y', strtotime($keluarga->tanggal_lahir)) : '');
                                $si->setCellValue('I'.$bar, $ttlKeluarga);
                                $si->setCellValue('M'.$bar, $keluarga->telepon ? "'".$keluarga->telepon : '');
                            }
                            
                            if ($i == 1 && $pendidikanJurusan != '') {
                                $si->setCellValue('O'.$bar, $pendidikanJurusan);
                            }
                        }
                        
                        // Perjanjian Kerja
                        if ($i < $numPerjanjian) {
                            $pj = $perjanjians[$i];
                            $si->setCellValue('P'.$bar, $pj->nomor);
                        }
                        
                        $si->getStyle('A'.$bar.':R'.$bar)->getAlignment()->setVertical('top');
                        $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('C'.$bar.':D'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('F'.$bar.':G'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('L'.$bar.':M'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('N'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('P'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('R'.$bar)->getAlignment()->setHorizontal('center');
                        
                        $bar++;
                    }
                    
                    if ($maxRows > 1) {
                        $si->mergeCells('J'.$startBar.':J'.($bar-1));
                        $si->mergeCells('K'.$startBar.':K'.($bar-1));
                        $si->getStyle('J'.$startBar.':K'.($bar-1))->getAlignment()->setVertical('top');
                        // Set WrapText for J, K, O, R for the whole block of this employee
                        $si->getStyle('J'.$startBar.':K'.($bar-1))->getAlignment()->setWrapText(true);
                        $si->getStyle('O'.$startBar.':O'.($bar-1))->getAlignment()->setWrapText(true);
                        $si->getStyle('R'.$startBar.':R'.($bar-1))->getAlignment()->setWrapText(true);
                    }
                    $si->getStyle('J'.$startBar.':K'.($bar-1))->getAlignment()->setWrapText(true);
                    $si->getStyle('O'.$startBar.':O'.($bar-1))->getAlignment()->setWrapText(true);
                    $si->getStyle('R'.$startBar.':R'.($bar-1))->getAlignment()->setWrapText(true);
                    
                    $bar++; // Insert empty row between employees
                    $nomor++;
                }
            }
        }
        
        if ($bar > 7) {
            $si->getStyle('A7:R'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A7:R'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A7:R'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
        }
        
        $bar += 2;
        $dbAreas = Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
        $allAreas = $dbAreas;
        foreach (array_keys($totalKaryawanPerArea) as $a) {
            if (!in_array($a, $allAreas)) {
                $allAreas[] = $a;
            }
        }
        
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + count($allAreas));
        
        $si->setCellValue('B'.$bar, 'KETERANGAN STATUS KARYAWAN');
        $si->getStyle('B'.$bar)->getFont()->setBold(true);
        $bar++;
        
        foreach ($totalKaryawanPerStatus as $statusNama => $total) {
            $statusId = isset($statusNamaToId[$statusNama]) ? $statusNamaToId[$statusNama] : null;
            $textColor = 'FF000000'; // Default black
            if ($statusId == '2') $textColor = 'FFBBDEFB';
            elseif ($statusId == '3') $textColor = 'FFFFCC80';
            elseif ($statusId == '4') $textColor = 'FFEA80FC';
            elseif ($statusId == '5') $textColor = 'FFB9F6CA';
            elseif ($statusId != '1' && $statusId != null) $textColor = 'FFF44336';
            
            $si->setCellValue('B'.$bar, $statusNama);
            $si->setCellValue('C'.$bar, ': ' . $total);
            
            $colIdx = 4; // Column D
            foreach ($allAreas as $kodeArea) {
                $nilai = isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]) ? $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] : 0;
                $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $si->setCellValue($cellName.$bar, $kodeArea . ': ' . $nilai);
                $colIdx++;
            }
            
            $si->getStyle('B'.$bar.':'.$lastCol.$bar)->getFont()->getColor()->setARGB($textColor);
            
            $bar++;
        }
        
        $si->setCellValue('B'.$bar, 'TOTAL KARYAWAN');
        $si->setCellValue('C'.$bar, ': ' . $totalKaryawan);
        
        $colIdx = 4;
        foreach ($allAreas as $kodeArea) {
            $nilaiArea = isset($totalKaryawanPerArea[$kodeArea]) ? $totalKaryawanPerArea[$kodeArea] : 0;
            $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $si->setCellValue($cellName.$bar, $kodeArea . ': ' . $nilaiArea);
            $colIdx++;
        }
        $si->getStyle('B'.$bar.':'.$lastCol.$bar)->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_KARYAWAN.xlsx"');
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

    public function listExKaryawan($tahun_awal, $tahun_akhir) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        
        $dataPhks = Phk::with(['karyawan', 'statusKerja', 'statusPhk'])
            ->whereYear('tanggal_akhir', '>=', $tahun_awal)
            ->whereYear('tanggal_akhir', '<=', $tahun_akhir)
            ->orderBy('tanggal_akhir', 'asc')
            ->get();
            
        $detailsByYear = [];
        foreach ($dataPhks as $phk) {
            $year = date('Y', strtotime($phk->tanggal_akhir));
            if ($phk->karyawan) {
                $staf = $phk->karyawan->staf;
                $area = $phk->karyawan->area ? $phk->karyawan->area->nama : 'Lainnya';
                $detailsByYear[$year][$staf][$area][] = $phk;
            }
        }
        
        $areasLookup = Area::pluck('urutan', 'nama')->toArray();
        foreach ($detailsByYear as &$stafs) {
            foreach ($stafs as &$areas) {
                uksort($areas, function($a, $b) use ($areasLookup) {
                    $urutanA = isset($areasLookup[$a]) ? $areasLookup[$a] : 999;
                    $urutanB = isset($areasLookup[$b]) ? $areasLookup[$b] : 999;
                    return $urutanA <=> $urutanB;
                });
            }
        }
        unset($stafs, $areas);
        
        if (empty($detailsByYear)) {
            $spreadsheet->getActiveSheet()->setTitle('KOSONG');
        }

        $sheetIndex = 0;
        foreach ($detailsByYear as $year => $details) {
            if ($sheetIndex > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($sheetIndex);
            $si = $spreadsheet->getActiveSheet();
            
            $si->setShowGridlines(false);
            $si->setTitle('EX KARYAWAN ' . $year);
            
            // Headers at row 3 and 4
            $si->setCellValue('A3', 'NO'); $si->mergeCells('A3:A4');
            $si->setCellValue('B3', 'N A M A'); $si->mergeCells('B3:B4');
            $si->setCellValue('C3', 'MASA KERJA'); $si->mergeCells('C3:C4');
            $si->setCellValue('D3', 'AGAMA'); $si->mergeCells('D3:D4');
            $si->setCellValue('E3', 'J A B A T A N'); $si->mergeCells('E3:E4');
            
            $si->setCellValue('F3', 'DOKUMEN KARYAWAN'); $si->mergeCells('F3:I3');
            $si->setCellValue('F4', 'NOMOR KK');
            $si->setCellValue('G4', 'NO.NIK/PASSEPORT');
            $si->setCellValue('H4', 'NAMA KARYAWAN & KELUARGA');
            $si->setCellValue('I4', 'TEMPAT & TGL LAHIR');
            
            $si->setCellValue('J3', 'ALAMAT SESUAI K T P'); $si->mergeCells('J3:J4');
            $si->setCellValue('K3', 'ALAMAT TINGGAL SEKARANG'); $si->mergeCells('K3:K4');
            $si->setCellValue('L3', 'NO.TLP'); $si->mergeCells('L3:L4');
            $si->setCellValue('M3', 'NO.TLP KELUARGA'); $si->mergeCells('M3:M4');
            $si->setCellValue('N3', 'STATUS'); $si->mergeCells('N3:N4');
            $si->setCellValue('O3', 'PENDIDIKAN TERAKHIR'); $si->mergeCells('O3:O4');
            $si->setCellValue('P3', 'NOMOR PERJANJIAN KERJA'); $si->mergeCells('P3:P4');
            $si->setCellValue('Q3', 'EMAIL PRIBADI'); $si->mergeCells('Q3:Q4');
            $si->setCellValue('R3', 'STATUS PHK KARYAWAN'); $si->mergeCells('R3:R4');
            
            $si->getStyle('A3:R4')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
            $si->getStyle('A3:R4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
            $si->getStyle('A3:R4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getRowDimension(3)->setRowHeight(18);
            $si->getRowDimension(4)->setRowHeight(15);
            
            // Column Widths
            $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>25.00, 'D'=>10.77, 'E'=>40.14, 'F'=>18.10, 'G'=>21.33, 'H'=>48.00, 'I'=>30.00, 'J'=>56, 'K'=>57.10, 'L'=>18.00, 'M'=>25.00, 'N'=>15.66, 'O'=>45.00, 'P'=>28.44, 'Q'=>28.55, 'R'=>45.00];
            foreach ($widths as $col => $width) {
                $si->getColumnDimension($col)->setWidth($width);
            }
            
            $si->freezePane('C5');
            $bar = 5;
            $nomor = 1;
            
            krsort($details);
            foreach ($details as $staf => $areas) {
                if($staf == 'N') {
                    $si->setCellValue('B'.$bar, 'NON STAF :');
                    $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                    $bar++;
                }
                foreach ($areas as $area => $phksGrouped) {
                    if($staf == 'Y') {
                        $si->setCellValue('B'.$bar, $area.' :');
                        $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('B'.$bar)->getFont()->getColor()->setARGB('0000FF');
                        $bar++;
                    }
                    
                    foreach ($phksGrouped as $phk) {
                        $d = $phk->karyawan;
                        
                        $keluargas = $d->keluargas()->get();
                        $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                        
                        $numKeluarga = $keluargas->count();
                        $numPerjanjian = $perjanjians->count();
                        $maxRows = max(1 + $numKeluarga, $numPerjanjian);
                        if ($maxRows < 1) $maxRows = 1;

                        $pendidikanFormat = '';
                        $pendidikanJurusan = '';
                        if ($d->pendidikan) {
                            $pendidikanFormat = $d->pendidikan->nama;
                            $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                            $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));
                            
                            if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                            if ($jurusan) {
                                if ($maxRows >= 2) {
                                    $pendidikanJurusan = 'Jurusan: ' . $jurusan;
                                } else {
                                    $pendidikanFormat .= ' , Jurusan: ' . $jurusan;
                                }
                            }
                        }

                        $startBar = $bar;
                        for ($i = 0; $i < $maxRows; $i++) {
                            if ($i == 0) {
                                $si->setCellValue('A'.$bar, $nomor);
                                $si->setCellValue('B'.$bar, $d->nama);
                                
                                $tgl_masuk = $phk->tanggal_awal ? date('d-m-Y', strtotime($phk->tanggal_awal)) : '';
                                $tgl_akhir = $phk->tanggal_akhir ? date('d-m-Y', strtotime($phk->tanggal_akhir)) : '';
                                $masaKerjaFormat = $tgl_masuk . ($tgl_akhir ? ' s/d ' . $tgl_akhir : '');
                                $si->setCellValue('C'.$bar, $masaKerjaFormat);
                                
                                $si->setCellValue('D'.$bar, $d->agama ? $d->agama->nama : '');
                                $si->setCellValue('E'.$bar, $d->jabatan ? $d->jabatan->nama : '');
                                
                                $si->setCellValue('F'.$bar, $d->nomor_kk ? "'".$d->nomor_kk : '');
                                $ktp_or_paspor = $d->nomor_ktp ? $d->nomor_ktp : $d->nomor_paspor;
                                $si->setCellValue('G'.$bar, $ktp_or_paspor ? "'".$ktp_or_paspor : '');
                                
                                $si->setCellValue('H'.$bar, $d->nama);
                                
                                $tgl_lahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                                $si->setCellValue('I'.$bar, $d->tempat_lahir . ', ' . $tgl_lahir);
                                
                                $alamat_ktp = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_ktp));
                                $alamat_tinggal = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_tinggal));
                                $si->setCellValue('J'.$bar, $alamat_ktp);
                                $si->setCellValue('K'.$bar, $alamat_tinggal);
                                $si->setCellValue('L'.$bar, $d->telepon ? "'".$d->telepon : '');
                                $si->setCellValue('M'.$bar, ''); // No TLP Keluarga
                                
                                $kawinStatus = $d->kawin == 'Y' ? 'Kawin' : ($d->kawin == 'N' ? 'Single' : 'Single Parent');
                                $jumlahAnak = $d->jumlahAnak();
                                $kawinFormat = $kawinStatus . ($jumlahAnak > 0 ? ' / ' . $jumlahAnak : '');
                                $si->setCellValue('N'.$bar, $kawinFormat); 
                                
                                $si->setCellValue('O'.$bar, $pendidikanFormat);
                                
                                $si->setCellValue('Q'.$bar, $d->email);
                                
                                $statusNama = $phk->statusKerja ? $phk->statusKerja->nama : '';
                                $statusPhkNama = $phk->statusPhk ? $phk->statusPhk->nama : '';
                                $keteranganPhk = $phk->keterangan ? ' ('.$phk->keterangan.')' : '';
                                $statusKolomR = $statusNama . ($statusPhkNama ? ' / ' . $statusPhkNama : '') . $keteranganPhk;
                                
                                $si->setCellValue('R'.$bar, $statusKolomR);
                                
                            } else {
                                // Family rows (i >= 1)
                                if ($i <= $numKeluarga) {
                                    $kel = $keluargas[$i - 1];
                                    $si->setCellValue('H'.$bar, $kel->nama);
                                    $tgl_lahir_kel = $kel->tanggal_lahir ? date('d-m-Y', strtotime($kel->tanggal_lahir)) : '';
                                    $si->setCellValue('I'.$bar, $kel->tempat_lahir . ', ' . $tgl_lahir_kel);
                                    
                                    $si->setCellValue('M'.$bar, $kel->telepon ? "'".$kel->telepon : '');
                                }
                                
                                // Second row for Pendidikan Jurusan
                                if ($i == 1 && $pendidikanJurusan != '') {
                                    $si->setCellValue('O'.$bar, $pendidikanJurusan);
                                }
                            }
                            
                            // Perjanjian Kerja
                            if ($i < $numPerjanjian) {
                                $pj = $perjanjians[$i];
                                $si->setCellValue('P'.$bar, $pj->nomor);
                            }
                            
                            $si->getStyle('A'.$bar.':R'.$bar)->getAlignment()->setVertical('top');
                            $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('C'.$bar.':D'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('F'.$bar.':G'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('L'.$bar.':M'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('N'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('P'.$bar)->getAlignment()->setHorizontal('center');
                            $si->getStyle('R'.$bar)->getAlignment()->setHorizontal('left');
                            
                            $bar++;
                        }
                        
                        // Set WrapText for J, K, O, R for the whole block of this employee
                        $si->getStyle('J'.$startBar.':K'.($bar-1))->getAlignment()->setWrapText(true);
                        $si->getStyle('O'.$startBar.':O'.($bar-1))->getAlignment()->setWrapText(true);
                        $si->getStyle('R'.$startBar.':R'.($bar-1))->getAlignment()->setWrapText(true);
                        
                        // Merge cells vertically for this employee
                        if ($maxRows > 1) {
                            $colsToMerge = ['J', 'K', 'R'];
                            foreach ($colsToMerge as $col) {
                                $si->mergeCells($col.$startBar.':'.$col.($bar-1));
                                $si->getStyle($col.$startBar.':'.$col.($bar-1))->getAlignment()->setVertical('top');
                            }
                        }
                        
                        // Add an empty row between employees
                        $bar++;
                        $nomor++;
                    }
                }
            }
            
            if ($bar > 5) {
                $si->getStyle('A5:R'.($bar-1))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $si->getStyle('A5:R'.($bar-1))->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $si->getStyle('A5:R'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
            }
            
            $sheetIndex++;
        }
        
        $spreadsheet->setActiveSheetIndex(0);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_EX_KARYAWAN.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
