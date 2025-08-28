<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Menu::firstOrCreate(['slug' => 'main'], ['name' => 'Main Menu']);

        if ($menu->items()->count() === 0) {
            MenuItem::create(['menu_id' => $menu->id, 'label' => 'Home', 'url' => '/', 'order' => 0]);
            MenuItem::create(['menu_id' => $menu->id, 'label' => 'Admin', 'url' => '/admin', 'order' => 10]);
        }
    }
}