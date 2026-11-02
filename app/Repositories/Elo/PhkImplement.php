<?php

namespace App\Repositories\Elo;

use App\Repositories\PhkRepository;
use App\Models\Phk;
use App\Models\UangPhk;
use App\Models\PayrollPhk;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PhkImplement implements PhkRepository {

    protected $model;

    function __construct(Phk $model) {
        $this->model = $model;
    }

    public function findById($karyawan_id, $id) {
        $model = $this->model->with(['statusKerja','statusPhk','uangPhk','payrollPhk'])->findOrFail($id);
        if($model->karyawan_id != $karyawan_id) {
            throw new HttpException(403, 'Karyawan dan PHK tidak sesuai');
        }
        return $model;
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['statusKerja','statusPhk','uangPhk','payrollPhk']);
        $hasil->where('karyawan_id', $inputs['karyawan_id']);
        if($inputs['search_by'] && $inputs['value']) {
            $value = $inputs['value'];
            $hasil->where($inputs['search_by'], 'like', "%$value%");
        }
        if($inputs['sort_by']) {
            $sort_order = $inputs['sort_order'] ? strtolower($inputs['sort_order']) : 'asc';
            $hasil->orderBy($inputs['sort_by'], $sort_order);
        } else {
            $hasil->orderBy('tanggal_awal');
        }
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if($model->karyawan_id != $inputs['karyawan_id']) {
            throw new HttpException(403, 'Karyawan dan PHK tidak sesuai');
        }
        if($inputs['tanggal_awal'] != null) {
            $model->tanggal_awal = $inputs['tanggal_awal'];
        }
        if($inputs['tanggal_akhir'] != null) {
            $model->tanggal_akhir = $inputs['tanggal_akhir'];
        }
        if($inputs['status_kerja_id'] != null) {
            $model->status_kerja_id = $inputs['status_kerja_id'];
        }
        if($inputs['status_phk_id'] != null) {
            $model->status_phk_id = $inputs['status_phk_id'];
        }
        $uangPhkToDestroy = null;
        if(array_key_exists('uang_phk_id', $inputs)) {
            $oldUangPhkId = $model->uang_phk_id;
            $newUangPhkId = !empty($inputs['uang_phk_id']) ? $inputs['uang_phk_id'] : null;
            $model->uang_phk_id = $newUangPhkId;
            if ($oldUangPhkId && $oldUangPhkId != $newUangPhkId) {
                $uangPhkToDestroy = $oldUangPhkId;
            }
        }
        $payrollPhkToDestroy = null;
        if(array_key_exists('payroll_phk_id', $inputs)) {
            $oldPayrollPhkId = $model->payroll_phk_id;
            $newPayrollPhkId = !empty($inputs['payroll_phk_id']) ? $inputs['payroll_phk_id'] : null;
            $model->payroll_phk_id = $newPayrollPhkId;
            if ($oldPayrollPhkId && $oldPayrollPhkId != $newPayrollPhkId) {
                $payrollPhkToDestroy = $oldPayrollPhkId;
            }
        }
        if($inputs['keterangan'] != null) {
            $model->keterangan = $inputs['keterangan'];
        }
        $model->save();

        if ($uangPhkToDestroy) {
            UangPhk::destroy($uangPhkToDestroy);
        }
        if ($payrollPhkToDestroy) {
            PayrollPhk::destroy($payrollPhkToDestroy);
        }
        return $model;
    }

    public function delete($id) {
        $model = $this->model->find($id);
        if ($model) {
            $uangPhkId = $model->uang_phk_id;
            $payrollPhkId = $model->payroll_phk_id;
            $model->destroy($id);
            if ($uangPhkId) {
                UangPhk::destroy($uangPhkId);
            }
            if ($payrollPhkId) {
                PayrollPhk::destroy($payrollPhkId);
            }
            return 1;
        }
        return 0;
    }

    public function deletesByKaryawanId($karyawan_id) {
        return $this->model->where('karyawan_id', $karyawan_id)->delete();
    }

}
