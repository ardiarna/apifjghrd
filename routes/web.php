<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('login', 'AuthController@login');
$router->post('register', 'UserController@create');
$router->post('resetpwd', 'UserController@resetPassword');

$router->group(['middleware' => 'auth:api'], function () use ($router) {
    $router->get('logout', 'AuthController@logout');
    $router->get('refresh', 'AuthController@refresh');
});

$router->group(['prefix' => 'user', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UserController@view');
    $router->post('/', 'UserController@create');
    $router->put('/', 'UserController@update');
    $router->put('editpwd', 'UserController@editPassword');
    $router->put('tokenpush', 'UserController@tokenPush');
    $router->post('photo', 'UserController@photo');
    $router->delete('/', 'UserController@delete');
});

$router->group(['prefix' => 'agama', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'AgamaController@findAll');
    $router->get('{id}', 'AgamaController@findById');
    $router->post('/', 'AgamaController@create');
    $router->put('{id}', 'AgamaController@update');
    $router->delete('{id}', 'AgamaController@delete');
});

$router->group(['prefix' => 'area', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'AreaController@findAll');
    $router->get('{id}', 'AreaController@findById');
    $router->post('/', 'AreaController@create');
    $router->put('{id}', 'AreaController@update');
    $router->delete('{id}', 'AreaController@delete');
});

$router->group(['prefix' => 'customer', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'CustomerController@findAll');
    $router->get('{id}', 'CustomerController@findById');
    $router->post('/', 'CustomerController@create');
    $router->put('{id}', 'CustomerController@update');
    $router->delete('{id}', 'CustomerController@delete');
});

$router->group(['prefix' => 'divisi', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'DivisiController@findAll');
    $router->get('{id}', 'DivisiController@findById');
    $router->post('/', 'DivisiController@create');
    $router->put('{id}', 'DivisiController@update');
    $router->delete('{id}', 'DivisiController@delete');
});

$router->group(['prefix' => 'hari_libur', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'HariLiburController@findAll');
    $router->get('{id}', 'HariLiburController@findById');
    $router->post('/', 'HariLiburController@create');
    $router->put('{id}', 'HariLiburController@update');
    $router->delete('{id}', 'HariLiburController@delete');
});

$router->group(['prefix' => 'jabatan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'JabatanController@findAll');
    $router->get('{id}', 'JabatanController@findById');
    $router->post('/', 'JabatanController@create');
    $router->put('{id}', 'JabatanController@update');
    $router->delete('{id}', 'JabatanController@delete');
});

$router->group(['prefix' => 'karyawan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'KaryawanController@findAll');
    $router->get('{id}', 'KaryawanController@findById');
    $router->get('{karyawan_id}/keluarga', 'KeluargaKaryawanController@findAll');
    $router->get('{karyawan_id}/keluarga/{id}', 'KeluargaKaryawanController@findById');
    $router->get('{karyawan_id}/kontak-keluarga', 'KeluargaKontakController@findAll');
    $router->get('{karyawan_id}/kontak-keluarga/{id}', 'KeluargaKontakController@findById');
    $router->get('{karyawan_id}/perjanjian-kerja', 'PerjanjianKerjaController@findAll');
    $router->get('{karyawan_id}/perjanjian-kerja/{id}', 'PerjanjianKerjaController@findById');
    $router->get('{karyawan_id}/timeline-masakerja', 'PerjanjianKerjaController@timelineMasaKerja');
    $router->get('{karyawan_id}/phk', 'PhkController@findAll');
    $router->get('{karyawan_id}/phk/{id}', 'PhkController@findById');
    $router->get('{karyawan_id}/upah', 'UpahController@findByKaryawanId');
    $router->get('{karyawan_id}/upah/{tahun}', 'PayrollController@findUpahByKaryawanIdAndTahun');
    $router->get('{karyawan_id}/medical-rekap/{tahun}', 'MedicalController@findRekapByKaryawanIdAndTahun');
    $router->get('{karyawan_id}/overtime-rekap/{tahun}', 'OvertimeController@findRekapByKaryawanIdAndTahun');
    $router->get('{karyawan_id}/payroll', 'PayrollController@findDetailByKaryawanId');
    $router->get('{karyawan_id}/payroll-phk', 'PayrollPhkController@findByKaryawanId');
    $router->get('rekap/area-kelamin', 'KaryawanController@rekapKaryawanByAreaAndKelamin');
    $router->post('/', 'KaryawanController@create');
    $router->post('{karyawan_id}/keluarga', 'KeluargaKaryawanController@create');
    $router->post('{karyawan_id}/kontak-keluarga', 'KeluargaKontakController@create');
    $router->post('{karyawan_id}/perjanjian-kerja', 'PerjanjianKerjaController@create');
    $router->post('{karyawan_id}/phk', 'PhkController@create');
    $router->post('{karyawan_id}/upah', 'UpahController@updateOrCreate');
    $router->post('{karyawan_id}/payroll-phk', 'PayrollPhkController@updateOrCreate');
    $router->put('{id}', 'KaryawanController@update');
    $router->put('{karyawan_id}/keluarga/{id}', 'KeluargaKaryawanController@update');
    $router->put('{karyawan_id}/kontak-keluarga/{id}', 'KeluargaKontakController@update');
    $router->put('{karyawan_id}/perjanjian-kerja/{id}', 'PerjanjianKerjaController@update');
    $router->put('{karyawan_id}/phk/{id}', 'PhkController@update');
    $router->put('{karyawan_id}/upah', 'UpahController@updateByKaryawanId');
    $router->delete('{id}', 'KaryawanController@delete');
    $router->delete('{karyawan_id}/keluarga/{id}', 'KeluargaKaryawanController@delete');
    $router->delete('{karyawan_id}/kontak-keluarga/{id}', 'KeluargaKontakController@delete');
    $router->delete('{karyawan_id}/perjanjian-kerja/{id}', 'PerjanjianKerjaController@delete');
    $router->delete('{karyawan_id}/phk/{id}', 'PhkController@delete');
    $router->delete('{karyawan_id}/upah', 'UpahController@deleteByKaryawanId');
});

$router->group(['prefix' => 'medical', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'MedicalController@findAll');
    $router->get('{id}', 'MedicalController@findById');
    $router->post('/', 'MedicalController@create');
    $router->put('{id}', 'MedicalController@update');
    $router->delete('all', 'MedicalController@deleteAll');
    $router->delete('{id}', 'MedicalController@delete');
});

$router->group(['prefix' => 'cuti', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'CutiController@findAll');
    $router->get('info', 'CutiController@info');
    $router->get('info-masal', 'CutiController@infoMasal');
    $router->post('submit-masal', 'CutiController@submitMasal');
    $router->post('submit', 'CutiController@submit');
    $router->delete('{id}', 'CutiController@delete');
    $router->get('excel/jadwal/{tahun}', 'CutiExcelController@jadwal');
    $router->get('excel/list/{tahunAwal}/{tahunAkhir}', 'CutiExcelController@listCuti');
    $router->get('excel/list/{tahun}', 'CutiExcelController@listCutiSingle');
    $router->get('excel/form/{id}', 'CutiExcelController@form');
    $router->get('excel/tanpa-potongan/{tahunAwal}/{tahunAkhir}', 'CutiExcelController@tanpaPotongan');
    $router->get('excel/tanpa-potongan/{tahun}', 'CutiExcelController@tanpaPotonganSingle');
    $router->get('excel/unpaid/{tahunAwal}/{tahunAkhir}', 'CutiExcelController@unpaid');
    $router->get('excel/unpaid/{tahun}', 'CutiExcelController@unpaidSingle');
});

$router->group(['prefix' => 'oncall_customer', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'OncallCustomerController@findAll');
    $router->get('{id}', 'OncallCustomerController@findById');
    $router->post('/', 'OncallCustomerController@create');
    $router->put('{id}', 'OncallCustomerController@update');
    $router->delete('all', 'OncallCustomerController@deleteAll');
    $router->delete('{id}', 'OncallCustomerController@delete');
});

$router->group(['prefix' => 'overtime', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'OvertimeController@findAll');
    $router->get('{id}', 'OvertimeController@findById');
    $router->post('/', 'OvertimeController@create');
    $router->put('{id}', 'OvertimeController@update');
    $router->delete('all', 'OvertimeController@deleteAll');
    $router->delete('{id}', 'OvertimeController@delete');
});

$router->group(['prefix' => 'payroll', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PayrollController@findAll');
    $router->get('{id}', 'PayrollController@findById');
    $router->get('{header_id}/detil', 'PayrollController@findDetail');
    $router->get('report-rekap/{tahun}', 'PayrollController@reportRekap');
    $router->post('/', 'PayrollController@create');
    $router->put('{id}', 'PayrollController@update');
    $router->put('{id}/kunci', 'PayrollController@kunciPayroll');
    $router->put('{header_id}/detil/{id}', 'PayrollController@updateDetail');
    $router->delete('{id}', 'PayrollController@delete');
});

$router->group(['prefix' => 'payroll_phk', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PayrollPhkController@findAll');
    $router->get('{id}', 'PayrollPhkController@findById');
    $router->post('/', 'PayrollPhkController@create');
    $router->put('{id}', 'PayrollPhkController@update');
    $router->delete('{id}', 'PayrollPhkController@delete');
});


$router->group(['prefix' => 'training', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'TrainingController@findAll');
    $router->get('{id}', 'TrainingController@findById');
    $router->post('/', 'TrainingController@create');
    $router->put('{id}', 'TrainingController@update');
    $router->delete('{id}', 'TrainingController@delete');
});

$router->group(['prefix' => 'training-karyawan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'TrainingKaryawanController@findAll');
    $router->get('{id}', 'TrainingKaryawanController@findById');
    $router->post('/', 'TrainingKaryawanController@create');
    $router->put('{id}', 'TrainingKaryawanController@update');
    $router->delete('{id}', 'TrainingKaryawanController@delete');
});

$router->group(['prefix' => 'pendidikan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PendidikanController@findAll');
    $router->get('{id}', 'PendidikanController@findById');
    $router->post('/', 'PendidikanController@create');
    $router->put('{id}', 'PendidikanController@update');
    $router->delete('{id}', 'PendidikanController@delete');
});

$router->group(['prefix' => 'penghasilan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PenghasilanController@findAll');
    $router->get('{id}', 'PenghasilanController@findById');
    $router->post('/', 'PenghasilanController@create');
    $router->put('{id}', 'PenghasilanController@update');
    $router->delete('all', 'PenghasilanController@deleteAll');
    $router->delete('{id}', 'PenghasilanController@delete');
});

$router->group(['prefix' => 'potongan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PotonganController@findAll');
    $router->get('{id}', 'PotonganController@findById');
    $router->post('/', 'PotonganController@create');
    $router->put('{id}', 'PotonganController@update');
    $router->delete('all', 'PotonganController@deleteAll');
    $router->delete('{id}', 'PotonganController@delete');
});

$router->group(['prefix' => 'ptkp', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PtkpController@findAll');
    $router->get('id/{id}', 'PtkpController@findById');
    $router->get('cari', 'PtkpController@findByKode');
    $router->post('/', 'PtkpController@create');
    $router->put('{id}', 'PtkpController@update');
    $router->delete('{id}', 'PtkpController@delete');
});

$router->group(['prefix' => 'status_kerja', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'StatusKerjaController@findAll');
    $router->get('{id}', 'StatusKerjaController@findById');
    $router->post('/', 'StatusKerjaController@create');
    $router->put('{id}', 'StatusKerjaController@update');
    $router->delete('{id}', 'StatusKerjaController@delete');
});

$router->group(['prefix' => 'status_phk', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'StatusPhkController@findAll');
    $router->get('{id}', 'StatusPhkController@findById');
    $router->post('/', 'StatusPhkController@create');
    $router->put('{id}', 'StatusPhkController@update');
    $router->delete('{id}', 'StatusPhkController@delete');
});

$router->group(['prefix' => 'tarif_efektif', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'TarifEfektifController@findAll');
    $router->get('id/{id}', 'TarifEfektifController@findById');
    $router->get('cari', 'TarifEfektifController@findByTerAndPenghasilan');
    $router->post('/', 'TarifEfektifController@create');
    $router->put('{id}', 'TarifEfektifController@update');
    $router->delete('{id}', 'TarifEfektifController@delete');
});

$router->group(['prefix' => 'uang_phk', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UangPhkController@findAll');
    $router->get('{id}', 'UangPhkController@findById');
    $router->post('/', 'UangPhkController@create');
    $router->put('{id}', 'UangPhkController@update');
    $router->delete('{id}', 'UangPhkController@delete');
});

$router->group(['prefix' => 'upah', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UpahController@findAll');
    $router->get('{id}', 'UpahController@findById');
    $router->put('{id}', 'UpahController@update');
    $router->delete('{id}', 'UpahController@delete');
});

$router->group(["prefix" => "excel", "middleware" => "auth:api"], function () use ($router) {
    $router->get('data-karyawan-per-joint/{tahunAwal}/{tahunAkhir}/{includeEx}', 'SpreadKaryawanCustomController@dataKaryawanPerJoint');
    $router->get('data-divisi/{id}', 'SpreadKaryawanCustomController@dataDivisi');
    $router->get('alamat-divisi/{id}', 'SpreadKaryawanCustomController@alamatDivisi');
    $router->get('nik-tlp-karyawan', 'SpreadKaryawanCustomController@nikTlpKaryawan');
    $router->get('data-jabatan-karyawan', 'SpreadKaryawanCustomController@dataJabatanKaryawan');
    $router->get('data-status-karyawan', 'SpreadKaryawanCustomController@dataStatusKaryawan');
    $router->get('list-payroll/{tahun}', 'SpreadsheetController@listPayroll');
    $router->get('list-phk/{tahun_awal}/{tahun_akhir}', 'SpreadsheetController@listPHK');
    $router->get('list-ex-karyawan/{tahun_awal}/{tahun_akhir}', 'SpreadsheetController@listExKaryawan');
    $router->get('list-karyawan', 'SpreadsheetController@listKaryawan');
    $router->get('list-salary', 'SpreadsheetController@listSalary');
    $router->get('rekap-gaji/{tahun}', 'SpreadsheetController@rekapGaji');
    $router->get('rekap-payroll-perkaryawan/{jenis}/{tahun}/{area}', 'SpreadPayrollController@rekapPerKaryawan');
    $router->get('rekap-medical/{tahun}', 'SpreadMedicalController@rekap');
    $router->get('rekap-overtime/{tahun}', 'SpreadOvertimeController@rekap');
    $router->get('slip-gaji/{tahun}/{bulan}/{jenis}/{area}', 'SpreadSlipGajiController@cetak');
    $router->get('pdf-slip-gaji/{tahun}/{bulan}/{jenis}/{area}', 'PdfSlipGajiController@cetak');
    $router->get('slip-karyawan/{karyawan_id}/{tahun}/{bulans}', 'SpreadSlipGajiController@perKaryawan');
    $router->get('pdf-slip-karyawan/{karyawan_id}/{tahun}/{bulans}', 'PdfSlipGajiController@perKaryawan');
    $router->get('payroll/{karyawan_id}/{tahun}', 'SpreadPphController@karyawan');
    $router->get('payroll-periode/{karyawan_id}/{tahun_awal}/{bulan_awal}/{tahun_akhir}/{bulan_akhir}', 'SpreadPphController@karyawanPeriode');
    $router->get('rekap-pph21/{jenis}/{tahun}/{area}', 'SpreadPphController@rekap');
});

$router->group(['prefix' => 'jatah-cuti', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('hitung-sisa', 'JatahCutiTahunanController@hitungSisa');

    $router->get('/', 'JatahCutiTahunanController@findAll');
    $router->post('/', 'JatahCutiTahunanController@create');
    $router->put('{id}', 'JatahCutiTahunanController@update');
    $router->delete('{id}', 'JatahCutiTahunanController@delete');
});

$router->group(['prefix' => 'jenis-cuti-khusus', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'JenisCutiKhususController@findAll');
    $router->post('/', 'JenisCutiKhususController@create');
    $router->put('{id}', 'JenisCutiKhususController@update');
    $router->delete('{id}', 'JenisCutiKhususController@delete');
});

$router->group(['prefix' => 'cic', 'middleware' => 'auth:api'], function () use ($router) {
    // Karyawan
    $router->group(['prefix' => 'karyawan'], function () use ($router) {
        $router->get('/', 'CicKaryawanController@findAll');
        $router->get('{id}', 'CicKaryawanController@findById');
        $router->post('/', 'CicKaryawanController@create');
        $router->put('{id}', 'CicKaryawanController@update');
        $router->delete('{id}', 'CicKaryawanController@delete');
    });

    // Cuti
    $router->group(['prefix' => 'cuti'], function () use ($router) {
        $router->get('/', 'CicCutiController@findAll');
        $router->get('info', 'CicCutiController@info');
        $router->get('info-masal', 'CicCutiController@infoMasal');
        $router->post('submit-masal', 'CicCutiController@submitMasal');
        $router->post('submit', 'CicCutiController@submit');
        $router->delete('{id}', 'CicCutiController@delete');

        $router->group(['prefix' => 'excel'], function () use ($router) {
            $router->get('jadwal/{tahun}', 'CicCutiExcelController@jadwal');
            $router->get('list/{tahunAwal}/{tahunAkhir}', 'CicCutiExcelController@listCuti');
            $router->get('list/{tahun}', 'CicCutiExcelController@listCutiSingle');
            $router->get('tanpa-potongan/{tahunAwal}/{tahunAkhir}', 'CicCutiExcelController@tanpaPotongan');
            $router->get('tanpa-potongan/{tahun}', 'CicCutiExcelController@tanpaPotonganSingle');
            $router->get('unpaid/{tahunAwal}/{tahunAkhir}', 'CicCutiExcelController@unpaid');
            $router->get('unpaid/{tahun}', 'CicCutiExcelController@unpaidSingle');
            $router->get('form/{id}', 'CicCutiExcelController@form');
        });
    });

    // Jatah
    $router->group(['prefix' => 'jatah-cuti-tahunan'], function () use ($router) {
        $router->get('hitung-sisa', 'CicJatahCutiTahunanController@hitungSisa');
        $router->get('/', 'CicJatahCutiTahunanController@findAll');
        $router->post('/', 'CicJatahCutiTahunanController@create');
        $router->put('{id}', 'CicJatahCutiTahunanController@update');
        $router->delete('{id}', 'CicJatahCutiTahunanController@delete');
    });

    // Jenis Khusus
    $router->group(['prefix' => 'jenis-cuti-khusus'], function () use ($router) {
        $router->get('/', 'CicJenisCutiKhususController@findAll');
        $router->post('/', 'CicJenisCutiKhususController@create');
        $router->put('{id}', 'CicJenisCutiKhususController@update');
        $router->delete('{id}', 'CicJenisCutiKhususController@delete');
    });
});
$router->get('debug-excel', 'SpreadKaryawanCustomController@dataStatusKaryawan');
