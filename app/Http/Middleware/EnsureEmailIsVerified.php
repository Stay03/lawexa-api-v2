<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return ApiResponse::forbidden(
                'Your email address is not verified. Please check your email for a verification link.',
                [
                    'verified' => false,
                    'message' => 'Email verification required'
                ]
            );
        }

        // Allow guest users to proceed without email verification
        if ($request->user()->isGuest()) {
            return $next($request);
        }

        // Check email verification for regular users
        if ($request->user() instanceof MustVerifyEmail &&
            !$request->user()->hasVerifiedEmail()) {
            return ApiResponse::forbidden(
                'Your email address is not verified. Please check your email for a verification link.',
                [
                    'verified' => false,
                    'message' => 'Email verification required'
                ]
            );
        }

        return $next($request);
    }
}