<?php

namespace App\Repositories\Elo;

use App\Repositories\OvertimeRepository;
use App\Models\Overtime;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OvertimeImplement implements OvertimeRepository {

    protected $model;

    function __construct(Overtime $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['karyawan.area', 'karyawan.jabatan'])
            ->orderBy('overtimes.jenis');

        if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '' && isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->whereBetween('overtimes.tanggal', [$inputs['tanggal_awal'], $inputs['tanggal_akhir']]);
        } else if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '') {
            $hasil->where('overtimes.tanggal', '>=', $inputs['tanggal_awal']);
        } else if(isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->where('overtimes.tanggal', '<=', $inputs['tanggal_akhir']);
        }
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('overtimes.tahun', $inputs['tahun']);
        }
        if(isset($inputs['bulan']) && $inputs['bulan'] != '') {
            $hasil->where('overtimes.bulan', $inputs['bulan']);
        }
        if(isset($inputs['jenis']) && $inputs['jenis'] != '') {
            $hasil->where('overtimes.jenis', $inputs['jenis']);
        }
        if(isset($inputs['karyawan_id']) && $inputs['karyawan_id'] != '') {
            $hasil->where('overtimes.karyawan_id', $inputs['karyawan_id']);
        } else {
            $hasil->select('overtimes.*')
                ->join('karyawans', 'overtimes.karyawan_id', '=', 'karyawans.id')
                ->join('areas', 'karyawans.area_id', '=', 'areas.id')
                ->orderBy('karyawans.staf')
                ->orderBy('areas.urutan')
                ->orderBy('karyawans.tanggal_masuk')
                ->orderBy('karyawans.id');
        }
        $hasil->orderBy('overtimes.tanggal');
        return $hasil->get();
    }

    public function findRekapByKaryawanIdAndTahun($karyawan_id, $tahun) {
        $hasil = $this->model->query()->with('karyawan')
            ->where('karyawan_id', $karyawan_id)
            ->where('tahun', $tahun)
            ->select(
                'karyawan_id',
                DB::raw("SUM(CASE WHEN bulan = 1 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_1"),
                DB::raw("SUM(CASE WHEN bulan = 2 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_2"),
                DB::raw("SUM(CASE WHEN bulan = 3 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_3"),
                DB::raw("SUM(CASE WHEN bulan = 4 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_4"),
                DB::raw("SUM(CASE WHEN bulan = 5 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_5"),
                DB::raw("SUM(CASE WHEN bulan = 6 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_6"),
                DB::raw("SUM(CASE WHEN bulan = 7 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_7"),
                DB::raw("SUM(CASE WHEN bulan = 8 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_8"),
                DB::raw("SUM(CASE WHEN bulan = 9 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_9"),
                DB::raw("SUM(CASE WHEN bulan = 10 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_10"),
                DB::raw("SUM(CASE WHEN bulan = 11 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_11"),
                DB::raw("SUM(CASE WHEN bulan = 12 AND jenis = 'F' THEN jumlah ELSE 0 END) AS fjg_12"),
                DB::raw("SUM(CASE WHEN bulan = 1 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_1"),
                DB::raw("SUM(CASE WHEN bulan = 2 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_2"),
                DB::raw("SUM(CASE WHEN bulan = 3 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_3"),
                DB::raw("SUM(CASE WHEN bulan = 4 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_4"),
                DB::raw("SUM(CASE WHEN bulan = 5 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_5"),
                DB::raw("SUM(CASE WHEN bulan = 6 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_6"),
                DB::raw("SUM(CASE WHEN bulan = 7 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_7"),
                DB::raw("SUM(CASE WHEN bulan = 8 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_8"),
                DB::raw("SUM(CASE WHEN bulan = 9 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_9"),
                DB::raw("SUM(CASE WHEN bulan = 10 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_10"),
                DB::raw("SUM(CASE WHEN bulan = 11 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_11"),
                DB::raw("SUM(CASE WHEN bulan = 12 AND jenis = 'C' THEN jumlah ELSE 0 END) AS cus_12")
            )
            ->groupBy('karyawan_id')
            ->first();
        return $hasil;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan tidak sesuai');
        }
        if(isset($inputs['jenis'])) {
            $model->jenis = $inputs['jenis'];
        }
        if(isset($inputs['tanggal'])) {
            $model->tanggal = $inputs['tanggal'];
        }
        if(isset($inputs['tahun'])) {
            $model->tahun = $inputs['tahun'];
        }
        if(isset($inputs['bulan'])) {
            $model->bulan = $inputs['bulan'];
        }
        if(isset($inputs['jumlah'])) {
            $model->jumlah = $inputs['jumlah'];
        }
        if(isset($inputs['keterangan'])) {
            $model->keterangan = $inputs['keterangan'];
        }
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
