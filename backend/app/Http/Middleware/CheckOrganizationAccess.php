<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationAccess
{
    /**
     * Handle an incoming request.
     * Ensures dealers and commercials only access their organization's data
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Admins can access everything
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Dealers and commercials must have an organization
        if (!$user->organization_id) {
            return response()->json(['message' => 'No organization assigned'], 403);
        }

        // Check if trying to access a specific organization's data
        $organizationId = $request->route('organization_id') ?? $request->input('organization_id');
        
        if ($organizationId && $organizationId != $user->organization_id) {
            return response()->json(['message' => 'Access denied to this organization'], 403);
        }

        return $next($request);
    }
}
