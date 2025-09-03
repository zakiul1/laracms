<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\LaracmsServiceProvider::class,

    // ✅ Enable dynamic system
    App\Providers\HookServiceProvider::class,
    App\Providers\RegistryServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Providers\ThemeServiceProvider::class,
    App\Providers\PluginManagerServiceProvider::class,
    App\Providers\MenusServiceProvider::class,


];