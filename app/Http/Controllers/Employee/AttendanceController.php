<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ClockInRequest;
use App\Http\Requests\Employee\ClockOutRequest;
use App\Models\Attendance;
use App\Models\OfficeSetting;
use App\Support\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * AttendanceController (Employee)
 * ---------------------------------------------------------------------
 * Fase 4 — absen masuk/pulang buat diri sendiri. Berlaku buat semua role
 * internal (Karyawan/Manajer/HRD/Owner absen sendiri-sendiri, sama-sama
 * "karyawan" dari sisi absensi). Jarak dari kantor DIHITUNG ULANG di sini
 * (bukan percaya angka dari JS) — koordinat browser tetap bisa disundul
 * user, jadi validasi utama tetap di server.
 *
 * Kesepakatan Fase 4:
 * - Mode WFH -> radius di-skip (nggak dianggap "di luar radius").
 * - Mode Kantor di luar radius -> TETAP boleh absen, cuma ditandai
 *   `within_radius = false` (bukan diblokir).
 * - Foto selfie opsional, dikirim sebagai base64 dari kamera browser.
 * - Koreksi/approval absen manual SENGAJA belum ada di sini — nyusul
 *   nyambung ke Fase 5 (Izin/Cuti & Approval).
 * ---------------------------------------------------------------------
 */
class AttendanceController extends Controller
{
    public function clockIn(ClockInRequest $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $existing = Attendance::query()->where('user_id', $userId)->where('date', $today)->first();

        if ($existing && $existing->clock_in_at) {
            return back()->with('warning', 'Kamu sudah absen masuk hari ini.');
        }

        $data = $request->validated();
        $setting = OfficeSetting::current();

        $distance = null;
        $withinRadius = null;

        if ($data['mode'] === 'kantor') {
            $distance = Geo::distanceMeters($setting->latitude, $setting->longitude, (float) $data['lat'], (float) $data['lng']);
            $withinRadius = $distance <= $setting->radius_meters;
        }

        $photoPath = $this->storePhoto($data['photo'] ?? null, $userId, $today, 'masuk');

        $attendance = $existing ?? new Attendance(['user_id' => $userId, 'date' => $today]);
        $attendance->fill([
            'mode' => $data['mode'],
            'work_context' => $data['work_context'] ?? null,
            'clock_in_at' => Carbon::now(),
            'clock_in_lat' => $data['lat'],
            'clock_in_lng' => $data['lng'],
            'clock_in_accuracy_meters' => isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
            'clock_in_distance_meters' => $distance,
            'clock_in_within_radius' => $withinRadius,
            'clock_in_photo' => $photoPath,
        ]);
        $attendance->save();

        $message = 'Absen masuk berhasil dicatat.';
        if ($data['mode'] === 'kantor' && $withinRadius === false) {
            $message .= " Catatan: lokasi kamu sekitar {$distance}m dari kantor, di luar radius {$setting->radius_meters}m.";
        }

        return back()->with('status', $message);
    }

    public function clockOut(ClockOutRequest $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::query()->where('user_id', $userId)->where('date', $today)->first();

        if (! $attendance || ! $attendance->clock_in_at) {
            return back()->with('error', 'Kamu belum absen masuk hari ini.');
        }

        if ($attendance->clock_out_at) {
            return back()->with('warning', 'Kamu sudah absen pulang hari ini.');
        }

        $data = $request->validated();
        $setting = OfficeSetting::current();

        $distance = null;
        $withinRadius = null;

        if ($attendance->mode === 'kantor') {
            $distance = Geo::distanceMeters($setting->latitude, $setting->longitude, (float) $data['lat'], (float) $data['lng']);
            $withinRadius = $distance <= $setting->radius_meters;
        }

        $photoPath = $this->storePhoto($data['photo'] ?? null, $userId, $today, 'pulang');

        $attendance->fill([
            'clock_out_at' => Carbon::now(),
            'clock_out_lat' => $data['lat'],
            'clock_out_lng' => $data['lng'],
            'clock_out_accuracy_meters' => isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
            'clock_out_distance_meters' => $distance,
            'clock_out_within_radius' => $withinRadius,
            'clock_out_photo' => $photoPath,
        ]);
        $attendance->save();

        return back()->with('status', 'Absen pulang berhasil dicatat. Selamat istirahat!');
    }

    /** Riwayat absensi bulanan milik sendiri. */
    public function history(Request $request)
    {
        $month = $request->query('bulan', Carbon::now()->format('Y-m'));

        try {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception) {
            $period = Carbon::now()->startOfMonth();
            $month = $period->format('Y-m');
        }

        $rows = Attendance::query()
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$period->copy()->startOfMonth()->toDateString(), $period->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('date')
            ->get();

        $setting = OfficeSetting::current();

        return view('employee.attendance.history', [
            'rows' => $rows,
            'setting' => $setting,
            'period' => $period,
            'currentMonth' => $month,
            'prevMonth' => $period->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $period->copy()->addMonth()->format('Y-m'),
            'isCurrentMonth' => $period->isSameMonth(Carbon::now()),
        ]);
    }

    /** Decode foto base64 dari browser lalu simpan ke storage publik. Return path relatif atau null. */
    private function storePhoto(?string $base64, int $userId, string $date, string $type): ?string
    {
        if (! $base64) {
            return null;
        }

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/', $base64, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $binary = base64_decode($matches[2]);

        if ($binary === false) {
            return null;
        }

        $path = "attendance/{$userId}/{$date}-{$type}-" . Str::random(8) . ".{$extension}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}