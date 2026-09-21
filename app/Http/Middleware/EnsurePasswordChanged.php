<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePasswordChanged
 * ---------------------------------------------------------------------
 * Paksa user yang `must_change_password`-nya menyala (password sementara
 * hasil reset dari dashboard IT, atau akun hasil import dengan password
 * default) untuk mengganti password dulu. Selama belum diganti, semua
 * halaman selain form ganti password dan logout dialihkan ke Profil.
 *
 * Dipasang di grup middleware `web` (bootstrap/app.php), jadi berlaku
 * untuk semua route tanpa perlu menempelkan middleware ini satu-satu.
 * Tamu (belum login) tidak terpengaruh.
 * ---------------------------------------------------------------------
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('employee.profile.index', 'employee.profile.password', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Ganti password sementara kamu dulu di halaman Profil.');
        }

        return redirect()
            ->route('employee.profile.index')
            ->with('warning', 'Password kamu masih sementara. Ganti dulu dengan password baru sebelum memakai aplikasi.');
    }
}