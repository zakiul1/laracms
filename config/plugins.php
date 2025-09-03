<?php
// config/plugins.php
return [
    'base_path' => base_path('plugins'), // /plugins
    // Allowed max upload size (in MB)
    'max_upload_mb' => 50,
    // Allowed file extensions inside zips
    'allowed_ext' => ['php', 'json', 'js', 'css', 'png', 'jpg', 'jpeg', 'svg', 'webp', 'gif', 'md', 'blade.php'],
];