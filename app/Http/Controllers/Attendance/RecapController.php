<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * RecapController (Attendance)
 * ---------------------------------------------------------------------
 * Fase 4 — rekap absensi buat Manajer/Owner/HRD. Dipisah dari namespace
 * Owner/Employee karena dipakai bareng 3 role (pola sama seperti
 * Recruitment\JobOpeningController buat HRD+Owner).
 *
 * Scope data per role:
 * - Owner & HRD -> semua karyawan aktif (fungsi HR, HRD perlu lihat
 *   semua walau bukan atasan langsungnya).
 * - Manajer -> diri sendiri + seluruh bawahan turunan (bukan cuma
 *   bawahan langsung), dibangun dari manager_id di memori sama seperti
 *   OrganizationController (jumlah karyawan masih kecil).
 * ---------------------------------------------------------------------
 */
class RecapController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('tanggal', Carbon::today()->toDateString());

        try {
            $date = Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Exception) {
            $date = Carbon::today()->toDateString();
        }

        $users = $this->scopedUsers();
        $attendances = Attendance::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        // Izin/cuti disetujui yang nyakup tanggal ini — dipetakan per user
        // biar "Belum Absen" nggak salah keliatan buat karyawan yang lagi cuti.
        $leaves = LeaveRequest::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get()
            ->keyBy('user_id');

        $setting = OfficeSetting::current();

        $rows = $users->map(function (User $user) use ($attendances, $leaves, $setting) {
            $attendance = $attendances->get($user->id);
            $leave = $leaves->get($user->id);

            if (! $attendance && $leave) {
                return [
                    'user' => $user,
                    'attendance' => null,
                    'leave' => $leave,
                    'statusLabel' => $leave->typeLabel(),
                    'badgeClass' => 'badge-wsm-blue',
                ];
            }

            return [
                'user' => $user,
                'attendance' => $attendance,
                'leave' => null,
                'statusLabel' => $attendance ? $attendance->statusLabel($setting) : 'Belum Absen',
                'badgeClass' => $attendance ? $attendance->statusBadgeClass($setting) : 'badge-wsm-gray',
            ];
        });

        $summary = [
            'total' => $rows->count(),
            'hadir' => $rows->filter(fn($r) => $r['attendance'] && in_array($r['attendance']->statusKey($setting), ['hadir', 'sedang_bekerja', 'terlambat', 'kurang_jam_kerja'], true))->count(),
            'terlambat' => $rows->filter(fn($r) => $r['attendance'] && $r['attendance']->statusKey($setting) === 'terlambat')->count(),
            'wfh' => $rows->filter(fn($r) => $r['attendance']?->mode === 'wfh')->count(),
            'izin_cuti' => $rows->filter(fn($r) => $r['leave'] !== null)->count(),
            'belum_absen' => $rows->filter(fn($r) => ! $r['attendance'] && ! $r['leave'])->count(),
        ];

        return view('attendance.recap.index', [
            'rows' => $rows,
            'summary' => $summary,
            'date' => $date,
            'isToday' => $date === Carbon::today()->toDateString(),
        ]);
    }

    public function show(Request $request, User $user)
    {
        $scopedIds = $this->scopedUsers()->pluck('id');
        abort_unless($scopedIds->contains($user->id), 403, 'Kamu tidak punya akses ke absensi karyawan ini.');

        $month = $request->query('bulan', Carbon::now()->format('Y-m'));

        try {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception) {
            $period = Carbon::now()->startOfMonth();
            $month = $period->format('Y-m');
        }

        $rows = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$period->copy()->startOfMonth()->toDateString(), $period->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('date')
            ->get();

        // Izin/cuti disetujui yang overlap bulan ini, buat ditampilin di
        // hari-hari yang nggak ada baris attendance-nya (karyawan lagi
        // cuti, wajar nggak absen).
        $leaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $period->copy()->endOfMonth())
            ->whereDate('end_date', '>=', $period->copy()->startOfMonth())
            ->orderByDesc('start_date')
            ->get();

        $setting = OfficeSetting::current();

        return view('attendance.recap.show', [
            'employee' => $user,
            'rows' => $rows,
            'leaves' => $leaves,
            'setting' => $setting,
            'currentMonth' => $month,
            'prevMonth' => $period->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $period->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * Koreksi absen manual (Manajer/Owner). Cuma edit jam masuk/pulang —
     * kesepakatan Fase 5: BUKAN buat nandain hari itu jadi izin/cuti
     * manual (itu harus lewat alur pengajuan resmi). `original_*`
     * cuma kesisi sekali di koreksi pertama biar karyawan tetap bisa
     * lihat jam aslinya walau dikoreksi berkali-kali.
     */
    public function correct(CorrectAttendanceRequest $request, Attendance $attendance)
    {
        $scopedIds = $this->scopedUsers()->pluck('id');
        abort_unless($scopedIds->contains($attendance->user_id), 403, 'Kamu tidak punya akses ke absensi karyawan ini.');

        $data = $request->validated();

        if (! empty($data['clock_in_time'])) {
            if ($attendance->original_clock_in_at === null && $attendance->clock_in_at) {
                $attendance->original_clock_in_at = $attendance->clock_in_at;
            }
            $attendance->clock_in_at = $attendance->date->copy()->setTimeFromTimeString($data['clock_in_time']);
        }

        if (! empty($data['clock_out_time'])) {
            if ($attendance->original_clock_out_at === null && $attendance->clock_out_at) {
                $attendance->original_clock_out_at = $attendance->clock_out_at;
            }
            $attendance->clock_out_at = $attendance->date->copy()->setTimeFromTimeString($data['clock_out_time']);
        }

        $attendance->corrected_by = Auth::id();
        $attendance->corrected_at = now();
        $attendance->correction_note = $data['correction_note'];
        $attendance->save();

        return back()->with('status', 'Absensi berhasil dikoreksi.');
    }

    /** Daftar user yang boleh dilihat rekapnya oleh user yang lagi login. */
    private function scopedUsers()
    {
        /** @var User $me */
        $me = Auth::user();

        if ($me->isOwner() || $me->isHrd()) {
            return User::query()->orderBy('name')->get();
        }

        // Manajer: diri sendiri + seluruh bawahan turunan.
        $all = User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'division', 'job_title', 'manager_id']);
        $byManager = $all->groupBy('manager_id');

        $ids = collect([$me->id]);
        $queue = [$me->id];

        while ($queue) {
            $currentId = array_shift($queue);
            foreach ($byManager->get($currentId, collect()) as $child) {
                if (! $ids->contains($child->id)) {
                    $ids->push($child->id);
                    $queue[] = $child->id;
                }
            }
        }

        return $all->whereIn('id', $ids)->sortBy('name')->values();
    }
}