<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            // Only fetch once per request
            static $shared = null;
            if ($shared === null) {
                $shared = [
                    'activeTheme' => config('laracms.active_theme', 'laracms'),
                    'settings' => SiteSetting::first(),
                    'mainMenu' => Menu::with('items.children')->where('slug', 'main')->first(),
                ];
            }
            $view->with($shared);
        });
    }
}