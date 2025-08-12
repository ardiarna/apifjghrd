<?php

namespace App\Repositories\Elo;

use App\Repositories\OncallCustomerRepository;
use App\Models\OncallCustomer;

class OncallCustomerImplement implements OncallCustomerRepository {

    protected $model;

    function __construct(OncallCustomer $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        $hasil = $this->model->query()->with(['customer']);

        if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '' && isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->whereBetween('oncall_customers.tanggal', [$inputs['tanggal_awal'], $inputs['tanggal_akhir']]);
        } else if(isset($inputs['tanggal_awal']) && $inputs['tanggal_awal'] != '') {
            $hasil->where('oncall_customers.tanggal', '>=', $inputs['tanggal_awal']);
        } else if(isset($inputs['tanggal_akhir']) && $inputs['tanggal_akhir'] != '') {
            $hasil->where('oncall_customers.tanggal', '<=', $inputs['tanggal_akhir']);
        }
        if(isset($inputs['tahun']) && $inputs['tahun'] != '') {
            $hasil->where('oncall_customers.tahun', $inputs['tahun']);
        }
        if(isset($inputs['bulan']) && $inputs['bulan'] != '') {
            $hasil->where('oncall_customers.bulan', $inputs['bulan']);
        }
        if(isset($inputs['customer_id']) && $inputs['customer_id'] != '') {
            $hasil->where('oncall_customers.customer_id', $inputs['customer_id']);
        } else {
            $hasil->select('oncall_customers.*')
                ->join('customers', 'oncall_customers.customer_id', '=', 'customers.id')
                ->orderBy('customers.nama');
        }

        $hasil->orderBy('oncall_customers.tanggal');
        return $hasil->get();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        if(isset($inputs['customer_id'])) {
            $model->customer_id = $inputs['customer_id'];
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

    public function deleteAll($inputs = []) {
        if (empty($inputs['tahun']) || empty($inputs['bulan'])) {
            return false;
        }
        $hasil = $this->model->query();
        $hasil->where('tahun', $inputs['tahun']);
        $hasil->where('bulan', $inputs['bulan']);
        return $hasil->delete();
    }

}
