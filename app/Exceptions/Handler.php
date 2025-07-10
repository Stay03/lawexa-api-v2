<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Authorization\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    private function handleApiException(Request $request, Throwable $exception)
    {
        switch (true) {
            case $exception instanceof ValidationException:
                return ApiResponse::validation(
                    $exception->errors(),
                    'Validation failed'
                );

            case $exception instanceof AuthenticationException:
                return ApiResponse::unauthorized(
                    'Authentication required'
                );

            case $exception instanceof AuthorizationException:
                return ApiResponse::forbidden(
                    'Access denied'
                );

            case $exception instanceof ModelNotFoundException:
                return ApiResponse::notFound(
                    'Resource not found'
                );

            case $exception instanceof NotFoundHttpException:
                return ApiResponse::notFound(
                    'Endpoint not found'
                );

            case $exception instanceof MethodNotAllowedHttpException:
                return ApiResponse::error(
                    'Method not allowed',
                    null,
                    405
                );

            default:
                $message = config('app.debug') ? $exception->getMessage() : 'Internal server error';
                return ApiResponse::error(
                    $message,
                    config('app.debug') ? [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ] : null,
                    500
                );
        }
    }
}