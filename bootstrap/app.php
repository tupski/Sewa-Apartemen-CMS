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
        $middleware->append([
            \App\Http\Middleware\CheckInstalled::class,
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
