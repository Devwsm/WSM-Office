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
use Illuminate\Support\Facades\Route;

// --- Publik (Fase 1) — tanpa login, siapa saja bisa akses ---
Route::name('public.')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
    Route::get('/layanan', [PageController::class, 'services'])->name('services');
    Route::get('/karir', [PageController::class, 'careers'])->name('careers');
    // TODO Fase 3: GET /karir/{lowongan} detail + form lamaran publik
    Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
    Route::post('/kontak', [PageController::class, 'storeContact'])
        ->middleware('throttle:5,1')
        ->name('contact.store');
});

require __DIR__ . '/auth.php';

// --- Karyawan & Manajer (Manajer tetap karyawan; HRD juga staf internal) ---
Route::middleware(['auth', 'role:karyawan,manajer,owner,hrd'])
    ->prefix('app')
    ->name('employee.')
    ->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        // TODO Fase 4: history absensi, Fase 5: request izin/cuti
    });

// --- Manajer only ---
Route::middleware(['auth', 'role:manajer,owner'])
    ->prefix('manajer')
    ->name('manajer.')
    ->group(function () {
        // TODO Fase 3: team-approval, Fase 1: team-overview
    });

// --- Owner only ---
Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // --- Fase 2: Manajemen Karyawan & Struktur Organisasi ---
        Route::resource('employees', EmployeeController::class)
            ->except(['show'])
            ->parameters(['employees' => 'employee']);
        Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])
            ->name('employees.restore');
        Route::get('/organisasi', [OrganizationController::class, 'index'])->name('organization');

        // TODO Fase 3-12: rekrutmen, attendance, requests, mom, memos,
        // projects, kpi, contracts, payroll, budgeting, royalty, settings
    });