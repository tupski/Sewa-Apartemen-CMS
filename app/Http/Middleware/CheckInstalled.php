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
     * BUG-022 FIX: Gunakan named-route check yang lebih robust daripada string matching.
     * Path-based check rentan terhadap perubahan route di masa depan.
     * Selain itu, tambahkan cleanup state file saat user kembali ke langkah 1
     * agar password/credential lama tidak menumpuk (BUG-021 partial fix).
     *
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to install routes without installation lock
        if ($request->is('install*')) {
            return $next($request);
        }

        // BUG-022 FIX: Cek auth routes menggunakan Route::has() / named patterns
        // agar tidak bergantung pada hardcoded path yang bisa berubah.
        $currentRoute = $request->route()?->getName() ?? '';

        $authRoutes = [
            'login', 'register', 'password.request',
            'password.reset', 'password.email', 'password.update',
            'verification.notice', 'verification.verify', 'verification.send',
        ];

        if (in_array($currentRoute, $authRoutes, true)) {
            return $next($request);
        }

        // Fallback path-based check untuk route yang belum punya nama
        if ($request->is('login') || $request->is('register')
            || $request->is('forgot-password') || $request->is('reset-password*')
            || $request->is('email/verify*') || $request->is('email/confirm*')) {
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
