<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\FeedbackImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FeedbackService
{
    private string $defaultDisk;
    private int $maxImages;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default', 's3');
        $this->maxImages = 4;

        // Configure AWS SDK for Windows SSL issues
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            putenv('AWS_CA_BUNDLE=');
        }
    }

    /**
     * Create feedback with images
     */
    public function createFeedback(array $data, ?array $images = null): Feedback
    {
        DB::beginTransaction();

        try {
            // Create feedback record
            $feedback = Feedback::create([
                'user_id' => $data['user_id'],
                'feedback_text' => $data['feedback_text'],
                'content_type' => $data['content_type'] ?? null,
                'content_id' => $data['content_id'] ?? null,
                'page' => $data['page'] ?? null,
                'status' => 'pending',
            ]);

            // Upload images if provided
            if ($images && !empty($images)) {
                $this->uploadFeedbackImages($feedback, $images);
            }

            DB::commit();

            // Load relationships
            $feedback->load(['user', 'images', 'content']);

            Log::info('Feedback created successfully', [
                'feedback_id' => $feedback->id,
                'user_id' => $feedback->user_id,
                'images_count' => $images ? count($images) : 0,
            ]);

            return $feedback;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Feedback creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $data['user_id'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Upload feedback images to S3
     */
    public function uploadFeedbackImages(Feedback $feedback, array $images): array
    {
        $uploadedImages = [];

        try {
            // Validate max images
            if (count($images) > $this->maxImages) {
                throw new Exception("Maximum {$this->maxImages} images allowed per feedback");
            }

            foreach ($images as $index => $image) {
                if (!$image instanceof UploadedFile) {
                    continue;
                }

                // Validate image
                $this->validateImage($image);

                // Upload to S3
                $path = $this->uploadImageToS3($image, $feedback->id);

                // Create image record
                $feedbackImage = FeedbackImage::create([
                    'feedback_id' => $feedback->id,
                    'image_path' => $path,
                    'order' => $index,
                ]);

                $uploadedImages[] = $feedbackImage;

                Log::info('Feedback image uploaded', [
                    'feedback_id' => $feedback->id,
                    'image_id' => $feedbackImage->id,
                    'path' => $path,
                ]);
            }

            return $uploadedImages;

        } catch (Exception $e) {
            // Clean up uploaded images if any error occurs
            $this->cleanupUploadedImages($uploadedImages);
            throw $e;
        }
    }

    /**
     * Upload single image to S3
     */
    private function uploadImageToS3(UploadedFile $image, int $feedbackId): string
    {
        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($image);

            // Create directory path
            $directory = $this->generateDirectoryPath($feedbackId);
            $fullPath = $directory . '/' . $filename;

            // Upload to S3
            $storedPath = Storage::disk($this->defaultDisk)->putFileAs($directory, $image, $filename);

            if (!$storedPath) {
                throw new Exception('Failed to upload image to S3');
            }

            return $storedPath;

        } catch (Exception $e) {
            Log::error('S3 upload failed', [
                'feedback_id' => $feedbackId,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Image upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate uploaded image
     */
    private function validateImage(UploadedFile $image): void
    {
        // Check if file is valid
        if (!$image->isValid()) {
            throw new Exception('Invalid image upload');
        }

        // Check file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($image->getSize() > $maxSize) {
            throw new Exception('Image size exceeds maximum allowed size of 5MB');
        }

        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($image->getClientOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Invalid image type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Validate MIME type
        $mimeType = $image->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Invalid image MIME type');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        $uuid = Str::uuid();
        return $uuid . '.' . $extension;
    }

    /**
     * Generate directory path for feedback images
     */
    private function generateDirectoryPath(int $feedbackId): string
    {
        $year = date('Y');
        $month = date('m');
        return "feedback/{$year}/{$month}/{$feedbackId}";
    }

    /**
     * Clean up uploaded images (in case of error)
     */
    private function cleanupUploadedImages(array $uploadedImages): void
    {
        foreach ($uploadedImages as $image) {
            try {
                if (Storage::disk($this->defaultDisk)->exists($image->image_path)) {
                    Storage::disk($this->defaultDisk)->delete($image->image_path);
                }
                $image->delete();
            } catch (Exception $e) {
                Log::error('Failed to cleanup uploaded image', [
                    'image_id' => $image->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete feedback images from S3
     */
    public function deleteFeedbackImages(Feedback $feedback): bool
    {
        try {
            foreach ($feedback->images as $image) {
                // Delete from S3
                if (Storage::disk($this->defaultDisk)->exists($image->image_path)) {
                    Storage::disk($this->defaultDisk)->delete($image->image_path);
                }

                // Delete database record
                $image->delete();
            }

            Log::info('Feedback images deleted', [
                'feedback_id' => $feedback->id,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete feedback images', [
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get max images allowed
     */
    public function getMaxImages(): int
    {
        return $this->maxImages;
    }
}
