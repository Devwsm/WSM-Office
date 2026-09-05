<?php

/**
 * routes/web.php
 * ---------------------------------------------------------------------
 * Route dikelompokkan per role. Nambah halaman baru? Taruh di grup role
 * yang sesuai, jangan lepas di luar grup.
 * ---------------------------------------------------------------------
 */

use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\OrganizationController;
use App\Http\Controllers\Employee\HomeController;
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
    // TODO Fase 4: history absensi, Fase 5: request izin/cuti
});

// --- Manajer only ---
Route::middleware(['auth', 'role:manajer,owner'])->prefix('manajer')->name('manajer.')->group(function () {
    // TODO Fase 3: team-approval, Fase 1: team-overview
});

// --- Owner only ---
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Fase 2: Manajemen Karyawan & Struktur Organisasi ---
    Route::resource('employees', EmployeeController::class)->except(['show'])->parameters(['employees' => 'employee']);
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::get('/organisasi', [OrganizationController::class, 'index'])->name('organization');

    // TODO Fase 4-12: attendance, requests, mom, memos, projects,
    // kpi, contracts, payroll, budgeting, royalty, settings
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