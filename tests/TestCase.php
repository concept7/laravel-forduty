<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Tests;

use Concept7\LaravelForduty\LaravelFordutyServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelFordutyServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /**
     * Append middleware to the global stack, standing in for the application
     * that does the same in `bootstrap/app.php`. The kernel gathers its global
     * middleware when it handles a request, so registering here still applies
     * to every request the test makes afterwards.
     *
     * @param  class-string  ...$middleware
     */
    protected function appendGlobalMiddleware(string ...$middleware): void
    {
        $kernel = $this->app?->make(HttpKernelContract::class);

        if (! $kernel instanceof HttpKernel) {
            return;
        }

        foreach ($middleware as $class) {
            $kernel->pushMiddleware($class);
        }
    }

    /**
     * Append middleware to a middleware group, standing in for the application
     * that does the same in `bootstrap/app.php`. This goes through the kernel
     * rather than the router, because the kernel syncs its own copy of the
     * groups over the router's the first time it is resolved.
     *
     * @param  class-string  ...$middleware
     */
    protected function appendMiddlewareToGroup(string $group, string ...$middleware): void
    {
        $kernel = $this->app?->make(HttpKernelContract::class);

        if (! $kernel instanceof HttpKernel) {
            return;
        }

        foreach ($middleware as $class) {
            $kernel->appendMiddlewareToGroup($group, $class);
        }
    }
}
