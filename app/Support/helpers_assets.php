<?php

use Illuminate\Support\Facades\Event;

if (!function_exists('theme_slug')) {
    function theme_slug(): string
    {
        try {
            return (string) app(\App\Support\Appearance\ThemeManager::class)->activeSlug();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
if (!function_exists('theme_path')) {
    function theme_path(string $sub = ''): string
    {
        $slug = theme_slug();
        $roots = [
            base_path("themes/{$slug}"),
            resource_path("views/themes/{$slug}"),
        ];
        foreach ($roots as $root) {
            $p = rtrim($root, '/\\') . ($sub ? '/' . ltrim($sub, '/\\') : '');
            if (is_dir($root) || is_file($p))
                return $p;
        }
        return $roots[0] ?? base_path('themes/unknown');
    }
}
if (!function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $slug = theme_slug();
        return $slug ? route('theme.asset', ['slug' => $slug, 'path' => ltrim($path, '/')]) : '#';
    }
}

$GLOBALS['__theme_assets'] ??= ['styles' => [], 'scripts' => [], 'q_styles' => [], 'q_scripts' => [], 'enqueued' => false];

if (!function_exists('register_style')) {
    function register_style(string $handle, string $href, array $deps = [], ?string $ver = null): void
    {
        $GLOBALS['__theme_assets']['styles'][$handle] = compact('href', 'deps', 'ver');
    }
}
if (!function_exists('enqueue_style')) {
    function enqueue_style(string $handle): void
    {
        $GLOBALS['__theme_assets']['q_styles'][] = $handle;
    }
}
if (!function_exists('register_script')) {
    function register_script(string $handle, string $src, array $deps = [], ?string $ver = null, bool $in_footer = true, array $attrs = []): void
    {
        $GLOBALS['__theme_assets']['scripts'][$handle] = compact('src', 'deps', 'ver', 'in_footer', 'attrs');
    }
}
if (!function_exists('enqueue_script')) {
    function enqueue_script(string $handle): void
    {
        $GLOBALS['__theme_assets']['q_scripts'][] = $handle;
    }
}

if (!function_exists('theme_maybe_enqueue')) {
    function theme_maybe_enqueue(): void
    {
        if (!$GLOBALS['__theme_assets']['enqueued']) {
            Event::dispatch('enqueue.front');
            $GLOBALS['__theme_assets']['enqueued'] = true;
        }
    }
}

if (!function_exists('theme_head')) {
    function theme_head(): string
    {
        theme_maybe_enqueue();
        $out = [];
        foreach (array_unique($GLOBALS['__theme_assets']['q_styles']) as $h) {
            $s = $GLOBALS['__theme_assets']['styles'][$h] ?? null;
            if (!$s)
                continue;
            $href = $s['href'];
            if (!empty($s['ver']))
                $href .= (str_contains($href, '?') ? '&' : '?') . 'ver=' . rawurlencode($s['ver']);
            $out[] = '<link rel="stylesheet" href="' . e($href) . '">';
        }
        // header scripts (rare)
        foreach (array_unique($GLOBALS['__theme_assets']['q_scripts']) as $h) {
            $s = $GLOBALS['__theme_assets']['scripts'][$h] ?? null;
            if (!$s || ($s['in_footer'] ?? true))
                continue;
            $src = $s['src'];
            if (!empty($s['ver']))
                $src .= (str_contains($src, '?') ? '&' : '?') . 'ver=' . rawurlencode($s['ver']);
            $attrs = '';
            foreach ($s['attrs'] ?? [] as $k => $v)
                $attrs .= ' ' . e($k) . (is_bool($v) ? '' : '="' . e($v) . '"');
            $out[] = '<script src="' . e($src) . '"' . $attrs . '></script>';
        }
        return implode("\n", $out) . "\n";
    }
}
if (!function_exists('theme_footer')) {
    function theme_footer(): string
    {
        theme_maybe_enqueue();
        $out = [];
        foreach (array_unique($GLOBALS['__theme_assets']['q_scripts']) as $h) {
            $s = $GLOBALS['__theme_assets']['scripts'][$h] ?? null;
            if (!$s || !($s['in_footer'] ?? true))
                continue;
            $src = $s['src'];
            if (!empty($s['ver']))
                $src .= (str_contains($src, '?') ? '&' : '?') . 'ver=' . rawurlencode($s['ver']);
            $attrs = '';
            foreach ($s['attrs'] ?? [] as $k => $v)
                $attrs .= ' ' . e($k) . (is_bool($v) ? '' : '="' . e($v) . '"');
            $out[] = '<script src="' . e($src) . '"' . $attrs . '></script>';
        }
        return implode("\n", $out) . "\n";
    }
}