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
                        Satu baris = satu project. Batang = lama project berjalan (tanggal Mulai–Selesai), titik =
                        deadline item.
                    @else
                        Warna mengikuti project. Filter bisa berdasarkan project maupun PIC.
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
                            <div class="px-1 py-2 text-center lg:px-2 lg:py-2.5"
                                title="{{ $r['focus'] }} · {{ $r['mode'] }} · {{ $r['hours'] }}">
                                <span class="block text-[10px] font-black lg:text-[11px]"><span
                                        class="lg:hidden">{{ \Illuminate\Support\Str::substr($r['day'], 0, 3) }}</span><span
                                        class="hidden lg:inline">{{ $r['day'] }}</span></span>
                                <span
                                    class="mt-1 block rounded-md px-1 py-0.5 text-[8px] font-black leading-tight {{ $modeClass }} {{ $todayDow === $dow ? 'ring-2 ring-white' : '' }}">
                                    <span class="hidden lg:block">{{ $r['focus'] }}</span>
                                    <span class="block opacity-80">{{ $r['mode'] }}<span class="hidden lg:inline"> ·
                                            {{ $r['hours'] }}</span></span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7">
                        @foreach ($weeks as $week)
                            @foreach ($week as $cell)
                                <div data-date="{{ $cell['key'] }}" @click="onCell($event, '{{ $cell['key'] }}')"
                                    @dragover.prevent="dragOver($event)" @dragleave="dragLeave($event)"
                                    @drop.prevent="drop($event, '{{ $cell['key'] }}')"
                                    class="min-h-20 cursor-pointer border-b border-r border-[#ece5da] p-1 transition lg:min-h-33 lg:p-2 nth-[7n]:border-r-0 {{ $cell['outside'] ? 'bg-[#f6f2eb] text-[#b5afa5]' : 'bg-white' }} {{ $cell['isToday'] ? 'relative z-10 outline-2 -outline-offset-2 outline-[#111]' : '' }}">
                                    <div class="mb-1 flex items-center justify-between text-[11px] font-black lg:mb-1.5">
                                        <span>{{ $cell['date']->day }}</span>
                                        @if ($cell['isToday'])
                                            <small class="text-[10px]">Today</small>
                                        @endif
                                    </div>

                                    {{-- Desktop: chip item (maks 3). Chip item bisa di-drag kalau punya akses manage. --}}
                                    <div class="hidden gap-1 lg:grid">
                                        @foreach ($cell['visible'] as $item)
                                            @include('dashboard.work._calendar-chip', ['item' => $item])
                                        @endforeach
                                        @if ($cell['hidden']->isNotEmpty())
                                            @php $moreLabel = '+' . $cell['hidden']->count() . ' item'; @endphp
                                            <button type="button" class="wsm-more"
                                                @click.stop="open('{{ $cell['key'] }}')">{{ $moreLabel }}</button>
                                        @endif
                                    </div>

                                    {{-- HP & tablet: titik warna ringkas; ketuk tanggal buka popup harian. --}}
                                    @if ($cell['all']->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-0.5 lg:hidden">
                                            @foreach ($cell['all']->take(6) as $item)
                                                <i class="inline-block h-2 w-2 rounded-full {{ $item['done'] ? 'opacity-50' : '' }}"
                                                    style="background:{{ $item['color'] }}"></i>
                                            @endforeach
                                            @if ($cell['all']->count() > 6)
                                                <span
                                                    class="text-[9px] font-black">+{{ $cell['all']->count() - 6 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
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
                        <div class="mb-3 flex items-start justify-between gap-3">
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
                                <div class="mt-1 flex flex-wrap items-center gap-2">
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
                    text-align: left;
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
            {{-- Mode TAHUN: garis waktu project. Batang = rentang project, titik = deadline item. --}}
            @php
                // Garis batas bulan + penanda hari ini digambar sebagai latar tiap baris (persen dari lebar lajur).
                $gridLayers = $yearMonths
                    ->skip(1)
                    ->map(fn($m) => "linear-gradient(#ece5da,#ece5da) {$m['left']}% 0/1px 100% no-repeat")
                    ->all();
                if ($todayPos !== null) {
                    $gridLayers[] = "linear-gradient(#111,#111) {$todayPos}% 0/2px 100% no-repeat";
                }
                $gridBg = implode(',', $gridLayers);
            @endphp

            @if ($yearRows->isEmpty())
                <p class="rounded-3xl border border-dashed border-line p-6 text-sm text-muted">
                    Belum ada project yang berjalan di {{ $year }}@if ($projectFilter || $picFilter)
                        pada filter ini
                    @endif.
                    Isi tanggal Mulai/Selesai di menu Projects, atau beri deadline pada item di Work Tracker.
                </p>
            @else
                <div class="overflow-x-auto rounded-[26px] border border-line bg-white" data-year-timeline>
                    <div class="min-w-230">
                        {{-- Header bulan (klik = buka bulan itu) --}}
                        <div class="flex bg-[#111] text-white">
                            <div
                                class="sticky left-0 z-20 flex w-40 shrink-0 items-center bg-[#111] px-3 py-2.5 text-[10px] font-black uppercase tracking-widest sm:w-56">
                                Project · {{ $year }}</div>
                            <div class="relative h-10 min-w-0 flex-1">
                                @foreach ($yearMonths as $m)
                                    <a href="{{ $m['url'] }}" title="Buka {{ $m['label'] }} {{ $year }}"
                                        class="absolute inset-y-0 grid place-items-center text-[11px] font-black hover:bg-white/10"
                                        style="left:{{ $m['left'] }}%;width:{{ $m['width'] }}%">{{ $m['label'] }}</a>
                                @endforeach
                            </div>
                        </div>

                        @foreach ($yearRows as $row)
                            <div class="flex border-t border-[#ece5da]" data-year-row="{{ $row['id'] }}">
                                <div class="sticky left-0 z-10 w-40 shrink-0 bg-white px-3 py-2.5 sm:w-56">
                                    <a href="{{ $row['url'] }}"
                                        class="block text-[12px] font-black leading-tight wrap-break-word hover:underline">{{ $row['name'] }}</a>
                                    <p class="mt-0.5 text-[10px] font-bold text-muted">{{ $row['from'] }} →
                                        {{ $row['to'] }}</p>
                                    <p class="text-[10px] font-extrabold">{{ $row['duration'] }}</p>
                                    <p class="text-[10px] font-medium text-muted">
                                        {{ $row['pct'] }}% selesai · {{ $row['items'] }} item
                                        @if ($row['derived'])
                                            <span
                                                title="Tanggal Mulai/Selesai project belum lengkap, jadi rentang diambil dari deadline item.">·
                                                tgl dari item</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="relative min-h-16 min-w-0 flex-1" style="background:{{ $gridBg }}">
                                    <div class="absolute top-1/2 h-7 -translate-y-1/2 overflow-hidden {{ $row['before'] ? 'rounded-l-none' : 'rounded-l-lg' }} {{ $row['after'] ? 'rounded-r-none' : 'rounded-r-lg' }}"
                                        style="left:{{ $row['left'] }}%;width:{{ $row['width'] }}%;background:{{ $row['color'] }};color:{{ $row['text'] }}"
                                        title="{{ $row['name'] }} · {{ $row['from'] }} → {{ $row['to'] }} · {{ $row['duration'] }} · {{ $row['pct'] }}% selesai ({{ $row['items'] }} item)">
                                        <span class="absolute inset-y-0 left-0 bg-black/20"
                                            style="width:{{ $row['pct'] }}%"></span>
                                        @if ($row['width'] >= 12)
                                            <span
                                                class="relative flex h-full items-center truncate px-2 text-[10px] font-black">{{ $row['before'] ? '◀ ' : '' }}{{ $row['duration'] }}{{ $row['after'] ? ' ▶' : '' }}</span>
                                        @endif
                                    </div>
                                    @foreach ($row['ticks'] as $tick)
                                        <i class="absolute top-1/2 z-1 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white {{ $tick['done'] ? 'opacity-50' : '' }}"
                                            style="left:{{ $tick['pos'] }}%;background:#17130a"
                                            title="{{ $tick['tip'] }}"></i>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="mt-2 text-[10px] font-medium text-muted">Bagian gelap di batang = persentase item yang sudah
                    Done.
                    @if ($todayPos !== null)
                        Garis hitam = hari ini.
                    @endif
                    ◀ ▶ = project mulai sebelum / selesai setelah {{ $year }}.
                </p>
            @endif

            @if ($undatedProjects->isNotEmpty())
                <p class="mt-2 rounded-2xl bg-[#f6f2eb] px-3.5 py-2.5 text-[11px] font-bold text-[#4e4a43]">
                    Belum punya tanggal (isi Mulai/Selesai di menu Projects): {{ $undatedProjects->implode(', ') }}.</p>
            @endif
            @if ($outsideCount > 0)
                <p class="mt-2 text-[11px] font-medium text-muted">{{ $outsideCount }} project lain tidak berjalan di
                    {{ $year }}.</p>
            @endif

        @endif

        @if ($mode === 'month' && $canManage)
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
