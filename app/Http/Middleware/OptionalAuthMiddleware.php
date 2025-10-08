<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserRegistrationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuthMiddleware
{
    public function __construct(
        private UserRegistrationService $userRegistrationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isBot = $request->attributes->get('is_bot', false);

        if ($isBot) {
            // Handle bot authentication - create guest user for tracking
            $this->handleBotAuth($request);
        } else {
            // Handle human authentication - use standard sanctum auth
            $this->handleHumanAuth($request);
        }

        return $next($request);
    }

    /**
     * Handle authentication for bot requests
     */
    private function handleBotAuth(Request $request): void
    {
        // First, check if there's a valid authenticated user (e.g., via Bearer token)
        $token = $this->getTokenFromRequest($request);

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
                if ($user) {
                    // User is authenticated - don't override with bot guest user
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                    return;
                }
            }
        }

        // Check if bot access with guest users is enabled
        if (!config('bot-detection.bot_access.create_guest_users', true)) {
            return;
        }

        // For bots without authentication, create a guest user for view tracking purposes
        $botInfo = $request->attributes->get('bot_info', []);
        $guestUser = $this->createBotGuestUser($request, $botInfo);

        if ($guestUser) {
            // Set the bot guest user for the request
            $request->setUserResolver(function () use ($guestUser) {
                return $guestUser;
            });
        }
    }

    /**
     * Handle authentication for human requests
     */
    private function handleHumanAuth(Request $request): void
    {
        // Try to authenticate using Bearer token (standard Sanctum auth)
        $token = $this->getTokenFromRequest($request);
        
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
                if ($user) {
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                    return;
                }
            }
        }

        // If no valid token, create a temporary guest user for view tracking
        // This ensures proper view tracking for human users without accounts
        $guestUser = $this->createTemporaryGuestUser($request);
        
        if ($guestUser) {
            $request->setUserResolver(function () use ($guestUser) {
                return $guestUser;
            });
        }
    }

    /**
     * Create a temporary guest user for human users without authentication
     */
    private function createTemporaryGuestUser(Request $request): ?User
    {
        try {
            // Extract geo and device data for guest registration
            $registrationData = $this->userRegistrationService->extractRegistrationData($request);
            
            $timestamp = now()->format('YmdHis');
            
            // Create a temporary guest user with standard expiration
            $guestUser = User::create(array_merge([
                'name' => "Temp Guest {$timestamp}",
                'email' => 'temp_guest_' . Str::random(32) . '@lawexa.com',
                'password' => Hash::make(Str::random(64)),
                'role' => 'guest',
                'guest_expires_at' => now()->addDays(30),
                'last_activity_at' => now(),
            ], $registrationData));

            return $guestUser;
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::warning('Failed to create temporary guest user', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return null;
        }
    }

    /**
     * Create a guest user for bot tracking
     */
    private function createBotGuestUser(Request $request, array $botInfo): ?User
    {
        try {
            // Extract geo and device data for guest registration
            $registrationData = $this->userRegistrationService->extractRegistrationData($request);
            
            // Use bot name for more descriptive guest user names
            $botName = $botInfo['bot_name'] ?? 'Unknown Bot';
            $timestamp = now()->format('YmdHis');
            
            // Create a bot guest user with extended expiration
            $expirationDays = config('bot-detection.bot_access.guest_expiration_days', 90);
            
            $guestUser = User::create(array_merge([
                'name' => "Bot Guest ({$botName}) {$timestamp}",
                'email' => 'bot_guest_' . Str::random(32) . '@lawexa.com',
                'password' => Hash::make(Str::random(64)),
                'role' => 'guest',
                'guest_expires_at' => now()->addDays($expirationDays),
                'last_activity_at' => now(),
            ], $registrationData));

            return $guestUser;
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::warning('Failed to create bot guest user', [
                'error' => $e->getMessage(),
                'bot_info' => $botInfo,
                'ip' => $request->ip(),
            ]);
            
            return null;
        }
    }

    /**
     * Extract Bearer token from request
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }

        return $request->input('token');
    }
}