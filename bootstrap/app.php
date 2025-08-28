<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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

        // (optionally) You can push global / group middleware here as well:
        // $middleware->append(\App\Http\Middleware\YourGlobalMiddleware::class);
        // $middleware->web( fn($web) => $web->append(...));
        // $middleware->api( fn($api) => $api->append(...));
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
    ])
    ->create();