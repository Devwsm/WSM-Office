{{--
    employee/home.blade.php
    ---------------------------------------------------------------------
    Shell/placeholder (Fase 0). Tombol clock-in/out belum fungsional —
    logic-nya (foto + geolocation) masuk di Fase 2.
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Home', 'navActive' => 'home'])

@section('content')
    <div class="employee-hero mb-7">
        <h1 class="text-[44px] font-black leading-[0.98] tracking-tight">Halo, {{ explode(' ', auth()->user()->name)[0] }} 👋
        </h1>
        <p class="mt-1 text-[15px] text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="mb-3.5 rounded-[28px] bg-brand-blue p-6 text-white">
        <span class="text-[11px] font-black uppercase tracking-wide text-white/75">Status Kehadiran</span>
        <p class="mt-3 text-2xl font-black">Belum absen</p>
        <p class="mt-1 text-xs text-white/70">Tombol absen aktif mulai Fase 2.</p>
        <button disabled
            class="mt-5 w-full rounded-[22px] bg-black/15 py-4 text-sm font-extrabold text-white/70 disabled:cursor-not-allowed">
            Absen Masuk (aktif di Fase 2)
        </button>
    </div>

    <div class="card-wsm-white">
        <p class="mb-1.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Info dari Owner</p>
        <p class="text-xs text-muted">Belum ada memo — fitur aktif di Fase 6.</p>
    </div>
@endsection
