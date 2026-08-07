<?php

namespace Modules\EV5\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'EV5';
    protected string $smallName = 'ev5';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerTranslation();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }

    private function registerTranslation():void
    {
        $langPath = resource_path('lang/modules/' .$this->smallName );

        if (is_dir($langPath)) {
            // Load published translations overridden by the app
            $this->loadTranslationsFrom($langPath, $this->smallName);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            // Load module default translations
            $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), $this->smallName);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'resources/lang'));
        }
    }
}
