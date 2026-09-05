<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * DashboardController (Owner)
 * ---------------------------------------------------------------------
 * Halaman ringkasan utama Owner. Kartu "Kehadiran Hari Ini" & "Pengajuan
 * Pending" (baru pelamar HRD dulu, izin/cuti nyusul Fase 5) sudah aktif
 * sejak Fase 4 — sisanya masih placeholder sampai modul terkait dibangun.
 * ---------------------------------------------------------------------
 */
class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $totalKaryawan = User::query()->count();
        $hadirHariIni = Attendance::query()->where('date', $today)->whereNotNull('clock_in_at')->count();

        $pelamarBaru = JobApplication::query()->where('status', 'baru')->count();

        return view('owner.dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'totalKaryawan' => $totalKaryawan,
            'pelamarBaru' => $pelamarBaru,
        ]);
    }
}