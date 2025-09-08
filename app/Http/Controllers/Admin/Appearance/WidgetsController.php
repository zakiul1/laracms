<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\WidgetArea;
use App\Models\Widget;
use App\Support\Appearance\ThemeManager;
use App\Support\Appearance\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
class WidgetsController extends Controller
{
    public function index(ThemeManager $themes, WidgetRegistry $registry)
    {
        $active = $themes->activeSlug();

        $areas = WidgetArea::where(function ($q) use ($active) {
            $q->whereNull('theme');
            if ($active !== '') {
                $q->orWhere('theme', $active);
            }
        })
            ->with(['widgets' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('slug')
            ->get();

        return view('admin.appearance.widgets.index', [
            'areas' => $areas,
            'activeTheme' => $active ?: null,
            'registry' => $registry->all(),
        ]);
    }

    // ----- Areas -----
    public function storeArea(Request $r, ThemeManager $themes)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140',
            'description' => 'nullable|string|max:255',
        ]);

        // Compute base slug then ensure uniqueness (slug, slug-2, slug-3, ...)
        $base = Str::slug($data['slug'] ?: $data['name']);
        $slug = $this->uniqueAreaSlug($base);

        $area = WidgetArea::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'theme' => $themes->activeSlug() ?: null,
        ]);

        return back()->with('success', "Area “{$area->name}” created.");
    }

    public function updateArea(Request $r, WidgetArea $area)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:140',
        ]);

        if (!empty($data['slug'])) {
            $base = Str::slug($data['slug']);
            // Only re-slug if it changed
            if ($base !== $area->slug) {
                $data['slug'] = $this->uniqueAreaSlug($base, $area->id);
            }
        } else {
            unset($data['slug']);
        }

        $area->update($data);

        return back()->with('success', 'Area updated.');
    }

    public function destroyArea(WidgetArea $area)
    {
        // Ensure widgets are removed as well (in case FK isn't ON DELETE CASCADE)
        $area->widgets()->delete();
        $area->delete();

        return back()->with('success', 'Area deleted.');
    }

    // ----- Widgets -----
    public function store(Request $r, WidgetRegistry $registry)
    {
        $data = $r->validate([
            'widget_area_id' => 'required|exists:widget_areas,id',
            'type' => 'required|string',
            'title' => 'nullable|string|max:160',
            'settings' => 'array',
        ]);

        if (!$registry->get($data['type'])) {
            return response()->json(['ok' => false, 'error' => 'Unknown widget type.'], 422);
        }

        $order = (int) (Widget::where('widget_area_id', $data['widget_area_id'])->max('sort_order') ?? 0) + 10;

        $w = Widget::create([
            'widget_area_id' => (int) $data['widget_area_id'],
            'type' => $data['type'],
            'title' => $data['title'] ?? null,
            'settings' => $data['settings'] ?? [],
            'sort_order' => $order,
            'status' => 'active',
        ]);

        return response()->json(['ok' => true, 'id' => $w->id]);
    }

    public function update(Request $r, Widget $widget)
    {
        $data = $r->validate([
            'title' => 'nullable|string|max:160',
            'settings' => 'array',
            'visibility' => 'array',
            'status' => 'sometimes|in:active,inactive',
            'widget_area_id' => 'sometimes|exists:widget_areas,id', // move between areas
            'sort_order' => 'sometimes|integer',
        ]);

        // If moved to a new area and no explicit position, append at end
        if (isset($data['widget_area_id']) && (int) $data['widget_area_id'] !== (int) $widget->widget_area_id) {
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = (int) (Widget::where('widget_area_id', $data['widget_area_id'])->max('sort_order') ?? 0) + 10;
            }
        }

        $widget->update($data);

        return response()->json(['ok' => true]);
    }

    public function toggle(Widget $widget)
    {
        $widget->status = $widget->status === 'active' ? 'inactive' : 'active';
        $widget->save();

        return response()->json(['ok' => true, 'status' => $widget->status]);
    }

    public function clone(Widget $widget)
    {
        $dup = $widget->duplicate();

        return response()->json(['ok' => true, 'id' => $dup->id]);
    }

    public function destroy(Widget $widget)
    {
        $widget->delete();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $r)
    {
        $data = $r->validate([
            'area_id' => 'required|exists:widget_areas,id',
            'order' => 'required|array',
        ]);

        $i = 10;
        foreach ($data['order'] as $id) {
            Widget::where('id', (int) $id)
                ->where('widget_area_id', (int) $data['area_id'])
                ->update(['sort_order' => $i]);
            $i += 10;
        }

        return response()->json(['ok' => true]);
    }

    // ----- Live preview (no save) -----
    public function preview(Request $r, WidgetRegistry $registry)
    {
        $data = $r->validate([
            'type' => 'required|string',
            'title' => 'nullable|string',
            'settings' => 'array',
        ]);

        $html = $registry->render($data['type'], $data['settings'] ?? [], $data['title'] ?? null);

        return response()->json(['ok' => true, 'html' => $html]);
    }

    // ----- Export / Import -----
    public function export(ThemeManager $themes)
    {
        $active = $themes->activeSlug();

        $areas = WidgetArea::with(['widgets' => fn($q) => $q->orderBy('sort_order')])
            ->where(function ($q) use ($active) {
                $q->whereNull('theme');
                if ($active !== '') {
                    $q->orWhere('theme', $active);
                }
            })->get();

        return response()->json([
            'theme' => $active ?: null,
            'exported_at' => now()->toIso8601String(),
            'areas' => $areas->toArray(),
        ]);
    }

    public function import(Request $r, ThemeManager $themes)
    {
        $data = $r->validate(['json' => 'required']);
        $payload = json_decode($data['json'], true);

        if (!is_array($payload) || empty($payload['areas'])) {
            return back()->with('error', 'Invalid import JSON.');
        }

        $targetTheme = $themes->activeSlug() ?: null;

        foreach ($payload['areas'] as $areaData) {
            // Prefer a stable slug from JSON; if missing, derive it ONCE from name.
            $incomingSlug = Str::slug($areaData['slug'] ?? ($areaData['name'] ?? 'area'));

            // Find or create by slug (idempotent). If creating new, ensure uniqueness.
            $area = WidgetArea::where('slug', $incomingSlug)->first();
            if (!$area) {
                $area = WidgetArea::create([
                    'slug' => $this->uniqueAreaSlug($incomingSlug),
                    'name' => $areaData['name'] ?? $incomingSlug,
                    'description' => $areaData['description'] ?? null,
                    'theme' => $targetTheme,
                ]);
            } else {
                $area->update([
                    'name' => $areaData['name'] ?? $area->name,
                    'description' => $areaData['description'] ?? $area->description,
                    'theme' => $targetTheme,
                ]);
            }

            // Replace widgets (avoid duplicates on repeated imports)
            $area->widgets()->delete();

            $order = 10;
            foreach ($areaData['widgets'] ?? [] as $w) {
                Widget::create([
                    'widget_area_id' => $area->id,
                    'type' => $w['type'],
                    'title' => $w['title'] ?? null,
                    'settings' => $w['settings'] ?? [],
                    'sort_order' => $order,
                    'status' => in_array(($w['status'] ?? 'active'), ['active', 'inactive']) ? $w['status'] : 'active',
                    'visibility' => $w['visibility'] ?? null,
                ]);
                $order += 10;
            }
        }

        return back()->with('success', 'Widgets imported.');
    }

    // ---------- helpers ----------

    /**
     * Make a slug unique by appending -2, -3, ... if needed.
     */
    protected function uniqueAreaSlug(string $base, ?int $ignoreId = null): string
    {
        $base = $base !== '' ? $base : 'area';
        $slug = $base;
        $i = 1;
        while (
            WidgetArea::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $i++;
            $slug = "{$base}-{$i}";
            if ($i > 200) { // safety valve
                $slug = $base . '-' . uniqid();
                break;
            }
        }
        return $slug;
    }


    public function list(Request $r, WidgetArea $area)
    {
        $per = max(5, min(100, (int) $r->query('per', 20)));
        $page = max(1, (int) $r->query('page', 1));

        $query = Widget::where('widget_area_id', $area->id)->orderBy('sort_order');
        $total = (clone $query)->count();
        $widgets = $query->forPage($page, $per)->get();

        $html = view('admin.appearance.widgets.partials.items', compact('widgets'))->render();

        $hasMore = ($page * $per) < $total;

        return response()->json([
            'ok' => true,
            'html' => $html,
            'next_page' => $page + 1,
            'has_more' => $hasMore,
        ]);
    }
}