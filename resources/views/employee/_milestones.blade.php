{{--
    employee/_milestones.blade.php
    ---------------------------------------------------------------------
    App Mode quick win (2026-09-09, dari audit ronde 4 di README) —
    padanan `employeeCelebrationMarkup(emp)` di prototype: Lama Bekerja,
    Birthday, dan Work Anniversary. Sengaja TIDAK dioper dari
    HomeController — sama pola kayak job_title/divisi (Fase 8), manggil
    `auth()->user()` langsung karena semua datanya (`birth_date`,
    `join_date`) sudah ada di kolom User, gak butuh query tambahan.

    Kalau `birth_date`/`join_date` belum diisi Owner di Master Karyawan,
    kartu yang bersangkutan nampilin pesan "belum diset" (bukan
    disembunyikan total) — sama seperti prototype (`b.configured` /
    `a.configured` false).
    ---------------------------------------------------------------------
--}}
@php
    $me = auth()->user();
    $birthday = $me->nextBirthdayOccurrence();
    $anniversary = $me->nextWorkAnniversaryOccurrence();
    $serviceLabel = $me->serviceDurationLabel();
@endphp

<div class="card-wsm-white mb-3.5">
    <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Milestones</p>
    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
        {{-- Lama Bekerja --}}
        <div class="rounded-2xl border border-line bg-cream p-3.5">
            <span class="text-lg leading-none">⌛</span>
            <p class="mt-1.5 text-xs font-black leading-tight">
                {{ $serviceLabel ?? 'Lama Bekerja' }}
            </p>
            <p class="mt-0.5 text-[10px] text-muted">
                @if ($serviceLabel)
                    Sejak {{ $me->join_date->translatedFormat('d F Y') }}
                @else
                    Join date belum diset oleh Owner
                @endif
            </p>
        </div>

        {{-- Birthday --}}
        <div class="rounded-2xl border border-line bg-cream p-3.5">
            <span class="text-lg leading-none">🎂</span>
            <p class="mt-1.5 text-xs font-black leading-tight">
                {{ $birthday ? $birthday['date']->translatedFormat('d F') : 'Birthday' }}
            </p>
            <p class="mt-0.5 text-[10px] text-muted">
                @if ($birthday)
                    {{ $birthday['days'] === 0 ? 'Hari ini! 🎉' : $birthday['days'] . ' hari lagi' }} · ulang tahun
                    pribadi
                @else
                    Birth date belum diset oleh Owner
                @endif
            </p>
        </div>

        {{-- Work Anniversary --}}
        <div class="rounded-2xl border border-line bg-cream p-3.5">
            <span class="text-lg leading-none">✦</span>
            <p class="mt-1.5 text-xs font-black leading-tight">
                @if ($anniversary)
                    {{ $anniversary['years'] }} Tahun
                @else
                    Work Anniversary
                @endif
            </p>
            <p class="mt-0.5 text-[10px] text-muted">
                @if ($anniversary)
                    {{ $anniversary['date']->translatedFormat('d F') }} ·
                    {{ $anniversary['days'] === 0 ? 'hari ini' : $anniversary['days'] . ' hari lagi' }}
                @else
                    Join date belum diset
                @endif
            </p>
        </div>
    </div>
</div>
