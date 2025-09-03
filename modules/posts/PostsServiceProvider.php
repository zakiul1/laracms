<?php

namespace Modules\Posts;

use Illuminate\Support\ServiceProvider;

class PostsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Inject WP-like Posts group (routes already exist in routes/web.php)
        if (function_exists('add_action')) {
            add_action('admin_menu', function ($menu) {
                $menu->add([
                    'key' => 'posts',
                    'label' => 'Posts',
                    'icon' => 'lucide-file-text',
                    'order' => 20,
                    'children' => [
                        [
                            'label' => 'All Posts',
                            'icon' => 'lucide-list',
                            'url' => route('admin.posts.index'),
                        ],
                        [
                            'label' => 'Add New',
                            'icon' => 'lucide-plus',
                            'url' => route('admin.posts.create'),
                        ],
                        [
                            'label' => 'Categories',
                            'icon' => 'lucide-folder-tree',
                            'url' => route('admin.categories.index'),
                        ],
                    ],
                ]);
            });
        }
    }
}