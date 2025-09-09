<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

use App\Support\ViteManifestLoader;
use App\Support\AssetPublisher;
use App\Support\Appearance\ThemeManager;

// NEW: settings
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsPageRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Load helpers early (idempotent via function_exists guards).
        $helpers = app_path('helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }

        // Core singletons
        $this->app->singleton(ViteManifestLoader::class, fn() => new ViteManifestLoader());
        $this->app->singleton(AssetPublisher::class, fn() => new AssetPublisher());

        // Theme manager as a singleton so everything (helpers/controllers) share it.
        $this->app->singleton(ThemeManager::class, fn() => new ThemeManager());

        // NEW: Settings system singletons
        $this->app->singleton(Settings::class, fn() => new Settings());
        $this->app->singleton(SettingsPageRegistry::class, fn() => new SettingsPageRegistry());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // --- Theme namespace binding (SAFE before migrations) ---
        try {
            /** @var ThemeManager $themes */
            $themes = $this->app->make(ThemeManager::class);

            // ThemeManager::activeSlug() is table-safe (we updated it).
            $themes->rebindViewNamespace();

            // Share slug + rich info (safe if table not ready -> slug '')
            View::share('activeTheme', $themes->activeSlug());
            try {
                View::share('activeThemeInfo', $themes->active()); // ['name','slug','version','author','paths','metadata','screenshot'] or null
            } catch (\Throwable $ignored) {
                View::share('activeThemeInfo', null);
            }
        } catch (\Throwable $e) {
            // During early boot (composer scripts / pre-migration), fall back cleanly
            View::share('activeTheme', '');
            View::share('activeThemeInfo', null);
            try {
                View::replaceNamespace('theme', [resource_path('views/theme-fallback')]);
                app('view.finder')->flush();
            } catch (\Throwable $ignored) {
            }
        }

        // NEW: @setting('general.site_title') directive
        Blade::directive('setting', function ($key) {
            return "<?php echo e(app(\\App\\Support\\Settings\\Settings::class)->get($key)); ?>";
        });

        // NEW: @themeCssVars -> inject saved Customizer CSS variables (safe if Customizer missing)
        Blade::directive('themeCssVars', function () {
            // Use a plain string to avoid heredoc parse issues
            return
                "<?php try { echo app(\\App\\Support\\Appearance\\Customizer::class)->renderCssVars(); } catch (\\Throwable \$e) { } ?>";
        });

        // OPTIONAL: apply locale/timezone from settings early (safe-guarded)
        try {
            /** @var Settings $settings */
            $settings = app(Settings::class);
            $tz = $settings->get('general.timezone', config('app.timezone', 'UTC'));
            if (is_string($tz) && $tz !== '') {
                config(['app.timezone' => $tz]);
                @date_default_timezone_set($tz);
            }
            $loc = $settings->get('general.language', config('app.locale', 'en'));
            if (is_string($loc) && $loc !== '') {
                config(['app.locale' => $loc]);
                app()->setLocale($loc);
            }
        } catch (\Throwable $e) {
            // swallow if settings table not migrated yet
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ModuleActivate::class,
                \App\Console\Commands\ModuleDeactivate::class,
                \App\Console\Commands\ModulePublish::class,
                // add more console commands here if needed
            ]);
        }
    }
}