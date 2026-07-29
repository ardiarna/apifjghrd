<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\PayrollPhkRepository;
use App\Repositories\PotonganRepository;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

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

    public function perKaryawan($karyawan_id, $tahun, $bulans) {
        $bulans    = explode('-', $bulans);
        $arrBulan  = ['Desember','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        // Ambil data karyawan untuk password
        $karyawan = $this->repoKaryawan->findById($karyawan_id);
        $tanggalLahir = $karyawan->tanggal_lahir; // format Y-m-d
        $password = '';
        if ($tanggalLahir) {
            $dt = \DateTime::createFromFormat('Y-m-d', $tanggalLahir);
            if ($dt) {
                $password = $dt->format('dmY'); // DDMMYYYY
            }
        }

        // Ambil data payroll
        $dataDetails = $this->repo->findAll([
            'tahun'       => $tahun,
            'karyawan_id' => $karyawan_id,
        ]);
        $dataPhk = $this->repoPhk->findAll([
            'tahun'       => $tahun,
            'karyawan_id' => $karyawan_id,
        ]);
        $dataDetails = $dataDetails->merge($dataPhk)->sortBy('bulan');

        $dataPotongans = $this->rpPotongan->findAll([
            'tahun'       => $tahun,
            'karyawan_id' => $karyawan_id,
        ]);
        $potongans = [];
        foreach ($dataPotongans as $d) {
            if ($d->keterangan != '') {
                $potongans[$d->bulan][$d->jenis][] = $d->keterangan;
            }
        }

        // Filter bulan yang dipilih
        $filteredDetails = [];
        foreach ($dataDetails as $d) {
            if (in_array($d->bulan, $bulans)) {
                $filteredDetails[] = $d;
            }
        }

        // Buat HTML untuk setiap slip
        $namaKaryawan = count($filteredDetails) > 0 ? $filteredDetails[0]->karyawan->nama : 'Karyawan';
        $htmlAll = '';

        foreach ($filteredDetails as $idx => $d) {
            $bulanLabel = $arrBulan[$d->bulan];
            $periodeAwal  = '26 ' . $arrBulan[$d->bulan - 1] . ' ' . ($d->bulan == 1 ? $tahun - 1 : $tahun);
            $periodeAkhir = '25 ' . $bulanLabel . ' ' . $tahun;

            // Kalkulasi total
            $totalA = $d->gaji + ($d->kenaikan_gaji ?? 0) + $d->uang_makan_jumlah
                    + $d->overtime_fjg + $d->overtime_cus
                    + $d->medical + $d->thr + $d->bonus + $d->insentif
                    + $d->telkomsel + $d->lain;
            $totalB = $d->pot_25_jumlah + $d->pot_telepon + $d->pot_kas
                    + $d->pot_cicilan + $d->pot_bpjs + $d->pot_bensin
                    + $d->pot_cuti_jumlah + $d->pot_kompensasi_jumlah + $d->pot_lain;
            $bersih  = $totalA - $totalB;

            // Keterangan potongan
            $ketTP = isset($potongans[$d->bulan]['TP']) ? '(' . implode(', ', $potongans[$d->bulan]['TP']) . ')' : '';
            $ketKS = isset($potongans[$d->bulan]['KS']) ? '(' . implode(', ', $potongans[$d->bulan]['KS']) . ')' : '';
            $ketCC = isset($potongans[$d->bulan]['CC']) ? '(' . implode(', ', $potongans[$d->bulan]['CC']) . ')' : '';
            $ketBP = isset($potongans[$d->bulan]['BP']) ? '(' . implode(', ', $potongans[$d->bulan]['BP']) . ')' : '';
            $ketBN = isset($potongans[$d->bulan]['BN']) ? '(' . implode(', ', $potongans[$d->bulan]['BN']) . ')' : '';
            $ketKJ = isset($potongans[$d->bulan]['KJ']) ? '('. implode(', ', $potongans[$d->bulan]['KJ']) . ')' : '';
            $ketLL = isset($potongans[$d->bulan]['LL']) ? '(' . implode(', ', $potongans[$d->bulan]['LL']) . ')' : '';

            // Uang makan label
            if ($d->makan_harian == 'Y') {
                $uMakanLabel = 'U/makan &amp; Transport';
                $uMakanDetail = $this->fmt($d->uang_makan_harian) . ' x ' . $d->hari_makan . ' HR';
                $ttgl  = explode('-', $d->tanggal_awal);
                $ttgm  = explode('-', $d->tanggal_akhir);
                $uMakanSub = '(Per: ' . $ttgl[2] . ' ' . $arrBulan[intval($ttgl[1])] . "'" . substr($ttgl[0], -2)
                           . ' s/d ' . $ttgm[2] . ' ' . $arrBulan[intval($ttgm[1])] . "'" . substr($ttgm[0], -2) . ')';
            } else {
                $uMakanLabel  = 'Tunjangan U/makan &amp; Transportasi';
                $uMakanDetail = '';
                $uMakanSub    = 'Bulan ' . $bulanLabel . ' ' . $tahun;
            }

            // Kompensasi
            $kompLabel = $ketKJ != '' ? 'Kompensasi ' . $ketKJ : 'Kompensasi Ijin';
            $kompJam   = $d->pot_kompensasi_jam > 0 ? $d->pot_kompensasi_jam . ' Jam' : '';

            // Keterlambatan
            $ketHadir = $d->pot_25_hari > 0 ? $this->fmt($d->uang_makan_harian / 4) . ' x ' . $d->pot_25_hari . ' HR' : '';

            // Bonus/Insentif
            if ($d->bonus > 0 && $d->insentif > 0) {
                $bonusLabel = 'Bonus &amp; Insentif';
                $bonusVal   = $d->bonus + $d->insentif;
            } elseif ($d->bonus > 0) {
                $bonusLabel = 'Bonus';
                $bonusVal   = $d->bonus;
            } elseif ($d->insentif > 0) {
                $bonusLabel = 'Insentif';
                $bonusVal   = $d->insentif;
            } else {
                $bonusLabel = 'Bonus';
                $bonusVal   = 0;
            }

            // Telkomsel/Lain
            if ($d->telkomsel > 0 && $d->lain > 0) {
                $lainLabel = 'Telkomsel &amp; Lain-lain';
                $lainVal   = $d->telkomsel + $d->lain;
            } elseif ($d->telkomsel > 0) {
                $lainLabel = 'Telkomsel';
                $lainVal   = $d->telkomsel;
            } elseif ($d->lain > 0) {
                $lainLabel = 'Lain-lain';
                $lainVal   = $d->lain;
            } else {
                $lainLabel = 'Lain-lain';
                $lainVal   = 0;
            }

            $pageBreak = ($idx > 0) ? '<pagebreak />' : '';

            $logoPath = public_path('images/logo_fjg.jpg');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
            }
            $stempelPath = public_path('images/stempel_fjg.png');
            $stempelBase64 = '';
            if (file_exists($stempelPath)) {
                $stempelBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($stempelPath));
            }
            $ttdPath = public_path('images/stempel_ttd.png');
            $ttdBase64 = '';
            if (file_exists($ttdPath)) {
                $ttdBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($ttdPath));
            }

            $htmlAll .= $pageBreak . '
<div style="font-family: Arial, sans-serif; font-size: 9pt; padding: 0;">
  <!-- HEADER -->
  <table width="100%" style="border-bottom: 1px solid #000; margin-bottom: 4px;">
    <tr>
      <td width="75%">
        <div style="font-size: 13pt; font-weight: bold; color: #0070C0;">PT. FRATEKINDO JAYA GEMILANG</div>
        <div style="font-size: 10pt; font-weight: bold;">SOVEREIGN PLAZA</div>
        <div style="font-size: 9pt;">TB. Simatupang Kav.36, Cilandak - Jakarta Selatan 12430</div>
      </td>
      <td width="25%" align="right">
        ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" height="44" />' : '') . '
      </td>
    </tr>
  </table>

  <!-- JUDUL -->
  <div style="text-align: center; background-color: #D9D9D9; padding: 3px 0; margin-bottom: 3px;">
    <div style="font-size: 11pt; font-weight: bold; text-decoration: underline;">SLIP GAJI KARYAWAN</div>
    <div style="font-size: 9pt;">Periode : ' . $periodeAwal . ' - ' . $periodeAkhir . '</div>
  </div>

  <!-- IDENTITAS -->
  <table width="100%" style="margin-bottom: 4px;">
    <tr>
      <td width="90px"><b>Nama</b></td>
      <td>: ' . htmlspecialchars($d->karyawan->nama) . '</td>
    </tr>
    <tr>
      <td><b>Jabatan</b></td>
      <td>: ' . htmlspecialchars($d->karyawan->jabatan->nama) . '</td>
    </tr>
    <tr>
      <td><b>Divisi</b></td>
      <td>: ' . htmlspecialchars($d->karyawan->divisi->nama) . '</td>
    </tr>
  </table>
  <hr style="border: 0.5px solid #ccc; margin: 3px 0;" />

  <!-- PENGHASILAN & POTONGAN -->
  <table width="100%" cellspacing="0" cellpadding="2">
    <tr>
      <td width="48%" valign="top">
        <table width="100%" cellspacing="0" cellpadding="1">
          <tr><td colspan="3" style="color:#0070C0; font-weight:bold; text-decoration:underline; padding-bottom:3px;">A. PENGHASILAN :</td></tr>
          ' . $this->rowPenghasilan('Gaji Pokok', $d->gaji) . '
          ' . $this->rowPenghasilan('Kenaikan Gaji', $d->kenaikan_gaji ?? 0) . '
          ' . $this->rowPenghasilan($uMakanLabel . ($uMakanDetail ? '<br/><small style="color:#888">' . $uMakanDetail . '</small><br/><small style="color:#888">' . $uMakanSub . '</small>' : '<br/><small style="color:#888">' . $uMakanSub . '</small>'), $d->uang_makan_jumlah) . '
          ' . $this->rowPenghasilan('Lembur/Overtime', $d->overtime_fjg + $d->overtime_cus, true) . '
          ' . $this->rowPenghasilan('Reimbursement Medical', $d->medical, true) . '
          ' . $this->rowPenghasilan('Tunjangan Hari Raya', $d->thr, true) . '
          ' . $this->rowPenghasilan($bonusLabel, $bonusVal, true) . '
          ' . $this->rowPenghasilan($lainLabel, $lainVal, true) . '
          <tr style="border-top: 1px solid #999;">
            <td colspan="2" style="padding-top:2px;"><b>Total A</b></td>
            <td align="right" style="padding-top:2px;"><b>' . $this->fmt($totalA) . '</b></td>
          </tr>
        </table>
      </td>
      <td width="4%"></td>
      <td width="48%" valign="top">
        <table width="100%" cellspacing="0" cellpadding="1">
          <tr><td colspan="3" style="color:#0070C0; font-weight:bold; text-decoration:underline; padding-bottom:3px;">B. POTONGAN :</td></tr>
          ' . $this->rowPotongan('Keterlambatan 25%', $ketHadir, $d->pot_25_jumlah) . '
          ' . $this->rowPotongan('Pemakaian Telepon ' . $ketTP, '', $d->pot_telepon) . '
          ' . $this->rowPotongan('Pinjaman Kas ' . $ketKS, '', $d->pot_kas) . '
          ' . $this->rowPotongan('Pinjaman/Cicilan ' . $ketCC, '', $d->pot_cicilan) . '
          ' . $this->rowPotongan('BPJS Kesehatan ' . $ketBP, '', $d->pot_bpjs) . '
          ' . $this->rowPotongan('Pemakaian Bensin ' . $ketBN, '', $d->pot_bensin) . '
          ' . $this->rowPotongan('Unpaid Leave / Cuti', ($d->pot_cuti_hari > 0 ? $d->pot_cuti_hari . ' HR' : ''), $d->pot_cuti_jumlah) . '
          ' . $this->rowPotongan($kompLabel, $kompJam, $d->pot_kompensasi_jumlah) . '
          ' . $this->rowPotongan('Lain-lain ' . $ketLL, '', $d->pot_lain) . '
          <tr style="border-top: 1px solid #999;">
            <td colspan="2" style="padding-top:2px;"><b>Total B</b></td>
            <td align="right" style="padding-top:2px;"><b>' . $this->fmt($totalB) . '</b></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <hr style="border: 0.5px solid #ccc; margin: 4px 0;" />

  <!-- PENERIMAAN BERSIH -->
  <table width="100%" style="background-color:#D9D9D9; border-top:1px solid #000; border-bottom:1px solid #000; margin-bottom:6px;">
    <tr>
      <td width="70%" style="padding:3px 5px;"><b>PENERIMAAN BERSIH (A - B)</b></td>
      <td align="right" style="padding:3px 5px; color:red; font-weight:bold;">Rp ' . $this->fmt($bersih) . '</td>
    </tr>
  </table>

  <!-- TTD -->
  <table width="100%" style="margin-top: 5px;">
    <tr>
      <td width="50%"></td>
      <td width="50%" align="center">
        <div>Head of HR Dept.</div>
        <div style="position:relative; height:70px;">
          ' . ($stempelBase64 ? '<img src="' . $stempelBase64 . '" height="55" style="position:absolute; left:30px; top:5px;" />' : '') . '
          ' . ($ttdBase64 ? '<img src="' . $ttdBase64 . '" height="65" style="position:absolute; left:-15px; top:3px;" />' : '') . '
        </div>
        <div><b>Sri Erni.S</b></div>
      </td>
    </tr>
  </table>
</div>';
        } // end foreach

        // Generate PDF dengan mPDF
        $mpdf = new Mpdf([
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_left'   => 12,
            'margin_right'  => 12,
            'format'        => 'A4',
        ]);

        // Set proteksi password
        if ($password !== '') {
            $mpdf->SetProtection(['print', 'print-highres', 'copy'], $password, '');
        }

        $mpdf->SetTitle('Slip Gaji - ' . $namaKaryawan);
        $mpdf->WriteHTML($htmlAll);

        $namaFile = 'PDF_SLIP_GAJI_' . substr($tahun, -2) . '_' . str_replace(' ', '_', $namaKaryawan) . '.pdf';

        // Dengan Destination::DOWNLOAD, parameter pertama adalah FILENAME
        // mPDF akan set Content-Type dan Content-Disposition secara otomatis
        $mpdf->Output($namaFile, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    // Helper: format angka
    private function fmt($val) {
        return number_format((float)$val, 0, ',', '.');
    }

    // Helper: baris penghasilan
    private function rowPenghasilan($label, $val, $skipZero = false) {
        if ($skipZero && $val == 0) return '';
        return '<tr>
          <td style="width:55%;">' . $label . '</td>
          <td style="width:5%; text-align:center;">=</td>
          <td style="width:40%; text-align:right;">' . ($val > 0 ? $this->fmt($val) : '') . '</td>
        </tr>';
    }

    // Helper: baris potongan
    private function rowPotongan($label, $detail, $val) {
        return '<tr>
          <td style="width:50%;">' . $label . ($detail ? '<br/><small style="color:#888">' . $detail . '</small>' : '') . '</td>
          <td style="width:5%; text-align:center;">=</td>
          <td style="width:45%; text-align:right;">' . ($val > 0 ? $this->fmt($val) : '') . '</td>
        </tr>';
    }
}
