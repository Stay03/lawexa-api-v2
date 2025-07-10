<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public static function success(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    public static function error(
        string $message = 'Error',
        $errors = null,
        int $statusCode = 400,
        array $meta = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    public static function validation(
        $errors,
        string $message = 'Validation failed',
        int $statusCode = 422
    ): JsonResponse {
        return self::error($message, $errors, $statusCode);
    }

    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return self::error($message, null, 401);
    }

    public static function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return self::error($message, null, 403);
    }

    public static function notFound(
        string $message = 'Not found'
    ): JsonResponse {
        return self::error($message, null, 404);
    }

    public static function created(
        $data = null,
        string $message = 'Created successfully',
        array $meta = []
    ): JsonResponse {
        return self::success($data, $message, 201, $meta);
    }

    public static function resource(
        JsonResource $resource,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $resource->toArray(request()),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    public static function collection(
        ResourceCollection $collection,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $data = $collection->toArray(request());
        
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data['data'],
        ];

        if (isset($data['meta'])) {
            $response['meta'] = $data['meta'];
        }

        if (isset($data['links'])) {
            $response['links'] = $data['links'];
        }

        return response()->json($response, $statusCode);
    }
}