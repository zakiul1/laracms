<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    /**
     * List installed themes + mark the active one.
     */
    public function index(Request $request)
    {
        $themes = $this->scanThemes();

        // Determine active theme: DB → file → config
        $active = (string) config('laracms.active_theme', 'laracms');

        $dbActive = null;
        try {
            $dbActive = DB::table('themes')->where('status', 'active')->value('slug');
        } catch (\Throwable $e) {
            // ignore, we'll use file fallback below
        }

        if ($dbActive) {
            $active = $dbActive;
        } else {
            // ✅ also check the file when DB returned null OR threw
            $file = storage_path('app/appearance_active_theme.txt');
            if (is_file($file)) {
                $slugFromFile = trim((string) @file_get_contents($file));
                if ($slugFromFile !== '') {
                    $active = $slugFromFile;
                }
            }
        }

        // Decorate themes for the blade and soft-sync DB (if present)
        foreach ($themes as $slug => &$t) {
            $t['status'] = ($slug === $active) ? 'active' : 'installed';
            $t['version'] = $t['meta']['version'] ?? null;
            $t['author'] = $t['meta']['author'] ?? null;
            $t['description'] = $t['meta']['description'] ?? null;
            $t['screenshot_url'] = $t['screenshot'] ?? null;
        }
        unset($t);

        try {
            foreach ($themes as $slug => $t) {
                DB::table('themes')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $t['name'],
                        'status' => $t['status'],
                        'metadata' => json_encode($t['meta'] ?? []),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // DB not required
        }

        return view('admin.appearance.themes.index', [
            'themes' => $themes,
            'active' => $active,
        ]);
    }


    /**
     * Activate a theme: DB if available + file fallback + runtime config.
     */
    public function activate(Request $request, string $slug)
    {
        $themes = $this->scanThemes();
        abort_unless(isset($themes[$slug]), 404, 'Theme not found');

        // Persist in DB when available
        try {
            DB::transaction(function () use ($slug, $themes) {
                DB::table('themes')->update(['status' => 'inactive']);
                DB::table('themes')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $themes[$slug]['name'],
                        'status' => 'active',
                        'metadata' => json_encode($themes[$slug]['meta'] ?? []),
                    ]
                );
            });
        } catch (\Throwable $e) {
        }

        // File fallback (so the ServiceProvider picks it up immediately)
        try {
            Storage::disk('local')->put('appearance_active_theme.txt', $slug);
        } catch (\Throwable $e) {
        }

        // Runtime
        config(['laracms.active_theme' => $slug]);

        return back()->with('success', "Theme “{$themes[$slug]['name']}” is now active.");
    }

    /**
     * Optional: set all inactive and fall back to default.
     */
    public function deactivate(Request $request, string $slug)
    {
        $default = (string) config('laracms.default_theme', config('laracms.active_theme', 'laracms'));

        try {
            DB::table('themes')->update(['status' => 'inactive']);
        } catch (\Throwable $e) {
        }
        try {
            Storage::disk('local')->put('appearance_active_theme.txt', $default);
        } catch (\Throwable $e) {
        }

        config(['laracms.active_theme' => $default]);

        return back()->with('success', 'Theme deactivated.');
    }

    /**
     * Preview using ?__theme=slug (ThemeServiceProvider honors this for logged-in users).
     */
    public function preview(string $slug)
    {
        return redirect()->to(url('/') . '?__theme=' . urlencode($slug));
    }

    /**
     * Upload stub (accepts "zip" or "theme_zip" input names).
     */
    public function upload(Request $request)
    {
        // Accept either field name
        $file = $request->file('zip') ?? $request->file('theme_zip');
        abort_unless($file, 422, 'No file provided');

        $request->validate([
            $file->getClientOriginalName() => 'nullable', // noop to keep Validator happy
        ]);
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return back()->with('error', 'Please upload a .zip file.');
        }

        // TODO: unzip to a theme root and then redirect.
        return back()->with('success', 'Theme uploaded (install/unzip not implemented yet).');
    }

    /**
     * Delete a theme (not the active one).
     */
    public function destroy(string $slug)
    {
        // Guard active
        $active = (string) config('laracms.active_theme', 'laracms');
        try {
            $dbActive = DB::table('themes')->where('status', 'active')->value('slug');
            if ($dbActive)
                $active = $dbActive;
        } catch (\Throwable $e) {
            $file = storage_path('app/appearance_active_theme.txt');
            if (is_file($file)) {
                $fileActive = trim((string) @file_get_contents($file));
                if ($fileActive !== '')
                    $active = $fileActive;
            }
        }
        if ($slug === $active) {
            return back()->with('error', 'Cannot delete the active theme.');
        }

        // Delete all actual matching folders (slug of folder name)
        $paths = $this->actualThemeDirsFor($slug);
        $deletedAny = false;
        foreach ($paths as $p) {
            if (is_dir($p)) {
                File::deleteDirectory($p);
                $deletedAny = true;
            }
        }

        // Clean published screenshots
        try {
            $disk = Storage::disk('public');
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $disk->delete("theme_screenshots/{$slug}.{$ext}");
            }
        } catch (\Throwable $e) {
        }

        // Clean DB row
        try {
            DB::table('themes')->where('slug', $slug)->delete();
        } catch (\Throwable $e) {
        }

        return back()->with(
            $deletedAny ? 'success' : 'warning',
            $deletedAny ? "Theme “{$slug}” deleted." : 'Theme directory not found.'
        );
    }

    /* ------------------------- Helpers ------------------------- */

    protected function themeSearchRoots(): array
    {
        return [
            resource_path('themes'),
            base_path('themes'),
            base_path('Modules/Appearance/Themes'),
            resource_path('views/themes'),
        ];
    }

    protected function actualThemeDirsFor(string $slug): array
    {
        $found = [];
        foreach ($this->themeSearchRoots() as $root) {
            if (!is_dir($root))
                continue;
            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..')
                    continue;
                $dir = $root . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($dir))
                    continue;
                if (Str::slug($entry) === Str::slug($slug)) {
                    $found[] = $dir;
                }
            }
        }
        return $found;
    }

    protected function scanThemes(): array
    {
        $themes = [];
        foreach ($this->themeSearchRoots() as $root) {
            if (!is_dir($root))
                continue;

            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..')
                    continue;
                $dir = $root . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($dir))
                    continue;

                $slug = Str::slug($entry);
                if (!isset($themes[$slug])) {
                    $themes[$slug] = $this->readTheme($slug, $dir);
                } else {
                    $themes[$slug]['paths'][] = $dir;
                    if (!$themes[$slug]['screenshot']) {
                        $themes[$slug]['screenshot'] = $this->findScreenshotUrl($slug, $dir);
                    }
                }
            }
        }
        ksort($themes);
        return $themes;
    }

    protected function readTheme(string $slug, string $path): array
    {
        $meta = [
            'name' => Str::headline($slug),
            'description' => null,
            'version' => null,
            'author' => null,
        ];

        $json = $path . DIRECTORY_SEPARATOR . 'theme.json';
        if (is_file($json)) {
            try {
                $data = json_decode(file_get_contents($json), true, flags: JSON_THROW_ON_ERROR);
                if (is_array($data))
                    $meta = array_merge($meta, array_intersect_key($data, $meta));
            } catch (\Throwable $e) {
            }
        }

        $php = $path . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($php)) {
            try {
                $arr = include $php;
                if (is_array($arr))
                    $meta = array_merge($meta, array_intersect_key($arr, $meta));
            } catch (\Throwable $e) {
            }
        }

        $views = is_dir($path . DIRECTORY_SEPARATOR . 'views')
            ? $path . DIRECTORY_SEPARATOR . 'views'
            : $path;

        return [
            'slug' => $slug,
            'name' => $meta['name'] ?: Str::headline($slug),
            'paths' => [$path],
            'path' => $path,
            'views' => $views,
            'screenshot' => $this->findScreenshotUrl($slug, $path),
            'meta' => $meta,
        ];
    }

    protected function findScreenshotUrl(string $slug, string $path): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = $path . DIRECTORY_SEPARATOR . 'screenshot.' . $ext;
            if (is_file($file))
                return $this->publishScreenshot($slug, $file, $ext);
        }
        return null;
    }

    protected function publishScreenshot(string $slug, string $absolute, string $ext): ?string
    {
        try {
            $dir = 'theme_screenshots';
            $filename = $slug . '.' . strtolower($ext);
            $disk = Storage::disk('public');

            $target = $dir . '/' . $filename;
            $needsCopy = !$disk->exists($target);
            if (!$needsCopy) {
                $srcTime = @filemtime($absolute) ?: 0;
                $dstTime = @filemtime($disk->path($target)) ?: 0;
                $needsCopy = $srcTime > $dstTime;
            }

            if ($needsCopy) {
                if (!$disk->exists($dir))
                    $disk->makeDirectory($dir);
                $disk->put($target, file_get_contents($absolute));
            }

            return asset('storage/' . $target);
        } catch (\Throwable $e) {
            return null;
        }
    }
}