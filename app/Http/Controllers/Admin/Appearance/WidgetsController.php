<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\WidgetArea;
use App\Models\Widget;
use App\Support\Theme;
use Illuminate\Http\Request;

class WidgetsController extends Controller
{
    public function index()
    {
        return view('admin.appearance.widgets.index', [
            'areas' => WidgetArea::where(function ($q) {
                $q->whereNull('theme')->orWhere('theme', Theme::activeSlug());
            })->with('widgets')->get(),
            'activeTheme' => Theme::activeSlug(),
        ]);
    }

    public function storeArea(Request $r)
    {
        $r->validate(['name' => 'required', 'slug' => 'required']);
        WidgetArea::create([
            'name' => $r->name,
            'slug' => $r->slug,
            'description' => $r->input('description'),
            'theme' => Theme::activeSlug(),
        ]);
        return back()->with('success', 'Area created.');
    }

    public function destroyArea(WidgetArea $area)
    {
        $area->delete();
        return back()->with('success', 'Area deleted.');
    }

    public function store(Request $r)
    {
        $r->validate([
            'widget_area_id' => 'required|exists:widget_areas,id',
            'type' => 'required'
        ]);
        $pos = (int) Widget::where('widget_area_id', $r->widget_area_id)->max('position') + 1;
        Widget::create([
            'widget_area_id' => $r->widget_area_id,
            'type' => $r->type,
            'title' => $r->input('title'),
            'settings' => $r->input('settings', []),
            'position' => $pos,
        ]);
        return back()->with('success', 'Widget added.');
    }

    public function update(Request $r, Widget $widget)
    {
        $widget->update([
            'title' => $r->input('title'),
            'settings' => $r->input('settings', [])
        ]);
        return back()->with('success', 'Widget saved.');
    }

    public function destroy(Widget $widget)
    {
        $widget->delete();
        return back()->with('success', 'Widget removed.');
    }

    public function reorder(Request $r)
    {
        // expects: items = [{id, position}, ...]
        foreach ((array) $r->input('items', []) as $row) {
            if (!empty($row['id'])) {
                Widget::where('id', $row['id'])->update(['position' => (int) $row['position']]);
            }
        }
        return response()->json(['ok' => true]);
    }
}