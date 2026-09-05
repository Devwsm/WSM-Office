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
 * Setelah berhasil login, redirectPath() menentukan halaman awal sesuai
 * role — Owner ke /owner/dashboard, HRD ke /rekrutmen/pelamar (kerjaan
 * utamanya sekarang), Manajer & Karyawan ke /app/home.
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
            'hrd' => route('recruitment.applications.index'),
            default => route('employee.home'),
        };
    }
}