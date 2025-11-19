<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * Loads the tenant from the authenticated user and ensures it's active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json([
                'error' => 'No tenant associated with this user',
            ], 403);
        }

        if (!$tenant->is_active) {
            return response()->json([
                'error' => 'Tenant account is inactive',
            ], 403);
        }

        // Make tenant available throughout the request
        $request->merge(['tenant' => $tenant]);
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
