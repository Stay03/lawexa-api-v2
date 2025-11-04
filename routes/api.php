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
use App\Http\Controllers\FolderController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\ContentRequestController;
use App\Http\Controllers\AdminContentRequestController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminCourtController;
use App\Http\Controllers\AdminCountryController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminFeedbackController;
use App\Http\Middleware\ViewTrackingMiddleware;

// Configure route model bindings - admin routes use ID, user routes use slug
Route::bind('case', function ($value, $route) {
    // Check if this is an admin route by examining the full URI pattern
    $uri = $route->uri();
    
    if (str_contains($uri, 'admin/cases')) {
        // Admin routes: bind by ID
        return \App\Models\CourtCase::findOrFail($value);
    }
    // User routes: bind by slug (default behavior via getRouteKeyName)
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

Route::bind('folder', function ($value, $route) {
    $uri = $route->uri();

    if (str_contains($uri, 'admin/folders')) {
        // Admin routes: bind by ID
        return \App\Models\Folder::findOrFail($value);
    }
    // User routes: bind by slug (default behavior via getRouteKeyName)
    return \App\Models\Folder::where('slug', $value)->firstOrFail();
});

Route::bind('course', function ($value, $route) {
    $uri = $route->uri();

    if (str_contains($uri, 'admin/courses')) {
        // Admin routes: bind by ID
        return \App\Models\Course::findOrFail($value);
    }
    // User routes: bind by slug (default behavior via getRouteKeyName)
    return \App\Models\Course::where('slug', $value)->firstOrFail();
});

Route::bind('court', function ($value, $route) {
    $uri = $route->uri();

    if (str_contains($uri, 'admin/courts')) {
        // Admin routes: bind by ID
        return \App\Models\Court::findOrFail($value);
    }
    // User routes: bind by slug (default behavior via getRouteKeyName)
    return \App\Models\Court::where('slug', $value)->firstOrFail();
});

Route::bind('country', function ($value, $route) {
    $uri = $route->uri();

    if (str_contains($uri, 'admin/countries')) {
        // Admin routes: bind by ID
        return \App\Models\Country::findOrFail($value);
    }
    // User routes: bind by slug (default behavior via getRouteKeyName)
    return \App\Models\Country::where('slug', $value)->firstOrFail();
});


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('guest-session', [AuthController::class, 'createGuestSession']);
    
    // Email verification routes
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware(['signed', 'throttle:6,1']);
    Route::post('email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');
    
    // Password reset routes
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
    Route::get('validate-reset-token', [AuthController::class, 'validateResetToken'])
        ->middleware('throttle:10,1');
    
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

// Trending content endpoints (no auth required)
Route::prefix('trending')->group(function () {
    Route::get('/', [TrendingController::class, 'index']);
    Route::get('stats', [TrendingController::class, 'stats']);
    Route::get('cases', [TrendingController::class, 'cases']);
    Route::get('statutes', [TrendingController::class, 'statutes']);
    Route::get('divisions', [TrendingController::class, 'divisions']);
    Route::get('provisions', [TrendingController::class, 'provisions']);
    Route::get('notes', [TrendingController::class, 'notes']);
    Route::get('folders', [TrendingController::class, 'folders']);
    Route::get('comments', [TrendingController::class, 'comments']);
});

Route::middleware(['auth:sanctum', 'track.guest.activity'])->group(function () {
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

    // Onboarding routes
    Route::prefix('onboarding')->group(function () {
        Route::get('profile', [OnboardingController::class, 'getProfile']);
        Route::put('profile', [OnboardingController::class, 'updateProfile']);
    });

    // Reference data routes
    Route::prefix('reference')->group(function () {
        Route::get('countries', [ReferenceDataController::class, 'getCountries']);
        Route::get('universities', [ReferenceDataController::class, 'getUniversities']);
        Route::get('levels', [ReferenceDataController::class, 'getAcademicLevels']);
        Route::get('legal-areas', [ReferenceDataController::class, 'getLegalAreas']);
        Route::get('professions', [ReferenceDataController::class, 'getCommonProfessions']);
        Route::get('areas-of-expertise', [ReferenceDataController::class, 'getAreasOfExpertise']);
        Route::get('case-topics', [ReferenceDataController::class, 'getCaseTopics']);
        Route::get('case-tags', [ReferenceDataController::class, 'getCaseTags']);
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
    });

    // User course routes (slug-based)
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
    });

    // User court routes (slug-based)
    Route::prefix('courts')->group(function () {
        Route::get('/', [CourtController::class, 'index']);
    });

    // User country routes (slug-based)
    Route::prefix('countries')->group(function () {
        Route::get('/', [CountryController::class, 'index']);
    });

    // Global search routes
    Route::get('divisions', [GlobalSearchController::class, 'divisions']);
    Route::get('provisions', [GlobalSearchController::class, 'provisions']);

    // User note routes
    Route::prefix('notes')->group(function () {
        Route::get('my-notes', [NoteController::class, 'myNotes']);
        Route::get('/', [NoteController::class, 'index']);
        Route::post('/', [NoteController::class, 'store'])->middleware('verified');
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

        // Lazy Loading Endpoints (Hash-First Navigation)
        // IMPORTANT: Specific routes must come BEFORE the catch-all {contentSlug} route
        Route::get('{statute}/content/sequential-pure', [App\Http\Controllers\StatuteContentController::class, 'sequentialPure']);
        Route::get('{statute}/content/sequential', [App\Http\Controllers\StatuteContentController::class, 'sequential']);
        Route::get('{statute}/content/range', [App\Http\Controllers\StatuteContentController::class, 'range']);
        Route::get('{statute}/content/{contentSlug}', [App\Http\Controllers\StatuteContentController::class, 'lookup']);

        // Statute Divisions - Hierarchical Navigation
        Route::get('{statute}/divisions', [StatuteController::class, 'divisions']);
        Route::get('{statute}/divisions/{division:slug}/children', [StatuteController::class, 'divisionChildren']);
        Route::get('{statute}/divisions/{division:slug}/provisions', [StatuteController::class, 'divisionProvisions']);

        // Statute Provisions - Hierarchical Navigation
        Route::get('{statute}/provisions', [StatuteController::class, 'provisions']);
        Route::get('{statute}/provisions/{provision:slug}/children', [StatuteController::class, 'provisionChildren']);

        // Statute Schedules
        Route::get('{statute}/schedules', [StatuteController::class, 'schedules']);
    });

    // User comment routes
    Route::prefix('comments')->middleware('verified')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::post('/', [CommentController::class, 'store']);
        Route::get('{comment}', [CommentController::class, 'show'])->where('comment', '[0-9]+')->middleware('track.views');
        Route::put('{comment}', [CommentController::class, 'update'])->where('comment', '[0-9]+');
        Route::delete('{comment}', [CommentController::class, 'destroy'])->where('comment', '[0-9]+');
        Route::post('{comment}/reply', [CommentController::class, 'reply'])->where('comment', '[0-9]+');
    });

    // User view statistics routes
    Route::prefix('views/stats')->group(function () {
        Route::get('my-activity', [App\Http\Controllers\ViewStatsController::class, 'myActivity']);
        Route::get('popular', [App\Http\Controllers\ViewStatsController::class, 'popular']);
    });


    // User folder routes (slug-based)
    Route::prefix('folders')->middleware('verified')->group(function () {
        Route::get('/', [FolderController::class, 'index']);
        Route::get('mine', [FolderController::class, 'mine']);
        Route::post('/', [FolderController::class, 'store']);
        Route::get('{folder}', [FolderController::class, 'show'])->middleware(ViewTrackingMiddleware::class);
        Route::put('{folder}', [FolderController::class, 'update']);
        Route::delete('{folder}', [FolderController::class, 'destroy']);
        
        // Folder children routes
        Route::get('{folder}/children', [FolderController::class, 'getChildren']);
        
        // Folder item management routes
        Route::post('{folder}/items', [FolderController::class, 'addItem']);
        Route::delete('{folder}/items', [FolderController::class, 'removeItem']);
    });

    // Bookmark routes
    Route::prefix('bookmarks')->middleware('verified')->group(function () {
        Route::get('/', [BookmarkController::class, 'index']);
        Route::post('/', [BookmarkController::class, 'store']);
        Route::delete('{bookmark}', [BookmarkController::class, 'destroy']);
        Route::get('check', [BookmarkController::class, 'check']);
        Route::get('stats', [BookmarkController::class, 'stats']);
    });

    // Content Request routes (user)
    Route::prefix('content-requests')->middleware('verified')->group(function () {
        Route::get('/', [ContentRequestController::class, 'index']);
        Route::post('/', [ContentRequestController::class, 'store']);
        Route::get('{contentRequest}', [ContentRequestController::class, 'show']);
        Route::delete('{contentRequest}', [ContentRequestController::class, 'destroy']);
    });

    // Feedback routes (user)
    Route::prefix('feedback')->middleware('verified')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);
        Route::post('/', [FeedbackController::class, 'store']);
        Route::get('{feedback}', [FeedbackController::class, 'show']);
    });

    // Search History routes (user - authenticated and guest)
    Route::prefix('search-history')->middleware('optional.auth')->group(function () {
        Route::get('/', [App\Http\Controllers\SearchHistoryController::class, 'index']);
        Route::get('/views', [App\Http\Controllers\SearchHistoryController::class, 'views']);
        Route::get('/stats', [App\Http\Controllers\SearchHistoryController::class, 'stats']);
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
            Route::get('{statute}/divisions', [AdminStatuteDivisionController::class, 'index']);
            Route::post('{statute}/divisions', [AdminStatuteDivisionController::class, 'store']);
            Route::get('{statute}/divisions/{division}', [AdminStatuteDivisionController::class, 'show']);
            Route::get('{statute}/divisions/{division}/children', [AdminStatuteDivisionController::class, 'children']);
            Route::get('{statute}/divisions/{division}/provisions', [AdminStatuteDivisionController::class, 'provisions']);
            Route::put('{statute}/divisions/{division}', [AdminStatuteDivisionController::class, 'update']);
            Route::delete('{statute}/divisions/{division}', [AdminStatuteDivisionController::class, 'destroy']);
            
            // Provisions CRUD
            Route::get('{statute}/provisions', [AdminStatuteProvisionController::class, 'index']);
            Route::post('{statute}/provisions', [AdminStatuteProvisionController::class, 'store']);
            Route::get('{statute}/provisions/{provision}', [AdminStatuteProvisionController::class, 'show']);
            Route::get('{statute}/provisions/{provision}/children', [AdminStatuteProvisionController::class, 'children']);
            Route::put('{statute}/provisions/{provision}', [AdminStatuteProvisionController::class, 'update']);
            Route::delete('{statute}/provisions/{provision}', [AdminStatuteProvisionController::class, 'destroy']);
            
            // Schedules CRUD
            Route::get('{statute}/schedules', [AdminStatuteScheduleController::class, 'index']);
            Route::post('{statute}/schedules', [AdminStatuteScheduleController::class, 'store']);
            Route::get('{statute}/schedules/{schedule}', [AdminStatuteScheduleController::class, 'show']);
            Route::put('{statute}/schedules/{schedule}', [AdminStatuteScheduleController::class, 'update']);
            Route::delete('{statute}/schedules/{schedule}', [AdminStatuteScheduleController::class, 'destroy']);
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

        // Admin content request management routes
        Route::prefix('content-requests')->group(function () {
            Route::get('stats', [AdminContentRequestController::class, 'stats']);
            Route::get('duplicates', [AdminContentRequestController::class, 'duplicates']);
            Route::get('/', [AdminContentRequestController::class, 'index']);
            Route::get('{contentRequest}', [AdminContentRequestController::class, 'show']);
            Route::put('{contentRequest}', [AdminContentRequestController::class, 'update']);
            Route::delete('{contentRequest}', [AdminContentRequestController::class, 'destroy']);
        });

        // Admin feedback management routes
        Route::prefix('feedback')->group(function () {
            Route::get('/', [AdminFeedbackController::class, 'index']);
            Route::get('{feedback}', [AdminFeedbackController::class, 'show']);
            Route::patch('{feedback}/status', [AdminFeedbackController::class, 'updateStatus']);
            Route::post('{feedback}/move-to-issues', [AdminFeedbackController::class, 'moveToIssues']);
        });

        // Admin course management routes (ID-based)
        Route::prefix('courses')->group(function () {
            Route::get('/', [AdminCourseController::class, 'index']);
            Route::post('/', [AdminCourseController::class, 'store']);
            Route::get('{id}', [AdminCourseController::class, 'show'])->where('id', '[0-9]+');
            Route::put('{id}', [AdminCourseController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('{id}', [AdminCourseController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // Admin court management routes (ID-based)
        Route::prefix('courts')->group(function () {
            Route::get('/', [AdminCourtController::class, 'index']);
            Route::post('/', [AdminCourtController::class, 'store']);
            Route::get('{id}', [AdminCourtController::class, 'show'])->where('id', '[0-9]+');
            Route::put('{id}', [AdminCourtController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('{id}', [AdminCourtController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // Admin country management routes (ID-based)
        Route::prefix('countries')->group(function () {
            Route::get('/', [AdminCountryController::class, 'index']);
            Route::post('/', [AdminCountryController::class, 'store']);
            Route::get('{id}', [AdminCountryController::class, 'show'])->where('id', '[0-9]+');
            Route::put('{id}', [AdminCountryController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('{id}', [AdminCountryController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // Admin views routes
        Route::get('views', [App\Http\Controllers\ViewStatsController::class, 'index']);
        
        // Admin view statistics routes
        Route::prefix('views/stats')->group(function () {
            Route::get('dashboard', [App\Http\Controllers\ViewStatsController::class, 'dashboard']);
            Route::get('overview', [App\Http\Controllers\ViewStatsController::class, 'overview']);
            Route::get('models', [App\Http\Controllers\ViewStatsController::class, 'models']);
            Route::get('users', [App\Http\Controllers\ViewStatsController::class, 'users']);
            Route::get('geography', [App\Http\Controllers\ViewStatsController::class, 'geography']);
            Route::get('devices', [App\Http\Controllers\ViewStatsController::class, 'devices']);
            Route::get('trends', [App\Http\Controllers\ViewStatsController::class, 'trends']);
        });
    });
});

// Bot-friendly routes with optional authentication
Route::prefix('cases')->group(function () {
    Route::get('{case}', [CaseController::class, 'show'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
});

Route::prefix('notes')->group(function () {
    Route::get('{note}', [NoteController::class, 'show'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
});

Route::prefix('statutes')->group(function () {
    Route::get('{statute}', [StatuteController::class, 'show'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
    Route::get('{statute}/divisions/{division:slug}', [StatuteController::class, 'showDivision'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
    Route::get('{statute}/provisions/{provision:slug}', [StatuteController::class, 'showProvision'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
    Route::get('{statute}/schedules/{schedule:slug}', [StatuteController::class, 'showSchedule'])->middleware(['bot.detection', 'optional.auth', 'track.views']);
});

Route::prefix('courses')->group(function () {
    Route::get('{course}', [CourseController::class, 'show']);
});

Route::prefix('courts')->group(function () {
    Route::get('{court}', [CourtController::class, 'show']);
});

Route::prefix('countries')->group(function () {
    Route::get('{country}', [CountryController::class, 'show']);
});