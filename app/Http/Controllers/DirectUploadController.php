<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectUploadRequest;
use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\DirectS3UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class DirectUploadController extends Controller
{
    private DirectS3UploadService $uploadService;

    public function __construct(DirectS3UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Generate a pre-signed URL for direct S3 upload
     */
    public function generateUploadUrl(DirectUploadRequest $request): JsonResponse
    {
        try {
            $fileData = $request->getValidatedDataWithDefaults();
            
            $result = $this->uploadService->generateUploadUrl(
                $fileData, 
                $request->user()->id
            );

            return ApiResponse::success([
                'upload_url' => $result['upload_url'],
                'file_id' => $result['file_id'],
                'filename' => $result['filename'],
                'expires_at' => $result['expires_at'],
                'instructions' => [
                    'method' => 'PUT',
                    'headers' => [
                        'Content-Type' => $fileData['mime_type'],
                        'Content-Length' => $fileData['size'],
                    ],
                    'note' => 'Upload the file using a PUT request to the upload_url, then call the completion endpoint'
                ]
            ], 'Pre-signed upload URL generated successfully');

        } catch (Exception $e) {
            Log::error('Failed to generate upload URL', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return ApiResponse::error(
                'Failed to generate upload URL: ' . $e->getMessage(), 
                422
            );
        }
    }

    /**
     * Mark upload as completed after successful S3 upload
     */
    public function markUploadCompleted(Request $request, int $fileId): JsonResponse
    {
        try {
            $request->validate([
                'etag' => 'sometimes|string|max:255',
                'metadata' => 'sometimes|array',
            ]);

            $file = File::findOrFail($fileId);

            // Check if user owns this file or has admin access
            if ($file->uploaded_by !== $request->user()->id && !$request->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to complete this upload', 403);
            }

            if ($file->upload_status !== 'pending') {
                return ApiResponse::error('File is not in pending status', 422);
            }

            $metadata = $request->get('metadata', []);
            if ($request->has('etag')) {
                $metadata['client_etag'] = $request->get('etag');
            }

            $completedFile = $this->uploadService->markUploadCompleted($fileId, $metadata);

            return ApiResponse::success([
                'file' => [
                    'id' => $completedFile->id,
                    'original_name' => $completedFile->original_name,
                    'filename' => $completedFile->filename,
                    'size' => $completedFile->size,
                    'human_size' => $completedFile->human_size,
                    'mime_type' => $completedFile->mime_type,
                    'category' => $completedFile->category,
                    'upload_status' => $completedFile->upload_status,
                    'url' => $completedFile->url,
                    'created_at' => $completedFile->created_at,
                ]
            ], 'File upload completed successfully');

        } catch (Exception $e) {
            Log::error('Failed to mark upload as completed', [
                'file_id' => $fileId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Failed to complete upload: ' . $e->getMessage(), 
                422
            );
        }
    }

    /**
     * Mark upload as failed
     */
    public function markUploadFailed(Request $request, int $fileId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'sometimes|string|max:500',
            ]);

            $file = File::findOrFail($fileId);

            // Check if user owns this file or has admin access
            if ($file->uploaded_by !== $request->user()->id && !$request->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to modify this upload', 403);
            }

            $reason = $request->get('reason', 'Upload failed on client side');
            $failedFile = $this->uploadService->markUploadFailed($fileId, $reason);

            return ApiResponse::success([
                'file' => [
                    'id' => $failedFile->id,
                    'upload_status' => $failedFile->upload_status,
                    'failure_reason' => $failedFile->metadata['failure_reason'] ?? null,
                ]
            ], 'Upload marked as failed');

        } catch (Exception $e) {
            Log::error('Failed to mark upload as failed', [
                'file_id' => $fileId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Failed to mark upload as failed: ' . $e->getMessage(), 
                422
            );
        }
    }

    /**
     * Get upload status
     */
    public function getUploadStatus(Request $request, int $fileId): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);

            // Check if user owns this file or has admin access
            if ($file->uploaded_by !== $request->user()->id && !$request->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to view this upload', 403);
            }

            return ApiResponse::success([
                'file' => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'upload_status' => $file->upload_status,
                    'size' => $file->size,
                    'expected_size' => $file->metadata['expected_size'] ?? null,
                    'created_at' => $file->created_at,
                    'metadata' => $file->metadata,
                ]
            ], 'Upload status retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error('Upload not found', 404);
        }
    }

    /**
     * Cancel a pending upload
     */
    public function cancelUpload(Request $request, int $fileId): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);

            // Check if user owns this file or has admin access
            if ($file->uploaded_by !== $request->user()->id && !$request->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to cancel this upload', 403);
            }

            if ($file->upload_status !== 'pending') {
                return ApiResponse::error('Can only cancel pending uploads', 422);
            }

            // Delete the file record and any partial S3 upload
            $file->deleteFromStorage();

            return ApiResponse::success(null, 'Upload cancelled successfully');

        } catch (Exception $e) {
            Log::error('Failed to cancel upload', [
                'file_id' => $fileId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Failed to cancel upload: ' . $e->getMessage(), 
                422
            );
        }
    }

    /**
     * Get list of pending uploads for the current user
     */
    public function getPendingUploads(Request $request): JsonResponse
    {
        try {
            $query = File::where('uploaded_by', $request->user()->id)
                         ->pending()
                         ->orderBy('created_at', 'desc');

            $perPage = min($request->get('per_page', 15), 50);
            $files = $query->paginate($perPage);

            return ApiResponse::success([
                'files' => $files->items(),
                'pagination' => [
                    'current_page' => $files->currentPage(),
                    'last_page' => $files->lastPage(),
                    'per_page' => $files->perPage(),
                    'total' => $files->total(),
                ]
            ], 'Pending uploads retrieved successfully');

        } catch (Exception $e) {
            Log::error('Failed to retrieve pending uploads', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Failed to retrieve pending uploads', 
                500
            );
        }
    }

    /**
     * Admin endpoint to cleanup expired uploads
     */
    public function cleanupExpiredUploads(Request $request): JsonResponse
    {
        try {
            // Check if user has admin access
            if (!$request->user()->hasAdminAccess()) {
                return ApiResponse::error('Unauthorized to perform cleanup', 403);
            }

            $result = $this->uploadService->cleanupExpiredUploads();

            return ApiResponse::success([
                'deleted_count' => $result['deleted'],
                'error_count' => count($result['errors']),
                'errors' => $result['errors']
            ], 'Expired uploads cleanup completed');

        } catch (Exception $e) {
            Log::error('Failed to cleanup expired uploads', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Failed to cleanup expired uploads: ' . $e->getMessage(), 
                500
            );
        }
    }

    /**
     * Get allowed file types and size limits
     */
    public function getUploadConfig(): JsonResponse
    {
        try {
            return ApiResponse::success([
                'allowed_types' => $this->uploadService->getAllowedTypes(),
                'max_file_size' => 5 * 1024 * 1024 * 1024, // 5GB in bytes
                'max_file_size_human' => '5GB',
                'categories' => ['general', 'legal', 'case', 'document', 'image'],
                'upload_url_expiration_minutes' => 60,
            ], 'Upload configuration retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::error(
                'Failed to retrieve upload configuration', 
                500
            );
        }
    }
}