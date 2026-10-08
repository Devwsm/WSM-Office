<?php

namespace App\Http\Middleware;

use App\Support\Presence;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackPresence
 * ---------------------------------------------------------------------
 * Mencatat halaman terakhir yang dibuka user yang sedang login, untuk
 * Monitor Login (Dashboard > IT). Dipasang di grup `web` (bootstrap/app.php).
 *
 * Yang dicatat hanya NAMA ROUTE + label halaman — bukan URL, query, atau
 * isi form. Hanya request GET berisi halaman (HTML, status 200) yang
 * dihitung; AJAX, unduhan, foto, dan redirect diabaikan.
 * ---------------------------------------------------------------------
 */
class TrackPresence
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (
            ! $user
            || ! $request->isMethod('GET')
            || $request->ajax()
            || $request->expectsJson()
            || $response->getStatusCode() !== 200
        ) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        $label = Presence::labelFor($routeName);

        if ($routeName && $label) {
            Presence::recordPage($user, $routeName, $label);
        }

        return $response;
    }
}