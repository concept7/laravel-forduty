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

        $endpoint = $this->endpoint();

        if (blank($endpoint)) {
            return $response;
        }

        $response->headers->set('Reporting-Endpoints', sprintf('default="%s"', $endpoint));

        return $response;
    }

    /**
     * Resolve the configured ingest endpoint, or null when reporting is not
     * configured. A base URL or token that cannot be parsed as a URI yields
     * null rather than an exception, so a misconfiguration can never break
     * the responses this middleware decorates. Browsers only honour an
     * absolute endpoint, so a base URL without a scheme and host is treated
     * as unconfigured too.
     */
    protected function endpoint(): ?string
    {
        $token = config('laravel-forduty.token');
        $baseUrl = config('laravel-forduty.base_url');

        if (! is_string($token) || ! is_string($baseUrl) || blank($token) || blank($baseUrl)) {
            return null;
        }

        $token = trim($token);
        $baseUrl = trim($baseUrl);

        $endpoint = rescue(function () use ($baseUrl, $token): ?string {
            $uri = Uri::of($baseUrl);

            if (blank($uri->scheme()) || blank($uri->host())) {
                return null;
            }

            $basePath = $uri->path() === '/' ? '' : $uri->path();

            return $uri
                ->withPath($basePath.'/'.ltrim($token, '/'))
                ->value();
        }, report: false);

        return is_string($endpoint) ? $endpoint : null;
    }
}
