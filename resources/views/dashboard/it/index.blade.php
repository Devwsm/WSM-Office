{{--
    dashboard/it/index.blade.php
    ---------------------------------------------------------------------
    Fase 15 — listing AuditLog, READ-ONLY sepenuhnya (gak ada tombol
    tambah/edit/hapus sama sekali di halaman ini, beda dari modul lain
    — lihat catatan scope di AuditLogController). Landing modul 'it'
    (kartu di /dashboard ngarah ke sini).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'IT — Audit Log', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Audit Log</h2>
            <p class="mt-1 text-[13px] text-muted">Jejak aktivitas sistem — siapa melakukan apa, kapan.</p>
        </div>
    </div>

    {{-- Tab ke System Changelog — "IT" punya 2 sub-halaman: Audit Log
         (ini, read-only) dan System Changelog (CRUD catatan rilis). --}}
    <div class="mb-5 flex gap-2">
        <span class="rounded-2xl bg-ink px-3.5 py-2 text-[11px] font-extrabold text-white">Audit Log</span>
        <a href="{{ route('dashboard.it.changelog.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">System
            Changelog</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari aksi, detail, atau nama..."
            class="w-full max-w-xs rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink sm:w-auto">
        <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Cari</button>
        @if ($search)
            <a href="{{ route('dashboard.it.index') }}" class="text-[11px] font-extrabold text-muted">Reset</a>
        @endif
    </form>

    @if ($logs->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">
                @if ($search)
                    Gak ada log yang cocok dengan pencarian.
                @else
                    Belum ada aktivitas tercatat.
                @endif
            </p>
        </div>
    @else
        <div class="grid gap-2.5">
            @foreach ($logs as $log)
                <div class="rounded-wsm border border-line bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="text-sm">{{ $log->action }}</strong>
                            <span class="ml-1.5 text-[11px] font-bold text-muted">oleh {{ $log->actorName() }}</span>
                            @if ($log->detail)
                                <p class="mt-1 text-xs text-ink">{{ $log->detail }}</p>
                            @endif
                        </div>
                        <span
                            class="flex-none text-[10px] text-muted">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
@endsection
