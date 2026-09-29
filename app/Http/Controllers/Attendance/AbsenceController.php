<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAbsence;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AbsenceController
 * ---------------------------------------------------------------------
 * Menandai / membatalkan "Absen (A)" (tidak masuk tanpa keterangan) per
 * karyawan per tanggal — hanya hari yang ditandai di sini yang dipotong
 * Payroll (opsi B). Gerbang: modul `people` level manage (lihat
 * routes/web.php), scope orangnya sama dengan Rekap Absensi
 * (`User::visibleAttendanceUserIds()`).
 *
 * Terkunci: kalau payroll bulan itu sudah Final/Dibayar, penanda tidak bisa
 * ditambah/dibatalkan — Owner harus membuka kembali payroll-nya dulu.
 *
 * Tidak boleh ditandai: tanggal masa depan, tanggal yang sudah ada
 * clock-in-nya (koreksi jamnya lewat "Koreksi"), dan tanggal yang
 * tercakup izin/cuti/sakit yang disetujui.
 * ---------------------------------------------------------------------
 */
class AbsenceController extends Controller
{
    public function store(Request $request, User $user)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($me->visibleAttendanceUserIds()->contains($user->id), 403, 'Kamu tidak punya akses ke absensi karyawan ini.');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'date.before_or_equal' => 'Tanggal absen tidak boleh di masa depan.',
            'note.required' => 'Keterangan wajib diisi.',
        ]);

        if ($locked = $this->lockedPayroll($user->id, $data['date'])) {
            return back()->with('error', $locked);
        }

        if (AttendanceAbsence::query()->where('user_id', $user->id)->whereDate('date', $data['date'])->exists()) {
            return back()->with('error', "{$user->name} sudah ditandai absen pada tanggal itu.");
        }

        if (Attendance::query()->where('user_id', $user->id)->whereDate('date', $data['date'])->whereNotNull('clock_in_at')->exists()) {
            return back()->with('error', "{$user->name} punya catatan masuk pada tanggal itu, jadi tidak bisa ditandai absen. Kalau jamnya keliru, pakai Koreksi.");
        }

        $onLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $data['date'])
            ->whereDate('end_date', '>=', $data['date'])
            ->exists();

        if ($onLeave) {
            return back()->with('error', "{$user->name} punya izin/cuti/sakit yang disetujui pada tanggal itu, jadi tidak bisa ditandai absen.");
        }

        AttendanceAbsence::create([
            'user_id' => $user->id,
            'date' => $data['date'],
            'note' => $data['note'],
            'marked_by' => $me->id,
        ]);

        AuditLog::record('Absen ditandai', "{$user->name} ditandai Absen pada {$data['date']} oleh {$me->name}: {$data['note']}", $me);

        return back()->with('status', "{$user->name} ditandai Absen pada tanggal {$data['date']}. Hari ini akan dipotong di payroll.");
    }

    public function destroy(AttendanceAbsence $absence)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($me->visibleAttendanceUserIds()->contains($absence->user_id), 403, 'Kamu tidak punya akses ke absensi karyawan ini.');

        $name = $absence->user->name;
        $date = $absence->date->toDateString();

        if ($locked = $this->lockedPayroll($absence->user_id, $date)) {
            return back()->with('error', $locked);
        }

        $absence->delete();

        AuditLog::record('Penanda absen dibatalkan', "Penanda Absen {$name} pada {$date} dibatalkan oleh {$me->name}.", $me);

        return back()->with('status', "Penanda Absen {$name} pada tanggal {$date} dibatalkan.");
    }

    /** Pesan error kalau payroll bulan dari tanggal ini sudah Final/Dibayar, null kalau masih boleh diubah. */
    private function lockedPayroll(int $userId, string $date): ?string
    {
        $locked = PayrollRecord::query()
            ->where('user_id', $userId)
            ->where('period', substr($date, 0, 7))
            ->whereIn('status', ['finalized', 'paid'])
            ->first();

        return $locked
            ? "Payroll {$locked->periodLabel()} sudah {$locked->statusLabel()}, jadi penanda absen bulan itu terkunci. Minta Owner membuka kembali payroll-nya dulu."
            : null;
    }
}