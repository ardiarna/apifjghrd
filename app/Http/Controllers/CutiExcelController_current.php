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
use App\Models\JatahCutiTahunan;

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
        $karyawans_raw = Karyawan::with('jabatan', 'area')->where('aktif', 'Y')->orderBy('id')->get();
        $details = [];
        foreach ($karyawans_raw as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }
        krsort($details);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        $arrkol = $this->getKolom();

        $bulanIndo = ['', 'JANUARI', 'PEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOPEMBER', 'DESEMBER'];

        for($m = 1; $m <= 12; $m++) {
            if($m > 1) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($m - 1);
            $sheet = $spreadsheet->getActiveSheet();
            
            $sheetName = $this->arrBulan[$m] ?? $bulanIndo[$m];
            $sheet->setTitle($sheetName . ' ' . substr($tahun, 2));
            $sheet->setShowGridlines(false);
            
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $tahun);

            $row = 2;
            
            $drawTable = function($title, $isManajemen) use (&$sheet, &$row, $details, $m, $tahun, $daysInMonth, $arrkol, $bulanIndo) {
                $sheet->setCellValue('A'.$row, 'LIST CUTI ' . $bulanIndo[$m] . ' ' . $tahun);
                $sheet->mergeCells('A'.$row.':AK'.$row);
                $sheet->getStyle('A'.$row)->getFont()->setName('Malgun Gothic')->setSize(13)->getColor()->setARGB('0000FF');
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getRowDimension($row)->setRowHeight(20);
                
                $row++;
                
                $sheet->setCellValue('A'.$row, 'DIVISI : ' . $title);
                $sheet->mergeCells('A'.$row.':AK'.$row);
                $sheet->getStyle('A'.$row)->getFont()->setName('Malgun Gothic')->setSize(11)->getColor()->setARGB('0000FF');
                $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getRowDimension($row)->setRowHeight(18);
                
                $row += 2;
                $headerStartRow = $row;
                
                // Headers
                $sheet->setCellValue('A'.$row, 'NO'); $sheet->mergeCells('A'.$row.':A'.($row+1));
                $sheet->setCellValue('B'.$row, 'NAMA KARYAWAN'); $sheet->mergeCells('B'.$row.':B'.($row+1));
                
                $sheet->setCellValue('C'.$row, 'TANGGAL'); $sheet->mergeCells('C'.$row.':AG'.$row);
                
                $sheet->setCellValue('AH'.$row, 'SISA CUTI'); $sheet->mergeCells('AH'.$row.':AH'.($row+1));
                $sheet->setCellValue('AI'.$row, 'UNPAID LEAVE'); $sheet->mergeCells('AI'.$row.':AI'.($row+1));
                $sheet->setCellValue('AJ'.$row, 'GANTI HARI LIBUR'); $sheet->mergeCells('AJ'.$row.':AJ'.($row+1));
                $sheet->setCellValue('AK'.$row, 'CUTI KHUSUS'); $sheet->mergeCells('AK'.$row.':AK'.($row+1));
                
                $row++; // Row for 1-31
                
                $weekendCols = [];
                for($d = 1; $d <= 31; $d++) {
                    $col = $arrkol[$d + 1]; // C is index 2
                    if($d <= $daysInMonth) {
                        $sheet->setCellValue($col.$row, $d);
                        
                        $dateStr = sprintf('%04d-%02d-%02d', $tahun, $m, $d);
                        $dayOfWeek = date('N', strtotime($dateStr));
                        if($dayOfWeek == 6 || $dayOfWeek == 7) { 
                            $weekendCols[] = $col;
                            $sheet->getStyle($col.$row)->getFont()->getColor()->setARGB('FFFF0000'); 
                        }
                    } else {
                        $sheet->setCellValue($col.$row, '');
                    }
                }
                
                // Style Headers
                $sheet->getStyle('A'.$headerStartRow.':AK'.$row)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
                $sheet->getStyle('A'.$headerStartRow.':AK'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
                $sheet->getStyle('A'.$headerStartRow.':AK'.$row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $row++;
                $dataStartRow = $row;
                $idx = 1;
                
                foreach ($details as $staf => $areas) {
                    $hasPrintedStafLabel = false;
                    
                    foreach ($areas as $area => $karyawansGrp) {
                        $hasPrintedAreaLabel = false;
                        
                        foreach ($karyawansGrp as $k) {
                            $man = $k->manajemen ?? 'N';
                            
                            if ($isManajemen && $man != 'Y') continue;
                            if (!$isManajemen && $man == 'Y') continue;
                            
                            $cutiDatesMonth = CutiDate::with('cutiDetail')
                                ->whereMonth('tanggal', $m)
                                ->whereHas('cutiDetail.cuti', function($q) use ($k, $tahun) {
                                    $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                })->get();
                                
                            if (!$isManajemen && $cutiDatesMonth->count() == 0) continue;
                            
                            if ($staf == 'N' && !$hasPrintedStafLabel) {
                                $sheet->setCellValue('B'.$row, 'NON STAF :');
                                $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                                $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                                $sheet->getStyle('A'.$row.':AK'.$row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                                $row++;
                                $hasPrintedStafLabel = true;
                            }
                            
                            if ($staf == 'Y' && !$hasPrintedAreaLabel) {
                                $sheet->setCellValue('B'.$row, $area.' :');
                                $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal('center');
                                $sheet->getStyle('B'.$row)->getFont()->getColor()->setARGB('0000FF');
                                $sheet->getStyle('A'.$row.':AK'.$row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                                $row++;
                                $hasPrintedAreaLabel = true;
                            }
                            
                            $sheet->setCellValue('A'.$row, $idx++);
                            $sheet->setCellValue('B'.$row, $k->nama);
                    
                    $tglMap = [];
                    $cntUnpaid = 0;
                    $cntGanti = 0;
                    $ketKhususArr = [];
                    
                    foreach($cutiDatesMonth as $cd) {
                        $d = (int)date('d', strtotime($cd->tanggal));
                        $tglMap[$d] = true;
                        
                        $kat = $cd->cutiDetail->kategori ?? '';
                        if($kat == 'UNPAID') {
                            $cntUnpaid++;
                        }
                        elseif($kat == 'GANTI_HARI_LIBUR') {
                            $cntGanti++;
                            $ket = trim($cd->cutiDetail->keterangan ?? '');
                            if($ket && !in_array($ket, $ketKhususArr)) {
                                $ketKhususArr[] = $ket;
                            }
                        }
                        elseif($kat == 'KHUSUS') {
                            $ket = trim($cd->cutiDetail->keterangan ?? '');
                            if($ket && !in_array($ket, $ketKhususArr)) {
                                $ketKhususArr[] = $ket;
                            }
                        }
                    }
                    $ketKhususStr = implode(', ', $ketKhususArr);
                    
                    for($d = 1; $d <= 31; $d++) {
                        $col = $arrkol[$d + 1];
                        if($d <= $daysInMonth) {
                            if(isset($tglMap[$d])) {
                                $sheet->setCellValue($col.$row, 'X');
                            }
                        }
                    }
                    
                    $jatah = JatahCutiTahunan::where('karyawan_id', $k->id)->where('tahun', $tahun)->first();
                    $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
                    $sisaCutiTahunLalu = $jatah ? ($jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
                    $totalHak = $jmlCuti + $sisaCutiTahunLalu;
                    
                    $diambil = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                        $q->whereIn('kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
                          ->whereHas('cuti', function($q2) use ($k, $tahun) {
                              $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                          });
                    })->whereMonth('tanggal', '<=', $m)->count();
                    
                    $sheet->setCellValue('AH'.$row, $totalHak - $diambil);
                    $sheet->setCellValue('AI'.$row, $cntUnpaid > 0 ? $cntUnpaid : '');
                    $sheet->setCellValue('AJ'.$row, $cntGanti > 0 ? $cntGanti : '');
                    $sheet->setCellValue('AK'.$row, $ketKhususStr);
                    
                    $sheet->getStyle('A'.$row.':AK'.$row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('C'.$row.':AK'.$row)->getAlignment()->setHorizontal('center');
                    
                    $row++;
                        }
                    }
                }
                
                if ($row == $dataStartRow) {
                    $sheet->setCellValue('A'.$row, 'Tidak ada data');
                    $sheet->mergeCells('A'.$row.':AK'.$row);
                    $sheet->getStyle('A'.$row.':AK'.$row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                    $row++;
                }
                
                foreach($weekendCols as $col) {
                    $startR = $headerStartRow + 1;
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFont()->getColor()->setARGB('FFFFFFFF');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                }
            };
            
            $drawTable('STAF', false);
            
            $row += 3;
            
            $drawTable('MANAJEMEN', true);
            
            foreach(range('A', 'B') as $colId) {
                $sheet->getColumnDimension($colId)->setAutoSize(true);
            }
            $sheet->getColumnDimension('AH')->setWidth(12);
            $sheet->getColumnDimension('AI')->setWidth(15);
            $sheet->getColumnDimension('AJ')->setWidth(18);
            $sheet->getColumnDimension('AK')->setAutoSize(true);
            for($d = 1; $d <= 31; $d++) {
                $sheet->getColumnDimension($arrkol[$d + 1])->setWidth(4);
            }
            
            $sheet->freezePane('C8');
        }
        $spreadsheet->setActiveSheetIndex(0);
        return $this->downloadExcel($spreadsheet, "JADWAL_CUTI_$tahun.xlsx");
    }

    public function listCuti($tahunAwal, $tahunAkhir)
    {
        $karyawans_raw = Karyawan::with('jabatan', 'area')->where('aktif', 'Y')->orderBy('id')->get();
        
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

        $sheetIndex = 0;
        for($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            if($sheetIndex > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CUTI TH ' . $tahun);
        $sheet->setShowGridlines(false);

        // Row 1 blank
        
        // 1. judul report dan periode merge dari kolom A sampai X
        $sheet->setCellValue('A2', 'CUTI KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $sheet->mergeCells('A2:X2');
        $sheet->getStyle('A2')->getFont()->setName('Malgun Gothic')->setSize(13)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->setCellValue('A3', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);
        $sheet->mergeCells('A3:X3');
        $sheet->getStyle('A3')->getFont()->setName('Malgun Gothic')->setSize(11)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Row 4 blank

        // 2. tabel header ada 2 baris semua di merge kecuali ...
        $sheet->setCellValue('A5', 'NO'); $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('B5', 'NAMA KARYAWAN'); $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('C5', 'MASA KERJA'); $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('D5', 'JML CUTI'); $sheet->mergeCells('D5:D6');
        
        $sheet->setCellValue('E5', 'THN LALU'); $sheet->mergeCells('E5:F5');
        $sheet->setCellValue('E6', '+');
        $sheet->setCellValue('F6', '-');
        
        $sheet->setCellValue('G5', 'TOTAL CUTI'); $sheet->mergeCells('G5:G6');
        
        $sheet->setCellValue('H5', 'CUTI TAHUNAN'); $sheet->mergeCells('H5:S5');
        $bulans = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOP', 'DES'];
        foreach($bulans as $k => $b) {
            $sheet->setCellValue($arrkol[7 + $k].'6', $b);
        }
        
        $sheet->setCellValue('T5', 'SISA CUTI TAHUNAN'); $sheet->mergeCells('T5:T6');
        $sheet->setCellValue('U5', 'JML CUTI BERSAMA'); $sheet->mergeCells('U5:U6');
        $sheet->setCellValue('V5', 'JML IJIN'); $sheet->mergeCells('V5:V6');
        $sheet->setCellValue('W5', 'SISA CUTI'); $sheet->mergeCells('W5:W6');
        $sheet->setCellValue('X5', 'KETERANGAN'); $sheet->mergeCells('X5:X6');
        
        // Style Header
        $sheet->getStyle('A5:X6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle('A5:X6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('A5:X6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Freeze Panes (same as data karyawan, freeze pane on C7)
        $sheet->freezePane('C7');

        $row = 7;
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
                    $detailsKet = CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
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
                            
                            $jatah = JatahCutiTahunan::where('karyawan_id', $k->id)->where('tahun', $tahun)->first();
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
                                $diambilBulan = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
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
                            
                            $cutiBersama = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                                $q->where('kategori', 'CUTI_MASAL')
                                  ->whereHas('cuti', function($q2) use ($k, $tahun) {
                                      $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                                  });
                            })->count();
                            $sheet->setCellValue('U'.$row, $cutiBersama != 0 ? $cutiBersama : '');
                            
                            $jmlIjin = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
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


            $sheetIndex++;
        }
        $spreadsheet->setActiveSheetIndex(0);
        $filename = "LIST_CUTI_" . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "_" . $tahunAkhir) . ".xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
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

    public function tanpaPotongan($tahunAwal, $tahunAkhir)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        $arrkol = $this->getKolom();
        
        $sheetIndex = 0;
        for($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            if($sheetIndex > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($sheetIndex);
            
            $karyawans_raw = Karyawan::with('jabatan', 'area')
                ->where('aktif', 'Y')
                ->whereHas('cutis', function($q) use ($tahun) {
                    $q->where('tahun', $tahun)->whereHas('details', function($q2) {
                        $q2->whereIn('kategori', ['UNPAID', 'GANTI_HARI_LIBUR']);
                    });
                })
                ->orderBy('id')->get();
                
            $details = [];
            foreach ($karyawans_raw as $d) {
                $staf = $d->staf;
                $area = $d->area ? $d->area->nama : 'Lainnya';
                $details[$staf][$area][] = $d;
            }
            krsort($details);
            
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('UNPAID LEAVE & GANTI HR LIBUR');
        $sheet->setShowGridlines(false);

        // Row 1 is blank
        
        $sheet->setCellValue('A2', 'UNPAID LEAVE (CUTI TIDAK DIBAYAR) & GANTI HARI LIBUR');
        $sheet->mergeCells('A2:Q2');
        $sheet->getStyle('A2')->getFont()->setName('Malgun Gothic')->setSize(13)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->setCellValue('A3', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);
        $sheet->mergeCells('A3:Q3');
        $sheet->getStyle('A3')->getFont()->setName('Malgun Gothic')->setSize(11)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('center');
        
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // Row 4 is blank
        
        $sheet->mergeCells('D5:O5');
        $sheet->setCellValue('D5', 'B U L A N');
        
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('A5', 'NO');
        
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'NAMA KARYAWAN');
        
        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('C5', 'MASA KERJA');
        
        $sheet->mergeCells('P5:P6');
        $sheet->setCellValue('P5', 'JML IJIN');
        
        $sheet->mergeCells('Q5:Q6');
        $sheet->setCellValue('Q5', 'KETERANGAN');
        
        $months = ['JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUNI', 'JULI', 'AUG', 'SEPT', 'OKT', 'NOP', 'DES'];
        foreach($months as $i => $m) {
            $sheet->setCellValue($arrkol[$i+3].'6', $m);
        }
        
        $sheet->getStyle('A5:Q6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle('A5:Q6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $sheet->getStyle('A5:Q6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
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
                    $detailsKet = CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
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
                        $prefix = ($det->kategori == "UNPAID") ? "UNPAID LEAVE " : "GANTI HARI LIBUR ";
                        $lines[] = $prefix . "Tgl " . $dateStr . " = " . $ket;
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
                                $jml = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
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


            $sheetIndex++;
        }
        $spreadsheet->setActiveSheetIndex(0);
        $filename = "UNPAID_LEAVE_&_GANTI_HARI_LIBUR_" . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "_" . $tahunAkhir) . ".xlsx";
        return $this->downloadExcel($spreadsheet, $filename);
    }

    private function downloadExcel($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
