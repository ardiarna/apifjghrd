<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\CutiDetail;
use App\Models\CutiDate;
use App\Models\CutiMasal;
use App\Models\CutiMasalDate;
use App\Models\CicCutiDate;
use App\Models\CicCutiMasalDate;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JatahCutiTahunan;

class CutiController extends Controller
{
    public function findAll(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        
        $query = Cuti::with(['karyawan.jabatan', 'karyawan.divisi', 'karyawan.statusKerja', 'karyawan.area', 'details.dates', 'details.jenisKhusus'])
            ->where('tahun', $tahun)
            ->orderBy('created_at', 'desc');
            
        if ($request->has('karyawan_id')) {
            $query->where('karyawan_id', $request->karyawan_id);
            $cutis = $query->get();
            return response()->json(['status' => 'success', 'message' => 'success', 'data' => $cutis], 200);
        }

        $query->where('jenis_form', '!=', 'CUTI_MASAL');
        $cutis = $query->get();
            
        $masals = CutiMasal::with('dates')
            ->withCount('cutis')
            ->where('tahun', $tahun)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $data = [];
        foreach ($cutis as $c) {
            $data[] = $c;
        }
        
        foreach ($masals as $m) {
            $count = $m->cutis_count;
            
            $data[] = [
                'id' => (string)$m->id,
                'jenis_form' => 'CUTI_MASAL_GLOBAL',
                'tahun' => (string)$m->tahun,
                'tanggal_kembali' => $m->tanggal_kembali,
                'created_at' => $m->created_at,
                'karyawan' => [
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
        
        usort($data, function($a, $b) {
            $dateA = is_array($a) ? $a['created_at'] : $a->created_at;
            $dateB = is_array($b) ? $b['created_at'] : $b->created_at;
            return strtotime($dateB) - strtotime($dateA);
        });
        
        return response()->json(['status' => 'success', 'message' => 'success', 'data' => $data], 200);
    }

    public function hariIni(Request $request)
    {
        $tanggal = $request->tanggal ?? date('Y-m-d');

        $cutiDates = CutiDate::with([
            'cutiDetail.cuti.karyawan.divisi',
            'cutiDetail.cuti.karyawan.jabatan',
            'cutiDetail.jenisKhusus'
        ])
        ->whereDate('tanggal', $tanggal)
        ->get();

        $data = [];
        $seen = [];
        foreach ($cutiDates as $cd) {
            $detail = $cd->cutiDetail;
            if (!$detail || !$detail->cuti || !$detail->cuti->karyawan) {
                continue;
            }
            $karyawan = $detail->cuti->karyawan;
            $key = $karyawan->id;

            $kategori = $detail->kategori;
            if ($kategori === 'KHUSUS' && $detail->jenisKhusus) {
                $kategori = 'KHUSUS (' . $detail->jenisKhusus->nama . ')';
            }

            if (isset($seen[$key])) {
                $idx = $seen[$key];
                if (strpos($data[$idx]['kategori'], $kategori) === false) {
                    $data[$idx]['kategori'] .= ', ' . $kategori;
                }
                continue;
            }

            $seen[$key] = count($data);

            $data[] = [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama,
                'nik' => $karyawan->nik,
                'divisi' => $karyawan->divisi ? $karyawan->divisi->nama : '',
                'jabatan' => $karyawan->jabatan ? $karyawan->jabatan->nama : '',
                'kategori' => $kategori,
                'keterangan' => $detail->keterangan ?? '',
            ];
        }

        if (class_exists(CutiMasalDate::class)) {
            $masalDates = CutiMasalDate::with(['cutiMasal.cutis.karyawan.divisi', 'cutiMasal.cutis.karyawan.jabatan'])
                ->whereDate('tanggal', $tanggal)
                ->get();

            foreach ($masalDates as $md) {
                if (!$md->cutiMasal || !$md->cutiMasal->cutis) continue;
                foreach ($md->cutiMasal->cutis as $c) {
                    if (!$c->karyawan) continue;
                    $karyawan = $c->karyawan;
                    $key = $karyawan->id;
                    if (isset($seen[$key])) continue;
                    $seen[$key] = count($data);

                    $data[] = [
                        'id' => $karyawan->id,
                        'nama' => $karyawan->nama,
                        'nik' => $karyawan->nik,
                        'divisi' => $karyawan->divisi ? $karyawan->divisi->nama : '',
                        'jabatan' => $karyawan->jabatan ? $karyawan->jabatan->nama : '',
                        'kategori' => 'CUTI_MASAL',
                        'keterangan' => $md->cutiMasal->keterangan ?? '',
                    ];
                }
            }
        }

        if (class_exists(CicCutiDate::class)) {
            $cicDates = CicCutiDate::with([
                'cicCutiDetail.cicCuti.cicKaryawan.divisi',
                'cicCutiDetail.cicCuti.cicKaryawan.jabatan',
                'cicCutiDetail.cicJenisCutiKhusus'
            ])
            ->whereDate('tanggal', $tanggal)
            ->get();

            foreach ($cicDates as $cd) {
                $detail = $cd->cicCutiDetail;
                if (!$detail || !$detail->cicCuti || !$detail->cicCuti->cicKaryawan) {
                    continue;
                }
                $karyawan = $detail->cicCuti->cicKaryawan;
                $key = 'cic_' . $karyawan->id;

                $kategori = $detail->kategori;
                if ($kategori === 'KHUSUS' && $detail->cicJenisCutiKhusus) {
                    $kategori = 'KHUSUS (' . $detail->cicJenisCutiKhusus->nama . ')';
                }

                if (isset($seen[$key])) {
                    $idx = $seen[$key];
                    if (strpos($data[$idx]['kategori'], $kategori) === false) {
                        $data[$idx]['kategori'] .= ', ' . $kategori;
                    }
                    continue;
                }

                $seen[$key] = count($data);

                $data[] = [
                    'id' => $karyawan->id,
                    'nama' => $karyawan->nama,
                    'nik' => $karyawan->nik,
                    'divisi' => $karyawan->divisi ? $karyawan->divisi->nama : '',
                    'jabatan' => $karyawan->jabatan ? $karyawan->jabatan->nama : '',
                    'kategori' => $kategori,
                    'keterangan' => $detail->keterangan ?? '',
                ];
            }
        }

        if (class_exists(CicCutiMasalDate::class)) {
            $cicMasalDates = CicCutiMasalDate::with(['cicCutiMasal.cutis.cicKaryawan.divisi', 'cicCutiMasal.cutis.cicKaryawan.jabatan'])
                ->whereDate('tanggal', $tanggal)
                ->get();

            foreach ($cicMasalDates as $md) {
                if (!$md->cicCutiMasal || !$md->cicCutiMasal->cutis) continue;
                foreach ($md->cicCutiMasal->cutis as $c) {
                    if (!$c->cicKaryawan) continue;
                    $karyawan = $c->cicKaryawan;
                    $key = 'cic_' . $karyawan->id;
                    if (isset($seen[$key])) continue;
                    $seen[$key] = count($data);

                    $data[] = [
                        'id' => $karyawan->id,
                        'nama' => $karyawan->nama,
                        'nik' => $karyawan->nik,
                        'divisi' => $karyawan->divisi ? $karyawan->divisi->nama : '',
                        'jabatan' => $karyawan->jabatan ? $karyawan->jabatan->nama : '',
                        'kategori' => 'CUTI_MASAL',
                        'keterangan' => $md->cicCutiMasal->keterangan ?? '',
                    ];
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'success', 'data' => $data], 200);
    }

    public function info(Request $request)
    {
        $karyawanId = $request->karyawan_id;
        $tahun = $request->tahun ?? date('Y');
        
        $karyawan = Karyawan::with(['jabatan', 'divisi'])->find($karyawanId);
        if (!$karyawan) {
            return response()->json(['message' => 'Karyawan not found'], 404);
        }

        $jatah = JatahCutiTahunan::where('karyawan_id', $karyawanId)
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

        $cutiCounts = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
            ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
            ->where('cutis.karyawan_id', $karyawanId)
            ->where('cutis.tahun', $tahun)
            ->whereIn('cuti_details.kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
            ->selectRaw("
                SUM(CASE WHEN cuti_details.kategori IN ('TAHUNAN', 'IJIN') THEN 1 ELSE 0 END) as sudah_diambil,
                SUM(CASE WHEN cuti_details.kategori = 'CUTI_MASAL' THEN 1 ELSE 0 END) as cuti_masal
            ")
            ->first();

        $sudahDiambil = $cutiCounts ? (int)$cutiCounts->sudah_diambil : 0;
        $cutiMasal = $cutiCounts ? (int)$cutiCounts->cuti_masal : 0;

        $belumDiambil = $totalHakCuti - $sudahDiambil - $cutiMasal;

        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => [
                'karyawan' => $karyawan,
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
        $query = Karyawan::with(['jabatan'])
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->select('karyawans.*')
            ->where('karyawans.aktif', 'Y')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan')
            ->orderBy('karyawans.id');
        if ($request->has('karyawan_id')) {
            $query->where('karyawans.id', $request->karyawan_id);
        }
        $karyawans = $query->get();
        
        $jatahsQuery = JatahCutiTahunan::where('tahun', $tahun);
        if ($request->has('karyawan_id')) {
            $jatahsQuery->where('karyawan_id', $request->karyawan_id);
        }
        $jatahs = $jatahsQuery->get()->keyBy('karyawan_id');
        
        $countsQuery = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
            ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
            ->where('cutis.tahun', $tahun)
            ->whereIn('cuti_details.kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL']);

        if ($request->has('karyawan_id')) {
            $countsQuery->where('cutis.karyawan_id', $request->karyawan_id);
        }

        $cutiCounts = $countsQuery->groupBy('cutis.karyawan_id')
            ->selectRaw("
                cutis.karyawan_id,
                SUM(CASE WHEN cuti_details.kategori IN ('TAHUNAN', 'IJIN') THEN 1 ELSE 0 END) as sudah_diambil,
                SUM(CASE WHEN cuti_details.kategori = 'CUTI_MASAL' THEN 1 ELSE 0 END) as cuti_masal
            ")
            ->get()
            ->keyBy('karyawan_id');

        $result = [];
        foreach($karyawans as $k) {
            $jatah = $jatahs[$k->id] ?? null;
            $totalHakCuti = $jatah ? ($jatah->jumlah_cuti + $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
            $counts = $cutiCounts->get($k->id);
            $sudahDiambil = $counts ? (int)$counts->sudah_diambil : 0;
            $cutiMasal = $counts ? (int)$counts->cuti_masal : 0;
            $belumDiambil = $totalHakCuti - $sudahDiambil - $cutiMasal;
            
            $result[] = [
                'karyawan_id' => (string)$k->id,
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
            'karyawan_id' => 'required|exists:karyawans,id',
            'jenis_form' => 'required|in:CUTI,IJIN,CUTI_MASAL',
            
            'tanggal_kembali' => 'nullable|string',
            'tahun' => 'required|integer',
            'details' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $cutiId = $request->id;
            if ($cutiId) {
                $cuti = Cuti::findOrFail($cutiId);
                $cuti->update([
                    'karyawan_id' => $request->karyawan_id,
                    'jenis_form' => $request->jenis_form,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $request->tahun,
                ]);
                
                $submittedDetailIds = array_filter(array_column($request->details, 'id'));
                if (!empty($submittedDetailIds)) {
                    CutiDetail::where('cuti_id', $cuti->id)->whereNotIn('id', $submittedDetailIds)->delete();
                } else {
                    CutiDetail::where('cuti_id', $cuti->id)->delete();
                }

                foreach ($request->details as $detail) {
                    if (!empty($detail['id'])) {
                        $cd = CutiDetail::find($detail['id']);
                        if ($cd) {
                            $cd->update([
                                'kategori' => $detail['kategori'],
                                'jenis_cuti_khusus_id' => $detail['jenis_cuti_khusus_id'] ?? null,
                                'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                                'keterangan' => $detail['keterangan'] ?? null,
                            ]);
                        }
                    } else {
                        $cd = CutiDetail::create([
                            'cuti_id' => $cuti->id,
                            'kategori' => $detail['kategori'],
                            'jenis_cuti_khusus_id' => $detail['jenis_cuti_khusus_id'] ?? null,
                            'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                            'keterangan' => $detail['keterangan'] ?? null,
                            'lama_hari' => $detail['lama_hari'] ?? null,
                        ]);
        
                        if (!empty($detail['dates'])) {
                            foreach ($detail['dates'] as $date) {
                                CutiDate::create([
                                    'cuti_detail_id' => $cd->id,
                                    'tanggal' => $date,
                                ]);
                            }
                        }
                    }
                }
            } else {
                $karyawanId = $request->karyawan_id;
                $tahun = $request->tahun;

                // Calculate snapshot values once for TAHUNAN/IJIN categories
                $jatahSnap = JatahCutiTahunan::where('karyawan_id', $karyawanId)
                    ->where('tahun', $tahun)->first();
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $cutiCounts = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
                    ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
                    ->where('cutis.karyawan_id', $karyawanId)
                    ->where('cutis.tahun', $tahun)
                    ->whereIn('cuti_details.kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
                    ->selectRaw("
                        SUM(CASE WHEN cuti_details.kategori IN ('TAHUNAN', 'IJIN') THEN 1 ELSE 0 END) as sudah_diambil,
                        SUM(CASE WHEN cuti_details.kategori = 'CUTI_MASAL' THEN 1 ELSE 0 END) as cuti_masal
                    ")
                    ->first();
                $snapSudahDiambil = $cutiCounts ? (int)$cutiCounts->sudah_diambil : 0;
                $snapCutiMasal = $cutiCounts ? (int)$cutiCounts->cuti_masal : 0;

                $cuti = Cuti::create([
                    'karyawan_id' => $karyawanId,
                    'jenis_form' => $request->jenis_form,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $tahun,
                ]);

                foreach ($request->details as $detail) {
                    $isSnapshotKategori = in_array($detail['kategori'], ['TAHUNAN', 'IJIN', 'CUTI_MASAL']);
                    $cd = CutiDetail::create([
                        'cuti_id' => $cuti->id,
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
                            CutiDate::create([
                                'cuti_detail_id' => $cd->id,
                                'tanggal' => $date,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $cuti]);
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
            'karyawans' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $cutiMasalModel = CutiMasal::create([
                'tahun' => $request->tahun,
                'keterangan' => $request->keterangan,
                'tanggal_kembali' => $request->tanggal_kembali ?? null,
                'lama_hari' => $request->lama_hari ?? 0,
            ]);

            if (!empty($request->global_dates)) {
                foreach($request->global_dates as $dt) {
                    CutiMasalDate::create([
                        'cuti_masal_id' => $cutiMasalModel->id,
                        'tanggal' => $dt,
                    ]);
                }
            }

            $karyawanIds = collect($request->karyawans)->pluck('karyawan_id')->unique()->filter()->values();
            $tahun = $request->tahun;

            $jatahSnaps = JatahCutiTahunan::whereIn('karyawan_id', $karyawanIds)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy('karyawan_id');

            $sudahDiambilCounts = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
                ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
                ->whereIn('cutis.karyawan_id', $karyawanIds)
                ->where('cutis.tahun', $tahun)
                ->whereIn('cuti_details.kategori', ['TAHUNAN', 'IJIN'])
                ->groupBy('cutis.karyawan_id')
                ->selectRaw('cutis.karyawan_id, count(*) as total')
                ->pluck('total', 'karyawan_id');

            $cutiMasalCounts = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
                ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
                ->whereIn('cutis.karyawan_id', $karyawanIds)
                ->where('cutis.tahun', $tahun)
                ->where('cuti_details.kategori', 'CUTI_MASAL')
                ->groupBy('cutis.karyawan_id')
                ->selectRaw('cutis.karyawan_id, count(*) as total')
                ->pluck('total', 'karyawan_id');

            foreach($request->karyawans as $k) {
                $karyawanId = $k['karyawan_id'];

                $jatahSnap = $jatahSnaps->get($karyawanId);
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $snapSudahDiambil = (int)($sudahDiambilCounts[$karyawanId] ?? 0);
                $snapCutiMasal = (int)($cutiMasalCounts[$karyawanId] ?? 0);

                $cuti = Cuti::create([
                    'karyawan_id' => $karyawanId,
                    'jenis_form' => 'CUTI_MASAL',
                    'tanggal_kembali' => $request->tanggal_kembali ?? null,
                    'tahun' => $tahun,
                    'cuti_masal_id' => $cutiMasalModel->id,
                ]);
                foreach($k['details'] as $detail) {
                    $isSnapshotKategori = ($detail['kategori'] == 'CUTI_MASAL');
                    $cd = CutiDetail::create([
                        'cuti_id' => $cuti->id,
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
                            CutiDate::create([
                                'cuti_detail_id' => $cd->id,
                                'tanggal' => $dt
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Berhasil generate cuti masal']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        $cuti = Cuti::find($id);
        if ($cuti) {
            $cuti->delete();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus']);
        }
        return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
    }

    public function detailMasal($id)
    {
        $masal = CutiMasal::with(['dates', 'cutis.karyawan.jabatan', 'cutis.karyawan.divisi', 'cutis.karyawan.statusKerja', 'cutis.karyawan.area', 'cutis.details.dates', 'cutis.details.jenisKhusus'])->find($id);
        if (!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'data' => $masal]);
    }

    public function updateKeteranganMasal(Request $request, $id)
    {
        $masal = CutiMasal::find($id);
        if (!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        $masal->keterangan = $request->keterangan;
        $masal->save();
        return response()->json(['status' => 'success']);
    }

    public function deleteMasal($id)
    {
        $masal = CutiMasal::find($id);
        if (!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        $cutis = Cuti::where('cuti_masal_id', $id)->get();
        foreach($cutis as $c) {
            $c->delete();
        }
        $masal->delete();
        return response()->json(['status' => 'success']);
    }

    public function updateKeteranganDetail(Request $request, $id)
    {
        $detail = CutiDetail::find($id);
        if (!$detail) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        $detail->keterangan = $request->keterangan;
        $detail->save();
        return response()->json(['status' => 'success']);
    }
    public function addKaryawanMasal(Request $request, $id)
    {
        $this->validate($request, [
            'karyawans' => 'required|array'
        ]);

        $masal = CutiMasal::find($id);
        if (!$masal) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        DB::beginTransaction();
        try {
            $karyawanIds = collect($request->karyawans)->pluck('karyawan_id')->unique()->filter()->values();
            $tahun = $masal->tahun;

            $jatahSnaps = JatahCutiTahunan::whereIn('karyawan_id', $karyawanIds)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy('karyawan_id');

            $cutiCounts = CutiDate::join('cuti_details', 'cuti_dates.cuti_detail_id', '=', 'cuti_details.id')
                ->join('cutis', 'cuti_details.cuti_id', '=', 'cutis.id')
                ->whereIn('cutis.karyawan_id', $karyawanIds)
                ->where('cutis.tahun', $tahun)
                ->whereIn('cuti_details.kategori', ['TAHUNAN', 'IJIN', 'CUTI_MASAL'])
                ->groupBy('cutis.karyawan_id')
                ->selectRaw("
                    cutis.karyawan_id,
                    SUM(CASE WHEN cuti_details.kategori IN ('TAHUNAN', 'IJIN') THEN 1 ELSE 0 END) as sudah_diambil,
                    SUM(CASE WHEN cuti_details.kategori = 'CUTI_MASAL' THEN 1 ELSE 0 END) as cuti_masal
                ")
                ->get()
                ->keyBy('karyawan_id');

            $existingCutiKaryawanIds = Cuti::whereIn('karyawan_id', $karyawanIds)
                ->where('cuti_masal_id', $id)
                ->pluck('karyawan_id')
                ->flip();

            foreach($request->karyawans as $k) {
                $karyawanId = $k['karyawan_id'];
                
                // check if already added to this mass leave
                if (isset($existingCutiKaryawanIds[$karyawanId])) continue;

                $jatahSnap = $jatahSnaps->get($karyawanId);
                $snapTotalHakCuti = $jatahSnap
                    ? ($jatahSnap->jumlah_cuti + $jatahSnap->plus_tahun_lalu - $jatahSnap->min_tahun_lalu)
                    : 0;
                $counts = $cutiCounts->get($karyawanId);
                $snapSudahDiambil = $counts ? (int)$counts->sudah_diambil : 0;
                $snapCutiMasal = $counts ? (int)$counts->cuti_masal : 0;

                $cuti = Cuti::create([
                    'karyawan_id' => $karyawanId,
                    'jenis_form' => 'CUTI_MASAL',
                    'tanggal_kembali' => $masal->tanggal_kembali,
                    'tahun' => $tahun,
                    'cuti_masal_id' => $masal->id,
                ]);
                foreach($k['details'] as $detail) {
                    $isSnapshotKategori = ($detail['kategori'] == 'CUTI_MASAL');
                    $cd = CutiDetail::create([
                        'cuti_id' => $cuti->id,
                        'kategori' => $detail['kategori'],
                        'jenis_unpaid' => $detail['jenis_unpaid'] ?? null,
                        'lama_hari' => $detail['lama_hari'],
                        'keterangan' => $detail['keterangan'] ?? $masal->keterangan,
                        'snap_total_hak_cuti' => $isSnapshotKategori ? $snapTotalHakCuti : null,
                        'snap_sudah_diambil'  => $isSnapshotKategori ? $snapSudahDiambil  : null,
                        'snap_cuti_masal'     => $isSnapshotKategori ? $snapCutiMasal     : null,
                    ]);
                    if(!empty($detail['dates'])) {
                        foreach($detail['dates'] as $dt) {
                            CutiDate::create([
                                'cuti_detail_id' => $cd->id,
                                'tanggal' => $dt
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }}
