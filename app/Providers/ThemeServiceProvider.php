<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\SiteSetting;
use App\Support\Appearance\ThemeManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(ThemeManager $themes): void
    {
        // --- Per-request theme selection (supports preview for logged-in users) ---
        $preview = '';
        try {
            $preview = trim((string) request()->query('__theme', ''));
        } catch (\Throwable $e) {
            $preview = '';
        }

        $overrideSlug = ($preview !== '' && Auth::check()) ? $preview : null;

        // Rebind the "theme::" namespace to the active (or preview) theme and flush the finder.
        $themes->rebindViewNamespace($overrideSlug);

        // Actual slug being used this request
        $currentSlug = $overrideSlug ?? $themes->activeSlug();

        // --- Shared data (defensive: DB might not exist yet) ---
        $settings = null;
        $mainMenu = $headerMenu = $footerMenu = null;

        try {
            $settings = SiteSetting::query()->first();
        } catch (\Throwable $e) {
            // ignore when tables are not migrated
        }

        try {
            $mainMenu = Menu::with('items.children')->where('slug', 'main')->first();
            $headerMenu = Menu::with('items.children')->where('slug', 'header')->first();
            $footerMenu = Menu::with('items.children')->where('slug', 'footer')->first();
        } catch (\Throwable $e) {
            // ignore when tables are not migrated
        }

        // Theme asset helper closure (public path configurable via appearance.public_themes_path)
        $publicBase = trim(config('appearance.public_themes_path', 'themes'), '/');
        $themeAsset = function (?string $path = '') use ($currentSlug, $publicBase): string {
            $base = rtrim(asset("{$publicBase}/{$currentSlug}"), '/');
            $p = ltrim((string) $path, '/');
            return $p ? "{$base}/{$p}" : $base;
        };

        // Share once per request
        View::share([
            'activeTheme' => $currentSlug,
            'isThemePreview' => $overrideSlug !== null,
            'settings' => $settings,
            'mainMenu' => $mainMenu,
            'headerMenu' => $headerMenu,
            'footerMenu' => $footerMenu,
            'themeAsset' => $themeAsset,
        ]);

        /**
         * IMPORTANT:
         * - Use the "theme::" namespace in your blades:
         *     @extends('theme::layout')
         *     @include('theme::partials.header')
         * - In controllers:
         *     return view('theme::home');
         *   (or use the helper: return view(theme_view('home'));)
         *
         * We intentionally DO NOT prepend arbitrary theme paths to the global finder here,
         * because that makes switching unreliable. The dynamic namespace keeps it deterministic.
         */
    }
}