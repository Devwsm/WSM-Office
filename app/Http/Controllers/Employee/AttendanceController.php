<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ClockInRequest;
use App\Http\Requests\Employee\ClockOutRequest;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
use App\Support\AttendanceReconciler;
use App\Support\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * AttendanceController (Employee)
 * ---------------------------------------------------------------------
 * Fase 4 — absen masuk/pulang buat diri sendiri. Fase 7 nambah 3 hal
 * (lihat README "Rombak Rencana" & Roadmap Fase 7):
 *
 * 1. Multi-sesi buat mode Lapangan/Gigs — SEKUENSIAL, bukan bersamaan
 *    (checkin1 -> checkout1 -> checkin2 -> checkout2), sama kayak
 *    tombol "CHECK IN AGAIN" di prototype yang cuma muncul SETELAH
 *    sesi sebelumnya checkout. Kantor/WFH tetap 1 sesi per hari.
 * 2. Auto-close: sesi Kantor/WFH yang lupa checkout DAN tanggalnya
 *    udah lewat, dipaksa ditutup sistem pas method apa pun di
 *    controller ini kepanggil (`AttendanceReconciler::reconcile()`,
 *    class terpisah — dipakai bareng `HomeController` juga) — gak ada
 *    cron di hosting cPanel shared, jadi reconcile-nya "on-demand"
 *    tiap ada yang buka halaman terkait (sama keterbatasan prototype
 *    yang browser-based, cuma di sini triggernya request ke server,
 *    bukan app dibuka).
 * 3. Geo toggle: `OfficeSetting::geo_attendance_enabled` &
 *    `enforce_radius` — kalau geo dimatiin total, gak ada
 *    perhitungan jarak sama sekali (bukan cuma di-skip nilainya).
 *    Kalau geo nyala tapi `enforce_radius` mati, jarak tetap dihitung
 *    & dicatat (buat informasi), tapi `within_radius` dipaksa true.
 * ---------------------------------------------------------------------
 */
class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceReconciler $reconciler) {}

    public function clockIn(ClockInRequest $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $this->reconciler->reconcile((int) $userId);

        if (LeaveRequest::approvedFor((int) $userId, $today)) {
            return back()->with('error', 'Kamu sedang izin/cuti hari ini, gak perlu absen.');
        }

        $data = $request->validated();
        $isMultiSession = in_array($data['mode'], Attendance::MULTI_SESSION_MODES, true);

        $sessions = Attendance::sessionsFor((int) $userId, $today);
        $openSession = $sessions->first(fn(Attendance $a) => $a->clock_in_at && ! $a->clock_out_at);

        if ($openSession) {
            return back()->with('warning', 'Kamu masih dalam sesi kerja yang belum checkout. Checkout dulu sebelum absen masuk lagi.');
        }

        if (! $isMultiSession && $sessions->isNotEmpty()) {
            return back()->with('warning', 'Kamu sudah absen masuk hari ini. Mode Kantor/WFH cuma 1 sesi per hari.');
        }

        $nextSessionNumber = $sessions->isEmpty() ? 1 : $sessions->max('session_number') + 1;

        $setting = OfficeSetting::current();
        [$distance, $withinRadius] = $this->calculateGeo($data['mode'], $setting, (float) $data['lat'], (float) $data['lng']);

        $photoPath = $this->storePhoto($data['photo'] ?? null, (int) $userId, $today, "sesi{$nextSessionNumber}-masuk");

        $attendance = new Attendance(['user_id' => $userId, 'date' => $today, 'session_number' => $nextSessionNumber]);
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

        $message = $nextSessionNumber > 1
            ? "Absen masuk sesi ke-{$nextSessionNumber} berhasil dicatat."
            : 'Absen masuk berhasil dicatat.';

        if ($data['mode'] === 'kantor' && $withinRadius === false) {
            $message .= " Catatan: lokasi kamu sekitar {$distance}m dari kantor, di luar radius {$setting->radius_meters}m.";
        }

        return back()->with('status', $message);
    }

    public function clockOut(ClockOutRequest $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        $this->reconciler->reconcile((int) $userId);

        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->where('date', $today)
            ->whereNull('clock_out_at')
            ->orderByDesc('session_number')
            ->first();

        if (! $attendance || ! $attendance->clock_in_at) {
            return back()->with('error', 'Kamu belum absen masuk hari ini.');
        }

        $data = $request->validated();
        $setting = OfficeSetting::current();
        [$distance, $withinRadius] = $this->calculateGeo($attendance->mode, $setting, (float) $data['lat'], (float) $data['lng']);

        $photoPath = $this->storePhoto($data['photo'] ?? null, (int) $userId, $today, "sesi{$attendance->session_number}-pulang");

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

        $message = $attendance->session_number > 1
            ? "Absen pulang sesi ke-{$attendance->session_number} berhasil dicatat."
            : 'Absen pulang berhasil dicatat. Selamat istirahat!';

        return back()->with('status', $message);
    }

    /** Riwayat absensi bulanan milik sendiri. */
    public function history(Request $request)
    {
        $this->reconciler->reconcile((int) Auth::id());

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
            ->orderByDesc('session_number')
            ->get();

        $setting = OfficeSetting::current();
        $shortage = Attendance::monthlyShortageBlocks((int) Auth::id(), $month, $setting);

        return view('employee.attendance.history', [
            'rows' => $rows,
            'setting' => $setting,
            'period' => $period,
            'currentMonth' => $month,
            'prevMonth' => $period->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $period->copy()->addMonth()->format('Y-m'),
            'isCurrentMonth' => $period->isSameMonth(Carbon::now()),
            'shortage' => $shortage,
        ]);
    }

    /**
     * Fase 7 — hitung ulang jarak dari kantor. Return [distance, withinRadius].
     * - Mode WFH/Lapangan/Gigs -> radius selalu di-skip (null, null), sama Fase 4.
     * - Geo dimatiin total (`geo_attendance_enabled=false`) -> (null, null) juga,
     *   walau mode-nya kantor.
     * - Geo nyala tapi `enforce_radius=false` -> jarak TETAP dihitung & dicatat
     *   (informasi), tapi `within_radius` dipaksa true (gak pernah dianggap masalah).
     */
    private function calculateGeo(string $mode, OfficeSetting $setting, float $lat, float $lng): array
    {
        if ($mode !== 'kantor' || ! $setting->geo_attendance_enabled) {
            return [null, null];
        }

        $distance = Geo::distanceMeters($setting->latitude, $setting->longitude, $lat, $lng);
        $withinRadius = $setting->enforce_radius ? $distance <= $setting->radius_meters : true;

        return [$distance, $withinRadius];
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