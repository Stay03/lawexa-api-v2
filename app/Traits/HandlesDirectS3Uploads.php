<?php

namespace App\Traits;

use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\DirectS3UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

trait HandlesDirectS3Uploads
{
    /**
     * Upload files from request directly to S3 and attach to model
     */
    protected function handleDirectS3FileUploads(
        Request $request, 
        $model, 
        string $inputName = 'files',
        string $category = 'general',
        ?int $uploadedBy = null
    ): array {
        if (!$request->hasFile($inputName)) {
            return ['uploaded' => [], 'errors' => []];
        }
        
        $directS3UploadService = app(DirectS3UploadService::class);
        $files = $request->file($inputName);
        
        // Handle single file or multiple files
        if (!is_array($files)) {
            $files = [$files];
        }
        
        $uploadedFiles = [];
        $errors = [];
        $uploadedBy = $uploadedBy ?? ($request->user() ? $request->user()->id : null);
        
        foreach ($files as $uploadedFile) {
            try {
                // Extract file information
                $fileData = [
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize(),
                    'category' => $category,
                ];

                // Use the existing service to handle the upload process
                $result = $directS3UploadService->generateUploadUrl($fileData, $uploadedBy);
                
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
                        'uploaded-by' => (string) $uploadedBy,
                    ]
                ]);
                
                // Mark the upload as completed
                $fileRecord = $directS3UploadService->markUploadCompleted($result['file_id']);
                
                // Attach file to the model
                $model->files()->save($fileRecord);
                
                $uploadedFiles[] = $fileRecord;
                
                Log::info('File uploaded successfully via direct S3 upload for model', [
                    'file_id' => $fileRecord->id,
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                    'filename' => $result['filename'],
                    'category' => $category
                ]);

            } catch (Exception $e) {
                $errors[] = [
                    'filename' => $uploadedFile->getClientOriginalName() ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to upload file via direct S3 upload for model', [
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                    'filename' => $uploadedFile->getClientOriginalName() ?? 'unknown',
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Upload a single file directly to S3 and attach to model
     */
    protected function handleDirectS3SingleFileUpload(
        Request $request,
        $model,
        string $inputName = 'file',
        string $category = 'general',
        ?int $uploadedBy = null
    ): ?File {
        if (!$request->hasFile($inputName)) {
            return null;
        }
        
        $result = $this->handleDirectS3FileUploads($request, $model, $inputName, $category, $uploadedBy);
        
        if (!empty($result['uploaded'])) {
            return $result['uploaded'][0];
        }
        
        if (!empty($result['errors'])) {
            throw new Exception($result['errors'][0]['error']);
        }
        
        return null;
    }

    /**
     * Delete S3 files associated with a model
     */
    protected function deleteDirectS3ModelFiles($model, array $fileIds = []): array
    {
        if (empty($fileIds)) {
            // Delete all files associated with the model
            $fileIds = $model->files()->pluck('id')->toArray();
        } else {
            // Only delete specified files that belong to the model
            $fileIds = $model->files()->whereIn('id', $fileIds)->pluck('id')->toArray();
        }
        
        if (empty($fileIds)) {
            return ['deleted' => [], 'errors' => []];
        }
        
        $deleted = [];
        $errors = [];
        
        foreach ($fileIds as $fileId) {
            try {
                $file = File::findOrFail($fileId);
                
                // Delete from S3 if it exists
                if ($file->disk === 's3') {
                    \Illuminate\Support\Facades\Storage::disk('s3')->delete($file->path);
                }
                
                // Remove association with model
                $model->files()->detach($fileId);
                
                // Delete database record
                $file->delete();
                
                $deleted[] = $fileId;
                
                Log::info('File deleted successfully from S3 and model', [
                    'file_id' => $fileId,
                    'model_type' => get_class($model),
                    'model_id' => $model->id
                ]);
                
            } catch (Exception $e) {
                $errors[] = [
                    'file_id' => $fileId,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to delete S3 file from model', [
                    'file_id' => $fileId,
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }

    /**
     * Replace S3 files for a model
     */
    protected function replaceDirectS3ModelFiles(
        Request $request,
        $model,
        string $inputName = 'files',
        string $category = 'general',
        ?int $uploadedBy = null
    ): array {
        // Delete existing files
        $deleteResult = $this->deleteDirectS3ModelFiles($model);
        
        // Upload new files
        $uploadResult = $this->handleDirectS3FileUploads($request, $model, $inputName, $category, $uploadedBy);
        
        return [
            'uploaded' => $uploadResult['uploaded'],
            'upload_errors' => $uploadResult['errors'],
            'deleted' => $deleteResult['deleted'],
            'delete_errors' => $deleteResult['errors']
        ];
    }

    /**
     * Get S3 files for API response
     */
    protected function getDirectS3ModelFilesForResponse($model): array
    {
        return $model->files()->get()->map(function ($file) {
            return [
                'id' => $file->id,
                'name' => $file->original_name,
                'filename' => $file->filename,
                'size' => $file->size,
                'human_size' => $file->human_size,
                'mime_type' => $file->mime_type,
                'url' => $file->url,
                'category' => $file->category,
                'upload_status' => $file->upload_status,
                'disk' => $file->disk,
                'created_at' => $file->created_at,
            ];
        })->toArray();
    }

    /**
     * Download an S3 file (with authorization check)
     */
    protected function downloadDirectS3File(int $fileId, $model = null): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            
            // If model is provided, ensure file belongs to the model
            if ($model && !$model->files()->where('id', $fileId)->exists()) {
                return ApiResponse::error('File not found', 404);
            }
            
            // Generate signed URL for S3 files
            if ($file->disk === 's3') {
                $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                    $file->path,
                    now()->addMinutes(60) // 1 hour expiration
                );
            } else {
                $url = $file->url;
            }
            
            return ApiResponse::success([
                'download_url' => $url,
                'filename' => $file->original_name,
                'size' => $file->size,
                'mime_type' => $file->mime_type
            ]);
            
        } catch (Exception $e) {
            return ApiResponse::error('File not found', 404);
        }
    }

    /**
     * Handle S3 file upload errors and return appropriate response
     */
    protected function handleDirectS3FileUploadErrors(array $errors): JsonResponse
    {
        if (empty($errors)) {
            return ApiResponse::success('Files uploaded successfully to S3');
        }
        
        $errorMessage = 'Some files failed to upload to S3: ' . implode(', ', array_column($errors, 'error'));
        return ApiResponse::error($errorMessage, 422);
    }
}