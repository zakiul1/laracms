<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function storeCustom(Menu $menu, Request $r)
    {
        $data = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'target' => ['nullable', Rule::in(['_self', '_blank'])],
            'icon' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $data['menu_id'] = $menu->id;
        $data['type'] = 'custom';
        $data['sort_order'] = ($menu->items()->max('sort_order') ?? 0) + 1;
        MenuItem::create($data);
        return back()->with('success', 'Custom link added.');
    }

    /** Bulk add pages/posts/categories by IDs */
    public function storeBulk(Menu $menu, Request $r)
    {
        $data = $r->validate([
            'type' => ['required', Rule::in(['page', 'post', 'category'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $target = $data['type'];
        $next = ($menu->items()->max('sort_order') ?? 0) + 1;

        foreach ($data['ids'] as $id) {
            [$title, $url] = $this->resolveTitleUrl($target, $id);
            MenuItem::create([
                'menu_id' => $menu->id,
                'parent_id' => $data['parent_id'] ?? null,
                'title' => $title,
                'url' => $url,
                'type' => $target,
                'type_id' => $id,
                'target' => '_self',
                'sort_order' => $next++,
            ]);
        }
        return back()->with('success', ucfirst($target) . ' items added.');
    }

    public function update(Menu $menu, MenuItem $item, Request $r)
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $data = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:1024'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
        $item->update($data);
        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu, MenuItem $item)
    {
        abort_unless($item->menu_id === $menu->id, 404);
        // Delete children cascade (DB doesn’t know); do it manually
        $this->deleteWithChildren($item);
        return back()->with('success', 'Menu item deleted.');
    }

    private function deleteWithChildren(MenuItem $item): void
    {
        foreach ($item->children as $c)
            $this->deleteWithChildren($c);
        $item->delete();
    }

    private function resolveTitleUrl(string $type, int $id): array
    {
        if ($type === 'page' || $type === 'post') {
            $p = \App\Models\Post::find($id);
            if (!$p)
                return ['(missing post)', '#'];
            // Adjust your frontend route here if needed:
            $url = url('/' . $p->slug);
            return [$p->title, $url];
        }
        if ($type === 'category') {
            $tt = \App\Models\TermTaxonomy::with('term')->find($id);
            if (!$tt)
                return ['(missing category)', '#'];
            $slug = $tt->term?->slug ?? 'category';
            $url = url('/category/' . $slug);
            return [$tt->term?->name ?? 'Category', $url];
        }
        return ['(unknown)', '#'];
    }
}