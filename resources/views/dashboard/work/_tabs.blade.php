{{--
    dashboard/work/_tabs.blade.php
    ---------------------------------------------------------------------
    2026-09-28 — Tab bar "Work Control" SATU sumber untuk semua sub-halaman
    (sebelumnya disalin manual di 4 halaman dan Projects tidak punya).
    Urutan mengikuti prototype & sidebar: Projects -> Work Tracker ->
    Timeline Calendar -> MoM / Meeting -> Memo Forum. Kalau menambah tab,
    ubah di sini SAJA, lalu samakan urutan di sidebar (layouts/app.blade.php).

    Pakai: @include('dashboard.work._tabs', ['active' => 'tracker'])
    Kunci $active: projects | tracker | calendar | meetings | memo
    ---------------------------------------------------------------------
--}}
@php
    $workTabs = [
        'projects' => ['Projects', route('dashboard.work.projects.index')],
        'tracker' => ['Work Tracker', route('dashboard.work.tracker.index')],
        'calendar' => ['Timeline Calendar', route('dashboard.work.calendar')],
        'meetings' => ['Rapat & Action Item', route('dashboard.work.meetings.index')],
        'memo' => ['MoM & Memo', route('dashboard.work.index')],
    ];
@endphp
<nav class="mb-5 flex flex-wrap gap-2" aria-label="Work Control">
    @foreach ($workTabs as $key => [$label, $url])
        @if ($key === ($active ?? null))
            <span aria-current="page"
                class="inline-flex items-center rounded-2xl px-3.5 py-2 text-[11px] font-extrabold text-white"
                style="background-color: var(--work-accent)">{{ $label }}</span>
        @else
            <a href="{{ $url }}"
                class="inline-flex items-center rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">{{ $label }}</a>
        @endif
    @endforeach
</nav>
