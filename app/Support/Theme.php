<?php

namespace App\Support;

use App\Models\AppearanceSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Theme
{
    public static function activeSlug(): string
    {
        $db = AppearanceSetting::get('active_theme');
        return $db['slug'] ?? config('laracms.active_theme', 'laracms');
    }

    public static function basePath(): string
    {
        // Keep themes in /themes/{slug} (core-friendly)
        return base_path('themes');
    }

    public static function dir(string $slug = null): string
    {
        $slug = $slug ?: static::activeSlug();
        return static::basePath() . DIRECTORY_SEPARATOR . $slug;
    }

    public static function meta(string $slug = null): array
    {
        $dir = static::dir($slug);
        $json = $dir . '/theme.json';
        $php = $dir . '/config.php';

        if (File::exists($json))
            return json_decode(File::get($json), true) ?: [];
        if (File::exists($php))
            return require $php;

        return [];
    }

    public static function list(): array
    {
        $out = [];
        $base = static::basePath();
        if (!is_dir($base))
            return $out;

        foreach (scandir($base) ?: [] as $d) {
            if ($d === '.' || $d === '..')
                continue;
            $path = $base . DIRECTORY_SEPARATOR . $d;
            if (!is_dir($path))
                continue;
            $meta = static::meta($d);
            $out[] = [
                'slug' => $d,
                'name' => $meta['name'] ?? ucfirst(str_replace('-', ' ', $d)),
                'screenshot' => $meta['screenshot'] ?? null,
                'version' => $meta['version'] ?? null,
                'author' => $meta['author'] ?? null,
                'meta' => $meta,
            ];
        }
        return $out;
    }
}