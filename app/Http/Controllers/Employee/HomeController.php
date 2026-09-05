<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OfficeSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * HomeController (Employee)
 * ---------------------------------------------------------------------
 * Halaman utama sisi Karyawan/Manajer/HRD/Owner. Sejak Fase 4 kartu
 * kehadiran di sini sudah fungsional — controller nyiapin absen hari ini
 * (kalau ada) + office_settings buat kartu absen & modal konfirmasi.
 * ---------------------------------------------------------------------
 */
class HomeController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        // Reminder kalau ada absen hari sebelumnya yang lupa di-checkout.
        // Koreksi manual belum ada di Fase 4 (nyusul Fase 5) — jadi ini
        // cuma pengingat visual, karyawan tetap perlu "dimention" langsung
        // sama Manajer/Owner kalau ini kejadian beneran.
        $forgottenAttendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('date', '<', $today)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->orderByDesc('date')
            ->first();

        return view('employee.home', [
            'attendance' => $attendance,
            'forgottenAttendance' => $forgottenAttendance,
            'officeSetting' => OfficeSetting::current(),
        ]);
    }
}