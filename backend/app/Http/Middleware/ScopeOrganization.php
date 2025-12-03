<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admin users can access everything - no filtering needed
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Dealer users can only access their organization's data
        if ($user->isDealer() && !$user->organization_id) {
            return response()->json([
                'message' => 'User must belong to an organization'
            ], 403);
        }

        // Add organization_id to request for filtering
        $request->merge(['scoped_organization_id' => $user->organization_id]);

        return $next($request);
    }
}
