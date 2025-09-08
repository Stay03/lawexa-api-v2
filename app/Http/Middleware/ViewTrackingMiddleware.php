<?php

namespace App\Http\Middleware;

use App\Services\ViewTrackingService;
use App\Traits\HasViewTracking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewTrackingMiddleware
{
    public function __construct(
        private ViewTrackingService $viewTrackingService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check guest view limits BEFORE serving content for trackable routes
        if ($request->isMethod('GET') && $this->isTrackableRouteForRequest($request)) {
            $limitResponse = $this->checkGuestViewLimitBeforeServing($request);
            if ($limitResponse) {
                return $limitResponse;
            }
        }

        $response = $next($request);

        // Only track on successful GET requests for show/detail endpoints
        if ($response->getStatusCode() === 200 && $request->isMethod('GET')) {
            $this->trackViewIfApplicable($request);
        }

        return $response;
    }

    private function trackViewIfApplicable(Request $request): void
    {
        // Get the route and its parameters
        $route = $request->route();
        if (!$route) {
            return;
        }

        $routeName = $route->getName();
        $parameters = $route->parameters();

        // Check if this is a show/detail route for trackable models
        if ($this->isTrackableRoute($routeName, $parameters)) {
            $model = $this->getTrackableModelFromRoute($parameters);
            if ($model && $this->usesViewTracking($model)) {
                $this->viewTrackingService->trackView($model, $request);
            }
        }
    }

    private function isTrackableRoute(?string $routeName, array $parameters): bool
    {
        // Check if route is a show/detail route and has trackable models
        $trackablePatterns = [
            'cases.show',
            'notes.show', 
            'statutes.show',
            'statutes.showDivision',
            'statutes.showProvision',
            'comments.show'
        ];

        // Route name check is primary - Laravel route model binding ensures models exist
        if ($routeName && in_array($routeName, $trackablePatterns)) {
            return true;
        }

        // For routes without names, check if we have a trackable model in parameters
        return $this->hasTrackableModelInParameters($parameters);
    }

    private function hasTrackableModelInParameters(array $parameters): bool
    {
        foreach ($parameters as $param) {
            if (is_object($param) && $param instanceof \Illuminate\Database\Eloquent\Model) {
                if ($this->usesViewTracking($param)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getTrackableModelFromRoute(array $parameters): ?\Illuminate\Database\Eloquent\Model
    {
        // Use Laravel's route model binding - models are already resolved
        // For nested routes, prioritize the most specific model (division/provision/schedule over statute)
        $specificModels = ['division', 'provision', 'schedule'];
        
        // First check for specific nested models
        foreach ($specificModels as $modelKey) {
            if (isset($parameters[$modelKey]) && 
                is_object($parameters[$modelKey]) && 
                $parameters[$modelKey] instanceof \Illuminate\Database\Eloquent\Model) {
                return $parameters[$modelKey];
            }
        }
        
        // Then check for other models
        foreach ($parameters as $param) {
            if (is_object($param) && $param instanceof \Illuminate\Database\Eloquent\Model) {
                return $param;
            }
        }
        
        return null;
    }

    private function usesViewTracking($model): bool
    {
        return in_array(HasViewTracking::class, class_uses_recursive(get_class($model)));
    }

    /**
     * Check if the current request is for a trackable route (before model binding).
     */
    private function isTrackableRouteForRequest(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
        }

        $routeName = $route->getName();
        $parameters = $route->parameters();

        return $this->isTrackableRoute($routeName, $parameters);
    }

    /**
     * Check if guest user has reached view limit before serving content.
     */
    private function checkGuestViewLimitBeforeServing(Request $request): ?\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        
        // Only check limits for guest users
        if (!$user || !$user->isGuest()) {
            return null;
        }

        // Check if guest has reached their view limit
        if ($this->viewTrackingService->hasGuestReachedViewLimit($user->id)) {
            $remainingViews = $this->viewTrackingService->getRemainingViewsForGuest($user->id);
            $viewLimit = config('view_tracking.guest_limits.total_views', 10);
            
            return \App\Http\Responses\ApiResponse::error(
                'View limit reached. Please upgrade your account for unlimited access.',
                null,
                429,
                [
                    'view_limit' => $viewLimit,
                    'views_used' => $viewLimit - $remainingViews,
                    'remaining_views' => $remainingViews,
                    'limit_type' => 'guest_view_limit'
                ]
            );
        }

        return null;
    }
}
