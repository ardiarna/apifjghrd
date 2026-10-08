<?php
namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\CutiDetail;
use App\Models\CutiDate;
use App\Models\JatahCutiTahunan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PdfKaryawanCutiController extends Controller
{
    private function getKolom() {
        $arrkol = [];
        $huruf = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for($i = 0; $i < 26; $i++) {
            $arrkol[] = $huruf[$i];
        }
        return $arrkol;
    }

    public function listCuti($karyawan_id, $tahun)
    {
        $k = Karyawan::with('jabatan', 'area')->find($karyawan_id);
        if(!$k) return response()->json(['status' => 'fail', 'message' => 'Karyawan tidak ditemukan']);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setWidth(20, Dimension::UOM_PIXELS);

        $arrkol = $this->getKolom();

        // baris 1 kosong
        // baris 2, kolom A merge s/d kolom O, 'LIST CUTI KARYAWAN', merge, center underline, blue
        $sheet->setCellValue('A2', 'LIST CUTI KARYAWAN');
        $sheet->mergeCells('A2:O2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setUnderline(true)->getColor()->setARGB('0000FF');
        $sheet->getStyle('A2')->getFont()->setSize(14);

        // baris 3, kolom B s/d D merge left 'NAMA KARYAWAN', kolom E s/d O merge left ': $nilai_nama_karyawan'
        $sheet->setCellValue('B3', 'NAMA KARYAWAN');
        $sheet->mergeCells('B3:D3');
        $sheet->setCellValue('E3', ': ' . $k->nama);
        $sheet->mergeCells('E3:O3');

        // baris 4, kolom B s/d D merge left 'TANGGAL GABUNG', kolom E s/d O merge left ': $nilai_masa_kerja'
        $masaKerja = $k->tanggal_masuk ? date('d-m-Y', strtotime($k->tanggal_masuk)) : '';
        $sheet->setCellValue('B4', 'TANGGAL GABUNG');
        $sheet->mergeCells('B4:D4');
        $sheet->setCellValue('E4', ': ' . $masaKerja);
        $sheet->mergeCells('E4:O4');

        // baris 5, kolom B merge baris 6 'TOTAL CUTI', kolom C s/d N merge 'B U L A N', kolom O merge baris 6 'SISA CUTI'
        $sheet->setCellValue('B6', 'TOTAL CUTI');
        $sheet->mergeCells('B6:B7');
        $sheet->setCellValue('C6', 'B U L A N');
        $sheet->mergeCells('C6:N6');
        $sheet->setCellValue('O6', 'SISA CUTI');
        $sheet->mergeCells('O6:O7');

        // baris 6
        $bulans = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
        foreach($bulans as $idx => $b) {
            $sheet->setCellValue($arrkol[2 + $idx].'7', $b);
        }

        // gaya baris 5 dan 6 center wrap
        $sheet->getStyle('B6:O7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('B6:O7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000'); // same as excel list cuti

        // baris 8 nilai-nilai
        $jatah = JatahCutiTahunan::where('karyawan_id', $k->id)->where('tahun', $tahun)->first();
        $jmlCuti = $jatah ? $jatah->jumlah_cuti : 0;
        $plus = $jatah ? $jatah->plus_tahun_lalu : 0;
        $min = $jatah ? $jatah->min_tahun_lalu : 0;
        $totalHak = $jmlCuti + $plus - $min;

        $sheet->setCellValue('B9', $totalHak != 0 ? $totalHak : '');
        
        $totalDiambilTahunan = 0;
        for($m=1; $m<=12; $m++) {
            $diambilBulan = CutiDate::whereHas('cutiDetail', function($q) use ($k, $tahun) {
                $q->whereIn('kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
                  ->whereHas('cuti', function($q2) use ($k, $tahun) {
                      $q2->where('karyawan_id', $k->id)->where('tahun', $tahun);
                  });
            })->whereMonth('tanggal', $m)->count();
            $totalDiambilTahunan += $diambilBulan;
            $sheet->setCellValue($arrkol[2 + $m - 1].'9', $diambilBulan != 0 ? $diambilBulan : '');
        }

        $sisaCuti = $totalHak - $totalDiambilTahunan;
        
        $sheet->setCellValue('O9', $jatah ? $sisaCuti : ($sisaCuti != 0 ? $sisaCuti : ''));
        $sheet->getStyle('B9:O9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B9')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        $sheet->getStyle('O9')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

        // border tabel dari baris 5 sampai 9 kolom B sampai O
        $sheet->getStyle('B6:O10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        // hapus baris 7, baris 9 (mereka kosong, tapi di-border)

        // baris 12 kolom B s/d E merge left 'RINCIAN TANGGAL CUTI'
        $sheet->setCellValue('B12', 'RINCIAN TANGGAL CUTI');
        $sheet->mergeCells('B12:E12');
        
        // baris 13
        $sheet->setCellValue('B13', 'TANGGAL');
        $sheet->mergeCells('B13:C13');
        $sheet->setCellValue('D13', 'KETERANGAN');
        $sheet->mergeCells('D13:N13');
        $sheet->setCellValue('O13', 'SISA CUTI');

        $sheet->getStyle('B13:O13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B13:O13')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');

        // Fetch all cuti details
        $detailsKet = CutiDetail::with(['dates', 'cuti', 'jenisKhusus'])
            ->whereHas('cuti', function($q) use ($k, $tahun) {
                $q->where('karyawan_id', $k->id)->where('tahun', $tahun);
            })
            ->get();

        $allDates = [];
        foreach($detailsKet as $det) {
            foreach($det->dates as $d) {
                $ket = $det->keterangan ?? '';
                if($det->kategori == 'UNPAID') {
                    $ket = trim('UNPAID LEAVE ' . $ket);
                } elseif($det->kategori == 'KHUSUS') {
                    $ket = trim('CUTI KHUSUS ' . $ket);
                } elseif($det->kategori == 'GANTI_HARI_LIBUR') {
                    $ket = trim('GANTI HARI LIBUR ' . $ket);
                } else {
                    if(empty($ket)) {
                        if($det->kategori == 'CUTI_MASAL') $ket = 'Cuti Bersama';
                        elseif($det->kategori == 'TAHUNAN') $ket = 'Cuti Tahunan';
                        else $ket = 'Ijin';
                    }
                }

                $allDates[] = [
                    'tanggal' => $d->tanggal,
                    'kategori' => $det->kategori,
                    'keterangan' => $ket
                ];
            }
        }
        
        // Sort by date
        usort($allDates, function($a, $b) {
            return strtotime($a['tanggal']) - strtotime($b['tanggal']);
        });

        $row = 14;
        $currentSisa = $totalHak; // total cuti from JatahCutiTahunan

        foreach($allDates as $item) {
            $dateStr = date('d-m-Y', strtotime($item['tanggal']));
            $ket = $item['keterangan'];

            $sheet->setCellValue('B'.$row, $dateStr);
            $sheet->mergeCells('B'.$row.':C'.$row);
            $sheet->setCellValue('D'.$row, $ket);
            $sheet->mergeCells('D'.$row.':N'.$row);
            
            if(in_array($item['kategori'], ['TAHUNAN', 'CUTI_MASAL', 'IJIN'])) {
                $currentSisa -= 1;
                $sheet->setCellValue('O'.$row, $currentSisa);
            } else {
                $sheet->setCellValue('O'.$row, '');
            }
            
            $sheet->getStyle('O'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('O'.$row)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

            $row++;
        }

        $sheet->getStyle('B13:O'.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToPage(TRUE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="LIST_CUTI_'.$k->nama.'_'.$tahun.'.pdf"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Mpdf');
        $writer->save('php://output');
        exit;
    }
}
