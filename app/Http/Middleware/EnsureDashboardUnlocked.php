<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureDashboardUnlocked
 * ---------------------------------------------------------------------
 * "Lock Dashboard" — padanan tombol merah di footer sidebar Owner
 * prototype (`lockOwner()`), diadaptasi ke arsitektur per-user web
 * resmi (bukan 1 password manajemen bersama — lihat README bagian
 * "Audit posisi UI/UX", entri "Update (2026-09-06, lanjutan)" buat
 * spek lengkap + alasan adaptasinya).
 *
 * Session-based (`session('dashboard_locked')`), server-side — beda
 * dari prototype yang pakai `sessionStorage` client-side (bisa
 * dibaca/diakalin lewat devtools).
 *
 * Dipasang di semua grup route yang nempatin user di `layouts.app`
 * (owner.*, manajer.*, recruitment.*, attendance.recap.*,
 * approval.leave.*, approval.overtime.*, dashboard.*) — BUKAN di grup
 * 'employee.*' (app-mobile, itu bukan area manajemen).
 *
 * Route lock/unlock sendiri (dashboard.lock.*) SENGAJA tidak dipasangi
 * middleware ini — kalau dipasang, orang yang lagi ke-lock gak akan
 * pernah bisa buka layar unlock-nya sendiri (infinite redirect).
 * ---------------------------------------------------------------------
 */
class EnsureDashboardUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('dashboard_locked') === true) {
            $request->session()->put('dashboard_locked_intended', $request->fullUrl());

            return redirect()->route('dashboard.lock.show');
        }

        return $next($request);
    }
}