<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class TenantRateLimit
{
    /**
     * Handle an incoming request.
     *
     * Apply rate limiting based on tenant's subscription plan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found',
            ], 403);
        }

        $key = 'tenant:' . $tenant->id;
        $maxAttempts = $tenant->getRateLimit();
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Rate limit exceeded. Please upgrade your plan.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $maxAttempts - RateLimiter::attempts($key));

        return $response;
    }
}
