{{--
    employee/attendance/history.blade.php
    ---------------------------------------------------------------------
    Fase 4 — riwayat absensi bulanan milik sendiri, navigasi bulan
    sebelum/sesudah lewat query string `bulan` (format Y-m).
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
                        <strong class="block text-sm">{{ $row->date->translatedFormat('d M Y') }}</strong>
                        <span class="mt-0.5 block text-[11px] text-muted">
                            {{ $row->mode === 'wfh' ? 'WFH' : 'Kantor' }}
                            @if ($row->work_context)
                                · {{ $row->work_context }}
                            @endif
                        </span>
                        @if ($row->mode === 'kantor' && $row->clock_in_within_radius === false)
                            <span class="mt-1 block text-[10px] font-extrabold text-[#a53b33]">📍 Absen masuk di
                                luar radius ({{ $row->clock_in_distance_meters }}m)</span>
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
