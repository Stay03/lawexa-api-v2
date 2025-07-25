<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\File;
use App\Services\DirectS3UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class S3WebhookController extends Controller
{
    private DirectS3UploadService $uploadService;

    public function __construct(DirectS3UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Handle S3 webhook notifications
     * This endpoint receives notifications from AWS S3 when objects are created
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('S3 webhook received', ['payload' => $payload]);

            // Handle SNS message format
            if ($request->has('Type') && $request->input('Type') === 'SubscriptionConfirmation') {
                return $this->handleSubscriptionConfirmation($request);
            }

            // Handle S3 event notification
            if ($request->has('Records')) {
                return $this->handleS3Events($request->input('Records', []));
            }

            // Handle SNS notification wrapping S3 events
            if ($request->has('Message')) {
                $message = json_decode($request->input('Message'), true);
                if (isset($message['Records'])) {
                    return $this->handleS3Events($message['Records']);
                }
            }

            Log::warning('Unhandled S3 webhook format', ['payload' => $payload]);
            
            return ApiResponse::success(null, 'Webhook received but not processed');

        } catch (Exception $e) {
            Log::error('S3 webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            // Return success to prevent AWS from retrying
            return ApiResponse::success(null, 'Webhook received with errors');
        }
    }

    /**
     * Handle SNS subscription confirmation
     */
    private function handleSubscriptionConfirmation(Request $request): JsonResponse
    {
        $subscribeUrl = $request->input('SubscribeURL');
        
        if ($subscribeUrl) {
            Log::info('SNS subscription confirmation received', [
                'subscribe_url' => $subscribeUrl
            ]);
            
            // In production, you might want to automatically confirm the subscription
            // file_get_contents($subscribeUrl);
        }

        return ApiResponse::success(null, 'Subscription confirmation received');
    }

    /**
     * Handle S3 event records
     */
    private function handleS3Events(array $records): JsonResponse
    {
        $processedEvents = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                if ($this->isS3ObjectCreatedEvent($record)) {
                    $this->processObjectCreatedEvent($record);
                    $processedEvents++;
                }
            } catch (Exception $e) {
                $errors[] = [
                    'record' => $record,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('S3 events processed', [
            'processed' => $processedEvents,
            'errors' => count($errors)
        ]);

        return ApiResponse::success([
            'processed_events' => $processedEvents,
            'errors' => $errors
        ], 'S3 events processed');
    }

    /**
     * Check if the record is an S3 object created event
     */
    private function isS3ObjectCreatedEvent(array $record): bool
    {
        return isset($record['eventSource']) && 
               $record['eventSource'] === 'aws:s3' &&
               isset($record['eventName']) &&
               str_starts_with($record['eventName'], 'ObjectCreated:');
    }

    /**
     * Process S3 object created event
     */
    private function processObjectCreatedEvent(array $record): void
    {
        if (!isset($record['s3']['object']['key'])) {
            throw new Exception('Missing S3 object key in webhook');
        }

        $objectKey = urldecode($record['s3']['object']['key']);
        $objectSize = $record['s3']['object']['size'] ?? 0;
        $etag = $record['s3']['object']['eTag'] ?? null;

        Log::info('Processing S3 object created event', [
            'key' => $objectKey,
            'size' => $objectSize,
            'etag' => $etag
        ]);

        // Find the pending file record by path
        $file = File::where('path', $objectKey)
                   ->where('upload_status', 'pending')
                   ->first();

        if (!$file) {
            Log::warning('No pending file found for S3 object', [
                'key' => $objectKey
            ]);
            return;
        }

        // Mark upload as completed
        $metadata = [
            's3_webhook_etag' => $etag,
            's3_webhook_size' => $objectSize,
            'webhook_processed_at' => now()->toISOString(),
        ];

        $this->uploadService->markUploadCompleted($file->id, $metadata);

        Log::info('File upload completed via S3 webhook', [
            'file_id' => $file->id,
            'object_key' => $objectKey
        ]);
    }

    /**
     * Health check endpoint for S3 webhook
     */
    public function health(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => 'S3 Webhook Handler'
        ], 'S3 webhook handler is healthy');
    }
}