<?php

namespace App\Http\Controllers;

use App\Models\CicKaryawan;
use Illuminate\Http\Request;

class CicKaryawanController extends Controller
{
    public function findAll(Request $request)
    {
        $data = CicKaryawan::with(['agama', 'area', 'jabatan', 'divisi', 'statusKerja', 'pendidikan'])
                ->orderBy('nama', 'asc')->get();
        return response()->json(['status' => 'success', 'message' => '', 'data' => $data], 200);
    }

    public function findById($id)
    {
        $data = CicKaryawan::with(['agama', 'area', 'jabatan', 'divisi', 'statusKerja', 'pendidikan'])->find($id);
        if (!$data) return response()->json(['message' => 'Data tidak ditemukan'], 404);
        return response()->json(['status' => 'success', 'message' => '', 'data' => $data], 200);
    }

    public function create(Request $request)
    {
        try {
            $data = CicKaryawan::create($request->all());
            return response()->json(['status' => 'success', 'message' => 'Karyawan berhasil ditambahkan', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = CicKaryawan::find($id);
            if (!$data) return response()->json(['message' => 'Data tidak ditemukan'], 404);
            $data->update($request->all());
            return response()->json(['status' => 'success', 'message' => 'Karyawan berhasil diupdate', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function delete($id)
    {
        try {
            $data = CicKaryawan::find($id);
            if (!$data) return response()->json(['message' => 'Data tidak ditemukan'], 404);
            $data->delete();
            return response()->json(['status' => 'success', 'message' => 'Karyawan berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
