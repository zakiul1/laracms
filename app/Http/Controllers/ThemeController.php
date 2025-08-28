<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\SiteSetting;

class ThemeController extends Controller
{
    public function home()
    {
        $settings = SiteSetting::first();
        $mainMenu = Menu::with('items.children')->where('slug', 'main')->first();

        return view("themes." . config('laracms.active_theme') . ".home", compact('settings', 'mainMenu'));
    }
}