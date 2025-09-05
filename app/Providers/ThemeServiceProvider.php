<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            static $shared = null;

            if ($shared === null) {
                // --- resolve active slug: DB → file → config ---
                $dbActive = null;
                try {
                    $dbActive = DB::table('themes')->where('status', 'active')->value('slug');
                } catch (\Throwable $e) {
                }
                $fileActive = null;
                $file = storage_path('app/appearance_active_theme.txt');
                if (is_file($file))
                    $fileActive = trim((string) @file_get_contents($file));
                $configured = (string) config('laracms.active_theme', 'laracms');
                $baseSlug = $dbActive ?: ($fileActive ?: $configured);

                // preview override for logged-in users
                $preview = trim((string) request()->query('__theme', ''));
                $activeSlug = ($preview !== '' && Auth::check()) ? $preview : $baseSlug;

                // --- find the REAL directory for that slug ---
                $roots = [
                    resource_path('themes'),
                    base_path('themes'),
                    base_path('Modules/Appearance/Themes'),
                    resource_path('views/themes'),
                ];
                $realDirs = [];
                foreach ($roots as $root) {
                    if (!is_dir($root))
                        continue;
                    foreach (scandir($root) ?: [] as $entry) {
                        if ($entry === '.' || $entry === '..')
                            continue;
                        $dir = $root . DIRECTORY_SEPARATOR . $entry;
                        if (!is_dir($dir))
                            continue;
                        if (Str::slug($entry) === Str::slug($activeSlug)) {
                            // use {dir}/views if present, else dir itself
                            $v = is_dir($dir . DIRECTORY_SEPARATOR . 'views')
                                ? $dir . DIRECTORY_SEPARATOR . 'views'
                                : $dir;
                            $realDirs[] = $v;
                        }
                    }
                }

                if ($realDirs) {
                    // give theme paths highest priority for ALL view lookups
                    foreach (array_reverse($realDirs) as $p) {
                        View::getFinder()->prependLocation($p);
                    }
                    View::addNamespace('theme', $realDirs);
                }

                // shared data (safe if tables don’t exist yet)
                $settings = null;
                $mainMenu = $headerMenu = $footerMenu = null;
                try {
                    $settings = SiteSetting::query()->first();
                } catch (\Throwable $e) {
                }
                try {
                    $mainMenu = Menu::with('items.children')->where('slug', 'main')->first();
                    $headerMenu = Menu::with('items.children')->where('slug', 'header')->first();
                    $footerMenu = Menu::with('items.children')->where('slug', 'footer')->first();
                } catch (\Throwable $e) {
                }

                $themeAsset = function (?string $path = '') use ($activeSlug): string {
                    $base = rtrim(asset("themes/{$activeSlug}"), '/');
                    $p = ltrim((string) $path, '/');
                    return $p ? "{$base}/{$p}" : $base;
                };

                $shared = [
                    'activeTheme' => $activeSlug,
                    'isThemePreview' => ($activeSlug !== $baseSlug),
                    'settings' => $settings,
                    'mainMenu' => $mainMenu,
                    'headerMenu' => $headerMenu,
                    'footerMenu' => $footerMenu,
                    'themeAsset' => $themeAsset,
                ];
            }

            $view->with($shared);
        });
    }
}