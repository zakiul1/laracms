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

    /**
     * Global save handler for the Locations screen (mapping style):
     * accepts `assign[slug] = menu_id`  OR  `locations[slug] = menu_id`.
     */
    public function save(Request $request)
    {
        // Accept either "assign" or "locations" maps.
        $input = $request->input('assign');
        if (!is_array($input) || empty($input)) {
            $input = $request->input('locations', []);
        }
        if (!is_array($input)) {
            $input = [];
        }

        $allowedSlugs = MenuLocation::pluck('slug')->all();
        $validMenuIds = Menu::pluck('id')->all();
        $filtered = [];

        foreach ($input as $slug => $menuId) {
            if (!in_array($slug, $allowedSlugs, true))
                continue;

            $menuId = ($menuId === '' || $menuId === null) ? null : (int) $menuId;
            if (!is_null($menuId) && !in_array($menuId, $validMenuIds, true))
                continue;

            $filtered[$slug] = $menuId;
        }

        foreach ($filtered as $slug => $menuId) {
            MenuLocation::where('slug', $slug)->update(['menu_id' => $menuId]);
        }

        return back()->with('success', 'Locations saved.');
    }

    /**
     * Per-menu assignment (used on the Edit Menu screen).
     * Accepts checkbox booleans:
     *   - assign[header] = 1 (checked) means assign CURRENT menu to "header"
     *   - unchecked means unassign from CURRENT menu for that slug.
     *
     * Requires `menu_id` hidden input.
     *
     * Also supports array form: locations[] = ['header','footer'].
     */
    public function assign(Request $request)
    {
        $menuId = (int) $request->input('menu_id');
        $menu = Menu::find($menuId);
        if (!$menu) {
            return back()->with('error', 'Invalid menu.');
        }

        $allowedSlugs = MenuLocation::pluck('slug')->all();

        // Support two shapes:
        // A) assign[slug] = 1/on/true
        $assignMap = $request->input('assign', []);
        // B) locations[] = ['header','footer']
        $locationsList = $request->input('locations', []);

        $selectedSlugs = [];

        if (is_array($assignMap) && !empty($assignMap)) {
            foreach ($assignMap as $slug => $val) {
                $truthy = in_array($val, [1, '1', true, 'true', 'on'], true);
                if ($truthy)
                    $selectedSlugs[] = $slug;
            }
        } elseif (is_array($locationsList) && !empty($locationsList)) {
            $selectedSlugs = array_values(array_filter(
                $locationsList,
                fn($slug) => in_array($slug, $allowedSlugs, true)
            ));
        }

        // Keep only valid slugs
        $selectedSlugs = array_values(array_intersect($selectedSlugs, $allowedSlugs));

        // Assign current menu to checked slugs, unassign for unchecked ones *only if currently set to this menu*.
        foreach ($allowedSlugs as $slug) {
            if (in_array($slug, $selectedSlugs, true)) {
                MenuLocation::where('slug', $slug)->update(['menu_id' => $menuId]);
            } else {
                MenuLocation::where('slug', $slug)
                    ->where('menu_id', $menuId)
                    ->update(['menu_id' => null]);
            }
        }

        return back()->with('success', 'Locations saved.');
    }

    /** Back-compat alias if anything posts to /locations/update */
    public function update(Request $request)
    {
        return $this->save($request);
    }
}