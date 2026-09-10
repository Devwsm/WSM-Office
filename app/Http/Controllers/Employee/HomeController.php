<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use App\Models\Memo;
use App\Models\OfficeSetting;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\AttendanceReconciler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * HomeController (Employee)
 * ---------------------------------------------------------------------
 * Halaman utama sisi Karyawan/Manajer/HRD/Owner. Kartu kehadiran
 * fungsional sejak Fase 4; sejak Fase 5 kartu ini juga cek apakah hari
 * ini lagi ada izin/cuti yang disetujui.
 *
 * Fase 7 nambah:
 * - `AttendanceReconciler::reconcile()` dipanggil di sini juga (bukan
 *   cuma di AttendanceController) — Home biasanya halaman PERTAMA yang
 *   dibuka, jadi auto-close sesi kemarin yang lupa checkout paling
 *   kepenuhi triggernya di sini, bukan nunggu user buka Riwayat/absen
 *   dulu.
 * - `$sessions`/`$openSession`/`$canStartNewSession` — dukung
 *   multi-sesi Lapangan/Gigs. `$attendance` yang dioper ke view
 *   sekarang representasi "sesi yang relevan buat ditampilin" (sesi
 *   terbuka kalau ada, kalau enggak sesi terakhir hari ini), BUKAN
 *   lagi asumsi "1 baris = 1 hari" kayak Fase 4.
 *
 * Fase 8 nambah:
 * - Kartu "Info dari Owner" jadi interaktif (baca/sembunyi/reply) —
 *   sekarang muat SEMUA memo (bukan limit 3), soalnya kartunya sendiri
 *   yang nentuin berapa yang ditampilin (padanan `employeeMemoMarkup`
 *   di prototype: hitungan "N disembunyikan" butuh tau total, bukan
 *   cuma yang lagi kelihatan).
 * - `$teamLeavesThisMonth` — banner siapa aja yang cuti bulan ini
 *   (selain diri sendiri).
 * - Job title & divisi TIDAK dioper lewat sini — udah ada langsung di
 *   `auth()->user()->job_title`/`->division`, dipakai langsung di view.
 *
 * App Mode quick win (2026-09-09, dari audit ronde 4 di README) nambah:
 * - Milestones (Birthday & Work Anniversary + lama bekerja) — TIDAK
 *   dioper lewat sini juga, sama pola kayak job_title/divisi di atas:
 *   view manggil `auth()->user()->nextBirthdayOccurrence()`,
 *   `->nextWorkAnniversaryOccurrence()`, `->serviceDurationLabel()`
 *   langsung (lihat User model), soalnya gak butuh query tambahan.
 * - `$latestAttendance` — 5 sesi absen terakhir milik sendiri (lintas
 *   bulan, BUKAN cuma bulan berjalan kayak `attendance.history`),
 *   ditampilkan langsung di Home padanan "Latest Attendance" prototype.
 *   Pakai partial `employee.attendance._history-card` yang sama
 *   dengan halaman Riwayat penuh, biar kartunya konsisten & gak dobel
 *   kode.
 * - `$kpis` — KPI aktif milik sendiri buat kartu "My KPI". KPI-nya
 *   sendiri masih diinput manual (Fase 10 — UI Owner buat kelola KPI
 *   per karyawan belum ada), jadi kartu ini kelihatan kosong sampai
 *   ada yang diisiin lewat `tinker`/seeder atau Fase 10 selesai.
 *
 * App Mode quick win (2026-09-09, ronde 5 — dari audit README) nambah:
 * - `$teamMoments` — "Team Moments", ulang tahun/anniversary SEMUA
 *   karyawan (bukan cuma diri sendiri) dalam 45 hari ke depan, padanan
 *   `teamCelebrationMarkup()` di prototype. Beda dari Milestones
 *   (`_milestones.blade.php`) yang cuma punya diri sendiri.
 * - Paid Leave banner (`_paid-leave.blade.php`) SENGAJA tidak dioper
 *   dari sini, sama pola kayak Milestones — manggil `auth()->user()`
 *   langsung dari partial karena datanya (`annual_leave_entitlement`,
 *   `usedAnnualLeaveDays()`) sudah ada di model, gak butuh query
 *   tambahan. Catatan: banner ini pakai `annual_leave_entitlement`
 *   FLAT (sama seperti halaman Cuti yang sudah ada), BUKAN hasil
 *   proration bulanan tahun pertama seperti `leaveCycle()` di
 *   prototype — WSM-Office belum punya logic accrual bulanan itu di
 *   modul Cuti manapun, jadi disamakan ke pola yang SUDAH ada di app
 *   ini dulu (lihat README, dicatat sebagai deviasi yang disengaja).
 * ---------------------------------------------------------------------
 */
class HomeController extends Controller
{
    public function index(AttendanceReconciler $reconciler)
    {
        $reconciler->reconcile((int) Auth::id());

        $today = Carbon::today()->toDateString();

        $sessions = Attendance::sessionsFor((int) Auth::id(), $today);
        $openSession = $sessions->first(fn(Attendance $a) => $a->clock_in_at && ! $a->clock_out_at);
        $latestSession = $sessions->last();
        $attendance = $openSession ?? $latestSession;

        // Lapangan/Gigs boleh check-in sesi baru lagi setelah sesi
        // sebelumnya checkout. Kantor/WFH tetap 1 sesi/hari.
        $canStartNewSession = ! $openSession
            && ($sessions->isEmpty() || $latestSession->isMultiSessionMode());

        // Reminder kalau ada absen hari sebelumnya yang lupa di-checkout.
        // SETELAH reconcile() di atas, ini seharusnya jarang kena lagi
        // (auto-close udah nutup duluan) — tetap dicek buat jaga-jaga
        // kalau ada race condition, atau baris yang auto-close-nya
        // barusan (biar user tetap tau itu kejadian, bukan cuma
        // diam-diam ditutup sistem tanpa pemberitahuan apa pun).
        $forgottenAttendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('date', '<', $today)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->orderByDesc('date')
            ->first();

        $todayLeave = LeaveRequest::approvedFor((int) Auth::id(), $today);

        // Fase 8: siapa aja yang cuti bulan ini (selain diri sendiri) —
        // banner di Home, "team awareness" ringan. Cuma tipe cuti_tahunan
        // & izin_pribadi yang ditampilin (izin_sakit sengaja dilewatin,
        // itu privat, bukan buat diumumin ke tim).
        $teamLeavesThisMonth = LeaveRequest::query()
            ->with('user')
            ->where('user_id', '!=', Auth::id())
            ->where('status', 'disetujui')
            ->whereIn('type', ['cuti_tahunan', 'izin_pribadi'])
            ->whereYear('start_date', now()->year)
            ->whereMonth('start_date', now()->month)
            ->orderBy('start_date')
            ->get();

        // Kartu "Info dari Owner" — sengaja kelihatan buat SEMUA role
        // internal terlepas dari dashboard_access (Fase 6a), karena ini
        // pengumuman ke tim, bukan modul kerja yang butuh akses. Fase 8:
        // eager-load status baca/sembunyi MILIK USER INI SAJA (bukan
        // semua user) + thread reply-nya.
        $memos = Memo::query()
            ->with([
                'creator',
                'threadMessages',
                'reads' => fn($q) => $q->where('user_id', Auth::id()),
            ])
            ->latestFirst()
            ->get();

        // 2026-09-10 (permintaan Arga) — auto mark-read, gak ada lagi
        // tombol "Tandai Sudah Dibaca" manual di kartu "Info dari Owner".
        // SENGAJA dipanggil SETELAH $memos di atas selesai diambil (biar
        // kunjungan yang LAGI JALAN ini tetap nampilin badge UNREAD yang
        // benar ke user — dia perlu tau ada yang baru), yang berubah
        // cuma status buat kunjungan Home BERIKUTNYA. Lihat
        // Memo::markAllReadFor().
        Memo::markAllReadFor(Auth::user());

        // App Mode quick win — "Latest Attendance" langsung di Home
        // (padanan `historyCards(id,5)` di prototype), lepas dari bulan
        // yang lagi dibuka di halaman Riwayat penuh.
        $latestAttendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('date')
            ->orderByDesc('session_number')
            ->limit(5)
            ->get();

        // App Mode quick win — kartu "My KPI" (padanan `employeeKpiMarkup`).
        // 'Archived' sengaja dikecualikan (KPI lama yang udah gak
        // relevan), 'Completed' tetap ditampilkan biar kelihatan yang
        // baru aja kelar.
        $kpis = Kpi::query()
            ->where('employee_id', Auth::id())
            ->whereIn('status', ['Active', 'Completed'])
            ->orderBy('due_date')
            ->get();

        // App Mode quick win (2026-09-09, ronde 5) — "Team Moments":
        // padanan `teamCelebrationMarkup()` / `celebrationRows(45)` di
        // prototype. BEDA dari Milestones (`_milestones.blade.php`,
        // punya SENDIRI): ini ulang tahun & work anniversary SEMUA
        // karyawan dalam 45 hari ke depan, termasuk diri sendiri kalau
        // relevan — prototype juga tidak exclude viewer dari daftar ini,
        // jadi sengaja disamakan biar tidak drift dari acuan v32.
        // Butuh query lintas-user (beda dari Milestones pribadi yang
        // manggil `auth()->user()->...` langsung dari view), makanya
        // dihitung di sini, bukan di partial-nya.
        $teamMoments = User::query()
            ->orderBy('name')
            ->get()
            ->flatMap(function (User $u) {
                $rows = [];

                if (($b = $u->nextBirthdayOccurrence()) && $b['days'] <= 45) {
                    $rows[] = ['type' => 'birthday', 'user' => $u, 'date' => $b['date'], 'days' => $b['days'], 'years' => null];
                }

                if (($a = $u->nextWorkAnniversaryOccurrence()) && $a['days'] <= 45) {
                    $rows[] = ['type' => 'anniversary', 'user' => $u, 'date' => $a['date'], 'days' => $a['days'], 'years' => $a['years']];
                }

                return $rows;
            })
            ->sortBy(fn($row) => $row['date']->toDateString())
            ->take(6)
            ->values();

        // App Mode (2026-09-09) — "My Work Tracker", padanan
        // `employeeTasksMarkup()` di prototype. `pic_employee_id`
        // adalah satu-satunya kolom yang nentuin "punya siapa" —
        // `additional_pic` (string bebas) sengaja gak ikut, lihat
        // catatan lengkap di WorkTrackerController.
        $myWorkItems = WorkItem::query()
            ->where('pic_employee_id', Auth::id())
            ->with('project')
            ->orderByRaw('due_date IS NULL, due_date')
            ->get();

        $openWorkItems = $myWorkItems->where('progress', '!=', 'Done')->values();
        $doneWorkItemsCount = $myWorkItems->where('progress', '=', 'Done')->count();

        return view('employee.home', [
            'attendance' => $attendance,
            'sessions' => $sessions,
            'canStartNewSession' => $canStartNewSession,
            'forgottenAttendance' => $forgottenAttendance,
            'todayLeave' => $todayLeave,
            'officeSetting' => OfficeSetting::current(),
            'memos' => $memos,
            'teamLeavesThisMonth' => $teamLeavesThisMonth,
            'latestAttendance' => $latestAttendance,
            'kpis' => $kpis,
            'teamMoments' => $teamMoments,
            'openWorkItems' => $openWorkItems,
            'doneWorkItemsCount' => $doneWorkItemsCount,
        ]);
    }
}