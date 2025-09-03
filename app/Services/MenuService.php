<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class MenuService
{
    /** Find menu by location slug */
    public function findByLocation(string $location): ?Menu
    {
        $loc = MenuLocation::where('slug', $location)->with('menu')->first();
        return $loc?->menu;
    }

    /** Build nested tree for a menu */
    public function tree(Menu $menu): array
    {
        $items = $menu->items()->get()->toArray();
        // index by id
        $byId = [];
        foreach ($items as $it) {
            $it['children'] = [];
            $byId[$it['id']] = $it;
        }
        // assemble
        $roots = [];
        foreach ($byId as $id => &$it) {
            if ($it['parent_id']) {
                if (isset($byId[$it['parent_id']])) {
                    $byId[$it['parent_id']]['children'][] = &$it;
                } else {
                    $roots[] = &$it; // orphan tolerance
                }
            } else {
                $roots[] = &$it;
            }
        }
        // sort children by sort_order already provided from DB
        return $roots;
    }

    /** Render by location with options (ul/li classes, depth, view override) */
    public function render(string $location, array $options = []): string
    {
        $menu = $this->findByLocation($location);
        if (!$menu)
            return '';

        $tree = $this->tree($menu);
        $view = $options['view'] ?? 'components.menu';

        return View::make($view, [
            'items' => $tree,
            'options' => $options,
            'root_class' => $options['ul_class'] ?? 'menu',
            'li_class' => $options['li_class'] ?? 'menu-item',
            'a_class' => $options['a_class'] ?? '',
            'depth' => $options['depth'] ?? 0,
        ])->render();
    }

    /** Save a nested tree (array of nodes with children) to DB */
    public function saveTree(int $menuId, array $nodes): void
    {
        $order = 0;
        $recur = function ($items, $parentId = null) use (&$recur, &$order, $menuId) {
            foreach ($items as $node) {
                $order++;
                MenuItem::where('id', $node['id'])->where('menu_id', $menuId)->update([
                    'parent_id' => $parentId,
                    'sort_order' => $order,
                ]);
                if (!empty($node['children'])) {
                    $recur($node['children'], $node['id']);
                }
            }
        };
        $recur($nodes, null);
    }
}