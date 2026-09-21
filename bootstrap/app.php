<?php

use App\Http\Middleware\EnsureDashboardUnlocked;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'module' => EnsureModuleAccess::class,
            'dashboard.unlocked' => EnsureDashboardUnlocked::class,
        ]);

        // Paksa ganti password sementara (reset IT / import default) di
        // SEMUA route web, tanpa perlu ditempel per grup.
        $middleware->web(append: [
            EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();