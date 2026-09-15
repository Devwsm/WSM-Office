{{--
    dashboard/module.blade.php
    ---------------------------------------------------------------------
    Fase 6a — placeholder generik. Semua 10 modul dashboard_access
    (work, kpi, contracts, payroll, budget, royalty, legal, it, people,
    recruitment) SEKARANG SUDAH punya halaman beneran masing-masing —
    modul ini secara konsep sudah tidak perlu dipakai lagi.

    ⚠️ BUG (belum diperbaiki, 2026-09-15 audit): `DashboardController::index()`
    cuma nge-map 8 dari 10 modul (work/kpi/contracts/payroll/budget/
    royalty/legal/it) ke route aslinya. `people` & `recruitment` TIDAK
    ada di match()-nya, jadi kalau user klik card modul itu dari
    /dashboard (bukan dari sidebar), dia tetap kelempar ke sini —
    padahal halaman asli mereka (/absensi, /rekrutmen/lowongan) sudah
    ada dan jalan. Perbaikan seharusnya nambah 'people' & 'recruitment'
    ke match() di DashboardController::index(), bukan ngubah view ini.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => $label, 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
        <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">{{ $label }}</h2>
        <p class="mt-1 text-[13px] text-muted">{{ $desc }}</p>
    </div>

    <div class="card-wsm-white text-center">
        <p class="text-xs text-muted">
            Modul ini belum dibangun — akses kamu di sini sudah "{{ $level === 'manage' ? 'Manage' : 'View' }}",
            tinggal nunggu konten (lihat roadmap Fase 6–12 di README project).
        </p>
    </div>
@endsection
