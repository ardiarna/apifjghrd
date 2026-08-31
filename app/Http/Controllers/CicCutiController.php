<?php

namespace App\Http\Controllers;

use App\Models\CicCuti;
use App\Models\CicCutiDetail;
use App\Models\CicCutiDate;
use App\Models\CicKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CicCutiController extends Controller
{
    public function findAll(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $data = CicCuti::with(['cicKaryawan.jabatan', 'cicKaryawan.divisi', 'cicKaryawan.statusKerja', 'cicKaryawan.area', 'details.dates', 'details.cicJenisCutiKhusus'])->where('tahun', $tahun)->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'message' => 'success', 'data' => $data], 200);
    }

    public function info(Request $request)
    {
        $karyawanId = $request->cic_karyawan_id;
        $tahun = $request->tahun ?? date('Y');
        
        $cicKaryawan = \App\Models\CicKaryawan::with(['jabatan', 'divisi'])->find($karyawanId);
        if (!$cicKaryawan) {
            return response()->json(['message' => 'CicKaryawan not found'], 404);
        }

        $jatah = \App\Models\CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
            ->where('tahun', $tahun)
            ->first();

        $totalHakCuti = 0;
        $sisaCutiTahunLalu = 0;
        $hakCuti = 0;
        $bolehMinus = 'N';
        if ($jatah) {
            $totalHakCuti = $jatah->jumlah_cuti + $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu;
            $sisaCutiTahunLalu = $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu;
            $hakCuti = $jatah->jumlah_cuti;
            $bolehMinus = $jatah->boleh_minus;
        }

        $sudahDiambil = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
            $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
              ->whereHas('cicCuti', function($q2) use ($karyawanId, $tahun) {
                  $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun);
              });
        })->count();

        $cutiMasal = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
            $q->where('kategori', 'CUTI_MASAL')
              ->whereHas('cicCuti', function($q2) use ($karyawanId, $tahun) {
                  $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun);
              });
        })->count();

        $belumDiambil = $totalHakCuti - $sudahDiambil - $cutiMasal;

        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => [
                'cicKaryawan' => $cicKaryawan,
                'kuota' => [
                    'hak_cuti' => $hakCuti,
                    'sisa_cuti_tahun_lalu' => $sisaCutiTahunLalu,
                    'total_hak_cuti' => $totalHakCuti,
                    'sudah_diambil' => $sudahDiambil,
                    'cuti_masal' => $cutiMasal,
                    'belum_diambil' => $belumDiambil,
                    'boleh_minus' => $bolehMinus,
                ]
            ]
        ], 200);
    }

    public function infoMasal(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $query = \App\Models\CicKaryawan::with(['jabatan'])
            ->join('areas', 'cic_karyawans.area_id', '=', 'areas.id')
            ->select('cic_karyawans.*')
            ->where('cic_karyawans.aktif', 'Y')
            ->orderBy('cic_karyawans.staf')
            ->orderBy('areas.urutan')
            ->orderBy('cic_karyawans.id');
        if ($request->has('cic_karyawan_id')) {
            $query->where('cic_karyawans.id', $request->cic_karyawan_id);
        }
        $cic_karyawans = $query->get();
        
        $jatahs = \App\Models\CicJatahCutiTahunan::where('tahun', $tahun)->get()->keyBy('cic_karyawan_id');
        
        $cutiDates = \App\Models\CicCutiDate::whereHas('cicCutiDetail.cicCuti', function($q) use ($tahun) {
            $q->where('tahun', $tahun);
        })->with('cicCutiDetail.cicCuti')->get();

        $sudahDiambilMap = [];
        $cutiMasalMap = [];

        foreach($cutiDates as $cd) {
            $cicCuti = $cd->cicCutiDetail->cicCuti;
            $kId = $cicCuti->cic_karyawan_id;
            
            if ($cd->cicCutiDetail->kategori == 'CUTI_MASAL') {
                if (!isset($cutiMasalMap[$kId])) $cutiMasalMap[$kId] = 0;
                $cutiMasalMap[$kId]++;
            } elseif (in_array($cd->cicCutiDetail->kategori, ['TAHUNAN', 'IJIN'])) {
                if (!isset($sudahDiambilMap[$kId])) $sudahDiambilMap[$kId] = 0;
                $sudahDiambilMap[$kId]++;
            }
        }

        $result = [];
        foreach($cic_karyawans as $k) {
            $jatah = $jatahs[$k->id] ?? null;
            $totalHakCuti = $jatah ? ($jatah->jumlah_cuti + $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
            $sudahDiambil = $sudahDiambilMap[$k->id] ?? 0;
            $cutiMasal = $cutiMasalMap[$k->id] ?? 0;
            $belumDiambil = $totalHakCuti - $sudahDiambil - $cutiMasal;
            
            $result[] = [
                'cic_karyawan_id' => (string)$k->id,
                'nama' => $k->nama,
                'jabatan' => $k->jabatan ? $k->jabatan->nama : '',
                'has_jatah' => $jatah != null,
                'total_hak_cuti' => $totalHakCuti,
                'sudah_diambil' => $sudahDiambil,
                'cuti_masal' => $cutiMasal,
                'belum_diambil' => $belumDiambil,
                'boleh_minus' => $jatah ? $jatah->boleh_minus : 'N',
            ];
        }
        
        return response()->json(['status' => 'success', 'message' => 'Berhasil mengambil data', 'data' => $result]);
    }

    public function submit(Request $request)
    {
        $this->validate($request, [
            'cic_karyawan_id' => 'required|exists:cic_karyawans,id',
            'jenis_form' => 'required|in:CUTI,IJIN,CUTI_MASAL',
            
            'tanggal_kembali' => 'nullable|date',
            'tahun' => 'required|integer',
            'details' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $cutiId = $request->id;
            if ($cutiId) {
                $cicCuti = CicCuti::findOrFail($cutiId);
                $cicCuti->update([
                    'cic_karyawan_id' => $request->cic_karyawan_id,
                    'jenis_form' => $request->jenis_form,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $request->tahun,
                ]);
                
                $submittedDetailIds = array_filter(array_column($request->details, 'id'));
                if (!empty($submittedDetailIds)) {
                    CicCutiDetail::where('cic_cuti_id', $cicCuti->id)->whereNotIn('id', $submittedDetailIds)->delete();
                } else {
                    CicCutiDetail::where('cic_cuti_id', $cicCuti->id)->delete();
                }

                foreach ($request->details as $detail) {
                    if (!empty($detail['id'])) {
                        $cd = CicCutiDetail::find($detail['id']);
                        if ($cd) {
                            $cd->update([
                                'kategori' => $detail['kategori'],
                                'jenis_cuti_khusus_id' => $detail['jenis_cuti_khusus_id'] ?? null,
                                'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                                'keterangan' => $detail['keterangan'] ?? null,
                            ]);
                        }
                    } else {
                        $cd = CicCutiDetail::create([
                            'cic_cuti_id' => $cicCuti->id,
                            'kategori' => $detail['kategori'],
                            'jenis_cuti_khusus_id' => $detail['jenis_cuti_khusus_id'] ?? null,
                            'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                            'keterangan' => $detail['keterangan'] ?? null,
                            'lama_hari' => $detail['lama_hari'] ?? null,
                        ]);
        
                        if (!empty($detail['dates'])) {
                            foreach ($detail['dates'] as $date) {
                                CicCutiDate::create([
                                    'cic_cuti_detail_id' => $cd->id,
                                    'tanggal' => $date,
                                ]);
                            }
                        }
                    }
                }
            } else {
                $karyawanId = $request->cic_karyawan_id;
                $tahun = $request->tahun;

                // Calculate snapshot values once for TAHUNAN/IJIN categories
                $jatahSnap = \App\Models\CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
                    ->where('tahun', $tahun)->first();
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $snapSudahDiambil = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();
                $snapCutiMasal = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->where('kategori', 'CUTI_MASAL')
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();

                $cicCuti = CicCuti::create([
                    'cic_karyawan_id' => $karyawanId,
                    'jenis_form' => $request->jenis_form,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $tahun,
                ]);

                foreach ($request->details as $detail) {
                    $isSnapshotKategori = in_array($detail['kategori'], ['TAHUNAN', 'IJIN', 'CUTI_MASAL']);
                    $cd = CicCutiDetail::create([
                        'cic_cuti_id' => $cicCuti->id,
                        'kategori' => $detail['kategori'],
                        'jenis_cuti_khusus_id' => $detail['jenis_cuti_khusus_id'] ?? null,
                        'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                        'keterangan' => $detail['keterangan'] ?? null,
                        'lama_hari' => $detail['lama_hari'] ?? null,
                        'snap_total_hak_cuti' => $isSnapshotKategori ? $snapTotalHakCuti : null,
                        'snap_sudah_diambil'  => $isSnapshotKategori ? $snapSudahDiambil  : null,
                        'snap_cuti_masal'     => $isSnapshotKategori ? $snapCutiMasal     : null,
                    ]);

                    if (!empty($detail['dates'])) {
                        foreach ($detail['dates'] as $date) {
                            CicCutiDate::create([
                                'cic_cuti_detail_id' => $cd->id,
                                'tanggal' => $date,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $cicCuti]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function submitMasal(Request $request)
    {
        $this->validate($request, [
            'tahun' => 'required',
            'keterangan' => 'required',
            'cic_karyawans' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            foreach($request->cic_karyawans as $k) {
                $karyawanId = $k['cic_karyawan_id'];
                $tahun = $request->tahun;

                $jatahSnap = \App\Models\CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
                    ->where('tahun', $tahun)->first();
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $snapSudahDiambil = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();
                $snapCutiMasal = \App\Models\CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->where('kategori', 'CUTI_MASAL')
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();

                $cicCuti = CicCuti::create([
                    'cic_karyawan_id' => $karyawanId,
                    'jenis_form' => 'CUTI_MASAL',
                    
                    'tanggal_kembali' => $request->tanggal_kembali ?? null,
                    'tahun' => $tahun,
                ]);
                foreach($k['details'] as $detail) {
                    $isSnapshotKategori = ($detail['kategori'] == 'CUTI_MASAL');
                    $cd = CicCutiDetail::create([
                        'cic_cuti_id' => $cicCuti->id,
                        'kategori' => $detail['kategori'],
                        'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                        'lama_hari' => $detail['lama_hari'],
                        'keterangan' => $request->keterangan,
                        'snap_total_hak_cuti' => $isSnapshotKategori ? $snapTotalHakCuti : null,
                        'snap_sudah_diambil'  => $isSnapshotKategori ? $snapSudahDiambil  : null,
                        'snap_cuti_masal'     => $isSnapshotKategori ? $snapCutiMasal     : null,
                    ]);
                    if(!empty($detail['dates'])) {
                        foreach($detail['dates'] as $dt) {
                            CicCutiDate::create([
                                'cic_cuti_detail_id' => $cd->id,
                                'tanggal' => $dt
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Berhasil generate cicCuti masal']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        $cicCuti = CicCuti::find($id);
        if ($cicCuti) {
            $cicCuti->delete();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus']);
        }
        return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
    }
}
