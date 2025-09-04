<?php

namespace App\Support\Cms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Admin sidebar menu registry with final-stage dedupe by resolved URL.
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

        // Seed Posts only if not already added elsewhere
        if (!$this->has('posts') && Route::has('admin.posts.index')) {
            $this->addResource(
                key: 'posts',
                label: 'Posts',
                icon: 'lucide-file-text',
                indexRoute: 'admin.posts.index',
                createRoute: 'admin.posts.create',
                order: 20
            );
        }

        // Seed Pages only if not already added elsewhere
        if (!$this->has('pages') && Route::has('admin.pages.index')) {
            $this->addResource(
                key: 'pages',
                label: 'Pages',
                icon: 'lucide-layout',
                indexRoute: 'admin.pages.index',
                createRoute: 'admin.pages.create',
                order: 21
            );
        }

        // Settings
        $this->add([
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'lucide-settings',
            'order' => 100,
            'url' => $this->safeRouteUrl('admin.dashboard') . '#settings',
        ]);
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function add(array $item): void
    {
        $item = $this->normalize($item);
        $key = $item['key'];

        if (isset($this->items[$key])) {
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

    public function group(string $key, array $group): void
    {
        $group['key'] = $key;
        $this->add($group);
    }

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

    /** Standard resource: "All" + "Add New" */
    public function addResource(
        string $key,
        string $label,
        ?string $icon,
        string $indexRoute,
        ?string $createRoute = null,
        ?int $order = 50
    ): void {
        $children = [
            [
                'key' => $key . '.all',
                'label' => 'All ' . $label,
                'route' => $indexRoute,
                'order' => 10,
            ],
        ];

        if ($createRoute && Route::has($createRoute)) {
            $children[] = [
                'key' => $key . '.create',
                'label' => 'Add New',
                'route' => $createRoute,
                'order' => 20,
            ];
        }

        $this->add([
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'route' => $indexRoute,
            'order' => $order ?? 50,
            'children' => $children,
        ]);
    }

    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    public function removeChild(string $groupKey, string $childKey): void
    {
        if (!isset($this->items[$groupKey]['children']))
            return;

        $this->items[$groupKey]['children'] = array_values(array_filter(
            $this->items[$groupKey]['children'],
            fn($c) => ($c['key'] ?? null) !== $childKey
        ));
    }

    /**
     * Resolve URLs, compute active/expanded, sort, and **final dedupe by URL**.
     *
     * @return array<int,array>
     */
    public function list(): array
    {
        $items = array_values($this->items);
        $current = url()->current();

        foreach ($items as &$it) {
            // resolve top-level url
            if (!empty($it['route'])) {
                $it['url'] = $this->safeRouteUrl($it['route'], $it['params'] ?? []);
            }

            // resolve children urls
            $anyChildActive = false;
            if (!empty($it['children'])) {
                foreach ($it['children'] as &$c) {
                    if (!empty($c['route'])) {
                        $c['url'] = $this->safeRouteUrl($c['route'], $c['params'] ?? []);
                    }
                    $c['active'] = $this->isActive($c['url'] ?? null, $current);
                    if ($c['active'])
                        $anyChildActive = true;
                }
                unset($c);

                // ✅ FINAL DEDUPE after URLs are resolved (route/url don’t matter anymore)
                $it['children'] = $this->dedupeChildrenByResolvedUrl($it['children']);

                // sort
                usort($it['children'], fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
            }

            // active/expanded
            $it['active'] = $this->isActive($it['url'] ?? null, $current) || $anyChildActive;
            $it['expanded'] = $anyChildActive;
        }
        unset($it);

        usort($items, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));

        return $items;
    }

    protected function normalize(array $item): array
    {
        $item['key'] = $item['key'] ?? StrKey::from($item['label'] ?? $item['route'] ?? $item['url'] ?? uniqid('item'));
        $item['label'] = $item['label'] ?? ucfirst($item['key']);
        $item['order'] = $item['order'] ?? 50;

        // Normalize children and ensure default child 'order'
        $children = [];
        foreach (($item['children'] ?? []) as $idx => $child) {
            $c = $this->normalize($child);
            $c['order'] = $c['order'] ?? (($idx + 1) * 10); // 10, 20, ...
            $children[] = $c;
        }
        $item['children'] = $children;

        return $item;
    }

    /**
     * Merge children roughly by key (early stage).
     * Real de-dupe happens in list() after URL resolution.
     */
    protected function mergeChildren(array $existing, array $incoming): array
    {
        $byKey = [];
        foreach ($existing as $c) {
            $key = $c['key'] ?? StrKey::from($c['label'] ?? uniqid('c'));
            $byKey[$key] = $c + ['key' => $key];
        }
        foreach ($incoming as $c) {
            $k = $c['key'] ?? StrKey::from($c['label'] ?? uniqid('c'));
            if (isset($byKey[$k])) {
                $merged = array_merge($byKey[$k], $c);
                $merged['children'] = $this->mergeChildren($byKey[$k]['children'] ?? [], $c['children'] ?? []);
                $byKey[$k] = $merged;
            } else {
                $c['key'] = $k;
                $byKey[$k] = $c;
            }
        }
        return array_values($byKey);
    }

    /** Final dedupe pass: collapse items that resolve to the same URL (or same route if URL is '#'). */
    protected function dedupeChildrenByResolvedUrl(array $children): array
    {
        $norm = function (?string $u): string {
            if (!$u)
                return '';
            $u = preg_replace('/[#?].*$/', '', $u);
            return rtrim($u, '/');
        };

        $map = [];
        foreach ($children as $c) {
            $url = $norm($c['url'] ?? '');
            $sig = $url ?: ('route:' . ($c['route'] ?? '') . '|' . json_encode($c['params'] ?? []));
            if (!isset($map[$sig])) {
                $map[$sig] = $c;
            } else {
                // If both exist, prefer the one that has a real URL over '#'
                $keep = $map[$sig];
                $curr = $c;
                $keepUrl = $norm($keep['url'] ?? '');
                $currUrl = $norm($curr['url'] ?? '');
                if ($currUrl && (!$keepUrl || $keepUrl === '#')) {
                    $map[$sig] = $curr;
                }
            }
        }
        return array_values($map);
    }

    protected function safeRouteUrl(string $name, array $params = []): string
    {
        try {
            return Route::has($name) ? route($name, $params) : '#';
        } catch (\Throwable $e) {
            return '#';
        }
    }

    protected function isActive(?string $url, string $current): bool
    {
        if (!$url)
            return false;
        $normalize = function (string $u): string {
            $u = preg_replace('/[#?].*$/', '', $u);
            return rtrim($u, '/');
        };
        return $normalize($url) === $normalize($current);
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