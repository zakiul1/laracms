<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('name')->get();
        $locations = MenuLocation::orderBy('name')->get();
        return view('admin.menus.index', compact('menus', 'locations'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('menus', 'slug')],
            'description' => ['nullable', 'string'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $menu = Menu::create($data);
        return redirect()->route('admin.menus.edit', $menu)->with('success', 'Menu created.');
    }

    public function edit(Menu $menu)
    {
        // Preload datasets for "Add items" panels
        $pages = \App\Models\Post::query()->where('type', 'page')->orderByDesc('id')->limit(50)->get(['id', 'title', 'slug']);
        $posts = \App\Models\Post::query()->whereIn('type', ['post', 'blog', 'news', 'product'])->orderByDesc('id')->limit(50)->get(['id', 'title', 'slug', 'type']);
        $categories = \App\Models\TermTaxonomy::query()
            ->with('term')
            ->where('taxonomy', 'category')
            ->orderBy('id', 'desc')
            ->limit(50)->get();

        $locations = \App\Models\MenuLocation::orderBy('name')->get();
        return view('admin.menus.edit', compact('menu', 'pages', 'posts', 'categories', 'locations'));
    }

    public function update(Menu $menu, Request $r)
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('menus', 'slug')->ignore($menu->id)],
            'description' => ['nullable', 'string'],
        ]);
        $menu->update($data);
        return back()->with('success', 'Menu updated.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted.');
    }

    /** Assign this menu to selected locations */
    public function assignLocations(Menu $menu, Request $r)
    {
        $slugs = $r->input('locations', []); // array of slugs
        \App\Models\MenuLocation::query()->whereIn('slug', $slugs)->update(['menu_id' => $menu->id]);
        \App\Models\MenuLocation::query()->whereNotIn('slug', $slugs)->where('menu_id', $menu->id)->update(['menu_id' => null]);

        return back()->with('success', 'Locations updated.');
    }

    /** Save drag-drop hierarchy JSON */
    public function reorder(Menu $menu, Request $r, \App\Services\MenuService $svc)
    {
        $data = $r->validate(['tree' => ['required', 'array']]);
        $svc->saveTree($menu->id, $data['tree']);
        return response()->json(['ok' => true]);
    }
}