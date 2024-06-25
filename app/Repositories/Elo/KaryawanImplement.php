<?php

namespace App\Repositories\Elo;

use App\Repositories\KaryawanRepository;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;

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
        if(isset($inputs['nama'])) {
            $model->nama = $inputs['nama'];
        }
        if(isset($inputs['nik'])) {
            $model->nik = $inputs['nik'];
        }
        if(isset($inputs['nomor_ktp'])) {
            $model->nomor_ktp = $inputs['nomor_ktp'];
        }
        if(isset($inputs['tanggal_masuk'])) {
            $model->tanggal_masuk = $inputs['tanggal_masuk'];
        }
        if(isset($inputs['tanggal_keluar'])) {
            $model->tanggal_keluar = $inputs['tanggal_keluar'];
        }
        if(isset($inputs['agama_id'])) {
            $model->agama_id = $inputs['agama_id'];
        }
        if(isset($inputs['area_id'])) {
            $model->area_id = $inputs['area_id'];
        }
        if(isset($inputs['jabatan_id'])) {
            $model->jabatan_id = $inputs['jabatan_id'];
        }
        if(isset($inputs['divisi_id'])) {
            $model->divisi_id = $inputs['divisi_id'];
        }
        if(isset($inputs['tempat_lahir'])) {
            $model->tempat_lahir = $inputs['tempat_lahir'];
        }
        if(isset($inputs['tanggal_lahir'])) {
            $model->tanggal_lahir = $inputs['tanggal_lahir'];
        }
        if(isset($inputs['alamat_ktp'])) {
            $model->alamat_ktp = $inputs['alamat_ktp'];
        }
        if(isset($inputs['alamat_tinggal'])) {
            $model->alamat_tinggal = $inputs['alamat_tinggal'];
        }
        if(isset($inputs['telepon'])) {
            $model->telepon = $inputs['telepon'];
        }
        if(isset($inputs['email'])) {
            $model->email = $inputs['email'];
        }
        if(isset($inputs['kawin'])) {
            $model->kawin = $inputs['kawin'];
        }
        if(isset($inputs['kelamin'])) {
            $model->kelamin = $inputs['kelamin'];
        }
        if(isset($inputs['status_kerja_id'])) {
            $model->status_kerja_id = $inputs['status_kerja_id'];
        }
        if(isset($inputs['ptkp_id'])) {
            $model->ptkp_id = $inputs['ptkp_id'];
        }
        if(isset($inputs['pendidikan_id'])) {
            $model->pendidikan_id = $inputs['pendidikan_id'];
        }
        if(isset($inputs['pendidikan_almamater'])) {
            $model->pendidikan_almamater = $inputs['pendidikan_almamater'];
        }
        if(isset($inputs['pendidikan_jurusan'])) {
            $model->pendidikan_jurusan = $inputs['pendidikan_jurusan'];
        }
        if(isset($inputs['aktif'])) {
            $model->aktif = $inputs['aktif'];
        }
        if(isset($inputs['staf'])) {
            $model->staf = $inputs['staf'];
        }
        if(isset($inputs['nomor_kk'])) {
            $model->nomor_kk = $inputs['nomor_kk'];
        }
        if(isset($inputs['nomor_paspor'])) {
            $model->nomor_paspor = $inputs['nomor_paspor'];
        }
        if(isset($inputs['nomor_pwp'])) {
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

    public function rekapKaryawanByAreaAndKelamin() {
        $hasil = $this->model->select('areas.nama as area_name', 'karyawans.kelamin', DB::raw('COUNT(*) as total'))
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->where('karyawans.aktif', 'Y')
            ->where('karyawans.staf', 'Y')
            ->groupBy('areas.nama', 'karyawans.kelamin', 'areas.urutan')
            ->orderBy('areas.urutan')
            ->get();
        return $hasil;
    }

}
