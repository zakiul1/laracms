<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LaracmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phase 2/3 will bind Hook/Enqueue and loaders here.
    }

    public function boot(): void
    {
        // Load active theme view namespace (theme::)
        $theme = config('laracms.active_theme');
        $path = config('laracms.themes_path') . "/{$theme}/views";
        if (is_dir($path)) {
            $this->loadViewsFrom($path, 'theme');
        }
    }
}