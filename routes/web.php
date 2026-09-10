<?php

/**
 * routes/web.php
 * ---------------------------------------------------------------------
 * Route dikelompokkan per role ATAU per modul dashboard_access —
 * dua-duanya masih dipakai, TAPI beda maksud (2026-09-09, lihat README
 * "Dashboard permission-based, bukan role"):
 * - `role:...` -> masih valid buat "apakah user ini jenis akun
 *   internal apa" (Owner vs staf biasa) — Owner tetap satu-satunya
 *   yang boleh masuk grup 'owner.*' (kelola karyawan, struktur
 *   organisasi, assign dashboard_access, dst), itu memang literally
 *   "Owner" sebagai konsep, bukan permission yang bisa didelegasikan.
 * - `module:xxx,view|manage` -> dashboard_access, permission per-user
 *   per-modul yang Owner assign lewat halaman "Dashboard Access".
 *   INI yang dipakai buat fitur yang DULUNYA role-gated
 *   (`role:manajer,owner,hrd` dkk) tapi sebenarnya harus bisa
 *   dicabut/dikasih per-orang, bukan blanket per-jabatan: Rekap
 *   Absensi, Persetujuan Izin/Cuti/Lembur (modul `people`), &
 *   Rekrutmen (modul `recruitment`).
 * Nambah halaman baru? Kalau dia Owner-only secara konsep, taruh di
 * grup 'owner.*'. Kalau dia bisa didelegasikan ke staf tertentu,
 * pakai `module:...`, JANGAN `role:...` — itu justru pola yang lagi
 * dibenerin di refactor ini.
 * ---------------------------------------------------------------------
 */

use App\Http\Controllers\Approval\LeaveRequestController as ApprovalLeaveRequestController;
use App\Http\Controllers\Approval\OvertimeRequestController as ApprovalOvertimeRequestController;
use App\Http\Controllers\Attendance\RecapController;
use App\Http\Controllers\Dashboard\DashboardController as ModuleDashboardController;
use App\Http\Controllers\Dashboard\DashboardLockController;
use App\Http\Controllers\Dashboard\Work\MemoController;
use App\Http\Controllers\Dashboard\Work\WorkTrackerBoardController;
use App\Http\Controllers\Owner\DashboardAccessController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\OfficeSettingController;
use App\Http\Controllers\Owner\OrganizationController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\HomeController;
use App\Http\Controllers\Employee\LeaveRequestController;
use App\Http\Controllers\Employee\MemoInteractionController;
use App\Http\Controllers\Employee\OvertimeRequestController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\WorkTrackerController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Recruitment\JobApplicationController;
use App\Http\Controllers\Recruitment\JobOpeningController;
use Illuminate\Support\Facades\Route;

// --- Publik (Fase 1) — tanpa login, siapa saja bisa akses ---
Route::name('public.')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
    Route::get('/layanan', [PageController::class, 'services'])->name('services');
    Route::get('/karir', [PageController::class, 'careers'])->name('careers');
    Route::get('/karir/{lowongan:slug}', [PageController::class, 'careerShow'])->name('careers.show');
    Route::post('/karir/{lowongan:slug}/lamar', [PageController::class, 'careerApply'])->middleware('throttle:5,1')->name('careers.apply');
    Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
    Route::post('/kontak', [PageController::class, 'storeContact'])->middleware('throttle:5,1')->name('contact.store');
});

require __DIR__ . '/auth.php';

// --- Karyawan & Manajer (Manajer tetap karyawan; HRD juga staf internal) ---
Route::middleware(['auth', 'role:karyawan,manajer,owner,hrd'])->prefix('app')->name('employee.')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // --- Fase 4 & 7: Absensi (self-service, berlaku buat semua role internal) ---
    Route::post('/absensi/masuk', [AttendanceController::class, 'clockIn'])->middleware('throttle:10,1')->name('attendance.clockIn');
    Route::post('/absensi/pulang', [AttendanceController::class, 'clockOut'])->middleware('throttle:10,1')->name('attendance.clockOut');
    Route::get('/riwayat', [AttendanceController::class, 'history'])->name('attendance.history');

    // --- Fase 5: Pengajuan Izin/Cuti (self-service) ---
    Route::get('/pengajuan', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::post('/pengajuan', [LeaveRequestController::class, 'store'])->middleware('throttle:10,1')->name('leave.store');
    Route::post('/pengajuan/{leave}/batalkan', [LeaveRequestController::class, 'cancel'])->name('leave.cancel');

    // --- Fase 7: Pengajuan Lembur (self-service) ---
    Route::get('/lembur', [OvertimeRequestController::class, 'index'])->name('overtime.index');
    Route::post('/lembur', [OvertimeRequestController::class, 'store'])->middleware('throttle:10,1')->name('overtime.store');
    Route::post('/lembur/{overtime}/batalkan', [OvertimeRequestController::class, 'cancel'])->name('overtime.cancel');

    // --- Fase 9 (2026-09-09): "Shared Calendar" — padanan
    // openSharedWorkloadCalendar() di prototype. Lihat
    // WorkTrackerController buat catatan lengkap. "My Work Tracker"
    // sendiri (padanan employeeTasksMarkup()) TIDAK punya route
    // sendiri — dia embedded langsung di /home (sama pola Milestones/
    // My KPI/Team Moments), dihitung di HomeController.
    Route::get('/kalender-tim', [WorkTrackerController::class, 'calendar'])->name('workTracker.calendar');

    // --- Tab Profile (bottom-nav) — sebelumnya placeholder "TODO Fase 1" ---
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // --- Fase 8: interaksi Memo Forum dari kartu "Info dari Owner" (Home) ---
    // Sengaja di grup role yang sama kayak home/profile di atas (SEMUA
    // role internal), bukan digabung ke middleware 'module:work,*' —
    // memo ini pengumuman ke tim, bukan modul kerja (sama alasan kenapa
    // $memos di HomeController gak dicek dashboard_access).
    Route::post('/memo/{memo}/baca', [MemoInteractionController::class, 'toggleRead'])->name('memo.toggleRead');
    Route::post('/memo/{memo}/sembunyikan', [MemoInteractionController::class, 'toggleHidden'])->name('memo.toggleHidden');
    Route::post('/memo/{memo}/balas', [MemoInteractionController::class, 'reply'])->middleware('throttle:15,1')->name('memo.reply');
});

// --- Lock Dashboard (2026-09-06) ---
// SENGAJA middleware-nya cuma ['auth', 'role:...'] — TANPA
// 'dashboard.unlocked' — karena rute inilah yang jadi jalan keluar
// pas lagi ke-lock. Kalau ikut dipasangi 'dashboard.unlocked', orang
// yang lagi terkunci gak akan pernah bisa buka layar unlock-nya
// sendiri (infinite redirect).
// 2026-09-09 — role di sini DILEBARIN ke 'karyawan' juga (dulu cuma
// manajer,owner,hrd). Sejak refactor "permission bukan role", siapa
// pun role-nya bisa masuk layouts.app kalau di-assign dashboard_access
// ke modul apa pun (Aldora contohnya, role 'karyawan' biasa, punya
// akses modul Work Control) — jadi dia juga harus bisa pakai
// "Kunci Dashboard" buat sesi kerjanya, bukan cuma role tinggi.
Route::middleware(['auth', 'role:karyawan,manajer,owner,hrd'])->prefix('dashboard-lock')->name('dashboard.lock.')->group(function () {
    Route::post('/kunci', [DashboardLockController::class, 'lock'])->name('lock');
    Route::get('/', [DashboardLockController::class, 'show'])->name('show');
    Route::post('/buka', [DashboardLockController::class, 'unlock'])->middleware('throttle:10,1')->name('unlock');
    Route::post('/batal', [DashboardLockController::class, 'cancel'])->name('cancel');
});

// --- Manajer only ---
Route::middleware(['auth', 'role:manajer,owner', 'dashboard.unlocked'])->prefix('manajer')->name('manajer.')->group(function () {
    // Approval izin/cuti & lembur ada di grup 'approval.leave.'/'approval.overtime.'
    // (prefix /persetujuan) di bawah, bareng Owner — bukan di sini.
    // TODO Fase 1: team-overview
});

// --- Owner only ---
Route::middleware(['auth', 'role:owner', 'dashboard.unlocked'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Fase 2: Manajemen Karyawan & Struktur Organisasi ---
    Route::resource('employees', EmployeeController::class)->except(['show'])->parameters(['employees' => 'employee']);
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::get('/organisasi', [OrganizationController::class, 'index'])->name('organization');

    // --- Fase 6a: Dashboard Access (permission per-user per-modul) ---
    // Sengaja cuma di grup role:owner (bukan manajer,owner,hrd kayak
    // fitur lain) — kesepakatan Fase 6: cuma Owner yang boleh
    // assign/ubah akses modul orang lain.
    Route::get('/employees/{employee}/akses', [DashboardAccessController::class, 'edit'])->name('employees.access.edit');
    Route::patch('/employees/{employee}/akses', [DashboardAccessController::class, 'update'])->name('employees.access.update');

    // --- Fase 7: Pengaturan Kantor (geo & jam kerja normal) ---
    // Sebelumnya cuma bisa diubah lewat OfficeSettingSeeder (developer
    // yang ubah + deploy ulang) — sekarang Owner bisa ubah sendiri.
    Route::get('/pengaturan-kantor', [OfficeSettingController::class, 'edit'])->name('office-settings.edit');
    Route::patch('/pengaturan-kantor', [OfficeSettingController::class, 'update'])->name('office-settings.update');

    // Fase 4 (rekap absensi) ada di grup 'attendance.recap.' di bawah,
    // bareng Manajer & HRD — Fase 5 & 7 (approval izin/cuti/lembur) ada
    // di grup 'approval.leave.'/'approval.overtime.' bareng Manajer —
    // bukan di sini. Fase 6a (dashboard_access) ada di atas
    // ('employees.access.*'). Fase 6b (MoM & Memo) ada di grup
    // 'dashboard.work.' di bawah.
    // TODO Fase 8-18: lihat README bagian "Roadmap Modul & Role"
});

// --- Rekrutmen (2026-09-09: permission bukan role, lihat README) ---
// SEBELUMNYA `role:hrd,owner` — siapa pun berrole HRD otomatis buka
// modul ini, gak bisa dicabut per-orang. SEKARANG `module:recruitment,*`
// (dashboard_access) — Owner assign satu-satu, role 'hrd' cuma label
// jabatan lagi, gak otomatis buka apa-apa (lihat migration backfill
// `backfill_dashboard_access_for_manajer_hrd` buat HRD/Manajer
// existing biar gak kehilangan akses pas migration ini jalan). Ditulis
// manual (bukan Route::resource() polos) biar bisa split view/manage
// per-route — pola sama persis grup 'dashboard.work.' di bawah.
Route::middleware(['auth', 'module:recruitment,view', 'dashboard.unlocked'])->prefix('rekrutmen')->name('recruitment.')->group(function () {
    Route::get('/lowongan', [JobOpeningController::class, 'index'])->name('openings.index');
    Route::get('/pelamar', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::get('/pelamar/{application}', [JobApplicationController::class, 'show'])->name('applications.show');
});
Route::middleware(['auth', 'module:recruitment,manage', 'dashboard.unlocked'])->prefix('rekrutmen')->name('recruitment.')->group(function () {
    Route::get('/lowongan/create', [JobOpeningController::class, 'create'])->name('openings.create');
    Route::post('/lowongan', [JobOpeningController::class, 'store'])->name('openings.store');
    Route::get('/lowongan/{opening}/edit', [JobOpeningController::class, 'edit'])->name('openings.edit');
    Route::match(['put', 'patch'], '/lowongan/{opening}', [JobOpeningController::class, 'update'])->name('openings.update');
    Route::patch('/pelamar/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::get('/pelamar/{application}/convert', [JobApplicationController::class, 'convert'])->name('applications.convert');
    Route::post('/pelamar/{application}/convert', [JobApplicationController::class, 'storeConvert'])->name('applications.convert.store');
});

// --- Rekap Absensi (Fase 4) — permission bukan role, 2026-09-09 ---
// SEBELUMNYA `role:manajer,owner,hrd` — siapa pun berrole itu otomatis
// lihat rekap SEMUA karyawan yang scopedUsers() balikin, gak peduli
// beneran ditugasin ngurus itu atau enggak (ini PERSIS bug yang
// dilaporkan: Aldora — role 'karyawan' biasa — kelihatan link
// "Absensi" di sidebar walau gak ada dashboard_access ke modul apa
// pun soal itu, gara-gara link-nya dulu malah TANPA @if sama sekali
// di layouts/app.blade.php, ketebus asal masuk ke layout itu lewat
// modul LAIN yang dia punya akses beneran). SEKARANG `module:people,view`
// — Owner assign satu-satu lewat halaman "Dashboard Access" yang udah
// ada, role cuma label jabatan. `correct` (koreksi jam) butuh
// `module:people,manage` — level 'view' cuma buat lihat, bukan edit.
// scopedUsers() di RecapController TIDAK diubah (masih Owner/HRD-role
// -> semua, else -> diri sendiri + bawahan turunan) — karyawan biasa
// yang di-assign `people` tapi bukan atasan siapa pun otomatis cuma
// lihat data dirinya sendiri, aman, gak perlu diubah.
Route::middleware(['auth', 'module:people,view', 'dashboard.unlocked'])->prefix('absensi')->name('attendance.recap.')->group(function () {
    Route::get('/', [RecapController::class, 'index'])->name('index');
    Route::get('/{user}', [RecapController::class, 'show'])->name('show');
});
Route::middleware(['auth', 'module:people,manage', 'dashboard.unlocked'])->prefix('absensi')->name('attendance.recap.')->group(function () {
    Route::post('/{attendance}/koreksi', [RecapController::class, 'correct'])->name('correct');
});

// --- Persetujuan Izin/Cuti (Fase 5) — permission bukan role, 2026-09-09 ---
// SEBELUMNYA `role:manajer,owner` buat GERBANG MASUK layarnya. SEKARANG
// `module:people,view` (dashboard_access) — sama modul dengan Rekap
// Absensi (persis prototype: "People & Leave" 1 modul buat direktori +
// leave monitoring). PENTING, INI TIDAK BERUBAH: siapa yang BOLEH
// approve/reject/cancel request TERTENTU tetap 100% relasi
// `manager_id` (LeaveRequestController::canDecide(), tidak disentuh
// sama sekali) — persis disclaimer di prototype: "Dashboard access
// tidak mengubah authority approval. Approval tetap mengikuti direct
// supervisor." `module:people` di sini CUMA ngatur siapa yang BISA
// MASUK layar Persetujuan sama sekali, bukan siapa yang boleh mutusin.
// HRD MASIH sengaja tidak ikut grup ini (kesepakatan Fase 5 lama tetap
// berlaku, gak berubah gara-gara refactor ini) — 'people' access HRD
// buat approval CUMA relevan kalau HRD juga manager_id langsung
// seseorang, kasus yang jarang tapi valid.
Route::middleware(['auth', 'module:people,view', 'dashboard.unlocked'])->prefix('persetujuan')->name('approval.leave.')->group(function () {
    Route::get('/', [ApprovalLeaveRequestController::class, 'index'])->name('index');
    Route::post('/{leave}/setujui', [ApprovalLeaveRequestController::class, 'approve'])->name('approve');
    Route::post('/{leave}/tolak', [ApprovalLeaveRequestController::class, 'reject'])->name('reject');
    Route::post('/{leave}/batalkan', [ApprovalLeaveRequestController::class, 'cancel'])->name('cancel');
});

// --- Persetujuan Lembur (Fase 7) — permission bukan role, 2026-09-09 ---
// Sama persis alasan & pola grup 'approval.leave.' di atas — lihat
// komentar di situ. OvertimeRequestController::canDecide() (relasi
// manager_id) TIDAK disentuh.
Route::middleware(['auth', 'module:people,view', 'dashboard.unlocked'])->prefix('persetujuan-lembur')->name('approval.overtime.')->group(function () {
    Route::get('/', [ApprovalOvertimeRequestController::class, 'index'])->name('index');
    Route::post('/{overtime}/setujui', [ApprovalOvertimeRequestController::class, 'approve'])->name('approve');
    Route::post('/{overtime}/tolak', [ApprovalOvertimeRequestController::class, 'reject'])->name('reject');
    Route::post('/{overtime}/batalkan', [ApprovalOvertimeRequestController::class, 'cancel'])->name('cancel');
});

// --- Semua role internal (Dashboard modul, Fase 6a) ---
// SENGAJA dibuka buat role:karyawan,manajer,owner,hrd (bukan cuma
// role tinggi) — akses beneran dicek per-modul di controller lewat
// User::canViewModule(), bukan lewat middleware role di sini. Jadi
// karyawan biasa yang di-assign akses ke 1 modul saja tetap bisa
// masuk /dashboard, cuma modul itu doang yang kelihatan.
Route::middleware(['auth', 'role:karyawan,manajer,owner,hrd', 'dashboard.unlocked'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [ModuleDashboardController::class, 'index'])->name('index');

    // --- Fase 6b: Work Control -> MoM & Memo ---
    // PENTING: grup ini harus terdaftar SEBELUM route '/{module}' generik
    // di bawah, soalnya Laravel matching route dari atas ke bawah —
    // kalau kebalik, '/dashboard/work' bakal kena ke
    // ModuleDashboardController::show('work') (placeholder), bukan ke
    // MemoController. 6 modul lain (budget, royalty, kpi, people,
    // contracts, payroll) belum punya controller sendiri, jadi masih
    // lewat placeholder generik itu.
    Route::prefix('work')->name('work.')->group(function () {
        Route::get('/', [MemoController::class, 'index'])->middleware('module:work,view')->name('index');
        Route::get('/create', [MemoController::class, 'create'])->middleware('module:work,manage')->name('create');
        Route::post('/', [MemoController::class, 'store'])->middleware('module:work,manage')->name('store');
        Route::get('/{memo}/edit', [MemoController::class, 'edit'])->middleware('module:work,manage')->name('edit');
        Route::patch('/{memo}', [MemoController::class, 'update'])->middleware('module:work,manage')->name('update');
        Route::delete('/{memo}', [MemoController::class, 'destroy'])->middleware('module:work,manage')->name('destroy');
        Route::post('/{memo}/balas', [MemoController::class, 'reply'])->middleware(['module:work,manage', 'throttle:15,1'])->name('reply');

        // --- Fase 9 lanjutan: Work Tracker board (audit ronde 6, 2026-09-09) ---
        // Sub-halaman lain di modul 'work' (Memo Forum di atas cuma
        // salah satu tab). 'view' bisa lihat board, 'manage' baru bisa
        // drag-drop/CRUD — sama pola gate view/manage kayak MemoController.
        Route::prefix('tracker')->name('tracker.')->group(function () {
            Route::get('/', [WorkTrackerBoardController::class, 'index'])->middleware('module:work,view')->name('index');
            Route::post('/proyek', [WorkTrackerBoardController::class, 'storeProject'])->middleware('module:work,manage')->name('projects.store');
            Route::patch('/proyek/{project}', [WorkTrackerBoardController::class, 'updateProject'])->middleware('module:work,manage')->name('projects.update');
            Route::delete('/proyek/{project}', [WorkTrackerBoardController::class, 'destroyProject'])->middleware('module:work,manage')->name('projects.destroy');
            Route::post('/task', [WorkTrackerBoardController::class, 'storeItem'])->middleware('module:work,manage')->name('items.store');
            Route::patch('/task/{item}', [WorkTrackerBoardController::class, 'updateItem'])->middleware('module:work,manage')->name('items.update');
            Route::delete('/task/{item}', [WorkTrackerBoardController::class, 'destroyItem'])->middleware('module:work,manage')->name('items.destroy');
            Route::patch('/task/{item}/progress', [WorkTrackerBoardController::class, 'updateProgress'])->middleware('module:work,manage')->name('items.progress');
        });
    });

    Route::get('/{module}', [ModuleDashboardController::class, 'show'])->name('show');
});