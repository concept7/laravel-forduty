<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Tests;

use Concept7\LaravelForduty\LaravelFordutyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelFordutyServiceProvider::class,
        ];
    }
}
