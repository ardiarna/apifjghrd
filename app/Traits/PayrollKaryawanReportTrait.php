<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Helper\Dimension;
use PhpOffice\PhpSpreadsheet\Style\Border;

trait PayrollKaryawanReportTrait
{
    protected function collectKaryawanPayrollDetails($karyawan_id, array $tahunList): array
    {
        $details = [];
        $dataKaryawan = null;

        foreach ($tahunList as $tahun) {
            $dataDetails = $this->repoDetail->findAll([
                'karyawan_id' => $karyawan_id,
                'tahun' => $tahun,
                'pph21' => 'Y',
            ]);
            $dataPhk = $this->repoPhk->findAll([
                'karyawan_id' => $karyawan_id,
                'tahun' => $tahun,
                'pph21' => 'Y',
            ]);
            $merged = $dataDetails->merge($dataPhk);

            foreach ($merged as $dt) {
                $uangPhk = (isset($dt->phk) && $dt->phk && $dt->phk->uangPhk) ? $dt->phk->uangPhk : null;
                $phkKompensasi = $uangPhk ? (float)$uangPhk->kompensasi : 0;
                $phkPesangon = $uangPhk ? (float)$uangPhk->pesangon : 0;
                $phkMasaKerja = $uangPhk ? (float)$uangPhk->masa_kerja : 0;
                $phkUangPisah = $uangPhk ? (float)$uangPhk->uang_pisah : 0;
                $phkSisaCutiHari = $uangPhk ? (float)$uangPhk->sisa_cuti_hari : 0;
                $phkSisaCutiJumlah = $uangPhk ? (float)$uangPhk->sisa_cuti_jumlah : 0;
                $phkLain = $uangPhk ? (float)$uangPhk->lain : 0;
                $kalkulasiTambah = $phkKompensasi + $phkPesangon + $phkMasaKerja + $phkUangPisah + $phkSisaCutiJumlah;

                if (isset($details[$dt->tahun][$dt->bulan])) {
                    $existing = $details[$dt->tahun][$dt->bulan];
                    $existing->gaji += $dt->gaji;
                    $existing->kenaikan_gaji += $dt->kenaikan_gaji;
                    $existing->hari_makan += $dt->hari_makan;
                    $existing->uang_makan_harian += $dt->uang_makan_harian;
                    $existing->uang_makan_jumlah += $dt->uang_makan_jumlah;
                    $existing->overtime_fjg += $dt->overtime_fjg;
                    $existing->overtime_cus += $dt->overtime_cus;
                    $existing->medical += $dt->medical;
                    $existing->thr += $dt->thr;
                    $existing->bonus += $dt->bonus;
                    $existing->insentif += $dt->insentif;
                    $existing->telkomsel += $dt->telkomsel;
                    $existing->lain += $dt->lain;
                    $existing->pot_25_hari += $dt->pot_25_hari;
                    $existing->pot_25_jumlah += $dt->pot_25_jumlah;
                    $existing->pot_telepon += $dt->pot_telepon;
                    $existing->pot_bensin += $dt->pot_bensin;
                    $existing->pot_kas += $dt->pot_kas;
                    $existing->pot_cicilan += $dt->pot_cicilan;
                    $existing->pot_bpjs += $dt->pot_bpjs;
                    $existing->pot_cuti_hari += $dt->pot_cuti_hari;
                    $existing->pot_cuti_jumlah += $dt->pot_cuti_jumlah;
                    $existing->pot_kompensasi_jam += $dt->pot_kompensasi_jam;
                    $existing->pot_kompensasi_jumlah += $dt->pot_kompensasi_jumlah;
                    $existing->pot_lain += $dt->pot_lain;
                    $existing->total_diterima += ($dt->total_diterima + $kalkulasiTambah);
                    $existing->kantor_jp += $dt->kantor_jp;
                    $existing->kantor_jht += $dt->kantor_jht;
                    $existing->kantor_jkk += $dt->kantor_jkk;
                    $existing->kantor_jkm += $dt->kantor_jkm;
                    $existing->kantor_bpjs += $dt->kantor_bpjs;
                    $existing->penghasilan_bruto += $dt->penghasilan_bruto;
                    $existing->dpp += $dt->dpp;
                    $existing->pph21 += $dt->pph21;

                    $existing->phk_kompensasi += $phkKompensasi;
                    $existing->phk_pesangon += $phkPesangon;
                    $existing->phk_masa_kerja += $phkMasaKerja;
                    $existing->phk_uang_pisah += $phkUangPisah;
                    $existing->phk_sisa_cuti_hari += $phkSisaCutiHari;
                    $existing->phk_sisa_cuti_jumlah += $phkSisaCutiJumlah;
                    $existing->phk_lain += $phkLain;
                } else {
                    $dt->phk_kompensasi = $phkKompensasi;
                    $dt->phk_pesangon = $phkPesangon;
                    $dt->phk_masa_kerja = $phkMasaKerja;
                    $dt->phk_uang_pisah = $phkUangPisah;
                    $dt->phk_sisa_cuti_hari = $phkSisaCutiHari;
                    $dt->phk_sisa_cuti_jumlah = $phkSisaCutiJumlah;
                    $dt->phk_lain = $phkLain;
                    $dt->total_diterima = $dt->total_diterima + $kalkulasiTambah;
                    $details[$dt->tahun][$dt->bulan] = $dt;
                }

                $dataKaryawan = $dt->karyawan;
            }
        }

        return [
            'details' => $details,
            'karyawan' => $dataKaryawan,
        ];
    }

    protected function hasKalkulasiPhkInRows(array $rows): bool
    {
        foreach ($rows as $row) {
            $d = $row['data'] ?? null;
            if (!$d) continue;
            if (
                ($d->phk_kompensasi ?? 0) > 0 ||
                ($d->phk_pesangon ?? 0) > 0 ||
                ($d->phk_masa_kerja ?? 0) > 0 ||
                ($d->phk_uang_pisah ?? 0) > 0 ||
                ($d->phk_sisa_cuti_hari ?? 0) > 0 ||
                ($d->phk_sisa_cuti_jumlah ?? 0) > 0 ||
                ($d->phk_lain ?? 0) > 0
            ) {
                return true;
            }
        }
        return false;
    }

    protected function populateKaryawanPayrollSheet(
        $si,
        array $rows,
        string $judulPeriode,
        $dataKaryawan,
        bool $showBenefit,
        bool $isYearlyMode,
        bool $isPdf = false,
        string $keyTahun = ''
    ): void {
        $kol = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ"];

        $hasKalkulasiPhk = $this->hasKalkulasiPhkInRows($rows);

        $idx = 24; // Kolom Y
        $colPhkStart = $colPhkEnd = '';
        $colKompensasi = $colPesangon = $colMasaKerja = $colUangPisah = $colSisaCutiHari = $colSisaCutiIdr = $colPhkLain = '';
        if ($hasKalkulasiPhk) {
            $colPhkStart = $kol[$idx];
            $colKompensasi = $kol[$idx++];
            $colPesangon = $kol[$idx++];
            $colMasaKerja = $kol[$idx++];
            $colUangPisah = $kol[$idx++];
            $colSisaCutiHari = $kol[$idx++];
            $colSisaCutiIdr = $kol[$idx++];
            $colPhkLain = $kol[$idx++];
            $colPhkEnd = $kol[$idx - 1];
        }

        $colTotalDiterimaIdx = $idx;
        $colTotalDiterima = $kol[$idx++];

        $colBenefitStart = $colBenefitEnd = '';
        $colJp = $colJht = $colJkk = $colJkm = $colBpjsKantor = '';
        $colPphStart = $colPphEnd = '';
        $colBruto = $colDpp = $colTer = $colPph21 = $colGrandTotal = '';
        if ($showBenefit) {
            $colBenefitStart = $kol[$idx];
            $colJp = $kol[$idx++];
            $colJht = $kol[$idx++];
            $colJkk = $kol[$idx++];
            $colJkm = $kol[$idx++];
            $colBpjsKantor = $kol[$idx++];
            $colBenefitEnd = $kol[$idx - 1];

            $colPphStart = $kol[$idx];
            $colBruto = $kol[$idx++];
            $colDpp = $kol[$idx++];
            $colTer = $kol[$idx++];
            $colPph21 = $kol[$idx++];
            $colPphEnd = $kol[$idx - 1];

            $colGrandTotal = $kol[$idx++];
        }

        $lastColIdx = $idx - 1;
        $kol_akhir = $kol[$lastColIdx];

        $namaKaryawan = $dataKaryawan ? $dataKaryawan->nama : '-';
        $jabatanKaryawan = ($dataKaryawan && $dataKaryawan->jabatan) ? $dataKaryawan->jabatan->nama : '-';
        $tglMasukStr = ($dataKaryawan && $dataKaryawan->tanggal_masuk) ? date('d-m-Y', strtotime($dataKaryawan->tanggal_masuk)) : '-';
        $tglKeluarStr = ($dataKaryawan && $dataKaryawan->tanggal_keluar) ? date('d-m-Y', strtotime($dataKaryawan->tanggal_keluar)) : '-';
        $isAktif = !$dataKaryawan || $dataKaryawan->aktif == 'Y';

        $bar = 1;
        $si->setCellValue('A'.$bar, $judulPeriode);
        $si->mergeCells('A'.$bar.':'.$kol_akhir.$bar);
        $bar++; // row 2
        $si->setCellValue('C'.$bar, 'NAMA');
        $si->mergeCells('C'.$bar.':E'.$bar);
        $si->setCellValue('F'.$bar, ': '.$namaKaryawan);
        $si->mergeCells('F'.$bar.':'.$kol_akhir.$bar);
        $bar++; // row 3
        $si->setCellValue('C'.$bar, 'JABATAN');
        $si->mergeCells('C'.$bar.':E'.$bar);
        $si->setCellValue('F'.$bar, ': '.$jabatanKaryawan);
        $si->mergeCells('F'.$bar.':'.$kol_akhir.$bar);
        $bar++; // row 4
        if ($isAktif) {
            $si->setCellValue('C'.$bar, 'TANGGAL GABUNG');
            $si->mergeCells('C'.$bar.':E'.$bar);
            $si->setCellValue('F'.$bar, ': '.$tglMasukStr);
            $si->mergeCells('F'.$bar.':'.$kol_akhir.$bar);
        } else {
            $si->setCellValue('C'.$bar, 'MASA KERJA');
            $si->mergeCells('C'.$bar.':E'.$bar);
            $si->setCellValue('F'.$bar, ': '.$tglMasukStr.' s/d '.$tglKeluarStr);
            $si->mergeCells('F'.$bar.':'.$kol_akhir.$bar);
        }
        $bar += 2; // row 6 (row 5 kosong)
        $headerStartRow = $bar;

        $si->setCellValue('A'.$bar, 'NO');
        $si->mergeCells('A'.$bar.':A'.($bar+2));
        $si->setCellValue('B'.$bar, 'BULAN');
        $si->mergeCells('B'.$bar.':B'.($bar+2));
        $si->setCellValue('C'.$bar, 'GAJI / UPAH IDR');
        $si->mergeCells('C'.$bar.':C'.($bar+2));
        $si->setCellValue('D'.$bar, 'U/MAKAN & TRANSPORTASI');
        $si->mergeCells('D'.$bar.':F'.$bar);
        $si->setCellValue('G'.$bar, 'TUNJANGAN LAIN');
        $si->mergeCells('G'.$bar.':N'.$bar);
        $si->setCellValue('O'.$bar, 'POTONGAN');
        $si->mergeCells('O'.$bar.':X'.$bar);

        if ($hasKalkulasiPhk) {
            $si->setCellValue($colPhkStart.$bar, 'KALKULASI PHK');
            $si->mergeCells($colPhkStart.$bar.':'.$colPhkEnd.$bar);
        }

        $si->setCellValue($colTotalDiterima.$bar, 'TOTAL DITERIMA IDR');
        $si->mergeCells($colTotalDiterima.$bar.':'.$colTotalDiterima.($bar+2));

        if ($showBenefit) {
            $si->setCellValue($colBenefitStart.$bar, 'BENEFIT LAINNYA');
            $si->mergeCells($colBenefitStart.$bar.':'.$colBenefitEnd.$bar);
            $si->setCellValue($colPphStart.$bar, 'PENGHITUNGAN PPh 21');
            $si->mergeCells($colPphStart.$bar.':'.$colPphEnd.$bar);
            $si->setCellValue($colGrandTotal.$bar, 'TOTAL IDR');
            $si->mergeCells($colGrandTotal.$bar.':'.$colGrandTotal.($bar+2));
        }

        $bar++; // row 4
        $si->setCellValue('D'.$bar, 'HR');
        $si->mergeCells('D'.$bar.':D'.($bar+1));
        $si->setCellValue('E'.$bar, '@ HARI IDR');
        $si->mergeCells('E'.$bar.':E'.($bar+1));
        $si->setCellValue('F'.$bar, 'JUMLAH IDR');
        $si->mergeCells('F'.$bar.':F'.($bar+1));
        $si->setCellValue('G'.$bar, 'OVERTIME');
        $si->mergeCells('G'.$bar.':H'.$bar);
        $si->setCellValue('I'.$bar, 'MEDICAL IDR');
        $si->mergeCells('I'.$bar.':I'.($bar+1));
        $si->setCellValue('J'.$bar, 'THR IDR');
        $si->mergeCells('J'.$bar.':J'.($bar+1));
        $si->setCellValue('K'.$bar, 'BONUS IDR');
        $si->mergeCells('K'.$bar.':K'.($bar+1));
        $si->setCellValue('L'.$bar, 'INSENTIF IDR');
        $si->mergeCells('L'.$bar.':L'.($bar+1));
        $si->setCellValue('M'.$bar, 'TELKOMSEL IDR');
        $si->mergeCells('M'.$bar.':M'.($bar+1));
        $si->setCellValue('N'.$bar, 'LAIN-LAIN IDR');
        $si->mergeCells('N'.$bar.':N'.($bar+1));
        $si->setCellValue('O'.$bar, '25%');
        $si->mergeCells('O'.$bar.':P'.$bar);
        $si->setCellValue('Q'.$bar, 'TELP. IDR');
        $si->mergeCells('Q'.$bar.':Q'.($bar+1));
        $si->setCellValue('R'.$bar, 'BENSIN IDR');
        $si->mergeCells('R'.$bar.':R'.($bar+1));
        $si->setCellValue('S'.$bar, 'PINJAMAN');
        $si->mergeCells('S'.$bar.':T'.$bar);
        $si->setCellValue('U'.$bar, 'BPJS (KIS) IDR');
        $si->mergeCells('U'.$bar.':U'.($bar+1));
        $si->setCellValue('V'.$bar, 'UNPAID LEAVE');
        $si->mergeCells('V'.$bar.':V'.($bar+1));
        $si->setCellValue('W'.$bar, 'KOMPENSASI IDR');
        $si->mergeCells('W'.$bar.':W'.($bar+1));
        $si->setCellValue('X'.$bar, 'LAIN-LAIN IDR');
        $si->mergeCells('X'.$bar.':X'.($bar+1));

        if ($hasKalkulasiPhk) {
            $si->setCellValue($colKompensasi.$bar, 'KOMPENSASI');
            $si->mergeCells($colKompensasi.$bar.':'.$colKompensasi.($bar+1));
            $si->setCellValue($colPesangon.$bar, 'PESANGON');
            $si->mergeCells($colPesangon.$bar.':'.$colPesangon.($bar+1));
            $si->setCellValue($colMasaKerja.$bar, 'MASA KERJA');
            $si->mergeCells($colMasaKerja.$bar.':'.$colMasaKerja.($bar+1));
            $si->setCellValue($colUangPisah.$bar, 'UANG PISAH');
            $si->mergeCells($colUangPisah.$bar.':'.$colUangPisah.($bar+1));
            $si->setCellValue($colSisaCutiHari.$bar, 'SISA CUTI');
            $si->mergeCells($colSisaCutiHari.$bar.':'.$colSisaCutiIdr.$bar);
            $si->setCellValue($colPhkLain.$bar, 'LAIN-LAIN');
            $si->mergeCells($colPhkLain.$bar.':'.$colPhkLain.($bar+1));
        }

        if ($showBenefit) {
            $si->setCellValue($colJp.$bar, 'JP 3%');
            $si->mergeCells($colJp.$bar.':'.$colJp.($bar+1));
            $si->setCellValue($colJht.$bar, 'JHT 5.7%');
            $si->mergeCells($colJht.$bar.':'.$colJht.($bar+1));
            $si->setCellValue($colJkk.$bar, 'JKK 0,24%');
            $si->mergeCells($colJkk.$bar.':'.$colJkk.($bar+1));
            $si->setCellValue($colJkm.$bar, 'JKM 0,3%');
            $si->mergeCells($colJkm.$bar.':'.$colJkm.($bar+1));
            $si->setCellValue($colBpjsKantor.$bar, 'BPJS KESEHATAN');
            $si->mergeCells($colBpjsKantor.$bar.':'.$colBpjsKantor.($bar+1));
            $si->setCellValue($colBruto.$bar, 'PENGHASILAN BRUTO');
            $si->mergeCells($colBruto.$bar.':'.$colBruto.($bar+1));
            $si->setCellValue($colDpp.$bar, 'DPP');
            $si->mergeCells($colDpp.$bar.':'.$colDpp.($bar+1));
            $si->setCellValue($colTer.$bar, 'TER '.($dataKaryawan ? $dataKaryawan->ptkp->ter : ''));
            $si->mergeCells($colTer.$bar.':'.$colTer.($bar+1));
            $si->setCellValue($colPph21.$bar, 'PPh 21');
            $si->mergeCells($colPph21.$bar.':'.$colPph21.($bar+1));
        }

        $bar++; // row 5
        $si->setCellValue('G'.$bar, 'FRATEKINDO');
        $si->setCellValue('H'.$bar, 'CUSTOMER');
        $si->setCellValue('O'.$bar, 'HR');
        $si->setCellValue('P'.$bar, 'JUMLAH IDR');
        $si->setCellValue('S'.$bar, 'KAS');
        $si->setCellValue('T'.$bar, 'CICILAN');
        if ($hasKalkulasiPhk) {
            $si->setCellValue($colSisaCutiHari.$bar, 'HARI');
            $si->setCellValue($colSisaCutiIdr.$bar, 'IDR');
        }

        $headerEndRow = $bar;
        $bar++; // row 9 (first data row)
        $firstDataRow = $bar;
        $totPenghasilanBruto = 0;
        $totAccMonth = 0;
        $totPph21 = 0;

        foreach ($rows as $row) {
            $si->setCellValue('A'.$bar, $row['no']);
            $si->setCellValue('B'.$bar, $row['label']);
            $d = $row['data'] ?? null;
            if ($d) {
                $si->setCellValue('C'.$bar, ($d->gaji + $d->kenaikan_gaji));
                $si->setCellValue('D'.$bar, $d->hari_makan);
                $si->setCellValue('E'.$bar, $d->uang_makan_harian);
                $si->setCellValue('F'.$bar, $d->uang_makan_jumlah);
                $si->setCellValue('G'.$bar, $d->overtime_fjg);
                $si->setCellValue('H'.$bar, $d->overtime_cus);
                $si->setCellValue('I'.$bar, $d->medical);
                $si->setCellValue('J'.$bar, $d->thr);
                $si->setCellValue('K'.$bar, $d->bonus);
                $si->setCellValue('L'.$bar, $d->insentif);
                $si->setCellValue('M'.$bar, $d->telkomsel);
                $si->setCellValue('N'.$bar, $d->lain);
                $si->setCellValue('O'.$bar, $d->pot_25_hari);
                $si->setCellValue('P'.$bar, $d->pot_25_jumlah);
                $si->setCellValue('Q'.$bar, $d->pot_telepon);
                $si->setCellValue('R'.$bar, $d->pot_bensin);
                $si->setCellValue('S'.$bar, $d->pot_kas);
                $si->setCellValue('T'.$bar, $d->pot_cicilan);
                $si->setCellValue('U'.$bar, $d->pot_bpjs);
                $si->setCellValue('V'.$bar, $d->pot_cuti_jumlah);
                $si->setCellValue('W'.$bar, $d->pot_kompensasi_jumlah);
                $si->setCellValue('X'.$bar, $d->pot_lain);

                if ($hasKalkulasiPhk) {
                    $si->setCellValue($colKompensasi.$bar, $d->phk_kompensasi ?? 0);
                    $si->setCellValue($colPesangon.$bar, $d->phk_pesangon ?? 0);
                    $si->setCellValue($colMasaKerja.$bar, $d->phk_masa_kerja ?? 0);
                    $si->setCellValue($colUangPisah.$bar, $d->phk_uang_pisah ?? 0);
                    $si->setCellValue($colSisaCutiHari.$bar, $d->phk_sisa_cuti_hari ?? 0);
                    $si->setCellValue($colSisaCutiIdr.$bar, $d->phk_sisa_cuti_jumlah ?? 0);
                    $si->setCellValue($colPhkLain.$bar, $d->phk_lain ?? 0);
                }

                $si->setCellValue($colTotalDiterima.$bar, $d->total_diterima);

                if ($showBenefit) {
                    $si->setCellValue($colJp.$bar, $d->kantor_jp);
                    $si->setCellValue($colJht.$bar, $d->kantor_jht);
                    $si->setCellValue($colJkk.$bar, $d->kantor_jkk);
                    $si->setCellValue($colJkm.$bar, $d->kantor_jkm);
                    $si->setCellValue($colBpjsKantor.$bar, $d->kantor_bpjs);
                    $si->setCellValue($colBruto.$bar, $d->penghasilan_bruto);
                    $si->setCellValue($colDpp.$bar, $d->dpp);
                    $si->setCellValue($colTer.$bar, $d->ter_persen / 100);
                    if (!$isYearlyMode || ((int)$d->bulan) !== 12) {
                        $si->setCellValue($colPph21.$bar, $d->pph21);
                        $totPph21 += $d->pph21;
                    }
                    $si->setCellValue(
                        $colGrandTotal.$bar,
                        '='.$colTotalDiterima.$bar.'+'.$colJp.$bar.'+'.$colJht.$bar.'+'.$colJkk.$bar.'+'.$colJkm.$bar.'+'.$colBpjsKantor.$bar.'+'.$colPph21.$bar
                    );
                }

                $totPenghasilanBruto += $d->penghasilan_bruto;
                $totAccMonth += 1;
            } else {
                for ($z = 2; $z <= $lastColIdx; $z++) {
                    $si->setCellValue($kol[$z].$bar, 0);
                }
            }
            $bar++;
        }

        $lastDataRow = $bar - 1;
        $bar++; // blank row before TOTAL
        $si->setCellValue('A'.$bar, 'TOTAL');
        $si->mergeCells('A'.$bar.':B'.$bar);
        $si->setCellValue('C'.$bar, '=SUM(C'.$firstDataRow.':C'.$lastDataRow.')');
        $si->setCellValue('F'.$bar, '=SUM(F'.$firstDataRow.':F'.$lastDataRow.')');
        for ($k = 6; $k <= 13; $k++) { // G s/d N
            $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].$firstDataRow.':'.$kol[$k].$lastDataRow.')');
        }
        for ($k = 15; $k <= 23; $k++) { // P s/d X
            $si->setCellValue($kol[$k].$bar, '=SUM('.$kol[$k].$firstDataRow.':'.$kol[$k].$lastDataRow.')');
        }
        if ($hasKalkulasiPhk) {
            foreach ([$colKompensasi, $colPesangon, $colMasaKerja, $colUangPisah, $colSisaCutiHari, $colSisaCutiIdr, $colPhkLain] as $cPhk) {
                $si->setCellValue($cPhk.$bar, '=SUM('.$cPhk.$firstDataRow.':'.$cPhk.$lastDataRow.')');
            }
        }
        $si->setCellValue($colTotalDiterima.$bar, '=SUM('.$colTotalDiterima.$firstDataRow.':'.$colTotalDiterima.$lastDataRow.')');

        if ($showBenefit) {
            foreach ([$colJp, $colJht, $colJkk, $colJkm, $colBpjsKantor, $colBruto, $colDpp, $colPph21, $colGrandTotal] as $cBen) {
                $si->setCellValue($cBen.$bar, '=SUM('.$cBen.$firstDataRow.':'.$cBen.$lastDataRow.')');
            }
        }

        $si->getStyle('A1')->getFont()->setName('Arial')->setSize(14)->setUnderline(TRUE)->getColor()->setARGB('0000FF');
        $si->getStyle('A1')->getAlignment()->setHorizontal('center');
        $si->getStyle('C2:'.$kol_akhir.'4')->getAlignment()->setHorizontal('left');
        $si->getStyle('A'.$headerStartRow.':A'.$bar)->getAlignment()->setHorizontal('center');
        $si->getStyle('O'.$headerStartRow.':X'.$bar)->getFont()->getColor()->setARGB('FF0000');
        $si->getStyle($colTotalDiterima.$headerStartRow.':'.$colTotalDiterima.$bar)->getFont()->getColor()->setARGB('0000FF');
        if ($showBenefit) {
            $si->getStyle($colBenefitStart.$headerStartRow.':'.$colGrandTotal.$bar)->getFont()->getColor()->setARGB('FFAA00');
        }
        $si->getStyle('A'.$headerStartRow.':'.$kol_akhir.$headerEndRow)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(TRUE);
        $si->getStyle('A'.$headerStartRow.':'.$kol_akhir.$headerEndRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A'.$firstDataRow.':'.$kol_akhir.($bar-1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A'.$firstDataRow.':'.$kol_akhir.($bar-1))->getBorders()->getVertical()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('A'.$firstDataRow.':'.$kol_akhir.($bar-1))->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
        $si->getStyle('A'.$bar.':'.$kol_akhir.$bar)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $si->getStyle('C'.$firstDataRow.':'.$kol_akhir.$bar)->getNumberFormat()->setFormatCode('#,##0');
        if ($showBenefit) {
            $si->getStyle($colTer.$firstDataRow.':'.$colTer.$bar)->getNumberFormat()->setFormatCode('0.00%');
        }

        if ($isYearlyMode && $showBenefit && $dataKaryawan) {
            $hitung = $this->hitungPPh21GrossUp($totPenghasilanBruto, $dataKaryawan->ptkp->jumlah, $totAccMonth);
            $c1 = $colBruto;
            $c2 = $colDpp;
            $c3 = $colTer;
            $c4 = $colPph21;
            $c5 = $colGrandTotal;
            $cNext = $kol[$lastColIdx + 1];

            $totalRowNum = $bar;
            $bar += 2;
            $barAwal = $bar;
            $si->setCellValue($c1.$bar, 'Penghitungan PPh Pasal 21 pada masa pajak terakhir (Desember)');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $bar++;
            $barBruto = $bar;
            $si->setCellValue($c1.$bar, 'Penghasilan Bruto Setahun');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '='.$colBruto.$totalRowNum);
            $bar++;
            $barDpp = $bar;
            $si->setCellValue($c1.$bar, 'Dasar Pengenaan Pajak (DPP)');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, $hitung['dpp']);
            $bar++;
            $si->setCellValue($c1.$bar, 'Pengurang:');
            $bar++;
            $barJabStart = $bar;
            $si->setCellValue($c1.$bar, '-');
            $si->setCellValue($c2.$bar, 'Biaya Jabatan Setahun :');
            if ($isPdf) $si->mergeCells($c2.$bar.':'.$c4.$bar);
            $bar++;
            $si->setCellValue($c2.$bar, '5% x');
            $si->setCellValue($c3.$bar, '='.$c5.$barDpp);
            $bar++;
            $si->setCellValue($c2.$bar, '(  max '.$totAccMonth.' x');
            $si->setCellValue($c3.$bar, '500000');
            $si->setCellValue($c4.$bar, ')');
            $si->setCellValue($c5.$bar, $hitung['biaya_jabatan']);
            $barJab = $bar;
            $bar++;
            $si->setCellValue($c1.$bar, '-');
            $si->setCellValue($c2.$bar, 'Iuran Pensiun & Hari Tua :');
            if ($isPdf) $si->mergeCells($c2.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '0');
            $barPensiun = $bar;
            $bar++;
            $si->setCellValue($c1.$bar, '-');
            $si->setCellValue($c2.$bar, 'Zakat & Sumbangan :');
            if ($isPdf) $si->mergeCells($c2.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '0');
            $si->setCellValue($cNext.$bar, '+');
            $barZakat = $bar;
            $bar++;
            $barTotPengurang = $bar;
            $si->setCellValue($c1.$bar, 'Total Pengurang');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '=SUM('.$c5.$barJab.':'.$c5.$barZakat.')');
            $si->setCellValue($cNext.$bar, '-');
            $bar++;
            $barNeto = $bar;
            $si->setCellValue($c1.$bar, 'Penghasilan Neto Setahun');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '='.$c5.$barDpp.'-'.$c5.$barTotPengurang);
            $bar++;
            $barPtkp = $bar;
            $si->setCellValue($c1.$bar, 'PTKP Setahun untuk '.$dataKaryawan->ptkp->kode);
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, $dataKaryawan->ptkp->jumlah);
            $si->setCellValue($cNext.$bar, '-');
            $bar++;
            $si->setCellValue($c1.$bar, 'Penghasilan Kena Pajak Setahun');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '=ROUNDDOWN('.$c5.$barNeto.'-'.$c5.$barPtkp.',-3)');
            $bar++;
            $si->setCellValue($c1.$bar, 'PPh Pasal 21 terutang setahun');
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $bar++;
            $barBreakdown = $bar;
            foreach ($hitung['breakdown'] as $item) {
                $si->setCellValue($c1.$bar, ($item['rate']*100).'% x');
                $si->setCellValue($c2.$bar, $item['amount']);
                $si->setCellValue($c4.$bar, $item['total']);
                $si->getStyle($c1.$bar)->getAlignment()->setHorizontal('right');
                $bar++;
            }
            $si->setCellValue($c5.($bar-1), '+');
            $si->setCellValue($c5.$bar, '=SUM('.$c4.$barBreakdown.':'.$c4.($bar-1).')');
            $bar++;
            $si->setCellValue($c1.$bar, 'PPh Pasal 21 yang telah dipotong sampai November '.$keyTahun);
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '=SUM('.$colPph21.$firstDataRow.':'.$colPph21.($firstDataRow+10).')');
            $si->setCellValue($cNext.$bar, '-');
            $si->getStyle($c5.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $bar++;
            if ($hitung['pph'] - $totPph21 >= 0) {
                $si->setCellValue($c1.$bar, 'PPh Pasal 21 yang harus dipotong pada bulan Desember '.$keyTahun);
            } else {
                $si->setCellValue($c1.$bar, 'PPh Pasal 21 yang lebih dipotong');
            }
            if ($isPdf) $si->mergeCells($c1.$bar.':'.$c4.$bar);
            $si->setCellValue($c5.$bar, '='.$c5.($bar-2).'-'.$c5.($bar-1));

            $si->getStyle($c1.$barAwal.':'.$c5.$bar)->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR);
            $si->getStyle($c3.$barAwal.':'.$c5.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle($c2.$barBreakdown.':'.$c2.$bar)->getNumberFormat()->setFormatCode('#,##0');
            $si->getStyle($c1.$barJabStart.':'.$c1.$barZakat)->getAlignment()->setHorizontal('right');
            $si->getStyle($c2.($barJabStart+1).':'.$c2.$barJab)->getAlignment()->setHorizontal('right');
            $si->getStyle($c5.$barZakat)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle($c5.$barTotPengurang)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle($c5.$barPtkp)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle($c4.($barBreakdown+count($hitung['breakdown'])-1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $si->getStyle($c5.$bar)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        }

        $si->getColumnDimension('A')->setWidth(40, Dimension::UOM_PIXELS);
        $si->getColumnDimension('B')->setWidth(100, Dimension::UOM_PIXELS);
        $si->getColumnDimension('C')->setWidth(100, Dimension::UOM_PIXELS);
        $si->getColumnDimension('D')->setWidth(30, Dimension::UOM_PIXELS);
        for ($k = 4; $k <= 13; $k++) { // E s/d N
            $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
        }
        $si->getColumnDimension('O')->setWidth(30, Dimension::UOM_PIXELS);
        for ($k = 15; $k <= 23; $k++) { // P s/d X
            $si->getColumnDimension($kol[$k])->setWidth(85, Dimension::UOM_PIXELS);
        }
        if ($hasKalkulasiPhk) {
            $si->getColumnDimension($colKompensasi)->setWidth(95, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colPesangon)->setWidth(95, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colMasaKerja)->setWidth(95, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colUangPisah)->setWidth(95, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colSisaCutiHari)->setWidth(45, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colSisaCutiIdr)->setWidth(90, Dimension::UOM_PIXELS);
            $si->getColumnDimension($colPhkLain)->setWidth(90, Dimension::UOM_PIXELS);
        }
        $si->getColumnDimension($colTotalDiterima)->setWidth(110, Dimension::UOM_PIXELS);
        if ($showBenefit) {
            foreach ([$colJp, $colJht, $colJkk, $colJkm, $colBpjsKantor] as $cBen) {
                $si->getColumnDimension($cBen)->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension($colBruto)->setWidth(110, Dimension::UOM_PIXELS);
            foreach ([$colDpp, $colTer, $colPph21] as $cPph) {
                $si->getColumnDimension($cPph)->setWidth(85, Dimension::UOM_PIXELS);
            }
            $si->getColumnDimension($colGrandTotal)->setWidth(120, Dimension::UOM_PIXELS);
        }
    }
}
