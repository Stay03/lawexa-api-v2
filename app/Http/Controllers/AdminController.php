<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserCollection;
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
        $query = User::query();

        switch ($user->role) {
            case 'admin':
                $query->where('role', 'user');
                break;
                
            case 'researcher':
                $query->whereIn('role', ['user', 'admin']);
                break;
                
            case 'superadmin':
                break;
        }

        $users = $query->paginate(10);
        $userCollection = new UserCollection($users);

        return ApiResponse::collection(
            $userCollection,
            "Users filtered by {$user->role} permissions"
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
}
