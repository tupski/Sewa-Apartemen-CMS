<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Force HTTPS on generated URLs when the request arrives over HTTPS
     * (e.g. behind Cloudflare Tunnel, which terminates TLS at the edge and
     * forwards the original scheme via X-Forwarded-Proto).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
