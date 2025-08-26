<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized - Authentication required'
            ], 401);
        }

        $user = $request->user();

        // Check if guest user is expired
        if ($user->isGuest() && $user->isGuestExpired()) {
            $user->currentAccessToken()->delete();
            return response()->json([
                'message' => 'Session expired'
            ], 401);
        }

        $userRole = $user->role;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Forbidden - Insufficient permissions',
                'required_roles' => $roles,
                'user_role' => $userRole
            ], 403);
        }

        return $next($request);
    }
}
