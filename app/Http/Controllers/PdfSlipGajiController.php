<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\PotonganRepository;
use Mpdf\Mpdf;
use App\Repositories\AreaRepository;

class PdfSlipGajiController extends Controller
{
    protected $repoKaryawan, $repo, $repoPhk, $rpPotongan;

    public function __construct(KaryawanRepository $repoKaryawan, PayrollRepository $repo, PayrollPhkRepository $repoPhk, PotonganRepository $rpPotongan
    ) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repo         = $repo;
        $this->repoPhk      = $repoPhk;
        $this->rpPotongan   = $rpPotongan;
    }

    public function cetak($tahun, $bulan, $jenis, $area)
    {
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.recursion_limit', '10000000');
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $arrBulan = ['Desember','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanInt = (int)$bulan;

        $rpArea = app(AreaRepository::class);
        if($area == 'all') {
            $namaArea = '';
        } else {
            $dArea = $rpArea->findById($area);
            $namaArea = $dArea->nama.'_';
        }

        $dataDetails = $this->repo->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
        ]);
        $dataPhk = $this->repoPhk->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'staf' => $jenis == '3' ? 'N' : ($jenis == '4' ? '' : 'Y'),
            'area' => $area == 'all' ? '' : $area,
            'engineer' => $jenis == '1' ? 'Y' : ($jenis == '2' ? 'N' : ''),
        ]);
        $dataDetails = $dataDetails->merge($dataPhk)->sort(function($a, $b) {
            if ($a->karyawan->staf != $b->karyawan->staf) {
                return $a->karyawan->staf <=> $b->karyawan->staf;
            }
            if ($a->karyawan->area->urutan != $b->karyawan->area->urutan) {
                return $a->karyawan->area->urutan <=> $b->karyawan->area->urutan;
            }
            return $a->karyawan_id <=> $b->karyawan_id;
        })->values();

        $dataPotongans = $this->rpPotongan->findAll([
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);
        $potongans = [];
        foreach ($dataPotongans as $pot) {
            if ($pot->keterangan != '') {
                $potongans[$pot->karyawan_id][$pot->jenis][] = $pot->keterangan;
            }
        }

        $logoB64    = $this->imgB64(public_path('images/logo_fjg.jpg'),    'jpeg');
        $stempelB64 = $this->imgB64(public_path('images/stempel_full.png'), 'png');

        $css = '<style>
        body  { font-family: dejavusans; font-size: 6.85pt; font-weight: bold; letter-spacing: -0.25pt; margin:0; padding:0; }
        table { border-collapse: collapse; }
        td    { padding: 1pt 1.5pt; vertical-align: middle; line-height: 1.2; }
        .pts { padding-top: 0.5mm; }
        .pt1 { padding-top: 1mm; }
        .pt2 { padding-top: 2mm; }
        .pbs { padding-bottom: 0.5mm; }
        .pb1 { padding-bottom: 1mm; }
        .pb2 { padding-bottom: 2mm; }
        .r21  { height: 6.0mm; }
        .r17  { height: 5.0mm; }
        .r12  { height: 3.5mm; }
        .cgray { background-color: #D9D9D9; }
        .cblue { color: #0070C0; }
        .cred  { color: #FF0000; }
        .ac { text-align: center; }
        .ar { text-align: right;  }
        .ul   { text-decoration: underline; }
        .sz12 { font-size: 9pt; }
        .sz11 { font-size: 8pt; }
        .sz8  { font-size:  6pt; }
        .bb { border-bottom: 0.5pt solid #000; }
        .bt { border-top:    0.5pt solid #000; }
        </style>';

        $mpdf = new Mpdf([
            'format'            => 'A4',
            'margin_top'        => 12,
            'margin_bottom'     => 12,
            'margin_left'       => 6,
            'margin_right'      => 6,
            'default_font'      => 'dejavusans',
            'default_font_size' => 8.5,
        ]);
        
        $mpdf->SetTitle('Slip Gaji');
        $mpdf->WriteHTML($css);

        $chunks  = array_chunk($dataDetails->all(), 2);

        foreach ($chunks as $chunkIdx => $pair) {
            $htmlChunk = '';
            if ($chunkIdx > 0) {
                $htmlChunk .= '<pagebreak />';
            }
            foreach ($pair as $slipIdx => $d) {
                if ($slipIdx > 0) {
                    $htmlChunk .= '<div style="height:13mm;"></div>';
                }

                $tahunAwal    = ($bulanInt == 1) ? $tahun - 1 : $tahun;
                $periodeAwal  = '26 ' . $arrBulan[$bulanInt - 1] . "'" . substr($tahunAwal, -2);
                $periodeAkhir = '25 ' . $arrBulan[$bulanInt]     . "'" . substr($tahun, -2);

                $kId = $d->karyawan_id;
                $ketTP = isset($potongans[$kId]['TP']) ? '(' . implode(', ', $potongans[$kId]['TP']) . ')' : '';
                $ketKS = isset($potongans[$kId]['KS']) ? '(' . implode(', ', $potongans[$kId]['KS']) . ')' : '';
                $ketCC = isset($potongans[$kId]['CC']) ? '(' . implode(', ', $potongans[$kId]['CC']) . ')' : '';
                $ketBP = isset($potongans[$kId]['BP']) ? '(' . implode(', ', $potongans[$kId]['BP']) . ')' : '';
                $ketBN = isset($potongans[$kId]['BN']) ? '(' . implode(', ', $potongans[$kId]['BN']) . ')' : '';
                $ketKJ = isset($potongans[$kId]['KJ']) ? implode(', ', $potongans[$kId]['KJ'])              : '';
                $ketLL = isset($potongans[$kId]['LL']) ? '(' . implode(', ', $potongans[$kId]['LL']) . ')' : '';

                if ($d->makan_harian == 'Y') {
                    $uMakanLabel = 'U/makan &amp; Transport';
                    $ttgl = explode('-', $d->tanggal_awal);
                    $ttgm = explode('-', $d->tanggal_akhir);
                    $uMakanSub = '(Per: '.$ttgl[2].' '.$arrBulan[(int)$ttgl[1]]."'".substr($ttgl[0],-2)
                               .' s/d '.$ttgm[2].' '.$arrBulan[(int)$ttgm[1]]."'".substr($ttgm[0],-2).')';
                    $makanC = $this->n($d->uang_makan_harian);
                    $makanD = 'x'; $makanE = $d->hari_makan; $makanF = 'HR';
                } else {
                    $uMakanLabel = 'Tunjangan U/makan &amp; Transportasi';
                    $uMakanSub   = 'Bulan '.$arrBulan[$bulanInt].' '.$tahun;
                    $makanC = $makanD = $makanE = $makanF = '';
                }

                $ket25K = ($d->pot_25_hari > 0) ? $this->n($d->uang_makan_harian / 4) : '';
                $ket25L = ($d->pot_25_hari > 0) ? 'x'              : '';
                $ket25M = ($d->pot_25_hari > 0) ? $d->pot_25_hari  : '';
                $ket25N = ($d->pot_25_hari > 0) ? 'HR'             : '';

                if ($d->bonus > 0 && $d->insentif > 0) {
                    $bonusLabel = 'Bonus &amp; Insentif'; $bonusVal = $d->bonus + $d->insentif;
                } elseif ($d->bonus > 0) {
                    $bonusLabel = 'Bonus';    $bonusVal = $d->bonus;
                } elseif ($d->insentif > 0) {
                    $bonusLabel = 'Insentif'; $bonusVal = $d->insentif;
                } else {
                    $bonusLabel = 'Bonus';    $bonusVal = 0;
                }

                if ($d->telkomsel > 0 && $d->lain > 0) {
                    $lainLabel = 'Telkomsel &amp; Lain-lain'; $lainVal = $d->telkomsel + $d->lain;
                } elseif ($d->telkomsel > 0) {
                    $lainLabel = 'Telkomsel'; $lainVal = $d->telkomsel;
                } elseif ($d->lain > 0) {
                    $lainLabel = 'Lain-lain'; $lainVal = $d->lain;
                } else {
                    $lainLabel = 'Lain-lain'; $lainVal = 0;
                }

                $kompLabel = $ketKJ != '' ? 'Kompensasi ('.$ketKJ.')' : 'Kompensasi Ijin';
                $kompN     = ($d->pot_kompensasi_jam > 0) ? $d->pot_kompensasi_jam.' Jam' : '';
                $cutiM     = ($d->pot_cuti_hari > 0) ? $d->pot_cuti_hari : '';
                $cutiN     = ($d->pot_cuti_hari > 0) ? 'HR' : '';

                $totalA = $d->gaji + ($d->kenaikan_gaji ?? 0) + $d->uang_makan_jumlah
                        + $d->overtime_fjg + $d->overtime_cus + $d->medical
                        + $d->thr + $d->bonus + $d->insentif + $d->telkomsel + $d->lain;
                $totalB = $d->pot_25_jumlah + $d->pot_telepon + $d->pot_kas
                        + $d->pot_cicilan   + $d->pot_bpjs   + $d->pot_bensin
                        + $d->pot_cuti_jumlah + $d->pot_kompensasi_jumlah + $d->pot_lain;
                $bersih = $totalA - $totalB;

                $logoImg    = $logoB64    ? '<img src="'.$logoB64.'"    height="32"/>' : '';
                $stempelImg = $stempelB64 ? '<img src="'.$stempelB64.'" height="40"/>' : '';

                $htmlChunk .= '
                <table style="width:195.6mm; border:0.7pt solid #000;">
                <tr class="r21">
                <td colspan="2"></td>
                <td colspan="11" class="ac cblue sz11 pt1" style="vertical-align:top;">PT.FRATEKINDO JAYA GEMILANG</td>
                <td colspan="3" rowspan="3" class="ar pt2 pb2" style="vertical-align:top;padding-right:4mm;">'.$logoImg.'</td>
                </tr>
                <tr class="r17">
                <td colspan="2"></td>
                <td colspan="11" class="ac sz11">SOVEREIGN PLAZA</td>
                </tr>
                <tr class="r17">
                <td colspan="2"></td>
                <td colspan="11" class="ac pb1">TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430</td>
                </tr>
                <tr class="r21 cgray">
                <td colspan="16" class="ac sz11 ul bt" style="padding:3mm;">SLIP GAJI KARYAWAN</td>
                </tr>
                <tr class="r21" >
                <td style="width:17mm;" class="pt2">Nama</td>
                <td colspan="15" class="pt2">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->nama))).'</td>
                </tr>
                <tr class="r17">
                <td>Jabatan</td>
                <td colspan="15">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->jabatan->nama))).'</td>
                </tr>
                <tr class="r17">
                <td>Divisi</td>
                <td colspan="15">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->divisi->nama))).'</td>
                </tr>
                <tr class="r12">
                <td colspan="16" class="bb pb1"></td>
                </tr>
                <tr class="r21">
                <td colspan="8"  class="cblue ul pt2">A. PENGHASILAN :</td>
                <td></td>
                <td colspan="7"  class="cblue ul pt2">B. POTONGAN :</td>
                </tr>
                <tr class="r17">
                <td colspan="6">Gaji Pokok Per : '.$periodeAwal.' - '.$periodeAkhir.'</td>
                <td class="ac">=</td>
                <td class="ar">'.$this->n($d->gaji).'</td>
                <td></td>
                <td style="width:46mm;">Keterlambatan Kehadiran 25%</td>
                <td style="width:12mm;">'.$ket25K.'</td>
                <td style="width:3mm;">'.$ket25L.'</td>
                <td style="width:5mm;" class="ac">'.$ket25M.'</td>
                <td style="width:5mm;" class="ac">'.$ket25N.'</td>
                <td style="width:5mm;" class="ac">=</td>
                <td class="ar">'.($d->pot_25_jumlah > 0 ? $this->n($d->pot_25_jumlah) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">Kenaikan Gaji</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->kenaikan_gaji > 0 ? $this->n($d->kenaikan_gaji) : '').'</td>
                <td></td>
                <td>Pemakaian Telepon/Telkomsel</td>
                <td colspan="4" class="ar">'.$ketTP.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_telepon > 0 ? $this->n($d->pot_telepon) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="2">'.$uMakanLabel.'</td>
                <td style="width:13mm;">'.$makanC.'</td>
                <td style="width:3mm;" class="ac">'.$makanD.'</td>
                <td style="width:8mm;" class="ac">'.$makanE.'</td>
                <td style="width:5mm;">'.$makanF.'</td>
                <td style="width:5mm;" class="ac">=</td>
                <td style="width:19mm;" class="ar">'.$this->n($d->uang_makan_jumlah).'</td>
                <td style="width:6mm;"></td>
                <td>Pinjaman Kas</td>
                <td colspan="4" class="ar">'.$ketKS.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_kas > 0 ? $this->n($d->pot_kas) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="8" class="sz8">'.$uMakanSub.'</td>
                <td></td>
                <td>Pinjaman / Cicilan</td>
                <td colspan="4" class="ar">'.$ketCC.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_cicilan > 0 ? $this->n($d->pot_cicilan) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">Lembur/Overtime</td>
                <td class="ac">=</td>
                <td class="ar">'.( ($d->overtime_fjg+$d->overtime_cus) > 0 ? $this->n($d->overtime_fjg+$d->overtime_cus) : '').'</td>
                <td></td>
                <td>BPJS Kesehatan</td>
                <td colspan="4" class="ar">'.$ketBP.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_bpjs > 0 ? $this->n($d->pot_bpjs) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">Reimbursement Medical</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->medical > 0 ? $this->n($d->medical) : '').'</td>
                <td></td>
                <td>Pemakaian Bensin</td>
                <td colspan="4" class="ar">'.$ketBN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_bensin > 0 ? $this->n($d->pot_bensin) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">Tunjangan Hari Raya</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->thr > 0 ? $this->n($d->thr) : '').'</td>
                <td></td>
                <td>Unpaid Leave / Cuti Bersama</td>
                <td colspan="3" class="ar" class="ar">'.$cutiM.'</td>
                <td class="ac">'.$cutiN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_cuti_jumlah > 0 ? $this->n($d->pot_cuti_jumlah) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">'.$bonusLabel.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($bonusVal > 0 ? $this->n($bonusVal) : '').'</td>
                <td></td>
                <td>'.$kompLabel.'</td>
                <td colspan="4" class="ar">'.$kompN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_kompensasi_jumlah > 0 ? $this->n($d->pot_kompensasi_jumlah) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6">'.$lainLabel.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($lainVal > 0 ? $this->n($lainVal) : '').'</td>
                <td></td>
                <td>Lain-lain</td>
                <td colspan="4" class="ar">'.$ketLL.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_lain > 0 ? $this->n($d->pot_lain) : '').'</td>
                </tr>
                <tr class="r17">
                <td colspan="6" class="ar">Total A</td>
                <td class="ac">=</td>
                <td class="ar bt">'.$this->n($totalA).'</td>
                <td></td>
                <td colspan="5" class="ar">Total B</td>
                <td class="ac">=</td>
                <td class="ar bt">'.$this->n($totalB).'</td>
                </tr>
                <tr class="r17">
                <td colspan="16" class="bb pt2 pb2"></td>
                </tr>
                <tr class="r17 cgray bb">
                <td colspan="7" class="ar bb">PENERIMAAN BERSIH (A-B)</td>
                <td class="bb"></td>
                <td class="ac cred bb">Rp</td>
                <td colspan="4" class="ac cred bb">'.$this->n($bersih).'</td>
                <td colspan="3" class="bb"></td>
                </tr>
                <tr>
                <td colspan="10"></td>
                <td colspan="5" class="ac">Head of HR Dept.</td>
                <td></td>
                </tr>
                <tr>
                <td colspan="10"></td>
                <td colspan="5">'.$stempelImg.'</td>
                <td></td>
                </tr>
                <tr>
                <td colspan="10"></td>
                <td colspan="5" class="ac">Sri Erni.S</td>
                <td></td>
                </tr>
                </table>';
            }
            $mpdf->WriteHTML($htmlChunk);
        }

        $jenisStr = ($jenis == '1' ? 'ENGINEERING_' : ($jenis == '2' ? 'STAF_' : ($jenis == '3' ? 'NON_STAF_' : '')));
        $namaFile = 'PDF_SLIP_GAJI_'.$jenisStr.str_replace(' ', '_', $namaArea).strtoupper($arrBulan[$bulanInt]).'_'.substr($tahun, -2).'.pdf';

        $mpdf->Output($namaFile, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    public function perKaryawan($karyawan_id, $tahun, $bulans)
    {
        $bulansList = explode('-', $bulans);
        $arrBulan   = [
            'Desember','Januari','Februari','Maret','April','Mei',
            'Juni','Juli','Agustus','September','Oktober','November','Desember'
        ];

        $karyawan = $this->repoKaryawan->findById($karyawan_id);
        $password = '';
        if (!empty($karyawan->tanggal_lahir)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $karyawan->tanggal_lahir);
            if ($dt) $password = $dt->format('dmY');  // DDMMYYYY
        }

        $dataDetails = $this->repo->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $dataPhk     = $this->repoPhk->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $dataDetails = $dataDetails->merge($dataPhk)->sortBy('bulan');

        $dataPotongans = $this->rpPotongan->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $potongans = [];
        foreach ($dataPotongans as $pot) {
            if ($pot->keterangan != '') {
                $potongans[$pot->bulan][$pot->jenis][] = $pot->keterangan;
            }
        }

        $filteredDetails = [];
        foreach ($dataDetails as $d) {
            if (in_array((string)$d->bulan, $bulansList)) {
                $filteredDetails[] = $d;
            }
        }

        $namaKaryawan = count($filteredDetails) > 0
            ? $filteredDetails[0]->karyawan->nama
            : 'Karyawan';

        $logoB64    = $this->imgB64(public_path('images/logo_fjg.jpg'),    'jpeg');
        $stempelB64 = $this->imgB64(public_path('images/stempel_full.png'), 'png');

        $css = '<style>
        body  { font-family: dejavusans; font-size: 6.85pt; font-weight: bold; letter-spacing: -0.25pt; margin:0; padding:0; }
        table { border-collapse: collapse; }
        td    { padding: 1pt 1.5pt; vertical-align: middle; line-height: 1.2; }
        .pts { padding-top: 0.5mm; }
        .pt1 { padding-top: 1mm; }
        .pt2 { padding-top: 2mm; }
        .pbs { padding-bottom: 0.5mm; }
        .pb1 { padding-bottom: 1mm; }
        .pb2 { padding-bottom: 2mm; }
        .r21  { height: 6.0mm; }
        .r17  { height: 5.0mm; }
        .r12  { height: 3.5mm; }
        .cgray { background-color: #D9D9D9; }
        .cblue { color: #0070C0; }
        .cred  { color: #FF0000; }
        .ac { text-align: center; }
        .ar { text-align: right;  }
        .ul   { text-decoration: underline; }
        .sz12 { font-size: 9pt; }
        .sz11 { font-size: 8pt; }
        .sz8  { font-size:  6pt; }
        .bb { border-bottom: 0.5pt solid #000; }
        .bt { border-top:    0.5pt solid #000; }
        </style>';
        /* ── Generate PDF ─────────────────────────────────────── */
        $mpdf = new Mpdf([
            'format'            => 'A4',
            'margin_top'        => 12,
            'margin_bottom'     => 12,
            'margin_left'       => 6,
            'margin_right'      => 6,
            'default_font'      => 'dejavusans',
            'default_font_size' => 8.5,
        ]);
        
        /* Password protection — 128-bit RC4
         * user_password  : tanggal lahir DDMMYYYY  (wajib untuk membuka)
         * owner_password : random kuat (tidak diketahui siapapun)       */
        if ($password !== '') {
            $ownerPass = bin2hex(random_bytes(16));
            $mpdf->SetProtection(['print', 'print-highres'], $password, $ownerPass, 128);
        }
        
        $mpdf->SetTitle('Slip Gaji - ' . $namaKaryawan);
        $mpdf->WriteHTML($css);

        /* ── Bangun HTML per pasang (2 slip per halaman) ─────── */
        $chunks  = array_chunk($filteredDetails, 2);

        foreach ($chunks as $chunkIdx => $pair) {
            $htmlChunk = '';
            // Page break setiap ganti pasangan (kecuali yang pertama)
            if ($chunkIdx > 0) {
                $htmlChunk .= '<pagebreak />';
            }

            foreach ($pair as $slipIdx => $d) {

                // Gap antara slip pertama dan kedua di halaman yang sama
                if ($slipIdx > 0) {
                    $htmlChunk .= '<div style="height:13mm;"></div>';
                }

                $bulanInt = (int)$d->bulan;

                /* ── Periode ──────────────────────────────────── */
                $tahunAwal    = ($bulanInt == 1) ? $tahun - 1 : $tahun;
                $periodeAwal  = '26 ' . $arrBulan[$bulanInt - 1] . "'" . substr($tahunAwal, -2);
                $periodeAkhir = '25 ' . $arrBulan[$bulanInt]     . "'" . substr($tahun, -2);

                /* ── Keterangan potongan ──────────────────────── */
                $ketTP = isset($potongans[$bulanInt]['TP']) ? '(' . implode(', ', $potongans[$bulanInt]['TP']) . ')' : '';
                $ketKS = isset($potongans[$bulanInt]['KS']) ? '(' . implode(', ', $potongans[$bulanInt]['KS']) . ')' : '';
                $ketCC = isset($potongans[$bulanInt]['CC']) ? '(' . implode(', ', $potongans[$bulanInt]['CC']) . ')' : '';
                $ketBP = isset($potongans[$bulanInt]['BP']) ? '(' . implode(', ', $potongans[$bulanInt]['BP']) . ')' : '';
                $ketBN = isset($potongans[$bulanInt]['BN']) ? '(' . implode(', ', $potongans[$bulanInt]['BN']) . ')' : '';
                $ketKJ = isset($potongans[$bulanInt]['KJ']) ? implode(', ', $potongans[$bulanInt]['KJ'])              : '';
                $ketLL = isset($potongans[$bulanInt]['LL']) ? '(' . implode(', ', $potongans[$bulanInt]['LL']) . ')' : '';

                /* ── Uang makan ───────────────────────────────── */
                if ($d->makan_harian == 'Y') {
                    $uMakanLabel = 'U/makan &amp; Transport';
                    $ttgl = explode('-', $d->tanggal_awal);
                    $ttgm = explode('-', $d->tanggal_akhir);
                    $uMakanSub = '(Per: '.$ttgl[2].' '.$arrBulan[(int)$ttgl[1]]."'".substr($ttgl[0],-2)
                               .' s/d '.$ttgm[2].' '.$arrBulan[(int)$ttgm[1]]."'".substr($ttgm[0],-2).')';
                    $makanC = $this->n($d->uang_makan_harian);
                    $makanD = 'x'; $makanE = $d->hari_makan; $makanF = 'HR';
                } else {
                    $uMakanLabel = 'Tunjangan U/makan &amp; Transportasi';
                    $uMakanSub   = 'Bulan '.$arrBulan[$bulanInt].' '.$tahun;
                    $makanC = $makanD = $makanE = $makanF = '';
                }

                /* ── Keterlambatan ────────────────────────────── */
                $ket25K = ($d->pot_25_hari > 0) ? $this->n($d->uang_makan_harian / 4) : '';
                $ket25L = ($d->pot_25_hari > 0) ? 'x'              : '';
                $ket25M = ($d->pot_25_hari > 0) ? $d->pot_25_hari  : '';
                $ket25N = ($d->pot_25_hari > 0) ? 'HR'             : '';

                /* ── Bonus / Insentif ─────────────────────────── */
                if ($d->bonus > 0 && $d->insentif > 0) {
                    $bonusLabel = 'Bonus &amp; Insentif'; $bonusVal = $d->bonus + $d->insentif;
                } elseif ($d->bonus > 0) {
                    $bonusLabel = 'Bonus';    $bonusVal = $d->bonus;
                } elseif ($d->insentif > 0) {
                    $bonusLabel = 'Insentif'; $bonusVal = $d->insentif;
                } else {
                    $bonusLabel = 'Bonus';    $bonusVal = 0;
                }

                /* ── Telkomsel / Lain ─────────────────────────── */
                if ($d->telkomsel > 0 && $d->lain > 0) {
                    $lainLabel = 'Telkomsel &amp; Lain-lain'; $lainVal = $d->telkomsel + $d->lain;
                } elseif ($d->telkomsel > 0) {
                    $lainLabel = 'Telkomsel'; $lainVal = $d->telkomsel;
                } elseif ($d->lain > 0) {
                    $lainLabel = 'Lain-lain'; $lainVal = $d->lain;
                } else {
                    $lainLabel = 'Lain-lain'; $lainVal = 0;
                }

                /* ── Kompensasi & Cuti ────────────────────────── */
                $kompLabel = $ketKJ != '' ? 'Kompensasi ('.$ketKJ.')' : 'Kompensasi Ijin';
                $kompN     = ($d->pot_kompensasi_jam > 0) ? $d->pot_kompensasi_jam.' Jam' : '';
                $cutiM     = ($d->pot_cuti_hari > 0) ? $d->pot_cuti_hari : '';
                $cutiN     = ($d->pot_cuti_hari > 0) ? 'HR' : '';

                /* ── Totals ───────────────────────────────────── */
                $totalA = $d->gaji + ($d->kenaikan_gaji ?? 0) + $d->uang_makan_jumlah
                        + $d->overtime_fjg + $d->overtime_cus + $d->medical
                        + $d->thr + $d->bonus + $d->insentif + $d->telkomsel + $d->lain;
                $totalB = $d->pot_25_jumlah + $d->pot_telepon + $d->pot_kas
                        + $d->pot_cicilan   + $d->pot_bpjs   + $d->pot_bensin
                        + $d->pot_cuti_jumlah + $d->pot_kompensasi_jumlah + $d->pot_lain;
                $bersih = $totalA - $totalB;

                /* ── Images HTML ──────────────────────────────── */
                $logoImg    = $logoB64    ? '<img src="'.$logoB64.'"    height="32"/>' : '';
                $stempelImg = $stempelB64 ? '<img src="'.$stempelB64.'" height="40"/>' : '';
                /* ══════════════════════════════════════════════════
                 *  SLIP TABLE — 16 kolom identik Excel perKaryawan
                 * ══════════════════════════════════════════════════ */
                $htmlChunk .= '
                <table style="width:195.6mm; border:0.7pt solid #000;">

                <tr class="r21">
                <td colspan="2"></td>
                <td colspan="11" class="ac cblue sz11 pt1" style="vertical-align:top;">PT.FRATEKINDO JAYA GEMILANG</td>
                <td colspan="3" rowspan="3" class="ar pt2 pb2" style="vertical-align:top;padding-right:4mm;">'.$logoImg.'</td>
                </tr>

                <tr class="r17">
                <td colspan="2"></td>
                <td colspan="11" class="ac sz11">SOVEREIGN PLAZA</td>
                </tr>

                <tr class="r17">
                <td colspan="2"></td>
                <td colspan="11" class="ac pb1">TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430</td>
                </tr>

                <tr class="r21 cgray">
                <td colspan="16" class="ac sz11 ul bt" style="padding:3mm;">SLIP GAJI KARYAWAN</td>
                </tr>

                <tr class="r21" >
                <td style="width:17mm;" class="pt2">Nama</td>
                <td colspan="15" class="pt2">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->nama))).'</td>
                </tr>

                <tr class="r17">
                <td>Jabatan</td>
                <td colspan="15">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->jabatan->nama))).'</td>
                </tr>

                <tr class="r17">
                <td>Divisi</td>
                <td colspan="15">: '.htmlspecialchars(ucwords(strtolower($d->karyawan->divisi->nama))).'</td>
                </tr>

                <tr class="r12">
                <td colspan="16" class="bb pb1"></td>
                </tr>

                <tr class="r21">
                <td colspan="8"  class="cblue ul pt2">A. PENGHASILAN :</td>
                <td></td>
                <td colspan="7"  class="cblue ul pt2">B. POTONGAN :</td>
                </tr>

                <tr class="r17">
                <td colspan="6">Gaji Pokok Per : '.$periodeAwal.' - '.$periodeAkhir.'</td>
                <td class="ac">=</td>
                <td class="ar">'.$this->n($d->gaji).'</td>
                <td></td>
                <td style="width:46mm;">Keterlambatan Kehadiran 25%</td>
                <td style="width:12mm;">'.$ket25K.'</td>
                <td style="width:3mm;">'.$ket25L.'</td>
                <td style="width:5mm;" class="ac">'.$ket25M.'</td>
                <td style="width:5mm;" class="ac">'.$ket25N.'</td>
                <td style="width:5mm;" class="ac">=</td>
                <td class="ar">'.($d->pot_25_jumlah > 0 ? $this->n($d->pot_25_jumlah) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6">Kenaikan Gaji</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->kenaikan_gaji > 0 ? $this->n($d->kenaikan_gaji) : '').'</td>
                <td></td>
                <td>Pemakaian Telepon/Telkomsel</td>
                <td colspan="4" class="ar">'.$ketTP.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_telepon > 0 ? $this->n($d->pot_telepon) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="2">'.$uMakanLabel.'</td>
                <td style="width:13mm;">'.$makanC.'</td>
                <td style="width:3mm;" class="ac">'.$makanD.'</td>
                <td style="width:8mm;" class="ac">'.$makanE.'</td>
                <td style="width:5mm;">'.$makanF.'</td>
                <td style="width:5mm;" class="ac">=</td>
                <td style="width:19mm;" class="ar">'.$this->n($d->uang_makan_jumlah).'</td>
                <td style="width:6mm;"></td>
                <td>Pinjaman Kas</td>
                <td colspan="4" class="ar">'.$ketKS.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_kas > 0 ? $this->n($d->pot_kas) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="8" class="sz8">'.$uMakanSub.'</td>
                <td></td>
                <td>Pinjaman / Cicilan</td>
                <td colspan="4" class="ar">'.$ketCC.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_cicilan > 0 ? $this->n($d->pot_cicilan) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6">Lembur/Overtime</td>
                <td class="ac">=</td>
                <td class="ar">'.( ($d->overtime_fjg+$d->overtime_cus) > 0 ? $this->n($d->overtime_fjg+$d->overtime_cus) : '').'</td>
                <td></td>
                <td>BPJS Kesehatan</td>
                <td colspan="4" class="ar">'.$ketBP.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_bpjs > 0 ? $this->n($d->pot_bpjs) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6">Reimbursement Medical</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->medical > 0 ? $this->n($d->medical) : '').'</td>
                <td></td>
                <td>Pemakaian Bensin</td>
                <td colspan="4" class="ar">'.$ketBN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_bensin > 0 ? $this->n($d->pot_bensin) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6">Tunjangan Hari Raya</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->thr > 0 ? $this->n($d->thr) : '').'</td>
                <td></td>
                <td>Unpaid Leave / Cuti Bersama</td>
                <td colspan="3" class="ar" class="ar">'.$cutiM.'</td>
                <td class="ac">'.$cutiN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_cuti_jumlah > 0 ? $this->n($d->pot_cuti_jumlah) : '').'</td>
                </tr>

                <!-- ROW 18 · Bonus/Insentif / Kompensasi (r17=5mm) -->
                <tr class="r17">
                <td colspan="6">'.$bonusLabel.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($bonusVal > 0 ? $this->n($bonusVal) : '').'</td>
                <td></td>
                <td>'.$kompLabel.'</td>
                <td colspan="4" class="ar">'.$kompN.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_kompensasi_jumlah > 0 ? $this->n($d->pot_kompensasi_jumlah) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6">'.$lainLabel.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($lainVal > 0 ? $this->n($lainVal) : '').'</td>
                <td></td>
                <td>Lain-lain</td>
                <td colspan="4" class="ar">'.$ketLL.'</td>
                <td class="ac">=</td>
                <td class="ar">'.($d->pot_lain > 0 ? $this->n($d->pot_lain) : '').'</td>
                </tr>

                <tr class="r17">
                <td colspan="6" class="ar">Total A</td>
                <td class="ac">=</td>
                <td class="ar bt">'.$this->n($totalA).'</td>
                <td></td>
                <td colspan="5" class="ar">Total B</td>
                <td class="ac">=</td>
                <td class="ar bt">'.$this->n($totalB).'</td>
                </tr>

                <tr class="r17">
                <td colspan="16" class="bb pt2 pb2"></td>
                </tr>

                <tr class="r17 cgray bb">
                <td colspan="7" class="ar bb">PENERIMAAN BERSIH (A-B)</td>
                <td class="bb"></td>
                <td class="ac cred bb">Rp</td>
                <td colspan="4" class="ac cred bb">'.$this->n($bersih).'</td>
                <td colspan="3" class="bb"></td>
                </tr>

                <tr>
                <td colspan="10"></td>
                <td colspan="5" class="ac">Head of HR Dept.</td>
                <td></td>
                </tr>

                <tr>
                <td colspan="10"></td>
                <td colspan="5">'.$stempelImg.'</td>
                <td></td>
                </tr>

                <tr>
                <td colspan="10"></td>
                <td colspan="5" class="ac">Sri Erni.S</td>
                <td></td>
                </tr>

                </table>';
            } // end foreach pair
            $mpdf->WriteHTML($htmlChunk);
        } // end foreach chunks

        $namaFile = 'PDF_SLIP_GAJI_'.substr($tahun,-2).'_'.str_replace(' ','_',$namaKaryawan).'.pdf';
        $mpdf->Output($namaFile, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    /* ── Helpers ──────────────────────────────────────────────── */

    private function n($val): string
    {
        return number_format((float)$val, 0, ',', '.');
    }

    private function imgB64(string $path, string $mime): ?string
    {
        if (!file_exists($path)) return null;
        return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
