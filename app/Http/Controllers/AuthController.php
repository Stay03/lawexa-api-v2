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

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return ApiResponse::unauthorized('Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            
            // Send welcome email after verification
            $this->notificationService->sendWelcomeEmail($user);
        }

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()->load(['activeSubscription', 'subscriptions']))
        ], 'Email verified successfully');
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified');
        }

        Mail::to($request->user()->email)->queue(new VerifyEmailMailable($request->user()));

        return ApiResponse::success(null, 'Verification email sent');
    }
}
