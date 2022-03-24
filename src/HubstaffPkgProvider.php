<?php

namespace Insyghts\Hubstaff;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class HubstaffPkgProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {   
        // Registering all controllers 
        $this->app->make('Insyghts\Hubstaff\Controllers\AttendanceController');
        $this->app->make('Insyghts\Hubstaff\Controllers\ActivitiesController');
        $this->app->make('Insyghts\Hubstaff\Controllers\HubstaffConfigController');
        $this->app->make('Insyghts\Hubstaff\Controllers\HubstaffServerController');
        
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}
