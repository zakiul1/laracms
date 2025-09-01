<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MediaModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (function_exists('add_action')) {
            add_action('admin_menu', function ($menu) {
                // $menu is \App\Support\Cms\AdminMenuRegistry
                $menu->add([
                    'label' => 'Media',
                    'icon' => 'lucide-images',
                    'children' => [
                        [
                            'label' => 'Library',
                            'icon' => 'lucide-images',
                            'url' => route('admin.media.index'),
                        ],
                        [
                            'label' => 'Categories',
                            'icon' => 'lucide-folder-tree',
                            'url' => route('admin.media.categories.index'),
                        ],
                    ],
                ]);
            });
        }
    }
}