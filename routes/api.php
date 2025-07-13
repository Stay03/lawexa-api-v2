<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\PaystackWebhookController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    Route::post('google/exchange', [GoogleAuthController::class, 'exchangeCodeForToken']);
});

// Paystack webhook (no auth required)
Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle']);

// Public plans endpoint (no auth required)
Route::get('plans', [PlanController::class, 'index']);
Route::get('plans/{plan}', [PlanController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('profile', function (Request $request) {
            $user = $request->user()->load(['activeSubscription', 'subscriptions']);
            return \App\Http\Responses\ApiResponse::success([
                'user' => new \App\Http\Resources\UserResource($user)
            ], 'User profile retrieved successfully');
        });
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });

    // User subscription routes
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::get('{subscription}', [SubscriptionController::class, 'show']);
        Route::post('{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('{subscription}/reactivate', [SubscriptionController::class, 'reactivate']);
        Route::get('{subscription}/invoices', [SubscriptionController::class, 'invoices']);
        Route::get('{subscription}/management-link', [SubscriptionController::class, 'managementLink']);
        Route::post('{subscription}/management-email', [SubscriptionController::class, 'sendManagementEmail']);
        Route::post('{subscription}/sync', [SubscriptionController::class, 'sync']);
        Route::post('switch-plan', [SubscriptionController::class, 'switchPlan']);
    });

    Route::middleware('role:admin,researcher,superadmin')->prefix('admin')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
        Route::get('users', [App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::get('users/{user}', [App\Http\Controllers\AdminController::class, 'getUser']);
        Route::get('stats', [App\Http\Controllers\AdminController::class, 'getUserStats']);
        Route::put('users/{user}', [App\Http\Controllers\AdminController::class, 'editUser']);
        Route::delete('users/{user}', [App\Http\Controllers\AdminController::class, 'deleteUser']);
        Route::get('subscriptions', [App\Http\Controllers\AdminController::class, 'getSubscriptions']);
        
        // Admin subscription management routes
        Route::prefix('subscriptions')->group(function () {
            Route::get('{subscription}', [AdminSubscriptionController::class, 'show']);
            Route::get('{subscription}/invoices', [AdminSubscriptionController::class, 'invoices']);
            Route::post('{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->middleware('role:admin,superadmin');
            Route::post('{subscription}/reactivate', [AdminSubscriptionController::class, 'reactivate'])->middleware('role:admin,superadmin');
            Route::post('{subscription}/sync', [AdminSubscriptionController::class, 'sync'])->middleware('role:admin,superadmin');
        });
        
        // Admin plan management routes (admin and superadmin only)
        Route::middleware('role:admin,superadmin')->prefix('plans')->group(function () {
            Route::post('/', [PlanController::class, 'store']);
            Route::put('{plan}', [PlanController::class, 'update']);
            Route::delete('{plan}', [PlanController::class, 'destroy']);
            Route::post('{plan}/activate', [PlanController::class, 'activate']);
            Route::post('{plan}/sync', [PlanController::class, 'sync']);
        });
    });
});