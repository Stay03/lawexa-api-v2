<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the view tracking
    | system. You can customize cooldown periods and other tracking
    | behaviors here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cooldown Settings
    |--------------------------------------------------------------------------
    |
    | These values determine how long users must wait before their view
    | of the same content is tracked again. This prevents spam and
    | inflated view counts.
    |
    */

    'cooldown' => [
        // Cooldown period for authenticated users
        'authenticated' => env('VIEW_COOLDOWN_AUTHENTICATED', 1),
        
        // Cooldown period for guest users
        'guest' => env('VIEW_COOLDOWN_GUEST', 2),
        
        // Time unit for cooldown periods (seconds, minutes, hours, days)
        'unit' => env('VIEW_COOLDOWN_UNIT', 'hours'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guest Limits
    |--------------------------------------------------------------------------
    |
    | Total view limits for guest users across all models.
    |
    */

    'guest_limits' => [
        // Maximum total views a guest can have across all models
        'total_views' => env('GUEST_TOTAL_VIEW_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep view records before they can be cleaned up.
    |
    */

    'retention' => [
        'days' => env('VIEW_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the view tracking background jobs.
    |
    */

    'jobs' => [
        'delay_seconds' => env('VIEW_JOB_DELAY', 1),
        'timeout_seconds' => env('VIEW_JOB_TIMEOUT', 30),
        'max_tries' => env('VIEW_JOB_TRIES', 3),
    ],
];