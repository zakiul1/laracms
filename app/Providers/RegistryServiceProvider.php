<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\Cms\AdminMenuRegistry;
use App\Support\Cms\PostTypeRegistry;
use App\Support\Cms\TaxonomyRegistry;
use App\Support\Cms\TermsRegistry;

class RegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Post types
        $this->app->singleton(PostTypeRegistry::class, fn() => new PostTypeRegistry());

        // Admin menu (seed baseline + WordPress-like "Posts" group)
        $this->app->singleton(AdminMenuRegistry::class, function () {
            $r = new AdminMenuRegistry();
            $r->seedBaseline();
            $r->add([
                'key' => 'posts',
                'label' => 'Posts',
                'icon' => 'lucide-file-text',
                'order' => 12,
                'children' => [
                    ['label' => 'All Posts', 'route' => 'admin.posts.index', 'icon' => 'lucide-list', 'order' => 10],
                    ['label' => 'Add New', 'route' => 'admin.posts.create', 'icon' => 'lucide-plus', 'order' => 20],
                    ['label' => 'Categories', 'route' => 'admin.categories.index', 'icon' => 'lucide-folders', 'order' => 30],
                ],
            ]);
            return $r;
        });

        // Taxonomies
        $this->app->singleton(TaxonomyRegistry::class, fn() => new TaxonomyRegistry());

        // Terms (seed container)
        $this->app->singleton(TermsRegistry::class, fn() => new TermsRegistry());
    }
}