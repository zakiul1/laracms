<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Support\Appearance\ThemeManager;
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
    public function index(Request $request, ThemeManager $themes)
    {
        // Themes discovered in base themes path
        $baseList = $themes->list(); // [ ['name','slug','version','author','path','metadata'], ... ]
        $map = [];

        foreach ($baseList as $t) {
            $slug = $t['slug'];
            $basePath = $t['path'];
            $views = is_dir($basePath . '/views') ? ($basePath . '/views') : $basePath;

            $map[$slug] = [
                'slug' => $slug,
                'name' => $t['name'],
                'paths' => [$basePath],
                'path' => $basePath,
                'views' => $views,
                'screenshot' => $this->findScreenshotUrl($slug, $basePath),
                'meta' => $t['metadata'] ?? [],
                'version' => $t['metadata']['version'] ?? ($t['version'] ?? null),
                'author' => $t['metadata']['author'] ?? ($t['author'] ?? null),
                'description' => $t['metadata']['description'] ?? null,
            ];
        }

        // Also scan resources/views/themes for additional themes
        $resRoot = resource_path('views/themes');
        if (is_dir($resRoot)) {
            foreach (scandir($resRoot) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..')
                    continue;
                $dir = $resRoot . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($dir))
                    continue;

                $slug = Str::slug($entry);
                if (!isset($map[$slug])) {
                    $info = $this->readTheme($slug, $dir);
                    $map[$slug] = $info;
                } else {
                    // augment paths/screenshots if not present yet
                    $map[$slug]['paths'][] = $dir;
                    if (!$map[$slug]['screenshot']) {
                        $map[$slug]['screenshot'] = $this->findScreenshotUrl($slug, $dir);
                    }
                }
            }
        }

        ksort($map);
        $active = $themes->activeSlug();

        // Decorate statuses
        foreach ($map as $slug => &$t) {
            $t['status'] = ($slug === $active) ? 'active' : 'installed';
            $t['screenshot_url'] = $t['screenshot'] ?? null;
        }
        unset($t);

        // Soft-sync DB table (if present)
        try {
            $themes->syncDb();
            // ensure status reflects current active
            DB::table('themes')->update(['status' => 'installed']);
            DB::table('themes')->where('slug', $active)->update(['status' => 'active']);

            // keep basic metadata in sync
            foreach ($map as $slug => $t) {
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
            // DB table may not exist yet; ignore
        }

        return view('admin.appearance.themes.index', [
            'themes' => $map,
            'active' => $active,
        ]);
    }

    /**
     * Activate a theme: uses ThemeManager for DB/cache/namespace rebinding.
     */
    public function activate(Request $request, string $slug, ThemeManager $themes)
    {
        abort_unless($themes->themeExists($slug), 404, 'Theme not found');

        // Activate + rebind namespace (immediate effect)
        $themes->activate($slug);

        return back()->with('success', "Theme “{$slug}” is now active.");
    }

    /**
     * Deactivate: switch back to default theme.
     */
    public function deactivate(Request $request, string $slug, \App\Support\Appearance\ThemeManager $themes)
    {
        // Only allow if the given slug is currently active; otherwise it's a no-op
        if ($slug !== $themes->activeSlug()) {
            return back()->with('warning', 'That theme is not currently active.');
        }

        $themes->deactivateAll();

        return back()->with('success', 'Theme deactivated. No theme is active now.');
    }


    /**
     * Preview using ?__theme=slug (ThemeServiceProvider honors this for logged-in users).
     */
    public function preview(string $slug)
    {
        return redirect()->to(url('/') . '?__theme=' . urlencode($slug));
    }

    /**
     * Upload and install a theme ZIP.
     */
    public function upload(Request $request, ThemeManager $themes)
    {
        $file = $request->file('zip') ?? $request->file('theme_zip');
        abort_unless($file, 422, 'No file provided');

        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return back()->with('error', 'Please upload a .zip file.');
        }

        // Save to a temp location then install
        $tmp = $file->storeAs('tmp', 'theme-' . time() . '-' . Str::random(6) . '.zip', 'local');
        $full = storage_path('app/' . $tmp);

        try {
            $slug = $themes->installZip($full);
            @unlink($full);
            return back()->with('success', "Theme “{$slug}” uploaded. You can activate it now.");
        } catch (\Throwable $e) {
            @unlink($full);
            return back()->with('error', 'Failed to install theme zip: ' . $e->getMessage());
        }
    }

    /**
     * Delete a theme (not the active one).
     */
    public function destroy(string $slug, ThemeManager $themes)
    {
        if ($slug === $themes->activeSlug()) {
            return back()->with('error', 'Cannot delete the active theme.');
        }

        $deletedAny = false;

        // Try manager deletion (base themes path)
        try {
            $themes->delete($slug);
            $deletedAny = true;
        } catch (\Throwable $e) {
            // ignore; we’ll also try resource cleanup below
        }

        // Also clean resource/views/themes/{slug} if present
        $resPath = resource_path('views/themes/' . $slug);
        if (is_dir($resPath)) {
            File::deleteDirectory($resPath);
            $deletedAny = true;
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
                if (is_array($data)) {
                    $meta = array_merge($meta, array_intersect_key($data, $meta));
                }
            } catch (\Throwable $e) {
            }
        }

        $php = $path . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($php)) {
            try {
                $arr = include $php;
                if (is_array($arr)) {
                    $meta = array_merge($meta, array_intersect_key($arr, $meta));
                }
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
            'version' => $meta['version'] ?? null,
            'author' => $meta['author'] ?? null,
            'description' => $meta['description'] ?? null,
        ];
    }

    protected function findScreenshotUrl(string $slug, string $path): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = $path . DIRECTORY_SEPARATOR . 'screenshot.' . $ext;
            if (is_file($file)) {
                return $this->publishScreenshot($slug, $file, $ext);
            }
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
                if (!$disk->exists($dir)) {
                    $disk->makeDirectory($dir);
                }
                $disk->put($target, file_get_contents($absolute));
            }

            return asset('storage/' . $target);
        } catch (\Throwable $e) {
            return null;
        }
    }
}