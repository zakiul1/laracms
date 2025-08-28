<?php

return [
    // Paths
    'modules_path' => base_path('modules'),
    'public_modules_path' => public_path('modules'),

    'themes_path' => base_path('themes'),
    'public_themes_path' => public_path('themes'),

    // Theme
    'active_theme' => env('LARACMS_THEME', 'laracms'),

    // Auto publish built assets (dist → public) when missing/stale
    'auto_publish' => true,

    // Hook names your layout uses
    'hooks' => [
        'enqueue_head',
        'enqueue_footer',
        'enqueue_front_assets',
        'enqueue_admin_head',
        'enqueue_admin_footer',
        'theme_head',
        'theme_footer',
    ],
];