<?php

namespace App\Support\Appearance;

use App\Support\Settings\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Customizer
{
    public function __construct(
        protected ThemeManager $themes,
        protected Settings $settings
    ) {
    }

    /** Read customize schema from theme.json or provide sensible defaults */
    public function schema(string $slug): array
    {
        // Merge all theme roots (disk + resources) and take the first theme.json that has customize
        $candidates = [
            $this->themes->basePath() . "/{$slug}/theme.json",
            resource_path("views/themes/{$slug}/theme.json"),
        ];

        $customize = null;
        foreach ($candidates as $p) {
            if (is_file($p)) {
                $json = json_decode((string) @file_get_contents($p), true) ?: [];
                if (!empty($json['customize'])) {
                    $customize = $json['customize'];
                    break;
                }
            }
        }

        if (!$customize) {
            // Fallback default schema
            $customize = [
                'panels' => [
                    [
                        'title' => 'Site Identity',
                        'fields' => [
                            ['key' => 'general.site_title', 'label' => 'Site Title', 'type' => 'text', 'default' => config('app.name', 'Laracms')],
                            ['key' => 'general.logo', 'label' => 'Logo URL', 'type' => 'image', 'default' => null],
                        ],
                    ],
                    [
                        'title' => 'Colors',
                        'fields' => [
                            ['key' => 'colors.primary', 'label' => 'Primary', 'type' => 'color', 'default' => '#2563eb'],
                            ['key' => 'colors.accent', 'label' => 'Accent', 'type' => 'color', 'default' => '#10b981'],
                        ],
                    ],
                    [
                        'title' => 'Typography',
                        'fields' => [
                            ['key' => 'typography.heading', 'label' => 'Heading Font', 'type' => 'text', 'default' => 'Inter'],
                            ['key' => 'typography.body', 'label' => 'Body Font', 'type' => 'text', 'default' => 'Inter'],
                        ],
                    ],
                ],
                'cssVars' => [
                    '--theme-primary' => 'colors.primary',
                    '--theme-accent' => 'colors.accent',
                    '--font-heading' => 'typography.heading',
                    '--font-body' => 'typography.body',
                ],
                'liveBindings' => [
                    '#title:text' => 'general.site_title',
                    '#logo:src' => 'general.logo',
                ],
            ];
        }

        // normalize structure
        $customize['panels'] = array_values($customize['panels'] ?? []);
        $customize['cssVars'] = $customize['cssVars'] ?? [];
        $customize['liveBindings'] = $customize['liveBindings'] ?? [];

        return $customize;
    }

    /** Current values (settings + defaults) as [key => value] */
    public function values(string $slug): array
    {
        $schema = $this->schema($slug);
        $out = [];
        foreach ($schema['panels'] as $panel) {
            foreach ($panel['fields'] as $f) {
                $key = (string) $f['key'];
                $def = $f['default'] ?? null;
                $val = $this->get($slug, $key, $def);
                $out[$key] = $val;
            }
        }
        return $out;
    }

    /** Get a single value */
    public function get(string $slug, string $key, mixed $default = null): mixed
    {
        return $this->settings->get("customize.{$slug}." . $key, $default);
    }

    /** Set a single value */
    public function set(string $slug, string $key, mixed $value): void
    {
        $this->settings->set("customize.{$slug}." . $key, $value);
    }

    /** Bulk save */
    public function save(string $slug, array $kv): void
    {
        foreach ($kv as $key => $val) {
            $this->set($slug, (string) $key, $val);
        }
    }

    /** Reset all known keys from schema */
    public function reset(string $slug): void
    {
        $schema = $this->schema($slug);
        foreach ($schema['panels'] as $panel) {
            foreach ($panel['fields'] as $f) {
                $this->set($slug, (string) $f['key'], null);
            }
        }
    }

    /** Render a <style> with CSS vars from saved values + cssVars mapping */
    public function renderCssVars(?string $slug = null): string
    {
        $slug ??= $this->themes->activeSlug();
        if (!$slug)
            return '';
        $schema = $this->schema($slug);
        $vals = $this->values($slug);

        $lines = [];
        foreach ($schema['cssVars'] as $var => $fromKey) {
            $val = Arr::get($vals, $fromKey);
            if (is_string($val) && $val !== '') {
                $lines[] = "{$var}: {$val};";
            }
        }
        if (!$lines)
            return '';

        $css = implode('', $lines);
        return "<style>:root{{$css}}</style>";
    }

    /** Live-bindable mapping for preview script */
    public function liveBindings(string $slug): array
    {
        $schema = $this->schema($slug);
        return (array) $schema['liveBindings'];
    }
}