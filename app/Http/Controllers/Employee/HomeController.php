<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Memo;
use App\Models\OfficeSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * HomeController (Employee)
 * ---------------------------------------------------------------------
 * Halaman utama sisi Karyawan/Manajer/HRD/Owner. Kartu kehadiran
 * fungsional sejak Fase 4; sejak Fase 5 kartu ini juga cek apakah hari
 * ini lagi ada izin/cuti yang disetujui — kalau ada, tombol absen
 * disembunyikan (nyambung ke App\Support kesepakatan Fase 5: karyawan
 * yang lagi cuti/izin approved nggak perlu/bisa absen).
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
        $forgottenAttendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('date', '<', $today)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->orderByDesc('date')
            ->first();

        $todayLeave = LeaveRequest::approvedFor((int) Auth::id(), $today);

        // Kartu "Info dari Owner" — sengaja kelihatan buat SEMUA role
        // internal terlepas dari dashboard_access (Fase 6a), karena ini
        // pengumuman ke tim, bukan modul kerja yang butuh akses.
        $memos = Memo::query()->with('creator')->latestFirst()->limit(3)->get();

        return view('employee.home', [
            'attendance' => $attendance,
            'forgottenAttendance' => $forgottenAttendance,
            'todayLeave' => $todayLeave,
            'officeSetting' => OfficeSetting::current(),
            'memos' => $memos,
        ]);
    }
}