{{--
    dashboard/work/calendar.blade.php
    ---------------------------------------------------------------------
    "Timeline Calendar" versi dashboard (tab ke-3 "Work Control",
    layouts.app). 2026-09-28 — tampilan disamakan dengan prototype
    (calendarPage()/calendarMarkup() v19+): header hari hitam, sel tinggi
    dengan chip Weekly Rhythm per hari, chip item berwarna project,
    penanda ▶ / ■ start-end project, "+N item", legend pill, dan panel
    "Weekly Rhythm Settings". Semua data dari database (WorkItem,
    Project, OfficeSetting) — lihat Dashboard\Work\CalendarController.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — Timeline Calendar', 'navActive' => 'modules'])

@section('content')
    @php
        $prevMonth = $anchor->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $anchor->copy()->addMonthNoOverflow()->format('Y-m');
        $todayMonth = now()->format('Y-m');
        $navQuery = fn(string $month) => array_filter([
            'month' => $month,
            'project' => $projectFilter,
            'pic' => $picFilter,
        ]);
        $todayDow = now()->dayOfWeek;
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">←
                Dashboard</a>
            <h2 class="mt-2 text-[36px] font-black leading-[0.98] tracking-tight">Timeline Calendar</h2>
            <p class="mt-1 text-[13px] text-muted">Kalender bersama dari deadline Work Tracker seluruh tim —
                bukan kalender pribadi.</p>
        </div>
    </div>

    @include('dashboard.work._tabs', ['active' => 'calendar'])

    <div class="card-wsm-white">
        {{-- Toolbar: bulan + navigasi --}}
        <div class="mb-3.5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Project Timeline + Weekly Rhythm</p>
                <strong
                    class="mt-1 block text-[28px] font-black leading-none tracking-tight">{{ $anchor->translatedFormat('F Y') }}</strong>
                <p class="mt-2 text-xs text-muted">Warna mengikuti project. Filter bisa berdasarkan project maupun PIC.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard.work.calendar', $navQuery($prevMonth)) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!" aria-label="Bulan sebelumnya">←</a>
                <a href="{{ route('dashboard.work.calendar', $navQuery($todayMonth)) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!">Today</a>
                <a href="{{ route('dashboard.work.calendar', $navQuery($nextMonth)) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!" aria-label="Bulan berikutnya">→</a>
            </div>
        </div>

        {{-- Filter Project & PIC --}}
        <form method="GET" class="mb-3.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:max-w-xl">
            <input type="hidden" name="month" value="{{ $anchor->format('Y-m') }}">
            <div>
                <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Project</label>
                <select name="project" onchange="this.form.submit()" class="input-wsm">
                    <option value="">Semua Project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected($projectFilter === $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">PIC</label>
                <select name="pic" onchange="this.form.submit()" class="input-wsm">
                    <option value="">Semua PIC</option>
                    @foreach ($picOptions as $person)
                        <option value="{{ $person->id }}" @selected($picFilter === $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- Weekly Rhythm strip (Senin–Jumat) — hari ini diberi tint biru seperti prototype --}}
        <div class="mb-3.5 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($weeklyRhythm as $dow => $rhythm)
                <div
                    class="rounded-2xl border p-3 {{ $todayDow === $dow ? 'border-[#bad0ff] bg-[#e7efff]' : 'border-line bg-[#f0ece5]' }}">
                    <strong class="block text-[11px] font-black uppercase">{{ $rhythm['day'] }}</strong>
                    <span class="mt-1 block text-[11px] font-extrabold leading-tight">{{ $rhythm['focus'] }}</span>
                    <small class="mt-1 block text-[9px] text-muted">{{ $rhythm['mode'] }} · {{ $rhythm['hours'] }}</small>
                </div>
            @endforeach
        </div>

        {{-- Weekly Rhythm Settings — hanya yang punya akses work=manage --}}
        @if ($canManage)
            <details class="group mb-3.5 rounded-[20px] border border-line bg-white">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-3 px-3.5 py-3 text-[11px] font-black [&::-webkit-details-marker]:hidden">
                    <span>⚙ Weekly Rhythm Settings</span>
                    <span class="flex items-center gap-2 text-[10px] font-medium text-muted">ubah Alignment & Planning dst.
                        <span class="transition group-open:rotate-180">⌄</span></span>
                </summary>
                <div class="grid gap-2 px-3.5 pb-3.5">
                    <form id="rhythm-form" method="POST" action="{{ route('dashboard.work.calendar.rhythm.update') }}"
                        class="grid gap-2"
                        data-confirm="Fokus, mode, dan jam kerja per hari akan berubah untuk seluruh tim (kalender, dashboard Owner, dan App Mode)."
                        data-confirm-title="Simpan Weekly Rhythm?" data-confirm-button="Ya, simpan">
                        @csrf
                        @method('PATCH')
                        @foreach ($weeklyRhythm as $dow => $rhythm)
                            <div class="grid grid-cols-1 items-end gap-2 sm:grid-cols-[90px_1.35fr_.65fr_1fr]">
                                <strong class="text-[10px] sm:pb-3">{{ $rhythm['day'] }}</strong>
                                <div>
                                    <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Focus /
                                        Rhythm</label>
                                    <input type="text" name="rhythm[{{ $dow }}][focus]" maxlength="80" required
                                        value="{{ old("rhythm.$dow.focus", $rhythm['focus']) }}" class="input-wsm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Mode</label>
                                    <select name="rhythm[{{ $dow }}][mode]" class="input-wsm">
                                        @foreach ($rhythmModes as $mode)
                                            <option value="{{ $mode }}" @selected(old("rhythm.$dow.mode", $rhythm['mode']) === $mode)>
                                                {{ $mode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Hours /
                                        Note</label>
                                    <input type="text" name="rhythm[{{ $dow }}][hours]" maxlength="40"
                                        value="{{ old("rhythm.$dow.hours", $rhythm['hours_custom']) }}"
                                        placeholder="{{ $rhythm['mode'] === 'WFO' ? 'Otomatis: jam kerja kantor' : 'Otomatis: Flexible / remote' }}"
                                        class="input-wsm">
                                </div>
                            </div>
                        @endforeach
                    </form>
                    <form id="rhythm-reset" method="POST" action="{{ route('dashboard.work.calendar.rhythm.reset') }}"
                        data-confirm="Semua isian rhythm akan kembali ke bawaan (Alignment & Planning dst.). Perubahan yang sudah disimpan akan hilang."
                        data-confirm-title="Kembalikan ke default?" data-confirm-button="Ya, reset" data-confirm-danger="1">
                        @csrf
                    </form>
                    <div class="mt-1 flex flex-wrap justify-end gap-2">
                        <button type="submit" form="rhythm-reset" class="btn-wsm-white py-2! px-3.5! text-xs!">Reset
                            Default</button>
                        <button type="submit" form="rhythm-form" class="btn-wsm-black py-2! px-3.5! text-xs!">Save Calendar
                            Rhythm</button>
                    </div>
                    <p class="text-[10px] text-muted">Kosongkan "Hours / Note" agar jam WFO otomatis mengikuti Pengaturan
                        Kantor.</p>
                </div>
            </details>
        @endif

        {{-- Grid bulan — header hitam, sel berbatas tipis, seperti .calendar-shell prototype --}}
        <div class="overflow-x-auto rounded-[26px] border border-line bg-white">
            <div class="min-w-190">
                <div class="grid grid-cols-7 bg-[#111] text-white">
                    @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $label)
                        <div class="p-3 text-center text-[11px] font-black">{{ $label }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($weeks as $week)
                        @foreach ($week as $cell)
                            <div
                                class="min-h-28 border-b border-r border-[#ece5da] p-2 lg:min-h-33 nth-[7n]:border-r-0 {{ $cell['outside'] ? 'bg-[#f6f2eb] text-[#b5afa5]' : 'bg-white' }} {{ $cell['isToday'] ? 'relative z-10 outline-2 -outline-offset-2 outline-[#111]' : '' }}">
                                <div class="mb-1.5 flex items-center justify-between text-[11px] font-black">
                                    <span>{{ $cell['date']->day }}</span>
                                    @if ($cell['isToday'])
                                        <small class="text-[10px]">Today</small>
                                    @endif
                                </div>

                                @if ($cell['rhythm'])
                                    <div class="mb-1.5 rounded-[7px] px-1.5 py-1 text-[8px] leading-tight {{ $cell['rhythm']['mode'] === 'WFO' ? 'bg-[#e8efff] text-[#23498d]' : 'bg-[#e8f8e8] text-[#286231]' }}"
                                        title="{{ $cell['rhythm']['focus'] }} · {{ $cell['rhythm']['mode'] }} · {{ $cell['rhythm']['hours'] }}">
                                        <strong class="block">{{ $cell['rhythm']['focus'] }}</strong>
                                        <span class="block opacity-70">{{ $cell['rhythm']['mode'] }}</span>
                                    </div>
                                @endif

                                <div class="grid gap-1">
                                    @foreach ($cell['visible'] as $item)
                                        @include('dashboard.work._calendar-chip', ['item' => $item])
                                    @endforeach

                                    @if ($cell['hidden']->isNotEmpty())
                                        <details class="group">
                                            <summary
                                                class="cursor-pointer list-none text-[9px] font-bold text-muted [&::-webkit-details-marker]:hidden">
                                                <span class="group-open:hidden">+{{ $cell['hidden']->count() }} item</span>
                                                <span class="hidden group-open:inline">Tutup</span>
                                            </summary>
                                            <div class="mt-1 grid gap-1">
                                                @foreach ($cell['hidden'] as $item)
                                                    @include('dashboard.work._calendar-chip', [
                                                        'item' => $item,
                                                    ])
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Legend warna project — pill seperti .calendar-project-legend --}}
        @if ($projects->isNotEmpty() || $hasNoProjectItems)
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @foreach ($projects as $project)
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2 py-1.5 text-[9px] font-extrabold">
                        <i class="inline-block h-2.5 w-2.5 rounded-full"
                            style="background:{{ \App\Models\Project::colorFor($project) }}"></i>
                        {{ $project->name }}
                    </span>
                @endforeach
                @if ($hasNoProjectItems)
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2 py-1.5 text-[9px] font-extrabold">
                        <i class="inline-block h-2.5 w-2.5 rounded-full" style="background:{{ $noProjectColor }}"></i>
                        Tanpa Project
                    </span>
                @endif
            </div>
        @endif
    </div>
@endsection
