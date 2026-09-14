{{--
    owner/dashboard.blade.php
    ---------------------------------------------------------------------
    Fase 16 — 2 kartu terakhir ("Tugas Berjalan"/"Kontrak Akan Habis")
    akhirnya diisi data beneran (lihat DashboardController), gak lagi
    "—" placeholder dari Fase 0.

    Audit visual vs prototype (2026-09-15) — nambah hero "Check Your
    Team Space", strip Weekly Rhythm, card Upcoming Birthday & Work
    Anniversary, dan card Service Length. 4 stat card asli (Kehadiran/
    Pengajuan Pending/Tugas Berjalan/Kontrak Akan Habis) SENGAJA
    dibiarkan seperti semula — link & datanya masih dipakai, cuma
    ditaruh di bawah hero + weekly rhythm sekarang, bukan diganti.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Dashboard', 'navActive' => 'dashboard'])

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-2xl">
            <p class="text-[11px] font-black uppercase tracking-wide text-muted">People + Execution Control</p>
            <h2 class="mt-1 text-[40px] font-black leading-[0.95] tracking-tight">Check Your Team Space</h2>
            <p class="mt-2 text-[15px] text-muted">Org chart, supervisor approval, live service, KPI, tracker,
                attendance, leave, contract, memo, dan payroll.</p>
        </div>
        <span class="rounded-2xl border border-line bg-white px-4 py-2.5 text-center">
            <span class="block text-[10px] font-extrabold uppercase tracking-wide text-muted">Periode aktif</span>
            <strong class="block text-[13px]">{{ now()->translatedFormat('F Y') }}</strong>
        </span>
    </div>

    {{-- Weekly Rhythm — 5 hari kerja (Senin-Jumat), padanan strip di
        prototype. Jam WFO-nya sinkron sama Pengaturan Kantor
        (lihat DashboardController). --}}
    <div class="mb-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($weeklyRhythm as $day)
            <div class="rounded-wsm-lg border border-line bg-white p-3.5">
                <p class="text-[10px] font-black uppercase tracking-wide text-muted">{{ $day['day'] }}</p>
                <strong class="mt-1 block text-[13px] font-black leading-snug">{{ $day['focus'] }}</strong>
                <span class="mt-1 block text-[10px] text-muted">{{ $day['mode'] }} · {{ $day['hours'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-wsm-blue">
            <span class="stat-wsm-label">Kehadiran Hari Ini</span>
            <div>
                <strong class="stat-wsm-value">{{ $hadirHariIni }}/{{ $totalKaryawan }}</strong>
                <p class="stat-wsm-note mt-1"><a href="{{ route('attendance.recap.index') }}" class="underline">Lihat rekap
                        →</a></p>
            </div>
        </div>
        <div class="stat-wsm-yellow">
            <span class="stat-wsm-label">Pengajuan Pending</span>
            <div>
                <strong class="stat-wsm-value">{{ $pendingTotal }}</strong>
                <p class="stat-wsm-note mt-1">
                    {{ $leavePending }} izin/cuti ·
                    {{ $attendanceNeedsAttention }} absen perlu dicek ·
                    <a href="{{ route('approval.leave.index') }}" class="underline">Lihat →</a>
                </p>
            </div>
        </div>
        <div class="stat-wsm-green">
            <span class="stat-wsm-label">Tugas Berjalan</span>
            <div>
                <strong class="stat-wsm-value">{{ $tugasBerjalan }}</strong>
                <p class="stat-wsm-note mt-1">Belum Done/Postpone ·
                    <a href="{{ route('dashboard.work.tracker.index') }}" class="underline">Lihat board →</a>
                </p>
            </div>
        </div>
        <div class="stat-wsm-lime">
            <span class="stat-wsm-label">Kontrak Akan Habis</span>
            <div>
                <strong class="stat-wsm-value">{{ $kontrakAkanHabis }}</strong>
                <p class="stat-wsm-note mt-1">≤30 hari lagi ·
                    <a href="{{ route('dashboard.contracts.index') }}" class="underline">Lihat →</a>
                </p>
            </div>
        </div>
    </div>

    <div class="mt-3.5 grid grid-cols-1 gap-3.5 lg:grid-cols-2">
        {{-- Upcoming Birthday & Work Anniversary — window 60 hari,
            semua karyawan (lihat DashboardController). --}}
        <div class="card-wsm-white">
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Upcoming 60 Days</p>
            <p class="mt-0.5 text-sm font-black">Birthday &amp; Work Anniversary</p>

            @if ($teamMoments->isEmpty())
                <p class="mt-3 text-xs text-muted">Tidak ada birthday atau work anniversary dalam 60 hari ke depan.
                </p>
            @else
                <div class="mt-3.5 grid gap-2">
                    @foreach ($teamMoments as $row)
                        <div class="rounded-2xl border border-line bg-[#faf8f3] px-3.5 py-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[10px] font-extrabold text-muted">
                                    + {{ $row['type'] === 'birthday' ? 'Birthday' : 'Work Anniversary' }}
                                </p>
                                <strong class="flex-none text-[11px]">{{ $row['date']->translatedFormat('d M') }}
                                    ·
                                    {{ $row['days'] }} hari lagi</strong>
                            </div>
                            <strong class="mt-0.5 block text-xs">
                                {{ $row['user']->name }}
                                @if ($row['years'])
                                    · {{ $row['years'] }} tahun
                                @endif
                            </strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Service Length — lama bekerja tiap karyawan, dari
            User::serviceDurationLabel() (sudah dipakai App Mode
            Milestones, sekarang dipanggil juga di sisi Owner). --}}
        <div class="card-wsm-white">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wide text-muted">Service Length</p>
                    <p class="mt-0.5 text-sm font-black">Sudah Lama Bekerja</p>
                </div>
                <a href="{{ route('owner.employees.index') }}" class="badge-wsm-gray">People →</a>
            </div>

            <div class="mt-3.5 grid max-h-96 gap-0.5 overflow-y-auto">
                @foreach ($serviceLengths as $employee)
                    <div class="flex items-center justify-between gap-3 border-b border-[#eee8df] py-2.5 last:border-0">
                        <div class="min-w-0">
                            <strong class="block truncate text-xs">{{ $employee->name }}</strong>
                            <span
                                class="text-[10px] text-muted">{{ $employee->job_title ?? $employee->roleLabel() }}</span>
                        </div>
                        <span
                            class="flex-none text-[11px] font-bold {{ $employee->join_date ? 'text-ink' : 'text-muted' }}">
                            {{ $employee->serviceDurationLabel() ?? 'Set join date' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
