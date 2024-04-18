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
        $this->app->bind('App\Repositories\PendidikanRepository', 'App\Repositories\Elo\PendidikanImplement');
        $this->app->bind('App\Repositories\UserRepository', 'App\Repositories\Elo\UserImplement');
    }
}
