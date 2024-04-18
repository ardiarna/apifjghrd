<?php

namespace App\Http\Controllers;

use App\Repositories\KaryawanRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(KaryawanRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll(['sort_by' => $req->query('sort_by'), 'sort_order' => $req->query('sort_order')]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
            'no_ktp' => 'digits:16',
            'tanggal_masuk' => 'required|date',
            'jabatan_id' => 'required',
            'divisi_id' => 'required',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'alamat_ktp' => 'required',
            'telepon' => 'required|numeric',
            'email' => 'email',
            'kawin' => 'in:Y,N'
        ]);
        $inputs = $req->only(['nama', 'no_ktp', 'tanggal_masuk', 'jabatan_id', 'divisi_id', 'tempat_lahir', 'tanggal_lahir', 'alamat_ktp', 'telepon']);
        $inputs['nik'] = $req->input('nik');
        $inputs['agama_id'] = $req->input('agama_id');
        $inputs['alamat_tinggal'] = $req->input('alamat_tinggal');
        $inputs['email'] = $req->input('email');
        $inputs['kawin'] = $req->input('kawin');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['pendidikan_id'] = $req->input('pendidikan_id');
        $inputs['pendidikan_almamater'] = $req->input('pendidikan_almamater');
        $inputs['pendidikan_jurusan'] = $req->input('pendidikan_jurusan');
        $inputs['aktif'] = 'Y';
        $inputs['no_kk'] = $req->input('no_kk');
        $inputs['no_paspor'] = $req->input('no_paspor');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Karyawan berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'no_ktp' => 'digits:16',
            'tanggal_masuk' => 'date',
            'tanggal_lahir' => 'date',
            'telepon' => 'numeric',
            'email' => 'email',
            'kawin' => 'in:Y,N',
            'aktif' => 'in:Y,N'
        ]);
        $inputs['nama'] = $req->input('nama');
        $inputs['nik'] = $req->input('nik');
        $inputs['no_ktp'] = $req->input('no_ktp');
        $inputs['tanggal_masuk'] = $req->input('tanggal_masuk');
        $inputs['agama_id'] = $req->input('agama_id');
        $inputs['jabatan_id'] = $req->input('jabatan_id');
        $inputs['divisi_id'] = $req->input('divisi_id');
        $inputs['tempat_lahir'] = $req->input('tempat_lahir');
        $inputs['tanggal_lahir'] = $req->input('tanggal_lahir');
        $inputs['alamat_ktp'] = $req->input('alamat_ktp');
        $inputs['alamat_tinggal'] = $req->input('alamat_tinggal');
        $inputs['telepon'] = $req->input('telepon');
        $inputs['email'] = $req->input('email');
        $inputs['kawin'] = $req->input('kawin');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['pendidikan_id'] = $req->input('pendidikan_id');
        $inputs['pendidikan_almamater'] = $req->input('pendidikan_almamater');
        $inputs['pendidikan_jurusan'] = $req->input('pendidikan_jurusan');
        $inputs['aktif'] = $req->input('aktif');
        $inputs['no_kk'] = $req->input('no_kk');
        $inputs['no_paspor'] = $req->input('no_paspor');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Karyawan berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Karyawan tidak ditemukan');
        }
        return $this->successResponse($data, 'Karyawan berhasil dihapus');
    }

}
