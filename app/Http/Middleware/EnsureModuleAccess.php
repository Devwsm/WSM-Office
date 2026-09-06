<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureModuleAccess
 * ---------------------------------------------------------------------
 * Fase 6a — cek User::accessLevel() ke satu modul dashboard_access,
 * BUKAN role. Pola pemakaiannya sengaja disamain kayak middleware
 * 'role' yang udah ada (EnsureRole) biar konsisten:
 *   Route::middleware(['auth', 'module:work,view'])->group(...)
 *   Route::middleware(['auth', 'module:work,manage'])->group(...)
 *
 * $level default 'view' kalau gak ditulis levelnya.
 * ---------------------------------------------------------------------
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module, string $level = 'view'): Response
    {
        $user = $request->user();

        $allowed = $level === 'manage'
            ? $user?->canManageModule($module)
            : $user?->canViewModule($module);

        if (! $allowed) {
            abort(403, 'Kamu tidak punya akses ke modul ini.');
        }

        return $next($request);
    }
}