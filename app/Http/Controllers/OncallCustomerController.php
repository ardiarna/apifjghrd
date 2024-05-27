<?php

namespace App\Http\Controllers;

use App\Repositories\OncallCustomerRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class OncallCustomerController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(OncallCustomerRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('OncallCustomer dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'tanggal_awal' => $req->query('tanggal_awal'),
            'tanggal_akhir' => $req->query('tanggal_akhir'),
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'customer_id' => $req->query('customer_id')
        ]);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'customer_id' => 'required',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['customer_id', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'OncallCustomer berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'customer_id' => 'required',
            'tanggal' => 'required|date',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer',
            'jumlah' => 'required|integer'
        ]);
        $inputs = $req->only(['customer_id', 'tanggal', 'tahun', 'bulan', 'jumlah']);
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'OncallCustomer berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('OncallCustomer dengan id '.$id.' tidak ditemukan');
        }
        return $this->successResponse($data, 'OncallCustomer berhasil dihapus');
    }

}
