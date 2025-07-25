<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class DirectS3UploadService
{
    private S3Client $s3Client;
    private string $bucket;
    private array $allowedImageTypes;
    private array $allowedDocumentTypes;
    private int $urlExpirationMinutes;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
        $this->allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->allowedDocumentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $this->urlExpirationMinutes = 60; // 1 hour expiration for pre-signed URLs
        
        // Initialize S3 client
        $this->s3Client = new S3Client([
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
    }

    /**
     * Generate a pre-signed URL for direct S3 upload
     */
    public function generateUploadUrl(array $fileData, ?int $uploadedBy = null): array
    {
        try {
            // Validate file data
            $this->validateFileData($fileData);
            
            // Generate unique filename and path
            $filename = $this->generateUniqueFilename($fileData['original_name']);
            $path = $this->generateDirectoryPath($fileData['category']) . '/' . $filename;
            
            // Create pending file record
            $fileRecord = $this->createPendingFileRecord($fileData, $filename, $path, $uploadedBy);
            
            // Generate pre-signed URL
            $presignedUrl = $this->createPresignedUrl($path, $fileData);
            
            Log::info('Pre-signed URL generated successfully', [
                'file_id' => $fileRecord->id,
                'filename' => $filename,
                'category' => $fileData['category']
            ]);
            
            return [
                'upload_url' => $presignedUrl,
                'file_id' => $fileRecord->id,
                'filename' => $filename,
                'path' => $path,
                'expires_at' => now()->addMinutes($this->urlExpirationMinutes)->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to generate pre-signed URL', [
                'error' => $e->getMessage(),
                'file_data' => $fileData
            ]);
            throw $e;
        }
    }

    /**
     * Mark file upload as completed and update metadata
     */
    public function markUploadCompleted(int $fileId, array $metadata = []): File
    {
        try {
            $file = File::findOrFail($fileId);
            
            if ($file->upload_status !== 'pending') {
                throw new Exception('File is not in pending status');
            }
            
            // Get file info from S3
            $s3FileInfo = $this->getS3FileInfo($file->path);
            
            // Update file record
            $file->update([
                'upload_status' => 'completed',
                'size' => $s3FileInfo['size'],
                'metadata' => array_merge($file->metadata ?? [], $metadata, [
                    'completed_at' => now()->toISOString(),
                    's3_etag' => $s3FileInfo['etag'] ?? null,
                ]),
                'url' => Storage::disk('s3')->url($file->path),
            ]);
            
            Log::info('File upload marked as completed', [
                'file_id' => $fileId,
                'size' => $s3FileInfo['size']
            ]);
            
            return $file->fresh();
            
        } catch (Exception $e) {
            Log::error('Failed to mark upload as completed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mark file upload as failed
     */
    public function markUploadFailed(int $fileId, string $reason = ''): File
    {
        try {
            $file = File::findOrFail($fileId);
            
            $file->update([
                'upload_status' => 'failed',
                'metadata' => array_merge($file->metadata ?? [], [
                    'failed_at' => now()->toISOString(),
                    'failure_reason' => $reason,
                ]),
            ]);
            
            Log::warning('File upload marked as failed', [
                'file_id' => $fileId,
                'reason' => $reason
            ]);
            
            return $file->fresh();
            
        } catch (Exception $e) {
            Log::error('Failed to mark upload as failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up expired pending uploads
     */
    public function cleanupExpiredUploads(): array
    {
        try {
            $expiredFiles = File::where('upload_status', 'pending')
                ->where('created_at', '<', now()->subHours(2))
                ->get();
            
            $deletedCount = 0;
            $errors = [];
            
            foreach ($expiredFiles as $file) {
                try {
                    // Delete from S3 if it exists
                    if (Storage::disk('s3')->exists($file->path)) {
                        Storage::disk('s3')->delete($file->path);
                    }
                    
                    // Delete database record
                    $file->delete();
                    $deletedCount++;
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'file_id' => $file->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            Log::info('Expired uploads cleanup completed', [
                'deleted' => $deletedCount,
                'errors' => count($errors)
            ]);
            
            return [
                'deleted' => $deletedCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup expired uploads', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate file data before generating upload URL
     */
    private function validateFileData(array $fileData): void
    {
        $required = ['original_name', 'mime_type', 'size', 'category'];
        foreach ($required as $field) {
            if (!isset($fileData[$field]) || empty($fileData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($fileData['original_name'], PATHINFO_EXTENSION));
        $allowedTypes = array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Validate MIME type matches extension
        $this->validateMimeType($fileData['mime_type'], $extension);
        
        // No file size limit for direct uploads - S3 can handle up to 5TB
        // But we can add a reasonable upper limit if needed
        $maxSize = 1024 * 1024 * 1024 * 5; // 5GB limit
        if ($fileData['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size of 5GB');
        }
    }

    /**
     * Validate MIME type matches file extension
     */
    private function validateMimeType(string $mimeType, string $extension): void
    {
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
    public function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uuid = Str::uuid();
        return $uuid . '.' . $extension;
    }

    /**
     * Generate directory path based on category and date
     */
    public function generateDirectoryPath(string $category): string
    {
        $year = date('Y');
        $month = date('m');
        return "uploads/{$category}/{$year}/{$month}";
    }

    /**
     * Create pending file record in database
     */
    private function createPendingFileRecord(array $fileData, string $filename, string $path, ?int $uploadedBy): File
    {
        return File::create([
            'original_name' => $fileData['original_name'],
            'filename' => $filename,
            'path' => $path,
            'disk' => 's3',
            'mime_type' => $fileData['mime_type'],
            'size' => 0, // Will be updated when upload completes
            'category' => $fileData['category'],
            'upload_status' => 'pending',
            'metadata' => [
                'upload_ip' => request()->ip(),
                'upload_user_agent' => request()->userAgent(),
                'expected_size' => $fileData['size'],
                'initiated_at' => now()->toISOString(),
            ],
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * Create pre-signed URL for S3 upload
     */
    private function createPresignedUrl(string $path, array $fileData): string
    {
        try {
            $command = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ContentType' => $fileData['mime_type'],
                'ContentLength' => $fileData['size'],
                'Metadata' => [
                    'original-name' => $fileData['original_name'],
                    'category' => $fileData['category'],
                    'uploaded-by' => request()->user()->id ?? 'anonymous',
                ]
            ]);
            
            $request = $this->s3Client->createPresignedRequest(
                $command,
                '+' . $this->urlExpirationMinutes . ' minutes'
            );
            
            return (string) $request->getUri();
            
        } catch (AwsException $e) {
            Log::error('AWS S3 error creating pre-signed URL', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            throw new Exception('Failed to create upload URL: ' . $e->getMessage());
        }
    }

    /**
     * Get file information from S3
     */
    private function getS3FileInfo(string $path): array
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            
            return [
                'size' => $result['ContentLength'] ?? 0,
                'etag' => trim($result['ETag'] ?? '', '"'),
                'last_modified' => $result['LastModified'] ?? null,
            ];
            
        } catch (AwsException $e) {
            Log::error('Failed to get S3 file info', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to get file information from S3');
        }
    }

    /**
     * Get allowed file types
     */
    public function getAllowedTypes(): array
    {
        return array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
    }
}