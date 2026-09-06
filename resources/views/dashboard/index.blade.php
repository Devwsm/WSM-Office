{{--
    dashboard/index.blade.php
    ---------------------------------------------------------------------
    Fase 6a — landing "Dashboard" (beda dari "Kelola Tim" yang ke rekap
    absensi/approval cuti, itu tetap role-based apa adanya). Modul yang
    kelihatan murni dari User::accessLevel(), bukan role.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Dashboard', 'navActive' => 'modules'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">Dashboard</h2>
        <p class="mt-1 text-[13px] text-muted">Modul yang bisa kamu akses.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($modules as $module)
            <a href="{{ $module['route'] }}"
                class="rounded-wsm border border-line bg-white p-4.5 transition hover:border-ink">
                <div class="flex items-start justify-between gap-2">
                    <strong class="text-sm">{{ $module['label'] }}</strong>
                    <span
                        class="flex-none rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $module['level'] === 'manage' ? 'bg-ink text-white' : 'bg-[#f2f0eb] text-[#5e5951]' }}">
                        {{ $module['level'] === 'manage' ? 'Manage' : 'View' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted">{{ $module['desc'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
