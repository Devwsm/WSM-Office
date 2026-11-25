{{--
    dashboard/it/index.blade.php
    ---------------------------------------------------------------------
    Fase 15 — listing AuditLog, READ-ONLY sepenuhnya (gak ada tombol
    tambah/edit/hapus sama sekali di halaman ini, beda dari modul lain
    — lihat catatan scope di AuditLogController). Landing modul 'it'
    (kartu di /dashboard ngarah ke sini).

    Mobile (2026-11-25): halaman tidak boleh bisa digeser ke samping. Filter
    ditumpuk penuh lebar di HP (tanggal dua kolom), tiap catatan bertumpuk
    (aksi → detail → waktu) dan teks panjang (URL, email) dipatahkan
    dengan wrap-anywhere. Dari sm ke atas tampilannya sama seperti sebelumnya.
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
    @include('dashboard.it._tabs', ['active' => 'audit'])

    @php
        $fieldClass =
            'min-w-0 w-full rounded-2xl border border-line bg-white px-3.5 py-2.5 text-[13px] font-bold text-ink sm:w-auto sm:py-2 sm:text-[11px]';
    @endphp
    <form method="GET" class="mb-4 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari aksi, detail, nama, atau IP..."
            class="{{ $fieldClass }} col-span-2 sm:max-w-xs">
        <select name="actor" class="{{ $fieldClass }} col-span-2">
            <option value="">Semua pelaku</option>
            <option value="system" @selected($actor === 'system')>Sistem / tanpa login</option>
            @foreach ($actors as $person)
                <option value="{{ $person->id }}" @selected((string) $actor === (string) $person->id)>{{ $person->name }}</option>
            @endforeach
        </select>
        <select name="area" class="{{ $fieldClass }} col-span-2">
            <option value="">Semua area</option>
            @foreach ($areas as $name)
                <option value="{{ $name }}" @selected($area === $name)>{{ $name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $from }}" class="{{ $fieldClass }}" title="Dari tanggal"
            aria-label="Dari tanggal">
        <input type="date" name="to" value="{{ $to }}" class="{{ $fieldClass }}"
            title="Sampai tanggal" aria-label="Sampai tanggal">
        <div class="col-span-2 flex items-center gap-3">
            <button type="submit" class="btn-wsm-white py-2! px-3.5! text-xs">Filter</button>
            @if ($filtered)
                <a href="{{ route('dashboard.it.index') }}" class="text-[11px] font-extrabold text-muted">Reset</a>
            @endif
        </div>
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
                <article class="min-w-0 rounded-wsm border border-line bg-white p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                <strong class="min-w-0 wrap-anywhere text-sm">{{ $log->action }}</strong>
                                <span class="text-[11px] font-bold text-muted">oleh {{ $log->actorName() }}</span>
                                @if ($log->area)
                                    <span
                                        class="rounded-full bg-[#eeeae3] px-2 py-0.5 text-[9px] font-black text-[#4e4a43]">{{ $log->area }}</span>
                                @endif
                            </div>
                            @if ($log->detail)
                                <p class="mt-1.5 wrap-anywhere text-xs leading-relaxed text-ink">{{ $log->detail }}</p>
                            @endif
                        </div>
                        <div
                            class="flex flex-wrap gap-x-3 text-[10px] text-muted sm:flex-none sm:flex-col sm:gap-x-0 sm:text-right">
                            <span>{{ $log->created_at->translatedFormat('d M Y, H:i:s') }}</span>
                            @if ($log->ip_address)
                                <span>IP {{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-5">{{ $logs->links() }}</div>
    @endif
@endsection
