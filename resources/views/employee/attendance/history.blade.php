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
                @include('employee.attendance._history-card', ['row' => $row, 'setting' => $setting])
            @endforeach
        </div>
    @endif
@endsection
