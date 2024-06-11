<?php

namespace App\Repositories\Elo;

use App\Repositories\KaryawanRepository;
use App\Models\Karyawan;

class KaryawanImplement implements KaryawanRepository {

    protected $model;

    function __construct(Karyawan $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->query()->with(['area','jabatan','divisi','agama','pendidikan','statusKerja','phk','ptkp'])
            ->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['area','jabatan','divisi','agama','pendidikan','statusKerja','phk','ptkp'])
            ->select('karyawans.*')
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan');
        if(isset($inputs['aktif']) && $inputs['aktif'] != '') {
            $hasil->where('karyawans.aktif', $inputs['aktif']);
        }
        if(isset($inputs['staf']) && $inputs['staf'] != '') {
            $hasil->where('karyawans.staf', $inputs['staf']);
        }
        if(isset($inputs['search_by']) && isset($inputs['value']) && $inputs['search_by'] != '' && $inputs['value'] != '') {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if(isset($inputs['sort_by']) && $inputs['sort_by'] != '') {
            $sort_order = (isset($inputs['sort_order']) && $inputs['sort_order'] != '') ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        }
        $hasil->orderBy('karyawans.id');
        $hasil = $hasil->get();
        foreach ($hasil as $h) {
            if($h->phk != null) {
                $h->phk->statusPhk;
            }
        }
        return $hasil;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($inputs['nama'] != null) {
            $model->nama = $inputs['nama'];
        }
        if($inputs['nik'] != null) {
            $model->nik = $inputs['nik'];
        }
        if($inputs['nomor_ktp'] != null) {
            $model->nomor_ktp = $inputs['nomor_ktp'];
        }
        if($inputs['tanggal_masuk'] != null) {
            $model->tanggal_masuk = $inputs['tanggal_masuk'];
        }
        if($inputs['tanggal_keluar'] != null) {
            $model->tanggal_keluar = $inputs['tanggal_keluar'];
        }
        if($inputs['agama_id'] != null) {
            $model->agama_id = $inputs['agama_id'];
        }
        if($inputs['area_id'] != null) {
            $model->area_id = $inputs['area_id'];
        }
        if($inputs['jabatan_id'] != null) {
            $model->jabatan_id = $inputs['jabatan_id'];
        }
        if($inputs['divisi_id'] != null) {
            $model->divisi_id = $inputs['divisi_id'];
        }
        if($inputs['tempat_lahir'] != null) {
            $model->tempat_lahir = $inputs['tempat_lahir'];
        }
        if($inputs['tanggal_lahir'] != null) {
            $model->tanggal_lahir = $inputs['tanggal_lahir'];
        }
        if($inputs['alamat_ktp'] != null) {
            $model->alamat_ktp = $inputs['alamat_ktp'];
        }
        if($inputs['alamat_tinggal'] != null) {
            $model->alamat_tinggal = $inputs['alamat_tinggal'];
        }
        if($inputs['telepon'] != null) {
            $model->telepon = $inputs['telepon'];
        }
        if($inputs['email'] != null) {
            $model->email = $inputs['email'];
        }
        if($inputs['kawin'] != null) {
            $model->kawin = $inputs['kawin'];
        }
        if($inputs['kelamin'] != null) {
            $model->kelamin = $inputs['kelamin'];
        }
        if($inputs['status_kerja_id'] != null) {
            $model->status_kerja_id = $inputs['status_kerja_id'];
        }
        if($inputs['ptkp_id'] != null) {
            $model->ptkp_id = $inputs['ptkp_id'];
        }
        if($inputs['pendidikan_id'] != null) {
            $model->pendidikan_id = $inputs['pendidikan_id'];
        }
        if($inputs['pendidikan_almamater'] != null) {
            $model->pendidikan_almamater = $inputs['pendidikan_almamater'];
        }
        if($inputs['pendidikan_jurusan'] != null) {
            $model->pendidikan_jurusan = $inputs['pendidikan_jurusan'];
        }
        if($inputs['aktif'] != null) {
            $model->aktif = $inputs['aktif'];
        }
        if($inputs['staf'] != null) {
            $model->staf = $inputs['staf'];
        }
        if($inputs['nomor_kk'] != null) {
            $model->nomor_kk = $inputs['nomor_kk'];
        }
        if($inputs['nomor_paspor'] != null) {
            $model->nomor_paspor = $inputs['nomor_paspor'];
        }
        if($inputs['nomor_pwp'] != null) {
            $model->nomor_pwp = $inputs['nomor_pwp'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function setNonAktif($id, $phk_id, $tanggal_keluar) {
        $model = $this->model->findOrFail($id);
        $model->aktif = 'N';
        $model->tanggal_keluar = $tanggal_keluar;
        $model->phk_id = $phk_id;
        $model->save();
        return $model;
    }

}
