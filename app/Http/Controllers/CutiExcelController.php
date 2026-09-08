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
use App\Models\HariLibur;
use App\Models\JatahCutiTahunan;

class CutiExcelController extends Controller
{
    public function listCutiSingle($tahun) {
        return $this->listCuti($tahun, $tahun);
    }

    public function tanpaPotonganSingle($tahun) {
        return $this->tanpaPotongan($tahun, $tahun);
    }

    public function unpaidSingle($tahun) {
        return $this->unpaid($tahun, $tahun);
    }

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

                        $hariLiburs = HariLibur::whereYear('tanggal', $tahun)->whereMonth('tanggal', $m)->orderBy('tanggal')->get();
            $holidayDatesRed = $hariLiburs->where('iscutber', 'N')->pluck('tanggal')->toArray();
            $holidayDatesGreen = $hariLiburs->where('iscutber', 'Y')->pluck('tanggal')->toArray();
            $drawTable = function($title, $isManajemen) use (&$sheet, &$row, $details, $m, $tahun, $daysInMonth, $arrkol, $bulanIndo, $holidayDatesRed, $holidayDatesGreen) {
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

                $redCols = [];
                $greenCols = [];
                for($d = 1; $d <= 31; $d++) {
                    $col = $arrkol[$d + 1]; // C is index 2
                    if($d <= $daysInMonth) {
                        $sheet->setCellValue($col.$row, $d);

                        $dateStr = sprintf('%04d-%02d-%02d', $tahun, $m, $d);
                        $dayOfWeek = date('N', strtotime($dateStr));
                                                if($dayOfWeek == 6 || $dayOfWeek == 7 || in_array($dateStr, $holidayDatesRed)) {
                            $redCols[] = $col;
                            $sheet->getStyle($col.$row)->getFont()->getColor()->setARGB('FFFF0000');
                        } elseif(in_array($dateStr, $holidayDatesGreen)) {
                            $greenCols[] = $col;
                            $sheet->getStyle($col.$row)->getFont()->getColor()->setARGB('FF92D050');
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

                foreach($redCols as $col) {
                    $startR = $headerStartRow + 1;
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFont()->getColor()->setARGB('FFFFFFFF');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                }
                foreach($greenCols as $col) {
                    $startR = $headerStartRow + 1;
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF92D050');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getFont()->getColor()->setARGB('FFFFFFFF');
                    $sheet->getStyle($col.$startR.':'.$col.($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                }
            };

            $drawTable('STAF', false);

            $row += 3;

                        $drawTable('MANAJEMEN', true);

            $row += 2;
            $holidaysRed = $hariLiburs->where('iscutber', 'N');
            foreach($holidaysRed as $hl) {
                $dt = \Carbon\Carbon::parse($hl->tanggal);
                $sheet->getStyle('A'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
                
                $tglStr = "TGL " . $dt->format('d') . " " . strtoupper($bulanIndo[$dt->format('n')]) . " '" . $dt->format('y');
                $sheet->setCellValue('B'.$row, $tglStr);
                $sheet->getStyle('B'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                
                $sheet->setCellValue('C'.$row, '=');
                $sheet->getStyle('C'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal('center');
                
                $sheet->mergeCells('D'.$row.':AK'.$row);
                $sheet->setCellValue('D'.$row, strtoupper($hl->nama));
                $sheet->getStyle('D'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                $row++;
            }
            
            $holidaysGreen = $hariLiburs->where('iscutber', 'Y');
            if($holidaysGreen->count() > 0) {
                $row += 2;
                foreach($holidaysGreen as $hl) {
                    $dt = \Carbon\Carbon::parse($hl->tanggal);
                    $sheet->getStyle('A'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF92D050');
                    
                    $tglStr = "TGL " . $dt->format('d') . " " . strtoupper($bulanIndo[$dt->format('n')]) . " '" . $dt->format('y');
                    $sheet->setCellValue('B'.$row, $tglStr);
                    $sheet->getStyle('B'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    
                    $sheet->setCellValue('C'.$row, '=');
                    $sheet->getStyle('C'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal('center');
                    
                    $sheet->mergeCells('D'.$row.':AK'.$row);
                    $sheet->setCellValue('D'.$row, strtoupper($hl->nama));
                    $sheet->getStyle('D'.$row)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    $row++;
                }
            }


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
        return $this->downloadExcel($spreadsheet, "LIST_CUTI_" . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir) . ".xlsx");
    }

    public function form($id)
    {
        $bulanInd = ['January'=>'Januari','February'=>'Februari','March'=>'Maret',
            'April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli',
            'August'=>'Agustus','September'=>'September','October'=>'Oktober',
            'November'=>'November','December'=>'Desember'];
        $fmtTgl = function($val) use ($bulanInd) {
            if (!$val) return '-';
            $dt = \Carbon\Carbon::parse($val);
            return $dt->format('j') . ' ' . $bulanInd[$dt->format('F')] . ' ' . $dt->format('Y');
        };

        $cuti = Cuti::with([
            'karyawan.jabatan',
            'karyawan.divisi',
            'karyawan.statusKerja',
            'karyawan.area',
            'details.dates',
            'details.jenisKhusus',
        ])->find($id);

        if (!$cuti) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $k     = $cuti->karyawan;
        $tahun = $cuti->tahun;

        $tanggalKembali = $fmtTgl($cuti->tanggal_kembali);
        $tanggalMasuk   = $fmtTgl($k->tanggal_masuk);

        $snapTotal = null; $snapDiambil = null; $snapMasal = null;
        $lamaKhusus = 0; $lamaUnpaid1 = 0; $lamaUnpaid2 = 0; $lamaGantiLibur = 0;
        $akanDiambil = 0;
        $lamaKhususSatuan = 'Hari';
        $allDates = []; $keperluanList = [];

        foreach ($cuti->details as $det) {
            if ($det->keterangan) $keperluanList[] = $det->keterangan;
            if ($det->snap_total_hak_cuti !== null && $snapTotal === null) {
                $snapTotal   = (int) $det->snap_total_hak_cuti;
                $snapDiambil = (int) $det->snap_sudah_diambil;
                $snapMasal   = (int) $det->snap_cuti_masal;
            }
            $lama = (int) ($det->lama_hari ?? 0);
            if ($det->kategori === 'KHUSUS') {
                $lamaKhusus += $lama;
                if ($det->jenisKhusus && strtolower($det->jenisKhusus->satuan) === 'bulan') {
                    $lamaKhususSatuan = 'Bulan';
                }
            }
            if ($det->kategori === 'UNPAID') {
                if ($det->jenis_unpaid === 'BELUM_TIMBUL') $lamaUnpaid1 += $lama;
                else $lamaUnpaid2 += $lama;
            }
            if ($det->kategori === 'GANTI_LIBUR') $lamaGantiLibur += $lama;
            if (in_array($det->kategori, ['TAHUNAN', 'IJIN'])) $akanDiambil += $lama;
            foreach ($det->dates as $d) $allDates[] = $d->tanggal;
        }

        // Tanggal pengambilan cuti (format panjang, deduplikasi)
        $tanggalInput = '-';
        if (count($allDates) > 0) {
            sort($allDates);
            $unique = array_values(array_unique($allDates));
            if (count($unique) === 1) {
                $tanggalInput = $fmtTgl($unique[0]);
            } else {
                $tanggalInput = $fmtTgl($unique[0]) . ' s/d ' . $fmtTgl($unique[count($unique)-1]);
            }
        }

        // Keperluan: deduplikasi, hanya tulis 1x jika sama
        $keperluan = implode('; ', array_unique(array_filter($keperluanList)));

        $totalHak     = $snapTotal   ?? 0;
        $sudahDiambil = $snapDiambil ?? 0;
        $cutiMasal    = $snapMasal   ?? 0;
        $belumDiambil = $totalHak - $sudahDiambil - $cutiMasal;
        $sisaHak      = $belumDiambil - $akanDiambil;

        // ---- spreadsheet ----
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setShowGridlines(false);
        $sheet->setTitle('FORM');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Century Gothic')->setSize(10)->setBold(true);

        $isJkt = $k->area && strtoupper($k->area->kode) === 'JKT';

        $sheet->getColumnDimension('A')->setWidth(4.83); // 29px
        if ($isJkt) {
            $sheet->getColumnDimension('B')->setWidth(37.17); // 223px
            $sheet->getColumnDimension('C')->setWidth(2.50); // 15px
        } else {
            $sheet->getColumnDimension('B')->setWidth(43.00); // 258px
            $sheet->getColumnDimension('C')->setWidth(3.17); // 19px
        }
        $sheet->getColumnDimension('D')->setWidth(12.67); // 76px
        $sheet->getColumnDimension('E')->setWidth(8.83); // 53px
        $sheet->getColumnDimension('F')->setWidth(7.00); // 42px
        $sheet->getColumnDimension('G')->setWidth(8.83); // 53px
        $sheet->getColumnDimension('H')->setWidth(5.83); // 35px
        $sheet->getColumnDimension('I')->setWidth(4.83); // 29px

        // Blank rows: normal height
        foreach ([1, 2, 13, 14, 23, 27, 31, 33, 35, 36, 37, 40, 41] as $r) {
            $sheet->getRowDimension($r)->setRowHeight(17);
        }

        // Row 3 title
        $sheet->mergeCells('B3:H3');
        $sheet->setCellValue('B3', 'PERMOHONAN PENGAMBILAN CUTI');
        $sheet->getStyle('B3')->getFont()->setSize(14)->setBold(true);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getRowDimension(3)->setRowHeight(17);

        // Rows 4-12 info
        $infoRows = [
            4  => ['N a m a',                  $k->nama],
            5  => ['Bagian',                    $k->divisi ? $k->divisi->nama : '-'],
            6  => ['Jabatan',                   $k->jabatan ? $k->jabatan->nama : '-'],
            7  => ['NIK',                       $k->nik ?? '-'],
            8  => ['Status Karyawan',           $k->statusKerja ? $k->statusKerja->nama : '-'],
            9  => ['Tanggal Mulai Masuk Kerja', $tanggalMasuk],
            10 => ['Pengambilan Cuti',          $tanggalInput],
            11 => ['Tanggal Masuk Kembali',     $tanggalKembali],
            12 => ['Keperluan Cuti',            $keperluan],
        ];
        foreach ($infoRows as $row => $data) {
            $sheet->setCellValue('B'.$row, $data[0]);
            $sheet->setCellValue('C'.$row, ':');
            $sheet->mergeCells('D'.$row.':H'.$row);
            $sheet->setCellValue('D'.$row, $data[1]);
            $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('B'.$row.':H'.$row)->getAlignment()->setVertical('center');
            $sheet->getRowDimension($row)->setRowHeight(17);
        }

        // Row 15: no merge, all border, center, grey background
        $sheet->setCellValue('B15', 'Diisi Oleh HR Dept :');
        $sheet->getStyle('B15')->getFont()->setItalic(true)->setBold(true);
        $sheet->getStyle('B15')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B15')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD8D8D8');
        $sheet->getStyle('B15')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(15)->setRowHeight(17);

        // Helper: category row (underline only for text, not numbering)
        $setCategoryRow = function($range, $prefix, $text) use ($sheet) {
            $sheet->mergeCells($range);
            [$startCell] = explode(':', $range);
            $rt = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
            $r1 = $rt->createTextRun($prefix);
            $r1->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(false);
            $r2 = $rt->createTextRun($text);
            $r2->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(true);
            $sheet->setCellValue($startCell, $rt);
            $sheet->getStyle($startCell)->getAlignment()->setVertical('center');
        };

        // Row 16 section 1
        $setCategoryRow('B16:H16', '1. ', 'Cuti Tahunan');
        $sheet->getRowDimension(16)->setRowHeight(17);

        // Row 17-22 tahunan
        $tahunanRows = [
            17 => ['Hak Cuti Tahunan Periode',                   'G', $totalHak,    'Hari'],
            18 => ['Cuti Yang Sudah Diambil',                    'E', $sudahDiambil, 'Hari', 'F'],
            19 => ['Cuti Masal ; Idul Fitri/Natal/Cuti Bersama', 'E', $cutiMasal,   'Hari', 'F'],
            20 => ['Cuti Yang Belum Diambil',                    'G', $belumDiambil, 'Hari'],
            21 => ['Cuti Yang Akan Diambil',                     'G', $akanDiambil,  'Hari'],
            22 => ['Sisa Hak Cuti Tahunan',                      'G', $sisaHak,      'Hari'],
        ];
        foreach ($tahunanRows as $row => $r) {
            $sheet->setCellValue('B'.$row, $r[0]);
            $sheet->setCellValue('C'.$row, ':');
            $sheet->setCellValue('D'.$row, $tahun);
            $valCol  = $r[1];
            $unitCol = isset($r[4]) ? $r[4] : chr(ord($valCol)+1);
            $sheet->setCellValue($valCol.$row, $r[2]);
            $sheet->setCellValue($unitCol.$row, $r[3]);
            $sheet->getStyle($valCol.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle($unitCol.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('C'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('B'.$row.':H'.$row)->getAlignment()->setVertical('center');
            $sheet->getRowDimension($row)->setRowHeight(17);
        }

        // Row 24 section 2
        $setCategoryRow('B24:H24', '2. ', 'Cuti Tanggungan Perusahaan');
        $sheet->getRowDimension(24)->setRowHeight(17);

        // Row 25
        $sheet->mergeCells('B25:E25');
        $sheet->setCellValue('B25', 'Melahirkan / Menikah / Baptis / Khitanan / Anak Menikah');
        $sheet->setCellValue('G25', $lamaKhusus);
        $sheet->setCellValue('H25', $lamaKhususSatuan);
        $sheet->getStyle('G25')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('F25')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('H25')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B25:H25')->getAlignment()->setVertical('center');
        $sheet->getRowDimension(25)->setRowHeight(17);

        // Row 26
        $sheet->mergeCells('B26:E26');
        $sheet->setCellValue('B26', 'Ibadah Haji / Ibadah Umroh / Sakit Lama');
        $sheet->getStyle('B26:H26')->getAlignment()->setVertical('center');
        $sheet->getRowDimension(26)->setRowHeight(17);

        // Row 28 section 3
        $setCategoryRow('B28:H28', '3. ', 'Cuti Diluar Tanggungan Perusahaan / Unpaid Leave');
        $sheet->getRowDimension(28)->setRowHeight(17);

        // Row 29 – rich text: "Unpaid Leave (Potong Upah)" merah
        $sheet->mergeCells('B29:E29');
        $rt29 = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $r29a = $rt29->createTextRun('- Hak Cuti Sebelum Timbul / ');
        $r29a->getFont()->setBold(true)->setName('Century Gothic')->setSize(10);
        $r29b = $rt29->createTextRun('Unpaid Leave (Potong Upah)');
        $r29b->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->getColor()->setARGB('FFFF0000');
        $sheet->getCell('B29')->setValue($rt29);
        $sheet->setCellValue('G29', $lamaUnpaid1);
        $sheet->setCellValue('H29', 'Hari');
        $sheet->getStyle('G29')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('H29')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B29:H29')->getAlignment()->setVertical('center');
        $sheet->getRowDimension(29)->setRowHeight(17);

        // Row 30 – rich text: "Unpaid Leave (Potong Upah)" merah
        $sheet->mergeCells('B30:E30');
        $rt30 = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $r30a = $rt30->createTextRun('- Hak Cuti Sudah Habis / ');
        $r30a->getFont()->setBold(true)->setName('Century Gothic')->setSize(10);
        $r30b = $rt30->createTextRun('Unpaid Leave (Potong Upah)');
        $r30b->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->getColor()->setARGB('FFFF0000');
        $sheet->getCell('B30')->setValue($rt30);
        $sheet->setCellValue('G30', $lamaUnpaid2);
        $sheet->setCellValue('H30', 'Hari');
        $sheet->getStyle('G30')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('H30')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B30:H30')->getAlignment()->setVertical('center');
        $sheet->getRowDimension(30)->setRowHeight(17);

        // Row 32 section 4
        $sheet->mergeCells('B32:E32');
        $rt4 = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $r4_1 = $rt4->createTextRun('4. ');
        $r4_1->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(false);
        $r4_2 = $rt4->createTextRun('Penggantian Hari Libur');
        $r4_2->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(true);
        $sheet->setCellValue('B32', $rt4);
        $sheet->getStyle('B32:H32')->getAlignment()->setVertical('center');
        $sheet->setCellValue('G32', $lamaGantiLibur);
        $sheet->setCellValue('H32', 'Hari');
        $sheet->getStyle('G32')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('H32')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getRowDimension(32)->setRowHeight(17);

        // Row 34: B only, all border, center, grey
        $sheet->setCellValue('B34', 'Keputusan :');
        $sheet->getStyle('B34')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B34')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD8D8D8');
        $sheet->getStyle('B34')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(34)->setRowHeight(17);

        // ---- SIGNATURE ROWS ----
        // Helper: RichText with optional leading spaces (no underline) then underlined text
        $rtSig = function($spaces, $text) {
            $rt = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
            if ($spaces !== '') {
                $sp = $rt->createTextRun($spaces);
                $sp->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(false);
            }
            $tx = $rt->createTextRun($text);
            $tx->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(true);
            return $rt;
        };

        if ($isJkt) {
            // Row 38 - JKT signatures
            $sheet->getCell('B38')->setValue($rtSig('         ', 'HR Dept'));
            $sheet->getStyle('B38')->getAlignment()->setVertical('center');

            $sheet->mergeCells('C38:D38');
            $sheet->setCellValue('C38', 'Adm Manager');
            $sheet->getStyle('C38')->getFont()->setUnderline(true)->setBold(true);
            $sheet->getStyle('C38')->getAlignment()->setHorizontal('center')->setVertical('center');

            $sheet->mergeCells('F38:H38');
            $sheet->setCellValue('F38', 'Presiden Direktur');
            $sheet->getStyle('F38')->getFont()->setUnderline(true)->setBold(true);
            $sheet->getStyle('F38')->getAlignment()->setHorizontal('center')->setVertical('center');

            $sheet->getRowDimension(38)->setRowHeight(17);

            $sheet->setCellValue('B39', '         Tgl.');
            $sheet->getStyle('B39')->getAlignment()->setVertical('center');

            $sheet->mergeCells('C39:D39');
            $sheet->setCellValue('C39', '    Tgl.');
            $sheet->getStyle('C39')->getAlignment()->setHorizontal('left')->setVertical('center');

            $sheet->mergeCells('F39:H39');
            $sheet->setCellValue('F39', '        Tgl.');
            $sheet->getStyle('F39')->getAlignment()->setHorizontal('left')->setVertical('center');

            $sheet->getRowDimension(39)->setRowHeight(17);
        } else {
            // Row 38 - non-JKT signatures
            $rtNonJkt = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
            $s1 = $rtNonJkt->createTextRun('      ');
            $s1->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(false);
            $t1 = $rtNonJkt->createTextRun('HR Dept');
            $t1->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(true);
            $s2 = $rtNonJkt->createTextRun('                ');
            $s2->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(false);
            $t2 = $rtNonJkt->createTextRun('Engin SPV/Team Leader/Koord.');
            $t2->getFont()->setBold(true)->setName('Century Gothic')->setSize(10)->setUnderline(true);

            $sheet->getCell('B38')->setValue($rtNonJkt);
            $sheet->getStyle('B38')->getAlignment()->setVertical('center');

            $sheet->mergeCells('D38:E38');
            $sheet->getCell('D38')->setValue($rtSig('     ', 'Adm Manager'));
            $sheet->getStyle('D38')->getAlignment()->setHorizontal('center')->setVertical('center');

            $sheet->mergeCells('F38:H38');
            $sheet->setCellValue('F38', 'Presiden Direktur');
            $sheet->getStyle('F38')->getFont()->setUnderline(true)->setBold(true);
            $sheet->getStyle('F38')->getAlignment()->setHorizontal('center')->setVertical('center');

            $sheet->getRowDimension(38)->setRowHeight(17);

            $sheet->setCellValue('B39', '      Tgl.                        Tgl.');
            $sheet->getStyle('B39')->getAlignment()->setVertical('center');

            $sheet->mergeCells('D39:E39');
            $sheet->setCellValue('D39', '           Tgl.');
            $sheet->getStyle('D39')->getAlignment()->setHorizontal('left')->setVertical('center');

            $sheet->mergeCells('F39:H39');
            $sheet->setCellValue('F39', '        Tgl.');
            $sheet->getStyle('F39')->getAlignment()->setHorizontal('left')->setVertical('center');

            $sheet->getRowDimension(39)->setRowHeight(17);
        }

        // ---- BORDERS ----
        // Outer border A2:I41
        $sheet->getStyle('A2:I41')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

        // Bottom border row 3 B:H
        $sheet->getStyle('B3:H3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Bottom borders on specific value cells
        $bottomCells = ['G17', 'E18', 'E19', 'G20', 'G25', 'G29', 'G30', 'G32'];
        foreach ($bottomCells as $cell) {
            $sheet->getStyle($cell)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }

        // G22: all border
        $sheet->getStyle('G22')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // G21 bottom border (for "akan diambil" – acts as input line)
        $sheet->getStyle('G21')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        return $this->downloadExcel($spreadsheet, 'FORM_CUTI_' . preg_replace('/\s+/', '_', $k->nama) . '_' . $tahun . '.xlsx');
    }




    public function tanpaPotongan($tahunAwal, $tahunAkhir)
    {
        $spreadsheet = new Spreadsheet();
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
                    $detailsKet = CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
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


            $sheetIndex++;
        }
        $spreadsheet->setActiveSheetIndex(0);
        return $this->downloadExcel($spreadsheet, "CUTI_TANPA_POTONGAN_" . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir) . ".xlsx");
    }

    public function unpaid($tahunAwal, $tahunAkhir)
    {
        $spreadsheet = new Spreadsheet();
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
        $sheet->setTitle('UNPAID & GANTI HR LIBUR ' . $tahun);
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
        return $this->downloadExcel($spreadsheet, "UNPAID_LEAVE_&_GANTI_HARI_LIBUR_" . ($tahunAwal == $tahunAkhir ? $tahunAwal : $tahunAwal . "-" . $tahunAkhir) . ".xlsx");
    }

    private function downloadExcel($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
