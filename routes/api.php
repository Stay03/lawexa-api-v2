<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\AdminCaseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DirectUploadController;
use App\Http\Controllers\S3WebhookController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AdminNoteController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\AdminIssueController;
use App\Http\Controllers\StatuteController;
use App\Http\Controllers\AdminStatuteController;
use App\Http\Controllers\AdminStatuteDivisionController;
use App\Http\Controllers\AdminStatuteProvisionController;
use App\Http\Controllers\AdminStatuteScheduleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminCommentController;

// Configure route model bindings - admin routes use ID, user routes use slug
Route::bind('case', function ($value, $route) {
    // Check if this is an admin route by examining the full URI pattern
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/cases')) {
        // Admin routes: bind by ID
        return \App\Models\CourtCase::findOrFail($value);
    }
    // User routes: bind by slug (default behavior)
    return \App\Models\CourtCase::where('slug', $value)->firstOrFail();
});

Route::bind('note', function ($value, $route) {
    // Check if this is an admin route
    if (str_contains($route->uri(), 'admin/notes')) {
        // Admin routes: bind by ID
        return \App\Models\Note::findOrFail($value);
    }
    // User routes: bind by ID but with ownership validation in controller
    return \App\Models\Note::findOrFail($value);
});

Route::bind('adminIssue', function ($value, $route) {
    // Admin issue routes: bind by ID
    return \App\Models\Issue::findOrFail($value);
});

Route::bind('statute', function ($value, $route) {
    // Check if this is an admin route by examining the full URI pattern
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/statutes')) {
        // Admin routes: bind by ID
        return \App\Models\Statute::findOrFail($value);
    }
    // User routes: bind by slug (default behavior)
    return \App\Models\Statute::where('slug', $value)->firstOrFail();
});

Route::bind('statuteDivision', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/')) {
        return \App\Models\StatuteDivision::findOrFail($value);
    }
    return \App\Models\StatuteDivision::where('slug', $value)->firstOrFail();
});

Route::bind('statuteProvision', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/')) {
        return \App\Models\StatuteProvision::findOrFail($value);
    }
    return \App\Models\StatuteProvision::where('slug', $value)->firstOrFail();
});

Route::bind('statuteSchedule', function ($value, $route) {
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/')) {
        return \App\Models\StatuteSchedule::findOrFail($value);
    }
    return \App\Models\StatuteSchedule::where('slug', $value)->firstOrFail();
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    // Email verification routes
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware(['signed', 'throttle:6,1']);
    Route::post('email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');
    
    // Alternative verification route without signed middleware (for signature issues)
    Route::get('email/verify-alt/{id}/{hash}', [AuthController::class, 'verifyEmailAlternative'])
        ->name('verification.alternative')
        ->middleware(['throttle:6,1']);
    
    // Debug route for verification (remove after fixing)
    Route::get('email/verify-debug/{id}/{hash}', [AuthController::class, 'debugVerifyEmail'])
        ->name('verification.debug');
    
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    Route::post('google/exchange', [GoogleAuthController::class, 'exchangeCodeForToken']);
});

// Webhook endpoints (no auth required)
Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle']);
Route::post('webhooks/s3', [S3WebhookController::class, 'handle']);
Route::get('webhooks/s3/health', [S3WebhookController::class, 'health']);

// Public plans endpoint (no auth required)
Route::get('plans', [PlanController::class, 'index']);
Route::get('plans/{plan}', [PlanController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // Primary upload endpoint (simple direct S3 upload)
    Route::post('upload', [DirectUploadController::class, 'simpleUpload']);
    
    // Direct upload routes (available to all authenticated users)
    Route::prefix('direct-upload')->group(function () {
        Route::get('config', [DirectUploadController::class, 'getUploadConfig']);
        Route::post('generate-url', [DirectUploadController::class, 'generateUploadUrl']);
        Route::post('simple', [DirectUploadController::class, 'simpleUpload']);
        Route::post('{fileId}/complete', [DirectUploadController::class, 'markUploadCompleted'])->where('fileId', '[0-9]+');
        Route::post('{fileId}/failed', [DirectUploadController::class, 'markUploadFailed'])->where('fileId', '[0-9]+');
        Route::get('{fileId}/status', [DirectUploadController::class, 'getUploadStatus'])->where('fileId', '[0-9]+');
        Route::delete('{fileId}/cancel', [DirectUploadController::class, 'cancelUpload'])->where('fileId', '[0-9]+');
        Route::get('pending', [DirectUploadController::class, 'getPendingUploads']);
    });

    Route::prefix('user')->group(function () {
        Route::get('profile', function (Request $request) {
            $user = $request->user()->load(['activeSubscription', 'subscriptions']);
            return \App\Http\Responses\ApiResponse::success([
                'user' => new \App\Http\Resources\UserResource($user)
            ], 'User profile retrieved successfully');
        });
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });

    // User subscription routes (require email verification)
    Route::prefix('subscriptions')->middleware('verified')->group(function () {
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

    // User case routes (slug-based)
    Route::prefix('cases')->group(function () {
        Route::get('/', [CaseController::class, 'index']);
        Route::get('{case}', [CaseController::class, 'show']);
    });

    // User note routes
    Route::prefix('notes')->group(function () {
        Route::get('/', [NoteController::class, 'index']);
        Route::post('/', [NoteController::class, 'store'])->middleware('verified');
        Route::get('{note}', [NoteController::class, 'show']);
        Route::put('{note}', [NoteController::class, 'update'])->middleware('verified');
        Route::delete('{note}', [NoteController::class, 'destroy'])->middleware('verified');
    });

    // User issue routes
    Route::prefix('issues')->group(function () {
        Route::get('/', [IssueController::class, 'index']);
        Route::post('/', [IssueController::class, 'store'])->middleware('verified');
        Route::get('{issue}', [IssueController::class, 'show'])->where('issue', '[0-9]+');
        Route::put('{issue}', [IssueController::class, 'update'])->where('issue', '[0-9]+')->middleware('verified');
        Route::delete('{issue}', [IssueController::class, 'destroy'])->where('issue', '[0-9]+')->middleware('verified');
    });

    // User statute routes (slug-based)
    Route::prefix('statutes')->group(function () {
        Route::get('/', [StatuteController::class, 'index']);
        Route::get('{statute}', [StatuteController::class, 'show']);
        
        // Statute Divisions - Hierarchical Navigation
        Route::get('{statute}/divisions', [StatuteController::class, 'divisions']);
        Route::get('{statute}/divisions/{divisionSlug}', [StatuteController::class, 'showDivision']);
        Route::get('{statute}/divisions/{divisionSlug}/children', [StatuteController::class, 'divisionChildren']);
        Route::get('{statute}/divisions/{divisionSlug}/provisions', [StatuteController::class, 'divisionProvisions']);
        
        // Statute Provisions - Hierarchical Navigation
        Route::get('{statute}/provisions', [StatuteController::class, 'provisions']);
        Route::get('{statute}/provisions/{provisionSlug}', [StatuteController::class, 'showProvision']);
        Route::get('{statute}/provisions/{provisionSlug}/children', [StatuteController::class, 'provisionChildren']);
        
        // Statute Schedules
        Route::get('{statute}/schedules', [StatuteController::class, 'schedules']);
        Route::get('{statute}/schedules/{scheduleSlug}', [StatuteController::class, 'showSchedule']);
    });

    // User comment routes
    Route::prefix('comments')->middleware('verified')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::post('/', [CommentController::class, 'store']);
        Route::get('{comment}', [CommentController::class, 'show'])->where('comment', '[0-9]+');
        Route::put('{comment}', [CommentController::class, 'update'])->where('comment', '[0-9]+');
        Route::delete('{comment}', [CommentController::class, 'destroy'])->where('comment', '[0-9]+');
        Route::post('{comment}/reply', [CommentController::class, 'reply'])->where('comment', '[0-9]+');
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
            Route::get('dashboard-metrics', [App\Http\Controllers\AdminController::class, 'dashboardMetrics']);
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
        
        // Admin case management routes (ID-based)
        Route::prefix('cases')->group(function () {
            Route::get('/', [AdminCaseController::class, 'index']);
            Route::post('/', [AdminCaseController::class, 'store']);
            Route::get('{id}', [AdminCaseController::class, 'show'])->where('id', '[0-9]+');
            Route::put('{id}', [AdminCaseController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('{id}', [AdminCaseController::class, 'destroy'])->where('id', '[0-9]+');
        });
        
        // Admin file management routes
        Route::prefix('files')->group(function () {
            Route::get('/', [FileController::class, 'index']);
            Route::post('/', [FileController::class, 'store']);
            Route::get('{id}', [FileController::class, 'show'])->where('id', '[0-9]+');
            Route::get('{id}/download', [FileController::class, 'download'])->where('id', '[0-9]+');
            Route::delete('{id}', [FileController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('delete-multiple', [FileController::class, 'destroyMultiple']);
            Route::get('stats', [FileController::class, 'stats']);
            Route::post('cleanup', [FileController::class, 'cleanup'])->middleware('role:admin,superadmin');
        });
        
        // Admin direct upload management routes
        Route::prefix('direct-upload')->group(function () {
            Route::post('cleanup-expired', [DirectUploadController::class, 'cleanupExpiredUploads'])->middleware('role:admin,superadmin');
        });
        
        // Admin note management routes
        Route::prefix('notes')->group(function () {
            Route::get('/', [AdminNoteController::class, 'index']);
            Route::post('/', [AdminNoteController::class, 'store'])->middleware('role:admin,superadmin');
            Route::get('{note}', [AdminNoteController::class, 'show'])->where('note', '[0-9]+');
            Route::put('{note}', [AdminNoteController::class, 'update'])->where('note', '[0-9]+')->middleware('role:admin,superadmin');
            Route::delete('{note}', [AdminNoteController::class, 'destroy'])->where('note', '[0-9]+')->middleware('role:admin,superadmin');
        });
        
        // Admin issue management routes
        Route::prefix('issues')->group(function () {
            Route::get('/', [AdminIssueController::class, 'index']);
            Route::get('stats', [AdminIssueController::class, 'stats']);
            Route::get('{adminIssue}', [AdminIssueController::class, 'show'])->where('adminIssue', '[0-9]+');
            Route::put('{adminIssue}', [AdminIssueController::class, 'update'])->where('adminIssue', '[0-9]+');
            Route::delete('{adminIssue}', [AdminIssueController::class, 'destroy'])->where('adminIssue', '[0-9]+')->middleware('role:admin,superadmin');
            Route::post('{adminIssue}/ai-analyze', [AdminIssueController::class, 'aiAnalyze'])->where('adminIssue', '[0-9]+');
        });
        
        // Admin statute management routes (ID-based)
        Route::prefix('statutes')->group(function () {
            Route::get('/', [AdminStatuteController::class, 'index']);
            Route::post('/', [AdminStatuteController::class, 'store']);
            Route::get('{id}', [AdminStatuteController::class, 'show'])->where('id', '[0-9]+');
            Route::put('{id}', [AdminStatuteController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('{id}', [AdminStatuteController::class, 'destroy'])->where('id', '[0-9]+');
            
            // Relationships
            Route::post('{id}/amendments', [AdminStatuteController::class, 'addAmendment'])->where('id', '[0-9]+');
            Route::delete('{id}/amendments/{amendmentId}', [AdminStatuteController::class, 'removeAmendment'])->where('id', '[0-9]+')->where('amendmentId', '[0-9]+');
            
            Route::post('{id}/citations', [AdminStatuteController::class, 'addCitation'])->where('id', '[0-9]+');
            Route::delete('{id}/citations/{citationId}', [AdminStatuteController::class, 'removeCitation'])->where('id', '[0-9]+')->where('citationId', '[0-9]+');
            
            // Bulk operations
            Route::post('bulk-update', [AdminStatuteController::class, 'bulkUpdate']);
            Route::post('bulk-delete', [AdminStatuteController::class, 'bulkDelete']);
            
            // Divisions CRUD
            Route::get('{statuteId}/divisions', [AdminStatuteDivisionController::class, 'index'])->where('statuteId', '[0-9]+');
            Route::post('{statuteId}/divisions', [AdminStatuteDivisionController::class, 'store'])->where('statuteId', '[0-9]+');
            Route::get('{statuteId}/divisions/{divisionId}', [AdminStatuteDivisionController::class, 'show'])->where(['statuteId' => '[0-9]+', 'divisionId' => '[0-9]+']);
            Route::get('{statuteId}/divisions/{divisionId}/children', [AdminStatuteDivisionController::class, 'children'])->where(['statuteId' => '[0-9]+', 'divisionId' => '[0-9]+']);
            Route::get('{statuteId}/divisions/{divisionId}/provisions', [AdminStatuteDivisionController::class, 'provisions'])->where(['statuteId' => '[0-9]+', 'divisionId' => '[0-9]+']);
            Route::put('{statuteId}/divisions/{divisionId}', [AdminStatuteDivisionController::class, 'update'])->where(['statuteId' => '[0-9]+', 'divisionId' => '[0-9]+']);
            Route::delete('{statuteId}/divisions/{divisionId}', [AdminStatuteDivisionController::class, 'destroy'])->where(['statuteId' => '[0-9]+', 'divisionId' => '[0-9]+']);
            
            // Provisions CRUD
            Route::get('{statuteId}/provisions', [AdminStatuteProvisionController::class, 'index'])->where('statuteId', '[0-9]+');
            Route::post('{statuteId}/provisions', [AdminStatuteProvisionController::class, 'store'])->where('statuteId', '[0-9]+');
            Route::get('{statuteId}/provisions/{provisionId}', [AdminStatuteProvisionController::class, 'show'])->where(['statuteId' => '[0-9]+', 'provisionId' => '[0-9]+']);
            Route::get('{statuteId}/provisions/{provisionId}/children', [AdminStatuteProvisionController::class, 'children'])->where(['statuteId' => '[0-9]+', 'provisionId' => '[0-9]+']);
            Route::put('{statuteId}/provisions/{provisionId}', [AdminStatuteProvisionController::class, 'update'])->where(['statuteId' => '[0-9]+', 'provisionId' => '[0-9]+']);
            Route::delete('{statuteId}/provisions/{provisionId}', [AdminStatuteProvisionController::class, 'destroy'])->where(['statuteId' => '[0-9]+', 'provisionId' => '[0-9]+']);
            
            // Schedules CRUD
            Route::get('{statuteId}/schedules', [AdminStatuteScheduleController::class, 'index'])->where('statuteId', '[0-9]+');
            Route::post('{statuteId}/schedules', [AdminStatuteScheduleController::class, 'store'])->where('statuteId', '[0-9]+');
            Route::get('{statuteId}/schedules/{scheduleId}', [AdminStatuteScheduleController::class, 'show'])->where(['statuteId' => '[0-9]+', 'scheduleId' => '[0-9]+']);
            Route::put('{statuteId}/schedules/{scheduleId}', [AdminStatuteScheduleController::class, 'update'])->where(['statuteId' => '[0-9]+', 'scheduleId' => '[0-9]+']);
            Route::delete('{statuteId}/schedules/{scheduleId}', [AdminStatuteScheduleController::class, 'destroy'])->where(['statuteId' => '[0-9]+', 'scheduleId' => '[0-9]+']);
        });
        
        // Admin comment management routes
        Route::prefix('comments')->group(function () {
            Route::get('/', [AdminCommentController::class, 'index']);
            Route::get('stats', [AdminCommentController::class, 'stats']);
            Route::get('{comment}', [AdminCommentController::class, 'show'])->where('comment', '[0-9]+');
            Route::post('{comment}/approve', [AdminCommentController::class, 'approve'])->where('comment', '[0-9]+');
            Route::post('{comment}/reject', [AdminCommentController::class, 'reject'])->where('comment', '[0-9]+');
            Route::delete('{comment}', [AdminCommentController::class, 'destroy'])->where('comment', '[0-9]+')->middleware('role:admin,superadmin');
        });
    });
});