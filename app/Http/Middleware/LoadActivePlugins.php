<?php

namespace App\Http\Middleware;

use App\Services\PluginLoader;
use Closure;

class LoadActivePlugins
{
    public function __construct(protected PluginLoader $loader)
    {
    }

    public function handle($request, Closure $next)
    {
        // load enabled plugins (providers/bootstraps)
        $this->loader->scanAndSync(); // keep DB in sync 
        $this->loader->bootActive();  // register providers/bootstraps
        return $next($request);
    }
}