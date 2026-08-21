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
        $karyawans = Karyawan::with('jabatan')->where('aktif', 'Y')->orderBy('nama')->get();
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
                $joinYear = date('Y', strtotime($k->tanggal_masuk));
                $sisaCutiTahunLalu = 0; // Simplified computation
                $totalHak = 12 + $sisaCutiTahunLalu;
                
                $diambil = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cuti', function($q2) use ($k, $tahun) {
                          $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                      });
                })->count();

                $sheet->setCellValue('D'.$row, $sisaCutiTahunLalu);
                $sheet->setCellValue('E'.$row, 12);
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
        $karyawans = Karyawan::with('jabatan')->where('aktif', 'Y')->orderBy('nama')->get();
        $spreadsheet = new Spreadsheet();
        $arrkol = $this->getKolom();

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LIST CUTI ' . $tahun);
        $sheet->setShowGridlines(false);

        $sheet->setCellValue('A1', 'CUTI KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'PERIODE : JANUARI S/D DESEMBER ' . $tahun);

        $headers = ['NO', 'NAMA KARYAWAN', 'MASA KERJA', 'JML CUTI', '+/- (THN LALU)', 'TOTAL CUTI', 'JAN', 'PEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOP', 'DES', 'SISA CUTI TAHUNAN', 'JML CUTI BERSAMA', 'JML IJIN', 'SISA CUTI', 'KETERANGAN'];
        foreach($headers as $i => $h) {
            $sheet->setCellValue($arrkol[$i].'4', $h);
        }
        $this->setHeaderStyle($sheet, 'A4:'.$arrkol[count($headers)-1].'4');

        $row = 5;
        foreach($karyawans as $idx => $k) {
            $sheet->setCellValue('A'.$row, $idx + 1);
            $sheet->setCellValue('B'.$row, $k->nama);
            
            $masaKerja = date_diff(date_create($k->tanggal_masuk), date_create(date('Y-m-d')))->y . ' Thn';
            $sheet->setCellValue('C'.$row, $masaKerja);
            
            $joinYear = date('Y', strtotime($k->tanggal_masuk));
            $sisaCutiTahunLalu = 0; // Simplified computation
            $totalHak = 12 + $sisaCutiTahunLalu;

            $sheet->setCellValue('D'.$row, 12);
            $sheet->setCellValue('E'.$row, $sisaCutiTahunLalu);
            $sheet->setCellValue('F'.$row, $totalHak);

            $totalDiambilTahunan = 0;
            for($m=1; $m<=12; $m++) {
                $diambilBulan = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                    $q->where('kategori', 'TAHUNAN')
                      ->whereHas('cuti', function($q2) use ($k, $tahun) {
                          $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                      });
                })->whereMonth('tanggal', $m)->count();
                $totalDiambilTahunan += $diambilBulan;
                $colIdx = 6 + $m - 1; 
                $sheet->setCellValue($arrkol[$colIdx].$row, $diambilBulan > 0 ? $diambilBulan : '');
            }

            $sisaTahunan = $totalHak - $totalDiambilTahunan;
            $sheet->setCellValue('S'.$row, $sisaTahunan);
            
            $cutiBersama = 5;
            $sheet->setCellValue('T'.$row, $cutiBersama);
            
            $jmlIjin = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                $q->where('kategori', 'IJIN')
                  ->whereHas('cuti', function($q2) use ($k, $tahun) {
                      $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                  });
            })->count();
            $sheet->setCellValue('U'.$row, $jmlIjin);
            
            $sheet->setCellValue('V'.$row, $sisaTahunan - $cutiBersama - $jmlIjin);
            
            $sheet->getStyle('A'.$row.':W'.$row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            $row++;
        }
        
        foreach(range('A', 'W') as $colId) {
            $sheet->getColumnDimension($colId)->setAutoSize(true);
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
            $sheet->setCellValue('A3', 'KEPERLUAN: ' . $cuti->keperluan);
        }
        return $this->downloadExcel($spreadsheet, "FORM_CUTI_$id.xlsx");
    }

    public function tanpaPotongan($tahun)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'CUTI TANPA POTONGAN TAHUN ' . $tahun);
        return $this->downloadExcel($spreadsheet, "CUTI_TANPA_POTONGAN_$tahun.xlsx");
    }

    public function unpaid($tahun)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'UNPAID LEAVE & GANTI HARI LIBUR TAHUN ' . $tahun);
        return $this->downloadExcel($spreadsheet, "UNPAID_LEAVE_$tahun.xlsx");
    }

    private function downloadExcel($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
