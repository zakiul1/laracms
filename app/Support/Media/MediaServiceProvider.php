<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Support\Cms\AdminMenuRegistry;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Optional: if you add config/media.php
        $config = base_path('config/media.php');
        if (is_file($config)) {
            $this->mergeConfigFrom($config, 'media');
        }
    }

    public function boot(): void
    {
        // Routes + views
        $this->mapRoutes();
        $this->loadViewsFrom(resource_path('views/media'), 'media');

        // Admin menu item (same style as your Menu support)
        if (function_exists('add_action')) {
            add_action('admin_menu', function (AdminMenuRegistry $menu) {
                $menu->group('media', [
                    'label' => 'Media',
                    'icon' => 'lucide-images',
                    'order' => 30,
                    'children' => [
                        [
                            'key' => 'media.library',
                            'label' => 'Library',
                            'route' => 'admin.media.index',
                            'order' => 10,
                        ],
                        [
                            'key' => 'media.categories',
                            'label' => 'Categories',
                            'route' => 'admin.media.categories.index',
                            'order' => 20,
                        ],
                    ],
                ]);
            });
        }
    }

    protected function mapRoutes(): void
    {
        $path = base_path('app/Support/Media/routes.php');
        if (is_file($path)) {
            Route::middleware(['web', 'admin'])     // uses your 'admin' alias
                ->prefix('admin/media')
                ->as('admin.media.')
                ->group($path);
        }
    }
}