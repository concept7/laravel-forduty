<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty;

use Illuminate\Support\ServiceProvider;

class LaravelFordutyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-forduty.php', 'laravel-forduty');

        $this->app->singleton(LaravelForduty::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-forduty.php' => config_path('laravel-forduty.php'),
        ], ['laravel-forduty', 'laravel-forduty-config']);
    }
}
