<?php

namespace App\Http\Controllers;

use App\Models\CicCuti;
use App\Models\CicCutiDetail;
use App\Models\CicCutiDate;
use App\Models\CicKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CicJatahCutiTahunan;

class CicCutiController extends Controller
{
    public function findAll(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $cutis = CicCuti::with(['cicKaryawan.jabatan', 'cicKaryawan.divisi', 'cicKaryawan.statusKerja', 'cicKaryawan.area', 'details.dates', 'details.cicJenisCutiKhusus'])
            ->where('tahun', $tahun)
            ->where('jenis_form', '!=', 'CUTI_MASAL')
            ->orderBy('created_at', 'desc')->get();

        $masals = \App\Models\CicCutiMasal::with('dates')->withCount('cutis')->where('tahun', $tahun)->get();

        $data = [];
        foreach($masals as $m) {
            $count = $m->cutis_count;
            $data[] = [
                'id' => (string)$m->id,
                'jenis_form' => 'CUTI_MASAL_GLOBAL',
                'tahun' => (string)$m->tahun,
                'tanggal_kembali' => $m->tanggal_kembali,
                'created_at' => $m->created_at,
                'cic_karyawan' => [
                    'id' => 0,
                    'nama' => $count . ' KARYAWAN'
                ],
                'details' => [
                    [
                        'id' => 'masal_' . $m->id,
                        'kategori' => 'CUTI_MASAL',
                        'lama_hari' => (float)$m->lama_hari,
                        'keterangan' => $m->keterangan ?? '',
                        'dates' => $m->dates->map(function($d) {
                            return ['tanggal' => $d->tanggal];
                        })->toArray()
                    ]
                ],
                'tanggal_cuti_str' => ''
            ];
        }

        foreach($cutis as $c) {
            $cArray = $c->toArray();
            $cArray['tanggal_cuti_str'] = '';
            $data[] = $cArray;
        }

        return response()->json(['status' => 'success', 'message' => 'success', 'data' => $data], 200);
    }

    public function info(Request $request)
    {
        $karyawanId = $request->cic_karyawan_id;
        $tahun = $request->tahun ?? date('Y');
        
        $cicKaryawan = CicKaryawan::with(['jabatan', 'divisi'])->find($karyawanId);
        if (!$cicKaryawan) {
            return response()->json(['message' => 'CicKaryawan not found'], 404);
        }

        $jatah = CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
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

        $sudahDiambil = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
            $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
              ->whereHas('cicCuti', function($q2) use ($karyawanId, $tahun) {
                  $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun);
              });
        })->count();

        $cutiMasal = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
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
        $query = CicKaryawan::with(['jabatan'])
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
        
        $jatahs = CicJatahCutiTahunan::where('tahun', $tahun)->get()->keyBy('cic_karyawan_id');
        
        $cutiDates = CicCutiDate::whereHas('cicCutiDetail.cicCuti', function($q) use ($tahun) {
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
                $jatahSnap = CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
                    ->where('tahun', $tahun)->first();
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $snapSudahDiambil = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();
                $snapCutiMasal = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
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
            $cutiMasalModel = \App\Models\CicCutiMasal::create([
                'tahun' => $request->tahun,
                'keterangan' => $request->keterangan,
                'tanggal_kembali' => $request->tanggal_kembali ?? null,
                'lama_hari' => $request->lama_hari ?? 0,
            ]);

            if (!empty($request->global_dates)) {
                foreach($request->global_dates as $dt) {
                    \App\Models\CicCutiMasalDate::create([
                        'cic_cuti_masal_id' => $cutiMasalModel->id,
                        'tanggal' => $dt,
                    ]);
                }
            }

            foreach($request->cic_karyawans as $k) {
                $karyawanId = $k['cic_karyawan_id'];
                $tahun = $request->tahun;

                $jatahSnap = CicJatahCutiTahunan::where('cic_karyawan_id', $karyawanId)
                    ->where('tahun', $tahun)->first();
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $snapSudahDiambil = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();
                $snapCutiMasal = CicCutiDate::whereHas('cicCutiDetail', function($q) use ($karyawanId, $tahun) {
                    $q->where('kategori', 'CUTI_MASAL')
                      ->whereHas('cicCuti', fn($q2) => $q2->where('cic_karyawan_id', $karyawanId)->where('tahun', $tahun));
                })->count();

                $cicCuti = CicCuti::create([
                    'cic_karyawan_id' => $karyawanId,
                    'jenis_form' => 'CUTI_MASAL',
                    'tanggal_kembali' => $request->tanggal_kembali ?? null,
                    'tahun' => $tahun,
                    'cic_cuti_masal_id' => $cutiMasalModel->id,
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

    public function detailMasal($id)
    {
        $masal = \App\Models\CicCutiMasal::with(['dates', 'cutis.cicKaryawan.jabatan', 'cutis.cicKaryawan.divisi', 'cutis.cicKaryawan.statusKerja', 'cutis.cicKaryawan.area', 'cutis.details.dates', 'cutis.details.cicJenisCutiKhusus'])->find($id);
        if (!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'data' => $masal]);
    }

    public function addKaryawanMasal(Request $request, $id)
    {
        $masal = \App\Models\CicCutiMasal::find($id);
        if(!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        
        DB::beginTransaction();
        try {
            foreach($request->cic_karyawans as $k) {
                $karyawanId = $k['cic_karyawan_id'];
                $tahun = $masal->tahun;
                
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

                $cuti = \App\Models\CicCuti::create([
                    'cic_karyawan_id' => $karyawanId,
                    'jenis_form'      => 'CUTI_MASAL',
                    'tanggal_kembali' => $masal->tanggal_kembali,
                    'tahun'           => $tahun,
                    'cic_cuti_masal_id' => $masal->id,
                ]);

                foreach($k['details'] as $d) {
                    $isSnapshotKategori = in_array($d['kategori'], ['TAHUNAN', 'IJIN', 'CUTI_MASAL']);
                    
                    $detail = \App\Models\CicCutiDetail::create([
                        'cic_cuti_id'         => $cuti->id,
                        'kategori'        => $d['kategori'],
                        'jenis_cuti_khusus_id' => $d['jenis_cuti_khusus_id'] ?? null,
                        'jenis_unpaid'    => $d['jenis_unpaid'] ?? null,
                        'lama_hari'       => $d['lama_hari'],
                        'keterangan'      => $d['keterangan'] ?? '',
                        'snap_total_hak_cuti' => $isSnapshotKategori ? $snapTotalHakCuti  : null,
                        'snap_sudah_diambil'  => $isSnapshotKategori ? $snapSudahDiambil  : null,
                        'snap_cuti_masal'     => $isSnapshotKategori ? $snapCutiMasal     : null,
                    ]);

                    if(!empty($d['dates'])) {
                        foreach($d['dates'] as $tgl) {
                            \App\Models\CicCutiDate::create([
                                'cic_cuti_detail_id' => $detail->id,
                                'tanggal'        => $tgl
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Berhasil']);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updateKeteranganMasal(Request $request, $id)
    {
        $masal = \App\Models\CicCutiMasal::find($id);
        if(!$masal) return response()->json(['status' => 'error', 'message' => 'Not found']);
        $masal->keterangan = $request->keterangan;
        $masal->save();
        return response()->json(['status' => 'success', 'message' => 'Berhasil update keterangan']);
    }

    public function updateKeteranganDetail(Request $request, $detailId)
    {
        $detail = \App\Models\CicCutiDetail::find($detailId);
        if(!$detail) return response()->json(['status' => 'error', 'message' => 'Not found']);
        $detail->keterangan = $request->keterangan;
        $detail->save();
        return response()->json(['status' => 'success', 'message' => 'Berhasil update keterangan detail']);
    }

    public function hapusCutiMasal($id)
    {
        $masal = \App\Models\CicCutiMasal::find($id);
        if(!$masal) return response()->json(['status' => 'error', 'message' => 'Not found']);
        DB::beginTransaction();
        try {
            foreach($masal->cutis as $cuti) {
                $cuti->delete();
            }
            $masal->dates()->delete();
            $masal->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Berhasil hapus cuti masal']);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
