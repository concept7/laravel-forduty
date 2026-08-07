<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty;

use Illuminate\Support\Uri;

class LaravelForduty
{
    /**
     * @var list<string>
     */
    protected const array ALLOWED_SCHEMES = [
        'http',
        'https',
    ];

    /**
     * Resolve the configured ingest endpoint, or null when reporting is not
     * configured. A base URL or token that cannot be parsed as a URI yields
     * null rather than an exception, so a misconfiguration can never break
     * the responses the middleware decorates.
     *
     * Browsers only deliver reports to an absolute http or https URL, so any
     * other scheme, or a base URL without a host, is treated as unconfigured.
     * A base URL carrying credentials is refused rather than stripped, since
     * this ends up in a header served to every visitor. A fragment is
     * meaningless to a reporting endpoint and is dropped.
     */
    public function endpoint(): ?string
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

            if (! in_array($uri->scheme(), self::ALLOWED_SCHEMES, true) || blank($uri->host())) {
                return null;
            }

            if (filled($uri->user())) {
                return null;
            }

            $basePath = $uri->path() === '/' ? '' : $uri->path();

            return $uri
                ->withPath($basePath.'/'.ltrim($token, '/'))
                ->withoutFragment()
                ->value();
        }, report: false);

        return is_string($endpoint) ? $endpoint : null;
    }
}
