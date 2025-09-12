<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeAssetController extends Controller
{
    public function show(string $slug, string $path)
    {
        // Prevent directory traversal
        $path = ltrim(str_replace(['..', '\\'], ['', '/'], $path), '/');

        $candidates = [
            base_path("themes/{$slug}/{$path}"),
            resource_path("views/themes/{$slug}/{$path}"),
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                // Set a safe, explicit content-type (don’t rely on mime_content_type for CSS/JS)
                $types = [
                    'css' => 'text/css; charset=utf-8',
                    'js' => 'application/javascript; charset=utf-8',
                    'svg' => 'image/svg+xml',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf' => 'font/ttf',
                    'eot' => 'application/vnd.ms-fontobject',
                ];
                $ctype = $types[$ext] ?? 'application/octet-stream';

                return response()->file($file, [
                    'Content-Type' => $ctype,
                    // 1 year cache; tweak if needed
                    'Cache-Control' => 'public, max-age=31536000',
                ]);
            }
        }

        abort(404);
    }
}