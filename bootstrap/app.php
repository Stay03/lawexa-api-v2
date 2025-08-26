<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Authorization\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'track.views' => \App\Http\Middleware\ViewTrackingMiddleware::class,
            'track.guest.activity' => \App\Http\Middleware\TrackGuestActivity::class,
        ]);
        
        // View tracking is now handled per-route after authorization
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Http\Responses\ApiResponse::validation(
                    $e->errors(),
                    'Validation failed'
                );
            }
        });
        
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Http\Responses\ApiResponse::unauthorized('Authentication required');
            }
        });
        
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Http\Responses\ApiResponse::forbidden('Access denied');
            }
        });
        
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return \App\Http\Responses\ApiResponse::notFound($model . ' not found');
            }
        });
        
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Http\Responses\ApiResponse::notFound('Endpoint not found');
            }
        });
        
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Http\Responses\ApiResponse::error('Method not allowed', null, 405);
            }
        });
    })->create();
