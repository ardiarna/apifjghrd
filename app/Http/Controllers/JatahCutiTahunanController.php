<?php

namespace App\Http\Controllers;

use App\Repositories\JatahCutiTahunanRepository;
use App\Traits\ApiResponser;
use App\Models\CutiDate;
use Illuminate\Http\Request;

class JatahCutiTahunanController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(JatahCutiTahunanRepository $repo)
    {
        $this->repo = $repo;
    }

    
    public function hitungSisa(Request $req)
    {
        $karyawanId = $req->query('karyawan_id');
        $tahun = $req->query('tahun');
        
        $plusTahunLalu = 0;
        $minTahunLalu = 0;

        if ($karyawanId && $tahun) {
            $tahunLalu = (string)((int)$tahun - 1);
            $jatahLalu = $this->repo->findAll([
                'karyawan_id' => $karyawanId,
                'tahun'       => $tahunLalu,
            ])->first();

            if ($jatahLalu) {
                $diambilLalu = CutiDate::whereHas('cutiDetail', function ($q) use ($karyawanId, $tahunLalu) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cuti', function ($q2) use ($karyawanId, $tahunLalu) {
                          $q2->where('karyawan_id', $karyawanId)
                             ->where('tahun', (int)$tahunLalu);
                      });
                })->count();

                $sisaCuti = $jatahLalu->total_cuti - $diambilLalu;

                if ($sisaCuti > 0) {
                    $plusTahunLalu = $sisaCuti;
                } elseif ($sisaCuti < 0) {
                    $minTahunLalu = abs($sisaCuti);
                }
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => [
                'plus_tahun_lalu' => $plusTahunLalu, 
                'min_tahun_lalu' => $minTahunLalu
            ]
        ], 200);
    }

    public function findAll(Request $req)
    {
        $data = $this->repo->findAll([
            'karyawan_id' => $req->query('karyawan_id'),
            'tahun'       => $req->query('tahun'),
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req)
    {
        $this->validate($req, [
            'karyawan_id' => 'required|exists:karyawans,id',
            'tahun'       => 'required|string|max:4',
        ]);

        $karyawanId  = $req->input('karyawan_id');
        $tahun       = $req->input('tahun');
        $jumlahCuti  = $req->input('jumlah_cuti', 12);
        $plusTahunLalu = $req->input('plus_tahun_lalu', 0);
        $minTahunLalu  = $req->input('min_tahun_lalu', 0);

        $totalCuti = $jumlahCuti + $plusTahunLalu - $minTahunLalu;

        $data = $this->repo->create([
            'karyawan_id'     => $karyawanId,
            'tahun'           => $tahun,
            'jumlah_cuti'     => $jumlahCuti,
            'plus_tahun_lalu' => $plusTahunLalu,
            'min_tahun_lalu'  => $minTahunLalu,
            'total_cuti'      => $totalCuti,
        ]);

        return $this->createdResponse($data, 'Jatah cuti tahunan berhasil dibuat');
    }

    public function update(Request $req, $id)
    {
        $this->validate($req, [
            'jumlah_cuti' => 'required|integer|min:0',
        ]);

        $existing = $this->repo->findById($id);
        if (!$existing) {
            return $this->failRespNotFound('Jatah cuti tahunan tidak ditemukan');
        }

        $jumlahCuti    = $req->input('jumlah_cuti');
        $plusTahunLalu = $req->input('plus_tahun_lalu', $existing->plus_tahun_lalu);
        $minTahunLalu  = $req->input('min_tahun_lalu', $existing->min_tahun_lalu);
        $totalCuti     = $jumlahCuti + $plusTahunLalu - $minTahunLalu;

        $data = $this->repo->update($id, [
            'jumlah_cuti'     => $jumlahCuti,
            'plus_tahun_lalu' => $plusTahunLalu,
            'min_tahun_lalu'  => $minTahunLalu,
            'total_cuti'      => $totalCuti,
        ]);

        return $this->successResponse($data, 'Jatah cuti tahunan berhasil diubah');
    }

    public function delete($id)
    {
        $data = $this->repo->delete($id);
        if ($data == 0) {
            return $this->failRespNotFound('Jatah cuti tahunan tidak ditemukan');
        }
        return $this->successResponse($data, 'Jatah cuti tahunan berhasil dihapus');
    }
}
