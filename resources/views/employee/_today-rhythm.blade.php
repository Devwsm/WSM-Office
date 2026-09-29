{{--
    employee/_today-rhythm.blade.php
    ---------------------------------------------------------------------
    Polish (2026-09-28, dari audit tampilan absensi vs prototype v32) —
    padanan `todayRhythmCard()` di prototype (dipanggil dari
    employeeCelebrationMarkup, tapi tampil PALING ATAS sebelum
    Milestones — screenshot user konfirmasi ini). Sebelumnya kartu ini
    TIDAK ADA sama sekali di Home; weekly rhythm cuma dipakai di
    Timeline Calendar & CEO Dashboard.

    Dipakai persis pola OfficeSetting::weeklyRhythm() yang sudah ada
    (key 1=Senin..5=Jumat, sama seperti Carbon::dayOfWeek). Weekend
    (Sabtu/Minggu) tidak punya baris rhythm -> kartu disembunyikan
    total, sama seperti prototype (`if(!x)return''`).
--}}
@php
    $todayRhythm = \App\Models\OfficeSetting::current()->weeklyRhythm()[now()->dayOfWeek] ?? null;
@endphp

@if ($todayRhythm)
    <div class="mb-3.5 flex items-center justify-between gap-3 rounded-wsm-lg bg-ink p-4.5 text-white">
        <div class="min-w-0">
            <span class="text-[10px] font-black uppercase tracking-wide text-white/55">Today's Work Rhythm</span>
            <p class="mt-1 truncate text-base font-black leading-tight">{{ $todayRhythm['focus'] }}</p>
            <p class="mt-0.5 text-[11px] text-white/65">{{ $todayRhythm['mode'] }} · {{ $todayRhythm['hours'] }}</p>
        </div>
        <a href="{{ route('employee.workTracker.calendar') }}"
            class="grid h-9 w-9 flex-none place-items-center rounded-2xl bg-white/12 text-base">↗</a>
    </div>
@endif
