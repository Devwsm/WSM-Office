{{--
    dashboard/work/tracker/index.blade.php
    ---------------------------------------------------------------------
    2026-09-28 — Work Tracker = TASK PER PROJECT (padanan
    `trackerBoardMarkup()` prototype v31), menggantikan board kanban
    Fase 9 (tim komplain cara list project & isinya beda dari
    prototype). Struktur: kartu Project (progress) -> accordion Section
    (warna, jumlah item, tombol "+") -> tabel item (NO, ITEM, DATE,
    FOCUS, PIC, PROGRESS, NOTE, LINK, aksi).

    Progress = <select> langsung simpan (PATCH JSON), Note = textarea
    simpan saat blur, Edit/Tambah item lewat 1 modal. Form project TIDAK
    di sini lagi — ada di menu Projects (modal).

    Section bisa diatur (2026-09-30, padanan prototype v24-v28): warna
    (color picker), urutan (↑ ↓), tambah section kosong, dan hapus
    (section berisi item -> ketik nama section untuk konfirmasi). Data
    warna/urutan ada di `project_sections`; default warna = dari nama.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Tracker', 'navActive' => 'modules'])

@section('content')
    @php
        $canManage = auth()->user()->canManageModule('work');
        $progressClass = fn(string $p) => match ($p) {
            'Done' => 'bg-[#ccebd5] text-[#176c37]',
            'Follow Up' => 'bg-[#ffe876] text-[#392f00]',
            'On Development' => 'bg-[#dce6ff] text-[#2647b8]',
            'Confirmed' => 'bg-[#e6f4e9] text-[#1c6c39]',
            default => 'bg-[#eeeae3] text-[#5e5952]',
        };
        $priorityClass = fn(?string $p) => match ($p) {
            'High' => 'badge-wsm-red',
            'Medium' => 'badge-wsm-yellow',
            default => 'badge-wsm-gray',
        };
        $statusClass = fn(?string $s) => match ($s) {
            'Done', 'Confirmed' => 'badge-wsm-green',
            'On Development' => 'badge-wsm-blue',
            'Follow Up' => 'badge-wsm-yellow',
            default => 'badge-wsm-gray',
        };
        $focusColors = fn(string $f) => match ($f) {
            'HARI INI' => ['#ffe876', '#392f00'],
            'BESOK' => ['#fff0ae', '#604d00'],
            'MINGGU INI', 'MINGGU DEPAN' => ['#e8f0ff', '#3158a8'],
            'AMAN', 'NOT URGENT' => ['#e6f4e9', '#1c6c39'],
            'KELEWAT' => ['#ffded8', '#9b392f'],
            'SELESAI' => ['#ccebd5', '#176c37'],
            default => ['#eeeae3', '#625c54'],
        };
    @endphp

    <div x-data="{ taskModalOpen: false, sectionDelete: null, typed: '', viewersModal: null }" @wt-open-task-modal.window="taskModalOpen = true"
        @keydown.escape.window="taskModalOpen = false; sectionDelete = null; viewersModal = null">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3.5">
            <div>
                <a href="{{ route('dashboard.work.index') }}" class="text-[11px] font-extrabold text-muted">← Work
                    Control</a>
                <h2 class="mt-2 text-3xl font-black leading-[0.98] tracking-tight sm:text-[36px]">Work Tracker</h2>
                <p class="mt-1 text-[13px] text-muted">Task per project, dikelompokkan per section.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.work.projects.index') }}"
                    class="btn-wsm-white inline-flex items-center justify-center">Kelola Projects</a>
                @if ($canManage)
                    {{-- Task wajib masuk project: belum ada project -> jangan buka form, arahkan buat project. --}}
                    <button type="button"
                        onclick="{{ $projects->isEmpty() ? 'wtNeedProject()' : "wtOpenAddTask('" . $selectedProjectId . "', '')" }}"
                        class="btn-wsm-black inline-flex items-center justify-center">+ Tambah Task</button>
                @endif
            </div>
        </div>

        @include('dashboard.work._tabs', ['active' => 'tracker'])

        {{-- Ringkasan --}}
        <div class="mb-4 grid grid-cols-2 gap-2.5 lg:grid-cols-4">
            @foreach ([['Hari Ini', $stats['today'], 'action sekarang', '#fff0ae'], ['Kelewat', $stats['overdue'], 'perlu follow-up', '#ffded8'], ['Follow Up', $stats['follow_up'], 'waiting / chasing', '#ffe9a8'], ['Done', $stats['done'], 'closed item', '#d6efdc']] as [$label, $value, $hint, $bg])
                <div class="rounded-2xl p-3.5" style="background:{{ $bg }}">
                    <p class="text-[9px] font-extrabold uppercase tracking-widest text-[#5e5952]">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-black leading-none">{{ $value }}</p>
                    <p class="mt-1 text-[11px] text-[#5e5952]">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filter --}}
        <form method="GET" class="mb-3 flex flex-wrap items-end gap-2">
            @foreach ([['project_id', 'Project', $projects->pluck('name', 'id'), $selectedProjectId, 'Semua Project'], ['pic', 'PIC', $employees->pluck('name', 'id'), $selectedPic, 'Semua PIC'], ['progress', 'Progress', collect(\App\Models\WorkItem::PROGRESS_OPTIONS)->mapWithKeys(fn($s) => [$s => $s]), $selectedProgress, 'Semua'], ['focus', 'Focus', collect($focusOptions)->mapWithKeys(fn($s) => [$s => $s]), $selectedFocus, 'Semua Focus']] as [$name, $label, $options, $current, $all])
                <label class="grid min-w-34 flex-1 gap-1 sm:flex-none">
                    <span class="text-[10px] font-extrabold uppercase text-muted">{{ $label }}</span>
                    <select name="{{ $name }}" onchange="this.form.submit()"
                        class="w-full rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-bold text-ink sm:max-w-52">
                        <option value="">{{ $all }}</option>
                        @foreach ($options as $value => $text)
                            <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            @if ($selectedProjectId || $selectedPic || $selectedProgress || $selectedFocus)
                <a href="{{ route('dashboard.work.tracker.index') }}"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold text-ink">Reset</a>
            @endif
            <span class="flex gap-2 sm:ml-auto">
                <button type="button" onclick="wtToggleAll(true)"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold">Expand
                    All</button>
                <button type="button" onclick="wtToggleAll(false)"
                    class="rounded-2xl border border-line bg-white px-3.5 py-2 text-[11px] font-extrabold">Collapse
                    All</button>
            </span>
        </form>

        {{-- Kartu project --}}
        <div class="grid gap-4">
            @forelse ($cards as $card)
                @php
                    $project = $card['project'];
                    $color = \App\Models\Project::colorFor($project);
                    $cardKey = 'p' . ($project?->id ?? 0);
                @endphp
                <article class="overflow-hidden rounded-3xl border border-line bg-[#fbf8f2]"
                    data-wt-card="{{ $cardKey }}">
                    <details class="wt-card group" data-wt-key="{{ $cardKey }}"
                        @if ($selectedProjectId) open @endif>
                        <summary
                            class="flex cursor-pointer list-none flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p
                                    class="flex items-center gap-1.5 text-[9px] font-extrabold uppercase tracking-widest text-muted">
                                    <span class="h-2.5 w-2.5 flex-none rounded-full"
                                        style="background:{{ $color }}"></span>Project
                                </p>
                                <h3 class="mt-0.5 wrap-break-word text-xl font-black leading-tight">
                                    {{ $project?->name ?? 'Tanpa Project' }}</h3>
                                <p class="mt-1 text-xs text-muted">
                                    @if ($project?->start_date || $project?->end_date)
                                        {{ collect([$project->start_date?->translatedFormat('M Y'), $project->end_date?->translatedFormat('M Y')])->filter()->unique()->implode('–') }}
                                        ·
                                    @endif
                                    {{ $card['total'] }} item
                                    @if ($project?->progress_recap)
                                        · {{ \Illuminate\Support\Str::limit($project->progress_recap, 90) }}
                                    @endif
                                </p>
                                <div class="mt-2 flex items-center gap-2.5">
                                    <span class="text-[10px] font-bold text-muted">Progress</span>
                                    <div class="h-2 max-w-sm min-w-0 flex-1 overflow-hidden rounded-full bg-[#ece7dd]">
                                        <div class="h-full rounded-full"
                                            style="width:{{ $card['pct'] }}%;background:{{ $color }}"></div>
                                    </div>
                                    <span class="text-sm font-black">{{ $card['pct'] }}%</span>
                                </div>
                            </div>
                            <div class="flex flex-none flex-wrap items-center gap-1.5 sm:justify-end">
                                @if ($project)
                                    <span class="{{ $priorityClass($project->priority) }}">{{ $project->priority }}</span>
                                    <span class="{{ $statusClass($project->status) }}">{{ $project->status }}</span>
                                @endif
                                <span
                                    class="grid h-8 w-8 place-items-center rounded-xl bg-white text-xs font-black transition group-open:rotate-180">⌄</span>
                            </div>
                        </summary>

                        <div class="border-t border-line">
                            @if ($project)
                                <div class="flex flex-wrap items-center gap-2 border-b border-line bg-white/60 px-4 py-2.5">
                                    <a href="{{ route('dashboard.work.tracker.projects.excel', $project) }}"
                                        class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">Download
                                        Excel</a>
                                    @if ($canManage)
                                        <a href="{{ route('dashboard.work.tracker.projects.sync.show', $project) }}"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">Sinkron
                                            Sheet</a>
                                    @endif
                                </div>
                            @endif
                            @if ($canManage && $project)
                                <form method="POST"
                                    action="{{ route('dashboard.work.tracker.sections.store', $project) }}"
                                    onsubmit="wtRememberOpen({ p: {{ $project->id }}, s: '' })"
                                    class="flex flex-wrap items-center gap-2 border-b border-line bg-white/60 px-4 py-2.5">
                                    @csrf
                                    <input name="name" required maxlength="80" list="wtSectionSuggestions"
                                        autocomplete="off" placeholder="Section baru (mis. CONTRACT)"
                                        class="min-w-0 flex-1 rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-bold sm:max-w-xs sm:flex-none">
                                    <button type="submit"
                                        class="rounded-2xl border border-line bg-white px-3.5 py-1.5 text-[11px] font-extrabold">+
                                        Section</button>
                                </form>
                            @endif
                            @forelse ($card['sections'] as $section)
                                @php
                                    $secColor = $section['color'];
                                    $secInk = \App\Models\Project::contrastTextFor($secColor);
                                    $sec = $section['model'];
                                    $secKey = $cardKey . ':' . md5($section['name']);
                                @endphp
                                <details class="wt-section border-b border-line last:border-b-0"
                                    data-wt-key="{{ $secKey }}" data-wt-project="{{ $project?->id ?? 0 }}"
                                    data-wt-section="{{ $section['name'] }}">
                                    <summary
                                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3"
                                        style="background:{{ $secColor }};color:{{ $secInk }}">
                                        <span
                                            class="flex min-w-0 items-center gap-2 wrap-break-word text-[12px] font-black uppercase tracking-wide">
                                            <span class="wt-chevron text-xs transition">⌄</span>{{ $section['name'] }}
                                        </span>
                                        <span class="flex flex-wrap items-center justify-end gap-1.5 text-[#17130a]">
                                            @if (! empty($section['viewer_ids']))
                                                <span title="Hanya orang tertentu yang bisa melihat section ini"
                                                    class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-extrabold">🔒
                                                    {{ count($section['viewer_ids']) }} orang</span>
                                            @endif
                                            @if ($canManage && $sec)
                                                {{-- Visibility per orang (kosong = semua orang boleh lihat). --}}
                                                <button type="button" title="Atur siapa yang bisa melihat section ini"
                                                    @click.prevent.stop="viewersModal = @js(['name' => $section['name'], 'action' => route('dashboard.work.tracker.sections.viewers', $sec), 'ids' => $section['viewer_ids']])"
                                                    class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-[13px]">👁</button>
                                                {{-- Warna: simpan langsung (PATCH JSON), tanpa reload. --}}
                                                <label title="Ubah warna section" onclick="event.stopPropagation()"
                                                    class="relative grid h-7 w-7 cursor-pointer place-items-center rounded-lg bg-white/70">
                                                    <span class="wt-swatch h-4 w-4 rounded-full border border-black/25"
                                                        style="background:{{ $secColor }}"></span>
                                                    <input type="color" value="{{ $secColor }}"
                                                        data-orig="{{ $secColor }}"
                                                        onchange="wtSetSectionColor({{ $sec->id }}, this)"
                                                        aria-label="Warna section {{ $section['name'] }}"
                                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                                                </label>
                                                @unless ($filtering)
                                                    @foreach ([['up', '↑', 'Pindah section ke atas', $section['is_first']], ['down', '↓', 'Pindah section ke bawah', $section['is_last']]] as [$dir, $arrow, $title, $disabled])
                                                        <form method="POST"
                                                            action="{{ route('dashboard.work.tracker.sections.move', $sec) }}"
                                                            onclick="event.stopPropagation()" onsubmit="wtRememberOpen()">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="direction" value="{{ $dir }}">
                                                            <button type="submit" title="{{ $title }}"
                                                                @disabled($disabled)
                                                                class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black disabled:cursor-not-allowed disabled:opacity-35">{{ $arrow }}</button>
                                                        </form>
                                                    @endforeach
                                                    <button type="button" title="Hapus section"
                                                        @click.prevent.stop="typed = ''; sectionDelete = @js(['name' => $section['name'], 'count' => $section['rows']->count(), 'action' => route('dashboard.work.tracker.sections.destroy', $sec)])"
                                                        class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black text-[#9b392f]">×</button>
                                                @endunless
                                            @endif
                                            @if ($canManage && $project)
                                                <button type="button" title="Tambah item di section ini"
                                                    onclick="event.preventDefault();event.stopPropagation();wtOpenAddTask('{{ $project?->id }}', @js($section['name'] === 'TANPA SECTION' ? '' : $section['name']))"
                                                    class="grid h-7 w-7 place-items-center rounded-lg bg-white/70 text-sm font-black">+</button>
                                            @endif
                                            <span
                                                class="rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-extrabold">{{ $section['rows']->count() }}
                                                item</span>
                                        </span>
                                    </summary>

                                    @if ($section['rows']->isEmpty())
                                        <p class="bg-white px-4 py-3 text-[11px] text-muted">Section ini masih kosong.
                                            @if ($canManage && $project)
                                                Tambah item lewat tombol <b>+</b> di header.
                                            @endif
                                        </p>
                                    @else
                                        <div class="wt-tablewrap overflow-x-auto bg-white">
                                            <table class="wt-table w-full text-left text-[11px]">
                                                <thead>
                                                    <tr
                                                        class="text-[9px] font-extrabold uppercase tracking-widest text-muted">
                                                        <th class="w-10 px-3 py-2">No</th>
                                                        <th class="px-3 py-2">Item</th>
                                                        <th class="w-24 px-3 py-2">Date</th>
                                                        <th class="w-24 px-3 py-2">Focus</th>
                                                        <th class="w-32 px-3 py-2">PIC</th>
                                                        <th class="w-36 px-3 py-2">Progress</th>
                                                        <th class="w-56 px-3 py-2">Note</th>
                                                        <th class="w-14 px-3 py-2">Link</th>
                                                        <th class="w-24 px-3 py-2"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($section['rows']->sortBy(fn($i) => [$i->due_date?->timestamp ?? PHP_INT_MAX, $i->item_no]) as $item)
                                                        @php
                                                            $focus = $item->computedFocus();
                                                            [$fbg, $ftx] = $focusColors($focus);
                                                        @endphp
                                                        <tr
                                                            class="border-t border-line align-middle {{ $item->progress === 'Done' ? 'opacity-70' : '' }}">
                                                            <td data-label="No"
                                                                class="wt-no px-3 py-2.5 font-bold text-muted">
                                                                {{ $item->item_no }}</td>
                                                            <td class="wt-title px-3 py-2.5 font-black leading-snug"><span
                                                                    class="wt-no-inline">#{{ $item->item_no }}</span>@if ($canManage)<input type="text" value="{{ $item->title }}" data-orig="{{ $item->title }}" maxlength="255"
                                                                        onblur="wtSaveField({{ $item->id }}, 'title', this)"
                                                                        onkeydown="if(event.key==='Enter'){this.blur()}else if(event.key==='Escape'){this.value=this.dataset.orig;this.blur()}"
                                                                        aria-label="Judul item"
                                                                        class="wt-inline w-full rounded-lg border border-transparent bg-transparent px-1.5 py-1 font-black hover:border-line focus:border-line focus:bg-white">@else{{ $item->title }}@endif
                                                            </td>
                                                            <td data-label="Date" class="px-3 py-2.5 whitespace-nowrap">
                                                                @if ($canManage)
                                                                    <input type="date" value="{{ $item->due_date?->format('Y-m-d') }}" data-orig="{{ $item->due_date?->format('Y-m-d') }}" data-reload="1"
                                                                        onchange="wtSaveField({{ $item->id }}, 'due_date', this)"
                                                                        aria-label="Tanggal"
                                                                        class="wt-inline w-31 rounded-lg border border-transparent bg-transparent px-1 py-1 text-[11px] hover:border-line focus:border-line focus:bg-white">
                                                                @else
                                                                    {{ $item->due_date?->format('d/m/Y') ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td data-label="Focus" class="px-3 py-2.5">
                                                                <span
                                                                    class="inline-flex rounded-full px-2 py-0.5 text-[8px] font-black"
                                                                    style="background:{{ $fbg }};color:{{ $ftx }}">{{ $focus }}</span>
                                                            </td>
                                                            <td data-label="PIC"
                                                                class="px-3 py-2.5 font-bold text-[#5e5952]">
                                                                @if ($canManage)
                                                                    <select data-orig="{{ $item->pic_employee_id }}" onchange="wtSaveField({{ $item->id }}, 'pic_employee_id', this)"
                                                                        aria-label="PIC utama"
                                                                        class="wt-inline w-full rounded-lg border border-transparent bg-transparent px-1 py-1 text-[11px] font-bold hover:border-line focus:border-line focus:bg-white">
                                                                        <option value="">Belum di-assign</option>
                                                                        @foreach ($employees as $emp)
                                                                            <option value="{{ $emp->id }}" @selected($item->pic_employee_id === $emp->id)>{{ $emp->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    {{ $item->pic?->name ?? 'Belum di-assign' }}
                                                                @endif
                                                                @foreach ($item->additionalPics as $extraPic)
                                                                    <span
                                                                        class="block text-[11px] font-bold text-[#5e5952]">{{ $extraPic->name }}</span>
                                                                @endforeach
                                                                {{-- Catatan teks lama / ALL TEAM hanya tampil kalau tidak ada PIC tambahan sungguhan. --}}
                                                                @if ($item->additional_pic && $item->additionalPics->isEmpty())
                                                                    <span
                                                                        class="block text-[10px] font-medium text-muted">+
                                                                        {{ $item->additional_pic }}</span>
                                                                @endif
                                                            </td>
                                                            <td data-label="Progress" class="px-3 py-2.5">
                                                                @if ($canManage)
                                                                    <select
                                                                        onchange="wtQuickProgress({{ $item->id }}, this)"
                                                                        class="w-full rounded-lg border-0 px-2 py-1.5 text-[11px] font-extrabold {{ $progressClass($item->progress) }}">
                                                                        @foreach (\App\Models\WorkItem::PROGRESS_OPTIONS as $status)
                                                                            <option value="{{ $status }}"
                                                                                @selected($item->progress === $status)>
                                                                                {{ $status }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <span
                                                                        class="inline-flex rounded-lg px-2 py-1 text-[11px] font-extrabold {{ $progressClass($item->progress) }}">{{ $item->progress }}</span>
                                                                @endif
                                                            </td>
                                                            <td data-label="Note" class="wt-note px-3 py-2.5">
                                                                @if ($canManage)
                                                                    <textarea rows="1" placeholder="Tambah note / blocker..." data-orig="{{ $item->notes }}"
                                                                        onblur="wtSaveNote({{ $item->id }}, this)"
                                                                        class="wt-note-input block w-full resize-y rounded-lg border border-transparent bg-transparent px-1.5 py-1.5 text-[11px] leading-snug hover:border-line focus:border-line focus:bg-white">{{ $item->notes }}</textarea>
                                                                @else
                                                                    {{ $item->notes ?: '-' }}
                                                                @endif
                                                            </td>
                                                            <td data-label="Link" class="px-3 py-2.5">
                                                                @if ($item->link)
                                                                    <a href="{{ $item->link }}" target="_blank"
                                                                        rel="noopener"
                                                                        class="font-extrabold text-[#2647b8] underline">Open</a>
                                                                @elseif (! $canManage)
                                                                    -
                                                                @endif
                                                                @if ($canManage)
                                                                    <button type="button" title="{{ $item->link ? 'Ubah link' : 'Tambah link' }}"
                                                                        onclick="wtEditLink({{ $item->id }}, this)" data-link="{{ $item->link }}"
                                                                        class="ml-1 rounded-md bg-[#ece7dd] px-1.5 py-0.5 text-[9px] font-extrabold">{{ $item->link ? '✎' : '+ Link' }}</button>
                                                                @endif
                                                            </td>
                                                            <td class="wt-actions px-3 py-2.5">
                                                                @if ($canManage)
                                                                    <div class="flex gap-1">
                                                                        <button type="button"
                                                                            data-item="{{ $item->toJson() }}"
                                                                            onclick="wtOpenEditTask(JSON.parse(this.dataset.item))"
                                                                            class="rounded-lg bg-[#ece7dd] px-2 py-1 text-[9px] font-extrabold">Edit</button>
                                                                        <form method="POST"
                                                                            action="{{ route('dashboard.work.tracker.items.destroy', $item) }}"
                                                                            data-confirm="Hapus task &quot;{{ $item->title }}&quot;?"
                                                                            data-confirm-title="Hapus task?"
                                                                            data-confirm-button="Ya, hapus">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                class="rounded-lg bg-[#ffded8] px-2 py-1 text-[9px] font-extrabold text-[#9b392f]">Hapus</button>
                                                                        </form>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </details>
                            @empty
                                <p class="p-4 text-xs text-muted">
                                    {{ $card['total'] > 0 ? 'Belum ada item pada filter ini.' : 'Project ini belum punya task.' }}
                                    @if ($canManage && $card['total'] === 0 && $project)
                                        <button type="button" onclick="wtOpenAddTask('{{ $project->id }}', '')"
                                            class="ml-1 font-extrabold text-ink underline">Tambah task pertama</button>
                                    @endif
                                </p>
                            @endforelse
                        </div>
                    </details>
                </article>
            @empty
                <p class="rounded-3xl border border-dashed border-line p-6 text-sm text-muted">Belum ada project. Buat dulu
                    di menu <a href="{{ route('dashboard.work.projects.index') }}"
                        class="font-extrabold underline">Projects</a>.
                </p>
            @endforelse
        </div>

        @if ($canManage && $projects->isNotEmpty())
            {{-- Modal Tambah/Edit Task --}}
            <div x-show="taskModalOpen" x-cloak
                class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
                <div @click.outside="taskModalOpen = false"
                    class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-black" id="wtTaskFormTitle">Tambah Task</h3>
                        <button type="button" @click="taskModalOpen = false"
                            class="grid h-9 w-9 place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                    </div>
                    <form id="wtTaskForm" method="POST" action="{{ route('dashboard.work.tracker.items.store') }}"
                        class="grid gap-3">
                        @csrf
                        <span id="wtTaskFormMethod"></span>
                        <div class="grid content-start gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Item / Pekerjaan</label>
                            <input name="title" id="wtTaskTitle" required
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        </div>
                        <div class="grid items-start gap-3 sm:grid-cols-2">
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Project</label>
                                <select name="project_id" id="wtTaskProject" required onchange="wtRebuildSections('')"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                    <option value="" disabled selected>Pilih project…</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Section</label>
                                <select id="wtTaskSectionSelect" required onchange="wtSectionChanged()"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm"></select>
                                {{-- Nilai yang dikirim tetap `section`; input teks cuma muncul saat harus membuat section baru. --}}
                                <input name="section" id="wtTaskSection" list="wtSectionSuggestions" autocomplete="off"
                                    maxlength="80" placeholder="Nama section baru (mis. CONTRACT)"
                                    class="hidden rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                <p id="wtSectionHint" class="hidden text-[10px] font-bold text-[#8a5a00]"></p>
                                <datalist id="wtSectionSuggestions">
                                    @foreach (\App\Models\WorkItem::SECTION_SUGGESTIONS as $section)
                                        <option value="{{ $section }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="grid items-start gap-3 sm:grid-cols-2">
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">PIC 1</label>
                                <select name="pic_employee_id" id="wtTaskPic" onchange="wtSyncPicOptions()"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                    <option value="">Belum di-assign</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Date / Due</label>
                                <input type="date" name="due_date" id="wtTaskDue"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                            </div>
                        </div>
                        <div class="grid items-start gap-3 sm:grid-cols-2">
                            @foreach ([2, 3] as $picNo)
                                <div class="grid content-start gap-1">
                                    <label class="text-[10px] font-extrabold uppercase text-muted">PIC {{ $picNo }}
                                        (opsional)
                                    </label>
                                    <select name="additional_pic_ids[]" id="wtTaskPic{{ $picNo }}"
                                        onchange="wtSyncPicOptions()"
                                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                        <option value="">Tidak ada</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <p class="text-[10px] font-medium text-muted sm:col-span-2">Maksimal 3 PIC. Semua PIC
                                melihat task ini di daftar kerjanya masing-masing.</p>
                        </div>
                        <div class="grid items-start gap-3 sm:grid-cols-2">
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Progress</label>
                                <select name="progress" id="wtTaskProgress"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                    @foreach (\App\Models\WorkItem::PROGRESS_OPTIONS as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid content-start gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Priority</label>
                                <select name="priority" id="wtTaskPriority"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                    @foreach (\App\Models\WorkItem::PRIORITIES as $priority)
                                        <option value="{{ $priority }}" @selected($priority === 'Medium')>
                                            {{ $priority }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid content-start gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Link (opsional)</label>
                            <input name="link" id="wtTaskLink" placeholder="https://..."
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                        </div>
                        <div class="grid content-start gap-1">
                            <label class="text-[10px] font-extrabold uppercase text-muted">Note / Progress Recap</label>
                            <textarea name="notes" id="wtTaskNotes" rows="2"
                                placeholder="Apa update terakhir, blocker, atau next action?"
                                class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm"></textarea>
                        </div>
                        <button type="submit" class="btn-wsm-black mt-1">Save Item</button>
                    </form>
                </div>
            </div>

            {{-- Modal visibility section: pilih orang yang boleh melihat; kosong = semua. --}}
            <div x-show="viewersModal" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
                <div @click.outside="viewersModal = null" class="w-full max-w-md rounded-4xl bg-cream p-5">
                    <h3 class="text-lg font-black">Siapa yang bisa lihat?</h3>
                    <p class="mt-1 text-[12px] text-muted">Section <b x-text="viewersModal ? viewersModal.name : ''"></b>.
                        Kosongkan semua centang agar terbuka untuk semua orang. Owner/Developer dan PIC item di section ini
                        selalu tetap bisa melihat.</p>
                    <form method="POST" :action="viewersModal ? viewersModal.action : '#'" onsubmit="wtRememberOpen()"
                        class="mt-3 grid gap-3">
                        @csrf
                        @method('PUT')
                        <div class="grid max-h-64 gap-1 overflow-y-auto rounded-2xl border border-line bg-white p-2">
                            @foreach ($employees as $emp)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl px-2 py-1.5 text-[12px] font-bold hover:bg-[#f6f2eb]">
                                    <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}"
                                        :checked="viewersModal && viewersModal.ids.includes({{ $emp->id }})">
                                    {{ $emp->name }}
                                </label>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="viewersModal = null" class="btn-wsm-white">Batal</button>
                            <button type="submit" class="btn-wsm-black">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal hapus section: section berisi item -> nama section harus diketik persis. --}}
            <div x-show="sectionDelete" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
                <div @click.outside="sectionDelete = null" class="w-full max-w-sm rounded-4xl bg-cream p-5">
                    <h3 class="text-lg font-black">Hapus section?</h3>
                    <template x-if="sectionDelete && sectionDelete.count === 0">
                        <p class="mt-2 text-[13px] text-muted">Section <b x-text="sectionDelete.name"></b> masih kosong
                            dan akan dihapus dari project ini.</p>
                    </template>
                    <template x-if="sectionDelete && sectionDelete.count > 0">
                        <p class="mt-2 text-[13px] text-[#9b392f]">Section <b x-text="sectionDelete.name"></b> berisi
                            <b x-text="sectionDelete.count + ' item'"></b>. Section <u>dan seluruh item di dalamnya</u>
                            akan terhapus permanen.
                        </p>
                    </template>
                    <form method="POST" :action="sectionDelete ? sectionDelete.action : '#'" onsubmit="wtRememberOpen()"
                        class="mt-3 grid gap-3">
                        @csrf
                        @method('DELETE')
                        <template x-if="sectionDelete && sectionDelete.count > 0">
                            <div class="grid gap-1">
                                <label class="text-[10px] font-extrabold uppercase text-muted">Ketik nama section untuk
                                    konfirmasi</label>
                                <input name="confirm_name" x-model="typed" autocomplete="off"
                                    :placeholder="sectionDelete.name"
                                    class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                            </div>
                        </template>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="sectionDelete = null" class="btn-wsm-white">Batal</button>
                            <button type="submit"
                                :disabled="sectionDelete && sectionDelete.count > 0 && typed !== sectionDelete.name"
                                class="btn-wsm-red disabled:cursor-not-allowed disabled:opacity-40">Ya, hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <style>
        details.wt-section[open]>summary .wt-chevron {
            transform: rotate(180deg);
        }

        details>summary::-webkit-details-marker {
            display: none;
        }

        /* Item di dalam section: responsif mengikuti LEBAR AREA KONTEN (container query), bukan
                       lebar layar — sidebar dashboard makan ~260px. Tabel 9 kolom butuh ~1100px; di bawah itu
                       tiap item jadi kartu 4 kolom, dan di bawah 640px jadi 2 kolom. */
        .wt-tablewrap {
            container-type: inline-size;
        }

        .wt-no-inline {
            display: none;
        }

        /* Note 1 baris supaya baris item sejajar dengan select Progress; tumbuh otomatis kalau isinya panjang. */
        .wt-note-input {
            min-height: 2rem;
            field-sizing: content;
        }

        @container (max-width: 1100px)

            {
            .wt-table thead {
                display: none;
            }

            .wt-table,
            .wt-table tbody {
                display: block;
                width: 100%;
            }

            .wt-table tr {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 4px 12px;
                padding: 12px 6px;
            }

            .wt-table td {
                display: block;
                min-width: 0;
                padding: 4px 8px !important;
            }

            .wt-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 2px;
                font-size: 9px;
                font-weight: 800;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #8b867e;
            }

            .wt-table td.wt-no {
                display: none;
            }

            .wt-table .wt-no-inline {
                display: inline-flex;
                margin-right: 6px;
                padding: 1px 6px;
                border-radius: 999px;
                background: #ece7dd;
                font-size: 9px;
                font-weight: 800;
                color: #6b665e;
                vertical-align: 1px;
            }

            .wt-table .wt-title,
            .wt-table .wt-actions {
                grid-column: 1 / -1;
            }

            .wt-table .wt-title {
                font-size: 13px;
            }

            .wt-table .wt-note {
                grid-column: span 3;
            }
        }

        @container (max-width: 640px)

            {
            .wt-table tr {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wt-table .wt-note {
                grid-column: 1 / -1;
            }
        }
    </style>

    <script>
        const wtCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
        const wtBase = "{{ url('/dashboard/work/tracker/task') }}";

        // --- Progress: simpan langsung lalu reload (ringkasan & progress bar ikut segar) ---
        function wtQuickProgress(id, select) {
            select.disabled = true;
            fetch(`${wtBase}/${id}/progress`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': wtCsrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    progress: select.value
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                wtRememberOpen();
                window.location.reload();
            }).catch(() => {
                select.disabled = false;
                wtError('Gagal update progress, coba lagi.');
            });
        }

        // --- Edit satuan (judul / tanggal / PIC / link): simpan saat berubah, tanpa buka form Edit ---
        function wtSaveField(id, field, el) {
            const orig = el.dataset.orig ?? '';
            if (el.value === orig) return;
            el.classList.add('opacity-60');
            fetch(`${wtBase}/${id}/field`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': wtCsrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    field,
                    value: el.value
                }),
            }).then(async (res) => {
                if (!res.ok) {
                    const body = await res.json().catch(() => ({}));
                    throw new Error(body.errors ? Object.values(body.errors).flat()[0] : (body.message || ''));
                }
                el.dataset.orig = el.value;
                // Tanggal/PIC mengubah badge Focus & ringkasan -> muat ulang, state buka dipertahankan.
                if (el.dataset.reload === '1' || field === 'pic_employee_id') {
                    wtRememberOpen();
                    window.location.reload();
                    return;
                }
                el.classList.remove('opacity-60');
                el.classList.add('border-[#27c84d]');
                setTimeout(() => el.classList.remove('border-[#27c84d]'), 900);
            }).catch((e) => {
                el.classList.remove('opacity-60');
                el.value = orig;
                wtError(e.message || 'Gagal menyimpan perubahan, coba lagi.');
            });
        }

        // Link: dialog SweetAlert (WsmAlert.prompt). URL jarang diubah, jadi tidak dibuat input permanen
        // di kolom sempit. Isian kosong = hapus link. Fallback ke prompt bawaan kalau aset JS belum ter-build.
        async function wtEditLink(id, btn) {
            const current = btn.dataset.link || '';
            let next;

            if (window.WsmAlert && WsmAlert.prompt) {
                const result = await WsmAlert.prompt({
                    title: current ? 'Ubah link' : 'Tambah link',
                    text: 'Tempel link Google Drive / Sheet / kontrak. Kosongkan lalu simpan untuk menghapus link.',
                    value: current,
                    placeholder: 'https://...',
                    inputType: 'url',
                    validate: (v) => (v.trim() === '' || /^https?:\/\/\S+$/i.test(v.trim())) ? undefined :
                        'Link harus diawali http:// atau https://',
                });
                if (!result.isConfirmed) return;
                next = (result.value || '').trim();
            } else {
                const typed = window.prompt('Link (kosongkan untuk menghapus):', current);
                if (typed === null) return;
                next = typed.trim();
            }

            if (next === current) return;
            fetch(`${wtBase}/${id}/field`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': wtCsrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    field: 'link',
                    value: next
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                wtRememberOpen();
                window.location.reload();
            }).catch(() => wtError('Link gagal disimpan. Pastikan diawali http:// atau https:// (maks 255 karakter).'));
        }

        // --- Warna section: simpan langsung, header diwarnai ulang tanpa reload ---
        const wtSectionBase = "{{ url('/dashboard/work/tracker/section') }}";

        function wtInkFor(hex) {
            const n = parseInt(hex.slice(1), 16);
            const lum = (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
            return lum > 0.6 ? '#17130a' : '#ffffff';
        }

        function wtSetSectionColor(id, input) {
            const color = input.value;
            fetch(`${wtSectionBase}/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': wtCsrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    color
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                const summary = input.closest('summary');
                summary.style.background = color;
                summary.style.color = wtInkFor(color);
                input.parentElement.querySelector('.wt-swatch').style.background = color;
                input.dataset.orig = color;
            }).catch(() => {
                input.value = input.dataset.orig;
                wtError('Warna section gagal disimpan, coba lagi.');
            });
        }

        // --- Note inline: simpan saat blur kalau isinya berubah ---
        function wtSaveNote(id, el) {
            if (el.value === (el.dataset.orig ?? '')) return;
            el.classList.add('opacity-60');
            fetch(`${wtBase}/${id}/note`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': wtCsrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    notes: el.value
                }),
            }).then((res) => {
                if (!res.ok) throw new Error();
                el.dataset.orig = el.value;
                el.classList.remove('opacity-60');
                el.classList.add('border-[#27c84d]');
                setTimeout(() => el.classList.remove('border-[#27c84d]'), 900);
            }).catch(() => {
                el.classList.remove('opacity-60');
                wtError('Note gagal disimpan, coba lagi.');
            });
        }

        function wtError(message) {
            if (window.WsmAlert) WsmAlert.error(message);
            else alert(message);
        }

        // Belum ada project -> form task tidak dibuka; arahkan buat project dulu.
        function wtNeedProject() {
            const go = () => window.location.href = "{{ route('dashboard.work.projects.index') }}";
            if (window.WsmAlert) {
                WsmAlert.confirm({
                    title: 'Belum ada project',
                    text: 'Task harus masuk ke sebuah project. Buat project dulu di menu Projects.',
                    confirmText: 'Buat Project',
                    cancelText: 'Nanti',
                    icon: 'info',
                }).then((r) => r.isConfirmed && go());
            } else if (confirm('Belum ada project. Buat project dulu di menu Projects?')) {
                go();
            }
        }

        // --- Buka/tutup semua. Default SEMUA TERTUTUP tiap halaman dibuka. ---
        function wtToggleAll(open) {
            document.querySelectorAll('details[data-wt-key]').forEach((d) => d.open = open);
        }

        // Aksi yang me-reload halaman (ganti progress, simpan/hapus task) tidak boleh
        // menutup ulang semuanya: state buka disimpan sebentar di sessionStorage, dipakai
        // sekali saat halaman berikutnya dimuat, lalu dibuang (kedaluwarsa 15 detik).
        function wtRememberOpen(target) {
            try {
                sessionStorage.setItem('wt-restore', JSON.stringify({
                    t: Date.now(),
                    keys: [...document.querySelectorAll('details[data-wt-key][open]')].map((d) => d.dataset
                        .wtKey),
                    target: target || null,
                }));
            } catch (e) {}
        }
        (function wtRestoreOpen() {
            let saved = null;
            try {
                saved = JSON.parse(sessionStorage.getItem('wt-restore'));
                sessionStorage.removeItem('wt-restore');
            } catch (e) {}
            if (!saved || Date.now() - saved.t > 15000) return;
            document.querySelectorAll('details[data-wt-key]').forEach((d) => {
                if (saved.keys.includes(d.dataset.wtKey)) d.open = true;
            });
            if (saved.target) {
                const card = document.querySelector(`details[data-wt-key="p${saved.target.p}"]`);
                if (card) card.open = true;
                document.querySelectorAll('details.wt-section').forEach((d) => {
                    if (d.dataset.wtProject === String(saved.target.p) && d.dataset.wtSection === saved.target
                        .s) d.open = true;
                });
            }
        })();
        document.getElementById('wtTaskForm')?.addEventListener('submit', () => {
            wtRememberOpen({
                p: document.getElementById('wtTaskProject').value || 0,
                s: document.getElementById('wtTaskSection').value,
            });
        });
        // Form hapus: ingat state hanya setelah dikonfirmasi (submit kedua dari alerts.js), bukan saat dibatalkan.
        document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', () => {
            if (f.dataset.confirmed === '1') wtRememberOpen();
        }));

        // 3 lapis: menutup project ikut menutup semua section di dalamnya.
        document.querySelectorAll('details.wt-card').forEach((card) => card.addEventListener('toggle', () => {
            if (!card.open) card.querySelectorAll('details.wt-section').forEach((s) => s.open = false);
        }));

        // --- Dropdown Section di form task: HANYA section yang sudah ada di project terpilih,
        // atau buat baru. Belum ada section sama sekali -> langsung minta buat section baru. ---
        const wtSections = @json($sectionsByProject);
        const wtOnlyProject = @json($projects->count() === 1 ? $projects->first()->id : null);

        function wtRebuildSections(selected) {
            const pid = document.getElementById('wtTaskProject').value;
            const sel = document.getElementById('wtTaskSectionSelect');
            const input = document.getElementById('wtTaskSection');
            const hint = document.getElementById('wtSectionHint');
            const list = pid ? (wtSections[pid] || []) : [];
            const opt = (value, label, disabled = false) => {
                const o = document.createElement('option');
                o.value = value;
                o.textContent = label;
                o.disabled = disabled;
                return o;
            };
            const useInput = (on, value = '') => {
                input.classList.toggle('hidden', !on);
                input.required = on;
                input.value = value;
            };

            sel.innerHTML = '';
            hint.classList.add('hidden');
            sel.classList.remove('hidden');

            if (!pid) {
                // belum pilih project
                sel.appendChild(opt('', 'Pilih project dulu', true));
                sel.value = '';
                sel.disabled = true;
                sel.required = false;
                useInput(false);
                return;
            }

            if (list.length === 0) {
                // project belum punya section -> wajib buat baru
                sel.classList.add('hidden');
                sel.disabled = true;
                sel.required = false;
                hint.textContent = 'Project ini belum punya section. Buat section pertamanya.';
                hint.classList.remove('hidden');
                useInput(true, selected || '');
                return;
            }

            sel.disabled = false;
            sel.required = true;
            sel.appendChild(opt('', 'Pilih section…', true));
            list.forEach((name) => sel.appendChild(opt(name, name)));
            sel.appendChild(opt('__new__', '+ Section baru…'));

            if (selected && list.includes(selected)) {
                sel.value = selected;
                useInput(false, selected);
            } else if (selected) {
                sel.value = '__new__';
                useInput(true, selected);
            } else {
                sel.value = '';
                useInput(false);
            }
        }

        function wtSectionChanged() {
            const sel = document.getElementById('wtTaskSectionSelect');
            const input = document.getElementById('wtTaskSection');
            if (sel.value === '__new__') {
                input.classList.remove('hidden');
                input.required = true;
                input.value = '';
                input.focus();
            } else {
                input.classList.add('hidden');
                input.required = false;
                input.value = sel.value;
            }
        }

        // PIC 1 / 2 / 3: orang yang sudah dipilih di salah satu kolom dinonaktifkan di dua kolom lainnya.
        function wtSyncPicOptions() {
            const ids = ['wtTaskPic', 'wtTaskPic2', 'wtTaskPic3'];
            const chosen = ids.map(id => document.getElementById(id).value);
            ids.forEach((id, index) => {
                document.getElementById(id).querySelectorAll('option').forEach(opt => {
                    opt.disabled = opt.value !== '' && chosen.some((val, i) => i !== index && val === opt
                        .value);
                });
            });
        }

        // --- Form Task: 1 modal untuk tambah & edit ---
        function wtOpenAddTask(projectId, section) {
            document.getElementById('wtTaskFormTitle').textContent = 'Tambah Task';
            const form = document.getElementById('wtTaskForm');
            form.reset();
            form.action = "{{ route('dashboard.work.tracker.items.store') }}";
            document.getElementById('wtTaskFormMethod').innerHTML = '';
            document.getElementById('wtTaskProject').value = projectId || wtOnlyProject || '';
            wtRebuildSections(section || '');
            wtSyncPicOptions();
            window.dispatchEvent(new CustomEvent('wt-open-task-modal'));
        }

        function wtOpenEditTask(item) {
            document.getElementById('wtTaskFormTitle').textContent = 'Edit Task';
            const form = document.getElementById('wtTaskForm');
            form.action = `${wtBase}/${item.id}`;
            document.getElementById('wtTaskFormMethod').innerHTML = '<input type="hidden" name="_method" value="PATCH">';
            document.getElementById('wtTaskTitle').value = item.title || '';
            document.getElementById('wtTaskProject').value = item.project_id || '';
            wtRebuildSections(item.section || '');
            document.getElementById('wtTaskPic').value = item.pic_employee_id || '';
            const extraPics = (item.additional_pics || []).map(u => u.id);
            document.getElementById('wtTaskPic2').value = extraPics[0] || '';
            document.getElementById('wtTaskPic3').value = extraPics[1] || '';
            wtSyncPicOptions();
            document.getElementById('wtTaskDue').value = item.due_date ? item.due_date.substring(0, 10) : '';
            document.getElementById('wtTaskProgress').value = item.progress || 'Pending';
            document.getElementById('wtTaskPriority').value = item.priority || 'Medium';
            document.getElementById('wtTaskLink').value = item.link || '';
            document.getElementById('wtTaskNotes').value = item.notes || '';
            window.dispatchEvent(new CustomEvent('wt-open-task-modal'));
        }
    </script>
@endsection
