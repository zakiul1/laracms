<?php

namespace App\Support\Shortcode;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class ShortcodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('shortcode', fn() => new ShortcodeManager());

        // Optional: config publishable if you want. For now we'll use defaults.
    }

    public function boot(): void
    {
        // Blade directive: @shortcodes($html)
        Blade::directive('shortcodes', function ($expr) {
            return "<?php echo app('shortcode')->compile($expr); ?>";
        });

        // Load default shortcodes
        \App\Shortcodes\Defaults::register(app('shortcode'));
    }
}