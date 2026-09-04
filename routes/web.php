<?php

/**
 * routes/web.php
 * ---------------------------------------------------------------------
 * Route dikelompokkan per role. Nambah halaman baru? Taruh di grup role
 * yang sesuai, jangan lepas di luar grup.
 * ---------------------------------------------------------------------
 */

use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Employee\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));
require __DIR__ . '/auth.php';

// --- Karyawan & Manajer (Manajer tetap karyawan) ---
Route::middleware(['auth', 'role:karyawan,manajer,owner'])
    ->prefix('app')
    ->name('employee.')
    ->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        // TODO Fase 2: history, Fase 3: request, Fase 1: profile
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
        // TODO Fase 1-12: employees, attendance, requests, mom, memos,
        // projects, kpi, contracts, payroll, budgeting, royalty, settings
    });