<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // BUG-002 FIX: Tambahkan protect.installer — hanya localhost/whitelist/token
            Route::middleware(['web', 'protect.installer'])
                ->prefix('install')
                ->group(__DIR__.'/../routes/install.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust X-Forwarded-* from Cloudflare Tunnel (local daemon connects
        // from loopback/private addresses) so isSecure() reflects the real
        // scheme before ForceHttps runs.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\RedirectMiddleware::class,
            \App\Http\Middleware\LocaleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin'             => \App\Http\Middleware\EnsureUserIsAdmin::class,
            // BUG-002 FIX: Installer hanya bisa diakses dari localhost / IP whitelist / token
            'protect.installer' => \App\Http\Middleware\ProtectInstaller::class,
            'captcha'           => \App\Http\Middleware\VerifyCaptcha::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
