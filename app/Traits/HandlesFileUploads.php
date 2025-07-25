<?php

namespace App\Traits;

use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

trait HandlesFileUploads
{
    /**
     * Upload files from request and attach to model
     */
    protected function handleFileUploads(
        Request $request, 
        $model, 
        string $inputName = 'files',
        string $category = 'general',
        ?string $disk = null
    ): array {
        if (!$request->hasFile($inputName)) {
            return ['uploaded' => [], 'errors' => []];
        }
        
        $fileUploadService = app(FileUploadService::class);
        $files = $request->file($inputName);
        
        // Handle single file or multiple files
        if (!is_array($files)) {
            $files = [$files];
        }
        
        try {
            $uploadedBy = $request->user() ? $request->user()->id : null;
            $result = $fileUploadService->uploadFiles($files, $category, $disk, [], $uploadedBy);
            
            // Attach uploaded files to the model
            foreach ($result['uploaded'] as $file) {
                $model->files()->save($file);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'uploaded' => [],
                'errors' => ['general' => $e->getMessage()]
            ];
        }
    }

    /**
     * Upload a single file and attach to model
     */
    protected function handleSingleFileUpload(
        Request $request,
        $model,
        string $inputName = 'file',
        string $category = 'general',
        ?string $disk = null
    ): ?File {
        if (!$request->hasFile($inputName)) {
            return null;
        }
        
        $fileUploadService = app(FileUploadService::class);
        $file = $request->file($inputName);
        
        try {
            $uploadedBy = $request->user() ? $request->user()->id : null;
            $uploadedFile = $fileUploadService->uploadFile($file, $category, $disk, [], $uploadedBy);
            
            // Attach file to the model
            $model->files()->save($uploadedFile);
            
            return $uploadedFile;
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete files associated with a model
     */
    protected function deleteModelFiles($model, array $fileIds = []): array
    {
        $fileUploadService = app(FileUploadService::class);
        
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
        
        return $fileUploadService->deleteFiles($fileIds);
    }

    /**
     * Replace files for a model
     */
    protected function replaceModelFiles(
        Request $request,
        $model,
        string $inputName = 'files',
        string $category = 'general',
        ?string $disk = null
    ): array {
        // Delete existing files
        $deleteResult = $this->deleteModelFiles($model);
        
        // Upload new files
        $uploadResult = $this->handleFileUploads($request, $model, $inputName, $category, $disk);
        
        return [
            'uploaded' => $uploadResult['uploaded'],
            'upload_errors' => $uploadResult['errors'],
            'deleted' => $deleteResult['deleted'],
            'delete_errors' => $deleteResult['errors']
        ];
    }

    /**
     * Get files for API response
     */
    protected function getModelFilesForResponse($model): array
    {
        return $model->files()->get()->map(function ($file) {
            return [
                'id' => $file->id,
                'name' => $file->original_name,
                'filename' => $file->filename,
                'size' => $file->size,
                'mime_type' => $file->mime_type,
                'url' => $file->url,
                'category' => $file->category,
                'created_at' => $file->created_at,
            ];
        })->toArray();
    }

    /**
     * Download a file (with authorization check)
     */
    protected function downloadFile(int $fileId, $model = null): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            
            // If model is provided, ensure file belongs to the model
            if ($model && !$model->files()->where('id', $fileId)->exists()) {
                return ApiResponse::error('File not found', 404);
            }
            
            $fileUploadService = app(FileUploadService::class);
            $url = $fileUploadService->getFileUrl($file);
            
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
     * Get file validation rules
     */
    protected function getFileValidationRules(array $allowedTypes = null, int $maxSize = null): array
    {
        $fileUploadService = app(FileUploadService::class);
        
        $rules = [
            'required',
            'file',
            'max:' . ($maxSize ?? ($fileUploadService->getMaxFileSize() / 1024)) // Convert to KB for Laravel validation
        ];
        
        if ($allowedTypes) {
            $rules[] = 'mimes:' . implode(',', $allowedTypes);
        } else {
            $rules[] = 'mimes:' . implode(',', $fileUploadService->getAllowedTypes());
        }
        
        return $rules;
    }

    /**
     * Get multiple files validation rules
     */
    protected function getMultipleFilesValidationRules(array $allowedTypes = null, int $maxSize = null, int $maxFiles = 10): array
    {
        return [
            'array',
            'max:' . $maxFiles,
            '*' => $this->getFileValidationRules($allowedTypes, $maxSize)
        ];
    }

    /**
     * Handle file upload errors and return appropriate response
     */
    protected function handleFileUploadErrors(array $errors): JsonResponse
    {
        if (empty($errors)) {
            return ApiResponse::success('Files uploaded successfully');
        }
        
        $errorMessage = 'Some files failed to upload: ' . implode(', ', $errors);
        return ApiResponse::error($errorMessage, 422);
    }
}