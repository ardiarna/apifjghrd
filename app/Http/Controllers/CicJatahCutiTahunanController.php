<?php

namespace App\Http\Controllers;

use App\Repositories\CicJatahCutiTahunanRepository;
use App\Traits\ApiResponser;
use App\Models\CicCutiDate;
use Illuminate\Http\Request;

class CicJatahCutiTahunanController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(CicJatahCutiTahunanRepository $repo)
    {
        $this->repo = $repo;
    }

    
    public function hitungSisa(Request $req)
    {
        $karyawanId = $req->query('cic_karyawan_id');
        $tahun = $req->query('tahun');
        
        $plusTahunLalu = 0;
        $minTahunLalu = 0;

        $exists = false;
        if ($karyawanId && $tahun) {
            $exists = $this->repo->findAll([
                'cic_karyawan_id' => $karyawanId,
                'tahun'       => $tahun,
            ])->isNotEmpty();

            $tahunLalu = (string)((int)$tahun - 1);
            $jatahLalu = $this->repo->findAll([
                'cic_karyawan_id' => $karyawanId,
                'tahun'       => $tahunLalu,
            ])->first();

            if ($jatahLalu) {
                $diambilLalu = CicCutiDate::whereHas('cicCutiDetail', function ($q) use ($karyawanId, $tahunLalu) {
                    $q->whereIn('kategori', ['TAHUNAN', 'IJIN'])
                      ->whereHas('cicCuti', function ($q2) use ($karyawanId, $tahunLalu) {
                          $q2->where('cic_karyawan_id', $karyawanId)
                             ->where('tahun', (int)$tahunLalu);
                      });
                })->count();
                
                $cutiMasalLalu = CicCutiDate::whereHas('cicCutiDetail', function ($q) use ($karyawanId, $tahunLalu) {
                    $q->where('kategori', 'CUTI_MASAL')
                      ->whereHas('cicCuti', function ($q2) use ($karyawanId, $tahunLalu) {
                          $q2->where('cic_karyawan_id', $karyawanId)
                             ->where('tahun', (int)$tahunLalu);
                      });
                })->count();

                $sisaCuti = $jatahLalu->total_cuti - $diambilLalu - $cutiMasalLalu;

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
                'min_tahun_lalu' => $minTahunLalu,
                'exists' => $exists
            ]
        ], 200);
    }

    public function findAll(Request $req)
    {
        $data = $this->repo->findAll([
            'cic_karyawan_id' => $req->query('cic_karyawan_id'),
            'tahun'       => $req->query('tahun'),
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req)
    {
        $this->validate($req, [
            'cic_karyawan_id' => 'required|exists:karyawans,id',
            'tahun'       => 'required|string|max:4',
        ]);

        $karyawanId  = $req->input('cic_karyawan_id');
        $tahun       = $req->input('tahun');
        $jumlahCuti  = $req->input('jumlah_cuti', 0);
        $plusTahunLalu = $req->input('plus_tahun_lalu', 0);
        $minTahunLalu  = $req->input('min_tahun_lalu', 0);
        $bolehMinus    = $req->input('boleh_minus', 'N');

        $totalCuti = $jumlahCuti + $plusTahunLalu - $minTahunLalu;

        $data = $this->repo->create([
            'boleh_minus'     => $bolehMinus,
            'cic_karyawan_id'     => $karyawanId,
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
        $bolehMinus    = $req->input('boleh_minus', $existing->boleh_minus);
        $totalCuti     = $jumlahCuti + $plusTahunLalu - $minTahunLalu;

        $data = $this->repo->update($id, [
            'boleh_minus'     => $bolehMinus,
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
