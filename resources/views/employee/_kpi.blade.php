{{--
    employee/_kpi.blade.php
    ---------------------------------------------------------------------
    App Mode quick win (2026-09-09, dari audit ronde 4 di README) —
    padanan `employeeKpiMarkup(id)` di prototype. `$kpis` dioper dari
    HomeController (KPI aktif/baru-selesai milik user yang lagi login).

    Fase 10 (KPI & Performance penuh, sisi Owner buat kelola KPI tim)
    BELUM dibangun — jadi kartu ini kelihatan kosong sampai ada baris
    `kpis` yang diisi (manual/tinker/seeder) atau Fase 10 selesai. Ini
    perilaku yang DISENGAJA, sama seperti prototype waktu `state.kpis`
    masih kosong (pesan "KPI belum diset...", bukan error).
    ---------------------------------------------------------------------
--}}
<div class="card-wsm-white mb-3.5">
    <div class="mb-2.5 flex items-center justify-between gap-3">
        <p class="text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">My KPI</p>
        @if ($kpis->isNotEmpty())
            <span class="badge-wsm-blue">{{ $kpis->count() }} active</span>
        @endif
    </div>

    @if ($kpis->isEmpty())
        <p class="text-xs text-muted">KPI belum diset oleh Owner / Manajer.</p>
    @else
        <div class="grid gap-2.5 sm:grid-cols-2">
            @foreach ($kpis as $kpi)
                @php $pct = min(100, max(0, $kpi->achievementPct())); @endphp
                <article class="rounded-2xl border border-line bg-white p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                                {{ $kpi->period }}
                            </span>
                            <h3 class="text-xs font-black leading-tight">{{ $kpi->title }}</h3>
                        </div>
                        <span class="{{ $kpi->achievementBadgeClass() }} flex-none">
                            {{ round($kpi->achievementPct()) }}%
                        </span>
                    </div>
                    <p class="mt-2 text-sm font-black">
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
                        <span>Due {{ $kpi->due_date?->translatedFormat('d M') ?? '-' }}</span>
                    </div>
                    @if ($kpi->owner_note)
                        <p class="mt-2 text-[11px] text-muted">{{ $kpi->owner_note }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
