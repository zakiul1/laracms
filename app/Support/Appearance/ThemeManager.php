<?php

namespace App\Support\Appearance;

use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeManager
{
    /** Cache key for the active theme slug */
    private const CACHE_ACTIVE_SLUG = 'appearance.active_theme.slug';

    public function basePath(): string
    {
        $path = config('appearance.themes_path');
        if (!is_string($path) || $path === '') {
            $path = base_path('themes');
        }
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /** Where to render if no theme is active */
    protected function fallbackViewsPath(): string
    {
        $p = (string) config('appearance.fallback_views_path', resource_path('views/theme-fallback'));
        if (!is_dir($p)) {
            @mkdir($p, 0775, true);
        }
        return $p;
    }

    /** Discover themes in BOTH /themes and resources/views/themes */
    public function list(): array
    {
        $roots = [
            $this->basePath(),
            resource_path('views/themes'),
        ];

        $out = [];
        foreach ($roots as $dir) {
            if (!is_dir($dir)) {
                if ($dir === $this->basePath()) {
                    @mkdir($dir, 0775, true);
                    if (!is_dir($dir))
                        continue;
                } else {
                    continue;
                }
            }

            foreach (glob($dir . '/*', GLOB_ONLYDIR) as $p) {
                $slug = Str::slug(basename($p));
                $meta = $this->readMeta($p);

                if (!isset($out[$slug])) {
                    $out[$slug] = [
                        'name' => $meta['name'] ?? Str::headline($slug),
                        'slug' => $slug,
                        'version' => $meta['version'] ?? null,
                        'author' => $meta['author'] ?? null,
                        'path' => $p,
                        'paths' => [$p],
                        'metadata' => $meta,
                    ];
                } else {
                    $out[$slug]['paths'][] = $p;
                }
            }
        }
        return array_values($out);
    }

    /** Sync DB rows to disk */
    public function syncDb(): void
    {
        $existing = Theme::pluck('id', 'slug')->all();

        foreach ($this->list() as $t) {
            Theme::updateOrCreate(
                ['slug' => $t['slug']],
                Arr::only($t, ['name', 'version', 'author', 'path', 'metadata'])
                + ['status' => Theme::where('slug', $t['slug'])->value('status') ?? 'installed']
            );
            unset($existing[$t['slug']]);
        }

        if ($existing) {
            $active = Cache::get(self::CACHE_ACTIVE_SLUG);
            if ($active && isset($existing[$active])) {
                Cache::forget(self::CACHE_ACTIVE_SLUG);
            }
            Theme::whereIn('id', array_values($existing))->delete();
        }
    }

    /** Activate specific theme (normal case) */
    public function activate(string $slug): void
    {
        if (!$this->themeExists($slug)) {
            throw new \InvalidArgumentException("Theme '{$slug}' not found.");
        }

        DB::transaction(function () use ($slug) {
            Theme::query()->update(['status' => 'installed']);
            Theme::where('slug', $slug)->update(['status' => 'active']);
        });

        Cache::forever(self::CACHE_ACTIVE_SLUG, $slug);

        $this->rebindViewNamespace($slug);
    }

    /** Deactivate ALL themes so none is active */
    public function deactivateAll(): void
    {
        DB::transaction(function () {
            Theme::query()->update(['status' => 'installed']);
        });

        // Clear any file hint you may have been writing
        try {
            Storage::disk('local')->delete('appearance_active_theme.txt');
        } catch (\Throwable $e) {
        }

        Cache::forget(self::CACHE_ACTIVE_SLUG);

        // Point "theme::" to the fallback views so frontend doesn't crash
        View::replaceNamespace('theme', [$this->fallbackViewsPath()]);
        app('view.finder')->flush();
    }

    /** Current active slug or '' if none */
    public function activeSlug(): string
    {
        return Cache::rememberForever(self::CACHE_ACTIVE_SLUG, function () {
            return (string) (Theme::where('status', 'active')->value('slug') ?? '');
        });
    }

    /** Absolute path to /themes/{slug}/views or /themes/{slug} */
    public function viewsPath(?string $slug = null): string
    {
        $slug ??= $this->activeSlug();
        $root = $this->basePath() . ($slug ? "/{$slug}" : '');

        if ($slug) {
            $v = $root . '/views';
            return is_dir($v) ? $v : $root;
        }

        // no slug: return fallback
        return $this->fallbackViewsPath();
    }

    /** Bind "theme::" to active (or fallback when none) */
    public function rebindViewNamespace(?string $slug = null): void
    {
        $slug ??= $this->activeSlug();

        $paths = [];
        if ($slug) {
            $primary = $this->viewsPath($slug);
            if (is_dir($primary))
                $paths[] = $primary;

            $res = resource_path("views/themes/{$slug}");
            if (is_dir($res)) {
                $paths[] = is_dir($res . '/views') ? ($res . '/views') : $res;
            }
        }

        if (empty($paths)) {
            $paths[] = $this->fallbackViewsPath();
        }

        View::replaceNamespace('theme', $paths);
        app('view.finder')->flush();
    }

    public function readMeta(string $themeDir): array
    {
        $json = $themeDir . '/theme.json';
        $php = $themeDir . '/config.php';

        if (is_file($json))
            return json_decode(file_get_contents($json), true) ?: [];
        if (is_file($php))
            return (array) (include $php);
        return [];
    }

    /** Install a theme ZIP to /themes */
    public function installZip(string $uploadedZipFullPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($uploadedZipFullPath) !== true) {
            throw new \RuntimeException('Invalid theme zip');
        }
        $top = rtrim($zip->getNameIndex(0), '/');
        $slug = basename($top);

        $zip->extractTo($this->basePath());
        $zip->close();

        $this->syncDb();
        return $slug;
    }

    /** Delete from /themes (controller also cleans resources path) */
    public function delete(string $slug): void
    {
        if ($slug === $this->activeSlug()) {
            throw new \RuntimeException("Cannot delete the active theme '{$slug}'. Deactivate first.");
        }

        $path = $this->basePath() . "/{$slug}";
        if (is_dir($path))
            $this->rrmdir($path);
        Theme::where('slug', $slug)->delete();
    }

    /** Does this slug exist in either root? */
    public function themeExists(string $slug): bool
    {
        $paths = [
            $this->basePath() . "/{$slug}",
            $this->basePath() . "/{$slug}/views",
            resource_path("views/themes/{$slug}"),
            resource_path("views/themes/{$slug}/views"),
        ];
        foreach ($paths as $p)
            if (is_dir($p))
                return true;
        return false;
    }

    protected function rrmdir(string $dir): void
    {
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = "{$dir}/{$f}";
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}