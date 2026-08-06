<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty;

use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
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
     *
     * That sync replaces the router's groups with the kernel's copy, which
     * drops any middleware another provider pushed straight onto the router.
     * Those entries are collected first and pushed back afterwards, so the
     * group is left exactly as it was found plus this package's middleware.
     */
    protected function appendMiddlewareToWebGroup(HttpKernelContract $kernel): void
    {
        if (! $kernel instanceof HttpKernel || ! array_key_exists('web', $kernel->getMiddlewareGroups())) {
            return;
        }

        $router = $this->app->make(Router::class);

        $pushedOntoRouterOnly = array_diff(
            data_get($router->getMiddlewareGroups(), 'web', []),
            data_get($kernel->getMiddlewareGroups(), 'web', []),
        );

        $kernel->appendMiddlewareToGroup('web', AddReportingEndpointsHeader::class);

        foreach ($pushedOntoRouterOnly as $middleware) {
            $router->pushMiddlewareToGroup('web', $middleware);
        }
    }
}
