<?php

namespace App\Providers;

use App\Services\TelegramaValidationService;
use App\Services\ResultadoCalculationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TelegramaValidationService::class);
        $this->app->singleton(ResultadoCalculationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
