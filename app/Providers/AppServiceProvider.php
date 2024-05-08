<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\AgamaRepository', 'App\Repositories\Elo\AgamaImplement');
        $this->app->bind('App\Repositories\AreaRepository', 'App\Repositories\Elo\AreaImplement');
        $this->app->bind('App\Repositories\DivisiRepository', 'App\Repositories\Elo\DivisiImplement');
        $this->app->bind('App\Repositories\JabatanRepository', 'App\Repositories\Elo\JabatanImplement');
        $this->app->bind('App\Repositories\KaryawanRepository', 'App\Repositories\Elo\KaryawanImplement');
        $this->app->bind('App\Repositories\KeluargaKaryawanRepository', 'App\Repositories\Elo\KeluargaKaryawanImplement');
        $this->app->bind('App\Repositories\KeluargaKontakRepository', 'App\Repositories\Elo\KeluargaKontakImplement');
        $this->app->bind('App\Repositories\PendidikanRepository', 'App\Repositories\Elo\PendidikanImplement');
        $this->app->bind('App\Repositories\PerjanjianKerjaRepository', 'App\Repositories\Elo\PerjanjianKerjaImplement');
        $this->app->bind('App\Repositories\PhkRepository', 'App\Repositories\Elo\PhkImplement');
        $this->app->bind('App\Repositories\StatusKerjaRepository', 'App\Repositories\Elo\StatusKerjaImplement');
        $this->app->bind('App\Repositories\StatusPhkRepository', 'App\Repositories\Elo\StatusPhkImplement');
        $this->app->bind('App\Repositories\UpahRepository', 'App\Repositories\Elo\UpahImplement');
        $this->app->bind('App\Repositories\UserRepository', 'App\Repositories\Elo\UserImplement');
    }
}
