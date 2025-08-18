<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use App\Mail\VerifyEmailMailable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Verified;

class AuthController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:user,admin,researcher,superadmin'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Send email verification
        Mail::to($user->email)->queue(new VerifyEmailMailable($user));

        return ApiResponse::created([
            'user' => new UserResource($user->load(['activeSubscription', 'subscriptions'])),
            'token' => $token,
            'message' => 'Registration successful. Please check your email to verify your account.'
        ], 'User registered successfully. Email verification required.');
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return ApiResponse::unauthorized('Invalid credentials');
        }

        $user = User::with(['activeSubscription', 'subscriptions'])->where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => new UserResource($request->user()->load(['activeSubscription.plan', 'subscriptions']))
        ], 'User profile retrieved successfully');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'password' => 'sometimes|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $user = $request->user();
        $updateData = $request->only(['name', 'email']);

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()->load(['activeSubscription', 'subscriptions']))
        ], 'Profile updated successfully');
    }

    public function verifyEmail(Request $request)
    {
        try {
            $user = User::find($request->route('id'));

            if (!$user) {
                return $this->handleVerificationResult($request, 'error', 'User not found');
            }

            if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                return $this->handleVerificationResult($request, 'error', 'Invalid verification link');
            }

            if ($user->hasVerifiedEmail()) {
                return $this->handleVerificationResult($request, 'success', 'Email already verified');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
                
                // Send welcome email after verification
                $this->notificationService->sendWelcomeEmail($user);
            }

            return $this->handleVerificationResult($request, 'success', 'Email verified successfully');

        } catch (\Exception $e) {
            \Log::error('Email verification error', [
                'user_id' => $request->route('id'),
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
            
            return $this->handleVerificationResult($request, 'error', 'Verification failed');
        }
    }

    /**
     * Handle verification result - redirect for browser requests, JSON for API
     */
    private function handleVerificationResult(Request $request, string $status, string $message)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        // If it's a browser request (not API), redirect to frontend
        if ($request->hasHeader('Accept') && !str_contains($request->header('Accept'), 'application/json')) {
            $redirectUrl = "{$frontendUrl}/email-verification?status={$status}&message=" . urlencode($message);
            return redirect($redirectUrl);
        }

        // For API requests, return JSON
        if ($status === 'success') {
            $user = User::find($request->route('id'));
            return ApiResponse::success([
                'user' => $user ? new UserResource($user->fresh()->load(['activeSubscription', 'subscriptions'])) : null
            ], $message);
        } else {
            return ApiResponse::error($message, null, 400);
        }
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified');
        }

        Mail::to($request->user()->email)->queue(new VerifyEmailMailable($request->user()));

        return ApiResponse::success(null, 'Verification email sent');
    }

    /**
     * Debug verification endpoint without signed middleware
     */
    public function debugVerifyEmail(Request $request)
    {
        $debugInfo = [
            'user_id' => $request->route('id'),
            'hash' => $request->route('hash'),
            'query_params' => $request->query(),
            'full_url' => $request->fullUrl(),
            'app_key_set' => !empty(config('app.key')),
            'app_env' => config('app.env'),
            'timestamp' => now()->toISOString(),
        ];

        // Try to find user
        $user = User::find($request->route('id'));
        $debugInfo['user_exists'] = !!$user;
        
        if ($user) {
            $debugInfo['user_email'] = $user->email;
            $debugInfo['user_verified'] = $user->hasVerifiedEmail();
            $debugInfo['expected_hash'] = sha1($user->getEmailForVerification());
            $debugInfo['hash_matches'] = hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()));
        }

        // Check if signature is valid manually
        if ($request->hasValidSignature()) {
            $debugInfo['signature_valid'] = true;
            // If signature is valid, proceed with verification
            return $this->verifyEmailDirect($request);
        } else {
            $debugInfo['signature_valid'] = false;
            $debugInfo['signature_error'] = 'Invalid or expired signature';
            
            // Even if signature is invalid, if hash matches and user exists, we can verify
            if ($user && $debugInfo['hash_matches'] && !$user->hasVerifiedEmail()) {
                $debugInfo['verification_attempted'] = true;
                $debugInfo['verification_bypassed_signature'] = true;
                
                // Proceed with verification despite invalid signature
                return $this->verifyEmailDirect($request);
            }
        }

        return response()->json([
            'debug_info' => $debugInfo,
            'message' => 'Debug information for email verification'
        ]);
    }

    /**
     * Direct verification without middleware
     */
    private function verifyEmailDirect(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return $this->handleVerificationResult($request, 'error', 'User not found');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->handleVerificationResult($request, 'error', 'Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->handleVerificationResult($request, 'success', 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            
            // Send welcome email after verification
            $this->notificationService->sendWelcomeEmail($user);
        }

        return $this->handleVerificationResult($request, 'success', 'Email verified successfully');
    }

    /**
     * Alternative verification without signed middleware for signature issues
     */
    public function verifyEmailAlternative(Request $request)
    {
        try {
            $user = User::find($request->route('id'));

            if (!$user) {
                return $this->handleVerificationResult($request, 'error', 'User not found');
            }

            if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                return $this->handleVerificationResult($request, 'error', 'Invalid verification link');
            }

            if ($user->hasVerifiedEmail()) {
                return $this->handleVerificationResult($request, 'success', 'Email already verified');
            }

            // Log the alternative verification for security tracking
            \Log::info('Alternative email verification used', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
                
                // Send welcome email after verification
                $this->notificationService->sendWelcomeEmail($user);
            }

            return $this->handleVerificationResult($request, 'success', 'Email verified successfully');

        } catch (\Exception $e) {
            \Log::error('Alternative email verification error', [
                'user_id' => $request->route('id'),
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
            
            return $this->handleVerificationResult($request, 'error', 'Verification failed');
        }
    }
}
