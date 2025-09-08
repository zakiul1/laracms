<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Support\Appearance\WidgetRegistry;
use App\Support\Appearance\WidgetRenderer;

class WidgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class);
        $this->app->singleton(WidgetRenderer::class);
    }

    public function boot(): void
    {
        // Register core widgets
        $reg = app(WidgetRegistry::class);
        $reg->register(\App\Widgets\TextWidget::key(), \App\Widgets\TextWidget::class);
        $reg->register(\App\Widgets\RecentPostsWidget::key(), \App\Widgets\RecentPostsWidget::class);
        $reg->register(\App\Widgets\CategoriesWidget::key(), \App\Widgets\CategoriesWidget::class);
        $reg->register(\App\Widgets\SearchWidget::key(), \App\Widgets\SearchWidget::class);
        $reg->register(\App\Widgets\MenuWidget::key(), \App\Widgets\MenuWidget::class);

        // Blade directives (convenience)
        Blade::directive('widget_area', fn($exp) =>
            "<?php echo app(\App\Support\Appearance\WidgetRenderer::class)->renderArea($exp); ?>");

        Blade::directive('widget', fn($exp) =>
            "<?php echo app(\App\Support\Appearance\WidgetRenderer::class)->renderWidgetByKey(...array_values((function(){return [$exp];})())); ?>");
    }
}