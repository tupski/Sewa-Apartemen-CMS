<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for admin/redirect routes (CRUD management)
        if ($request->is('admin/redirects*')) {
            return $next($request);
        }

        // Skip for AJAX/API
        if ($request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        try {
            $redirects = Cache::remember('redirects', 3600, function () {
                return Redirect::pluck('to_url', 'from_url')->toArray();
            });
        } catch (\Exception $e) {
            // Table may not exist yet (e.g., migrations not run)
            return $next($request);
        }

        if (!isset($redirects[$path])) {
            return $next($request);
        }

        // Follow chain with cycle detection, max depth 5
        $visited = [$path => true];
        $current = $path;
        $maxDepth = 5;

        while (isset($redirects[$current]) && $maxDepth > 0) {
            $target = $redirects[$current];
            if (isset($visited[$target])) {
                // Cycle detected
                abort(404);
            }
            $visited[$target] = true;
            $current = $target;
            $maxDepth--;
        }

        if ($maxDepth === 0 && isset($redirects[$current])) {
            abort(404);
        }

        $targetUrl = $current;
        $statusCode = $this->getStatusCode($path) ?: 301;

        // Normalize: if target doesn't start with http, build full URL
        if (!preg_match('#^https?://#', $targetUrl)) {
            $targetUrl = url('/' . ltrim($targetUrl, '/'));
        }

        return redirect($targetUrl, $statusCode);
    }

    /**
     * Get the status code for the redirect.
     */
    protected function getStatusCode(string $from): int
    {
        $codes = Cache::remember('redirect_status_codes', 3600, function () {
            return Redirect::pluck('status_code', 'from_url')->toArray();
        });

        return (int) ($codes[$from] ?? 301);
    }
}
