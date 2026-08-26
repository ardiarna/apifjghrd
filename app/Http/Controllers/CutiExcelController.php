<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\CutiDate;
use App\Models\CutiDetail;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CutiExcelController extends Controller
{
    private $arrBulan = [
        1 => 'JAN', 2 => 'PEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN',
        7 => 'JUL', 8 => 'AGS', 9 => 'SEP', 10 => 'OKT', 11 => 'NOP', 12 => 'DES'
    ];

    private function getKolom() {
        $arrkol = [];
        $huruf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for($i = 0; $i < 26; $i++) {
            $arrkol[] = $huruf[$i];
        }
        for($i = 0; $i < 26; $i++) {
            for($j = 0; $j < 26; $j++) {
                $arrkol[] = $huruf[$i].$huruf[$j];
            }
        }
        return $arrkol;
    }

    private function setHeaderStyle($sheet, $range) {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '4F81BD']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);
    }

    public function jadwal($tahun)
    {
        $karyawans = Karyawan::with('jabatan')->where('aktif', 'Y')->orderBy('id')->get();
        $spreadsheet = new Spreadsheet();
        $arrkol = $this->getKolom();

        for($m = 1; $m <= 12; $m++) {
            if($m > 1) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($m - 1);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($this->arrBulan[$m] . substr($tahun, 2));
            $sheet->setShowGridlines(false);

            $sheet->setCellValue('A1', 'JADWAL CUTI KARYAWAN TAHUN ' . $tahun);
            $sheet->mergeCells('A1:AK1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->setCellValue('A2', 'PERIODE: ' . $this->arrBulan[$m]);
            
            $headers = ['NO', 'NAMA KARYAWAN', 'JABATAN', 'SISA CUTI TAHUN LALU', 'HAK CUTI TAHUNAN', 'TOTAL HAK CUTI', 'CUTI DIAMBIL', 'SISA CUTI'];
            foreach($headers as $i => $h) {
                $sheet->setCellValue($arrkol[$i].'4', $h);
                $sheet->mergeCells($arrkol[$i].'4:'.$arrkol[$i].'5');
            }
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $tahun);
            $colStart = count($headers);
            $sheet->setCellValue($arrkol[$colStart].'4', 'TANGGAL');
            $sheet->mergeCells($arrkol[$colStart].'4:'.$arrkol[$colStart + $daysInMonth - 1].'4');
            
            for($d = 1; $d <= $daysInMonth; $d++) {
                $sheet->setCellValue($arrkol[$colStart + $d - 1].'5', $d);
            }
            $this->setHeaderStyle($sheet, 'A4:'.$arrkol[$colStart + $daysInMonth - 1].'5');

            $row = 6;
            foreach($karyawans as $idx => $k) {
                $sheet->setCellValue('A'.$row, $idx + 1);
                $sheet->setCellValue('B'.$row, $k->nama);
                $sheet->setCellValue('C'.$row, $k->jabatan->nama ?? '');
                
                // Logic perhitungan cuti
                $jatah = \App\Models\JatahCutiTahunan::where('karyawan_id', $k->id)->where('tahun', $tahun)->first();
                $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
                $sisaCutiTahunLalu = $jatah ? ($jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
                $totalHak = $jmlCuti + $sisaCutiTahunLalu;
                
                $diambil = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cuti', function($q2) use ($k, $tahun) {
                          $q2->where('karyawan_id', $k->id)->where('tahun', $tahun)->where('jenis_form', '!=', 'CUTI_MASAL');
                      });
                })->count();

                $sheet->setCellValue('D'.$row, $sisaCutiTahunLalu);
                $sheet->setCellValue('E'.$row, $jmlCuti);
                $sheet->setCellValue('F'.$row, $totalHak);
                $sheet->setCellValue('G'.$row, $diambil);
                $sheet->setCellValue('H'.$row, $totalHak - $diambil);
                
                // Mapping dates
                $cutiDates = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                    $q->whereHas('cuti', function($q2) use ($k, $tahun) {
                        $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                    });
                })->whereMonth('tanggal', $m)->pluck('tanggal')->toArray();
                
                $tglMap = array_map(function($d) { return (int)date('d', strtotime($d)); }, $cutiDates);

                for($d = 1; $d <= $daysInMonth; $d++) {
                    if(in_array($d, $tglMap)) {
                        $sheet->setCellValue($arrkol[$colStart + $d - 1].$row, 'C');
                        $sheet->getStyle($arrkol[$colStart + $d - 1].$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFFFF00'); // Yellow
                    }
                }

                $sheet->getStyle('A'.$row.':'.$arrkol[$colStart + $daysInMonth - 1].$row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $row++;
            }
            foreach(range('A', 'H') as $colId) {
                $sheet->getColumnDimension($colId)->setAutoSize(true);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        return $this->downloadExcel($spreadsheet, "JADWAL_CUTI_$tahun.xlsx");
    }

    public function listCuti($tahun)
    {
        $karyawans_raw = Karyawan::with('jabatan', 'area')
            ->where('aktif', 'Y')
            ->whereHas('cutis', function($q) use ($tahun) {
                $q->where('tahun', $tahun)->whereHas('details', function($q2) {
                    $q2->where('kategori', 'KHUSUS');
                });
            })
            ->orderBy('id')
            ->get();
        
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        
        $arrkol = $this->getKolom();

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LIST CUTI ' . $tahun);
        $sheet->setShowGridlines(false);

        // 1. judul report dan periode merge dari kolom A sampai X
        $sheet->setCellValue('A1', 'CUTI KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $sheet->mergeCells('A1:X1');
        $sheet->getStyle('A1')->getFont()->setName('Malgun Gothic')->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->setCellValue('A2', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);
        $sheet->mergeCells('A2:X2');
        $sheet->getStyle('A2')->getFont()->setName('Malgun Gothic')->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // 2. tabel header ada 2 baris semua di merge kecuali ...
        $sheet->setCellValue('A4', 'NO'); $sheet->mergeCells('A4:A5');
        $sheet->setCellValue('B4', 'NAMA KARYAWAN'); $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('C4', 'MASA KERJA'); $sheet->mergeCells('C4:C5');
        $sheet->setCellValue('D4', 'JML CUTI'); $sheet->mergeCells('D4:D5');
        
        $sheet->setCellValue('E4', 'THN LALU'); $sheet->mergeCells('E4:F4');
        $sheet->setCellValue('E5', '+');
        $sheet->setCellValue('F5', '-');
        
        $sheet->setCellValue('G4', 'TOTAL CUTI'); $sheet->mergeCells('G4:G5');
        
        $sheet->setCellValue('H4', 'CUTI TAHUNAN'); $sheet->mergeCells('H4:S4');
        $bulans = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOP', 'DES'];
        foreach($bulans as $k => $b) {
            $sheet->setCellValue($arrkol[7 + $k].'5', $b);
        }
        
        $sheet->setCellValue('T4', 'SISA CUTI TAHUNAN'); $sheet->mergeCells('T4:T5');
        $sheet->setCellValue('U4', 'JML CUTI BERSAMA'); $sheet->mergeCells('U4:U5');
        $sheet->setCellValue('V4', 'JML IJIN'); $sheet->mergeCells('V4:V5');
        $sheet->setCellValue('W4', 'SISA CUTI'); $sheet->mergeCells('W4:W5');
        $sheet->setCellValue('X4', 'KETERANGAN'); $sheet->mergeCells('X4:X5');
        
        // Style Header
        $sheet->getStyle('A4:X5')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle('A4:X5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('A4:X5')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Freeze Panes (same as data karyawan, freeze pane on C7)
        $sheet->freezePane('C6');

        $row = 6;
        $idx = 1;
        
        // 3. Urutan karyawan harus sama persis seperti di excel data karyawan
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $sheet->setCellValue('B'.$row, 'NON STAF :');
                $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                $row++;
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $sheet->setCellValue('B'.$row, $area.' :');
                    $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                    $row++;
                }
                
                foreach ($karyawans as $k) {
                    $detailsKet = \App\Models\CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
                        ->whereIn('kategori', ['IJIN', 'CUTI_MASAL'])
                        ->whereHas('cuti', function($q) use ($k, $tahun) {
                            $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
                        })
                        ->get();
                    
                    $lines = [];
                    foreach($detailsKet as $det) {
                        if($det->dates->count() == 0) continue;
                        
                        $satuan = $det->jenisKhusus ? strtolower($det->jenisKhusus->satuan) : 'hari';
                        $lama = (int) $det->lama_hari;
                        
                        if ($lama > 5 || $satuan == 'bulan') {
                            $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                            $formatDate = function($tanggal) use ($months) {
                                $engMon = date('M', strtotime($tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                return ltrim(date('d', strtotime($tanggal)), '0') . ' ' . $indMon . ' \'' . date('y', strtotime($tanggal));
                            };
                            
                            $dates = $det->dates()->orderBy('tanggal')->get();
                            if($dates->count() == 1) {
                                $dateStr = $formatDate($dates->first()->tanggal);
                            } else {
                                $first = $dates->first()->tanggal;
                                $last = $dates->last()->tanggal;
                                $dateStr = $formatDate($first) . ' s/d ' . $formatDate($last);
                            }
                        } else {
                            $groupedDates = [];
                            foreach($det->dates()->orderBy('tanggal')->get() as $d) {
                                $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                                $engMon = date('M', strtotime($d->tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                $my = $indMon . ' \'' . date('y', strtotime($d->tanggal));
                                
                                $day = date('d', strtotime($d->tanggal));
                                if(!isset($groupedDates[$my])) $groupedDates[$my] = [];
                                $groupedDates[$my][] = ltrim($day, '0');
                            }
                            
                            $dateStrings = [];
                            foreach($groupedDates as $my => $days) {
                                $dateStrings[] = implode(', ', $days) . ' ' . $my;
                            }
                            
                            $dateStr = implode(', ', $dateStrings);
                        }
                        $ket = $det->keterangan ?? ($det->kategori == 'CUTI_MASAL' ? 'Cutber' : 'Ijin');
                        $lines[] = "Tgl " . $dateStr . " = " . $ket;
                    }
                    
                    $maxRows = max(1, count($lines));
                    $startRow = $row;
                    
                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $sheet->setCellValue('A'.$row, $idx++);
                            $sheet->setCellValue('B'.$row, $k->nama);
                            $tgl_masuk = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
                            $sheet->setCellValue('C'.$row, $tgl_masuk);
                            
                            $jatah = \App\Models\JatahCutiTahunan::where('karyawan_id', $k->id)->where('tahun', $tahun)->first();
                            $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
                            $plus = $jatah ? $jatah->plus_tahun_lalu : 0;
                            $min = $jatah ? $jatah->min_tahun_lalu : 0;
                            $totalHak = $jmlCuti + $plus - $min;

                            $sheet->setCellValue('D'.$row, $jmlCuti != 0 ? $jmlCuti : '');
                            $sheet->setCellValue('E'.$row, $plus != 0 ? $plus : '');
                            $sheet->setCellValue('F'.$row, $min != 0 ? $min : '');
                            $sheet->setCellValue('G'.$row, $totalHak != 0 ? $totalHak : '');

                            $totalDiambilTahunan = 0;
                            for($m=1; $m<=12; $m++) {
                                $diambilBulan = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                                    $q->where('kategori', 'TAHUNAN')
                                      ->whereHas('cuti', function($q2) use ($k, $tahun) {
                                          $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                      });
                                })->whereMonth('tanggal', $m)->count();
                                $totalDiambilTahunan += $diambilBulan;
                                $colIdx = 7 + $m - 1; 
                                $sheet->setCellValue($arrkol[$colIdx].$row, $diambilBulan != 0 ? $diambilBulan : '');
                            }

                            $sisaTahunan = $totalHak - $totalDiambilTahunan;
                            $sheet->setCellValue('T'.$row, $jatah ? $sisaTahunan : ($sisaTahunan != 0 ? $sisaTahunan : ''));
                            
                            $cutiBersama = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                                $q->where('kategori', 'CUTI_MASAL')
                                  ->whereHas('cuti', function($q2) use ($k, $tahun) {
                                      $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                  });
                            })->count();
                            $sheet->setCellValue('U'.$row, $cutiBersama != 0 ? $cutiBersama : '');
                            
                            $jmlIjin = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                                $q->where('kategori', 'IJIN')
                                  ->whereHas('cuti', function($q2) use ($k, $tahun) {
                                      $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                  });
                            })->count();
                            $sheet->setCellValue('V'.$row, $jmlIjin != 0 ? $jmlIjin : '');
                            
                            $sisaCuti = $sisaTahunan - $cutiBersama - $jmlIjin;
                                                        $sheet->setCellValue('W'.$row, $jatah ? $sisaCuti : ($sisaCuti != 0 ? $sisaCuti : ''));
                            
                            // Font Color Red for G, T, W
                            $sheet->getStyle('G'.$row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                            $sheet->getStyle('T'.$row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                            $sheet->getStyle('W'.$row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                        }
                        
                        if (isset($lines[$i])) {
                            $sheet->setCellValue('X'.$row, $lines[$i]);
                        } else {
                            $sheet->setCellValue('X'.$row, '');
                        }
                        
                        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                        $sheet->getStyle('D'.$row.':W'.$row)->getAlignment()->setHorizontal('center');
                        
                        $row++;
                    }
                    $row++; // blank row
                }
            }
        }
        $sheet->getStyle('A6:X'.($row-1))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A6:X'.($row-1))->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A6:X'.($row-1))->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>10.77, 'D'=>8.0, 'E'=>8.0, 'F'=>8.0, 'G'=>8.0, 'H'=>8.0, 'I'=>8.0, 'J'=>8.0, 'K'=>8.0, 'L'=>8.0, 'M'=>8.0, 'N'=>8.0, 'O'=>8.0, 'P'=>8.0, 'Q'=>8.0, 'R'=>8.0, 'S'=>8.0, 'T'=>10.0, 'U'=>10.0, 'V'=>10.0, 'W'=>10.0, 'X'=>70.0];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return $this->downloadExcel($spreadsheet, "LIST_CUTI_$tahun.xlsx");
    }

    public function form($id)
    {
        $cuti = Cuti::with('karyawan', 'details.dates')->find($id);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'FORM CUTI');
        if($cuti) {
            $sheet->setCellValue('A2', 'NAMA: ' . $cuti->karyawan->nama);
            // Keperluan moved to cuti details
        }
        return $this->downloadExcel($spreadsheet, "FORM_CUTI_$id.xlsx");
    }

    public function tanpaPotongan($tahun)
    {
        $karyawans_raw = Karyawan::with('jabatan', 'area')
            ->where('aktif', 'Y')
            ->whereHas('cutis', function($q) use ($tahun) {
                $q->where('tahun', $tahun)->whereHas('details', function($q2) {
                    $q2->where('kategori', 'KHUSUS');
                });
            })
            ->orderBy('id')
            ->get();
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        $arrkol = $this->getKolom();

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CUTI TANPA POTONGAN ' . $tahun);
        $sheet->setShowGridlines(false);

        // Row 1 is blank
        
        $sheet->setCellValue('A2', 'CUTI/IJIN TANPA MENGURANGI HAK KARYAWAN');
        $sheet->mergeCells('A2:R2');
        $sheet->getStyle('A2')->getFont()->setName('Malgun Gothic')->setSize(13)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->setCellValue('A3', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);
        $sheet->mergeCells('A3:R3');
        $sheet->getStyle('A3')->getFont()->setName('Malgun Gothic')->setSize(11)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Row 4 is blank

        $sheet->mergeCells('D5:O5');
        $sheet->setCellValue('D5', 'BULAN');
        
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'NO');
        
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'NAMA KARYAWAN');
        
        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('C5', 'MASA KERJA');
        
        $sheet->mergeCells('P5:P6');
        $sheet->setCellValue('P5', 'JUMLAH (HARI)');
        
        $sheet->mergeCells('Q5:Q6');
        $sheet->setCellValue('Q5', 'JUMLAH (BULAN)');
        
        $sheet->mergeCells('R5:R6');
        $sheet->setCellValue('R5', 'KETERANGAN');
        
        $months = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUNI', 'JULI', 'AUG', 'SEPT', 'OKT', 'NOP', 'DES'];
        foreach($months as $i => $m) {
            $sheet->setCellValue($arrkol[$i+3].'6', $m);
        }
        
        $sheet->getStyle('A5:R6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle('A5:R6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('A5:R6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $sheet->freezePane('C7');

        $row = 7;
        $idx = 1;
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $sheet->setCellValue('B'.$row, 'NON STAF :');
                $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                $row++;
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $sheet->setCellValue('B'.$row, $area.' :');
                    $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                    $row++;
                }
                
                foreach ($karyawans as $k) {
                    $detailsKet = \App\Models\CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
                        ->where('kategori', 'KHUSUS')
                        ->whereHas('cuti', function($q) use ($k, $tahun) {
                            $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
                        })
                        ->get();
                    
                    $lines = [];
                    foreach($detailsKet as $det) {
                        if($det->dates->count() == 0) continue;
                        
                        $satuan = $det->jenisKhusus ? strtolower($det->jenisKhusus->satuan) : 'hari';
                        $lama = (int) $det->lama_hari;
                        
                        if ($lama > 5 || $satuan == 'bulan') {
                            $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                            $formatDate = function($tanggal) use ($months) {
                                $engMon = date('M', strtotime($tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                return ltrim(date('d', strtotime($tanggal)), '0') . ' ' . $indMon . ' \'' . date('y', strtotime($tanggal));
                            };
                            
                            $dates = $det->dates()->orderBy('tanggal')->get();
                            if($dates->count() == 1) {
                                $dateStr = $formatDate($dates->first()->tanggal);
                            } else {
                                $first = $dates->first()->tanggal;
                                $last = $dates->last()->tanggal;
                                $dateStr = $formatDate($first) . ' s/d ' . $formatDate($last);
                            }
                        } else {
                            $groupedDates = [];
                            foreach($det->dates()->orderBy('tanggal')->get() as $d) {
                                $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                                $engMon = date('M', strtotime($d->tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                $my = $indMon . ' \'' . date('y', strtotime($d->tanggal));
                                
                                $day = date('d', strtotime($d->tanggal));
                                if(!isset($groupedDates[$my])) $groupedDates[$my] = [];
                                $groupedDates[$my][] = ltrim($day, '0');
                            }
                            
                            $dateStrings = [];
                            foreach($groupedDates as $my => $days) {
                                $dateStrings[] = implode(', ', $days) . ' ' . $my;
                            }
                            
                            $dateStr = implode(', ', $dateStrings);
                        }
                        $ket = $det->keterangan ?? 'Cuti Khusus';
                        $lines[] = "Tgl " . $dateStr . " = " . $ket;
                    }

                    $maxRows = max(1, count($lines));
                    
                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $sheet->setCellValue('A'.$row, $idx++);
                            $sheet->setCellValue('B'.$row, $k->nama);
                            $tgl_masuk = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
                            $sheet->setCellValue('C'.$row, $tgl_masuk);
                            
                            $totalHari = 0;
                            $totalBulan = 0;
                            foreach ($detailsKet as $det) {
                                $sat = strtolower($det->jenisKhusus->satuan ?? 'hari');
                                if ($sat == 'bulan') $totalBulan += $det->lama_hari;
                                else $totalHari += $det->lama_hari;
                            }
                            
                            for($m=1; $m<=12; $m++) {
                                $hariInMonth = 0;
                                $isBulan = false;
                                
                                foreach ($detailsKet as $det) {
                                    $sat = strtolower($det->jenisKhusus->satuan ?? 'hari');
                                    $datesInMonth = $det->dates->filter(function($d) use ($m) {
                                        return (int)date('m', strtotime($d->tanggal)) == $m;
                                    });
                                    
                                    if ($datesInMonth->count() > 0) {
                                        if ($sat == 'bulan') {
                                            $isBulan = true;
                                        } else {
                                            $hariInMonth += $datesInMonth->count();
                                        }
                                    }
                                }
                                
                                $colIdx = 3 + $m - 1; 
                                $cell = $arrkol[$colIdx].$row;
                                
                                if ($isBulan) {
                                    $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF99CC00');
                                }
                                
                                if ($hariInMonth > 0) {
                                    $sheet->setCellValue($cell, $hariInMonth);
                                }
                            }

                            $sheet->setCellValue('P'.$row, $totalHari != 0 ? $totalHari : '');
                            $sheet->setCellValue('Q'.$row, $totalBulan != 0 ? $totalBulan : '');
                        }
                        
                        if (isset($lines[$i])) {
                            $sheet->setCellValue('R'.$row, $lines[$i]);
                        } else {
                            $sheet->setCellValue('R'.$row, '');
                        }
                        
                        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                        $sheet->getStyle('D'.$row.':Q'.$row)->getAlignment()->setHorizontal('center');
                        
                        $row++;
                    }
                    $row++; // blank row
                }
            }
        }
        $sheet->getStyle('A5:R'.($row-1))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A5:R'.($row-1))->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A5:R'.($row-1))->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>10.77, 'D'=>8.0, 'E'=>8.0, 'F'=>8.0, 'G'=>8.0, 'H'=>8.0, 'I'=>8.0, 'J'=>8.0, 'K'=>8.0, 'L'=>8.0, 'M'=>8.0, 'N'=>8.0, 'O'=>8.0, 'P'=>15.0, 'Q'=>15.0, 'R'=>70.0];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return $this->downloadExcel($spreadsheet, "CUTI_TANPA_POTONGAN_$tahun.xlsx");
    }

    public function unpaid($tahun)
    {
        $karyawans_raw = Karyawan::with('jabatan', 'area')
            ->where('aktif', 'Y')
            ->whereHas('cutis', function($q) use ($tahun) {
                $q->where('tahun', $tahun)->whereHas('details', function($q2) {
                    $q2->where('kategori', 'KHUSUS');
                });
            })
            ->orderBy('id')
            ->get();
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        $arrkol = $this->getKolom();

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('UNPAID LEAVE ' . $tahun);
        $sheet->setShowGridlines(false);

        $sheet->setCellValue('A1', 'UNPAID LEAVE & GANTI HARI LIBUR PT.FRATEKINDO JAYA GEMILANG');
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1')->getFont()->setName('Malgun Gothic')->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->setCellValue('A2', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);
        $sheet->mergeCells('A2:Q2');
        $sheet->getStyle('A2')->getFont()->setName('Malgun Gothic')->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $headers = ['NO', 'NAMA KARYAWAN', 'MASA KERJA', 'JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUNI', 'JULI', 'AUG', 'SEPT', 'OKT', 'NOP', 'DES', 'JUMLAH', 'KETERANGAN'];
        foreach($headers as $i => $h) {
            $sheet->setCellValue($arrkol[$i].'4', $h);
        }
        
        $sheet->getStyle('A4:Q4')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle('A4:Q4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('A4:Q4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $sheet->freezePane('C5');

        $row = 5;
        $idx = 1;
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $sheet->setCellValue('B'.$row, 'NON STAF :');
                $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                $row++;
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $sheet->setCellValue('B'.$row, $area.' :');
                    $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                    $row++;
                }
                
                foreach ($karyawans as $k) {
                    $detailsKet = \App\Models\CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
                        ->whereIn('kategori', ['UNPAID', 'GANTI_HARI_LIBUR'])
                        ->whereHas('cuti', function($q) use ($k, $tahun) {
                            $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
                        })
                        ->get();
                    
                    $lines = [];
                    foreach($detailsKet as $det) {
                        if($det->dates->count() == 0) continue;
                        
                        $satuan = $det->jenisKhusus ? strtolower($det->jenisKhusus->satuan) : 'hari';
                        $lama = (int) $det->lama_hari;
                        
                        if ($lama > 5 || $satuan == 'bulan') {
                            $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                            $formatDate = function($tanggal) use ($months) {
                                $engMon = date('M', strtotime($tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                return ltrim(date('d', strtotime($tanggal)), '0') . ' ' . $indMon . ' \'' . date('y', strtotime($tanggal));
                            };
                            
                            $dates = $det->dates()->orderBy('tanggal')->get();
                            if($dates->count() == 1) {
                                $dateStr = $formatDate($dates->first()->tanggal);
                            } else {
                                $first = $dates->first()->tanggal;
                                $last = $dates->last()->tanggal;
                                $dateStr = $formatDate($first) . ' s/d ' . $formatDate($last);
                            }
                        } else {
                            $groupedDates = [];
                            foreach($det->dates()->orderBy('tanggal')->get() as $d) {
                                $months = ['Jan' => 'Jan', 'Feb' => 'Peb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ags', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nop', 'Dec' => 'Des'];
                                $engMon = date('M', strtotime($d->tanggal));
                                $indMon = $months[$engMon] ?? $engMon;
                                $my = $indMon . ' \'' . date('y', strtotime($d->tanggal));
                                
                                $day = date('d', strtotime($d->tanggal));
                                if(!isset($groupedDates[$my])) $groupedDates[$my] = [];
                                $groupedDates[$my][] = ltrim($day, '0');
                            }
                            
                            $dateStrings = [];
                            foreach($groupedDates as $my => $days) {
                                $dateStrings[] = implode(', ', $days) . ' ' . $my;
                            }
                            
                            $dateStr = implode(', ', $dateStrings);
                        }
                        $ket = $det->keterangan ?? 'Unpaid';
                        $lines[] = "Tgl " . $dateStr . " = " . $ket;
                    }

                    $maxRows = max(1, count($lines));
                    
                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $sheet->setCellValue('A'.$row, $idx++);
                            $sheet->setCellValue('B'.$row, $k->nama);
                            $tgl_masuk = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
                            $sheet->setCellValue('C'.$row, $tgl_masuk);
                            
                            $totalJumlah = 0;
                            for($m=1; $m<=12; $m++) {
                                $jml = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                                    $q->whereIn('kategori', ['UNPAID', 'GANTI_HARI_LIBUR'])
                                      ->whereHas('cuti', function($q2) use ($k, $tahun) {
                                          $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                      });
                                })->whereMonth('tanggal', $m)->count();
                                
                                $totalJumlah += $jml;
                                $colIdx = 3 + $m - 1; 
                                $sheet->setCellValue($arrkol[$colIdx].$row, $jml != 0 ? $jml : '');
                            }

                            $sheet->setCellValue('P'.$row, $totalJumlah != 0 ? $totalJumlah : '');
                        }
                        
                        if (isset($lines[$i])) {
                            $sheet->setCellValue('Q'.$row, $lines[$i]);
                        } else {
                            $sheet->setCellValue('Q'.$row, '');
                        }
                        
                        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                        $sheet->getStyle('D'.$row.':P'.$row)->getAlignment()->setHorizontal('center');
                        
                        $row++;
                    }
                    $row++; // blank row
                }
            }
        }
        $sheet->getStyle('A5:Q'.($row-1))->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A5:Q'.($row-1))->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A5:Q'.($row-1))->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>10.77, 'D'=>8.0, 'E'=>8.0, 'F'=>8.0, 'G'=>8.0, 'H'=>8.0, 'I'=>8.0, 'J'=>8.0, 'K'=>8.0, 'L'=>8.0, 'M'=>8.0, 'N'=>8.0, 'O'=>8.0, 'P'=>10.0, 'Q'=>70.0];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return $this->downloadExcel($spreadsheet, "UNPAID_LEAVE_GANTI_LIBUR_$tahun.xlsx");
    }

    private function downloadExcel($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
