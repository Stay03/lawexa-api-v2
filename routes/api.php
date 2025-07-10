<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    Route::post('google/exchange', [GoogleAuthController::class, 'exchangeCodeForToken']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('profile', function (Request $request) {
            return \App\Http\Responses\ApiResponse::success([
                'user' => new \App\Http\Resources\UserResource($request->user())
            ], 'User profile retrieved successfully');
        });
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });

    Route::middleware('role:admin,researcher,superadmin')->prefix('admin')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
        Route::get('users', [App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::get('users/{user}', [App\Http\Controllers\AdminController::class, 'getUser']);
        Route::get('stats', [App\Http\Controllers\AdminController::class, 'getUserStats']);
        Route::put('users/{user}', [App\Http\Controllers\AdminController::class, 'editUser']);
    });
});