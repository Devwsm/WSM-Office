{{--
    employee/attendance/_history-card.blade.php
    ---------------------------------------------------------------------
    Partial 1 baris riwayat absen (1 sesi). Diambil dari
    employee/attendance/history.blade.php (Fase 4/7) supaya bisa dipakai
    ulang di kartu "Latest Attendance" pada Home (App Mode quick win,
    2026-09-09) tanpa duplikasi markup.

    Wajib dioper: $row (Attendance), $setting (OfficeSetting|null, buat
    statusBadgeClass()/statusLabel()).
    ---------------------------------------------------------------------
--}}
<div class="history-card flex items-center justify-between gap-3.5 rounded-wsm border border-line bg-white p-4">
    <div class="min-w-0">
        <strong class="block text-sm">
            {{ $row->date->translatedFormat('d M Y') }}
            @if ($row->session_number > 1)
                <span class="ml-1 text-[10px] font-bold text-muted">· Sesi {{ $row->session_number }}</span>
            @endif
        </strong>
        <span class="mt-0.5 block text-[11px] text-muted">
            {{ match ($row->mode) {
                'wfh' => 'WFH',
                'lapangan' => 'Lapangan',
                'gigs' => 'Gigs',
                default => 'Kantor',
            } }}
            @if ($row->work_context)
                · {{ $row->work_context }}
            @endif
        </span>
        @if ($row->mode === 'kantor' && $row->clock_in_within_radius === false)
            <span class="mt-1 block text-[10px] font-extrabold text-[#a53b33]">📍 Absen masuk di
                luar radius ({{ $row->clock_in_distance_meters }}m)</span>
        @endif
        @if ($row->auto_closed)
            <span class="mt-1 block text-[10px] font-extrabold text-[#a53b33]">⏱ Ditutup otomatis
                sistem (lupa checkout)</span>
        @endif
        @if ($row->wasCorrected())
            <p class="mt-2 rounded-xl bg-[#eef2ff] p-2.5 text-[10px] text-brand-blue">
                <strong>Dikoreksi oleh {{ $row->corrector?->name ?? '-' }}</strong>
                ({{ $row->corrected_at->translatedFormat('d M, H:i') }})
                : {{ $row->correction_note }}
                @if ($row->original_clock_in_at || $row->original_clock_out_at)
                    <br>Jam asli sebelum dikoreksi:
                    {{ $row->original_clock_in_at?->format('H:i') ?? '--:--' }} –
                    {{ $row->original_clock_out_at?->format('H:i') ?? '--:--' }}
                @endif
            </p>
        @endif
    </div>
    <div class="flex-none text-right">
        <span class="{{ $row->statusBadgeClass($setting) }}">{{ $row->statusLabel($setting) }}</span>
        <p class="mt-1.5 text-[11px] text-muted">
            {{ $row->clock_in_at?->format('H:i') ?? '--:--' }} –
            {{ $row->clock_out_at?->format('H:i') ?? '--:--' }}
        </p>
    </div>
</div>
