<?php
return [
    'modules_path' => base_path('modules'),  // plugins
    'themes_path' => base_path('themes'),   // themes
    'active_theme' => env('LARACMS_THEME', 'laracms'), // default theme slug
];