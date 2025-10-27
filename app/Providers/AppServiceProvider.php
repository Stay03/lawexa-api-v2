<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\StatuteDivision;
use App\Models\StatuteProvision;
use App\Models\StatuteSchedule;
use App\Observers\StatuteDivisionObserver;
use App\Observers\StatuteProvisionObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers for cache invalidation
        StatuteDivision::observe(StatuteDivisionObserver::class);
        StatuteProvision::observe(StatuteProvisionObserver::class);


        // Custom route model binding for nested statute resources
        Route::bind('division', function ($value, $route) {
            if ($route->hasParameter('statute')) {
                $statute = $route->parameter('statute');
                // If statute is still a string (slug), resolve it first
                if (is_string($statute)) {
                    $statute = \App\Models\Statute::where('slug', $statute)->firstOrFail();
                }
                return $statute->divisions()->where('slug', $value)->firstOrFail();
            }
            return StatuteDivision::where('slug', $value)->firstOrFail();
        });

        Route::bind('provision', function ($value, $route) {
            if ($route->hasParameter('statute')) {
                $statute = $route->parameter('statute');
                // If statute is still a string (slug), resolve it first
                if (is_string($statute)) {
                    $statute = \App\Models\Statute::where('slug', $statute)->firstOrFail();
                }
                return $statute->provisions()->where('slug', $value)->firstOrFail();
            }
            return StatuteProvision::where('slug', $value)->firstOrFail();
        });

        Route::bind('schedule', function ($value, $route) {
            if ($route->hasParameter('statute')) {
                $statute = $route->parameter('statute');
                // If statute is still a string (slug), resolve it first
                if (is_string($statute)) {
                    $statute = \App\Models\Statute::where('slug', $statute)->firstOrFail();
                }
                return $statute->schedules()->where('slug', $value)->firstOrFail();
            }
            return StatuteSchedule::where('slug', $value)->firstOrFail();
        });
    }
}
