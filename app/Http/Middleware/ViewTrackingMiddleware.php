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
}
