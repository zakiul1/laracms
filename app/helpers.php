<?php

use App\Support\Cms\PostTypeRegistry;
use App\Support\Cms\TaxonomyRegistry;
use App\Support\Cms\TermsRegistry;
use App\Support\Cms\AdminMenuRegistry;
use App\Support\Hooks\HookManager;
use App\Support\Appearance\ThemeManager;
use Illuminate\Support\Facades\View;

// NEW: Settings system
use App\Support\Settings\Settings;
use App\Support\Settings\SettingsPageRegistry;

/*
|--------------------------------------------------------------------------
| Hook helpers (WordPress-style)
|--------------------------------------------------------------------------
*/

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, ...$args): void
    {
        app(HookManager::class)->doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookManager::class)->addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value, ...$args)
    {
        return app(HookManager::class)->applyFilters($hook, $value, ...$args);
    }
}

/*
|--------------------------------------------------------------------------
| CPT & Taxonomy helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('register_post_type')) {
    function register_post_type(string $slug, array $args = []): void
    {
        app(PostTypeRegistry::class)->register($slug, $args);
    }
}

if (!function_exists('register_taxonomy')) {
    /**
     * @param string|array $objectType
     */
    function register_taxonomy(string $taxonomy, $objectType, array $args = []): void
    {
        app(TaxonomyRegistry::class)->register($taxonomy, $objectType, $args);
    }
}

if (!function_exists('register_terms')) {
    /**
     * @param array<int,array{slug?:string,name:string,parent?:string}> $terms
     */
    function register_terms(string $taxonomy, array $terms): void
    {
        app(TermsRegistry::class)->add($taxonomy, $terms);
    }
}

/*
|--------------------------------------------------------------------------
| Admin Menu helper (simplest API)
|--------------------------------------------------------------------------
*/

if (!function_exists('register_admin_menu')) {
    /**
     * Two forms:
     * 1) register_admin_menu('posts', ['label'=>'All Posts','route'=>'admin.posts.index','icon'=>'lucide-list','order'=>10]);
     * 2) register_admin_menu(['key'=>'posts','label'=>'Posts','icon'=>'lucide-file-text','order'=>10,'children'=>[ ... ]]);
     */
    function register_admin_menu(string|array $groupKeyOrItem, ?array $child = null): void
    {
        /** @var AdminMenuRegistry $menu */
        $menu = app(AdminMenuRegistry::class);

        if (is_array($groupKeyOrItem) && $child === null) {
            $menu->add($groupKeyOrItem);
            return;
        }

        if (is_string($groupKeyOrItem) && is_array($child)) {
            $menu->addChild($groupKeyOrItem, $child);
            return;
        }

        throw new \InvalidArgumentException('register_admin_menu expects (array) or (groupKey, childArray).');
    }
}

/*
|--------------------------------------------------------------------------
| Theme helpers (dynamic theme resolution)
|--------------------------------------------------------------------------
*/

if (!function_exists('theme_slug')) {
    function theme_slug(): string
    {
        return app(ThemeManager::class)->activeSlug(); // '' when none active
    }
}

if (!function_exists('theme_view')) {
    /**
     * Build a "theme::view" reference and ensure the namespace is bound.
     */
    function theme_view(string $view): string
    {
        // Make sure "theme::" points to the current active theme this request (or fallback)
        app(ThemeManager::class)->rebindViewNamespace();
        return "theme::{$view}";
    }
}

if (!function_exists('theme_asset')) {
    /**
     * Resolve an asset path under the active theme's public directory.
     * Uses config('appearance.public_themes_path', 'themes')/{$slug}/{$path}.
     * Falls back to plain asset() when no theme is active.
     */
    function theme_asset(string $path): string
    {
        $slug = theme_slug();
        $base = trim(config('appearance.public_themes_path', 'themes'), '/');
        $path = ltrim($path, '/');

        return $slug !== ''
            ? asset("{$base}/{$slug}/{$path}")
            : asset($path);
    }
}

/*
|--------------------------------------------------------------------------
| Lucide icon render (optional)
|--------------------------------------------------------------------------
*/
if (!function_exists('lucide_icon')) {
    function lucide_icon(string $slug, string $class = 'w-4 h-4')
    {
        // If you installed blade-lucide-icons: <x-lucide-... />
        if (function_exists('view') && View::exists('components.lucide-' . $slug)) {
            return view('components.lucide-' . $slug, ['class' => $class])->render();
        }

        // Dynamic component fallback if you registered it
        if (function_exists('view') && View::exists('components.dynamic-icon-fallback')) {
            return view('components.dynamic-icon-fallback', [
                'component' => 'lucide-' . $slug,
                'class' => $class,
            ])->render();
        }

        // Last-resort invisible span to avoid errors
        return '<span class="' . e($class) . '" aria-hidden="true"></span>';
    }
}

/*
|--------------------------------------------------------------------------
| Front-end Menu renderer
|--------------------------------------------------------------------------
*/
if (!function_exists('render_menu')) {
    function render_menu(string $location, array $options = []): string
    {
        return app(\App\Services\MenuService::class)->render($location, $options);
    }
}

/*
|--------------------------------------------------------------------------
| Widgets helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('render_widget_area')) {
    function render_widget_area(string $slug): string
    {
        return app(\App\Support\Appearance\WidgetRenderer::class)->renderArea($slug);
    }
}

if (!function_exists('render_widget')) {
    /**
     * Render a single widget by registry key anywhere in blades.
     * Example: {!! render_widget('recent_posts', ['limit'=>5], 'Latest') !!}
     */
    function render_widget(string $type, array $settings = [], ?string $title = null): string
    {
        return app(\App\Support\Appearance\WidgetRenderer::class)
            ->renderWidgetByKey($type, $settings, $title);
    }
}

/*
|--------------------------------------------------------------------------
| Developer hooks for widgets (extensible)
|--------------------------------------------------------------------------
*/

if (!function_exists('register_widget')) {
    /**
     * Register a widget type into the global registry.
     * @param string $key   Registry key (e.g., 'recent_posts')
     * @param string $class Class implementing \App\Support\Appearance\WidgetType
     */
    function register_widget(string $key, string $class): void
    {
        app(\App\Support\Appearance\WidgetRegistry::class)->register($key, $class);
    }
}

if (!function_exists('register_sidebar')) {
    /**
     * Register (or ensure) a widget area exists.
     * Example: register_sidebar(['name'=>'Sidebar','slug'=>'sidebar','description'=>'Main sidebar'])
     */
    function register_sidebar(array $area): void
    {
        \App\Models\WidgetArea::firstOrCreate(
            ['slug' => $area['slug']],
            [
                'name' => $area['name'] ?? $area['slug'],
                'description' => $area['description'] ?? null,
                'theme' => $area['theme'] ?? null, // null = global
            ]
        );
    }
}

/*
|--------------------------------------------------------------------------
| SETTINGS helpers (API + extensibility)
|--------------------------------------------------------------------------
|
| - setting('group.key', $default) → read value
| - settings_set('group.key', $value) → write + persist
| - settings_group('group') → array of that group's settings
| - register_settings_page('key', $meta, $callback) → add modular page
| - settings_export()/settings_import() → JSON import/export wrappers
|
*/

if (!function_exists('setting')) {
    /**
     * Get a setting value by dot key, e.g. 'general.site_title'.
     */
    function setting(string $key, $default = null)
    {
        try {
            return app(Settings::class)->get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('settings_set')) {
    /**
     * Persist a setting value by dot key.
     * Returns true on success, false otherwise.
     */
    function settings_set(string $key, $value): bool
    {
        try {
            /** @var Settings $s */
            $s = app(Settings::class);
            $s->set($key, $value);
            $s->save();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('settings_group')) {
    /**
     * Get all settings for a group (e.g. 'general', 'reading', ...).
     */
    function settings_group(string $group): array
    {
        try {
            return (array) app(Settings::class)->group($group);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('register_settings_page')) {
    /**
     * Register an admin settings page.
     *
     * $meta example:
     * [
     *   'label' => 'General',
     *   'icon'  => 'lucide-settings',
     *   'order' => 10,
     *   'permission' => 'settings.manage', // optional
     * ]
     *
     * $callback: function(): \Illuminate\Contracts\View\View|string
     *   Return a view or HTML; or register fields with your registry.
     */
    function register_settings_page(string $key, array $meta, ?callable $callback = null): void
    {
        try {
            app(SettingsPageRegistry::class)->register($key, $meta, $callback);
        } catch (\Throwable $e) {
            // swallow if registry not bound yet
        }
    }
}

if (!function_exists('settings_export')) {
    /**
     * Export all settings; when $asJson=true returns JSON string.
     */
    function settings_export(bool $asJson = false)
    {
        try {
            $data = app(Settings::class)->export();
            return $asJson ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $data;
        } catch (\Throwable $e) {
            return $asJson ? '{}' : [];
        }
    }
}

if (!function_exists('settings_import')) {
    /**
     * Import settings from array or JSON string.
     * If $merge is true, merge into existing; else replace.
     */
    function settings_import(array|string $payload, bool $merge = true): bool
    {
        try {
            if (is_string($payload)) {
                $payload = json_decode($payload, true) ?: [];
            }
            app(Settings::class)->import((array) $payload, $merge);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}