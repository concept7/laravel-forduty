<?php

declare(strict_types=1);

namespace Concept7\LaravelForduty\Http\Middleware;

use Closure;
use Concept7\LaravelForduty\LaravelForduty;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddNetworkErrorLoggingHeader
{
    /**
     * The reporting group this policy delivers to. Matches the group name
     * AddReportingEndpointsHeader declares, since a NEL policy can only name
     * a group the browser has been given an endpoint for.
     */
    protected const string REPORT_TO = 'default';

    public function __construct(protected LaravelForduty $forduty) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (blank($this->forduty->endpoint())) {
            return $response;
        }

        $policy = $this->policy();

        if (blank($policy)) {
            return $response;
        }

        $response->headers->set('NEL', $policy);

        return $response;
    }

    /**
     * Build the policy document, or null when it is configured with a value a
     * browser would not accept. A bad value yields no header rather than a
     * corrected one, because silently substituting a default would change how
     * much a site reports without saying so.
     */
    protected function policy(): ?string
    {
        $maxAge = $this->maxAge();
        $successFraction = $this->fraction('success_fraction');
        $failureFraction = $this->fraction('failure_fraction');

        if ($maxAge === null || $successFraction === null || $failureFraction === null) {
            return null;
        }

        $policy = json_encode([
            'report_to' => self::REPORT_TO,
            'max_age' => $maxAge,
            'include_subdomains' => (bool) config('laravel-forduty.nel.include_subdomains'),
            'success_fraction' => $successFraction,
            'failure_fraction' => $failureFraction,
        ]);

        return $policy === false ? null : $policy;
    }

    /**
     * The policy lifetime in seconds. Must be a whole, non-negative number;
     * zero is valid and tells the browser to drop a policy it already holds.
     */
    protected function maxAge(): ?int
    {
        $maxAge = config('laravel-forduty.nel.max_age');

        if (! is_numeric($maxAge)) {
            return null;
        }

        $maxAge = (float) $maxAge;

        if ($maxAge < 0.0 || floor($maxAge) !== $maxAge) {
            return null;
        }

        return (int) $maxAge;
    }

    /**
     * A sampling rate, which browsers read as a fraction between zero and one.
     */
    protected function fraction(string $key): ?float
    {
        $fraction = config('laravel-forduty.nel.'.$key);

        if (! is_numeric($fraction)) {
            return null;
        }

        $fraction = (float) $fraction;

        return $fraction >= 0.0 && $fraction <= 1.0 ? $fraction : null;
    }
}
