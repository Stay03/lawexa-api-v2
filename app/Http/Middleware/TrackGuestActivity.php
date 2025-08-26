<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackGuestActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Update last activity for guest users after successful request
        $user = $request->user();
        if ($user && $user->isGuest()) {
            // Update activity timestamp synchronously with error handling
            // This is critical for guest cleanup functionality
            try {
                $user->update(['last_activity_at' => now()]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to update guest activity', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return $response;
    }
}
