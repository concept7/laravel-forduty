<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty;

use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
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
        if ($this->app->bound(HttpKernelContract::class)) {
            $this->appendMiddlewareToWebGroup($this->app->make(HttpKernelContract::class));
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-forduty.php' => config_path('laravel-forduty.php'),
            ], ['laravel-forduty', 'laravel-forduty-config']);
        }
    }

    /**
     * Append the reporting middleware to the web group on the kernel, so the
     * registration survives the kernel's own middleware sync to the router.
     */
    protected function appendMiddlewareToWebGroup(HttpKernelContract $kernel): void
    {
        if ($kernel instanceof HttpKernel && array_key_exists('web', $kernel->getMiddlewareGroups())) {
            $kernel->appendMiddlewareToGroup('web', AddReportingEndpointsHeader::class);
        }
    }
}
