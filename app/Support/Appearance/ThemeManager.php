<?php

namespace App\Support\Appearance;

use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeManager
{
    /** Cache key for the active theme slug */
    private const CACHE_ACTIVE_SLUG = 'appearance.active_theme.slug';

    /** Candidate screenshot file names (in priority order) */
    private const SCREENSHOT_CANDIDATES = [
        'screenshot.png',
        'screenshot.jpg',
        'screenshot.jpeg',
        'screenshot.webp',
    ];

    /** True if the themes table exists (safe during early boot/composer scripts) */
    private function themesTableReady(): bool
    {
        try {
            return Schema::hasTable('themes');
        } catch (\Throwable $e) {
            return false;
        }
    }

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
                    if (!is_dir($dir)) {
                        continue;
                    }
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
                        'screenshot' => $this->findScreenshot($slug),
                    ];
                } else {
                    $out[$slug]['paths'][] = $p;
                    // prefer a screenshot if not yet found
                    if (empty($out[$slug]['screenshot'])) {
                        $out[$slug]['screenshot'] = $this->findScreenshot($slug);
                    }
                }
            }
        }

        return array_values($out);
    }

    /** Sync DB rows to disk */
    public function syncDb(): void
    {
        if (!$this->themesTableReady()) {
            return;
        }

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
        if (!$this->themesTableReady()) {
            throw new \RuntimeException('Themes table not available yet. Run migrations first.');
        }

        DB::transaction(function () use ($slug) {
            Theme::query()->update(['status' => 'installed']);
            Theme::where('slug', $slug)->update(['status' => 'active']);
        });

        Cache::forever(self::CACHE_ACTIVE_SLUG, $slug);
        $this->writeActiveHint($slug);

        $this->rebindViewNamespace($slug);
    }

    /** Deactivate ALL themes so none is active */
    public function deactivateAll(): void
    {
        if ($this->themesTableReady()) {
            DB::transaction(function () {
                Theme::query()->update(['status' => 'installed']);
            });
        }

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

    /** Current active slug or '' if none (SAFE before migrations) */
    public function activeSlug(): string
    {
        if (!$this->themesTableReady()) {
            return '';
        }

        return Cache::rememberForever(self::CACHE_ACTIVE_SLUG, function () {
            return (string) (Theme::where('status', 'active')->value('slug') ?? '');
        });
    }

    /** Get active theme info array or null */
    public function active(): ?array
    {
        $slug = $this->activeSlug();
        return $slug ? $this->discover($slug) : null;
    }

    /** Aggregate info for a specific slug (name/version/author/paths/metadata/screenshot) */
    public function discover(string $slug): ?array
    {
        if (!$this->themeExists($slug)) {
            return null;
        }

        $paths = $this->asPaths($slug);
        $meta = [];
        foreach ($paths as $p) {
            $m = $this->readMeta($p);
            if ($m) {
                $meta = array_replace($meta, $m);
            }
        }

        return [
            'name' => $meta['name'] ?? Str::headline($slug),
            'slug' => $slug,
            'version' => $meta['version'] ?? null,
            'author' => $meta['author'] ?? null,
            'path' => $paths[0] ?? null,
            'paths' => $paths,
            'metadata' => $meta,
            'screenshot' => $this->findScreenshot($slug),
        ];
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

    /**
     * Bind "theme::" to active (or fallback when none).
     * Always append the fallback as a final namespace to avoid hard crashes for missing partials.
     */
    public function rebindViewNamespace(?string $slug = null): void
    {
        $slug ??= $this->activeSlug();

        $paths = [];
        if ($slug) {
            $primary = $this->viewsPath($slug);
            if (is_dir($primary)) {
                $paths[] = $primary;
            }

            $res = resource_path("views/themes/{$slug}");
            if (is_dir($res)) {
                $paths[] = is_dir($res . '/views') ? ($res . '/views') : $res;
            }
        }

        // Always add fallback at the end (lowest priority)
        $paths[] = $this->fallbackViewsPath();

        // Deduplicate while preserving order
        $paths = array_values(array_unique($paths));

        View::replaceNamespace('theme', $paths);
        app('view.finder')->flush();
    }

    /** Read metadata from theme.json or config.php in a theme directory */
    public function readMeta(string $themeDir): array
    {
        $json = $themeDir . '/theme.json';
        $php = $themeDir . '/config.php';

        if (is_file($json)) {
            return json_decode((string) @file_get_contents($json), true) ?: [];
        }
        if (is_file($php)) {
            return (array) (include $php);
        }
        return [];
    }

    /** Install a theme ZIP to /themes */
    public function installZip(string $uploadedZipFullPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($uploadedZipFullPath) !== true) {
            throw new \RuntimeException('Invalid theme zip');
        }

        // Try to detect the top-level folder name; fallback to filename slug
        $first = rtrim($zip->getNameIndex(0) ?: '', '/');
        $guessed = $first && str_contains($first, '/') ? explode('/', $first, 2)[0] : $first;
        $slug = Str::slug(basename($guessed ?: pathinfo($uploadedZipFullPath, PATHINFO_FILENAME)));

        // Extract to basePath/{slug}
        $target = $this->basePath() . "/{$slug}";
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }
        $zip->extractTo($target);
        $zip->close();

        // Safe if table doesn't exist yet
        $this->syncDb();

        return $slug;
    }

    /** Delete from /themes (controller also cleans resources path) */
    public function delete(string $slug): void
    {
        if ($slug === $this->activeSlug()) {
            throw new \RuntimeException("Cannot delete the active theme '{$slug}'. Deactivate first.");
        }

        foreach ($this->asPaths($slug) as $path) {
            if (is_dir($path)) {
                $this->rrmdir($path);
            }
        }

        if ($this->themesTableReady()) {
            Theme::where('slug', $slug)->delete();
        }
    }

    /** Does this slug exist in either root? */
    public function themeExists(string $slug): bool
    {
        foreach ($this->asPaths($slug) as $p) {
            if (is_dir($p)) {
                return true;
            }
        }
        return false;
    }

    /** All candidate paths (disk + resources), primary first */
    public function asPaths(string $slug): array
    {
        $paths = [
            $this->basePath() . "/{$slug}",
            $this->basePath() . "/{$slug}/views",
            resource_path("views/themes/{$slug}"),
            resource_path("views/themes/{$slug}/views"),
        ];
        // Keep only unique, existing directories (but we still want non-existing order for discovery)
        return array_values(array_unique($paths));
    }

    /** Find a screenshot file for a theme across both roots */
    public function findScreenshot(string $slug): ?string
    {
        $roots = [
            $this->basePath() . "/{$slug}",
            resource_path("views/themes/{$slug}"),
        ];
        foreach ($roots as $root) {
            foreach (self::SCREENSHOT_CANDIDATES as $name) {
                $p = $root . '/' . $name;
                if (is_file($p)) {
                    return $p;
                }
            }
        }
        return null;
    }

    protected function rrmdir(string $dir): void
    {
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = "{$dir}/{$f}";
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    /** Write a small hint for the active theme (purely optional, used by ops/tools) */
    private function writeActiveHint(?string $slug): void
    {
        try {
            Storage::disk('local')->put('appearance_active_theme.txt', (string) $slug);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}