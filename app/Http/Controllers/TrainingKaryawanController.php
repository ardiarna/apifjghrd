<?php

namespace App\Http\Controllers;

use App\Repositories\TrainingKaryawanRepository;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;

class TrainingKaryawanController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(TrainingKaryawanRepository $repo)
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
            'karyawan_id' => 'required|exists:karyawans,id',
            'training_id' => 'required|exists:trainings,id',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string'
        ]);

        $inputs = $request->all();
        if (isset($inputs['tanggal']) && $inputs['tanggal'] === '') {
            $inputs['tanggal'] = null;
        }
        $data = $this->repo->create($inputs);
        $data = $this->repo->findById($data->id);
        $data->load('training');
        return $this->createdResponse($data, 'Data berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'training_id' => 'nullable|exists:trainings,id',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string'
        ]);

        $inputs = $request->all();
        if (isset($inputs['tanggal']) && $inputs['tanggal'] === '') {
            $inputs['tanggal'] = null;
        }
        $data = $this->repo->update($id, $inputs);
        $data = $this->repo->findById($data->id);
        $data->load('training');
        return $this->successResponse($data, 'Data berhasil diubah');
    }

    public function delete($id)
    {
        $data = $this->repo->delete($id);
        return $this->successResponse($data, 'Data berhasil dihapus');
    }
}
