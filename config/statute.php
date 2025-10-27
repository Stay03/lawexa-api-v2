<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Index Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the statute order_index system used for lazy loading
    | and sequential content navigation.
    |
    */

    'order_index' => [
        /**
         * Gap size between order indices
         *
         * This creates space for future insertions without requiring
         * a full reindex of the statute content.
         */
        'gap_size' => env('STATUTE_ORDER_GAP_SIZE', 100),

        /**
         * Minimum gap threshold
         *
         * When the gap between consecutive items falls below this threshold,
         * a reindexing operation is triggered.
         */
        'min_gap_threshold' => env('STATUTE_MIN_GAP_THRESHOLD', 2),

        /**
         * Reindex strategy
         *
         * Options: 'auto', 'manual', 'scheduled'
         * - auto: Automatically trigger reindex when gap threshold is reached
         * - manual: Only reindex via admin command
         * - scheduled: Reindex via scheduled jobs
         */
        'reindex_strategy' => env('STATUTE_REINDEX_STRATEGY', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching breadcrumbs, position metadata, and other
    | frequently accessed data.
    |
    */

    'cache' => [
        /**
         * Breadcrumb cache TTL (in seconds)
         */
        'breadcrumb_ttl' => env('STATUTE_BREADCRUMB_TTL', 3600), // 1 hour

        /**
         * Position metadata cache TTL (in seconds)
         */
        'position_ttl' => env('STATUTE_POSITION_TTL', 1800), // 30 minutes

        /**
         * Total items cache TTL (in seconds)
         */
        'total_items_ttl' => env('STATUTE_TOTAL_ITEMS_TTL', 3600), // 1 hour

        /**
         * Cache tags enabled
         *
         * Requires a cache driver that supports tags (Redis, Memcached)
         */
        'tags_enabled' => env('STATUTE_CACHE_TAGS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for lazy loading endpoints and pagination.
    |
    */

    'lazy_loading' => [
        /**
         * Default number of items to load per sequential request
         */
        'default_limit' => env('STATUTE_DEFAULT_LIMIT', 5),

        /**
         * Maximum number of items per sequential request
         */
        'max_limit' => env('STATUTE_MAX_LIMIT', 50),

        /**
         * Maximum range size for range-based loading
         */
        'max_range_size' => env('STATUTE_MAX_RANGE_SIZE', 100),

        /**
         * Enable lazy loading feature
         *
         * Set to false to disable lazy loading endpoints
         */
        'enabled' => env('STATUTE_LAZY_LOADING_ENABLED', true),
    ],
];
