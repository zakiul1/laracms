<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\Cms\AdminMenuRegistry;

class MenusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // no bindings needed
    }

    public function boot(): void
    {
        // Add “Menus” into the admin sidebar
        add_action('admin_menu', function (AdminMenuRegistry $menu) {
            $menu->group('menus', [
                'label' => 'Menus',
                'icon' => 'lucide-list-tree',
                'order' => 25,
                'children' => [
                    ['key' => 'menus.all', 'label' => 'All Menus', 'route' => 'admin.menus.index', 'order' => 10],
                    ['key' => 'menus.locations', 'label' => 'Locations', 'route' => 'admin.menus.locations.index', 'order' => 20],
                ],
            ]);
        });
    }
}