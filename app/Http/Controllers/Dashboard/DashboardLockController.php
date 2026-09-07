<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UnlockDashboardRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * DashboardLockController
 * ---------------------------------------------------------------------
 * "Lock Dashboard" — lihat penjelasan lengkap di
 * `App\Http\Middleware\EnsureDashboardUnlocked` & README bagian
 * "Audit posisi UI/UX vs prototype v18".
 * ---------------------------------------------------------------------
 */
class DashboardLockController extends Controller
{
    /**
     * Tombol "Kunci Dashboard" di footer sidebar — set flag session,
     * lempar balik ke app-mobile (padanan `lockOwner()` prototype yang
     * langsung nampilin layar login ulang).
     */
    public function lock(Request $request): RedirectResponse
    {
        $request->session()->put('dashboard_locked', true);

        return redirect()->route('employee.home')->with('status', 'Dashboard dikunci.');
    }

    /**
     * Layar unlock — GET, ditampilkan lewat redirect dari
     * EnsureDashboardUnlocked.
     */
    public function show(Request $request)
    {
        // Kalau ternyata gak lagi ke-lock (mis. buka tab baru dari
        // bookmark lama), gak perlu nahan di sini.
        if ($request->session()->get('dashboard_locked') !== true) {
            return redirect()->route('dashboard.index');
        }

        return view('dashboard.locked');
    }

    /**
     * Submit password buat buka kunci. Berhasil → lempar balik ke URL
     * yang lagi dituju pas ke-lock (`dashboard_locked_intended`), atau
     * ke dashboard modul kalau gak ada.
     */
    public function unlock(UnlockDashboardRequest $request): RedirectResponse
    {
        $request->session()->forget('dashboard_locked');
        $intended = $request->session()->pull('dashboard_locked_intended');

        return redirect($intended ?: route('dashboard.index'));
    }

    /**
     * Tombol "Kembali" di layar unlock — padanan tombol "Kembali" ke
     * account picker di prototype; versi web resmi per-user, jadi
     * baliknya ke app-mobile sendiri (bukan logout paksa, sesi login
     * tetap ada, cuma area dashboard-nya masih terkunci).
     */
    public function cancel(Request $request): RedirectResponse
    {
        return redirect()->route('employee.home');
    }
}