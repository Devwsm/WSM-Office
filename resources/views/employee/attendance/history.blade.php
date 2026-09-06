{{--
    employee/attendance/history.blade.php
    ---------------------------------------------------------------------
    Fase 4 — riwayat absensi bulanan milik sendiri, navigasi bulan
    sebelum/sesudah lewat query string `bulan` (format Y-m).
    Fase 7 nambah: label mode Lapangan/Gigs + badge nomor sesi (bisa
    >1 baris per tanggal buat 2 mode itu), indikator "ditutup otomatis"
    (auto_closed), dan ringkasan potongan kurang jam per blok 60 menit
    sebulan ($shortage, dari Attendance::monthlyShortageBlocks()).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Riwayat Absensi', 'navActive' => 'riwayat'])

@section('content')
    <div class="employee-section-head mb-5 flex items-end justify-between gap-3">
        <div>
            <h2 class="text-[30px] font-black leading-none tracking-tight">Riwayat Absensi</h2>
            <p class="mt-1.5 text-xs text-muted">{{ $period->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('employee.attendance.history', ['bulan' => $prevMonth]) }}"
                class="btn-wsm-white py-2.5! px-3.5! text-xs!">←</a>
            @unless ($isCurrentMonth)
                <a href="{{ route('employee.attendance.history', ['bulan' => $nextMonth]) }}"
                    class="btn-wsm-white py-2.5! px-3.5! text-xs!">→</a>
            @endunless
        </div>
    </div>

    @if ($shortage['total_shortage_minutes'] > 0)
        <div class="stat-wsm-yellow mb-4">
            <span class="stat-wsm-label">Kurang Jam Kerja Bulan Ini</span>
            <div>
                <strong class="stat-wsm-value">{{ $shortage['blocks'] }} blok</strong>
                <p class="stat-wsm-note mt-1">
                    ({{ $shortage['total_shortage_minutes'] }} menit, dipotong per blok 60 menit
                    @if ($shortage['remainder_minutes'] > 0)
                        — sisa {{ $shortage['remainder_minutes'] }} menit belum genap 1 blok
                    @endif
                    )
                </p>
            </div>
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada data absensi di bulan ini.</p>
        </div>
    @else
        <div class="list grid gap-2.5">
            @foreach ($rows as $row)
                <div
                    class="history-card flex items-center justify-between gap-3.5 rounded-wsm border border-line bg-white p-4">
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
            @endforeach
        </div>
    @endif
@endsection
