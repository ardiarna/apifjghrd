<?php

namespace App\Http\Controllers;

use App\Repositories\UangPhkRepository;
use App\Repositories\KaryawanRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UangPhkController extends Controller
{
    use ApiResponser;

    protected $repo, $karyawanRepo;

    public function __construct(UangPhkRepository $repo, KaryawanRepository $karyawanRepo) {
        $this->repo = $repo;
        $this->karyawanRepo = $karyawanRepo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Uang PHK dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'tahun' => $req->query('tahun'),
            'karyawan_id' => $req->query('karyawan_id')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'tahun' => 'required|integer',
            'kompensasi' => 'numeric',
            'uang_pisah' => 'numeric',
            'pesangon' => 'numeric',
            'masa_kerja' => 'numeric',
            'penggantian_hak' => 'numeric',
            'sisa_cuti_hari' => 'numeric',
            'sisa_cuti_jumlah' => 'numeric',
            'lain' => 'numeric',
            'pot_kas' => 'numeric',
            'pot_cicilan' => 'numeric',
            'pot_cuti_hari' => 'numeric',
            'pot_cuti_jumlah' => 'numeric',
            'pot_lain' => 'numeric'
        ]);
        $inputs = $req->only(['karyawan_id', 'tahun']);
        $inputs['kompensasi'] = $req->input('kompensasi');
        $inputs['uang_pisah'] = $req->input('uang_pisah');
        $inputs['pesangon'] = $req->input('pesangon');
        $inputs['masa_kerja'] = $req->input('masa_kerja');
        $inputs['penggantian_hak'] = $req->input('penggantian_hak');
        $inputs['sisa_cuti_hari'] = $req->input('sisa_cuti_hari');
        $inputs['sisa_cuti_jumlah'] = $req->input('sisa_cuti_jumlah');
        $inputs['lain'] = $req->input('lain');
        $inputs['pot_kas'] = $req->input('pot_kas');
        $inputs['pot_cicilan'] = $req->input('pot_cicilan');
        $inputs['pot_cuti_hari'] = $req->input('pot_cuti_hari');
        $inputs['pot_cuti_jumlah'] = $req->input('pot_cuti_jumlah');
        $inputs['pot_lain'] = $req->input('pot_lain');
        $inputs['keterangan'] = $req->input('keterangan');
        $inputs['ket_pesangon'] = $req->input('ket_pesangon');
        $inputs['ket_lain'] = $req->input('ket_lain');
        $inputs['ket_pot_lain'] = $req->input('ket_pot_lain');
        $data = $this->repo->create($inputs);
        if ($req->has('phk_id') && $req->input('phk_id')) {
            \App\Models\Phk::where('id', $req->input('phk_id'))->update(['uang_phk_id' => $data->id]);
        } else {
            $karyawan = \App\Models\Karyawan::find($data->karyawan_id);
            if ($karyawan && $karyawan->phk_id) {
                \App\Models\Phk::where('id', $karyawan->phk_id)->update(['uang_phk_id' => $data->id]);
            }
        }
        return $this->createdResponse($data, 'Uang PHK berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'karyawan_id' => 'required',
            'tahun' => 'required|integer',
            'kompensasi' => 'numeric',
            'uang_pisah' => 'numeric',
            'pesangon' => 'numeric',
            'masa_kerja' => 'numeric',
            'penggantian_hak' => 'numeric',
            'sisa_cuti_hari' => 'numeric',
            'sisa_cuti_jumlah' => 'numeric',
            'lain' => 'numeric',
            'pot_kas' => 'numeric',
            'pot_cicilan' => 'numeric',
            'pot_cuti_hari' => 'numeric',
            'pot_cuti_jumlah' => 'numeric',
            'pot_lain' => 'numeric'
        ]);
        $inputs = $req->only(['karyawan_id', 'tahun']);
        $inputs['kompensasi'] = $req->input('kompensasi');
        $inputs['uang_pisah'] = $req->input('uang_pisah');
        $inputs['pesangon'] = $req->input('pesangon');
        $inputs['masa_kerja'] = $req->input('masa_kerja');
        $inputs['penggantian_hak'] = $req->input('penggantian_hak');
        $inputs['sisa_cuti_hari'] = $req->input('sisa_cuti_hari');
        $inputs['sisa_cuti_jumlah'] = $req->input('sisa_cuti_jumlah');
        $inputs['lain'] = $req->input('lain');
        $inputs['pot_kas'] = $req->input('pot_kas');
        $inputs['pot_cicilan'] = $req->input('pot_cicilan');
        $inputs['pot_cuti_hari'] = $req->input('pot_cuti_hari');
        $inputs['pot_cuti_jumlah'] = $req->input('pot_cuti_jumlah');
        $inputs['pot_lain'] = $req->input('pot_lain');
        $inputs['keterangan'] = $req->input('keterangan');
        $inputs['ket_pesangon'] = $req->input('ket_pesangon');
        $inputs['ket_lain'] = $req->input('ket_lain');
        $inputs['ket_pot_lain'] = $req->input('ket_pot_lain');
        $data = $this->repo->update($id, $inputs);
        if ($req->has('phk_id') && $req->input('phk_id')) {
            \App\Models\Phk::where('id', $req->input('phk_id'))->update(['uang_phk_id' => $id]);
        } else {
            $karyawan = \App\Models\Karyawan::find($data->karyawan_id);
            if ($karyawan && $karyawan->phk_id) {
                \App\Models\Phk::where('id', $karyawan->phk_id)->update(['uang_phk_id' => $id]);
            }
        }
        return $this->successResponse($data, 'Uang PHK berhasil diubah');
    }

    public function delete($id) {
        \App\Models\Phk::where('uang_phk_id', $id)->update(['uang_phk_id' => null]);
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Uang PHK dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'Uang PHK berhasil dihapus');
    }

}
