<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\ViteManifestLoader;
use App\Support\AssetPublisher;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure helper functions (do_action, add_action, etc.) are loaded early.
        // Safe to include repeatedly because helpers use function_exists guards.
        $helpers = app_path('helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }

        // Singletons you already had
        $this->app->singleton(ViteManifestLoader::class, fn() => new ViteManifestLoader());
        $this->app->singleton(AssetPublisher::class, fn() => new AssetPublisher());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ModuleActivate::class,
                \App\Console\Commands\ModuleDeactivate::class,
                \App\Console\Commands\ModulePublish::class,
                // include create scaffolder if you added it
            ]);
        }
    }
}