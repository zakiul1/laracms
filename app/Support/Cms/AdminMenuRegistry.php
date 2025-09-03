<?php

namespace App\Support\Cms;

use Illuminate\Support\Facades\Route;

/**
 * Collects admin sidebar menu items and groups (modules can add here).
 *
 * Item shape:
 * - key: string (unique)
 * - label: string
 * - icon?: string (lucide component name like 'lucide-file-text', or plain text/emoji)
 * - route?: string (route name)
 * - params?: array (route parameters)
 * - url?: string (explicit URL if not using route)
 * - order?: int (lower first)
 * - children?: array<item>
 */
class AdminMenuRegistry
{
    /** @var array<string,array> */
    protected array $items = [];

    public function seedBaseline(): void
    {
        // Minimal baseline so the sidebar always has useful entries.
        $this->add([
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'lucide-home',
            'order' => 1,
        ]);

        // Keep Settings (you didn't ask to remove it)
        $this->add([
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'lucide-settings',
            'order' => 100,
            // keep an explicit URL if you want a hash anchor under dashboard
            'url' => $this->safeRouteUrl('admin.dashboard') . '#settings',
        ]);
    }

    /** Add or merge a top-level item/group */
    public function add(array $item): void
    {
        $item = $this->normalize($item);
        $key = $item['key'];

        if (isset($this->items[$key])) {
            // Merge existing (children merge by key)
            $existing = $this->items[$key];
            $existing['label'] = $item['label'] ?? $existing['label'] ?? $key;
            $existing['icon'] = $item['icon'] ?? $existing['icon'] ?? null;
            $existing['url'] = $item['url'] ?? $existing['url'] ?? null;
            $existing['route'] = $item['route'] ?? $existing['route'] ?? null;
            $existing['params'] = $item['params'] ?? $existing['params'] ?? [];
            $existing['order'] = $item['order'] ?? $existing['order'] ?? 50;

            $existingChildren = $existing['children'] ?? [];
            $incomingChildren = $item['children'] ?? [];
            $existing['children'] = $this->mergeChildren($existingChildren, $incomingChildren);

            $this->items[$key] = $existing;
            return;
        }

        $this->items[$key] = $item;
    }

    /** Convenience: create/merge a top-level group with a fixed key */
    public function group(string $key, array $group): void
    {
        $group['key'] = $key;
        $this->add($group);
    }

    /** Add a child under an existing group key */
    public function addChild(string $groupKey, array $child): void
    {
        $child = $this->normalize($child);

        if (!isset($this->items[$groupKey])) {
            $this->items[$groupKey] = [
                'key' => $groupKey,
                'label' => ucfirst(str_replace(['-', '_'], ' ', $groupKey)),
                'order' => 50,
                'children' => [],
            ];
        }

        $children = $this->items[$groupKey]['children'] ?? [];
        $this->items[$groupKey]['children'] = $this->mergeChildren($children, [$child]);
    }

    /** Remove a top-level item by key */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /** Remove a child from a group by child key */
    public function removeChild(string $groupKey, string $childKey): void
    {
        if (!isset($this->items[$groupKey]['children'])) {
            return;
        }

        $this->items[$groupKey]['children'] = array_values(array_filter(
            $this->items[$groupKey]['children'],
            fn($c) => ($c['key'] ?? null) !== $childKey
        ));
    }

    /** @return array<int,array> sorted list with children sorted and URLs resolved at render time */
    public function list(): array
    {
        $items = array_values($this->items);

        // Resolve URLs *now* so Route::has() sees all registered routes.
        foreach ($items as &$it) {
            // top-level URL resolution
            if (!empty($it['route'])) {
                $it['url'] = $this->safeRouteUrl($it['route'], $it['params'] ?? []);
            }

            // children URL resolution + sort
            if (!empty($it['children'])) {
                foreach ($it['children'] as &$c) {
                    if (!empty($c['route'])) {
                        $c['url'] = $this->safeRouteUrl($c['route'], $c['params'] ?? []);
                    }
                }
                usort($it['children'], fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
                unset($c);
            }
        }
        unset($it);

        // sort top-level
        usort($items, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));

        return $items;
    }

    protected function normalize(array $item): array
    {
        $item['key'] = $item['key'] ?? StrKey::from($item['label'] ?? $item['route'] ?? $item['url'] ?? uniqid('item'));
        $item['label'] = $item['label'] ?? ucfirst($item['key']);
        $item['order'] = $item['order'] ?? 50;

        // IMPORTANT: Do NOT resolve $item['url'] here based on route; routes may not be loaded yet.
        // Keep any explicit 'url' passed by the caller; otherwise leave null and resolve in list().

        $item['children'] = array_map([$this, 'normalize'], $item['children'] ?? []);
        return $item;
    }

    protected function mergeChildren(array $existing, array $incoming): array
    {
        // index by key for stable merge
        $byKey = [];
        foreach ($existing as $c) {
            $byKey[$c['key'] ?? StrKey::from($c['label'] ?? uniqid('c'))] = $c;
        }
        foreach ($incoming as $c) {
            $k = $c['key'] ?? StrKey::from($c['label'] ?? uniqid('c'));
            if (isset($byKey[$k])) {
                // shallow merge; prefer incoming scalar fields
                $byKey[$k] = array_merge($byKey[$k], $c);
            } else {
                $byKey[$k] = $c;
            }
        }
        return array_values($byKey);
    }

    protected function safeRouteUrl(string $name, array $params = []): string
    {
        try {
            return Route::has($name) ? route($name, $params) : '#';
        } catch (\Throwable $e) {
            return '#';
        }
    }
}

/** Small helper for slugging keys without pulling Str in here */
class StrKey
{
    public static function from(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s ?? '', '-') ?: uniqid('k');
    }
}