<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Allow the request only if the user is authenticated AND marked as admin.
     * Adjust the checks to match your users table.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Option A: boolean column `is_admin`
        $isAdmin = (bool) optional($user)->is_admin;

        // Option B: role column equals 'admin' (uncomment if you use this)
        // $isAdmin = optional($user)->role === 'admin';

        if (!$user || !$isAdmin) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}