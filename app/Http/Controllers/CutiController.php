<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\CutiDetail;
use App\Models\CutiDate;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CutiController extends Controller
{
    public function findAll(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $data = Cuti::with(['karyawan', 'details.dates'])->where('tahun', $tahun)->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'message' => 'success', 'data' => $data], 200);
    }

    public function info(Request $request)
    {
        $karyawanId = $request->karyawan_id;
        $tahun = $request->tahun ?? date('Y');
        
        $karyawan = \App\Models\Karyawan::with(['jabatan', 'divisi'])->find($karyawanId);
        if (!$karyawan) {
            return response()->json(['message' => 'Karyawan not found'], 404);
        }

        $jatah = \App\Models\JatahCutiTahunan::where('karyawan_id', $karyawanId)
            ->where('tahun', $tahun)
            ->first();

        $totalHakCuti = 0;
        $sisaCutiTahunLalu = 0;
        $hakCuti = 0;
        if ($jatah) {
            $totalHakCuti = $jatah->jumlah_cuti + $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu;
            $sisaCutiTahunLalu = $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu;
            $hakCuti = $jatah->jumlah_cuti;
        }

        $sudahDiambil = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($karyawanId, $tahun) {
            $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
              ->whereHas('cuti', function($q2) use ($karyawanId, $tahun) {
                  $q2->where('karyawan_id', $karyawanId)->where('tahun', $tahun);
              });
        })->count();

        $cutiMasal = \App\Models\CutiDate::whereHas('cutiDetail', function($q) use ($karyawanId, $tahun) {
            $q->where('kategori', 'CUTI_MASAL')
              ->whereHas('cuti', function($q2) use ($karyawanId, $tahun) {
                  $q2->where('karyawan_id', $karyawanId)->where('tahun', $tahun);
              });
        })->count();

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
                ]
            ]
        ], 200);
    }

    public function infoMasal(Request $request)
    {
        $tahun = $request->tahun ?? date('Y');
        $karyawans = \App\Models\Karyawan::with(['jabatan'])
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->select('karyawans.*')
            ->where('karyawans.aktif', 'Y')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan')
            ->orderBy('karyawans.id')
            ->get();
        
        $jatahs = \App\Models\JatahCutiTahunan::where('tahun', $tahun)->get()->keyBy('karyawan_id');
        
        $cutiDates = \App\Models\CutiDate::whereHas('cutiDetail.cuti', function($q) use ($tahun) {
            $q->where('tahun', $tahun);
        })->with('cutiDetail.cuti')->get();

        $sudahDiambilMap = [];
        $cutiMasalMap = [];

        foreach($cutiDates as $cd) {
            $cuti = $cd->cutiDetail->cuti;
            $kId = $cuti->karyawan_id;
            
            if ($cd->cutiDetail->kategori == 'CUTI_MASAL') {
                if (!isset($cutiMasalMap[$kId])) $cutiMasalMap[$kId] = 0;
                $cutiMasalMap[$kId]++;
            } elseif (in_array($cd->cutiDetail->kategori, ['TAHUNAN', 'IJIN'])) {
                if (!isset($sudahDiambilMap[$kId])) $sudahDiambilMap[$kId] = 0;
                $sudahDiambilMap[$kId]++;
            }
        }

        $result = [];
        foreach($karyawans as $k) {
            $jatah = $jatahs[$k->id] ?? null;
            $totalHakCuti = $jatah ? ($jatah->jumlah_cuti + $jatah->plus_tahun_lalu - $jatah->min_tahun_lalu) : 0;
            $sudahDiambil = $sudahDiambilMap[$k->id] ?? 0;
            $cutiMasal = $cutiMasalMap[$k->id] ?? 0;
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
            ];
        }
        
        return response()->json(['status' => 'success', 'message' => 'Berhasil mengambil data', 'data' => $result]);
    }

    public function submit(Request $request)
    {
        $this->validate($request, [
            'karyawan_id' => 'required|exists:karyawans,id',
            'jenis_form' => 'required|in:CUTI,IJIN,UNPAID_LEAVE,CUTI_MASAL',
            'keperluan' => 'required|string',
            'tanggal_kembali' => 'nullable|date',
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
                    'keperluan' => $request->keperluan,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $request->tahun,
                ]);
                CutiDetail::where('cuti_id', $cuti->id)->delete();
            } else {
                $cuti = Cuti::create([
                    'karyawan_id' => $request->karyawan_id,
                    'jenis_form' => $request->jenis_form,
                    'keperluan' => $request->keperluan,
                    'tanggal_kembali' => $request->tanggal_kembali,
                    'tahun' => $request->tahun,
                ]);
            }

            foreach ($request->details as $detail) {
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
            'keperluan' => 'required',
            'karyawans' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            foreach($request->karyawans as $k) {
                $cuti = Cuti::create([
                    'karyawan_id' => $k['karyawan_id'],
                    'jenis_form' => 'CUTI_MASAL',
                    'keperluan' => $request->keperluan,
                    'tanggal_kembali' => $request->tanggal_kembali ?? null,
                    'tahun' => $request->tahun,
                ]);
                foreach($k['details'] as $detail) {
                    $cd = CutiDetail::create([
                        'cuti_id' => $cuti->id,
                        'kategori' => $detail['kategori'],
                        'lama_hari' => $detail['lama_hari'],
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
}
