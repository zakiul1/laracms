<?php

namespace App\Support\Appearance;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class TemplateScanner
{
    /**
     * Return ['full-width' => 'Full Width', ...] for the active theme.
     * $type = 'page' | 'post'
     *
     * Scans multiple compatible locations so you don't have to move files:
     *  - resources/views/themes/<slug>/*
     *  - resources/themes/<slug>/*
     *  And inside those roots, supports patterns like:
     *    /templates/page/*.blade.php
     *    /pages/*.blade.php
     *    /pages/templates/*.blade.php        <-- your current layout
     *    /page/templates/*.blade.php
     *    /templates/pages/*.blade.php
     *    /page-*.blade.php                   (legacy)
     * (For posts, the same shapes with 'post(s)' + 'single-*.blade.php')
     *
     * You may add at top of a Blade to set the label and scope:
     *    {{-- Template: Landing Page --}}
     *    {{-- For: page --}}
     * or
     *    {{-- Template Name: Landing Page --}}
     */
    public function list(string $type): array
    {
        $type = $type === 'post' ? 'post' : 'page';

        // Try to keep "theme::" namespace in sync (if ThemeManager exists)
        try {
            app(\App\Support\Appearance\ThemeManager::class)->rebindViewNamespace();
        } catch (\Throwable $e) {
            // ignore
        }

        // Resolve active theme slug
        $slug = '';
        try {
            $slug = (string) app(\App\Support\Appearance\ThemeManager::class)->activeSlug();
        } catch (\Throwable $e) {
            // ignore
        }
        if ($slug === '') {
            // fallback to config/env if ThemeManager isn't wired
            $slug = (string) (config('appearance.active_theme') ?? env('APP_THEME', 'default'));
        }
        if ($slug === '') {
            return [];
        }

        $cacheKey = "tplscan:{$slug}:{$type}";
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($slug, $type) {
            // Candidate roots (support both locations)
            $roots = [];
            $r1 = resource_path("views/themes/{$slug}");   // /resources/views/themes/<slug>
            $r2 = resource_path("themes/{$slug}");         // /resources/themes/<slug>
            foreach ([$r1, $r2] as $root) {
                if (is_dir($root)) {
                    $roots[] = $root;
                    // Some projects nest an extra /views layer inside the theme
                    if (is_dir($root . '/views')) {
                        $roots[] = $root . '/views';
                    }
                }
            }
            if (!$roots) {
                return [];
            }

            // Patterns to search (type-aware)
            $pat = $type === 'page'
                ? [
                    '/templates/page/*.blade.php',
                    '/pages/*.blade.php',
                    '/pages/templates/*.blade.php',   // <-- your current path
                    '/page/templates/*.blade.php',
                    '/templates/pages/*.blade.php',
                    '/page-*.blade.php',
                ]
                : [
                    '/templates/post/*.blade.php',
                    '/posts/*.blade.php',
                    '/posts/templates/*.blade.php',
                    '/post/templates/*.blade.php',
                    '/templates/posts/*.blade.php',
                    '/single-*.blade.php',
                ];

            // Collect unique files from all roots/patterns
            $found = [];
            foreach ($roots as $root) {
                foreach ($pat as $p) {
                    foreach ((glob($root . $p) ?: []) as $path) {
                        if (is_file($path)) {
                            $found[$this->normalizePath($path)] = true;
                        }
                    }
                }
            }
            $candidates = array_keys($found);
            if (!$candidates) {
                return [];
            }

            // Build options "file-slug" => "Label"
            $options = [];
            foreach ($candidates as $path) {
                $file = basename($path, '.blade.php'); // e.g. full-width
                if ($file === 'default' || Str::startsWith($file, '_')) {
                    continue; // skip partials
                }

                // Default label from filename
                $label = Str::of($file)->replace(['-', '_'], ' ')->title()->value();

                // Read a small header chunk for metadata
                $head = @file_get_contents($path, false, null, 0, 4096) ?: '';

                // Prefer explicit label if provided
                if (preg_match('/\{\-\-\s*Template\s*:\s*(.+?)\s*\-\-\}/is', $head, $m)) {
                    $label = trim($m[1]);
                } elseif (preg_match('/\{\-\-\s*Template\s*Name\s*:\s*(.+?)\s*\-\-\}/is', $head, $m2)) {
                    $label = trim($m2[1]);
                }

                // Optional scoping: For|Types|PostType: page|post
                $scopedTypes = $this->parseTypesFromHeader($head);
                if ($scopedTypes && !in_array($type, $scopedTypes, true)) {
                    continue; // skip if scoped to a different type
                }

                $options[$file] = $label;
            }

            ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
            return $options;
        });
    }

    // -------- helpers --------

    protected function normalizePath(string $p): string
    {
        return str_replace('\\', '/', $p);
    }

    /**
     * Parse any of:
     *   {{-- For: page, post --}}
     *   {{-- Types: page --}}
     *   {{-- PostType: post --}}
     * Returns lowercased array, e.g. ['page', 'post'] or [].
     */
    protected function parseTypesFromHeader(string $head): array
    {
        $keys = ['For', 'Types', 'PostType'];
        $out = [];
        foreach ($keys as $k) {
            if (preg_match('/\{\-\-\s*' . $k . '\s*:\s*(.+?)\s*\-\-\}/is', $head, $m)) {
                $vals = preg_split('/[,|]/', $m[1]) ?: [];
                foreach ($vals as $v) {
                    $v = Str::lower(trim($v));
                    if ($v !== '') {
                        $out[$v] = true;
                    }
                }
            }
        }
        return array_keys($out);
    }
}