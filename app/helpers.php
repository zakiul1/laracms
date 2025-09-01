<?php

use App\Support\Cms\PostTypeRegistry;
use App\Support\Cms\TaxonomyRegistry;
use App\Support\Cms\TermsRegistry;
use App\Support\Cms\AdminMenuRegistry;
use App\Support\Hooks\HookManager;

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

        throw new InvalidArgumentException('register_admin_menu expects (array) or (groupKey, childArray).');
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
        if (function_exists('view') && \Illuminate\Support\Facades\View::exists('components.lucide-' . $slug)) {
            return view('components.lucide-' . $slug, ['class' => $class])->render();
        }

        // Dynamic component fallback if you registered it
        if (function_exists('view') && \Illuminate\Support\Facades\View::exists('components.dynamic-icon-fallback')) {
            return view('components.dynamic-icon-fallback', ['component' => 'lucide-' . $slug, 'class' => $class])->render();
        }

        // Last-resort invisible span to avoid errors
        return '<span class="' . e($class) . '" aria-hidden="true"></span>';
    }
}