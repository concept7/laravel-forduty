<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Concerns;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

trait RespectsRouteExclusions
{
    /**
     * Whether the matched route asked to be served without this middleware.
     *
     * Laravel only applies a route's `withoutMiddleware()` to middleware the
     * router itself gathers, and this package's middleware is global. Both of
     * these middlewares decorate the response on the way out though, by which
     * point a route has been matched, so the exclusion can be honoured here
     * and `withoutMiddleware()` keeps working as documented.
     */
    protected function isExcludedFromRoute(Request $request): bool
    {
        $route = $request->route();

        return $route instanceof Route
            && in_array(static::class, $route->excludedMiddleware(), true);
    }
}
