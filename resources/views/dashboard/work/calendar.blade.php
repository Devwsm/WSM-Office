{{--
    dashboard/work/calendar.blade.php
    ---------------------------------------------------------------------
    2026-09-15 — "Timeline Calendar" versi dashboard (tab ke-2 "Work
    Control", layouts.app). Padanan visual dari
    employee/work-tracker/calendar.blade.php (App Mode, layouts.employee)
    tapi dipakein di sini biar sidebar dashboard gak lompat keluar ke
    layout App Mode pas diklik. Data & query-nya sama persis (lihat
    Dashboard\Work\CalendarController), cuma header/tab-bar & container
    lebar yang disesuaikan ke pola dashboard.work.* lain (index.blade.php,
    meetings/index.blade.php, tracker/index.blade.php).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — Timeline Calendar', 'navActive' => 'modules'])

@section('content')
    @php
        $prevMonth = $anchor->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $anchor->copy()->addMonthNoOverflow()->format('Y-m');
        $todayMonth = now()->format('Y-m');
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

    {{-- Tab "Work Control" — sama pola kayak dashboard/work/index.blade.php,
         MoM & Memo, dan Rapat & Action Item, cuma tab aktifnya beda. --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('dashboard.work.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">MoM
            &amp; Memo</a>
        <a href="{{ route('dashboard.work.tracker.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Work
            Tracker</a>
        <span class="rounded-2xl px-3.5 py-2 text-[11px] font-extrabold text-white"
            style="background-color: var(--work-accent)">Timeline Calendar</span>
        <a href="{{ route('dashboard.work.meetings.index') }}"
            class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Rapat
            &amp; Action Item</a>
    </div>

    <div class="card-wsm-white">
        <div class="mb-3.5 flex flex-wrap items-center justify-between gap-3">
            <strong class="text-lg font-black">{{ $anchor->translatedFormat('F Y') }}</strong>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard.work.calendar', array_filter(['month' => $prevMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!">←</a>
                <a href="{{ route('dashboard.work.calendar', array_filter(['month' => $todayMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!">Today</a>
                <a href="{{ route('dashboard.work.calendar', array_filter(['month' => $nextMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
                    class="btn-wsm-white py-2! px-3.5! text-xs!">→</a>
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

        {{-- Weekly Rhythm strip --}}
        <div class="mb-3.5 flex flex-wrap gap-2">
            @foreach ($weeklyRhythm as $dow => $rhythm)
                <div
                    class="w-36 flex-none rounded-2xl border p-2.5 {{ now()->dayOfWeek === $dow ? 'border-ink bg-ink text-white' : 'border-line bg-white' }}">
                    <strong class="block text-[11px] font-black">{{ $rhythm['day'] }}</strong>
                    <span
                        class="mt-1 block text-[10px] leading-tight {{ now()->dayOfWeek === $dow ? 'text-white/80' : 'text-muted' }}">{{ $rhythm['focus'] }}</span>
                    <small
                        class="mt-1 block text-[9px] {{ now()->dayOfWeek === $dow ? 'text-white/60' : 'text-muted' }}">{{ $rhythm['mode'] }}
                        · {{ $rhythm['hours'] }}</small>
                </div>
            @endforeach
        </div>

        {{-- Grid bulan penuh — sama pola native grid-cols-7 kayak versi App
             Mode, cuma di sini container-nya lebih lega (bukan max-w-140),
             jadi sel-nya natural lebih lapang tanpa perlu penyesuaian lain. --}}
        <div class="grid grid-cols-7 gap-1 text-center">
            @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $label)
                <div class="pb-1 text-[10px] font-black uppercase text-muted">{{ $label }}</div>
            @endforeach
        </div>
        <div class="mb-3.5 grid grid-cols-7 gap-1">
            @foreach ($weeks as $week)
                @foreach ($week as $cell)
                    <div
                        class="min-h-20 overflow-hidden rounded-xl border p-1.5 lg:min-h-28 {{ $cell['outside'] ? 'border-line/60 bg-[#faf8f3] opacity-40' : ($cell['isToday'] ? 'border-ink ring-2 ring-ink/10 bg-white' : 'border-line bg-white') }}">
                        <div class="flex items-center justify-between gap-0.5">
                            <span class="text-[11px] font-black">{{ $cell['date']->day }}</span>
                            @if ($cell['rhythm'])
                                <span
                                    class="h-1.5 w-1.5 flex-none rounded-full {{ $cell['rhythm']['mode'] === 'WFH' ? 'bg-brand-blue' : 'bg-[#a39c8f]' }}"
                                    title="{{ $cell['rhythm']['focus'] }} · {{ $cell['rhythm']['mode'] }} · {{ $cell['rhythm']['hours'] }}"></span>
                            @endif
                        </div>
                        <div class="mt-1 grid gap-0.5">
                            @foreach ($cell['items']->take(3) as $item)
                                <div class="truncate rounded px-1 py-0.5 text-[9px] font-bold leading-tight"
                                    style="background:{{ $item['color'] }};color:{{ $item['text'] }}"
                                    title="{{ $item['title'] }}{{ $item['pic'] ? ' · ' . $item['pic'] : '' }}">
                                    {{ $item['title'] }}
                                </div>
                            @endforeach
                            @if ($cell['items']->count() > 3)
                                <span class="text-[9px] text-muted">+{{ $cell['items']->count() - 3 }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Legend warna project --}}
        @if ($projects->isNotEmpty())
            <div class="flex flex-wrap gap-x-3.5 gap-y-1.5">
                @foreach ($projects as $project)
                    <span class="flex items-center gap-1.5 text-[10px] text-muted">
                        <i class="inline-block h-2 w-2 rounded-full"
                            style="background:{{ $projectColor($project->id) }}"></i>
                        {{ $project->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endsection
