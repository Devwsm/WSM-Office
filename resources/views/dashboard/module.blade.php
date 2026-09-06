{{--
    dashboard/module.blade.php
    ---------------------------------------------------------------------
    Fase 6a — placeholder generik. Modul 'work' TIDAK lewat sini lagi
    sejak Fase 6b (punya controller & view sendiri di dashboard/work/*)
    — lihat urutan route di routes/web.php. 6 modul sisanya masih
    nunggu dibangun satu-satu di fase berikutnya (lihat README "Peta
    Fase 6–12").
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
