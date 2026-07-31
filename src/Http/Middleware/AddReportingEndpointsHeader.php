<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddReportingEndpointsHeader
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = config('laravel-forduty.token');
        $baseUrl = config('laravel-forduty.base_url');

        if (! is_string($token) || trim($token) === '' || ! is_string($baseUrl) || trim($baseUrl) === '') {
            return $response;
        }

        $endpoint = rtrim($baseUrl, '/').'/'.ltrim($token, '/');

        $response->headers->set('Reporting-Endpoints', sprintf('default="%s"', $endpoint));

        return $response;
    }
}
