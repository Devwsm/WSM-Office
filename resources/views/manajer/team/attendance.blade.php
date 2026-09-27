{{--
    manajer/team/attendance.blade.php
    ---------------------------------------------------------------------
    README #40 — Team Overview (bagian Absensi). Snapshot HARI INI saja,
    scope tim dari TeamAttendanceController (visibleAttendanceUserIds()).
    Beda dari Rekap Absensi penuh (attendance.recap.index): ini gak ada
    filter tanggal / koreksi, cuma "siapa yang perlu di-follow-up sekarang".
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Absensi Tim', 'navActive' => 'manajer-team'])

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('employee.home') }}" class="text-[11px] font-extrabold text-muted">← Home</a>
            <h2 class="mt-2 text-[40px] font-black leading-[0.95] tracking-tight">Absensi Tim</h2>
            <p class="mt-1 text-[15px] text-muted">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} ·
                snapshot hari ini.</p>
        </div>
        <a href="{{ route('manajer.team.work') }}" class="btn-wsm-white">Lihat Progress Kerja Tim →</a>
    </div>

    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-5">
        <div class="stat-wsm-blue">
            <span class="stat-wsm-label">Total</span>
            <div><strong class="stat-wsm-value">{{ $summary['total'] }}</strong></div>
        </div>
        <div class="stat-wsm-green">
            <span class="stat-wsm-label">Sudah Absen</span>
            <div><strong class="stat-wsm-value">{{ $summary['sudahAbsen'] }}</strong></div>
        </div>
        <div class="stat-wsm-yellow">
            <span class="stat-wsm-label">Belum Absen</span>
            <div><strong class="stat-wsm-value">{{ $summary['belumAbsen'] }}</strong></div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Cuti/Izin</span>
            <div><strong class="stat-wsm-value">{{ $summary['cuti'] }}</strong></div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Lembur</span>
            <div><strong class="stat-wsm-value">{{ $summary['lembur'] }}</strong></div>
        </div>
    </div>

    <div class="table-wrap mt-5 overflow-auto rounded-2xl border border-line bg-white">
        <table class="w-full min-w-140 border-collapse">
            <thead>
                <tr class="bg-[#fbf8f2] text-left text-[10px] font-black uppercase tracking-wide text-[#867f76]">
                    <th class="px-3.5 py-3">Karyawan</th>
                    <th class="px-3.5 py-3">Status</th>
                    <th class="px-3.5 py-3">Masuk</th>
                    <th class="px-3.5 py-3">Lembur</th>
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
                        <td class="px-3.5 py-3">
                            <span class="{{ $row['badgeClass'] }}">{{ $row['statusLabel'] }}</span>
                        </td>
                        <td class="px-3.5 py-3 text-xs">{{ $row['attendance']?->clock_in_at?->format('H:i') ?? '--:--' }}
                        </td>
                        <td class="px-3.5 py-3 text-xs">{{ $row['overtime'] ? 'Disetujui' : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3.5 py-6 text-center text-xs text-muted">Belum ada anggota tim.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
