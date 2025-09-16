<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// ⬇️ Add your console command class here
use App\Console\Commands\MigrateOfferingsLegacyMedia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 👉 Register your admin middleware alias here (Laravel 12 style)
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // 🔐 Force installer until APP_INSTALLED=true (or installed=true) in .env
        $middleware->append(\App\Http\Middleware\RedirectIfNotInstalled::class);

        // (optionally) push other global / group middleware here...
        // $middleware->web(fn ($web) => $web->append(...));
        // $middleware->api(fn ($api) => $api->append(...));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\RegistryServiceProvider::class,
        App\Providers\ThemeServiceProvider::class,

        // Hooks must be available early for do_action()/add_action()
        App\Providers\HookServiceProvider::class,

        // Module loader after hooks
        App\Providers\ModuleServiceProvider::class,
        App\Providers\WidgetServiceProvider::class,
        App\Providers\SettingsServiceProvider::class,
        \App\Support\Shortcode\ShortcodeServiceProvider::class,
    ])
    // ⬇️ Register class-based Artisan commands here
    ->withCommands([
        MigrateOfferingsLegacyMedia::class,
        // Add more commands here as needed
    ])
    ->create();