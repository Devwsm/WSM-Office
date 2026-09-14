{{--
    employee/work-tracker/calendar.blade.php
    ---------------------------------------------------------------------
    "Shared Workload Calendar" — padanan renderEmployeeSharedCalendarV21
    di prototype v32/v18.

    REVISI TOTAL (2026-09-13) — versi sebelumnya (14-hari, list vertikal
    per hari) dibangun berdasarkan audit kode yang KELIRU (baca fungsi
    versi lama v9 yang udah ketimpa versi v21 di file yang sama, gampang
    ketuker karena banyak function senama). Dikonfirmasi LANGSUNG dari
    screenshot app prototype yang beneran jalan: versi final itu FULL
    MONTH GRID (Minggu-Sabtu, 6 baris), bukan list 14 hari. Dibangun
    ulang total di sini biar match:
    - Navigasi bulan (←/Today/→) lewat query string ?month=YYYY-MM
    - Filter Project & PIC (dropdown, submit GET biasa — bukan AJAX
      kayak prototype, konsisten sama pola multi-page WSM-Office)
    - "Weekly Rhythm" strip (fokus kerja Senin-Jumat) — hari/fokus
      hardcoded (lihat WorkTrackerController::WEEKLY_RHYTHM, belum ada
      UI buat Owner ubah), TAPI jam kerjanya (fix 2026-09-14, "belum
      pakai data asli") ambil dari OfficeSetting ASLI, bukan angka
      hardcoded terpisah
    - Task berwarna per-project (palet WSM, di-assign deterministik per
      project_id — BELUM ada kolom `color` asli di tabel projects) +
      legend warna di bawah. Task item sendiri (judul/PIC) SELALU dari
      WorkItem::query() beneran, bukan data contoh — kalau kelihatan
      kosong artinya emang belum ada WorkItem dengan due_date di bulan
      itu, bukan bug.

    Mobile (fix 2026-09-14, "belum responsif") — grid-nya sekarang
    NATIVE 7-kolom yang menyusut ngikutin lebar container App Mode
    (`max-w-140` ~560px), TANPA dipaksa scroll-horizontal seperti
    percobaan pertama. Rhythm ditampilkan sebagai titik warna kecil
    (bukan teks penuh) & task item dipendekin agresif + "+N" count di
    tiap sel biar tetap kebaca utuh tanpa geser. Weekly rhythm strip di
    atas grid TETAP horizontal-scroll (pola carousel kartu biasa, beda
    konteks dari grid tabel yang harus utuh kebaca sekali lihat).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Shared Calendar'])

@section('content')
    @php
        $prevMonth = $anchor->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $anchor->copy()->addMonthNoOverflow()->format('Y-m');
        $todayMonth = now()->format('Y-m');
    @endphp

    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Shared Workload Calendar</p>
            <h1 class="mt-0.5 text-xl font-black leading-tight">{{ $anchor->translatedFormat('F Y') }}</h1>
            <p class="mt-1 text-[11px] text-muted">
                Kalender bersama dari deadline Work Tracker seluruh tim — bukan kalender pribadi.
            </p>
        </div>
        <a href="{{ route('employee.home') }}"
            class="grid h-8 w-8 flex-none place-items-center rounded-full border border-line bg-white text-sm font-black text-muted">
            ×
        </a>
    </div>

    {{-- Navigasi bulan --}}
    <div class="mb-3.5 flex items-center gap-2">
        <a href="{{ route('employee.workTracker.calendar', array_filter(['month' => $prevMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
            class="btn-wsm-white py-2! px-3.5! text-xs!">←</a>
        <a href="{{ route('employee.workTracker.calendar', array_filter(['month' => $todayMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
            class="btn-wsm-white py-2! px-3.5! text-xs!">Today</a>
        <a href="{{ route('employee.workTracker.calendar', array_filter(['month' => $nextMonth, 'project' => $projectFilter, 'pic' => $picFilter])) }}"
            class="btn-wsm-white py-2! px-3.5! text-xs!">→</a>
    </div>

    {{-- Filter Project & PIC — submit GET biasa (bukan onchange AJAX kayak prototype) --}}
    <form method="GET" class="mb-3.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
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

    {{-- Weekly Rhythm strip — fokus kerja Senin-Jumat, hardcoded (lihat
         catatan WorkTrackerController::WEEKLY_RHYTHM) --}}
    <div class="mb-3.5 -mx-4 overflow-x-auto px-4 pb-1">
        <div class="flex gap-2" style="min-width:max-content">
            @foreach ($weeklyRhythm as $dow => $rhythm)
                <div
                    class="w-32 flex-none rounded-2xl border p-2.5 {{ now()->dayOfWeek === $dow ? 'border-ink bg-ink text-white' : 'border-line bg-white' }}">
                    <strong class="block text-[11px] font-black">{{ $rhythm['day'] }}</strong>
                    <span
                        class="mt-1 block text-[10px] leading-tight {{ now()->dayOfWeek === $dow ? 'text-white/80' : 'text-muted' }}">{{ $rhythm['focus'] }}</span>
                    <small
                        class="mt-1 block text-[9px] {{ now()->dayOfWeek === $dow ? 'text-white/60' : 'text-muted' }}">{{ $rhythm['mode'] }}
                        · {{ $rhythm['hours'] }}</small>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Grid bulan penuh — REVISI (2026-09-14, fix "belum responsif").
         Sebelumnya dipaksa `min-width:700px` + scroll-horizontal, yang
         di App Mode (container `max-w-140` ~560px) BERARTI SELALU perlu
         geser horizontal buat lihat grid-nya, bahkan di layar desktop
         lebar sekalipun (container-nya tetap sempit sengaja, App Mode
         emang dibikin mobile-first). Sekarang grid-nya NATIVE 7-kolom
         yang nyusut sendiri ngikutin lebar container (gak ada
         min-width paksa) — sel jadi kecil di HP, tapi TETAP kebaca
         tanpa perlu geser: rhythm ditampilkan sebagai strip warna tipis
         (bukan blok teks penuh) & task item dipendekin agresif +
         "+N" count kalau kepanjangan. Weekly rhythm strip di atas
         TETAP scroll-horizontal (itu pola kartu carousel biasa, beda
         dari grid tabel yang harus utuh kebaca). --}}
    <div class="grid grid-cols-7 gap-1 text-center">
        @foreach (['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $label)
            <div class="pb-1 text-[8px] font-black uppercase text-muted sm:text-[10px]">{{ $label }}</div>
        @endforeach
    </div>
    <div class="mb-3.5 grid grid-cols-7 gap-1">
        @foreach ($weeks as $week)
            @foreach ($week as $cell)
                <div
                    class="min-h-16 overflow-hidden rounded-lg border p-1 sm:min-h-24 sm:rounded-xl sm:p-1.5 {{ $cell['outside'] ? 'border-line/60 bg-[#faf8f3] opacity-40' : ($cell['isToday'] ? 'border-ink ring-2 ring-ink/10 bg-white' : 'border-line bg-white') }}">
                    <div class="flex items-center justify-between gap-0.5">
                        <span class="text-[9px] font-black sm:text-[11px]">{{ $cell['date']->day }}</span>
                        @if ($cell['rhythm'])
                            <span
                                class="h-1.5 w-1.5 flex-none rounded-full {{ $cell['rhythm']['mode'] === 'WFH' ? 'bg-brand-blue' : 'bg-[#a39c8f]' }}"
                                title="{{ $cell['rhythm']['focus'] }} · {{ $cell['rhythm']['mode'] }} · {{ $cell['rhythm']['hours'] }}"></span>
                        @endif
                    </div>
                    <div class="mt-0.5 grid gap-0.5 sm:mt-1">
                        @foreach ($cell['items']->take(2) as $item)
                            <div class="truncate rounded px-1 py-0.5 text-[6.5px] font-bold leading-tight sm:text-[8px]"
                                style="background:{{ $item['color'] }};color:{{ $item['text'] }}"
                                title="{{ $item['title'] }}{{ $item['pic'] ? ' · ' . $item['pic'] : '' }}">
                                {{ $item['title'] }}
                            </div>
                        @endforeach
                        @if ($cell['items']->count() > 2)
                            <span class="text-[6.5px] text-muted sm:text-[8px]">+{{ $cell['items']->count() - 2 }}</span>
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
@endsection
