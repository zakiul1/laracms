<?php

namespace Modules\Media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action('admin_menu', function (\App\Support\Cms\AdminMenuRegistry $menu) {
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