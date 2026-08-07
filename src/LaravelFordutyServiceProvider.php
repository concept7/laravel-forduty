<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty;

use Concept7\LaravelForduty\Http\Middleware\AddNetworkErrorLoggingHeader;
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;

class LaravelFordutyServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string>
     */
    protected const array MIDDLEWARE = [
        AddReportingEndpointsHeader::class,
        AddNetworkErrorLoggingHeader::class,
    ];

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
            $this->appendMiddlewareToGlobalStack($this->app->make(HttpKernelContract::class));
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-forduty.php' => config_path('laravel-forduty.php'),
            ], ['laravel-forduty', 'laravel-forduty-config']);
        }
    }

    /**
     * Append this package's middleware to the global middleware stack, so every
     * response the application returns carries the headers.
     *
     * The `web` group is not enough. Anything that brings its own stack never
     * sees it — a Filament panel passes an explicit middleware list, and so do
     * plenty of hand-rolled route groups — while a `Content-Security-Policy`
     * added globally still names the `default` reporting group on those same
     * responses. A browser handed a group it was never given an endpoint for
     * drops every report it would otherwise have delivered, silently.
     *
     * `pushMiddleware()` skips a class already in the stack, so an application
     * that registers either middleware itself does not get it twice.
     */
    protected function appendMiddlewareToGlobalStack(HttpKernelContract $kernel): void
    {
        if (! $kernel instanceof HttpKernel) {
            return;
        }

        foreach (self::MIDDLEWARE as $middleware) {
            $kernel->pushMiddleware($middleware);
        }
    }
}
