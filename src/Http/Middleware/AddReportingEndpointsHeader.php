<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
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

        if (! is_string($token) || ! is_string($baseUrl) || blank($token) || blank($baseUrl)) {
            return $response;
        }

        $token = trim($token);
        $baseUrl = trim($baseUrl);

        $uri = Uri::of($baseUrl);

        $endpoint = $uri
            ->withPath(rtrim($uri->path(), '/').'/'.ltrim($token, '/'))
            ->value();

        $response->headers->set('Reporting-Endpoints', sprintf('default="%s"', $endpoint));

        return $response;
    }
}
