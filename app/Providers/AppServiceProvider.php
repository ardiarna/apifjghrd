<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type;
use App\Doctrine\Types\EnumType;

class AppServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if (!Type::hasType('enum')) {
            Type::addType('enum', EnumType::class);
        }

        $platform = DB::getDoctrineConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'enum');
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\AgamaRepository', 'App\Repositories\Elo\AgamaImplement');
        $this->app->bind('App\Repositories\AreaRepository', 'App\Repositories\Elo\AreaImplement');
        $this->app->bind('App\Repositories\CustomerRepository', 'App\Repositories\Elo\CustomerImplement');
        $this->app->bind('App\Repositories\DivisiRepository', 'App\Repositories\Elo\DivisiImplement');
        $this->app->bind('App\Repositories\HariLiburRepository', 'App\Repositories\Elo\HariLiburImplement');
        $this->app->bind('App\Repositories\JabatanRepository', 'App\Repositories\Elo\JabatanImplement');
        $this->app->bind('App\Repositories\KaryawanRepository', 'App\Repositories\Elo\KaryawanImplement');
        $this->app->bind('App\Repositories\KeluargaKaryawanRepository', 'App\Repositories\Elo\KeluargaKaryawanImplement');
        $this->app->bind('App\Repositories\KeluargaKontakRepository', 'App\Repositories\Elo\KeluargaKontakImplement');
        $this->app->bind('App\Repositories\MedicalRepository', 'App\Repositories\Elo\MedicalImplement');
        $this->app->bind('App\Repositories\OncallCustomerRepository', 'App\Repositories\Elo\OncallCustomerImplement');
        $this->app->bind('App\Repositories\OvertimeRepository', 'App\Repositories\Elo\OvertimeImplement');
        $this->app->bind('App\Repositories\PayrollHeaderRepository', 'App\Repositories\Elo\PayrollHeaderImplement');
        $this->app->bind('App\Repositories\PayrollRepository', 'App\Repositories\Elo\PayrollImplement');
        $this->app->bind('App\Repositories\PendidikanRepository', 'App\Repositories\Elo\PendidikanImplement');
        $this->app->bind('App\Repositories\PenghasilanRepository', 'App\Repositories\Elo\PenghasilanImplement');
        $this->app->bind('App\Repositories\PerjanjianKerjaRepository', 'App\Repositories\Elo\PerjanjianKerjaImplement');
        $this->app->bind('App\Repositories\PhkRepository', 'App\Repositories\Elo\PhkImplement');
        $this->app->bind('App\Repositories\PotonganRepository', 'App\Repositories\Elo\PotonganImplement');
        $this->app->bind('App\Repositories\PtkpRepository', 'App\Repositories\Elo\PtkpImplement');
        $this->app->bind('App\Repositories\StatusKerjaRepository', 'App\Repositories\Elo\StatusKerjaImplement');
        $this->app->bind('App\Repositories\StatusPhkRepository', 'App\Repositories\Elo\StatusPhkImplement');
        $this->app->bind('App\Repositories\TarifEfektifRepository', 'App\Repositories\Elo\TarifEfektifImplement');
        $this->app->bind('App\Repositories\UangPhkRepository', 'App\Repositories\Elo\UangPhkImplement');
        $this->app->bind('App\Repositories\UpahRepository', 'App\Repositories\Elo\UpahImplement');
        $this->app->bind('App\Repositories\UserRepository', 'App\Repositories\Elo\UserImplement');
    }
}
