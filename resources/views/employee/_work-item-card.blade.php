{{--
    employee/_work-item-card.blade.php
    ---------------------------------------------------------------------
    App Mode (2026-09-09) — padanan `taskCardMarkupV9()` di prototype.
    Dipakai di My Work Tracker (`_work-tracker.blade.php`). Butuh
    `$item` (WorkItem, dengan relasi `project` sudah di-eager-load).

    VIEW-ONLY (lihat catatan scope di WorkTrackerController) — prototype
    versi employeeModeView=true juga punya dropdown ubah status + tombol
    "Update Note" di sini, SENGAJA belum diikutin di v1 ini.
    ---------------------------------------------------------------------
--}}
@php
    $focus = $item->computedFocus();
    $overdue = $item->isOverdue();

    // Padanan progressOptions/badge color di taskCardMarkupV9: Done=green,
    // Follow Up=yellow, overdue=red, On Development=blue, sisanya gray.
    $progressBadgeClass = match (true) {
        $item->progress === 'Done' => 'badge-wsm-green',
        $item->progress === 'Follow Up' => 'badge-wsm-yellow',
        $overdue => 'badge-wsm-red',
        $item->progress === 'On Development' => 'badge-wsm-blue',
        default => 'badge-wsm-gray',
    };

    // Padanan focusClass() di prototype — warna PERSIS (hex sama, nol
    // drift), dikonversi ke inline style karena bukan token Tailwind
    // yang udah ada di app.css.
    $focusColors = match ($focus) {
        'HARI INI' => ['bg' => '#ffe876', 'text' => '#392f00'],
        'BESOK' => ['bg' => '#fff0ae', 'text' => '#604d00'],
        'MINGGU INI', 'MINGGU DEPAN' => ['bg' => '#e8f0ff', 'text' => '#3158a8'],
        'AMAN', 'NOT URGENT' => ['bg' => '#e6f4e9', 'text' => '#1c6c39'],
        'KELEWAT' => ['bg' => '#ffded8', 'text' => '#9b392f'],
        'SELESAI' => ['bg' => '#ccebd5', 'text' => '#176c37'],
        default => ['bg' => '#eeeae3', 'text' => '#625c54'],
    };
@endphp

<article
    class="mb-2 rounded-2xl border {{ $overdue ? 'border-[#f1c7c2] bg-[#fff8f7]' : ($item->progress === 'Follow Up' ? 'border-[#f3e3ae] bg-[#fffdf6]' : 'border-line bg-white') }} p-3.5">
    <div class="mb-2 flex items-start justify-between gap-2.5">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-wide text-muted">
                {{ $item->project?->name ?? 'General WSM' }} · {{ $item->section }}
            </p>
            <h3 class="mt-0.5 truncate text-sm font-black">{{ $item->title }}</h3>
        </div>
        <span class="{{ $progressBadgeClass }} flex-none">{{ $item->progress }}</span>
    </div>
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="inline-flex rounded-full px-2 py-1 text-[8px] font-black"
            style="background:{{ $focusColors['bg'] }};color:{{ $focusColors['text'] }}">
            {{ $focus }}
        </span>
        <span class="rounded-full bg-[#f2f0eb] px-2 py-1 text-[10px] font-bold text-[#5e5952]">
            {{ $item->due_date ? $item->due_date->translatedFormat('d M') : 'No date' }}
        </span>
        @if ($item->link)
            <a href="{{ $item->link }}" target="_blank" rel="noopener"
                class="rounded-full bg-[#f2f0eb] px-2 py-1 text-[10px] font-bold text-[#5e5952] underline">
                Open Link
            </a>
        @endif
    </div>
    @if ($item->notes)
        <p class="mt-2 text-[11px] leading-relaxed text-[#5e5952]">{{ $item->notes }}</p>
    @endif
</article>
