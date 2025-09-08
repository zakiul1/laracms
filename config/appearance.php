<?php

return [
    'themes_path' => env('THEMES_PATH', base_path('themes')),
    'public_themes_path' => env('PUBLIC_THEMES_PATH', 'themes'),
    'fallback_views_path' => env('THEME_FALLBACK_VIEWS', resource_path('views/theme-fallback')),
];