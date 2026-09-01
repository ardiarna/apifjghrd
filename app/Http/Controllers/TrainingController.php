<?php

namespace App\Http\Controllers;

use App\Repositories\TrainingRepository;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;

class TrainingController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(TrainingRepository $repo)
    {
        $this->repo = $repo;
    }

    public function findAll(Request $request)
    {
        $data = $this->repo->findAll($request->all());
        return $this->successResponse($data, 'Data berhasil diambil');
    }

    public function findById($id)
    {
        $data = $this->repo->findById($id);
        return $this->successResponse($data, 'Data berhasil diambil');
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'nama' => 'required|string',
            'urutan' => 'nullable|integer'
        ]);

        $data = $this->repo->create($request->all());
        return $this->createdResponse($data, 'Data berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama' => 'nullable|string',
            'urutan' => 'nullable|integer'
        ]);

        $data = $this->repo->update($id, $request->all());
        return $this->successResponse($data, 'Data berhasil diubah');
    }

    public function delete($id)
    {
        $data = $this->repo->delete($id);
        return $this->successResponse($data, 'Data berhasil dihapus');
    }
}
