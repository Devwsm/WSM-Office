<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * LoginController
 * ---------------------------------------------------------------------
 * Login memakai 1 form untuk semua role (Owner/Manajer/Karyawan/HRD).
 * Setelah berhasil login, redirectPath() menentukan halaman awal.
 *
 * 2026-09-09 — HRD DIHAPUS dari redirect khusus (dulu -> /rekrutmen/
 * pelamar). Sejak refactor "permission bukan role", role 'hrd' TIDAK
 * LAGI otomatis berarti punya akses Rekrutmen (itu sekarang
 * dashboard_access modul `recruitment`, bisa dicabut Owner per-orang)
 * — kalau tetap diarahkan ke sana dan kebetulan akses itu udah dicabut,
 * orangnya bakal kena 403 tepat setelah login. Semua role SELAIN Owner
 * sekarang seragam ke /app/home (App Mode) — dari situ tombol
 * "Dashboard"/"Kelola Tim" di header cuma muncul kalau memang punya
 * akses beneran (lihat layouts/employee.blade.php).
 * ---------------------------------------------------------------------
 */
class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectPath(): string
    {
        return match (Auth::user()->role) {
            'owner' => route('owner.dashboard'),
            default => route('employee.home'),
        };
    }
}