<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class Controller
{
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $statusCode, $meta);
    }

    protected function errorResponse(
        string $message = 'Error',
        $errors = null,
        int $statusCode = 400,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::error($message, $errors, $statusCode, $meta);
    }

    protected function validationErrorResponse(
        $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return ApiResponse::validation($errors, $message);
    }

    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return ApiResponse::unauthorized($message);
    }

    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return ApiResponse::forbidden($message);
    }

    protected function notFoundResponse(
        string $message = 'Not found'
    ): JsonResponse {
        return ApiResponse::notFound($message);
    }

    protected function createdResponse(
        $data = null,
        string $message = 'Created successfully',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::created($data, $message, $meta);
    }

    protected function resourceResponse(
        JsonResource $resource,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::resource($resource, $message, $statusCode, $meta);
    }

    protected function collectionResponse(
        ResourceCollection $collection,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return ApiResponse::collection($collection, $message, $statusCode);
    }
}
