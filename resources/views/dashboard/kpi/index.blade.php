{{--
    dashboard/kpi/index.blade.php
    ---------------------------------------------------------------------
    Fase 10 — listing KPI seluruh tim. Tombol tambah/edit/hapus cuma
    kelihatan kalau canManageModule('kpi') — sama pola kayak modul
    'work'. Style kartu (badge/progress bar) numpang ulang
    achievementBadgeClass()/progressBarClass() dari Kpi model, PERSIS
    sama yang dipakai kartu "My KPI" di Home — biar 1 sumber warna,
    bukan didefinisiin ulang di sini.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'KPI & Performance', 'navActive' => 'modules'])

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-[11px] font-extrabold text-muted">← Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">KPI & Performance</h2>
            <p class="mt-1 text-[13px] text-muted">KPI seluruh tim, tampil juga di kartu "My KPI" masing-masing
                karyawan.</p>
        </div>
        @if (auth()->user()->canManageModule('kpi'))
            <a href="{{ route('dashboard.kpi.create') }}" class="btn-wsm-black">+ Tambah KPI</a>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="employee_id" onchange="this.form.submit()"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink">
            <option value="">Semua Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected($selectedEmployeeId === $employee->id)>
                    {{ $employee->name }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($kpis->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada KPI.</p>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($kpis as $kpi)
                @php $pct = min(100, max(0, $kpi->achievementPct())); @endphp
                <div class="rounded-wsm border border-line bg-white p-4.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                                    {{ $kpi->period }}
                                </span>
                                @if ($kpi->status !== 'Active')
                                    <span
                                        class="rounded-full bg-[#f2f0eb] px-2 py-0.5 text-[9px] font-extrabold text-[#5e5951]">
                                        {{ $kpi->status }}
                                    </span>
                                @endif
                            </div>
                            <strong class="mt-1 block text-sm">{{ $kpi->title }}</strong>
                            <span class="text-[10px] text-muted">{{ $kpi->employee->name }}</span>
                        </div>
                        <span class="{{ $kpi->achievementBadgeClass() }} flex-none">
                            {{ round($kpi->achievementPct()) }}%
                        </span>
                    </div>

                    <p class="mt-2.5 text-sm font-black">
                        {{ \App\Models\Kpi::formatNumber($kpi->current) }}
                        <small class="text-[11px] font-bold text-muted">
                            / {{ \App\Models\Kpi::formatNumber($kpi->target) }} {{ $kpi->unit }}
                        </small>
                    </p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[#eee8df]">
                        <div class="h-full rounded-full {{ $kpi->progressBarClass() }}"
                            style="width: {{ $pct }}%">
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[10px] text-muted">
                        <span>Weight {{ \App\Models\Kpi::formatNumber($kpi->weight) }}%</span>
                        <span>Due {{ $kpi->due_date?->translatedFormat('d M Y') ?? '-' }}</span>
                    </div>
                    @if ($kpi->owner_note)
                        <p class="mt-2 text-[11px] text-muted">{{ $kpi->owner_note }}</p>
                    @endif

                    @if (auth()->user()->canManageModule('kpi'))
                        <div class="mt-3.5 flex gap-2 border-t border-[#eee8df] pt-3.5">
                            <a href="{{ route('dashboard.kpi.edit', $kpi) }}"
                                class="btn-wsm-white py-2! px-3.5! text-xs">Edit</a>
                            <form method="POST" action="{{ route('dashboard.kpi.destroy', $kpi) }}"
                                data-confirm="{{ $kpi->title }} ({{ $kpi->employee->name }}) akan dihapus permanen."
                                data-confirm-title="Hapus KPI ini?" data-confirm-button="Ya, hapus" data-confirm-danger="1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-wsm-red py-2! px-3.5! text-xs">Hapus</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $kpis->links() }}</div>
    @endif
@endsection
