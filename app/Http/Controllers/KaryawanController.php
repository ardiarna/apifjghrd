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
        $data = $this->repo->findAll([
            'aktif' => $req->query('aktif'),
            'staf' => $req->query('staf'),
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
            'nomor_ktp' => 'digits:16',
            'tanggal_masuk' => 'required|date',
            'area_id' => 'required',
            'jabatan_id' => 'required',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'alamat_ktp' => 'required',
            'telepon' => 'required|numeric',
            'email' => 'email',
            'kawin' => 'in:Y,N',
            'kelamin' => 'required|in:L,P',
            'staf' => 'required|in:Y,N',
        ]);
        $inputs = $req->only([
            'nama', 'nomor_ktp', 'tanggal_masuk', 'area_id', 'jabatan_id',
            'tempat_lahir', 'tanggal_lahir', 'alamat_ktp', 'telepon', 'kelamin', 'staf'
        ]);
        $inputs['nik'] = $req->input('nik');
        $inputs['agama_id'] = $req->input('agama_id');
        $inputs['divisi_id'] = $req->input('divisi_id');
        $inputs['alamat_tinggal'] = $req->input('alamat_tinggal');
        $inputs['email'] = $req->input('email');
        $inputs['kawin'] = $req->input('kawin');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['ptkp_id'] = $req->input('ptkp_id');
        $inputs['pendidikan_id'] = $req->input('pendidikan_id');
        $inputs['pendidikan_almamater'] = $req->input('pendidikan_almamater');
        $inputs['pendidikan_jurusan'] = $req->input('pendidikan_jurusan');
        $inputs['aktif'] = 'Y';
        $inputs['nomor_kk'] = $req->input('nomor_kk');
        $inputs['nomor_paspor'] = $req->input('nomor_paspor');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Karyawan berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'nomor_ktp' => 'digits:16',
            'tanggal_masuk' => 'date',
            'tanggal_lahir' => 'date',
            'telepon' => 'numeric',
            'email' => 'email',
            'kawin' => 'in:Y,N',
            'aktif' => 'in:Y,N',
            'kelamin' => 'in:L,P',
            'staf' => 'in:Y,N',
        ]);
        $inputs['nama'] = $req->input('nama');
        $inputs['nik'] = $req->input('nik');
        $inputs['nomor_ktp'] = $req->input('nomor_ktp');
        $inputs['tanggal_masuk'] = $req->input('tanggal_masuk');
        $inputs['tanggal_keluar'] = $req->input('tanggal_keluar');
        $inputs['agama_id'] = $req->input('agama_id');
        $inputs['area_id'] = $req->input('area_id');
        $inputs['jabatan_id'] = $req->input('jabatan_id');
        $inputs['divisi_id'] = $req->input('divisi_id');
        $inputs['tempat_lahir'] = $req->input('tempat_lahir');
        $inputs['tanggal_lahir'] = $req->input('tanggal_lahir');
        $inputs['alamat_ktp'] = $req->input('alamat_ktp');
        $inputs['alamat_tinggal'] = $req->input('alamat_tinggal');
        $inputs['telepon'] = $req->input('telepon');
        $inputs['email'] = $req->input('email');
        $inputs['kawin'] = $req->input('kawin');
        $inputs['kelamin'] = $req->input('kelamin');
        $inputs['status_kerja_id'] = $req->input('status_kerja_id');
        $inputs['ptkp_id'] = $req->input('ptkp_id');
        $inputs['pendidikan_id'] = $req->input('pendidikan_id');
        $inputs['pendidikan_almamater'] = $req->input('pendidikan_almamater');
        $inputs['pendidikan_jurusan'] = $req->input('pendidikan_jurusan');
        $inputs['aktif'] = $req->input('aktif');
        $inputs['staf'] = $req->input('staf');
        $inputs['nomor_kk'] = $req->input('nomor_kk');
        $inputs['nomor_paspor'] = $req->input('nomor_paspor');
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
