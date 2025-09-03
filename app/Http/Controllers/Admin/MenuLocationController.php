<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuLocation;
use Illuminate\Http\Request;

class MenuLocationController extends Controller
{
    public function index()
    {
        $locations = MenuLocation::orderBy('name')->get();
        $menus = Menu::orderBy('name')->get();
        return view('admin.menus.locations', compact('locations', 'menus'));
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'assign' => ['array'],
            'assign.*' => ['nullable', 'integer', 'exists:menus,id']
        ]);

        foreach ($data['assign'] ?? [] as $slug => $menuId) {
            $loc = MenuLocation::where('slug', $slug)->first();
            if ($loc) {
                $loc->menu_id = $menuId ?: null;
                $loc->save();
            }
        }
        return back()->with('success', 'Locations saved.');
    }
}