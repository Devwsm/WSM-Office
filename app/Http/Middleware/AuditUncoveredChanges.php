<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditUncoveredChanges
 * ---------------------------------------------------------------------
 * Jaring pengaman Audit Log (2026-10-09): setiap aksi yang mengubah data
 * (POST/PUT/PATCH/DELETE), berhasil, dan dilakukan user yang login, tapi
 * tidak menulis catatan khusus, tetap dicatat sebagai
 * "Perubahan data (tidak terinci)" supaya tidak ada perubahan yang hilang
 * dari jejak. Catatan khusus selalu lebih baik (nilai lama → baru), jadi
 * route baru sebaiknya mencatat sendiri; AuditCoverageTest mengingatkannya.
 *
 * Tidak mencatat: validasi gagal, error (status >= 400), pesan `error`
 * (aksi ditolak secara logika), dan route di config/audit.php.
 * ---------------------------------------------------------------------
 */
class AuditUncoveredChanges
{
    public function handle(Request $request, Closure $next): Response
    {
        AuditLog::$recorded = 0;

        $response = $next($request);

        $user = $request->user();

        if (
            ! $user
            || in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            || AuditLog::$recorded > 0
            || $response->getStatusCode() >= 400
        ) {
            return $response;
        }

        $name = $request->route()?->getName();

        if ($this->ignored($name, $request->path())) {
            return $response;
        }

        if ($request->hasSession()) {
            $errors = $request->session()->get('errors');

            if (($errors && $errors->any()) || $request->session()->has('error')) {
                return $response;
            }
        }

        AuditLog::record(
            'Perubahan data (tidak terinci)',
            sprintf('%s %s oleh %s.', $request->method(), $name ?? $request->path(), $user->name),
            $user,
        );

        return $response;
    }

    private function ignored(?string $name, string $path): bool
    {
        if ($name !== null) {
            foreach (config('audit.ignore', []) as $pattern) {
                if (Str::is($pattern, $name)) {
                    return true;
                }
            }
        }

        return in_array($path, config('audit.ignore_uris', []), true);
    }
}