<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureRole
 * ---------------------------------------------------------------------
 * Cek apakah role user yang sedang login termasuk dalam daftar role yang
 * diizinkan untuk route tersebut. Dipakai di routes/web.php seperti:
 *   Route::middleware(['auth', 'role:owner'])->group(...)
 *   Route::middleware(['auth', 'role:manajer,owner'])->group(...)
 * ---------------------------------------------------------------------
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Kamu tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}