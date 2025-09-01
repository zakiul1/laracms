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

        // Placeholder sections to keep order space for modules
        $this->add(['key' => 'content', 'label' => 'Content', 'icon' => 'lucide-folder', 'order' => 20, 'children' => []]);
        $this->add(['key' => 'modules', 'label' => 'Modules', 'icon' => 'lucide-puzzle', 'order' => 90, 'url' => $this->safeRouteUrl('admin.dashboard') . '#modules']);
        $this->add(['key' => 'settings', 'label' => 'Settings', 'icon' => 'lucide-settings', 'order' => 100, 'url' => $this->safeRouteUrl('admin.dashboard') . '#settings']);
    }

    /** Add or merge a top-level item/group */
    public function add(array $item): void
    {
        $item = $this->normalize($item);
        $key = $item['key'];
        if (isset($this->items[$key])) {
            // Merge existing (children append)
            $existing = $this->items[$key];
            $existing['label'] = $item['label'] ?? $existing['label'] ?? $key;
            $existing['icon'] = $item['icon'] ?? $existing['icon'] ?? null;
            $existing['url'] = $item['url'] ?? $existing['url'] ?? null;
            $existing['route'] = $item['route'] ?? $existing['route'] ?? null;
            $existing['params'] = $item['params'] ?? $existing['params'] ?? [];
            $existing['order'] = $item['order'] ?? $existing['order'] ?? 50;
            $existingChildren = $existing['children'] ?? [];
            $incomingChildren = $item['children'] ?? [];
            $existing['children'] = array_merge($existingChildren, array_map([$this, 'normalize'], $incomingChildren));
            $this->items[$key] = $existing;
            return;
        }
        $this->items[$key] = $item;
    }

    /** Add a child under an existing group key */
    public function addChild(string $groupKey, array $child): void
    {
        $child = $this->normalize($child);
        if (!isset($this->items[$groupKey])) {
            $this->items[$groupKey] = ['key' => $groupKey, 'label' => ucfirst(str_replace(['-', '_'], ' ', $groupKey)), 'order' => 50, 'children' => []];
        }
        $this->items[$groupKey]['children'][] = $child;
    }

    /** @return array<int,array> sorted list with children sorted */
    public function list(): array
    {
        $items = array_values($this->items);
        // sort children
        foreach ($items as &$it) {
            if (!empty($it['children'])) {
                usort($it['children'], fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
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
        // Resolve URL from route if provided
        if (empty($item['url']) && !empty($item['route'])) {
            $item['url'] = $this->safeRouteUrl($item['route'], $item['params'] ?? []);
        }
        $item['children'] = array_map([$this, 'normalize'], $item['children'] ?? []);
        return $item;
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