<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to install routes without installation lock
        if ($request->is('install*')) {
            return $next($request);
        }

        // Allow access to authentication routes without installation lock
        if ($request->is('login') || $request->is('register') || $request->is('forgot-password')) {
            return $next($request);
        }

        // Allow access to email verification routes without installation lock
        if ($request->is('email/verify*') || $request->is('email/confirm*')) {
            return $next($request);
        }

        // Skip installation check for API requests and testing
        if ($request->is('api/*') || app()->runningUnitTests()) {
            return $next($request);
        }

        // Require installation lock for all other routes
        if (!file_exists(storage_path('installed.lock'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}
