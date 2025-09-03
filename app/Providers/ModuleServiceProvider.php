<?php

namespace App\Providers;

use App\Support\Modules\ModuleLoader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleLoader::class, fn() => new ModuleLoader());
    }

    public function boot(ModuleLoader $loader): void
    {
        // 1) Always boot protected (first-party) modules without touching DB
        $this->bootProtectedModules();

        // 2) Only boot DB-active modules if the table exists (fixes migrate error)
        if ($this->dbReady()) {
            // keep your existing behavior
            $loader->bootActive();
        }
    }

    /**
     * Force-boot "protected" first-party modules by slug, without DB access.
     */
    protected function bootProtectedModules(): void
    {
        $base = rtrim(config('laracms.modules_path', base_path('modules')), DIRECTORY_SEPARATOR);

        foreach ((array) config('laracms.autoload_modules', []) as $slug) {
            $dir = $base . DIRECTORY_SEPARATOR . $slug;
            if (!File::isDirectory($dir)) {
                continue;
            }

            $meta = $this->readManifest($dir);
            $provider = $meta['provider'] ?? $this->guessProvider($slug);

            if ($provider && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Safe check to avoid DB calls before tables exist (e.g., during first migrate).
     */
    protected function dbReady(): bool
    {
        try {
            return Schema::hasTable('modules');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function guessProvider(string $slug): string
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
        return "\\Modules\\{$studly}\\{$studly}ServiceProvider";
    }

    protected function readManifest(string $dir): array
    {
        $json = $dir . '/module.json';
        if (is_file($json)) {
            return json_decode((string) file_get_contents($json), true) ?: [];
        }
        return [];
    }
}