<?php

namespace App\Providers;

use BladeUI\Icons\Factory;
use Illuminate\Support\ServiceProvider;
use Modules\EV\Helpers\EvLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->callAfterResolving(Factory::class,function (Factory $factory){
            $factory->add('custom-icons',[
               'path'=>resource_path('svg/custom')
            ]);
        });
    }
}
