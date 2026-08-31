<?php

namespace App\Http\Controllers;

use App\Repositories\CicJenisCutiKhususRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CicJenisCutiKhususController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(CicJenisCutiKhususRepository $repo)
    {
        $this->repo = $repo;
    }

    public function findAll(Request $req)
    {
        $data = $this->repo->findAll();
        return $this->successResponse($data);
    }

    public function create(Request $req)
    {
        $this->validate($req, [
            'nama'      => 'required|string|max:100',
            'lama_hari' => 'required|integer|min:1',
            'satuan'    => 'in:hari,bulan',
            'urutan'    => 'integer|min:0',
        ]);

        $inputs = $req->only(['nama', 'lama_hari', 'satuan', 'urutan']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Jenis cuti khusus berhasil dibuat');
    }

    public function update(Request $req, $id)
    {
        $this->validate($req, [
            'lama_hari' => 'integer|min:1',
            'satuan'    => 'in:hari,bulan',
            'urutan'    => 'integer|min:0',
        ]);

        $inputs = [
            'nama'      => $req->input('nama'),
            'lama_hari' => $req->input('lama_hari'),
            'satuan'    => $req->input('satuan'),
            'urutan'    => $req->input('urutan'),
        ];

        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Jenis cuti khusus berhasil diubah');
    }

    public function delete($id)
    {
        $data = $this->repo->delete($id);
        if ($data == 0) {
            return $this->failRespNotFound('Jenis cuti khusus tidak ditemukan');
        }
        return $this->successResponse($data, 'Jenis cuti khusus berhasil dihapus');
    }
}
