{{--
    employee/work-tracker/calendar.blade.php
    ---------------------------------------------------------------------
    "Shared Calendar" — padanan `openSharedWorkloadCalendar()` di
    prototype. Fungsinya identik: 14 hari ke depan termasuk hari ini,
    semua WorkItem TIM (bukan cuma punya sendiri) yang due di rentang
    itu & belum Done.

    2026-09-09 — REVISI setelah dicek ulang & dibandingkan visual sama
    Arga ("kenapa beda jauh dari prototype?"). 2 hal yang beda itu
    SEHARUSNYA GAK PERLU beda, sudah dibetulkan di revisi ini:
    - Warna kartu task: prototype `.workload-task` = `#eeeae3` (bukan
      punya sendiri) / `.workload-task.mine` = `#dfe7ff` (punya
      sendiri) — versi awal salah pakai hex custom (`#f6f4ef`/
      `#eef6ff`), sekarang persis sama, nol drift warna.
    - Header: prototype pakai header KOMPAK (eyebrow "SHARED WORKLOAD"
      + h2 "14-Day Team Calendar" + tombol close ×) — versi awal malah
      pakai gaya "Hero" halaman penuh (32px bold, 2 baris) kayak Home,
      kegedean & gak sesuai konteks "buka dari 1 tombol di widget".
      Sekarang header dibikin kompak, konsisten sama header section
      lain di App Mode (pola sama `.employee-section-head` prototype).

    1 hal yang TETAP beda, DAN INI SENGAJA (bukan salah): prototype
    render ini sebagai MODAL di atas Home (SPA, sekali render client-
    side). WSM-Office bukan SPA (multi-page Laravel) — diadaptasi jadi
    HALAMAN TERSENDIRI. Layout grid-nya sendiri PERSIS SAMA — dicek
    ulang CSS prototype: `.workload-grid` itu `repeat(7,1fr)` di layar
    lebar, TAPI di breakpoint `@media(max-width:560px)` prototype
    SENDIRI juga collapse jadi `grid-template-columns:1fr` (1 kolom,
    vertikal) — App Mode di sini mobile-first, jadi versi 1-kolom
    vertikal itu yang dipakai, PERSIS versi mobile prototype sendiri,
    bukan improvisasi.
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Shared Calendar'])

@section('content')
    <div class="mb-5 flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-black uppercase tracking-wide text-muted">Shared Workload</p>
            <h1 class="mt-0.5 text-xl font-black leading-tight">14-Day Team Calendar</h1>
            <p class="mt-1 text-[11px] text-muted">
                Melihat workload bersama dari deadline Work Tracker, bukan kalender pribadi.
            </p>
        </div>
        <a href="{{ route('employee.home') }}"
            class="grid h-8 w-8 flex-none place-items-center rounded-full border border-line bg-white text-sm font-black text-muted">
            ×
        </a>
    </div>

    <div class="grid gap-2">
        @foreach ($days as $day)
            @php
                $dateKey = $day->toDateString();
                $dayItems = $items->get($dateKey, collect());
                $isToday = $day->isToday();
            @endphp
            {{-- Padanan `.workload-day` / `.workload-day.today` — hari ini ditandai
                 border + ring tipis (padanan `box-shadow` glow ring di prototype),
                 bukan diblok warna solid, biar tetap kebaca sebagai "kartu putih". --}}
            <div
                class="rounded-[17px] border bg-white p-2.5 {{ $isToday ? 'border-ink ring-2 ring-ink/10' : 'border-line' }}">
                <strong class="text-[10px]">{{ $day->translatedFormat('D, d M') }}</strong>
                <small class="mb-2 block text-[8px] text-muted">{{ $dayItems->count() }} item</small>

                @forelse ($dayItems as $item)
                    {{-- Padanan `.workload-task` / `.workload-task.mine` — hex warna
                        PERSIS prototype, nol drift. --}}
                    <div class="mb-1.5 rounded-[9px] px-2.5 py-1.5 text-[10px] leading-tight last:mb-0"
                        style="background:{{ $item->pic_employee_id === $meId ? '#dfe7ff' : '#eeeae3' }}">
                        <b class="block font-black">{{ $item->title }}</b>
                        <span class="text-muted">{{ $item->pic?->name ?? 'Belum ada PIC' }}</span>
                    </div>
                @empty
                    <p class="text-[10px] text-muted">No workload</p>
                @endforelse
            </div>
        @endforeach
    </div>
@endsection
