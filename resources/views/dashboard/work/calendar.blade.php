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

    2026-10-03 — revisi permintaan tim:
      - Ritme kerja pindah ke HEADER kolom hari (bukan di tiap tanggal), 7 hari:
        Sabtu/Minggu berisi ritme "event".
      - Maks 3 chip per tanggal, sisanya "+N item"; klik tanggal/+N buka POPUP harian.
      - Drag & drop chip antar tanggal HANYA di desktop (layar lebar + mouse) dan
        hanya untuk yang punya akses work=manage; di HP/tablet kalender cuma
        bisa dilihat + buka popup harian.

    2026-10-20 — revisi permintaan tim:
      - Klik item (chip di tanggal ATAU item di popup harian) membuka MODAL
        detail, bukan pindah halaman. Yang punya akses work=manage langsung bisa
        mengedit (judul, tanggal, PIC 1-3, progress, prioritas, link, catatan);
        yang hanya view melihat detailnya saja. Simpan lewat PATCH JSON.
      - Mode TAHUN (?view=year): garis waktu project selama setahun (1 baris =
        1 project) untuk melihat berapa lama project berjalan.

    2026-10-21 — disamakan dengan kalender Samsung (permintaan tim):
      - Mode TAHUN: 12 bulan mini (Minggu-Sabtu) di atas garis waktu project.
        Hari yang punya deadline berwarna project; klik/ketuk bulan langsung
        membuka kalender bulan itu.
      - Mode BULAN: sel membulat dengan sedikit warna project, nomor tanggal di
        tengah (hari ini = lingkaran hitam, Minggu merah), chip satu baris. Di HP
        grid muat satu layar (tanpa geser samping); judul lengkap + detail lewat
        popup harian (popup tidak berubah).

    2026-10-21 (2) — garis waktu project TERSAMBUNG:
      - Gantt di mode tahun dihapus.
      - Mode BULAN: project dengan tanggal Mulai & Selesai = bar menyambung
        antar-hari (lane per minggu, label di awal tiap minggu).
      - Mode TAHUN: jalur warna menyambung di bulan mini (pekat = ada deadline).
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Work Control — Timeline Calendar', 'navActive' => 'modules'])

@section('content')
    @php
        $isYear = $mode === 'year';
        $filterQuery = array_filter(['project' => $projectFilter, 'pic' => $picFilter]);
        $navQuery = fn(string $month) => ['month' => $month] + $filterQuery;
        $yearQuery = fn(int $y) => ['view' => 'year', 'year' => $y] + $filterQuery;
        $todayDow = now()->dayOfWeek;

        if ($isYear) {
            $prevUrl = route('dashboard.work.calendar', $yearQuery($year - 1));
            $nextUrl = route('dashboard.work.calendar', $yearQuery($year + 1));
            $todayUrl = route('dashboard.work.calendar', $yearQuery((int) now()->year));
            $monthModeUrl = route(
                'dashboard.work.calendar',
                $navQuery($year === (int) now()->year ? now()->format('Y-m') : $year . '-01'),
            );
            $yearModeUrl = route('dashboard.work.calendar', $yearQuery($year));
        } else {
            $prevUrl = route(
                'dashboard.work.calendar',
                $navQuery($anchor->copy()->subMonthNoOverflow()->format('Y-m')),
            );
            $nextUrl = route(
                'dashboard.work.calendar',
                $navQuery($anchor->copy()->addMonthNoOverflow()->format('Y-m')),
            );
            $todayUrl = route('dashboard.work.calendar', $navQuery(now()->format('Y-m')));
            $monthModeUrl = route('dashboard.work.calendar', $navQuery($anchor->format('Y-m')));
            $yearModeUrl = route('dashboard.work.calendar', $yearQuery((int) $anchor->year));
        }
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
        {{-- Toolbar: judul periode + pilihan mode (Bulan / Tahun) + navigasi --}}
        <div class="mb-3.5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                    {{ $isYear ? 'Project Timeline · Tahunan' : 'Project Timeline + Weekly Rhythm' }}</p>
                <strong
                    class="mt-1 block text-[28px] font-black leading-none tracking-tight">{{ $isYear ? $year : $anchor->translatedFormat('F Y') }}</strong>
                <p class="mt-2 text-xs text-muted">
                    @if ($isYear)
                        Ketuk bulan untuk membukanya. Jalur warna menyambung = lama project berjalan; hari yang
                        lebih pekat = ada deadline item.
                    @else
                        Warna mengikuti project. Bar menyambung = lama project berjalan. Filter bisa berdasarkan project
                        maupun PIC.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-2xl border border-line bg-white p-0.5 text-[11px] font-extrabold"
                    role="group" aria-label="Mode kalender">
                    <a href="{{ $monthModeUrl }}" @if (!$isYear) aria-current="page" @endif
                        class="rounded-[14px] px-3.5 py-1.5 {{ !$isYear ? 'text-white' : 'text-ink' }}"
                        @if (!$isYear) style="background-color: var(--work-accent)" @endif>Bulan</a>
                    <a href="{{ $yearModeUrl }}" @if ($isYear) aria-current="page" @endif
                        class="rounded-[14px] px-3.5 py-1.5 {{ $isYear ? 'text-white' : 'text-ink' }}"
                        @if ($isYear) style="background-color: var(--work-accent)" @endif>Tahun</a>
                </div>
                <a href="{{ $prevUrl }}" class="btn-wsm-white py-2! px-3.5! text-xs!"
                    aria-label="{{ $isYear ? 'Tahun sebelumnya' : 'Bulan sebelumnya' }}">←</a>
                <a href="{{ $todayUrl }}" class="btn-wsm-white py-2! px-3.5! text-xs!">Today</a>
                <a href="{{ $nextUrl }}" class="btn-wsm-white py-2! px-3.5! text-xs!"
                    aria-label="{{ $isYear ? 'Tahun berikutnya' : 'Bulan berikutnya' }}">→</a>
            </div>
        </div>

        {{-- Filter Project & PIC --}}
        <form method="GET" class="mb-3.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:max-w-xl">
            @if ($isYear)
                <input type="hidden" name="view" value="year">
                <input type="hidden" name="year" value="{{ $year }}">
            @else
                <input type="hidden" name="month" value="{{ $anchor->format('Y-m') }}">
            @endif
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

        @if ($mode === 'month')
            {{-- Weekly Rhythm Settings — hanya yang punya akses work=manage --}}
            @if ($canManage)
                <details class="group mb-3.5 rounded-[20px] border border-line bg-white">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-3.5 py-3 text-[11px] font-black [&::-webkit-details-marker]:hidden">
                        <span>⚙ Weekly Rhythm Settings</span>
                        <span class="flex items-center gap-2 text-[10px] font-medium text-muted">ubah ritme Senin–Minggu
                            <span class="transition group-open:rotate-180">⌄</span></span>
                    </summary>
                    <div class="grid gap-2 px-3.5 pb-3.5">
                        <form id="rhythm-form" method="POST" action="{{ route('dashboard.work.calendar.rhythm.update') }}"
                            class="grid gap-2"
                            data-confirm="Fokus, mode, dan jam kerja per hari akan berubah untuk seluruh tim (kalender, dashboard Owner, dan App Mode)."
                            data-confirm-title="Simpan Weekly Rhythm?" data-confirm-button="Ya, simpan">
                            @csrf
                            @method('PATCH')
                            @foreach ($rhythmOrder as $dow)
                                @php $rhythm = $weeklyRhythm[$dow]; @endphp
                                <div class="grid grid-cols-1 items-end gap-2 sm:grid-cols-[90px_1.35fr_.65fr_1fr]">
                                    <strong class="text-[10px] sm:pb-3">{{ $rhythm['day'] }}</strong>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Focus /
                                            Rhythm</label>
                                        <input type="text" name="rhythm[{{ $dow }}][focus]" maxlength="80"
                                            required value="{{ old("rhythm.$dow.focus", $rhythm['focus']) }}"
                                            class="input-wsm">
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Mode</label>
                                        <select name="rhythm[{{ $dow }}][mode]" class="input-wsm">
                                            @foreach ($rhythmModes as $rhythmMode)
                                                <option value="{{ $rhythmMode }}" @selected(old("rhythm.$dow.mode", $rhythm['mode']) === $rhythmMode)>
                                                    {{ $rhythmMode }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-extrabold uppercase text-muted">Hours /
                                            Note</label>
                                        <input type="text" name="rhythm[{{ $dow }}][hours]" maxlength="40"
                                            value="{{ old("rhythm.$dow.hours", $rhythm['hours_custom']) }}"
                                            placeholder="{{ match ($rhythm['mode']) {'WFO' => 'Otomatis: jam kerja kantor','Event' => 'Otomatis: Sesuai jadwal event',default => 'Otomatis: Flexible / remote'} }}"
                                            class="input-wsm">
                                    </div>
                                </div>
                            @endforeach
                        </form>
                        <form id="rhythm-reset" method="POST" action="{{ route('dashboard.work.calendar.rhythm.reset') }}"
                            data-confirm="Semua isian rhythm akan kembali ke bawaan (Alignment & Planning dst.). Perubahan yang sudah disimpan akan hilang."
                            data-confirm-title="Kembalikan ke default?" data-confirm-button="Ya, reset"
                            data-confirm-danger="1">
                            @csrf
                        </form>
                        <div class="mt-1 flex flex-wrap justify-end gap-2">
                            <button type="submit" form="rhythm-reset" class="btn-wsm-white py-2! px-3.5! text-xs!">Reset
                                Default</button>
                            <button type="submit" form="rhythm-form" class="btn-wsm-black py-2! px-3.5! text-xs!">Save
                                Calendar
                                Rhythm</button>
                        </div>
                        <p class="text-[10px] text-muted">Kosongkan "Hours / Note" agar jam WFO otomatis mengikuti
                            Pengaturan
                            Kantor.</p>
                    </div>
                </details>
            @endif

            {{-- Grid bulan — header hitam memuat ritme kerja tiap hari (Sabtu/Minggu = ritme event). --}}
            <div x-data="wsmCalendar(@js($dayPopups), {{ $canManage ? 'true' : 'false' }}, @js(route('dashboard.work.calendar.items.move', ['item' => '__ID__'])), @js($itemDetails))" @keydown.escape.window="popup = null; closeItem(false)">
                <div class="overflow-hidden rounded-[26px] border border-line bg-white">
                    <div class="overflow-hidden">
                        <div class="grid grid-cols-7 bg-[#111] text-white">
                            @foreach ([0, 1, 2, 3, 4, 5, 6] as $dow)
                                @php
                                    $r = $weeklyRhythm[$dow];
                                    $modeClass = match ($r['mode']) {
                                        'WFO' => 'bg-[#e8efff] text-[#23498d]',
                                        'WFH' => 'bg-[#e8f8e8] text-[#286231]',
                                        'Event' => 'bg-[#fff0bd] text-[#6b4a00]',
                                        default => 'bg-[#eeeae3] text-[#4e4a43]',
                                    };
                                @endphp
                                <div class="min-w-0 px-0.5 py-2 text-center sm:px-2 sm:py-2.5"
                                    title="{{ $r['focus'] }} · {{ $r['mode'] }} · {{ $r['hours'] }}">
                                    <span
                                        class="block text-[10px] font-black sm:text-[11px] {{ $dow === 0 ? 'text-[#ff8a7a]' : '' }}"><span
                                            class="sm:hidden">{{ mb_strtoupper(mb_substr($r['day'], 0, 3)) }}</span><span
                                            class="hidden sm:inline">{{ $r['day'] }}</span></span>
                                    {{-- HP: kolom sempit, ritme cukup berupa garis warna mode (detail ada di popup harian & tooltip). --}}
                                    <span
                                        class="mt-1 block h-1 rounded-full sm:hidden {{ $modeClass }} {{ $todayDow === $dow ? 'ring-1 ring-white' : '' }}"></span>
                                    <span
                                        class="mt-1 hidden rounded-md px-1 py-0.5 text-[8px] font-black leading-tight sm:block {{ $modeClass }} {{ $todayDow === $dow ? 'ring-2 ring-white' : '' }}">
                                        <span class="block">{{ $r['focus'] }}</span>
                                        <span class="block opacity-80">{{ $r['mode'] }}<span> ·
                                                {{ $r['hours'] }}</span></span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7 gap-0.5 p-0.5 [--d:6px] sm:gap-1 sm:p-1 sm:[--d:16px]">
                            @foreach ($weeks as $week)
                                @foreach ($week as $cell)
                                    <div data-date="{{ $cell['key'] }}" @click="onCell($event, '{{ $cell['key'] }}')"
                                        @dragover.prevent="dragOver($event)" @dragleave="dragLeave($event)"
                                        @drop.prevent="drop($event, '{{ $cell['key'] }}')"
                                        @if (!$cell['outside'] && $cell['tint']) style="background:color-mix(in srgb, {{ $cell['tint'] }} 14%, #fff)" @endif
                                        class="min-h-24 min-w-0 cursor-pointer rounded-lg p-0.5 transition sm:min-h-33 sm:rounded-xl sm:p-1.5 {{ $cell['outside'] ? 'bg-[#f6f2eb] text-[#b5afa5]' : 'bg-[#faf8f4]' }} {{ $cell['isToday'] ? 'outline-2 -outline-offset-2 outline-[#111]' : '' }}">
                                        {{-- Nomor tanggal di tengah ala Samsung: hari ini = lingkaran hitam, Minggu = merah. --}}
                                        <div class="mb-1 flex justify-center text-[11px] font-black">
                                            <span
                                                class="grid h-5 min-w-5 place-items-center rounded-full px-1 {{ $cell['isToday'] ? 'bg-[#111] text-white' : ($cell['date']->dayOfWeek === 0 && !$cell['outside'] ? 'text-[#d64535]' : '') }}">{{ $cell['date']->day }}</span>
                                        </div>

                                        {{-- Chip item (maks 3); di HP kalender digeser ke samping. Chip bisa di-drag di desktop kalau punya akses manage. --}}
                                        <div class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-0.5 sm:gap-1">
                                            {{-- Bar project menyambung: tiap lane = 1 project; kosong = penyeimbang tinggi. Bar kiri→kanan melebar lewat margin negatif (--d = padding + gap sel). --}}
                                            @foreach ($cell['lanes'] as $seg)
                                                @if ($seg === null)
                                                    <span class="block h-3.5 sm:h-4" aria-hidden="true"></span>
                                                @else
                                                    @php
                                                        $isFirst = $seg['from'] === $cell['col'];
                                                        $isLast = $seg['to'] === $cell['col'];
                                                        $span = $seg['to'] - $seg['from'] + 1;
                                                    @endphp
                                                    <a href="{{ $seg['url'] }}" data-chip
                                                        data-project-span="{{ $seg['id'] }}"
                                                        title="{{ $seg['name'] }} · {{ $seg['range'] }}"
                                                        class="relative block h-3.5 text-[8px] font-black leading-3.5 sm:h-4 sm:text-[10px] sm:leading-4 {{ $isFirst && $seg['capL'] ? 'rounded-l-[5px]' : '' }} {{ $isLast && $seg['capR'] ? 'rounded-r-[5px]' : '' }}"
                                                        style="background:{{ $seg['color'] }};color:{{ $seg['text'] }};{{ $isLast ? '' : 'margin-right:calc(-1 * var(--d));' }}">
                                                        @if ($isFirst)
                                                            <span data-project-label
                                                                class="absolute inset-y-0 left-0 block overflow-hidden text-ellipsis whitespace-nowrap px-1"
                                                                style="width:{{ $span > 1 ? "calc({$span} * 100% - var(--d))" : '100%' }}">{{ $seg['name'] }}</span>
                                                        @endif
                                                    </a>
                                                @endif
                                            @endforeach
                                            @foreach ($cell['visible'] as $item)
                                                @include('dashboard.work._calendar-chip', [
                                                    'item' => $item,
                                                ])
                                            @endforeach
                                            @if ($cell['hidden']->isNotEmpty())
                                                @php $moreLabel = '+' . $cell['hidden']->count() . ' item'; @endphp
                                                <button type="button" class="wsm-more"
                                                    @click.stop="open('{{ $cell['key'] }}')">{{ $moreLabel }}</button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Popup harian: ritme hari itu + semua item/penanda project di tanggal tersebut. --}}
                <div x-show="popup" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
                    <div @click.outside="popup = null"
                        class="max-h-[85vh] w-full max-w-md overflow-y-auto rounded-4xl bg-cream p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Agenda Harian</p>
                                <h3 class="text-lg font-black leading-tight" x-text="popup ? popup.label : ''"></h3>
                            </div>
                            <button type="button" @click="popup = null" aria-label="Tutup"
                                class="grid h-8 w-8 flex-none place-items-center rounded-full bg-white text-sm font-black">×</button>
                        </div>
                        <template x-if="popup && popup.rhythm">
                            <p class="mt-2 rounded-xl bg-white px-3 py-2 text-[11px] font-bold text-[#4e4a43]">
                                <span x-text="popup.rhythm.focus"></span>
                                <span class="text-muted"> · <span x-text="popup.rhythm.mode"></span> · <span
                                        x-text="popup.rhythm.hours"></span></span>
                            </p>
                        </template>
                        <div class="mt-3 grid gap-2">
                            <template x-if="popup && popup.items.length === 0">
                                <p class="rounded-xl bg-white px-3 py-4 text-center text-[12px] text-muted">Tidak ada item
                                    di tanggal ini.</p>
                            </template>
                            <template x-for="(it, i) in (popup ? popup.items : [])" :key="i">
                                <a :href="it.url"
                                    @click="if (it.id) { $event.preventDefault(); openItem(it.id, popupKey) }"
                                    class="block rounded-2xl border-l-4 border-black/20 px-3 py-2.5"
                                    :style="`background:${it.color};color:${it.text};${it.done ? 'opacity:.65' : ''}`">
                                    <span class="block text-[12px] font-black leading-snug" x-text="it.title"></span>
                                    <span class="mt-0.5 block text-[10px] font-bold opacity-80"
                                        x-text="[it.project, it.pic, it.progress, it.focus].filter(Boolean).join(' · ')"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Modal item: detail + edit langsung (yang punya akses work=manage). Klik item di kalender / popup harian membuka ini. --}}
                <div x-show="item" x-cloak
                    class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4">
                    <div @click.outside="closeItem(true)"
                        class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                        <div
                            class="sticky -top-5 z-10 -mx-5 -mt-5 mb-3 flex items-start justify-between gap-3 bg-cream px-5 pb-2 pt-5">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">
                                    {{ $canManage ? 'Edit Item' : 'Detail Item' }}</p>
                                <h3 class="text-lg font-black leading-tight wrap-break-word"
                                    x-text="item ? [item.project, item.section].filter(Boolean).join(' · ') : ''"></h3>
                            </div>
                            <button type="button" @click="closeItem(true)" aria-label="Tutup"
                                class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                        </div>

                        @if ($canManage)
                            <form @submit.prevent="save()" class="grid gap-3" autocomplete="off">
                                <div class="grid content-start gap-1">
                                    <label class="text-[10px] font-extrabold uppercase text-muted">Item / Pekerjaan</label>
                                    <textarea x-model="form.title" rows="2" maxlength="255" required @keydown.enter.prevent="save()"
                                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm font-bold"></textarea>
                                </div>
                                <div class="grid items-start gap-3 sm:grid-cols-2">
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">Date / Due</label>
                                        <input type="date" x-model="form.due_date"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                    </div>
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">Progress</label>
                                        <select x-ref="progress" data-searchable data-search-placeholder="Cari progress…"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                            @foreach ($progressOptions as $status)
                                                <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid items-start gap-3 sm:grid-cols-2">
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">PIC 1</label>
                                        <select x-ref="pic1" @change="syncPics()" data-searchable
                                            data-search-placeholder="Cari nama PIC…"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                            <option value="">Belum di-assign</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">Priority</label>
                                        <select x-ref="priority" data-searchable data-search-placeholder="Cari priority…"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                            @foreach ($priorityOptions as $priority)
                                                <option value="{{ $priority }}">{{ $priority }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid items-start gap-3 sm:grid-cols-2">
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">PIC 2
                                            (opsional)</label>
                                        <select x-ref="pic2" @change="syncPics()" data-searchable
                                            data-search-placeholder="Cari nama PIC…"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                            <option value="">Tidak ada</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid content-start gap-1">
                                        <label class="text-[10px] font-extrabold uppercase text-muted">PIC 3
                                            (opsional)</label>
                                        <select x-ref="pic3" @change="syncPics()" data-searchable
                                            data-search-placeholder="Cari nama PIC…"
                                            class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                            <option value="">Tidak ada</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="text-[10px] font-medium text-muted sm:col-span-2"
                                        x-show="item && item.pic_text" x-cloak>
                                        Catatan PIC lama: <b x-text="item ? item.pic_text : ''"></b> (hilang kalau PIC 2/3
                                        diisi).
                                    </p>
                                </div>
                                <div class="grid content-start gap-1">
                                    <label class="text-[10px] font-extrabold uppercase text-muted">Link (opsional)</label>
                                    <input type="text" x-model="form.link" inputmode="url" placeholder="https://..."
                                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm">
                                </div>
                                <div class="grid content-start gap-1">
                                    <label class="text-[10px] font-extrabold uppercase text-muted">Note / Progress
                                        Recap</label>
                                    <textarea x-model="form.notes" rows="3" maxlength="5000"
                                        placeholder="Apa update terakhir, blocker, atau next action?"
                                        class="rounded-2xl border border-line bg-white px-3.5 py-2.5 text-sm"></textarea>
                                </div>
                                <p x-show="error" x-cloak x-text="error" role="alert"
                                    class="rounded-xl bg-[#ffded8] px-3 py-2 text-[12px] font-bold text-[#9b392f]"></p>
                                <div
                                    class="sticky -bottom-5 z-10 -mx-5 -mb-5 mt-1 flex flex-wrap items-center gap-2 bg-cream px-5 pb-5 pt-2">
                                    <a :href="item ? item.tracker_url : '#'"
                                        class="btn-wsm-white py-2! px-3.5! text-xs!">Buka di Work Tracker</a>
                                    <button type="submit" :disabled="saving"
                                        class="btn-wsm-black ml-auto disabled:cursor-wait disabled:opacity-60"
                                        x-text="saving ? 'Menyimpan…' : 'Save Item'"></button>
                                </div>
                            </form>
                        @else
                            <div class="grid gap-2.5 text-[13px]" x-show="item">
                                <p class="text-base font-black leading-snug wrap-break-word"
                                    x-text="item ? item.title : ''"></p>
                                <dl
                                    class="grid grid-cols-[88px_1fr] gap-x-3 gap-y-1.5 rounded-2xl bg-white px-3.5 py-3 text-[12px]">
                                    <dt class="font-extrabold text-muted">Due</dt>
                                    <dd class="font-bold" x-text="item && item.due_date ? item.due_date : '-'"></dd>
                                    <dt class="font-extrabold text-muted">Progress</dt>
                                    <dd class="font-bold" x-text="item ? item.progress + ' · ' + item.focus : ''"></dd>
                                    <dt class="font-extrabold text-muted">Priority</dt>
                                    <dd class="font-bold" x-text="item ? item.priority : ''"></dd>
                                    <dt class="font-extrabold text-muted">PIC</dt>
                                    <dd class="font-bold"
                                        x-text="item ? ([item.pic_name].concat(item.extra_names).filter(Boolean).join(' · ') || item.pic_text || 'Belum di-assign') : ''">
                                    </dd>
                                    <template x-if="item && item.link">
                                        <dt class="font-extrabold text-muted">Link</dt>
                                    </template>
                                    <template x-if="item && item.link">
                                        <dd class="font-bold break-all"><a :href="item.link" target="_blank"
                                                rel="noopener" class="text-[#2647b8] underline" x-text="item.link"></a>
                                        </dd>
                                    </template>
                                    <dt class="font-extrabold text-muted">Note</dt>
                                    <dd class="font-medium whitespace-pre-line wrap-break-word"
                                        x-text="item && item.notes ? item.notes : '-'"></dd>
                                </dl>
                                <a :href="item ? item.tracker_url : '#'"
                                    class="btn-wsm-white py-2! px-3.5! text-xs! justify-self-start">Buka di Work
                                    Tracker</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <style>
                [data-date].wsm-drop-over {
                    background: #e7efff !important;
                    box-shadow: inset 0 0 0 2px #3558f4;
                }

                /* Tombol "+N item": class pendek supaya tag-nya tidak dipecah formatter Blade (teks jadi satu ekspresi). */
                .wsm-more {
                    text-align: center;
                    font-size: 10px;
                    font-weight: 800;
                    color: #8b867e;
                }

                .wsm-more:hover {
                    text-decoration: underline;
                    text-underline-offset: 2px;
                }

                a[data-chip][draggable="true"] {
                    cursor: grab;
                }

                a[data-chip].wsm-dragging {
                    opacity: .4;
                }
            </style>
            <script>
                function wsmCalendar(days, canManage, moveUrl, items) {
                    // Drag & drop hanya di desktop sungguhan: layar lebar + mouse (bukan HP/tablet sentuh).
                    const desktop = () => window.matchMedia('(min-width: 1024px) and (hover: hover) and (pointer: fine)').matches;

                    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                    return {
                        popup: null,
                        popupKey: null,
                        item: null,
                        returnTo: null,
                        form: {
                            title: '',
                            due_date: '',
                            link: '',
                            notes: ''
                        },
                        saving: false,
                        error: '',
                        dragId: null,
                        init() {
                            if (!canManage) return;
                            const arm = () => this.$root.querySelectorAll('a[data-chip][data-item-id]').forEach((a) => {
                                a.draggable = desktop();
                                a.ondragstart = (e) => {
                                    if (!desktop()) return e.preventDefault();
                                    this.dragId = a.dataset.itemId;
                                    a.classList.add('wsm-dragging');
                                    e.dataTransfer.effectAllowed = 'move';
                                    e.dataTransfer.setData('text/plain', a.dataset.itemId);
                                };
                                a.ondragend = () => {
                                    a.classList.remove('wsm-dragging');
                                    this.$root.querySelectorAll('.wsm-drop-over').forEach((c) => c.classList.remove(
                                        'wsm-drop-over'));
                                };
                            });
                            arm();
                            window.matchMedia('(min-width: 1024px)').addEventListener('change', arm);
                        },
                        open(key) {
                            this.popup = days[key] ?? null;
                            this.popupKey = key;
                        },
                        onCell(e, key) {
                            const chip = e.target.closest('a[data-chip]');
                            // Chip item = buka modal detail/edit (bukan pindah halaman).
                            if (chip && chip.dataset.itemId) {
                                e.preventDefault();
                                this.openItem(chip.dataset.itemId, null);
                                return;
                            }
                            // Penanda ▶/■ project di desktop: ke halaman Projects seperti biasa. Selain itu (dan di HP/tablet): popup harian.
                            if (desktop() && chip) return;
                            e.preventDefault();
                            this.open(key);
                        },
                        // Modal item. `fromKey` = tanggal popup harian asal (kembali ke popup itu saat modal ditutup).
                        openItem(id, fromKey) {
                            const it = items[id];
                            if (!it) return;
                            this.returnTo = fromKey ?? null;
                            this.popup = null;
                            this.error = '';
                            this.saving = false;
                            this.item = it;
                            this.form = {
                                title: it.title,
                                due_date: it.due_date || '',
                                link: it.link || '',
                                notes: it.notes || '',
                            };
                            if (!canManage) return;
                            // Dropdown pencarian = <select> asli yang disembunyikan: nilainya diisi lewat `.value`
                            // (bukan x-model) supaya label tombolnya ikut berubah.
                            this.$nextTick(() => {
                                const r = this.$refs;
                                r.pic1.value = it.pic || '';
                                r.pic2.value = it.extra[0] || '';
                                r.pic3.value = it.extra[1] || '';
                                r.progress.value = it.progress;
                                r.priority.value = it.priority;
                                this.syncPics();
                            });
                        },
                        closeItem(backToDay) {
                            if (!this.item) return;
                            const back = this.returnTo;
                            this.item = null;
                            this.returnTo = null;
                            if (backToDay && back) this.open(back);
                        },
                        // PIC 1/2/3: orang yang sudah dipilih di satu kolom dinonaktifkan di dua kolom lainnya.
                        syncPics() {
                            const sels = [this.$refs.pic1, this.$refs.pic2, this.$refs.pic3];
                            const chosen = sels.map((s) => s.value);
                            sels.forEach((s, i) => [...s.options].forEach((o) => {
                                o.disabled = o.value !== '' && chosen.some((v, j) => j !== i && v === o.value);
                            }));
                        },
                        save() {
                            if (this.saving || !this.item) return;
                            const r = this.$refs;
                            const title = this.form.title.replace(/\s*[\r\n]+\s*/g, ' ').trim();
                            if (!title) {
                                this.error = 'Judul tidak boleh kosong.';
                                return;
                            }
                            this.saving = true;
                            this.error = '';
                            fetch(this.item.update_url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf(),
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    title,
                                    due_date: this.form.due_date || null,
                                    pic_employee_id: r.pic1.value || null,
                                    additional_pic_ids: [r.pic2.value, r.pic3.value].filter(Boolean),
                                    progress: r.progress.value,
                                    priority: r.priority.value,
                                    link: this.form.link.trim() || null,
                                    notes: this.form.notes || null,
                                }),
                            }).then(async (res) => {
                                if (!res.ok) {
                                    const body = await res.json().catch(() => ({}));
                                    throw new Error(body.errors ? Object.values(body.errors).flat()[0] : (body
                                        .message || 'Gagal menyimpan, coba lagi.'));
                                }
                                // Kalender (chip, titik, popup) dihitung server -> muat ulang supaya langsung sesuai.
                                window.location.reload();
                            }).catch((e) => {
                                this.saving = false;
                                this.error = e.message || 'Gagal menyimpan, coba lagi.';
                            });
                        },
                        dragOver(e) {
                            if (!this.dragId || !desktop()) return;
                            e.currentTarget.classList.add('wsm-drop-over');
                        },
                        dragLeave(e) {
                            e.currentTarget.classList.remove('wsm-drop-over');
                        },
                        drop(e, key) {
                            e.currentTarget.classList.remove('wsm-drop-over');
                            const id = this.dragId;
                            this.dragId = null;
                            if (!id || !canManage || !desktop()) return;
                            fetch(moveUrl.replace('__ID__', id), {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    due_date: key
                                }),
                            }).then((res) => {
                                if (!res.ok) throw new Error();
                                window.location.reload();
                            }).catch(() => {
                                const msg = 'Gagal memindahkan item, coba lagi.';
                                if (window.WsmAlert) WsmAlert.error(msg);
                                else alert(msg);
                            });
                        },
                    };
                }
            </script>
        @else
            {{-- Mode TAHUN (gaya kalender Samsung): 12 bulan mini. Ketuk bulan = buka kalender bulan itu. --}}
            <div class="mb-6 grid grid-cols-3 gap-x-2 gap-y-4 sm:gap-x-4 lg:grid-cols-4" data-year-months>
                @foreach ($miniMonths as $mm)
                    <a href="{{ $mm['url'] }}" data-year-month="{{ $loop->iteration }}"
                        title="Buka {{ $mm['label'] }} {{ $year }}{{ $mm['itemCount'] ? ' · ' . $mm['itemCount'] . ' item' : '' }}"
                        class="block min-w-0 rounded-2xl p-1 transition hover:bg-[#f6f2eb] sm:p-2">
                        <span
                            class="mx-auto mb-1.5 block w-fit rounded-lg px-2.5 py-0.5 text-center text-[13px] font-black {{ $mm['isCurrent'] ? 'bg-[#111] text-white' : '' }}">{{ $mm['short'] }}</span>
                        <span class="grid grid-cols-7 gap-x-0 gap-y-0.5 text-center">
                            @foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $i => $initial)
                                <span
                                    class="pb-0.5 text-[8px] font-black sm:text-[10px] {{ $i === 0 ? 'text-[#d64535]' : 'text-muted' }}">{{ $initial }}</span>
                            @endforeach
                            @for ($i = 0; $i < $mm['lead']; $i++)
                                <span></span>
                            @endfor
                            @foreach ($mm['days'] as $d)
                                @php
                                    // Jalur project menyambung (ujung bulat di awal/akhir); hari berdeadline lebih pekat.
                                    $dayBg = $d['isToday']
                                        ? null
                                        : ($d['color']
                                            ? "color-mix(in srgb, {$d['color']} 45%, #fff)"
                                            : ($d['span']
                                                ? "color-mix(in srgb, {$d['span']['color']} 22%, #fff)"
                                                : null));
                                    $dayRound = $d['span']
                                        ? ($d['roundL'] ? 'rounded-l-[5px] ' : '') .
                                            ($d['roundR'] ? 'rounded-r-[5px]' : '')
                                        : 'rounded-[5px]';
                                @endphp
                                <span data-day="{{ $d['day'] }}"
                                    @if ($d['span']) data-span="{{ $d['span']['id'] }}" @endif
                                    class="grid aspect-square place-items-center text-[8px] font-bold leading-none sm:text-[11px] {{ $dayRound }} {{ $d['isToday'] ? 'bg-[#111] text-white' : ($d['dow'] === 0 ? 'text-[#d64535]' : 'text-ink') }}"
                                    @if ($dayBg) style="background:{{ $dayBg }}" @endif>{{ $d['day'] }}</span>
                            @endforeach
                        </span>
                    </a>
                @endforeach
            </div>

            <p class="mt-3 text-[10px] font-medium text-muted">Jalur warna menyambung = lama project berjalan (tanggal
                Mulai–Selesai di menu Projects). Hari yang lebih pekat = ada deadline item. Lingkaran hitam = hari ini.</p>
        @endif

        @if (!$isYear && $canManage)
            {{-- Dropdown pencarian (PIC/Progress/Priority di modal edit item) — komponen yang sama dengan Work Tracker. --}}
            @include('dashboard.work.tracker._searchable-select')
        @endif

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
