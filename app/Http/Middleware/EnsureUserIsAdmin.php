<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Block non-admin users from the admin panel.
     *
     * Relies on the auth middleware having run first (route group order).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke area admin.');
        }

        return $next($request);
    }
}
