<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
    /** Batas salah password per kombinasi email + IP, dan lama blokirnya (detik). */
    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

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

        // 2026-10-11 — batas percobaan sekarang dihitung per email + IP dan HANYA
        // untuk login yang gagal. Dulu `throttle:5,1` per IP menghitung semua
        // request, jadi karyawan yang login bersamaan lewat WiFi kantor yang
        // sama bisa saling memblokir. Pelindung per IP (lebih longgar) tetap
        // ada di routes/auth.php.
        $key = $this->throttleKey($request, $credentials['email']);

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login yang salah. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    private function throttleKey(Request $request, string $email): string
    {
        return 'login|' . Str::lower($email) . '|' . $request->ip();
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