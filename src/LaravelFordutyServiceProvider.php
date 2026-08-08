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
     *
     * This package registers no middleware of its own. Where a header is
     * written, and on which responses, is the application's call: it makes
     * the registration visible in `bootstrap/app.php` next to every other
     * middleware, rather than something a package does behind its back.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-forduty.php' => config_path('laravel-forduty.php'),
            ], ['laravel-forduty', 'laravel-forduty-config']);
        }
    }
}
