<?php

namespace App\Http\Controllers;

use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystackService
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (!$this->verifySignature($payload, $signature)) {
            Log::warning('Invalid Paystack webhook signature', [
                'signature' => $signature,
                'payload' => $payload
            ]);
            
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (!$event) {
            Log::error('Invalid JSON in Paystack webhook', ['payload' => $payload]);
            return response('Invalid JSON', 400);
        }

        try {
            $this->paystackService->handleWebhookEvent($event);
            
            Log::info('Paystack webhook processed successfully', [
                'event' => $event['event'] ?? 'unknown'
            ]);
            
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error processing Paystack webhook', [
                'event' => $event['event'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response('Error processing webhook', 500);
        }
    }

    private function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));
        
        return hash_equals($computedSignature, $signature);
    }
}
