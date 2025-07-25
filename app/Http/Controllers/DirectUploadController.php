<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectUploadRequest;
use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\DirectS3UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * Simple upload endpoint that handles the entire direct S3 upload process
     */
    public function simpleUpload(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|max:' . (100 * 1024), // 100MB in KB
                'category' => 'sometimes|string|in:general,legal,case,document,image',
            ]);

            $category = $request->get('category', 'general');
            $uploadedFiles = [];
            $errors = [];
            
            foreach ($request->file('files') as $uploadedFile) {
                try {
                    // Extract file information
                    $fileData = [
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $uploadedFile->getMimeType(),
                        'size' => $uploadedFile->getSize(),
                        'category' => $category,
                    ];

                    // Use the existing service to handle the upload process
                    $result = $this->uploadService->generateUploadUrl($fileData, $request->user()->id);
                    
                    // Get the file content and upload it directly using the service's S3 client
                    $fileContent = file_get_contents($uploadedFile->getRealPath());
                    
                    // Upload directly to S3 using the service's existing S3 client
                    $s3Client = new \Aws\S3\S3Client([
                        'version' => 'latest',
                        'region' => config('filesystems.disks.s3.region'),
                        'credentials' => [
                            'key' => config('filesystems.disks.s3.key'),
                            'secret' => config('filesystems.disks.s3.secret'),
                        ],
                        'http' => [
                            'verify' => false, // For development - should be true in production
                        ],
                    ]);
                    
                    $s3Client->putObject([
                        'Bucket' => config('filesystems.disks.s3.bucket'),
                        'Key' => $result['path'],
                        'Body' => $fileContent,
                        'ContentType' => $fileData['mime_type'],
                        'Metadata' => [
                            'original-name' => $fileData['original_name'],
                            'category' => $category,
                            'uploaded-by' => (string) $request->user()->id,
                        ]
                    ]);
                    
                    // Mark the upload as completed
                    $fileRecord = $this->uploadService->markUploadCompleted($result['file_id']);

                    $uploadedFiles[] = [
                        'id' => $fileRecord->id,
                        'original_name' => $fileRecord->original_name,
                        'filename' => $fileRecord->filename,
                        'size' => $fileRecord->size,
                        'human_size' => $fileRecord->human_size,
                        'mime_type' => $fileRecord->mime_type,
                        'category' => $fileRecord->category,
                        'upload_status' => $fileRecord->upload_status,
                        'url' => $fileRecord->url,
                        'created_at' => $fileRecord->created_at,
                    ];

                    Log::info('File uploaded successfully via simple upload', [
                        'file_id' => $fileRecord->id,
                        'filename' => $filename,
                        'size' => $fileData['size']
                    ]);

                } catch (Exception $e) {
                    $errors[] = [
                        'filename' => $uploadedFile->getClientOriginalName() ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to upload file via simple upload', [
                        'filename' => $uploadedFile->getClientOriginalName() ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $response_data = [
                'files' => $uploadedFiles,
                'uploaded_count' => count($uploadedFiles),
                'failed_count' => count($errors),
            ];

            if (!empty($errors)) {
                $response_data['errors'] = $errors;
            }

            $message = count($uploadedFiles) > 0 
                ? 'Files uploaded successfully' 
                : 'No files were uploaded successfully';

            return ApiResponse::success($response_data, $message);

        } catch (Exception $e) {
            Log::error('Simple upload request failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                'Upload failed: ' . $e->getMessage(), 
                422
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