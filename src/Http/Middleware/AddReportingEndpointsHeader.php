<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Http\Middleware;

use Closure;
use Concept7\LaravelForduty\Concerns\RespectsRouteExclusions;
use Concept7\LaravelForduty\LaravelForduty;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddReportingEndpointsHeader
{
    use RespectsRouteExclusions;

    public function __construct(protected LaravelForduty $forduty) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isExcludedFromRoute($request)) {
            return $response;
        }

        $endpoint = $this->forduty->endpoint();

        if (blank($endpoint)) {
            return $response;
        }

        $response->headers->set('Reporting-Endpoints', sprintf('default="%s"', $endpoint));

        return $response;
    }
}
