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
        // Ensure "theme::" always points to the current active theme for this request.
        /** @var ThemeManager $themes */
        $themes = $this->app->make(ThemeManager::class);
        $themes->rebindViewNamespace();

        // Handy in blades: {{ $activeTheme }}
        View::share('activeTheme', $themes->activeSlug());

        // NEW: @setting('general.site_title') directive
        Blade::directive('setting', function ($key) {
            return "<?php echo e(app(\\App\\Support\\Settings\\Settings::class)->get($key)); ?>";
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