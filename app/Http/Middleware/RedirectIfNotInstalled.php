<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // Allow CLI (artisan), install routes, and some static assets
        if (app()->runningInConsole()) {
            return $next($request);
        }

        // If already installed → continue
        if (env('APP_INSTALLED', false) || env('installed', false)) {
            return $next($request);
        }

        // Allow installer paths and public assets
        $path = $request->path();

        // Installer routes
        if (str_starts_with($path, 'install')) {
            return $next($request);
        }

        // Common asset/health paths that shouldn't be blocked
        if (preg_match('#^(storage|assets|build|vendor|mix-manifest\.json|favicon\.ico|robots\.txt|up)$#', $path)) {
            return $next($request);
        }

        // → otherwise force to installer
        return redirect()->to('/install');
    }
}