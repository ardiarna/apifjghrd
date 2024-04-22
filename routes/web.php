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

$router->group(['prefix' => 'divisi', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'DivisiController@findAll');
    $router->get('{id}', 'DivisiController@findById');
    $router->post('/', 'DivisiController@create');
    $router->put('{id}', 'DivisiController@update');
    $router->delete('{id}', 'DivisiController@delete');
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
    $router->post('/', 'KaryawanController@create');
    $router->post('{karyawan_id}/keluarga', 'KeluargaKaryawanController@create');
    $router->put('{id}', 'KaryawanController@update');
    $router->put('{karyawan_id}/keluarga/{id}', 'KeluargaKaryawanController@update');
    $router->delete('{id}', 'KaryawanController@delete');
    $router->delete('{karyawan_id}/keluarga/{id}', 'KeluargaKaryawanController@delete');
});

$router->group(['prefix' => 'pendidikan', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PendidikanController@findAll');
    $router->get('{id}', 'PendidikanController@findById');
    $router->post('/', 'PendidikanController@create');
    $router->put('{id}', 'PendidikanController@update');
    $router->delete('{id}', 'PendidikanController@delete');
});

$router->group(['prefix' => 'statuskerja', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'StatusKerjaController@findAll');
    $router->get('{id}', 'StatusKerjaController@findById');
    $router->post('/', 'StatusKerjaController@create');
    $router->put('{id}', 'StatusKerjaController@update');
    $router->delete('{id}', 'StatusKerjaController@delete');
});
