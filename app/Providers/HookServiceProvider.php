<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\Hooks\HookManager;

class HookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Hook manager as a singleton
        $this->app->singleton(HookManager::class, function ($app) {
            $manager = new HookManager();

            // Optionally pre-seed hook names from config
            $hooks = config('laracms.hooks', []);
            foreach ($hooks as $hook) {
                if (method_exists($manager, 'ensure')) {
                    // If you added ensure() in HookManager
                    $manager->ensure($hook);
                }
            }

            return $manager;
        });

        // Alias for easy resolve: app('hooks')
        $this->app->alias(HookManager::class, 'hooks');
    }

    public function boot(): void
    {
        // Here you can register default actions/filters if needed.
        // Example:
        // add_action('admin_head', fn() => logger('Admin head called'));
    }
}