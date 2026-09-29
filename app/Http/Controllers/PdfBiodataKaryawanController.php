<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use Mpdf\Mpdf;

class PdfBiodataKaryawanController extends Controller
{
    protected $repoKaryawan;

    public function __construct(KaryawanRepository $repoKaryawan) {
        $this->repoKaryawan = $repoKaryawan;
    }

    public function rekap($id) {
        $karyawan = $this->repoKaryawan->findById($id);
        if (!$karyawan) return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan']);

        $mpdf = new Mpdf([
            'format'        => 'A4-P',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 12,
        ]);

        $themeColor = '#1F4E78';
        $sectionColor = '#D9E1F2';
        $borderColor = '#CCCCCC';

        $html = "<style>
            body { font-family: sans-serif; font-size: 10pt; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            
            /* Main Header */
            .main-header { 
                background-color: {$themeColor}; 
                color: #FFFFFF; 
                font-size: 16pt; 
                font-weight: bold; 
                text-align: center; 
                padding: 10px; 
                margin-bottom: 20px;
            }

            /* Section Header */
            .section-header { 
                background-color: {$sectionColor}; 
                color: {$themeColor}; 
                font-weight: bold; 
                font-size: 11pt; 
                padding: 8px; 
                border-bottom: 2px solid {$themeColor}; 
                margin-top: 15px;
                margin-bottom: 5px;
            }

            /* Profile Data Table */
            .profile-table td { padding: 4px; vertical-align: top; }
            .profile-label { width: 22%; }
            .profile-colon { width: 2%; text-align: center; }
            .profile-value { width: 26%; }

            /* Grid Tables (Keluarga, Training, etc) */
            .grid-table th, .grid-table td { 
                border: 1px solid {$borderColor}; 
                padding: 6px; 
            }
            .grid-table th { 
                background-color: {$themeColor}; 
                color: #FFFFFF; 
                font-weight: bold; 
                text-align: center; 
            }
            .ac { text-align: center; }
            .al { text-align: left; }
            .italic { font-style: italic; color: #555555; }
            
            .footer-date { font-style: italic; font-size: 9pt; color: #7F7F7F; margin-top: 30px; }
        </style>";

        $html .= "<div class=\"main-header\">BIODATA KARYAWAN</div>";

        // --- 1. DATA PRIBADI ---
        $html .= "<div class=\"section-header\">I. DATA PRIBADI</div>";
        
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
            ['Jumlah Anak', $j_anak ? $j_anak : '0', 'Pendidikan Terakhir', $karyawan->pendidikan ? $karyawan->pendidikan->nama : ''],
            ['No. KTP', $karyawan->nomor_ktp ? $karyawan->nomor_ktp : '', 'Jurusan', $karyawan->pendidikan_jurusan],
            ['No. KK', $karyawan->nomor_kk ? $karyawan->nomor_kk : '', 'Almamater', $karyawan->pendidikan_almamater],
            ['No. Paspor', $karyawan->nomor_paspor ? $karyawan->nomor_paspor : '', 'Email', $karyawan->email],
            ['NPWP', $karyawan->nomor_pwp ? $karyawan->nomor_pwp : '', 'No. Telepon / HP', $karyawan->telepon ? "'".$karyawan->telepon : ''],
        ];

        $html .= "<table class=\"profile-table\">";
        foreach ($fields as $f) {
            $html .= "<tr>";
            $html .= "<td class=\"profile-label\">{$f[0]}</td><td class=\"profile-colon\">:</td><td class=\"profile-value\">{$f[1]}</td>";
            if (isset($f[2])) {
                $html .= "<td class=\"profile-label\">{$f[2]}</td><td class=\"profile-colon\">:</td><td class=\"profile-value\">{$f[3]}</td>";
            }
            $html .= "</tr>";
        }
        $html .= "<tr><td class=\"profile-label\">Alamat KTP</td><td class=\"profile-colon\">:</td><td colspan=\"4\">".trim(preg_replace('/\s+/', ' ', (string)$karyawan->alamat_ktp))."</td></tr>";
        $html .= "<tr><td class=\"profile-label\">Alamat Tinggal</td><td class=\"profile-colon\">:</td><td colspan=\"4\">".trim(preg_replace('/\s+/', ' ', (string)$karyawan->alamat_tinggal))."</td></tr>";
        $html .= "</table>";


        // --- 2. ANGGOTA KELUARGA ---
        $html .= "<div class=\"section-header\">II. ANGGOTA KELUARGA</div>";
        $mapHubungan = ['S' => 'Suami', 'I' => 'Istri', 'A' => 'Anak', 'M' => 'Menantu', 'C' => 'Cucu', 'O' => 'Orang Tua', 'T' => 'Mertua', 'F' => 'Famili Lain'];
        
        $keluargas = method_exists($karyawan, 'keluargas') ? $karyawan->keluargas()->get() : [];
        if (count($keluargas) > 0) {
            $html .= "<table class=\"grid-table\">";
            $html .= "<tr><th style=\"width:30%;\">Nama Lengkap</th><th style=\"width:20%;\">Nomor KTP</th><th style=\"width:15%;\">Hubungan</th><th style=\"width:35%;\">Tempat & Tanggal Lahir</th></tr>";
            foreach ($keluargas as $kel) {
                $hubLabel = isset($mapHubungan[$kel->hubungan]) ? $mapHubungan[$kel->hubungan] : $kel->hubungan;
                $ttl = $kel->tempat_lahir . ', ' . ($kel->tanggal_lahir ? date('d-m-Y', strtotime($kel->tanggal_lahir)) : '');
                $ktp = $kel->nomor_ktp ? $kel->nomor_ktp : '';
                $html .= "<tr><td class=\"al\">{$kel->nama}</td><td class=\"ac\">{$ktp}</td><td class=\"ac\">{$hubLabel}</td><td class=\"al\">{$ttl}</td></tr>";
            }
            $html .= "</table>";
        } else {
            $html .= "<div class=\"italic\">- Tidak ada data anggota keluarga -</div>";
        }


        // --- 3. KONTAK DARURAT ---
        $html .= "<div class=\"section-header\">III. KONTAK DARURAT KELUARGA</div>";
        $kontaks = method_exists($karyawan, 'keluargaKontaks') ? $karyawan->keluargaKontaks()->get() : [];
        if (count($kontaks) > 0) {
            $html .= "<table class=\"grid-table\">";
            $html .= "<tr><th style=\"width:30%;\">No. Telepon</th><th style=\"width:70%;\">Keterangan</th></tr>";
            foreach ($kontaks as $kon) {
                $telp = $kon->telepon ? $kon->telepon : '';
                $html .= "<tr><td class=\"ac\">{$telp}</td><td class=\"al\">{$kon->nama}</td></tr>";
            }
            $html .= "</table>";
        } else {
            $html .= "<div class=\"italic\">- Tidak ada data kontak darurat -</div>";
        }


        // --- 4. RIWAYAT TRAINING ---
        $html .= "<div class=\"section-header\">IV. RIWAYAT TRAINING</div>";
        $trainings = method_exists($karyawan, 'trainingKaryawans') ? $karyawan->trainingKaryawans()->get() : [];
        if (count($trainings) > 0) {
            $html .= "<table class=\"grid-table\">";
            $html .= "<tr><th style=\"width:40%;\">Nama Training</th><th style=\"width:20%;\">Tanggal</th><th style=\"width:40%;\">Keterangan</th></tr>";
            foreach ($trainings as $tr) {
                $trainingName = $tr->training ? $tr->training->nama : '';
                $tgl = $tr->tanggal ? date('d-m-Y', strtotime($tr->tanggal)) : '';
                $html .= "<tr><td class=\"al\">{$trainingName}</td><td class=\"ac\">{$tgl}</td><td class=\"al\">{$tr->keterangan}</td></tr>";
            }
            $html .= "</table>";
        } else {
            $html .= "<div class=\"italic\">- Tidak ada riwayat training -</div>";
        }


        // --- 5. PERJANJIAN KERJA ---
        $html .= "<div class=\"section-header\">V. PERJANJIAN KERJA</div>";
        $perjanjians = method_exists($karyawan, 'perjanjianKerjas') ? $karyawan->perjanjianKerjas()->orderBy('tanggal_awal')->get() : [];
        if (count($perjanjians) > 0) {
            $html .= "<table class=\"grid-table\">";
            $html .= "<tr><th style=\"width:30%;\">Nomor Kontrak</th><th style=\"width:30%;\">Status Kerja</th><th style=\"width:20%;\">Mulai</th><th style=\"width:20%;\">Berakhir</th></tr>";
            foreach ($perjanjians as $pj) {
                $statusKerja = $pj->statusKerja ? $pj->statusKerja->nama : '';
                $tglAwal = $pj->tanggal_awal ? date('d-m-Y', strtotime($pj->tanggal_awal)) : '';
                $tglAkhir = $pj->tanggal_akhir ? date('d-m-Y', strtotime($pj->tanggal_akhir)) : '';
                $html .= "<tr><td class=\"ac\">{$pj->nomor}</td><td class=\"al\">{$statusKerja}</td><td class=\"ac\">{$tglAwal}</td><td class=\"ac\">{$tglAkhir}</td></tr>";
            }
            $html .= "</table>";
        } else {
            $html .= "<div class=\"italic\">- Tidak ada data perjanjian kerja -</div>";
        }


        $html .= "<div class=\"footer-date\">Tanggal cetak : " . date('d-m-Y') . "</div>";

        $mpdf->WriteHTML($html);
        $mpdf->Output('BIODATA_' . str_replace(' ', '_', strtoupper($karyawan->nama)) . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}