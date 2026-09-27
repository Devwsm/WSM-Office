{{--
    manajer/team/work.blade.php
    ---------------------------------------------------------------------
    README #40 — Team Overview (bagian Progress Kerja). Scope tim dari
    TeamWorkController (visibleAttendanceUserIds()), diurutkan siapa
    yang overdue-nya paling banyak duluan. Warna badge fokus PERSIS
    disalin dari dashboard/work/tracker/index.blade.php (board Work
    Tracker) biar konsisten di seluruh app, bukan skema warna baru.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Progress Kerja Tim', 'navActive' => 'manajer-team'])

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('employee.home') }}" class="text-[11px] font-extrabold text-muted">← Home</a>
            <h2 class="mt-2 text-[40px] font-black leading-[0.95] tracking-tight">Progress Kerja Tim</h2>
            <p class="mt-1 text-[15px] text-muted">Task aktif (belum Done) per anggota tim, diurutkan yang paling
                perlu di-follow-up.</p>
        </div>
        <a href="{{ route('manajer.team.attendance') }}" class="btn-wsm-white">Lihat Absensi Tim →</a>
    </div>

    <div class="grid gap-3.5">
        @forelse ($rows as $row)
            <div class="card-wsm-white">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <strong class="block text-[15px]">{{ $row['user']->name }}</strong>
                        <span class="text-[10px] text-muted">{{ $row['user']->roleLabel() }} ·
                            {{ $row['user']->division ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($row['overdueCount'] > 0)
                            <span class="badge-wsm-red">{{ $row['overdueCount'] }} overdue</span>
                        @endif
                        <span class="badge-wsm-gray">{{ $row['openCount'] }} task aktif</span>
                    </div>
                </div>

                @if ($row['tasks']->isNotEmpty())
                    <div class="mt-3 grid gap-1.5 border-t border-[#eee8df] pt-3">
                        @foreach ($row['tasks'] as $task)
                            @php
                                $focus = $task->computedFocus();
                                $focusColors = match ($focus) {
                                    'HARI INI' => ['bg' => '#ffe876', 'text' => '#392f00'],
                                    'BESOK' => ['bg' => '#fff0ae', 'text' => '#604d00'],
                                    'MINGGU INI', 'MINGGU DEPAN' => ['bg' => '#e8f0ff', 'text' => '#3158a8'],
                                    'AMAN', 'NOT URGENT' => ['bg' => '#e6f4e9', 'text' => '#1c6c39'],
                                    'KELEWAT' => ['bg' => '#ffded8', 'text' => '#9b392f'],
                                    default => ['bg' => '#eeeae3', 'text' => '#625c54'],
                                };
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="truncate">{{ $task->title }}
                                    <span class="text-[10px] text-muted">·
                                        {{ $task->project?->name ?? 'Tanpa Project' }}</span>
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wide"
                                    style="background:{{ $focusColors['bg'] }};color:{{ $focusColors['text'] }}">
                                    {{ $focus }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 border-t border-[#eee8df] pt-3 text-xs text-muted">Tidak ada task aktif.</p>
                @endif
            </div>
        @empty
            <div class="card-wsm-white text-center">
                <p class="text-xs text-muted">Belum ada anggota tim.</p>
            </div>
        @endforelse
    </div>
@endsection
