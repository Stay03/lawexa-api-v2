<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bot Detection Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for bot detection middleware
    | to enable SEO-friendly access to content while maintaining security
    | for human users.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Bot Detection
    |--------------------------------------------------------------------------
    |
    | Enable or disable bot detection functionality. When enabled, bots will
    | be automatically detected and granted access without authentication.
    |
    */
    'enabled' => env('BOT_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Known Bot User Agents
    |--------------------------------------------------------------------------
    |
    | List of known bot user agent patterns. These patterns are used to
    | identify bots and search engine crawlers.
    |
    */
    'bot_patterns' => [
        // Search Engines
        'googlebot',
        'bingbot',
        'yandexbot',
        'duckduckbot',
        'baiduspider',
        'sogou',
        'exabot',
        'ia_archiver',
        'wayback',
        'archive.org_bot',
        
        // Social Media Crawlers
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'whatsapp',
        'slackbot',
        'telegrambot',
        'discordbot',
        'skype',
        
        // SEO Tools
        'semrushbot',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'blexbot',
        'screaming frog',
        
        // Generic Bot Patterns
        'bot/',
        'crawler',
        'spider',
        'scraper',
        'fetcher',
        'parser',
        'checker',
        'validator',
        'monitor',
        'reader',
        'aggregator',
        'indexer',
        'archiver',
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Bot Detection Rules
    |--------------------------------------------------------------------------
    |
    | Additional rules for bot detection beyond user agent patterns.
    |
    */
    'detection_rules' => [
        // Check for common bot headers
        'check_headers' => true,
        'bot_headers' => [
            'X-Forwarded-For-Bot',
            'X-Bot-Request',
            'X-Crawler',
        ],
        
        // Exclude certain IPs from bot detection (for testing)
        'exclude_ips' => [
            // Add IPs that should never be treated as bots
        ],
        
        // Override detection for specific IPs (force bot detection)
        'force_bot_ips' => [
            // Add IPs that should always be treated as bots
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Access Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for bot access and content filtering.
    |
    */
    'bot_access' => [
        // Create guest users for bots (for view tracking)
        'create_guest_users' => true,
        
        // Guest user expiration for bots (longer than regular guests)
        'guest_expiration_days' => 90,
        
        // Skip view tracking cooldowns for bots
        'skip_cooldown' => true,
        
        // Content filtering for bots
        'filter_sensitive_content' => true,
        
        // Fields to exclude from case responses for bots
        'case_excluded_fields' => [
            'report',
            'case_report_text',
            'body',
            'files',
        ],
        
        // Maximum content length for bot responses (optional)
        'max_content_length' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for logging bot activity and monitoring.
    |
    */
    'logging' => [
        // Log bot detections
        'log_bot_detections' => env('LOG_BOT_DETECTIONS', false),
        
        // Log channel for bot activity
        'log_channel' => env('LOG_CHANNEL', 'single'),
        
        // Include user agent in logs
        'include_user_agent' => true,
        
        // Include IP address in logs
        'include_ip' => true,
    ],
];