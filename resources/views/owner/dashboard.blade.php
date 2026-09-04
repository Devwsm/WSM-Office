{{--
    owner/dashboard.blade.php
    ---------------------------------------------------------------------
    Shell/placeholder (Fase 0). Isi tiap kartu tiap modul terkait selesai.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Dashboard', 'navActive' => 'dashboard'])

@section('content')
    <div class="mb-6">
        <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Dashboard</h2>
        <p class="mt-1 text-[15px] text-muted">Ringkasan operasional WSM Office System.</p>
    </div>

    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-wsm-blue">
            <span class="stat-wsm-label">Kehadiran Hari Ini</span>
            <div>
                <strong class="stat-wsm-value">—</strong>
                <p class="stat-wsm-note mt-1">Data aktif mulai Fase 4</p>
            </div>
        </div>
        <div class="stat-wsm-yellow">
            <span class="stat-wsm-label">Pengajuan Pending</span>
            <div>
                <strong class="stat-wsm-value">—</strong>
                <p class="stat-wsm-note mt-1">Data aktif mulai Fase 5</p>
            </div>
        </div>
        <div class="stat-wsm-green">
            <span class="stat-wsm-label">Tugas Berjalan</span>
            <div>
                <strong class="stat-wsm-value">—</strong>
                <p class="stat-wsm-note mt-1">Data aktif mulai Fase 7</p>
            </div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Kontrak Akan Habis</span>
            <div>
                <strong class="stat-wsm-value">—</strong>
                <p class="stat-wsm-note mt-1">Data aktif mulai Fase 9</p>
            </div>
        </div>
    </div>
@endsection
