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
        $model = $this->model->query()
            ->select('karyawans.*')
            ->withCount(['anaks as jumlah_anak'])
            ->with(['area','jabatan','divisi','agama','pendidikan','statusKerja','phk.uangPhk','phk.payrollPhk','phk.statusPhk','phk.statusKerja','ptkp','upah'])
            ->find($id);
        if ($model) {
            $model->jumlah_anak = (int) $model->jumlah_anak;
        }
        return $model;
    }

    public function findAll($inputs = []) {
        $relations = [
            'area',
            'jabatan',
            'divisi',
            'agama',
            'pendidikan',
            'statusKerja',
            'ptkp',
            'upah',
        ];

        // Relasi PHK bertingkat hanya dimuat jika mengambil mantan karyawan ('N') atau jika status aktif tidak disaring
        $isAktif = isset($inputs['aktif']) ? $inputs['aktif'] : null;
        if ($isAktif === 'N' || empty($isAktif)) {
            $relations[] = 'phk.uangPhk';
            $relations[] = 'phk.payrollPhk';
            $relations[] = 'phk.statusPhk';
            $relations[] = 'phk.statusKerja';
        }

        $hasil = $this->model->query()
            ->select('karyawans.*')
            ->withCount(['anaks as jumlah_anak'])
            ->with($relations)
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->orderBy('karyawans.staf')
            ->orderBy('areas.urutan');
        if(isset($inputs['aktif']) && $inputs['aktif'] != '') {
            $hasil->where('karyawans.aktif', $inputs['aktif']);
        }
        if(isset($inputs['staf']) && $inputs['staf'] != '') {
            $hasil->where('karyawans.staf', $inputs['staf']);
        }
        if(isset($inputs['area']) && $inputs['area'] != '') {
            $hasil->where('karyawans.area_id', $inputs['area']);
        }
        if(isset($inputs['divisi_id']) && $inputs['divisi_id'] != '') {
            $hasil->where('karyawans.divisi_id', $inputs['divisi_id']);
        }
        if(isset($inputs['divisi']) && $inputs['divisi'] != '') {
            $hasil->where('karyawans.divisi_id', $inputs['divisi']);
        }
        if(isset($inputs['jabatan_id']) && $inputs['jabatan_id'] != '') {
            $hasil->where('karyawans.jabatan_id', $inputs['jabatan_id']);
        }
        if(isset($inputs['jabatan_nama']) && $inputs['jabatan_nama'] != '') {
            $jabatanNama = $inputs['jabatan_nama'];
            $hasil->whereHas('jabatan', function($q) use ($jabatanNama) {
                $q->where('nama', $jabatanNama);
            });
        }
        if(isset($inputs['status_kerja']) && $inputs['status_kerja'] != '') {
            $hasil->where('karyawans.status_kerja_id', $inputs['status_kerja']);
        }
        if(!empty($inputs['has_training'])) {
            $hasil->has('trainingKaryawans');
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
            $h->jumlah_anak = (int) $h->jumlah_anak;
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
        if(isset($inputs['manajemen'])) {
            $model->manajemen = $inputs['manajemen'];
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

    public function setAktif($id) {
        $model = $this->model->findOrFail($id);
        $model->aktif = 'Y';
        $model->tanggal_keluar = null;
        $model->phk_id = null;
        $model->save();
        return $model;
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

    public function demografi() {
        // Single-pass query: ambil seluruh kolom demografi karyawan aktif dalam 1 query
        $karyawans = DB::table('karyawans')
            ->join('areas', 'karyawans.area_id', '=', 'areas.id')
            ->leftJoin('agamas', 'karyawans.agama_id', '=', 'agamas.id')
            ->leftJoin('pendidikans', 'karyawans.pendidikan_id', '=', 'pendidikans.id')
            ->where('karyawans.aktif', 'Y')
            ->select(
                'areas.kode as area_kode',
                'karyawans.kelamin',
                'karyawans.kawin',
                'agamas.nama as agama_nama',
                'pendidikans.nama as pendidikan_nama',
                'karyawans.tanggal_lahir'
            )
            ->orderBy('areas.kode')
            ->get();

        $totalPerArea = ['TOTAL KARYAWAN' => 0];
        $totalPerKelamin = [];
        $totalPerKawin = [];
        $totalPerAgama = [];
        $totalPerPendidikan = [];
        $totalPerUsia = [];
        $now = new \DateTime();

        foreach ($karyawans as $r) {
            $area = $r->area_kode;

            // 1. Total per Area
            $totalPerArea[$area] = ($totalPerArea[$area] ?? 0) + 1;
            $totalPerArea['TOTAL KARYAWAN'] += 1;

            // 2. Per Kelamin
            $k = $r->kelamin ?: 'Lainnya';
            if (!isset($totalPerKelamin[$k])) {
                $totalPerKelamin[$k] = ['TOTAL KARYAWAN' => 0];
            }
            $totalPerKelamin[$k][$area] = ($totalPerKelamin[$k][$area] ?? 0) + 1;
            $totalPerKelamin[$k]['TOTAL KARYAWAN'] += 1;

            // 3. Per Kawin
            $kawin = $r->kawin ?: 'N';
            if (!isset($totalPerKawin[$kawin])) {
                $totalPerKawin[$kawin] = ['TOTAL KARYAWAN' => 0];
            }
            $totalPerKawin[$kawin][$area] = ($totalPerKawin[$kawin][$area] ?? 0) + 1;
            $totalPerKawin[$kawin]['TOTAL KARYAWAN'] += 1;

            // 4. Per Agama
            $agama = $r->agama_nama ?: 'Lainnya';
            if (!isset($totalPerAgama[$agama])) {
                $totalPerAgama[$agama] = ['TOTAL KARYAWAN' => 0];
            }
            $totalPerAgama[$agama][$area] = ($totalPerAgama[$agama][$area] ?? 0) + 1;
            $totalPerAgama[$agama]['TOTAL KARYAWAN'] += 1;

            // 5. Per Pendidikan
            $pendidikan = $r->pendidikan_nama ?: 'Lainnya';
            if (!isset($totalPerPendidikan[$pendidikan])) {
                $totalPerPendidikan[$pendidikan] = ['TOTAL KARYAWAN' => 0];
            }
            $totalPerPendidikan[$pendidikan][$area] = ($totalPerPendidikan[$pendidikan][$area] ?? 0) + 1;
            $totalPerPendidikan[$pendidikan]['TOTAL KARYAWAN'] += 1;

            // 6. Per Usia
            $grp = '0';
            if ($r->tanggal_lahir) {
                $birth = new \DateTime($r->tanggal_lahir);
                $age = $now->diff($birth)->y;
                if ($age < 20) $grp = '< 20';
                else if ($age <= 25) $grp = '20–25';
                else if ($age <= 30) $grp = '26–30';
                else if ($age <= 35) $grp = '31–35';
                else if ($age <= 40) $grp = '36–40';
                else if ($age <= 45) $grp = '41–45';
                else if ($age <= 50) $grp = '46–50';
                else if ($age <= 55) $grp = '51–55';
                else $grp = '56+';
            }
            if (!isset($totalPerUsia[$grp])) {
                $totalPerUsia[$grp] = ['TOTAL KARYAWAN' => 0];
            }
            $totalPerUsia[$grp][$area] = ($totalPerUsia[$grp][$area] ?? 0) + 1;
            $totalPerUsia[$grp]['TOTAL KARYAWAN'] += 1;
        }

        return [
            'total_per_area' => $totalPerArea,
            'total_per_kelamin' => $totalPerKelamin,
            'total_per_kawin' => $totalPerKawin,
            'total_per_agama' => $totalPerAgama,
            'total_per_pendidikan' => $totalPerPendidikan,
            'total_per_usia' => $totalPerUsia,
        ];
    }

    public function ulangTahun($bulan = null) {
        if (!$bulan) {
            $bulan = (int)date('n');
        }

        return DB::table('karyawans')
            ->where('karyawans.aktif', 'Y')
            ->whereRaw('MONTH(karyawans.tanggal_lahir) = ?', [$bulan])
            ->select('karyawans.id', 'karyawans.nama', 'karyawans.tanggal_lahir')
            ->orderByRaw('DAY(karyawans.tanggal_lahir) ASC')
            ->get();
    }
}
