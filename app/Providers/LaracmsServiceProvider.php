<?php


namespace App\Providers;


use Illuminate\Support\ServiceProvider;


class LaracmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }


    public function boot(): void
    {
        $theme = config('laracms.active_theme', 'laracms');


        // 1) Real themes dir (Option A)
        $real = rtrim(config('laracms.themes_path'), DIRECTORY_SEPARATOR) . "/{$theme}/views";
        if (is_dir($real))
            $this->loadViewsFrom($real, 'theme');


        // 2) Fallback to your current structure: resources/views/themes/<slug>
        $fallback = resource_path("views/themes/{$theme}");
        if (is_dir($fallback))
            $this->loadViewsFrom($fallback, 'themes.' . $theme);
    }
}