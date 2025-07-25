<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    private string $defaultDisk;
    private array $allowedImageTypes;
    private array $allowedDocumentTypes;
    private int $maxFileSize;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default', 'local');
        $this->allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->allowedDocumentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
        
        // Configure AWS SDK for Windows SSL issues
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            putenv('AWS_CA_BUNDLE=');
        }
    }

    /**
     * Upload a single file
     */
    public function uploadFile(
        UploadedFile $file, 
        string $category = 'general',
        ?string $disk = null,
        ?array $options = [],
        ?int $uploadedBy = null
    ): File {
        $disk = $disk ?? $this->defaultDisk;
        
        try {
            // Validate file
            $this->validateFile($file, $options);
            
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            
            // Create directory path
            $directory = $this->generateDirectoryPath($category);
            $fullPath = $directory . '/' . $filename;
            
            // Store file
            try {
                $storedPath = Storage::disk($disk)->putFileAs($directory, $file, $filename);
                
                if (!$storedPath) {
                    throw new Exception('Failed to store file - putFileAs returned false');
                }
            } catch (Exception $storageException) {
                Log::error('Storage operation failed', [
                    'disk' => $disk,
                    'directory' => $directory,
                    'filename' => $filename,
                    'error' => $storageException->getMessage(),
                    'trace' => $storageException->getTraceAsString()
                ]);
                throw new Exception('Storage failed: ' . $storageException->getMessage());
            }
            
            // Create file record
            $fileRecord = File::create([
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $storedPath,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => $category,
                'url' => $this->generateFileUrl($storedPath, $disk),
                'metadata' => $this->extractMetadata($file, $options),
                'uploaded_by' => $uploadedBy,
            ]);
            
            Log::info('File uploaded successfully', [
                'file_id' => $fileRecord->id,
                'filename' => $filename,
                'category' => $category
            ]);
            
            return $fileRecord;
            
        } catch (Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadFiles(
        array $files, 
        string $category = 'general',
        ?string $disk = null,
        ?array $options = [],
        ?int $uploadedBy = null
    ): array {
        $uploadedFiles = [];
        $errors = [];
        
        foreach ($files as $index => $file) {
            try {
                $uploadedFiles[] = $this->uploadFile($file, $category, $disk, $options, $uploadedBy);
            } catch (Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            Log::warning('Some files failed to upload', ['errors' => $errors]);
        }
        
        return [
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Delete a file
     */
    public function deleteFile(File $file): bool
    {
        try {
            // Delete from storage
            if (Storage::disk($file->disk)->exists($file->path)) {
                Storage::disk($file->disk)->delete($file->path);
            }
            
            // Delete database record
            $file->delete();
            
            Log::info('File deleted successfully', ['file_id' => $file->id]);
            return true;
            
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteFiles(array $fileIds): array
    {
        $deleted = [];
        $errors = [];
        
        $files = File::whereIn('id', $fileIds)->get();
        
        foreach ($files as $file) {
            if ($this->deleteFile($file)) {
                $deleted[] = $file->id;
            } else {
                $errors[] = $file->id;
            }
        }
        
        return [
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }

    /**
     * Get file URL
     */
    public function getFileUrl(File $file): string
    {
        if ($file->disk === 's3') {
            return Storage::disk('s3')->url($file->path);
        }
        
        return Storage::disk($file->disk)->url($file->path);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file, array $options = []): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }
        
        // Check file size
        $maxSize = $options['max_size'] ?? $this->maxFileSize;
        if ($file->getSize() > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB');
        }
        
        // Check file type
        $allowedTypes = $options['allowed_types'] ?? array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Security check - validate MIME type matches extension
        $this->validateMimeType($file, $extension);
    }

    /**
     * Validate MIME type matches file extension
     */
    private function validateMimeType(UploadedFile $file, string $extension): void
    {
        $mimeType = $file->getMimeType();
        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'rtf' => ['application/rtf', 'text/rtf']
        ];
        
        if (isset($allowedMimes[$extension]) && !in_array($mimeType, $allowedMimes[$extension])) {
            throw new Exception('File MIME type does not match extension');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid();
        return $uuid . '.' . $extension;
    }

    /**
     * Generate directory path based on category and date
     */
    private function generateDirectoryPath(string $category): string
    {
        $year = date('Y');
        $month = date('m');
        return "uploads/{$category}/{$year}/{$month}";
    }

    /**
     * Generate file URL
     */
    private function generateFileUrl(string $path, string $disk): string
    {
        if ($disk === 's3') {
            return Storage::disk('s3')->url($path);
        }
        
        return Storage::disk($disk)->url($path);
    }

    /**
     * Extract file metadata
     */
    private function extractMetadata(UploadedFile $file, array $options = []): array
    {
        $metadata = [
            'upload_ip' => request()->ip(),
            'upload_user_agent' => request()->userAgent(),
        ];
        
        // Add image-specific metadata
        if (in_array(strtolower($file->getClientOriginalExtension()), $this->allowedImageTypes)) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                }
            } catch (Exception $e) {
                // Ignore image info extraction errors
            }
        }
        
        return $metadata;
    }

    /**
     * Get allowed file types for validation
     */
    public function getAllowedTypes(): array
    {
        return array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }
}