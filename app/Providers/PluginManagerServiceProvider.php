<?php

namespace App\Providers;

use App\Services\PluginLoader;
use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginLoader::class, fn() => new PluginLoader());
    }

    public function boot(): void
    {
        // Admin menu
        add_action('admin_menu', function (\App\Support\Cms\AdminMenuRegistry $menu) {
            $menu->group('plugins', [
                'label' => 'Plugins',
                'icon' => 'lucide-plug',
                'order' => 40,
                'children' => [
                    ['key' => 'plugins.all', 'label' => 'All Plugins', 'route' => 'admin.plugins.index', 'order' => 10],
                    ['key' => 'plugins.upload', 'label' => 'Upload', 'route' => 'admin.plugins.upload.form', 'order' => 20],
                ],
            ]);
        });
    }
}