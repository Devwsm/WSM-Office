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
                                        placeholder="{{ match ($rhythm['mode']) {'WFO' => 'Otomatis: jam kerja kantor','Event' => 'Otomatis: Sesuai jadwal event',default => 'Otomatis: Flexible / remote'} }}"
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

        {{-- Grid bulan — header hitam memuat ritme kerja tiap hari (Sabtu/Minggu = ritme event). --}}
        <div x-data="wsmCalendar(@js($dayPopups), {{ $canManage ? 'true' : 'false' }}, @js(route('dashboard.work.calendar.items.move', ['item' => '__ID__'])))" @keydown.escape.window="popup = null">
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
                                        <button type="button"
                                            class="text-left text-[10px] font-extrabold text-muted underline-offset-2 hover:underline"
                                            @click.stop="open('{{ $cell['key'] }}')">+{{ $cell['hidden']->count() }}
                                            item</button>
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
                                            <span class="text-[9px] font-black">+{{ $cell['all']->count() - 6 }}</span>
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
                            <p class="rounded-xl bg-white px-3 py-4 text-center text-[12px] text-muted">Tidak ada item di
                                tanggal ini.</p>
                        </template>
                        <template x-for="(it, i) in (popup ? popup.items : [])" :key="i">
                            <a :href="it.url" class="block rounded-2xl border-l-4 border-black/20 px-3 py-2.5"
                                :style="`background:${it.color};color:${it.text};${it.done ? 'opacity:.65' : ''}`">
                                <span class="block text-[12px] font-black leading-snug" x-text="it.title"></span>
                                <span class="mt-0.5 block text-[10px] font-bold opacity-80"
                                    x-text="[it.project, it.pic, it.progress, it.focus].filter(Boolean).join(' · ')"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <style>
            [data-date].wsm-drop-over {
                background: #e7efff !important;
                box-shadow: inset 0 0 0 2px #3558f4;
            }

            a[data-chip][draggable="true"] {
                cursor: grab;
            }

            a[data-chip].wsm-dragging {
                opacity: .4;
            }
        </style>
        <script>
            function wsmCalendar(days, canManage, moveUrl) {
                // Drag & drop hanya di desktop sungguhan: layar lebar + mouse (bukan HP/tablet sentuh).
                const desktop = () => window.matchMedia('(min-width: 1024px) and (hover: hover) and (pointer: fine)').matches;

                return {
                    popup: null,
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
                    },
                    onCell(e, key) {
                        // Desktop: klik chip = buka tracker seperti biasa. Selain itu (dan di HP/tablet): popup harian.
                        if (desktop() && e.target.closest('a[data-chip]')) return;
                        e.preventDefault();
                        this.open(key);
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
