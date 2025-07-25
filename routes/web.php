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
    $router->get('rekap/area-kelamin', 'KaryawanController@rekapKaryawanByAreaAndKelamin');
    $router->post('/', 'KaryawanController@create');
    $router->post('{karyawan_id}/keluarga', 'KeluargaKaryawanController@create');
    $router->post('{karyawan_id}/kontak-keluarga', 'KeluargaKontakController@create');
    $router->post('{karyawan_id}/perjanjian-kerja', 'PerjanjianKerjaController@create');
    $router->post('{karyawan_id}/phk', 'PhkController@create');
    $router->post('{karyawan_id}/upah', 'UpahController@updateOrCreate');
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
    $router->delete('{id}', 'MedicalController@delete');
});

$router->group(['prefix' => 'oncall_customer', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'OncallCustomerController@findAll');
    $router->get('{id}', 'OncallCustomerController@findById');
    $router->post('/', 'OncallCustomerController@create');
    $router->put('{id}', 'OncallCustomerController@update');
    $router->delete('{id}', 'OncallCustomerController@delete');
});

$router->group(['prefix' => 'overtime', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'OvertimeController@findAll');
    $router->get('{id}', 'OvertimeController@findById');
    $router->post('/', 'OvertimeController@create');
    $router->put('{id}', 'OvertimeController@update');
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
    $router->delete('{id}', 'PenghasilanController@delete');
});

$router->group(['prefix' => 'potongan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PotonganController@findAll');
    $router->get('{id}', 'PotonganController@findById');
    $router->post('/', 'PotonganController@create');
    $router->put('{id}', 'PotonganController@update');
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

$router->group(['prefix' => 'excel', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('list-payroll/{tahun}', 'SpreadsheetController@listPayroll');
    $router->get('rekap-gaji/{tahun}', 'SpreadsheetController@rekapGaji');
    $router->get('rekap-payroll-perkaryawan/{jenis}/{tahun}/{area}', 'SpreadPayrollController@rekapPerKaryawan');
    $router->get('rekap-medical/{tahun}', 'SpreadMedicalController@rekap');
    $router->get('rekap-overtime/{tahun}', 'SpreadOvertimeController@rekap');
    $router->get('slip-gaji/{tahun}/{bulan}/{jenis}/{area}', 'SpreadSlipGajiController@cetak');
    $router->get('slip-karyawan/{karyawan_id}/{tahun}/{bulans}', 'SpreadSlipGajiController@perKaryawan');
    $router->get('payroll/{karyawan_id}/{tahun}', 'SpreadPphController@karyawan');
    $router->get('rekap-pph21/{jenis}/{tahun}/{area}', 'SpreadPphController@rekap');
});
