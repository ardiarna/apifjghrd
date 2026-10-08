<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SpreadKaryawanCustomController extends Controller
{

    public function alamatDivisi($id) {
        $namaDivisi = '';
        if ($id == 'PS') {
            $namaDivisi = 'PROFESSIONAL SERVICE';
        } else {
            $divisi = \App\Models\Divisi::find($id);
            if (!$divisi) return response()->json(['success' => false, 'message' => 'Divisi not found']);
            $namaDivisi = $divisi->nama;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('ALAMAT ' . strtoupper(substr($namaDivisi, 0, 20)));

        // Fetch Data
        $all = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $dataKaryawan = [];
        foreach ($all as $d) {
            if ($id == 'PS') {
                if ($d->jabatan && strtoupper($d->jabatan->nama) == 'PROFESSIONAL SERVICE') {
                    $dataKaryawan[] = $d;
                }
            } else {
                if ($d->divisi_id == $id) {
                    $dataKaryawan[] = $d;
                }
            }
        }

        $details = [];
        $totalKaryawanPerArea = [];
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;

            if (!isset($totalKaryawanPerArea[$kodeArea])) {
                $totalKaryawanPerArea[$kodeArea] = 0;
            }
            $totalKaryawanPerArea[$kodeArea]++;
        }

        $activeAreas = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($totalKaryawanPerArea[$a->kode])) {
                $activeAreas[] = strtoupper($a->nama);
            }
        }
        if (isset($totalKaryawanPerArea['Lainnya'])) {
            $activeAreas[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreas);

        $si->setCellValue('A2', 'DATA/ALAMAT ' . strtoupper($namaDivisi));
        $si->mergeCells('A2:G2');
        $si->setCellValue('A3', $areaString);
        $si->mergeCells('A3:G3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:G3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        $headers = ['A'=>'NO', 'B'=>'N A M A', 'C'=>'TEMPAT & TGL LAHIR', 'D'=>'TANGGAL GABUNG', 'E'=>'JABATAN', 'F'=>'ALAMAT SESUAI KTP', 'G'=>'ALAMAT TINGGAL SEKARANG'];
        foreach ($headers as $col => $title) {
            $si->setCellValue($col.'5', $title);
            $si->mergeCells($col.'5:'.$col.'6');
        }
        $si->getStyle('A5:G6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:G6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:G6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>30.00, 'D'=>10.77, 'E'=>40.14, 'F'=>56.00, 'G'=>57.10];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }

        $si->freezePane('A7');
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
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->nama);

                    $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                    $si->setCellValue('C'.$bar, $ttl);

                    $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $si->setCellValue('D'.$bar, $tglMasuk);

                    $si->setCellValue('E'.$bar, $d->jabatan ? $d->jabatan->nama : '');
                    $si->setCellValue('F'.$bar, $d->alamat_ktp);
                    $si->setCellValue('G'.$bar, $d->alamat_tinggal);

                    $si->getStyle('A'.$bar.':G'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('F'.$bar)->getAlignment()->setWrapText(true);
                    $si->getStyle('G'.$bar)->getAlignment()->setWrapText(true);
                    $si->getStyle('B'.$bar.':G'.$bar)->getAlignment()->setWrapText(true);

                    $bar++;
                    $nomor++;

                    // 1 blank row after each employee
                    $bar++;
                }
            }
        }

        $lastDataRow = $bar - 1;
        if ($lastDataRow >= 7) {
            $si->getStyle('A7:G'.$lastDataRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:G'.$lastDataRow)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:G'.$lastDataRow)->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ALAMAT_' . strtoupper(str_replace(' ', '_', $namaDivisi)) . '.xlsx"');
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


    public function dataDivisi($id) {
        $namaDivisi = '';
        if ($id == 'PS') {
            $namaDivisi = 'PROFESSIONAL SERVICE';
        } else {
            $divisi = \App\Models\Divisi::find($id);
            if (!$divisi) return response()->json(['success' => false, 'message' => 'Divisi not found']);
            $namaDivisi = $divisi->nama;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('DATA ' . strtoupper(substr($namaDivisi, 0, 25)));

        // Fetch Data
        $all = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $dataKaryawan = [];
        foreach ($all as $d) {
            if ($id == 'PS') {
                if ($d->jabatan && strtoupper($d->jabatan->nama) == 'PROFESSIONAL SERVICE') {
                    $dataKaryawan[] = $d;
                }
            } else {
                if ($d->divisi_id == $id) {
                    $dataKaryawan[] = $d;
                }
            }
        }

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
            $statusNama = $d->statusKerja ? strtoupper($d->statusKerja->nama) : 'LAIN-LAIN';

            $details[$staf][$area][] = $d;

            if (!isset($totalKaryawanPerStatus[$statusNama])) {
                $totalKaryawanPerStatus[$statusNama] = 0;
            }
            if (!isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea])) {
                $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] = 0;
            }
            if (!isset($totalKaryawanPerArea[$kodeArea])) {
                $totalKaryawanPerArea[$kodeArea] = 0;
            }

            $totalKaryawanPerStatus[$statusNama]++;
            $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]++;
            $totalKaryawanPerArea[$kodeArea]++;

            $statusNamaToId[$statusNama] = $d->statusKerja ? $d->statusKerja->id : 999;
            $totalKaryawan++;
        }

        $activeAreas = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($totalKaryawanPerArea[$a->kode])) {
                $activeAreas[] = strtoupper($a->nama);
            }
        }
        if (isset($totalKaryawanPerArea['Lainnya'])) {
            $activeAreas[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreas);

        $judul = ($id == 'PS') ? 'LIST PROFESSIONAL SERVICE' : 'LIST DIVISION ' . strtoupper($namaDivisi);
        $si->setCellValue('A2', $judul);
        $si->mergeCells('A2:I2');
        $si->setCellValue('A3', $areaString);
        $si->mergeCells('A3:I3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:I3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        $headers = ['A'=>'NO', 'B'=>'N A M E', 'C'=>'DATE OF BIRTH', 'D'=>'START WORKING', 'E'=>'AGE (YEARS)', 'F'=>'YEARS OF SERVICE', 'G'=>'POSITION', 'H'=>'LAST EDUCATION', 'I'=>'TRAINING'];
        foreach ($headers as $col => $title) {
            $si->setCellValue($col.'5', $title);
            $si->mergeCells($col.'5:'.$col.'6');
        }
        $si->getStyle('A5:I6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:I6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:I6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>18.00, 'D'=>18.00, 'E'=>11.07, 'F'=>15.00, 'G'=>39.95, 'H'=>58.04, 'I'=>60.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }

        $si->freezePane('A7');
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
                    $trainings = $d->trainingKaryawans()->get();
                    $numTrainings = $trainings->count();
                    $maxRows = max(1, $numTrainings);

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
                                $pendidikanFormat .= ', Jurusan: ' . $jurusan;
                            }
                        }
                    }

                    $startBar = $bar;

                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $si->setCellValue('A'.$bar, $nomor);
                            $si->setCellValue('B'.$bar, $d->nama);

                            $tglLahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                            $si->setCellValue('C'.$bar, $tglLahir);

                            $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                            $si->setCellValue('D'.$bar, $tglMasuk);

                            $age = '';
                            if ($d->tanggal_lahir) {
                                $dt1 = date_create($d->tanggal_lahir);
                                $dt2 = date_create('today');
                                $age = date_diff($dt1, $dt2)->y;
                            }
                            $si->setCellValue('E'.$bar, $age);

                            $service = '';
                            if ($d->tanggal_masuk) {
                                $dt1 = date_create($d->tanggal_masuk);
                                $dt2 = date_create('today');
                                $service = date_diff($dt1, $dt2)->y;
                            }
                            $si->setCellValue('F'.$bar, $service);

                            $si->setCellValue('G'.$bar, $d->jabatan ? $d->jabatan->nama : '');
                            $si->setCellValue('H'.$bar, $pendidikanFormat);
                        }

                        if ($i == 1 && $pendidikanJurusan != '') {
                            $si->setCellValue('H'.$bar, $pendidikanJurusan);
                        }

                        if ($i < $numTrainings) {
                            $tk = $trainings[$i];
                            $tName = '';
                            if ($tk->training) {
                                $tName = $tk->training->nama;
                            } else {
                                $tName = 'Training ID: ' . $tk->training_id;
                            }

                            $tInfo = $tName;
                            if ($tk->tanggal) {
                                $tInfo .= " - " . date('d-m-Y', strtotime($tk->tanggal));
                            }
                            if ($tk->keterangan) {
                                $tInfo .= "\n" . trim($tk->keterangan);
                            }

                            $si->setCellValue('I'.$bar, $tInfo);
                        }

                        $si->getStyle('A'.$bar.':I'.$bar)->getAlignment()->setVertical('top');
                        $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('D'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('E'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('F'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('I'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('B'.$bar.':I'.$bar)->getAlignment()->setWrapText(true);

                        $bar++;
                    }
                    $nomor++;

                    // 1 blank row after each employee
                    $bar++;
                }
            }
        }

        $lastDataRow = $bar - 1;
        if ($lastDataRow >= 7) {
            $si->getStyle('A7:I'.$lastDataRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:I'.$lastDataRow)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:I'.$lastDataRow)->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_' . strtoupper(str_replace(' ', '_', $namaDivisi)) . '.xlsx"');
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

    protected $repoKaryawan;

    public function __construct(KaryawanRepository $repoKaryawan) {
        $this->repoKaryawan = $repoKaryawan;
    }

    public function dataJabatanKaryawan() {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('DATA KARYAWAN & JABATAN');

        // Month names array for formatting
        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];

        // Title Rows
        $si->setCellValue('A2', 'DATA KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A2:C2');
        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');
        $si->setCellValue('A3', 'UPDATE : ' . $bulanStr);
        $si->mergeCells('A3:C3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:C3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        // Headers
        $si->setCellValue('A5', 'NO'); $si->mergeCells('A5:A6');
        $si->setCellValue('B5', 'N A M A'); $si->mergeCells('B5:B6');
        $si->setCellValue('C5', 'J A B A T A N'); $si->mergeCells('C5:C6');

        $si->getStyle('A5:C6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:C6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:C6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $si->getRowDimension(5)->setRowHeight(18);
        $si->getRowDimension(6)->setRowHeight(15);

        // Column Widths
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>45.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }

        // Data Setup
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $details = [];

        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $details[$staf][$area][] = $d;
        }

        $si->freezePane('A7');
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
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->nama);
                    $si->setCellValue('C'.$bar, $d->jabatan ? $d->jabatan->nama : '');

                    $si->getStyle('A'.$bar.':C'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar)->getAlignment()->setWrapText(true);

                    $bar++;
                    $nomor++;
                }

                // 1 blank row after each area/group
                $bar++;
            }
        }

        if ($bar > 7) {
            $si->getStyle('A7:C'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A7:C'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A7:C'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_JABATAN_KARYAWAN.xlsx"');
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

    public function dataKaryawanPerJoint($tahunAwal, $tahunAkhir, $includeEx = 0) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

                if ($includeEx == 1) {
            $dataKaryawan = $this->repoKaryawan->findAll([]);
        } else {
            $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        }

        $idxSheet = 0;
        for ($tahun = $tahunAwal; $tahun <= $tahunAkhir; $tahun++) {
            if ($idxSheet > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($idxSheet);
            $si = $spreadsheet->getActiveSheet();
            $si->setShowGridlines(false);
            $si->setTitle((string)$tahun);

            // Filter by year
            $karyawanTahunIni = [];
            foreach ($dataKaryawan as $d) {
                if ($d->tanggal_masuk) {
                    if (date('Y', strtotime($d->tanggal_masuk)) == $tahun) {
                        $karyawanTahunIni[] = $d;
                    }
                }
            }

            // Group by Staf and Area
            $details = [];
            $activeAreas = [];
            foreach ($karyawanTahunIni as $d) {
                $staf = $d->staf;
                $area = $d->area ? $d->area->nama : 'Lainnya';
                $details[$staf][$area][] = $d;
                $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
                $activeAreas[$kodeArea] = strtoupper($area);
            }

            $si->setCellValue('A2', 'DATA KARYAWAN PT.FRATEKINDO JAYA GEMILANG (JOINT PER : ' . $tahun . ')');
            $si->mergeCells('A2:D2');

            $areaString = '';
            $activeAreasSorted = [];
            $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
            foreach ($dbAreasModels as $a) {
                if (isset($activeAreas[$a->kode])) {
                    $activeAreasSorted[] = strtoupper($a->nama);
                }
            }
            if (isset($activeAreas['Lainnya'])) {
                $activeAreasSorted[] = 'LAINNYA';
            }
            $areaString = implode(' - ', $activeAreasSorted);

            $si->setCellValue('A3', $areaString);
            $si->mergeCells('A3:D3');


        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
            $si->getStyle('A2:D3')->applyFromArray($styleJudul);
            $si->getRowDimension(2)->setRowHeight(22);
            $si->getRowDimension(3)->setRowHeight(22);

            $si->setCellValue('A5', 'NO'); $si->mergeCells('A5:A6');
            $si->setCellValue('B5', 'N A M A'); $si->mergeCells('B5:B6');
            $si->setCellValue('C5', 'MASA KERJA'); $si->mergeCells('C5:C6');
            $si->setCellValue('D5', 'J A B A T A N'); $si->mergeCells('D5:D6');

            $si->getStyle('A5:D6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
            $si->getStyle('A5:D6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
            $si->getStyle('A5:D6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $si->getRowDimension(5)->setRowHeight(18);
            $si->getRowDimension(6)->setRowHeight(15);

            $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>15.00, 'D'=>40.00];
            foreach ($widths as $col => $width) {
                $si->getColumnDimension($col)->setWidth($width);
            }

            $si->freezePane('A7');
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
                        $si->setCellValue('A'.$bar, $nomor);
                        $si->setCellValue('B'.$bar, $d->nama);

                        $masaKerja = '';
                        if ($d->tanggal_masuk) {
                            $masaKerja = date('d-m-Y', strtotime($d->tanggal_masuk));
                            if ($d->aktif == 'N' && $d->tanggal_keluar) {
                                $masaKerja .= ' s/d ' . date('d-m-Y', strtotime($d->tanggal_keluar));
                            }
                        }
                        $si->setCellValue('C'.$bar, $masaKerja);

                        if ($d->aktif == 'N') {
                            $si->getStyle('A'.$bar.':D'.$bar)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC0CB');
                            $si->getColumnDimension('C')->setWidth(30.00);
                        }
                        $si->setCellValue('D'.$bar, $d->jabatan ? $d->jabatan->nama : '');

                        $si->getStyle('A'.$bar.':D'.$bar)->getAlignment()->setVertical('top');
                        $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                        $si->getStyle('D'.$bar)->getAlignment()->setWrapText(true);

                        $bar++;
                        $nomor++;
                    }

                    // 1 blank row after each area/group
                    $bar++;
                }
            }
            if ($bar > 7) {
                $si->getStyle('A7:D'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                $si->getStyle('A7:D'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
                $si->getStyle('A7:D'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            }

            $bar += 2;
            $si->getStyle('A'.$bar)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC0CB');
            $si->setCellValue('B'.$bar, ' = EX KARYAWAN');

            $idxSheet++;
        }

        if ($idxSheet == 0) {
            $spreadsheet->getActiveSheet()->setTitle('Kosong');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_KARYAWAN_PERJOINT_' . $tahunAwal . '_' . $tahunAkhir . '.xlsx"');
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


    public function nikTlpKaryawan() {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('NIK KARYAWAN FJG');

        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);

        $details = [];
        $activeAreas = [];
        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $details[$staf][$area][] = $d;
            $activeAreas[$kodeArea] = strtoupper($d->area ? $d->area->nama : 'Lainnya');
        }

        $activeAreasSorted = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($activeAreas[$a->kode])) {
                $activeAreasSorted[] = strtoupper($a->nama);
            }
        }
        if (isset($activeAreas['Lainnya'])) {
            $activeAreasSorted[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreasSorted);

        $si->setCellValue('A2', 'LIST NIK KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A2:H2');

        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];
        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');
        $si->setCellValue('A3', 'UPDATE : ' . $bulanStr);
        $si->mergeCells('A3:H3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:H3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        $headers = ['A'=>'NO', 'B'=>'N A M A', 'C'=>'TGL LAHIR', 'D'=>'TANGGAL GABUNG', 'E'=>'N I K', 'F'=>'NO TLP', 'G'=>'AGE (YEARS)', 'H'=>'YEARS OF SERVICE'];
        foreach ($headers as $col => $title) {
            $si->setCellValue($col.'5', $title);
            $si->mergeCells($col.'5:'.$col.'6');
        }
        $si->getStyle('A5:H6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:H6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:H6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>15.00, 'D'=>15.00, 'E'=>21.33, 'F'=>18.00, 'G'=>12.00, 'H'=>15.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }

        $si->freezePane('A7');
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
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->nama);

                    $tglLahir = $d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '';
                    $si->setCellValue('C'.$bar, $tglLahir);

                    $tglMasuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $si->setCellValue('D'.$bar, $tglMasuk);

                    $si->setCellValue('E'.$bar, $d->nik);
                    $si->setCellValue('F'.$bar, $d->telepon);

                    $age = '';
                    if ($d->tanggal_lahir) {
                        $dt1 = date_create($d->tanggal_lahir);
                        $dt2 = date_create('today');
                        $age = date_diff($dt1, $dt2)->y;
                    }
                    $si->setCellValue('G'.$bar, $age);

                    $service = '';
                    if ($d->tanggal_masuk) {
                        $dt1 = date_create($d->tanggal_masuk);
                        $dt2 = date_create('today');
                        $service = date_diff($dt1, $dt2)->y;
                    }
                    $si->setCellValue('H'.$bar, $service);

                    $si->getStyle('A'.$bar.':H'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('E'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('F'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('G'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('H'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar.':H'.$bar)->getAlignment()->setWrapText(true);

                    $bar++;
                    $nomor++;
                }
                // 1 blank row after each area/group
                $bar++;
            }
        }

        $lastDataRow = $bar - 1;
        if ($lastDataRow >= 7) {
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        }

        // --- DIVISI TABLE ---
        $bar += 1;
        $divisiStart = $bar;
        $bar++; // blank row inside border (top)

        $divisis = \App\Models\Divisi::orderBy('nama', 'asc')->get();
        foreach ($divisis as $div) {
            $si->setCellValue('B'.$bar, $div->nama);
            $si->setCellValue('C'.$bar, $div->kode);

            $si->getStyle('B'.$bar)->getAlignment()->setHorizontal('left');
            $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
            $bar++;
        }

        $divisiEnd = $bar;
        $bar++; // advance past the blank row inside border (bottom)

        $si->getStyle('B'.$divisiStart.':C'.$divisiEnd)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $si->getStyle('B'.$divisiStart.':C'.$divisiEnd)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="NIK_&_TLP_KARYAWAN.xlsx"');
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


        public function dataPendidikanKaryawan() {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('DATA PENDIDIKAN');

        // Calculate active areas
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $activeAreas = [];
        $details = [];

        foreach ($dataKaryawan as $d) {
            $staf = $d->staf;
            $area = $d->area ? $d->area->nama : 'Lainnya';
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
            $activeAreas[$kodeArea] = strtoupper($d->area ? $d->area->nama : 'Lainnya');
            $details[$staf][$area][] = $d;
        }
        $activeAreasSorted = [];
        $dbAreasModels = \App\Models\Area::orderBy('urutan', 'asc')->get();
        foreach ($dbAreasModels as $a) {
            if (isset($activeAreas[$a->kode])) {
                $activeAreasSorted[] = strtoupper($a->nama);
            }
        }
        if (isset($activeAreas['Lainnya'])) {
            $activeAreasSorted[] = 'LAINNYA';
        }
        $areaString = implode(' - ', $activeAreasSorted);

        // Title
        $si->setCellValue('A2', 'DATA PENDIDIKAN KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A2:H2');
        $si->setCellValue('A3', $areaString);
        $si->mergeCells('A3:H3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:H3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        // Headers
        $si->setCellValue('A5', 'NO'); $si->mergeCells('A5:A6');
        $si->setCellValue('B5', 'N A M A'); $si->mergeCells('B5:B6');
        $si->setCellValue('C5', 'TANGGAL GABUNG'); $si->mergeCells('C5:C6');
        $si->setCellValue('D5', 'AGE (YEARS)'); $si->mergeCells('D5:D6');
        $si->setCellValue('E5', 'YEARS OF SERVICE'); $si->mergeCells('E5:E6');
        $si->setCellValue('F5', 'N I K'); $si->mergeCells('F5:F6');
        $si->setCellValue('G5', 'J A B A T A N'); $si->mergeCells('G5:G6');
        $si->setCellValue('H5', 'PENDIDIKAN TERAKHIR'); $si->mergeCells('H5:H6');

        $si->getStyle('A5:H6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:H6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:H6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $si->getRowDimension(5)->setRowHeight(18);
        $si->getRowDimension(6)->setRowHeight(15);

        // Column Widths
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>11.00, 'D'=>10.77, 'E'=>16.00, 'F'=>21.33, 'G'=>40.14, 'H'=>45.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
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
                    $si->setCellValue('A'.$bar, $nomor);
                    $si->setCellValue('B'.$bar, $d->nama);

                    $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $si->setCellValue('C'.$bar, $tgl_masuk); // Masa Kerja

                    $age = '';
                    if ($d->tanggal_lahir) {
                        $dt1 = date_create($d->tanggal_lahir);
                        $dt2 = date_create('today');
                        $age = date_diff($dt1, $dt2)->y;
                    }
                    $si->setCellValue('D'.$bar, $age);

                    $service = '';
                    if ($d->tanggal_masuk) {
                        $dt1 = date_create($d->tanggal_masuk);
                        $dt2 = date_create('today');
                        $service = date_diff($dt1, $dt2)->y;
                    }
                    $si->setCellValue('E'.$bar, $service);

                    $si->setCellValue('F'.$bar, $d->nik ? $d->nik : '');
                    $si->setCellValue('G'.$bar, $d->jabatan ? $d->jabatan->nama : '');

                    $pendidikanFormat = '';
                    if ($d->pendidikan) {
                        $pendidikanFormat = $d->pendidikan->nama;
                        $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                        $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));

                        if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                        if ($jurusan) {
                            $pendidikanFormat .= ' , Jurusan: ' . $jurusan;
                        }
                    }
                    $si->setCellValue('H'.$bar, $pendidikanFormat);

                    $si->getStyle('A'.$bar.':H'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('E'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('F'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar.':H'.$bar)->getAlignment()->setWrapText(true);

                    $bar++;
                    $nomor++;
                }
                // 1 blank row after each area/group
                $bar++;
            }
        }

        $lastDataRow = $bar - 1;
        if ($lastDataRow >= 7) {
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:H'.$lastDataRow)->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_PENDIDIKAN_KARYAWAN.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

                        public function biodataKaryawan($id) {
        $karyawan = $this->repoKaryawan->findById($id);
        if (!$karyawan) return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('BIODATA');

        // Page Setup
        $si->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $si->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $si->getPageSetup()->setFitToPage(true);
        $si->getPageSetup()->setFitToWidth(1);
        $si->getPageSetup()->setFitToHeight(0);

        // Column Widths
        $si->getColumnDimension('A')->setWidth(3); // Small margin left
        $si->getColumnDimension('B')->setWidth(26);
        $si->getColumnDimension('C')->setWidth(3);
        $si->getColumnDimension('D')->setWidth(40);
        $si->getColumnDimension('E')->setWidth(15);
        $si->getColumnDimension('F')->setWidth(15);
        $si->getColumnDimension('G')->setWidth(3);
        $si->getColumnDimension('H')->setWidth(35);

        $themeColor = 'FF1F4E78'; // Dark Blue
        $sectionColor = 'FFD9E1F2'; // Light Blue

        // Header Title
        $si->setCellValue('B2', 'BIODATA KARYAWAN');
        $si->mergeCells('B2:H3');
        $si->getStyle('B2:H3')->getAlignment()->setHorizontal('center')->setVertical('center');
        $si->getStyle('B2:H3')->getFont()->setSize(16)->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $si->getStyle('B2:H3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($themeColor);

        $bar = 5;

        // Helper function for section headers
        $addSectionHeader = function($title, $row) use ($si, $sectionColor, $themeColor) {
            $si->setCellValue('B'.$row, $title);
            $si->mergeCells('B'.$row.':H'.$row);
            $si->getStyle('B'.$row.':H'.$row)->getFont()->setBold(true)->getColor()->setARGB($themeColor);
            $si->getStyle('B'.$row.':H'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($sectionColor);
            $si->getStyle('B'.$row.':H'.$row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)->getColor()->setARGB($themeColor);
            $si->getStyle('B'.$row.':H'.$row)->getAlignment()->setVertical('center');
            $si->getRowDimension($row)->setRowHeight(20);
        };

        // --- 1. DATA PRIBADI ---
        $addSectionHeader('I. DATA PRIBADI', $bar);
        $bar += 2;

        $usia = '';
        if ($karyawan->tanggal_lahir) {
            $dt1 = date_create($karyawan->tanggal_lahir);
            $dt2 = date_create('today');
            $usia = date_diff($dt1, $dt2)->y . ' Tahun';
        }

        $masaKerja = '';
        if ($karyawan->tanggal_masuk) {
            $dt1 = date_create($karyawan->tanggal_masuk);
            $dt2 = $karyawan->tanggal_keluar ? date_create($karyawan->tanggal_keluar) : date_create('today');
            $diff = date_diff($dt1, $dt2);
            $masaKerja = $diff->y . ' Tahun ' . $diff->m . ' Bulan ' . $diff->d . ' Hari';
        }

        $j_anak = method_exists($karyawan, 'jumlahAnak') ? $karyawan->jumlahAnak() : $karyawan->jumlah_anak;

        $fields = [
            ['NIK / No. Karyawan', $karyawan->nik, 'Jenis Karyawan', $karyawan->staf == 'Y' ? 'Staf' : 'Non-Staf'],
            ['Nama Lengkap', $karyawan->nama, 'Status Aktif', $karyawan->aktif == 'Y' ? 'Aktif' : 'Non-Aktif'],
            ['Area', $karyawan->area ? $karyawan->area->nama : '', 'Tanggal Masuk', $karyawan->tanggal_masuk ? date('d-m-Y', strtotime($karyawan->tanggal_masuk)) : ''],
            ['Divisi', $karyawan->divisi ? $karyawan->divisi->nama : '', 'Tanggal Keluar', $karyawan->tanggal_keluar ? date('d-m-Y', strtotime($karyawan->tanggal_keluar)) : ''],
            ['Jabatan', $karyawan->jabatan ? $karyawan->jabatan->nama : '', 'Masa Kerja', $masaKerja],
            ['Tempat Lahir', $karyawan->tempat_lahir, 'Status Kerja', $karyawan->statusKerja ? $karyawan->statusKerja->nama : ''],
            ['Tanggal Lahir', $karyawan->tanggal_lahir ? date('d-m-Y', strtotime($karyawan->tanggal_lahir)) : '', 'Status PTKP', $karyawan->ptkp ? $karyawan->ptkp->nama : ''],
            ['Usia', $usia, 'Jenis Kelamin', $karyawan->kelamin == 'L' ? 'Laki-Laki' : ($karyawan->kelamin == 'P' ? 'Perempuan' : '')],
            ['Status Pernikahan', $karyawan->kawin == 'Y' ? 'Kawin' : ($karyawan->kawin == 'N' ? 'Single' : 'Single Parent'), 'Agama', $karyawan->agama ? $karyawan->agama->nama : ''],
            ['Jumlah Anak', $j_anak ? "'".$j_anak : "'0", 'Pendidikan Terakhir', $karyawan->pendidikan ? $karyawan->pendidikan->nama : ''],
            ['No. KTP', $karyawan->nomor_ktp ? "'".$karyawan->nomor_ktp : '', 'Jurusan', $karyawan->pendidikan_jurusan],
            ['No. KK', $karyawan->nomor_kk ? "'".$karyawan->nomor_kk : '', 'Almamater', $karyawan->pendidikan_almamater],
            ['No. Paspor', $karyawan->nomor_paspor ? "'".$karyawan->nomor_paspor : '', 'Email', $karyawan->email],
            ['NPWP', $karyawan->nomor_pwp ? "'".$karyawan->nomor_pwp : '', 'No. Telepon / HP', $karyawan->telepon ? "'".$karyawan->telepon : ''],
        ];

        foreach ($fields as $f) {
            $si->setCellValue('B'.$bar, $f[0]); $si->setCellValue('C'.$bar, ':'); $si->setCellValue('D'.$bar, $f[1]);
            if (isset($f[2])) {
                $si->setCellValue('F'.$bar, $f[2]); $si->setCellValue('G'.$bar, ':'); $si->setCellValue('H'.$bar, $f[3]);
            }
            $si->getRowDimension($bar)->setRowHeight(18);
            $bar++;
        }

        $bar++;
        $si->setCellValue('B'.$bar, 'Alamat KTP'); $si->setCellValue('C'.$bar, ':'); $si->setCellValue('D'.$bar, trim(preg_replace('/\s+/', ' ', (string)$karyawan->alamat_ktp)));
        $si->mergeCells('D'.$bar.':H'.$bar); $si->getStyle('D'.$bar)->getAlignment()->setWrapText(true); $si->getRowDimension($bar)->setRowHeight(30);
        $bar++;
        $si->setCellValue('B'.$bar, 'Alamat Tinggal'); $si->setCellValue('C'.$bar, ':'); $si->setCellValue('D'.$bar, trim(preg_replace('/\s+/', ' ', (string)$karyawan->alamat_tinggal)));
        $si->mergeCells('D'.$bar.':H'.$bar); $si->getStyle('D'.$bar)->getAlignment()->setWrapText(true); $si->getRowDimension($bar)->setRowHeight(30);
        $bar += 2;

        // Table header style helper
        $styleTableHeader = function($range) use ($si, $themeColor) {
            $si->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $si->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($themeColor);
            $si->getStyle($range)->getAlignment()->setHorizontal('center')->setVertical('center');
            $si->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFCCCCCC');
        };

        // Table body style helper
        $styleTableBody = function($range) use ($si) {
            $si->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFCCCCCC');
            $si->getStyle($range)->getAlignment()->setVertical('center');
        };

        // --- 2. ANGGOTA KELUARGA ---
        $addSectionHeader('II. ANGGOTA KELUARGA', $bar);
        $bar += 2;

        $mapHubungan = [
            'S' => 'Suami',
            'I' => 'Istri',
            'A' => 'Anak',
            'M' => 'Menantu',
            'C' => 'Cucu',
            'O' => 'Orang Tua',
            'T' => 'Mertua',
            'F' => 'Famili Lain',
        ];

        $keluargas = method_exists($karyawan, 'keluargas') ? $karyawan->keluargas()->get() : [];
        if (count($keluargas) > 0) {
            $si->setCellValue('B'.$bar, 'Nama Lengkap'); $si->mergeCells('B'.$bar.':C'.$bar);
            $si->setCellValue('D'.$bar, 'Nomor KTP');
            $si->setCellValue('E'.$bar, 'Hubungan'); $si->mergeCells('E'.$bar.':F'.$bar);
            $si->setCellValue('G'.$bar, 'Tempat & Tanggal Lahir'); $si->mergeCells('G'.$bar.':H'.$bar);
            $styleTableHeader('B'.$bar.':H'.$bar);
            $si->getRowDimension($bar)->setRowHeight(20);
            $bar++;

            $startTable = $bar;
            foreach ($keluargas as $kel) {
                $si->setCellValue('B'.$bar, $kel->nama); $si->mergeCells('B'.$bar.':C'.$bar);
                $si->setCellValue('D'.$bar, $kel->nomor_ktp ? "'".$kel->nomor_ktp : '');
                $hubLabel = isset($mapHubungan[$kel->hubungan]) ? $mapHubungan[$kel->hubungan] : $kel->hubungan;
                $si->setCellValue('E'.$bar, $hubLabel); $si->mergeCells('E'.$bar.':F'.$bar);
                $ttl = $kel->tempat_lahir . ', ' . ($kel->tanggal_lahir ? date('d-m-Y', strtotime($kel->tanggal_lahir)) : '');
                $si->setCellValue('G'.$bar, $ttl); $si->mergeCells('G'.$bar.':H'.$bar);
                $si->getRowDimension($bar)->setRowHeight(18);
                $bar++;
            }
            $styleTableBody('B'.$startTable.':H'.($bar-1));
        } else {
            $si->setCellValue('B'.$bar, '- Tidak ada data anggota keluarga -');
            $si->getStyle('B'.$bar)->getFont()->setItalic(true);
            $bar++;
        }
        $bar += 2;

        // --- 3. KONTAK DARURAT ---
        $addSectionHeader('III. KONTAK DARURAT KELUARGA', $bar);
        $bar += 2;

        $kontaks = method_exists($karyawan, 'keluargaKontaks') ? $karyawan->keluargaKontaks()->get() : [];
        if (count($kontaks) > 0) {
            $si->setCellValue('B'.$bar, 'No. Telepon'); $si->mergeCells('B'.$bar.':C'.$bar);
            $si->setCellValue('D'.$bar, 'Keterangan'); $si->mergeCells('D'.$bar.':H'.$bar);
            $styleTableHeader('B'.$bar.':H'.$bar);
            $si->getRowDimension($bar)->setRowHeight(20);
            $bar++;

            $startTable = $bar;
            foreach ($kontaks as $kon) {
                $si->setCellValue('B'.$bar, $kon->telepon ? "'".$kon->telepon : ''); $si->mergeCells('B'.$bar.':C'.$bar);
                $si->setCellValue('D'.$bar, $kon->nama); $si->mergeCells('D'.$bar.':H'.$bar);
                $si->getRowDimension($bar)->setRowHeight(18);
                $bar++;
            }
            $styleTableBody('B'.$startTable.':H'.($bar-1));
        } else {
            $si->setCellValue('B'.$bar, '- Tidak ada data kontak darurat -');
            $si->getStyle('B'.$bar)->getFont()->setItalic(true);
            $bar++;
        }
        $bar += 2;

        // --- 4. RIWAYAT TRAINING ---
        $addSectionHeader('IV. RIWAYAT TRAINING', $bar);
        $bar += 2;

        $trainings = method_exists($karyawan, 'trainingKaryawans') ? $karyawan->trainingKaryawans()->get() : [];
        if (count($trainings) > 0) {
            $si->setCellValue('B'.$bar, 'Nama Training'); $si->mergeCells('B'.$bar.':C'.$bar);
            $si->setCellValue('D'.$bar, 'Tanggal');
            $si->setCellValue('E'.$bar, 'Keterangan'); $si->mergeCells('E'.$bar.':H'.$bar);
            $styleTableHeader('B'.$bar.':H'.$bar);
            $si->getRowDimension($bar)->setRowHeight(20);
            $bar++;

            $startTable = $bar;
            foreach ($trainings as $tr) {
                $trainingName = $tr->training ? $tr->training->nama : '';
                $si->setCellValue('B'.$bar, $trainingName); $si->mergeCells('B'.$bar.':C'.$bar);
                $si->setCellValue('D'.$bar, $tr->tanggal ? date('d-m-Y', strtotime($tr->tanggal)) : '');
                $si->setCellValue('E'.$bar, $tr->keterangan); $si->mergeCells('E'.$bar.':H'.$bar);
                $si->getRowDimension($bar)->setRowHeight(18);
                $bar++;
            }
            $styleTableBody('B'.$startTable.':H'.($bar-1));
        } else {
            $si->setCellValue('B'.$bar, '- Tidak ada riwayat training -');
            $si->getStyle('B'.$bar)->getFont()->setItalic(true);
            $bar++;
        }
        $bar += 2;

        // --- 5. PERJANJIAN KERJA ---
        $addSectionHeader('V. PERJANJIAN KERJA', $bar);
        $bar += 2;

        $perjanjians = method_exists($karyawan, 'perjanjianKerjas') ? $karyawan->perjanjianKerjas()->orderBy('tanggal_awal')->get() : [];
        if (count($perjanjians) > 0) {
            $si->setCellValue('B'.$bar, 'Nomor Kontrak'); $si->mergeCells('B'.$bar.':C'.$bar);
            $si->setCellValue('D'.$bar, 'Status Kerja');
            $si->setCellValue('E'.$bar, 'Mulai'); $si->mergeCells('E'.$bar.':F'.$bar);
            $si->setCellValue('G'.$bar, 'Berakhir'); $si->mergeCells('G'.$bar.':H'.$bar);
            $styleTableHeader('B'.$bar.':H'.$bar);
            $si->getRowDimension($bar)->setRowHeight(20);
            $bar++;

            $startTable = $bar;
            foreach ($perjanjians as $pj) {
                $si->setCellValue('B'.$bar, $pj->nomor); $si->mergeCells('B'.$bar.':C'.$bar);
                $si->setCellValue('D'.$bar, $pj->statusKerja ? $pj->statusKerja->nama : '');
                $si->setCellValue('E'.$bar, $pj->tanggal_awal ? date('d-m-Y', strtotime($pj->tanggal_awal)) : ''); $si->mergeCells('E'.$bar.':F'.$bar);
                $si->setCellValue('G'.$bar, $pj->tanggal_akhir ? date('d-m-Y', strtotime($pj->tanggal_akhir)) : ''); $si->mergeCells('G'.$bar.':H'.$bar);
                $si->getRowDimension($bar)->setRowHeight(18);
                $bar++;
            }
            $styleTableBody('B'.$startTable.':H'.($bar-1));
        } else {
            $si->setCellValue('B'.$bar, '- Tidak ada data perjanjian kerja -');
            $si->getStyle('B'.$bar)->getFont()->setItalic(true);
            $bar++;
        }

        $bar += 3;
        // Tanggal Cetak
        $si->setCellValue('B'.$bar, 'Tanggal cetak : ' . date('d-m-Y'));
        $si->getStyle('B'.$bar)->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF7F7F7F');

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="BIODATA_'.str_replace(' ', '_', strtoupper($karyawan->nama)).'.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function dataStatusKaryawan() {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->setBold(TRUE);

        $si = $spreadsheet->getActiveSheet();
        $si->setShowGridlines(false);
        $si->setTitle('DATA KARYAWAN & STATUS');

        // Month names array for formatting
        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];

        $dbAreas = \App\Models\Area::orderBy('urutan', 'asc')->pluck('nama')->toArray();
        $areaString = implode(' - ', $dbAreas);

        // Title Rows
        $si->setCellValue('A2', 'STATUS KARYAWAN PT.FRATEKINDO JAYA GEMILANG');
        $si->mergeCells('A2:G2');
        $si->setCellValue('A3', $areaString);
        $si->mergeCells('A3:G3');

        $styleJudul = [
            'font' => [
                'name' => 'Malgun Gothic',
                'size' => 14,
                'bold' => true,
                'underline' => true,
                'color' => ['argb' => '0000FF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $si->getStyle('A2:G3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');
        $si->setCellValue('A4', 'UPDATE : ' . $bulanStr);
        $si->mergeCells('A4:G4');
        $si->getStyle('A4:G4')->getAlignment()->setHorizontal('center');
        $si->getStyle('A4:G4')->getFont()->setUnderline(true)->getColor()->setARGB('0000FF');

        // Headers
        $si->setCellValue('A6', 'NO'); $si->mergeCells('A6:A7');
        $si->setCellValue('B6', 'N A M A'); $si->mergeCells('B6:B7');
        $si->setCellValue('C6', 'TEMPAT & TGL LAHIR'); $si->mergeCells('C6:C7');
        $si->setCellValue('D6', 'TANGGAL GABUNG'); $si->mergeCells('D6:D7');
        $si->setCellValue('E6', 'J A B A T A N'); $si->mergeCells('E6:E7');
        $si->setCellValue('F6', 'PENDIDIKAN TERAKHIR'); $si->mergeCells('F6:F7');
        $si->setCellValue('G6', 'STATUS KARYAWAN PKWT / KONTRAK'); $si->mergeCells('G6:G7');

        $si->getStyle('A6:G7')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A6:G7')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A6:G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $si->getRowDimension(6)->setRowHeight(18);
        $si->getRowDimension(7)->setRowHeight(15);

        // Column Widths
        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>30.00, 'D'=>15.00, 'E'=>40.14, 'F'=>45.00, 'G'=>45.00];
        foreach ($widths as $col => $width) {
            $si->getColumnDimension($col)->setWidth($width);
        }

        // Data Setup
        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
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

        $si->freezePane('A8');
        $bar = 8;
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

                    $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                    $si->setCellValue('C'.$bar, $ttl);

                    $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                    $si->setCellValue('D'.$bar, $tgl_masuk);

                    $si->setCellValue('E'.$bar, $d->jabatan ? $d->jabatan->nama : '');

                    // Pendidikan Format
                    $pendidikanFormat = '';
                    if ($d->pendidikan) {
                        $pendidikanFormat = $d->pendidikan->nama;
                        $almamater = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_almamater));
                        $jurusan = trim(preg_replace('/\s+/', ' ', (string)$d->pendidikan_jurusan));

                        if ($almamater) $pendidikanFormat .= ' ' . $almamater;
                        if ($jurusan) {
                            $pendidikanFormat .= ' , Jurusan: ' . $jurusan;
                        }
                    }
                    $si->setCellValue('F'.$bar, $pendidikanFormat);

                    // Status & Perjanjian
                    $statusNamaCell = $d->statusKerja ? $d->statusKerja->nama : '';
                    $perjanjians = $d->perjanjianKerjas()->orderBy('tanggal_awal', 'asc')->get();
                    $numPerjanjian = $perjanjians->count();

                    if ($numPerjanjian > 0) {
                        $latestPerjanjian = $perjanjians[$numPerjanjian - 1];
                        $statusId = $d->status_kerja_id;
                        $bulanMapSingkat = [
                            1 => "JAN", 2 => "PEB", 3 => "MAR", 4 => "APR", 5 => "MEI", 6 => "JUN",
                            7 => "JUL", 8 => "AGUSTUS", 9 => "SEP", 10 => "OKT", 11 => "NOP", 12 => "DES"
                        ];

                        if ($statusId == '1') {
                            $awl = strtotime($latestPerjanjian->tanggal_awal);
                            $tglAwal = date('d ', $awl) . $bulanMapSingkat[(int)date('n', $awl)] . date('\'y', $awl);
                            $statusNamaCell .= " (Per: " . $tglAwal . ")";
                        } else {
                            $awl = strtotime($latestPerjanjian->tanggal_awal);
                            $tglAwal = date('d ', $awl) . $bulanMapSingkat[(int)date('n', $awl)] . date('\'y', $awl);
                            $tglAkhir = '';
                            if ($latestPerjanjian->tanggal_akhir) {
                                $akr = strtotime($latestPerjanjian->tanggal_akhir);
                                $tglAkhir = date('d ', $akr) . $bulanMapSingkat[(int)date('n', $akr)] . date('\'y', $akr);
                            }
                            $statusNamaCell .= " (PER : " . $tglAwal . ($tglAkhir ? " S/D " . $tglAkhir : "") . ")";
                        }
                    }
                    $si->setCellValue('G'.$bar, $statusNamaCell);

                    $si->getStyle('A'.$bar.':G'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar.':D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('F'.$bar.':G'.$bar)->getAlignment()->setWrapText(true);

                    // Coloring
                    $statusId = $d->status_kerja_id;
                    $bgColor = null;
                    if ($statusId == '2') $bgColor = 'FFBBDEFB';
                    elseif ($statusId == '3') $bgColor = 'FFFFCC80';
                    elseif ($statusId == '4') $bgColor = 'FFEA80FC';
                    elseif ($statusId == '5') $bgColor = 'FFB9F6CA';
                    elseif ($statusId != '1' && $statusId != null) $bgColor = 'FFF44336';

                    if ($bgColor) {
                        $si->getStyle('A'.$bar.':G'.$bar)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);
                    }

                    $bar++;
                    $nomor++;
                }

                // 1 blank row after each area/group
                $bar++;
            }
        }

        if ($bar > 8) {
            $si->getStyle('A8:G'.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A8:G'.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_THIN);
            $si->getStyle('A8:G'.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
        }

        $bar += 2;
        $allAreas = \App\Models\Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
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

            $rincianArea = [];
            foreach ($allAreas as $kodeArea) {
                $nilai = isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]) ? $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] : 0;
                $rincianArea[] = $kodeArea . ': ' . $nilai;
            }
            $rincianAreaStr = implode(' - ', $rincianArea);
            $si->setCellValue('D'.$bar, $rincianAreaStr);
            $si->mergeCells('D'.$bar.':G'.$bar);

            $si->getStyle('B'.$bar.':G'.$bar)->getFont()->getColor()->setARGB($textColor);

            $bar++;
        }

        $si->setCellValue('B'.$bar, 'TOTAL KARYAWAN');
        $si->setCellValue('C'.$bar, ': ' . $totalKaryawan);

        $rincianAreaTotal = [];
        foreach ($allAreas as $kodeArea) {
            $nilaiArea = isset($totalKaryawanPerArea[$kodeArea]) ? $totalKaryawanPerArea[$kodeArea] : 0;
            $rincianAreaTotal[] = $kodeArea . ': ' . $nilaiArea;
        }
        $rincianAreaTotalStr = implode(' - ', $rincianAreaTotal);
        $si->setCellValue('D'.$bar, $rincianAreaTotalStr);
        $si->mergeCells('D'.$bar.':G'.$bar);

        $si->getStyle('B'.$bar.':G'.$bar)->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="DATA_STATUS_KARYAWAN.xlsx"');
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
