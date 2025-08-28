<?php

namespace App\Providers;

use App\Support\Modules\ModuleLoader;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleLoader::class, fn() => new ModuleLoader());
    }

    public function boot(ModuleLoader $loader): void
    {
        $loader->bootActive();
    }
}