{{--
    attendance/recap/index.blade.php
    ---------------------------------------------------------------------
    Fase 4 — rekap absensi harian. Scope data (siapa aja yang muncul)
    ditentukan di RecapController::scopedUsers() per role yang login:
    Owner & HRD lihat semua, Manajer lihat diri sendiri + bawahan turunan.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Rekap Absensi', 'navActive' => 'attendance'])

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <h2 class="text-[40px] font-black leading-[0.95] tracking-tight">Rekap Absensi</h2>
            <p class="mt-1 text-[15px] text-muted">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="tanggal" value="{{ $date }}" onchange="this.form.submit()"
                class="input-wsm w-auto!">
            @unless ($isToday)
                <a href="{{ route('attendance.recap.index') }}" class="btn-wsm-white py-2.5! text-xs!">Hari Ini</a>
            @endunless
        </form>
    </div>

    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
        <div class="stat-wsm-blue">
            <span class="stat-wsm-label">Total</span>
            <div><strong class="stat-wsm-value">{{ $summary['total'] }}</strong></div>
        </div>
        <div class="stat-wsm-green">
            <span class="stat-wsm-label">Hadir</span>
            <div><strong class="stat-wsm-value">{{ $summary['hadir'] }}</strong></div>
        </div>
        <div class="stat-wsm-yellow">
            <span class="stat-wsm-label">Terlambat</span>
            <div><strong class="stat-wsm-value">{{ $summary['terlambat'] }}</strong></div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Belum Absen</span>
            <div><strong class="stat-wsm-value">{{ $summary['belum_absen'] }}</strong></div>
        </div>
    </div>

    <div class="table-wrap mt-5 overflow-auto rounded-2xl border border-line bg-white">
        <table class="w-full min-w-180 border-collapse">
            <thead>
                <tr class="bg-[#fbf8f2] text-left text-[10px] font-black uppercase tracking-wide text-[#867f76]">
                    <th class="px-3.5 py-3">Karyawan</th>
                    <th class="px-3.5 py-3">Mode</th>
                    <th class="px-3.5 py-3">Masuk</th>
                    <th class="px-3.5 py-3">Pulang</th>
                    <th class="px-3.5 py-3">Radius</th>
                    <th class="px-3.5 py-3">Status</th>
                    <th class="px-3.5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-[#eee8df] text-sm">
                        <td class="px-3.5 py-3">
                            <strong class="block">{{ $row['user']->name }}</strong>
                            <span class="text-[10px] text-muted">{{ $row['user']->roleLabel() }} ·
                                {{ $row['user']->division ?? '-' }}</span>
                        </td>
                        <td class="px-3.5 py-3 text-xs">
                            {{ $row['attendance'] ? ($row['attendance']->mode === 'wfh' ? 'WFH' : 'Kantor') : '-' }}
                        </td>
                        <td class="px-3.5 py-3 text-xs">{{ $row['attendance']?->clock_in_at?->format('H:i') ?? '--:--' }}
                        </td>
                        <td class="px-3.5 py-3 text-xs">{{ $row['attendance']?->clock_out_at?->format('H:i') ?? '--:--' }}
                        </td>
                        <td class="px-3.5 py-3 text-xs">
                            @if ($row['attendance']?->mode === 'kantor' && $row['attendance']->clock_in_within_radius === false)
                                <span class="badge-wsm-red">Di luar radius</span>
                            @elseif ($row['attendance']?->mode === 'kantor' && $row['attendance']->clock_in_within_radius === true)
                                <span class="badge-wsm-green">Dalam radius</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-3.5 py-3">
                            <span class="{{ $row['badgeClass'] }}">{{ $row['statusLabel'] }}</span>
                        </td>
                        <td class="px-3.5 py-3 text-right">
                            <a href="{{ route('attendance.recap.show', $row['user']) }}"
                                class="text-xs font-extrabold text-brand-blue hover:underline">Riwayat →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3.5 py-6 text-center text-xs text-muted">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
