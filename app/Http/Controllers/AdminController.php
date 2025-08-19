<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminDashboardResource;
use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\SubscriptionMetricsService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = [];

        switch ($user->role) {
            case 'admin':
                $data = [
                    'total_users' => User::where('role', 'user')->count(),
                    'message' => 'Admin dashboard - basic user management data',
                    'permissions' => ['view_users', 'manage_basic_settings'],
                ];
                break;

            case 'researcher':
                $data = [
                    'total_users' => User::count(),
                    'user_breakdown' => User::selectRaw('role, count(*) as count')
                        ->groupBy('role')->get(),
                    'message' => 'Researcher dashboard - extended analytics data',
                    'permissions' => ['view_all_users', 'export_data', 'view_analytics'],
                ];
                break;

            case 'superadmin':
                $data = [
                    'total_users' => User::count(),
                    'user_breakdown' => User::selectRaw('role, count(*) as count')
                        ->groupBy('role')->get(),
                    'recent_users' => User::latest()->take(10)->get(),
                    'system_stats' => [
                        'total_tokens' => $user->tokens()->count(),
                        'active_sessions' => User::whereNotNull('email_verified_at')->count(),
                    ],
                    'message' => 'Superadmin dashboard - full system access',
                    'permissions' => ['full_access', 'user_management', 'system_config'],
                ];
                break;
        }

        $dashboardData = new AdminDashboardResource([
            'role' => $user->role,
            'data' => $data,
        ]);

        return ApiResponse::resource(
            $dashboardData,
            'Dashboard data retrieved successfully'
        );
    }

    public function getUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate query parameters
        $validated = $request->validate([
            'search' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|in:user,admin,researcher,superadmin',
            'verified' => 'sometimes|boolean',
            'oauth' => 'sometimes|boolean',
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date|after_or_equal:created_from',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|string|in:name,email,role,created_at,updated_at,email_verified_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
        ]);

        $query = User::query();

        // Apply role-based access control
        switch ($user->role) {
            case 'admin':
            case 'researcher':
            case 'superadmin':
                // No restrictions - all roles can view all users
                break;
        }

        // Apply search filter
        if (! empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Apply role filter (if user has permission to see that role)
        if (! empty($validated['role'])) {
            $requestedRole = $validated['role'];

            // Check if user has permission to filter by this role
            $allowedRoles = match ($user->role) {
                'admin' => ['user', 'researcher', 'admin', 'superadmin'],
                'researcher' => ['user', 'admin', 'researcher', 'superadmin'],
                'superadmin' => ['user', 'admin', 'researcher', 'superadmin'],
                default => []
            };

            if (in_array($requestedRole, $allowedRoles)) {
                $query->where('role', $requestedRole);
            }
        }

        // Apply verification status filter
        if (isset($validated['verified'])) {
            if ($validated['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Apply OAuth filter
        if (isset($validated['oauth'])) {
            if ($validated['oauth']) {
                $query->whereNotNull('google_id');
            } else {
                $query->whereNull('google_id');
            }
        }

        // Apply date range filters
        if (! empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }

        if (! empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $validated['per_page'] ?? 10;
        $users = $query->with(['activeSubscription.plan', 'subscriptions'])->paginate($perPage);

        // Build response message with applied filters
        $appliedFilters = [];
        if (! empty($validated['search'])) {
            $appliedFilters[] = "search: '{$validated['search']}'";
        }
        if (! empty($validated['role'])) {
            $appliedFilters[] = "role: {$validated['role']}";
        }
        if (isset($validated['verified'])) {
            $appliedFilters[] = 'verified: '.($validated['verified'] ? 'true' : 'false');
        }
        if (isset($validated['oauth'])) {
            $appliedFilters[] = 'oauth: '.($validated['oauth'] ? 'true' : 'false');
        }
        if (! empty($validated['created_from']) || ! empty($validated['created_to'])) {
            $dateRange = [];
            if (! empty($validated['created_from'])) {
                $dateRange[] = "from: {$validated['created_from']}";
            }
            if (! empty($validated['created_to'])) {
                $dateRange[] = "to: {$validated['created_to']}";
            }
            $appliedFilters[] = 'created '.implode(', ', $dateRange);
        }

        $filterMessage = empty($appliedFilters)
            ? "Users filtered by {$user->role} permissions"
            : "Users filtered by {$user->role} permissions with ".implode(', ', $appliedFilters);

        $userCollection = new UserCollection($users);

        return ApiResponse::success(
            $userCollection->toArray($request),
            $filterMessage
        );
    }

    public function getUserStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = [];

        if ($user->isResearcher() || $user->isSuperAdmin()) {
            $stats = [
                'total_users' => User::count(),
                'users_by_role' => User::selectRaw('role, count(*) as count')
                    ->groupBy('role')->get(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            if ($user->isSuperAdmin()) {
                $stats['oauth_users'] = User::whereNotNull('google_id')->count();
                $stats['verified_users'] = User::whereNotNull('email_verified_at')->count();
            }
        } else {
            $stats = [
                'basic_user_count' => User::where('role', 'user')->count(),
                'message' => 'Admin access - limited statistics',
            ];
        }

        return ApiResponse::success([
            'role' => $user->role,
            'stats' => $stats,
        ], 'User statistics retrieved successfully');
    }

    public function editUser(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can edit users.', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$targetUser->id,
            'role' => 'sometimes|string|in:user,admin,researcher,superadmin',
            'avatar' => 'sometimes|nullable|string|max:255',
        ]);

        if (isset($validated['role'])) {
            $requestedRole = $validated['role'];

            $allowedRoles = match ($user->role) {
                'admin' => ['user', 'researcher'],
                'superadmin' => ['user', 'admin', 'researcher', 'superadmin'],
                default => []
            };

            if (! in_array($requestedRole, $allowedRoles)) {
                return ApiResponse::error('Unauthorized. You cannot assign this role.', 403);
            }
        }

        if ($user->isAdmin()) {
            if ($targetUser->hasAnyRole(['admin', 'superadmin'])) {
                return ApiResponse::error('Unauthorized. Admins can only edit regular users and researchers.', 403);
            }
        }

        $targetUser->update($validated);

        return ApiResponse::resource(
            new UserResource($targetUser->fresh()->load(['activeSubscription.plan', 'subscriptions'])),
            'User updated successfully'
        );
    }

    public function getUser(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if (! $currentUser->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins, researchers, and superadmins can view users.', 403);
        }

        // Load subscription relationships
        $user->load(['activeSubscription.plan', 'subscriptions']);

        // All roles can view all users - no restrictions needed

        return ApiResponse::resource(
            new UserResource($user),
            'User details retrieved successfully'
        );
    }

    public function deleteUser(Request $request, User $targetUser): JsonResponse
    {
        $currentUser = $request->user();

        if (! $currentUser->isSuperAdmin()) {
            return ApiResponse::error('Unauthorized. Only superadmins can delete users.', 403);
        }

        if ($currentUser->id === $targetUser->id) {
            return ApiResponse::error('Cannot delete your own account.', 400);
        }

        // Log user details for debugging
        \Log::info("Attempting to delete user with ID: {$targetUser->id}, Name: {$targetUser->name}, Email: {$targetUser->email}");
        
        // Verify user exists in database
        $userExists = \DB::table('users')->where('id', $targetUser->id)->exists();
        \Log::info("User exists in database: " . ($userExists ? 'yes' : 'no') . " for ID: {$targetUser->id}");
        
        if (!$userExists) {
            return ApiResponse::error('User not found in database', 404);
        }

        // Capture user data immediately before any operations
        $deletedUserData = [
            'id' => $targetUser->id,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => $targetUser->role,
            'avatar' => $targetUser->avatar,
            'google_id' => $targetUser->google_id,
            'email_verified_at' => $targetUser->email_verified_at?->toISOString(),
            'created_at' => $targetUser->created_at?->toISOString(),
            'updated_at' => $targetUser->updated_at?->toISOString(),
        ];

        try {
            \DB::transaction(function () use ($targetUser) {
                // Delete user tokens first
                $tokensDeleted = $targetUser->tokens()->delete();
                \Log::info("Deleted {$tokensDeleted} tokens for user {$targetUser->id}");
                
                // Try to delete the user using direct database query to get better error info
                try {
                    $deleted = \DB::table('users')->where('id', $targetUser->id)->delete();
                    \Log::info("Direct DB deletion result: {$deleted} rows affected for user {$targetUser->id}");
                    
                    if ($deleted === 0) {
                        throw new \Exception('No rows were deleted from users table');
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    \Log::error("Database deletion error: " . $e->getMessage());
                    throw new \Exception('Database constraint error: ' . $e->getMessage());
                }
            });

            // Verify deletion by checking if user still exists
            $userStillExists = User::find($deletedUserData['id']);
            if ($userStillExists) {
                \Log::error("User {$deletedUserData['id']} still exists after deletion attempt");
                return ApiResponse::error('User deletion failed - user still exists in database', 500);
            }

            \Log::info("User {$deletedUserData['id']} successfully deleted and verified");
            return ApiResponse::success(
                $deletedUserData,
                'User deleted successfully'
            );
        } catch (\Throwable $e) {
            \Log::error("User deletion failed: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
            return ApiResponse::error(
                'Failed to delete user: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getSubscriptions(Request $request, SubscriptionService $subscriptionService): JsonResponse
    {
        $user = $request->user();

        // Validate query parameters
        $validated = $request->validate([
            'search' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:active,attention,completed,cancelled,non-renewing',
            'plan_id' => 'sometimes|integer|exists:plans,id',
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date|after_or_equal:created_from',
            'next_payment_from' => 'sometimes|date',
            'next_payment_to' => 'sometimes|date|after_or_equal:next_payment_from',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort_by' => 'sometimes|string|in:created_at,updated_at,next_payment_date,amount,status',
            'sort_direction' => 'sometimes|string|in:asc,desc',
        ]);

        // Apply role-based access control
        if (! $user->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins, researchers, and superadmins can view subscriptions.', 403);
        }

        // Get subscriptions with filters
        $subscriptions = $subscriptionService->getAllSubscriptions($validated);

        // Build response message with applied filters
        $appliedFilters = [];
        if (! empty($validated['search'])) {
            $appliedFilters[] = "search: '{$validated['search']}'";
        }
        if (! empty($validated['status'])) {
            $appliedFilters[] = "status: {$validated['status']}";
        }
        if (! empty($validated['plan_id'])) {
            $appliedFilters[] = "plan_id: {$validated['plan_id']}";
        }
        if (! empty($validated['created_from']) || ! empty($validated['created_to'])) {
            $dateRange = [];
            if (! empty($validated['created_from'])) {
                $dateRange[] = "from: {$validated['created_from']}";
            }
            if (! empty($validated['created_to'])) {
                $dateRange[] = "to: {$validated['created_to']}";
            }
            $appliedFilters[] = 'created '.implode(', ', $dateRange);
        }
        if (! empty($validated['next_payment_from']) || ! empty($validated['next_payment_to'])) {
            $paymentDateRange = [];
            if (! empty($validated['next_payment_from'])) {
                $paymentDateRange[] = "from: {$validated['next_payment_from']}";
            }
            if (! empty($validated['next_payment_to'])) {
                $paymentDateRange[] = "to: {$validated['next_payment_to']}";
            }
            $appliedFilters[] = 'next_payment '.implode(', ', $paymentDateRange);
        }

        $filterMessage = empty($appliedFilters)
            ? "Subscriptions filtered by {$user->role} permissions"
            : "Subscriptions filtered by {$user->role} permissions with ".implode(', ', $appliedFilters);

        $subscriptionCollection = new SubscriptionCollection($subscriptions);

        return ApiResponse::success(
            $subscriptionCollection->toArray($request),
            $filterMessage
        );
    }

    public function dashboardMetrics(Request $request, SubscriptionMetricsService $metricsService): JsonResponse
    {
        $user = $request->user();

        // Apply role-based access control
        if (! $user->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins, researchers, and superadmins can view metrics.', 403);
        }

        // Validate query parameters
        $validated = $request->validate([
            'period' => 'sometimes|string|in:daily,weekly,monthly,quarterly,biannually,annually',
        ]);

        $period = $validated['period'] ?? 'monthly';
        $metrics = $metricsService->getDashboardMetrics($period);

        $response = [
            'success' => true,
            'data' => [
                'financial_overview' => $metrics['financial_overview'],
                'subscription_counts' => $metrics['subscription_counts'],
                'payment_health' => $metrics['payment_health'],
                'business_metrics' => $metrics['business_metrics'],
                'plan_performance' => $metrics['plan_performance'],
            ],
            'meta' => [
                'last_updated' => now()->toISOString(),
                'period' => $metrics['meta']['period'],
            ],
        ];

        return response()->json($response, 200);
    }
}
