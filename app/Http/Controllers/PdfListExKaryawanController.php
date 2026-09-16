<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Models\Phk;
use App\Models\Area;
use Mpdf\Mpdf;

class PdfListExKaryawanController extends Controller
{
    public function rekap($tahun_awal, $tahun_akhir) {
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
            .text-blue { color: #0000FF; font-weight: bold; }
        </style>";

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
            $html .= "<div class=\"title\">KOSONG</div>";
        }

        $sheetIndex = 0;
        foreach ($detailsByYear as $year => $details) {
            if ($sheetIndex > 0) {
                $html .= "<pagebreak />";
            }
            $sheetIndex++;

            $html .= "<div class=\"title\">EX KARYAWAN $year</div>";

            $html .= "<table>";

            $html .= "<tr style=\"height:0; line-height:0; font-size:0;\">";
            $widths = [10, 55, 30, 30, 60, 30, 40, 60, 45, 75, 75, 30, 30, 35, 60, 40, 50, 60];
            foreach ($widths as $w) $html .= "<td style=\"width:{$w}mm; padding:0; border:none; height:0;\"></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<th rowspan=\"2\">NO</th>";
            $html .= "<th rowspan=\"2\">N A M A</th>";
            $html .= "<th rowspan=\"2\">MASA KERJA</th>";
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
            $html .= "<th rowspan=\"2\">STATUS PHK KARYAWAN</th>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<th>NOMOR KK</th>";
            $html .= "<th>NO.KTP / PASPOR</th>";
            $html .= "<th>NAMA KARYAWAN & KELUARGA</th>";
            $html .= "<th>TEMPAT & TGL LAHIR</th>";
            $html .= "</tr>";

            krsort($details);
            $nomor = 1;
            foreach ($details as $staf => $areas) {
                if($staf == 'N') {
                    $html .= "<tr><td></td><td colspan=\"17\" class=\"al text-blue\">NON STAF :</td></tr>";
                }
                foreach ($areas as $area => $phks) {
                    if($staf == 'Y') {
                        $html .= "<tr><td></td><td colspan=\"17\" class=\"al text-blue\">".$area." :</td></tr>";
                    }

                    foreach ($phks as $phk) {
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

                        for ($i = 0; $i < $maxRows; $i++) {
                            $html .= "<tr>";

                            if ($i == 0) {
                                $html .= "<td class=\"ac\">".$nomor."</td>";
                                $html .= "<td class=\"al\">".$d->nama."</td>";
                                $tgl_masuk = $phk->tanggal_awal ? date('d-m-Y', strtotime($phk->tanggal_awal)) : '';
                                $tgl_akhir = $phk->tanggal_akhir ? date('d-m-Y', strtotime($phk->tanggal_akhir)) : '';
                                $masaKerjaFormat = $tgl_masuk . ($tgl_akhir ? ' s/d ' . $tgl_akhir : '');
                                $html .= "<td class=\"ac\">".$masaKerjaFormat."</td>";
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
                                // H to I (Keluarga)
                                if ($i <= $numKeluarga) {
                                    $keluarga = $keluargas[$i - 1];
                                    $html .= "<td class=\"al\">".$keluarga->nama."</td>";
                                    $ttlKeluarga = $keluarga->tempat_lahir . ', ' . ($keluarga->tanggal_lahir ? date('d-m-Y', strtotime($keluarga->tanggal_lahir)) : '');
                                    $html .= "<td class=\"al\">".$ttlKeluarga."</td>";
                                } else {
                                    $html .= "<td></td><td></td>";
                                }
                            }

                            // J & K (Alamat)
                            if ($i == 0) {
                                $alamat_ktp = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_ktp));
                                $alamat_tinggal = trim(preg_replace('/\s+/', ' ', (string)$d->alamat_tinggal));
                                $html .= "<td class=\"al\">".$alamat_ktp."</td>";
                                $html .= "<td class=\"al\">".$alamat_tinggal."</td>";
                                $html .= "<td class=\"ac\">".($d->telepon ? "".$d->telepon : '')."</td>";
                            } else {
                                $html .= "<td></td><td></td><td></td>"; // J, K, L
                            }

                            // M (Keluarga TLP)
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

                            // N (Status Kawin)
                            if ($i == 0) {
                                $kawinStatus = $d->kawin == 'Y' ? 'Kawin' : ($d->kawin == 'N' ? 'Single' : 'Single Parent');
                                $jumlahAnak = $d->jumlahAnak();
                                $kawinFormat = $kawinStatus . ($jumlahAnak > 0 ? ' / ' . $jumlahAnak : '');
                                $html .= "<td class=\"ac\">".$kawinFormat."</td>";
                            } else {
                                $html .= "<td></td>";
                            }

                            // O (Pendidikan)
                            if ($i == 0) {
                                $html .= "<td class=\"al\">".$pendidikanFormat."</td>";
                            } else if ($i == 1 && $pendidikanJurusan != '') {
                                $html .= "<td class=\"al\">".$pendidikanJurusan."</td>";
                            } else {
                                $html .= "<td></td>";
                            }

                            // P (Perjanjian Kerja)
                            if ($i < $numPerjanjian) {
                                $pj = $perjanjians[$i];
                                $html .= "<td class=\"ac\">".$pj->nomor."</td>";
                            } else {
                                $html .= "<td></td>";
                            }

                            // Q & R (Email & Status PHK)
                            if ($i == 0) {
                                $html .= "<td class=\"al\">".$d->email."</td>";

                                $statusNamaCell = $phk->statusKerja ? $phk->statusKerja->nama : '';
                                $statusPhkNama = $phk->statusPhk ? $phk->statusPhk->nama : '';
                                $keteranganPhk = $phk->keterangan ? ' ('.$phk->keterangan.')' : '';
                                $statusKolomR = $statusNamaCell . ($statusPhkNama ? ' / ' . $statusPhkNama : '') . $keteranganPhk;

                                $html .= "<td class=\"ac\">".$statusKolomR."</td>";
                            } else {
                                $html .= "<td></td><td></td>"; // Q, R empty
                            }

                            $html .= "</tr>";
                        }
                        $nomor++;
                    }
                }
            }
            $html .= "</table>";
        }

        $mpdf->WriteHTML($html);
        $mpdf->Output('DATA_EX_KARYAWAN.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}
