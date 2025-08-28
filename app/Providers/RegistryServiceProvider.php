<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// CMS registries
use App\Support\Cms\AdminMenuRegistry;
use App\Support\Cms\PostTypeRegistry;
use App\Support\Cms\TaxonomyRegistry;
use App\Support\Cms\TermsRegistry;

class RegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Post Types (CPT)
        $this->app->singleton(PostTypeRegistry::class, fn() => new PostTypeRegistry());

        // Admin Menu (keeps your existing baseline seeding)
        $this->app->singleton(AdminMenuRegistry::class, function () {
            $r = new AdminMenuRegistry();
            $r->seedBaseline();
            return $r;
        });

        // Taxonomies
        $this->app->singleton(TaxonomyRegistry::class, fn() => new TaxonomyRegistry());

        // Terms (initial/seed terms per taxonomy)
        $this->app->singleton(TermsRegistry::class, fn() => new TermsRegistry());
    }
}