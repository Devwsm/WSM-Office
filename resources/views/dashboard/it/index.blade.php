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
        <a href="{{ route('dashboard.it.presence.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Monitor
            Login</a>
        @if (auth()->user()->canManageModule('it'))
            <a href="{{ route('dashboard.it.password-resets.index') }}"
                class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Reset
                Password</a>
        @endif
    </div>

    @php
        $fieldClass = 'rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink';
    @endphp
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari aksi, detail, nama, atau IP..."
            class="{{ $fieldClass }} w-full max-w-xs sm:w-auto">
        <select name="actor" class="{{ $fieldClass }}">
            <option value="">Semua pelaku</option>
            <option value="system" @selected($actor === 'system')>Sistem / tanpa login</option>
            @foreach ($actors as $person)
                <option value="{{ $person->id }}" @selected((string) $actor === (string) $person->id)>{{ $person->name }}</option>
            @endforeach
        </select>
        <select name="area" class="{{ $fieldClass }}">
            <option value="">Semua area</option>
            @foreach ($areas as $name)
                <option value="{{ $name }}" @selected($area === $name)>{{ $name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $from }}" class="{{ $fieldClass }}" title="Dari tanggal">
        <input type="date" name="to" value="{{ $to }}" class="{{ $fieldClass }}"
            title="Sampai tanggal">
        <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Filter</button>
        @if ($filtered)
            <a href="{{ route('dashboard.it.index') }}" class="text-[11px] font-extrabold text-muted">Reset</a>
        @endif
    </form>

    @if ($logs->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">
                @if ($filtered)
                    Gak ada log yang cocok dengan filter.
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
                            @if ($log->area)
                                <span
                                    class="ml-1.5 rounded-full bg-[#eeeae3] px-2 py-0.5 text-[9px] font-black text-[#4e4a43]">{{ $log->area }}</span>
                            @endif
                            @if ($log->detail)
                                <p class="mt-1 wrap-break-word text-xs text-ink">{{ $log->detail }}</p>
                            @endif
                        </div>
                        <div class="flex-none text-right text-[10px] text-muted">
                            <span class="block">{{ $log->created_at->translatedFormat('d M Y, H:i:s') }}</span>
                            @if ($log->ip_address)
                                <span class="block">IP {{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
@endsection
