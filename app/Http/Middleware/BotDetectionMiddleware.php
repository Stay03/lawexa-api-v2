<?php

namespace App\Http\Middleware;

use App\Services\DeviceDetectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BotDetectionMiddleware
{
    public function __construct(
        private DeviceDetectionService $deviceDetectionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect if request is from a bot
        $isBot = $this->deviceDetectionService->isBot($request);
        
        // Add bot information to request attributes
        $request->attributes->set('is_bot', $isBot);
        
        if ($isBot) {
            $botInfo = $this->deviceDetectionService->getBotInfo($request);
            $request->attributes->set('bot_info', $botInfo);
            
            // Log bot detection if enabled
            if (config('bot-detection.logging.log_bot_detections', false)) {
                $this->logBotDetection($request, $botInfo);
            }
        }

        return $next($request);
    }

    /**
     * Log bot detection for monitoring purposes
     */
    private function logBotDetection(Request $request, array $botInfo): void
    {
        $logData = [
            'bot_detected' => true,
            'bot_name' => $botInfo['bot_name'],
            'robot_name' => $botInfo['robot_name'],
            'is_search_engine' => $botInfo['is_search_engine'],
            'is_social_media' => $botInfo['is_social_media'],
            'url' => $request->url(),
            'method' => $request->method(),
        ];

        // Add user agent if configured
        if (config('bot-detection.logging.include_user_agent', true)) {
            $logData['user_agent'] = $botInfo['user_agent'];
        }

        // Add IP if configured
        if (config('bot-detection.logging.include_ip', true)) {
            $logData['ip_address'] = $request->ip();
        }

        Log::channel(config('bot-detection.logging.log_channel', 'single'))
           ->info('Bot request detected', $logData);
    }
}