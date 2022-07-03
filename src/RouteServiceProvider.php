<?php

namespace Kayalous\SocialAuth;

use Illuminate\Routing\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
         $this->loadRoutesFrom(__DIR__.'/routes/api.php');
         $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
