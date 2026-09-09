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

        $headers = ['A'=>'NO', 'B'=>'N A M A', 'C'=>'TEMPAT & TGL LAHIR', 'D'=>'MASA KERJA', 'E'=>'JABATAN', 'F'=>'ALAMAT SESUAI KTP', 'G'=>'ALAMAT TINGGAL SEKARANG'];
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
        $si->setCellValue('A2', 'DATA KARYAWAN FJG');
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
        $si->mergeCells('A2:F2');

        $bulanMap = [
            1 => 'JANUARI', 2 => 'PEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
            7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOPEMBER', 12 => 'DESEMBER'
        ];
        $bulanStr = $bulanMap[(int)date('n')] . ' ' . date('Y');
        $si->setCellValue('A3', 'UPDATE : ' . $bulanStr);
        $si->mergeCells('A3:F3');

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
        $si->getStyle('A2:F3')->applyFromArray($styleJudul);
        $si->getRowDimension(2)->setRowHeight(22);
        $si->getRowDimension(3)->setRowHeight(22);

        $headers = ['A'=>'NO', 'B'=>'N A M A', 'C'=>'TGL LAHIR', 'D'=>'MASA KERJA', 'E'=>'N I K', 'F'=>'NO TLP'];
        foreach ($headers as $col => $title) {
            $si->setCellValue($col.'5', $title);
            $si->mergeCells($col.'5:'.$col.'6');
        }
        $si->getStyle('A5:F6')->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $si->getStyle('A5:F6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $si->getStyle('A5:F6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $widths = ['A'=>4.55, 'B'=>38.00, 'C'=>15.00, 'D'=>15.00, 'E'=>21.33, 'F'=>18.00];
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

                    $si->getStyle('A'.$bar.':F'.$bar)->getAlignment()->setVertical('top');
                    $si->getStyle('A'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('C'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('D'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('E'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('F'.$bar)->getAlignment()->setHorizontal('center');
                    $si->getStyle('B'.$bar.':F'.$bar)->getAlignment()->setWrapText(true);

                    $bar++;
                    $nomor++;
                }
                // 1 blank row after each area/group
                $bar++;
            }
        }

        $lastDataRow = $bar - 1;
        if ($lastDataRow >= 7) {
            $si->getStyle('A7:F'.$lastDataRow)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:F'.$lastDataRow)->getBorders()->getVertical()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $si->getStyle('A7:F'.$lastDataRow)->getBorders()->getHorizontal()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
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
        $si->setCellValue('D6', 'MASA KERJA'); $si->mergeCells('D6:D7');
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
