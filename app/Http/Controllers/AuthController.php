<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use App\Services\SecurityLoggerService;
use App\Mail\VerifyEmailMailable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\PasswordResetMailable;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;

class AuthController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private SecurityLoggerService $securityLogger
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
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
            $this->securityLogger->logAuthenticationAttempt(
                $request->email,
                false,
                'Invalid credentials',
                $request
            );
            return ApiResponse::unauthorized('Invalid credentials');
        }

        $user = User::with(['activeSubscription', 'subscriptions'])->where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->securityLogger->logAuthenticationAttempt(
            $request->email,
            true,
            null,
            $request
        );

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->securityLogger->logUserLogout($request->user()->id, $request);
        
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

        $this->securityLogger->logProfileUpdate(
            $user->id,
            array_keys($updateData),
            $request
        );

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
                $this->securityLogger->logEmailVerification($user->id, false, 'Invalid verification hash', $request);
                return $this->handleVerificationResult($request, 'error', 'Invalid verification link');
            }

            if ($user->hasVerifiedEmail()) {
                $this->securityLogger->logEmailVerification($user->id, true, 'Email already verified', $request);
                return $this->handleVerificationResult($request, 'success', 'Email already verified');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
                
                $this->securityLogger->logEmailVerification($user->id, true, null, $request);
                
                // Send welcome email after verification
                $this->notificationService->sendWelcomeEmail($user);
            } else {
                $this->securityLogger->logEmailVerification($user->id, false, 'Failed to mark email as verified', $request);
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
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        
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




    public function createGuestSession(Request $request): JsonResponse
    {
        // Create a guest user with 30-day expiration
        $guestUser = User::create([
            'name' => 'Guest User',
            'email' => 'guest_' . Str::random(32) . '@guest.local', // Generate unique email for guests
            'password' => Hash::make(Str::random(64)), // Generate dummy password for guests
            'role' => 'guest',
            'guest_expires_at' => now()->addDays(30),
            'last_activity_at' => now(),
        ]);

        // Create token for the guest user
        $token = $guestUser->createToken('guest_token', [], now()->addDays(30))->plainTextToken;

        $this->securityLogger->logGuestSessionCreated($guestUser->id, $token, $request);

        return ApiResponse::success([
            'token' => $token,
            'guest_id' => $guestUser->id,
            'expires_at' => $guestUser->guest_expires_at->toISOString(),
        ], 'Guest session created successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->securityLogger->logPasswordResetAttempt(
                $request->email,
                false,
                'User not found',
                $request
            );
            return ApiResponse::error('We could not find an account with that email address.', null, 404);
        }

        if ($user->isGuest()) {
            return ApiResponse::error('Password reset is not available for guest accounts.', null, 400);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $resetUrl = "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($request->email);

        Mail::to($request->email)->queue(new PasswordResetMailable($user, $token, $resetUrl));

        $this->securityLogger->logPasswordResetAttempt(
            $request->email,
            true,
            null,
            $request
        );

        return ApiResponse::success(null, 'Password reset link sent to your email address.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->securityLogger->logPasswordResetAttempt(
                $request->email,
                false,
                'User not found during reset',
                $request
            );
            return ApiResponse::error('We could not find an account with that email address.', null, 404);
        }

        if ($user->isGuest()) {
            return ApiResponse::error('Password reset is not available for guest accounts.', null, 400);
        }

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord) {
            $this->securityLogger->logPasswordResetAttempt(
                $request->email,
                false,
                'No reset token found',
                $request
            );
            return ApiResponse::error('Invalid or expired reset token.', null, 400);
        }

        if (!Hash::check($request->token, $tokenRecord->token)) {
            $this->securityLogger->logPasswordResetAttempt(
                $request->email,
                false,
                'Invalid reset token',
                $request
            );
            return ApiResponse::error('Invalid or expired reset token.', null, 400);
        }

        $expireTime = config('auth.passwords.users.expire', 60);
        if (now()->diffInMinutes($tokenRecord->created_at) > $expireTime) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            $this->securityLogger->logPasswordResetAttempt(
                $request->email,
                false,
                'Expired reset token',
                $request
            );
            return ApiResponse::error('Reset token has expired. Please request a new one.', null, 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        event(new PasswordReset($user));

        $user->tokens()->delete();

        $this->securityLogger->logPasswordReset($user->id, $request);

        return ApiResponse::success(null, 'Password has been reset successfully. Please log in with your new password.');
    }

    public function validateResetToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors());
        }

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            return ApiResponse::error('Invalid or expired reset token.', null, 400);
        }

        $expireTime = config('auth.passwords.users.expire', 60);
        if (now()->diffInMinutes($tokenRecord->created_at) > $expireTime) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return ApiResponse::error('Reset token has expired. Please request a new one.', null, 400);
        }

        return ApiResponse::success([
            'valid' => true,
            'email' => $request->email
        ], 'Reset token is valid.');
    }
}
