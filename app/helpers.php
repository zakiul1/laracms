<?php

use App\Support\Cms\PostTypeRegistry;
use App\Support\Cms\TaxonomyRegistry;
use App\Support\Cms\TermsRegistry;
use App\Support\Hooks\HookManager;

/*
|--------------------------------------------------------------------------
| Hook helpers (WordPress-style)
| Thin wrappers around your HookManager so views and modules can use
| add_action()/do_action() and add_filter()/apply_filters().
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
| CMS registries: CPT / Taxonomy / Terms
|--------------------------------------------------------------------------
*/

/**
 * Register a Custom Post Type (CPT) — WP-style.
 */
if (!function_exists('register_post_type')) {
    function register_post_type(string $slug, array $args = []): void
    {
        app(PostTypeRegistry::class)->register($slug, $args);
    }
}

/**
 * Register a taxonomy for one or more CPTs — WP-style.
 *
 * @param string|array $objectType CPT slug or array of CPT slugs
 */
if (!function_exists('register_taxonomy')) {
    function register_taxonomy(string $taxonomy, $objectType, array $args = []): void
    {
        app(TaxonomyRegistry::class)->register($taxonomy, $objectType, $args);
    }
}

/**
 * Register initial terms for a taxonomy (optional seeding).
 *
 * @param array<int,array{slug?:string,name:string,parent?:string}> $terms
 */
if (!function_exists('register_terms')) {
    function register_terms(string $taxonomy, array $terms): void
    {
        app(TermsRegistry::class)->add($taxonomy, $terms);
    }
}

/*
|--------------------------------------------------------------------------
| Lucide icon helper
|--------------------------------------------------------------------------
*/

/**
 * Render a Lucide icon by slug using Blade dynamic component.
 * Requires blade-lucide-icons. Works even if your wrapper component is missing.
 */
if (!function_exists('lucide_icon')) {
    function lucide_icon(string $name, string $class = 'w-4 h-4'): string
    {
        $slug = str_replace('_', '-', $name);

        // Prefer a custom wrapper component if you created it:
        // resources/views/components/lucide.blade.php
        if (function_exists('view') && view()->exists('components.lucide')) {
            return view('components.lucide', ['name' => $slug, 'class' => $class])->render();
        }

        // Fallback tiny view that renders a dynamic component, if you added it
        if (function_exists('view') && view()->exists('components.dynamic-icon-fallback')) {
            return view('components.dynamic-icon-fallback', [
                'component' => 'lucide-' . $slug,
                'class' => $class,
            ])->render();
        }

        // Last-resort placeholder (prevents crashes if views aren’t available yet)
        return '<span class="' . e($class) . '" aria-hidden="true"></span>';
    }
}