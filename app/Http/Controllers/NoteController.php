<?php

namespace App\Http\Controllers;

use App\Repositories\NoteRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(NoteRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if (!$data) {
            return $this->failRespNotFound('Note tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findByKode($kode) {
        $data = $this->repo->findByKode($kode);
        if (!$data) {
            return $this->failRespNotFound('Note tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $data = $this->repo->findAll([
            'search_by' => $req->query('search_by'),
            'value' => $req->query('value'),
            'kode' => $req->query('kode'),
            'sort_by' => $req->query('sort_by'),
            'sort_order' => $req->query('sort_order')
        ]);
        return $this->successResponse($data);
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'nama' => 'required',
            'teks' => 'required',
        ]);
        $inputs['nama'] = $req->input('nama');
        $inputs['teks'] = $req->input('teks');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Note berhasil diubah');
    }
}
