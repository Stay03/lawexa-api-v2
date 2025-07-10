<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\AdminDashboardResource;
use App\Http\Responses\ApiResponse;
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
                    'permissions' => ['view_users', 'manage_basic_settings']
                ];
                break;
                
            case 'researcher':
                $data = [
                    'total_users' => User::count(),
                    'user_breakdown' => User::selectRaw('role, count(*) as count')
                        ->groupBy('role')->get(),
                    'message' => 'Researcher dashboard - extended analytics data',
                    'permissions' => ['view_all_users', 'export_data', 'view_analytics']
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
                        'active_sessions' => User::whereNotNull('email_verified_at')->count()
                    ],
                    'message' => 'Superadmin dashboard - full system access',
                    'permissions' => ['full_access', 'user_management', 'system_config']
                ];
                break;
        }

        $dashboardData = new AdminDashboardResource([
            'role' => $user->role,
            'data' => $data
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
                $query->where('role', 'user');
                break;
                
            case 'researcher':
                $query->whereIn('role', ['user', 'admin']);
                break;
                
            case 'superadmin':
                // No restrictions for superadmin
                break;
        }

        // Apply search filter
        if (!empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Apply role filter (if user has permission to see that role)
        if (!empty($validated['role'])) {
            $requestedRole = $validated['role'];
            
            // Check if user has permission to filter by this role
            $allowedRoles = match ($user->role) {
                'admin' => ['user'],
                'researcher' => ['user', 'admin'],
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
        if (!empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }

        if (!empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $validated['per_page'] ?? 10;
        $users = $query->paginate($perPage);

        // Build response message with applied filters
        $appliedFilters = [];
        if (!empty($validated['search'])) {
            $appliedFilters[] = "search: '{$validated['search']}'";
        }
        if (!empty($validated['role'])) {
            $appliedFilters[] = "role: {$validated['role']}";
        }
        if (isset($validated['verified'])) {
            $appliedFilters[] = "verified: " . ($validated['verified'] ? 'true' : 'false');
        }
        if (isset($validated['oauth'])) {
            $appliedFilters[] = "oauth: " . ($validated['oauth'] ? 'true' : 'false');
        }
        if (!empty($validated['created_from']) || !empty($validated['created_to'])) {
            $dateRange = [];
            if (!empty($validated['created_from'])) {
                $dateRange[] = "from: {$validated['created_from']}";
            }
            if (!empty($validated['created_to'])) {
                $dateRange[] = "to: {$validated['created_to']}";
            }
            $appliedFilters[] = "created " . implode(', ', $dateRange);
        }

        $filterMessage = empty($appliedFilters) 
            ? "Users filtered by {$user->role} permissions" 
            : "Users filtered by {$user->role} permissions with " . implode(', ', $appliedFilters);

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
                'message' => 'Admin access - limited statistics'
            ];
        }

        return ApiResponse::success([
            'role' => $user->role,
            'stats' => $stats
        ], 'User statistics retrieved successfully');
    }

    public function editUser(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return ApiResponse::error('Unauthorized. Only admins and superadmins can edit users.', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $targetUser->id,
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
            
            if (!in_array($requestedRole, $allowedRoles)) {
                return ApiResponse::error('Unauthorized. You cannot assign this role.', 403);
            }
        }

        if ($user->isAdmin()) {
            if ($targetUser->hasAnyRole(['admin', 'researcher', 'superadmin'])) {
                return ApiResponse::error('Unauthorized. Admins can only edit regular users.', 403);
            }
        }

        $targetUser->update($validated);

        return ApiResponse::resource(
            new UserResource($targetUser),
            'User updated successfully'
        );
    }
}
