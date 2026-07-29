<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\PotonganRepository;
use Mpdf\Mpdf;

class PdfSlipGajiController extends Controller
{
    protected $repoKaryawan, $repo, $repoPhk, $rpPotongan;

    public function __construct(
        KaryawanRepository $repoKaryawan,
        PayrollRepository $repo,
        PayrollPhkRepository $repoPhk,
        PotonganRepository $rpPotongan
    ) {
        $this->repoKaryawan = $repoKaryawan;
        $this->repo         = $repo;
        $this->repoPhk      = $repoPhk;
        $this->rpPotongan   = $rpPotongan;
    }

    public function perKaryawan($karyawan_id, $tahun, $bulans)
    {
        $bulansList = explode('-', $bulans);
        $arrBulan   = [
            'Desember','Januari','Februari','Maret','April','Mei',
            'Juni','Juli','Agustus','September','Oktober','November','Desember'
        ];

        /* ── Karyawan & password ────────────────────────────────────────── */
        $karyawan = $this->repoKaryawan->findById($karyawan_id);
        $password = '';
        if (!empty($karyawan->tanggal_lahir)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $karyawan->tanggal_lahir);
            if ($dt) $password = $dt->format('dmY');   // DDMMYYYY
        }

        /* ── Data payroll ───────────────────────────────────────────────── */
        $dataDetails = $this->repo->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $dataPhk     = $this->repoPhk->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $dataDetails = $dataDetails->merge($dataPhk)->sortBy('bulan');

        /* ── Potongan keterangan ────────────────────────────────────────── */
        $dataPotongans = $this->rpPotongan->findAll(['tahun' => $tahun, 'karyawan_id' => $karyawan_id]);
        $potongans = [];
        foreach ($dataPotongans as $pot) {
            if ($pot->keterangan != '') {
                $potongans[$pot->bulan][$pot->jenis][] = $pot->keterangan;
            }
        }

        /* ── Filter bulan yang dipilih ──────────────────────────────────── */
        $filteredDetails = [];
        foreach ($dataDetails as $d) {
            if (in_array((string)$d->bulan, $bulansList)) {
                $filteredDetails[] = $d;
            }
        }

        $namaKaryawan = count($filteredDetails) > 0
            ? $filteredDetails[0]->karyawan->nama
            : 'Karyawan';

        /* ── Images ─────────────────────────────────────────────────────── */
        $logoB64    = $this->imgB64(public_path('images/logo_fjg.jpg'),      'jpeg');
        $stempelB64 = $this->imgB64(public_path('images/stempel_fjg.png'),   'png');
        $ttdB64     = $this->imgB64(public_path('images/stempel_ttd.png'),   'png');

        /* ── Build HTML ─────────────────────────────────────────────────── */
        /*
         * Excel column widths (px = 10.7/64, total=770 px):
         * A=60  B=63  C=57  D=13  E=23  F=27  G=21  H=83
         * I=19  J=172 K=52  L=18  M=18  N=16  O=25  P=103
         *
         * Mapped to A4 usable width 186mm (12mm margin each side):
         * A=14.5 B=15.2 C=13.8 D=3.1 E=5.6 F=6.5 G=5.1 H=20.0
         * I=4.6  J=41.6 K=12.6 L=4.3 M=4.3 N=3.9 O=6.0 P=24.9  Total=186mm
         *
         * Excel row heights (all in pt; 1pt ≈ 0.353mm):
         * H21 = 7.41mm   H17 = 6.00mm   H12 = 4.24mm
         */
        $css = '<style>
body  { font-family: dejavusans; font-size: 9pt; font-weight: bold; margin:0; padding:0; }
table { border-collapse: collapse; }
td    { padding: 0.3pt 1.5pt; vertical-align: middle; line-height: 1.1; }

/* heights (Excel pt → mm) */
.r21  { height: 7.41mm; }
.r17  { height: 6.00mm; }
.r12  { height: 4.24mm; }

/* colours / fills */
.cgray  { background-color: #D9D9D9; }
.cblue  { color: #0070C0; }
.cred   { color: #FF0000; }

/* alignment */
.ac { text-align: center; }
.ar { text-align: right; }
.al { text-align: left;  }

/* typography */
.ul   { text-decoration: underline; }
.sz12 { font-size: 12pt; }
.sz11 { font-size: 11pt; }
.sz8  { font-size:  8pt; }

/* borders */
.bb   { border-bottom: 0.5pt solid #000; }
.bt   { border-top:    0.5pt solid #000; }
</style>';

        $htmlAll = $css;

        foreach ($filteredDetails as $idx => $d) {
            $bulanInt = (int)$d->bulan;

            /* periode */
            $tahunAwal   = ($bulanInt == 1) ? $tahun - 1 : $tahun;
            $periodeAwal  = '26 ' . $arrBulan[$bulanInt - 1] . ' ' . $tahunAwal;
            $periodeAkhir = '25 ' . $arrBulan[$bulanInt]     . ' ' . $tahun;

            /* potongan keterangan */
            $ketTP = isset($potongans[$bulanInt]['TP']) ? '(' . implode(', ', $potongans[$bulanInt]['TP']) . ')' : '';
            $ketKS = isset($potongans[$bulanInt]['KS']) ? '(' . implode(', ', $potongans[$bulanInt]['KS']) . ')' : '';
            $ketCC = isset($potongans[$bulanInt]['CC']) ? '(' . implode(', ', $potongans[$bulanInt]['CC']) . ')' : '';
            $ketBP = isset($potongans[$bulanInt]['BP']) ? '(' . implode(', ', $potongans[$bulanInt]['BP']) . ')' : '';
            $ketBN = isset($potongans[$bulanInt]['BN']) ? '(' . implode(', ', $potongans[$bulanInt]['BN']) . ')' : '';
            $ketKJ = isset($potongans[$bulanInt]['KJ']) ? implode(', ', $potongans[$bulanInt]['KJ'])              : '';
            $ketLL = isset($potongans[$bulanInt]['LL']) ? '(' . implode(', ', $potongans[$bulanInt]['LL']) . ')' : '';

            /* uang makan */
            if ($d->makan_harian == 'Y') {
                $uMakanLabel = 'U/makan &amp; Transport';
                $ttgl = explode('-', $d->tanggal_awal);
                $ttgm = explode('-', $d->tanggal_akhir);
                $uMakanSub = '(Per: ' . $ttgl[2] . ' ' . $arrBulan[(int)$ttgl[1]] . "'" . substr($ttgl[0], -2)
                           . ' s/d ' . $ttgm[2] . ' ' . $arrBulan[(int)$ttgm[1]] . "'" . substr($ttgm[0], -2) . ')';
                $makanC = $this->n($d->uang_makan_harian);
                $makanD = 'x';
                $makanE = $d->hari_makan;
                $makanF = 'HR';
            } else {
                $uMakanLabel = 'Tunjangan U/makan &amp; Transportasi';
                $uMakanSub   = 'Bulan ' . $arrBulan[$bulanInt] . ' ' . $tahun;
                $makanC = $makanD = $makanE = $makanF = '';
            }

            /* keterlambatan */
            $ket25K = ($d->pot_25_hari > 0) ? $this->n($d->uang_makan_harian / 4) : '';
            $ket25L = ($d->pot_25_hari > 0) ? 'x'               : '';
            $ket25M = ($d->pot_25_hari > 0) ? $d->pot_25_hari   : '';
            $ket25N = ($d->pot_25_hari > 0) ? 'HR'              : '';

            /* bonus / insentif */
            if ($d->bonus > 0 && $d->insentif > 0) {
                $bonusLabel = 'Bonus &amp; Insentif'; $bonusVal = $d->bonus + $d->insentif;
            } elseif ($d->bonus > 0) {
                $bonusLabel = 'Bonus';    $bonusVal = $d->bonus;
            } elseif ($d->insentif > 0) {
                $bonusLabel = 'Insentif'; $bonusVal = $d->insentif;
            } else {
                $bonusLabel = 'Bonus';    $bonusVal = 0;
            }

            /* telkomsel / lain */
            if ($d->telkomsel > 0 && $d->lain > 0) {
                $lainLabel = 'Telkomsel &amp; Lain-lain'; $lainVal = $d->telkomsel + $d->lain;
            } elseif ($d->telkomsel > 0) {
                $lainLabel = 'Telkomsel'; $lainVal = $d->telkomsel;
            } elseif ($d->lain > 0) {
                $lainLabel = 'Lain-lain'; $lainVal = $d->lain;
            } else {
                $lainLabel = 'Lain-lain'; $lainVal = 0;
            }

            /* kompensasi */
            $kompLabel = $ketKJ != '' ? 'Kompensasi (' . $ketKJ . ')' : 'Kompensasi Ijin';
            $kompN     = ($d->pot_kompensasi_jam > 0) ? $d->pot_kompensasi_jam . ' Jam' : '';

            /* cuti */
            $cutiM = ($d->pot_cuti_hari > 0) ? $d->pot_cuti_hari : '';
            $cutiN = ($d->pot_cuti_hari > 0) ? 'HR' : '';

            /* totals */
            $totalA = $d->gaji
                    + ($d->kenaikan_gaji ?? 0)
                    + $d->uang_makan_jumlah
                    + $d->overtime_fjg + $d->overtime_cus
                    + $d->medical + $d->thr
                    + $d->bonus + $d->insentif
                    + $d->telkomsel + $d->lain;
            $totalB = $d->pot_25_jumlah + $d->pot_telepon + $d->pot_kas
                    + $d->pot_cicilan  + $d->pot_bpjs    + $d->pot_bensin
                    + $d->pot_cuti_jumlah + $d->pot_kompensasi_jumlah + $d->pot_lain;
            $bersih = $totalA - $totalB;

            /* page break between slips */
            if ($idx > 0) $htmlAll .= '<pagebreak />';

            /* ── Slip HTML ───────────────────────────────────────────────
             * Single table, 16 columns (A–P), identical column proportions
             * to Excel perKaryawan (px = 10.7/64 mapping → 186mm total).
             * One td = one Excel cell; colspan used for merged cells.
             * ──────────────────────────────────────────────────────────── */
            $logoImg    = $logoB64    ? '<img src="' . $logoB64    . '" height="44" />' : '';
            $stempelImg = $stempelB64 ? '<img src="' . $stempelB64 . '" height="55" />' : '';
            $ttdImg     = $ttdB64     ? '<img src="' . $ttdB64     . '" height="65" />' : '';

            $htmlAll .= '
<table style="width:186mm; border:0.5pt solid #000;">
<colgroup>
  <col style="width:14.5mm"/><!-- A -->
  <col style="width:15.2mm"/><!-- B -->
  <col style="width:13.8mm"/><!-- C -->
  <col style="width:3.1mm" /><!-- D -->
  <col style="width:5.6mm" /><!-- E -->
  <col style="width:6.5mm" /><!-- F -->
  <col style="width:5.1mm" /><!-- G -->
  <col style="width:20.0mm"/><!-- H -->
  <col style="width:4.6mm" /><!-- I -->
  <col style="width:41.6mm"/><!-- J -->
  <col style="width:12.6mm"/><!-- K -->
  <col style="width:4.3mm" /><!-- L -->
  <col style="width:4.3mm" /><!-- M -->
  <col style="width:3.9mm" /><!-- N -->
  <col style="width:6.0mm" /><!-- O -->
  <col style="width:24.9mm"/><!-- P -->
</colgroup>

<!-- ROW 1 · Company name + logo  (H=21pt) ─────────────────────────── -->
<tr class="r21">
  <td></td><!-- A -->
  <td colspan="12" class="ac cblue sz12">PT.FRATEKINDO JAYA GEMILANG</td><!-- B–M -->
  <td colspan="2"></td><!-- N–O -->
  <td rowspan="3" class="ar" style="vertical-align:top;padding-top:1mm;">' . $logoImg . '</td><!-- P (rowspan=3) -->
</tr>

<!-- ROW 2 · SOVEREIGN PLAZA  (H=17pt) ──────────────────────────────── -->
<tr class="r17">
  <td></td><!-- A -->
  <td colspan="14" class="ac sz11">SOVEREIGN PLAZA</td><!-- B–O -->
</tr>

<!-- ROW 3 · Address / border-bottom  (H=17pt) ──────────────────────── -->
<tr class="r17">
  <td class="bb"></td><!-- A -->
  <td colspan="14" class="ac bb">TB.Simatupang Kav.36, Cilandak - Jakarta selatan 12430</td><!-- B–O -->
</tr>

<!-- ROW 4 · SLIP GAJI KARYAWAN  (H=21pt, gray) ─────────────────────── -->
<tr class="r21 cgray">
  <td colspan="16" class="ac sz11 ul">SLIP GAJI KARYAWAN</td>
</tr>

<!-- ROW 5 · Periode  (H=17pt, gray) ────────────────────────────────── -->
<tr class="r17 cgray">
  <td colspan="16" class="ac">Periode : ' . $periodeAwal . ' - ' . $periodeAkhir . '</td>
</tr>

<!-- ROW 6 · Nama  (H=21pt) ─────────────────────────────────────────── -->
<tr class="r21">
  <td>Nama</td>
  <td colspan="15">: ' . htmlspecialchars($d->karyawan->nama) . '</td>
</tr>

<!-- ROW 7 · Jabatan  (H=17pt) ──────────────────────────────────────── -->
<tr class="r17">
  <td>Jabatan</td>
  <td colspan="15">: ' . htmlspecialchars($d->karyawan->jabatan->nama) . '</td>
</tr>

<!-- ROW 8 · Divisi  (H=17pt) ───────────────────────────────────────── -->
<tr class="r17">
  <td>Divisi</td>
  <td colspan="15">: ' . htmlspecialchars($d->karyawan->divisi->nama) . '</td>
</tr>

<!-- ROW 9 · Separator  (H=12pt, border-bottom) ─────────────────────── -->
<tr class="r12">
  <td colspan="16" class="bb"></td>
</tr>

<!-- ROW 10 · A.PENGHASILAN / B.POTONGAN headers  (H=21pt) ──────────── -->
<tr class="r21">
  <td colspan="8"  class="cblue ul">A. PENGHASILAN :</td><!-- A–H -->
  <td></td><!-- I -->
  <td colspan="7"  class="cblue ul">B. POTONGAN :</td><!-- J–P -->
</tr>

<!-- ROW 11 · Gaji Pokok / Keterlambatan  (H=17pt) ──────────────────── -->
<tr class="r17">
  <td colspan="6">Gaji Pokok</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . $this->n($d->gaji) . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Keterlambatan Kehadiran 25%</td><!-- J -->
  <td class="ar">' . $ket25K . '</td><!-- K -->
  <td class="ac">' . $ket25L . '</td><!-- L -->
  <td class="ar">' . $ket25M . '</td><!-- M -->
  <td class="ac">' . $ket25N . '</td><!-- N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_25_jumlah > 0 ? $this->n($d->pot_25_jumlah) : '') . '</td><!-- P -->
</tr>

<!-- ROW 12 · Kenaikan Gaji / Telepon  (H=17pt) ─────────────────────── -->
<tr class="r17">
  <td colspan="6">Kenaikan Gaji</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . ($d->kenaikan_gaji > 0 ? $this->n($d->kenaikan_gaji) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Pemakaian Telepon/Telkomsel</td><!-- J -->
  <td>' . $ketTP . '</td><!-- K -->
  <td colspan="3"></td><!-- L–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_telepon > 0 ? $this->n($d->pot_telepon) : '') . '</td><!-- P -->
</tr>

<!-- ROW 13 · Uang Makan / Pinjaman Kas  (H=17pt) ───────────────────── -->
<tr class="r17">
  <td>' . $uMakanLabel . '</td><!-- A -->
  <td></td><!-- B -->
  <td class="ar">' . $makanC . '</td><!-- C -->
  <td class="ac">' . $makanD . '</td><!-- D -->
  <td>' . $makanE . '</td><!-- E -->
  <td>' . $makanF . '</td><!-- F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . $this->n($d->uang_makan_jumlah) . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Pinjaman Kas</td><!-- J -->
  <td>' . $ketKS . '</td><!-- K -->
  <td colspan="3"></td><!-- L–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_kas > 0 ? $this->n($d->pot_kas) : '') . '</td><!-- P -->
</tr>

<!-- ROW 14 · Makan sub-label / Pinjaman Cicilan  (H=17pt) ──────────── -->
<tr class="r17">
  <td colspan="8" class="sz8">' . $uMakanSub . '</td><!-- A–H -->
  <td></td><!-- I -->
  <td>Pinjaman / Cicilan</td><!-- J -->
  <td>' . $ketCC . '</td><!-- K -->
  <td colspan="3"></td><!-- L–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_cicilan > 0 ? $this->n($d->pot_cicilan) : '') . '</td><!-- P -->
</tr>

<!-- ROW 15 · Lembur / BPJS  (H=17pt) ──────────────────────────────── -->
<tr class="r17">
  <td colspan="6">Lembur/Overtime</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . (($d->overtime_fjg + $d->overtime_cus) > 0 ? $this->n($d->overtime_fjg + $d->overtime_cus) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>BPJS Kesehatan</td><!-- J -->
  <td>' . $ketBP . '</td><!-- K -->
  <td colspan="3"></td><!-- L–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_bpjs > 0 ? $this->n($d->pot_bpjs) : '') . '</td><!-- P -->
</tr>

<!-- ROW 16 · Reimbursement Medical / Bensin  (H=17pt) ──────────────── -->
<tr class="r17">
  <td colspan="6">Reimbursement Medical</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . ($d->medical > 0 ? $this->n($d->medical) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Pemakaian Bensin</td><!-- J -->
  <td>' . $ketBN . '</td><!-- K -->
  <td colspan="3"></td><!-- L–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_bensin > 0 ? $this->n($d->pot_bensin) : '') . '</td><!-- P -->
</tr>

<!-- ROW 17 · THR / Cuti  (H=17pt) ─────────────────────────────────── -->
<tr class="r17">
  <td colspan="6">Tunjangan Hari Raya</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . ($d->thr > 0 ? $this->n($d->thr) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Unpaid Leave / Cuti Bersama</td><!-- J -->
  <td></td><!-- K -->
  <td></td><!-- L -->
  <td class="ar">' . $cutiM . '</td><!-- M -->
  <td class="ac">' . $cutiN . '</td><!-- N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_cuti_jumlah > 0 ? $this->n($d->pot_cuti_jumlah) : '') . '</td><!-- P -->
</tr>

<!-- ROW 18 · Bonus/Insentif / Kompensasi  (H=17pt) ─────────────────── -->
<tr class="r17">
  <td colspan="6">' . $bonusLabel . '</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . ($bonusVal > 0 ? $this->n($bonusVal) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>' . $kompLabel . '</td><!-- J -->
  <td colspan="3"></td><!-- K–M -->
  <td class="ar">' . $kompN . '</td><!-- N (right-aligned per Excel) -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_kompensasi_jumlah > 0 ? $this->n($d->pot_kompensasi_jumlah) : '') . '</td><!-- P -->
</tr>

<!-- ROW 19 · Lain-lain / Lain-lain  (H=17pt) ───────────────────────── -->
<tr class="r17">
  <td colspan="6">' . $lainLabel . '</td><!-- A–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar">' . ($lainVal > 0 ? $this->n($lainVal) : '') . '</td><!-- H -->
  <td></td><!-- I -->
  <td>Lain-lain ' . $ketLL . '</td><!-- J -->
  <td colspan="4"></td><!-- K–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar">' . ($d->pot_lain > 0 ? $this->n($d->pot_lain) : '') . '</td><!-- P -->
</tr>

<!-- ROW 20 · Total A / Total B  (H=17pt, border-top on H & P) ──────── -->
<tr class="r17">
  <td colspan="4"></td><!-- A–D -->
  <td colspan="2" class="ar">Total A</td><!-- E–F -->
  <td class="ac">=</td><!-- G -->
  <td class="ar bt">' . $this->n($totalA) . '</td><!-- H -->
  <td></td><!-- I -->
  <td></td><!-- J -->
  <td></td><!-- K -->
  <td>Total B</td><!-- L -->
  <td colspan="2"></td><!-- M–N -->
  <td class="ac">=</td><!-- O -->
  <td class="ar bt">' . $this->n($totalB) . '</td><!-- P -->
</tr>

<!-- ROW 21 · Separator (H=17pt, border-bottom) ─────────────────────── -->
<tr class="r17">
  <td colspan="16" class="bb"></td>
</tr>

<!-- ROW 22 · Penerimaan Bersih (H=17pt, gray, border-bottom) ───────── -->
<tr class="r17 cgray bb">
  <td colspan="7" class="ar bb">PENERIMAAN BERSIH (A-B)</td><!-- A–G -->
  <td class="bb"></td><!-- H -->
  <td class="ac cred bb">Rp</td><!-- I -->
  <td class="ar cred bb">' . $this->n($bersih) . '</td><!-- J -->
  <td colspan="6" class="bb"></td><!-- K–P -->
</tr>

<!-- ROW 23 · Head of HR Dept. (H=17pt) ─────────────────────────────── -->
<tr class="r17">
  <td colspan="10"></td><!-- A–I -->
  <td colspan="5" class="ac">Head of HR Dept.</td><!-- K–O (merged in Excel) -->
  <td></td><!-- P -->
</tr>

<!-- ROW 24–25 · Stempel & TTD images (~20mm) ───────────────────────── -->
<tr style="height:20mm;">
  <td colspan="10"></td><!-- A–I -->
  <td colspan="5" class="ac" style="vertical-align:middle;">' . $ttdImg . '&nbsp;' . $stempelImg . '</td><!-- K–O -->
  <td></td><!-- P -->
</tr>

<!-- ROW 26 · Sri Erni.S (H=17pt) ───────────────────────────────────── -->
<tr class="r17">
  <td colspan="10"></td><!-- A–I -->
  <td colspan="5" class="ac">Sri Erni.S</td><!-- K–O -->
  <td></td><!-- P -->
</tr>

</table>';
        } // end foreach slip

        /* ── Generate PDF ───────────────────────────────────────────────── */
        $mpdf = new Mpdf([
            'format'            => 'A4',
            'margin_top'        => 8,
            'margin_bottom'     => 8,
            'margin_left'       => 12,
            'margin_right'      => 12,
            'default_font'      => 'dejavusans',
            'default_font_size' => 9,
        ]);

        /* Password protection:
         * SetProtection(permissions, user_password, owner_password, length)
         * - user_password  → password WAJIB untuk membuka dokumen (tanggal lahir DDMMYYYY)
         * - owner_password → password owner (random kuat, tidak diketahui siapapun)
         * - length = 128   → RC4 128-bit (lebih kuat dari default 40-bit)
         * Harus dipanggil SEBELUM WriteHTML.                               */
        if ($password !== '') {
            $ownerPass = bin2hex(random_bytes(16)); // 32-char random hex owner password
            $mpdf->SetProtection(['print', 'print-highres'], $password, $ownerPass, 128);
        }

        $mpdf->SetTitle('Slip Gaji - ' . $namaKaryawan);
        $mpdf->WriteHTML($htmlAll);

        $namaFile = 'PDF_SLIP_GAJI_' . substr($tahun, -2) . '_'
                  . str_replace(' ', '_', $namaKaryawan) . '.pdf';

        $mpdf->Output($namaFile, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    /** Format angka → Indonesian (dot as thousands separator) */
    private function n($val): string
    {
        return number_format((float)$val, 0, ',', '.');
    }

    /** Load image file → base64 data-URI */
    private function imgB64(string $path, string $mime): ?string
    {
        if (!file_exists($path)) return null;
        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
