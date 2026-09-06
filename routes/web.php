<?php

/**
 * routes/web.php
 * ---------------------------------------------------------------------
 * Route dikelompokkan per role. Nambah halaman baru? Taruh di grup role
 * yang sesuai, jangan lepas di luar grup.
 * ---------------------------------------------------------------------
 */

use App\Http\Controllers\Approval\LeaveRequestController as ApprovalLeaveRequestController;
use App\Http\Controllers\Approval\OvertimeRequestController as ApprovalOvertimeRequestController;
use App\Http\Controllers\Attendance\RecapController;
use App\Http\Controllers\Dashboard\DashboardController as ModuleDashboardController;
use App\Http\Controllers\Dashboard\Work\MemoController;
use App\Http\Controllers\Owner\DashboardAccessController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\OfficeSettingController;
use App\Http\Controllers\Owner\OrganizationController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\HomeController;
use App\Http\Controllers\Employee\LeaveRequestController;
use App\Http\Controllers\Employee\OvertimeRequestController;
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
});

// --- Manajer only ---
Route::middleware(['auth', 'role:manajer,owner'])->prefix('manajer')->name('manajer.')->group(function () {
    // Approval izin/cuti & lembur ada di grup 'approval.leave.'/'approval.overtime.'
    // (prefix /persetujuan) di bawah, bareng Owner — bukan di sini.
    // TODO Fase 1: team-overview
});

// --- Owner only ---
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
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

// --- HRD & Owner (Rekrutmen) ---
// Dipisah dari grup 'owner' karena HRD juga butuh akses penuh ke modul
// ini, bukan cuma Owner. Kalau nanti Manajer ikut butuh (mis. lihat
// pelamar divisinya sendiri), tambah role baru di sini, jangan taruh
// duplikat rute di grup manajer.
Route::middleware(['auth', 'role:hrd,owner'])->prefix('rekrutmen')->name('recruitment.')->group(function () {
    Route::resource('lowongan', JobOpeningController::class)->except(['show', 'destroy'])->parameters(['lowongan' => 'opening'])->names('openings');

    Route::get('/pelamar', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::get('/pelamar/{application}', [JobApplicationController::class, 'show'])->name('applications.show');
    Route::patch('/pelamar/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::get('/pelamar/{application}/convert', [JobApplicationController::class, 'convert'])->name('applications.convert');
    Route::post('/pelamar/{application}/convert', [JobApplicationController::class, 'storeConvert'])->name('applications.convert.store');
});

// --- Manajer, HRD & Owner (Rekap Absensi, Fase 4) ---
// Dipisah dari grup 'owner'/'manajer' karena dipakai bareng 3 role
// sekaligus (sama seperti pola rekrutmen di atas), dengan scope data
// berbeda per role (lihat RecapController::scopedUsers()).
Route::middleware(['auth', 'role:manajer,owner,hrd'])->prefix('absensi')->name('attendance.recap.')->group(function () {
    Route::get('/', [RecapController::class, 'index'])->name('index');
    Route::get('/{user}', [RecapController::class, 'show'])->name('show');
    Route::post('/{attendance}/koreksi', [RecapController::class, 'correct'])->name('correct');
});

// --- Manajer & Owner (Persetujuan Izin/Cuti, Fase 5) ---
// HRD SENGAJA nggak dikasih akses di sini (kesepakatan Fase 5: cuma
// Manajer & Owner yang approve/reject/cancel izin-cuti).
Route::middleware(['auth', 'role:manajer,owner'])->prefix('persetujuan')->name('approval.leave.')->group(function () {
    Route::get('/', [ApprovalLeaveRequestController::class, 'index'])->name('index');
    Route::post('/{leave}/setujui', [ApprovalLeaveRequestController::class, 'approve'])->name('approve');
    Route::post('/{leave}/tolak', [ApprovalLeaveRequestController::class, 'reject'])->name('reject');
    Route::post('/{leave}/batalkan', [ApprovalLeaveRequestController::class, 'cancel'])->name('cancel');
});

// --- Manajer & Owner (Persetujuan Lembur, Fase 7) ---
// Scope & alasan HRD-dikecualikan SAMA PERSIS grup 'approval.leave.'
// di atas — lihat Approval\OvertimeRequestController.
Route::middleware(['auth', 'role:manajer,owner'])->prefix('persetujuan-lembur')->name('approval.overtime.')->group(function () {
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
Route::middleware(['auth', 'role:karyawan,manajer,owner,hrd'])->prefix('dashboard')->name('dashboard.')->group(function () {
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
    });

    Route::get('/{module}', [ModuleDashboardController::class, 'show'])->name('show');
});