<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfListKaryawanController extends Controller
{
    protected $repoKaryawan, $repoArea;

    public function __construct(KaryawanRepository $repoKaryawan, AreaRepository $repoArea) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repoArea = $repoArea;
    }

    public function rekap() {
        $mpdf = new Mpdf([
            'format'        => 'A2-L',
            'margin_left'   => 5,
            'margin_right'  => 5,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $html = "<style>
            body { font-family: sans-serif; font-size: 6pt; }
            .title { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 10px; color: #0000FF; text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; table-layout: fixed; }
            th, td { border: 1px solid black; padding: 2px 4px; vertical-align: top; }
            th { text-align: center; font-weight: bold; background-color: #FFC000; vertical-align: middle; }
            .ac { text-align: center; }
            .al { text-align: left; }
            .ar { text-align: right; }
            .bg-yellow { background-color: #FFC000; }
            .bg-blue { background-color: #BBDEFB; }
            .bg-orange { background-color: #FFCC80; }
            .bg-purple { background-color: #EA80FC; }
            .bg-green { background-color: #B9F6CA; }
            .bg-red { background-color: #F44336; }
            .text-blue { color: #0000FF; font-weight: bold; }
            .legend-table td { border: none !important; }
        </style>";

        $dataKaryawan = $this->repoKaryawan->findAll(['aktif' => 'Y']);
        $activeAreas = [];
        foreach ($dataKaryawan as $d) {
            $kodeArea = $d->area ? $d->area->kode : 'Lainnya';
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

        $html .= "<div class=\"title\">LIST KARYAWAN PT.FRATEKINDO JAYA GEMILANG</div>";
        $html .= "<div class=\"title\">$areaString</div>";

        $html .= "<table>";
        
        $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
        $widths = [10, 55, 20, 30, 20, 60, 30, 40, 60, 45, 75, 75, 30, 30, 35, 60, 40, 50, 60];
        foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th rowspan=\"2\">NO</th>";
        $html .= "<th rowspan=\"2\">N A M A</th>";
        $html .= "<th rowspan=\"2\">MASA KERJA</th>";
        $html .= "<th rowspan=\"2\">NIK</th>";
        $html .= "<th rowspan=\"2\">AGAMA</th>";
        $html .= "<th rowspan=\"2\">J A B A T A N</th>";
        $html .= "<th colspan=\"4\">DOKUMEN KARYAWAN</th>";
        $html .= "<th rowspan=\"2\">ALAMAT SESUAI K T P</th>";
        $html .= "<th rowspan=\"2\">ALAMAT TINGGAL SEKARANG</th>";
        $html .= "<th rowspan=\"2\">NO.TLP</th>";
        $html .= "<th rowspan=\"2\">NO.TLP KELUARGA</th>";
        $html .= "<th rowspan=\"2\">STATUS</th>";
        $html .= "<th rowspan=\"2\">PENDIDIKAN TERAKHIR</th>";
        $html .= "<th rowspan=\"2\">NOMOR PERJANJIAN KERJA</th>";
        $html .= "<th rowspan=\"2\">EMAIL PRIBADI</th>";
        $html .= "<th rowspan=\"2\">STATUS KARYAWAN PKWT / KONTRAK</th>";
        $html .= "</tr>";

        $html .= "<tr>";
        $html .= "<th>NOMOR KK</th>";
        $html .= "<th>NO.NIK/PASSEPORT</th>";
        $html .= "<th>NAMA KARYAWAN & KELUARGA</th>";
        $html .= "<th>TEMPAT & TGL LAHIR</th>";
        $html .= "</tr>";

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

        krsort($details);
        $nomor = 1;
        foreach ($details as $staf => $areas) {
            if($staf == 'N') {
                $html .= "<tr><td></td><td colspan=\"18\" class=\"al text-blue\">NON STAF :</td></tr>";
            }
            foreach ($areas as $area => $karyawans) {
                if($staf == 'Y') {
                    $html .= "<tr><td></td><td colspan=\"18\" class=\"al text-blue\">".$area." :</td></tr>";
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

                    $statusId = $d->status_kerja_id;
                    $bgClass = "";
                    if ($statusId == '2') $bgClass = 'bg-blue';
                    elseif ($statusId == '3') $bgClass = 'bg-orange';
                    elseif ($statusId == '4') $bgClass = 'bg-purple';
                    elseif ($statusId == '5') $bgClass = 'bg-green';
                    elseif ($statusId != '1') $bgClass = 'bg-red';

                    for ($i = 0; $i < $maxRows; $i++) {
                        if ($i == 0) {
                            $html .= "<tr class=\"$bgClass\">";
                        } else {
                            $html .= "<tr>";
                        }
                        
                        if ($i == 0) {
                            $html .= "<td class=\"ac\">".$nomor."</td>";
                            $html .= "<td class=\"al\">".$d->nama."</td>";
                            $tgl_masuk = $d->tanggal_masuk ? date('d-m-Y', strtotime($d->tanggal_masuk)) : '';
                            $html .= "<td class=\"ac\">".$tgl_masuk."</td>";
                            $html .= "<td class=\"ac\">".($d->nik ? $d->nik : '')."</td>";
                            $html .= "<td class=\"ac\">".($d->agama ? $d->agama->nama : '')."</td>";
                            $html .= "<td class=\"al\">".($d->jabatan ? $d->jabatan->nama : '')."</td>";
                            $html .= "<td class=\"ac\">".($d->nomor_kk ? "".$d->nomor_kk : '')."</td>";
                            $ktp_or_paspor = $d->nomor_ktp ? $d->nomor_ktp : $d->nomor_paspor;
                            $html .= "<td class=\"ac\">".($ktp_or_paspor ? "".$ktp_or_paspor : '')."</td>";
                            $html .= "<td class=\"al\">".$d->nama."</td>";
                            $ttl = $d->tempat_lahir . ', ' . ($d->tanggal_lahir ? date('d-m-Y', strtotime($d->tanggal_lahir)) : '');
                            $html .= "<td class=\"al\">".$ttl."</td>";
                        } else {
                            // Blank for A to G
                            $html .= "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
                            // H to J (Keluarga)
                            if ($i <= $numKeluarga) {
                                $keluarga = $keluargas[$i - 1];
                                $html .= "<td class=\"ac\">".($keluarga->nomor_ktp ? "".$keluarga->nomor_ktp : '')."</td>";
                                $html .= "<td class=\"al\">".$keluarga->nama."</td>";
                                $ttlKeluarga = $keluarga->tempat_lahir . ', ' . ($keluarga->tanggal_lahir ? date('d-m-Y', strtotime($keluarga->tanggal_lahir)) : '');
                                $html .= "<td class=\"al\">".$ttlKeluarga."</td>";
                            } else {
                                $html .= "<td></td><td></td><td></td>";
                            }
                        }

                        // K & L (Alamat)
                        if ($i == 0) {
                            $alamat_ktp = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_ktp));
                            $alamat_tinggal = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_tinggal));
                            $html .= "<td class=\"al\">".$alamat_ktp."</td>";
                            $html .= "<td class=\"al\">".$alamat_tinggal."</td>";
                            $html .= "<td class=\"ac\">".($d->telepon ? "".$d->telepon : '')."</td>";
                        } else {
                            // Excel merges K and L, but user prefers grid without merge for consistency, or we output empty cells.
                            // I'll output empty cells to make it consistent with A-F.
                            $html .= "<td></td><td></td><td></td>"; // K, L, M
                        }

                        // N (Keluarga TLP)
                        if ($i == 0) {
                            $html .= "<td></td>"; // Family phone on row 0 is blank
                        } else {
                            if ($i <= $numKeluarga) {
                                $keluarga = $keluargas[$i - 1];
                                $html .= "<td class=\"ac\">".($keluarga->telepon ? "".$keluarga->telepon : '')."</td>";
                            } else {
                                $html .= "<td></td>";
                            }
                        }

                        // O (Status Kawin)
                        if ($i == 0) {
                            $kawinStatus = $d->kawin == 'Y' ? 'Kawin' : ($d->kawin == 'N' ? 'Single' : 'Single Parent');
                            $jumlahAnak = $d->jumlahAnak();
                            $kawinFormat = $kawinStatus . ($jumlahAnak > 0 ? ' / ' . $jumlahAnak : '');
                            $html .= "<td class=\"ac\">".$kawinFormat."</td>";
                        } else {
                            $html .= "<td></td>";
                        }

                        // P (Pendidikan)
                        if ($i == 0) {
                            $html .= "<td class=\"al\">".$pendidikanFormat."</td>";
                        } else if ($i == 1 && $pendidikanJurusan != '') {
                            $html .= "<td class=\"al\">".$pendidikanJurusan."</td>";
                        } else {
                            $html .= "<td></td>";
                        }

                        // Q (Perjanjian Kerja)
                        if ($i < $numPerjanjian) {
                            $pj = $perjanjians[$i];
                            $html .= "<td class=\"ac\">".$pj->nomor."</td>";
                        } else {
                            $html .= "<td></td>";
                        }

                        // R & S (Email & Status Karyawan)
                        if ($i == 0) {
                            $html .= "<td class=\"al\">".$d->email."</td>";
                            
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
                            $html .= "<td class=\"ac\">".$statusNamaCell."</td>";
                        } else {
                            $html .= "<td></td><td></td>"; // R, S empty
                        }

                        $html .= "</tr>";
                    }
                    $nomor++;
                }
            }
        }
        $html .= "</table>";

        $dbAreas = \App\Models\Area::orderBy('urutan', 'asc')->pluck('kode')->toArray();
        $allAreas = $dbAreas;
        foreach (array_keys($totalKaryawanPerArea) as $a) {
            if (!in_array($a, $allAreas)) {
                $allAreas[] = $a;
            }
        }

        $html .= "<br/><br/><table class=\"legend-table\" style=\"width:50%; table-layout:auto; font-weight:bold; border:none;\">";
        $html .= "<tr><td colspan=\"".(2 + count($allAreas))."\" style=\"border:none; font-size:10pt;\">KETERANGAN STATUS KARYAWAN</td></tr>";

        foreach ($totalKaryawanPerStatus as $statusNama => $total) {
            $statusId = isset($statusNamaToId[$statusNama]) ? $statusNamaToId[$statusNama] : null;
            $textStyle = "";
            if ($statusId == '2') $textStyle = "color: #2196F3;"; // Darker blue
            elseif ($statusId == '3') $textStyle = "color: #FF9800;"; // Darker orange
            elseif ($statusId == '4') $textStyle = "color: #9C27B0;"; // Darker purple
            elseif ($statusId == '5') $textStyle = "color: #4CAF50;"; // Darker green
            elseif ($statusId != '1' && $statusId != null) $textStyle = "color: #F44336;"; // Red

            $html .= "<tr>";
            $html .= "<td style=\"border:none; $textStyle\">".$statusNama."</td>";
            $html .= "<td style=\"border:none; $textStyle\">: ".$total."</td>";
            
            foreach ($allAreas as $kodeArea) {
                $nilai = isset($totalKaryawanPerStatusPerArea[$statusNama][$kodeArea]) ? $totalKaryawanPerStatusPerArea[$statusNama][$kodeArea] : 0;
                $html .= "<td style=\"border:none; padding-left:20px; $textStyle\">".$kodeArea.": ".$nilai."</td>";
            }
            $html .= "</tr>";
        }
        
        $html .= "<tr>";
        $html .= "<td style=\"border:none; color: #FF0000;\">TOTAL KARYAWAN</td>";
        $html .= "<td style=\"border:none; color: #FF0000;\">: ".$totalKaryawan."</td>";
        foreach ($allAreas as $kodeArea) {
            $nilai = isset($totalKaryawanPerArea[$kodeArea]) ? $totalKaryawanPerArea[$kodeArea] : 0;
            $html .= "<td style=\"border:none; padding-left:20px; color: #FF0000;\">".$kodeArea.": ".$nilai."</td>";
        }
        $html .= "</tr>";
        $html .= "</table>";
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_GENERAL_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
