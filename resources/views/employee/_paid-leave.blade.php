{{--
    employee/_paid-leave.blade.php
    ---------------------------------------------------------------------
    App Mode quick win (2026-09-09, ronde 5 dari audit README) —
    padanan `paidLeaveBannerV18(emp)` di prototype: banner besar warna
    lime di Home nampilin sisa cuti tahunan. Posisi PERSIS prototype —
    tepat setelah Milestones, sebelum banner "Cuti Tim Bulan Ini".

    Sengaja TIDAK dioper dari HomeController (sama pola Milestones) —
    manggil `auth()->user()` langsung karena datanya
    (`annual_leave_entitlement`, `usedAnnualLeaveDays()`,
    `remainingAnnualLeaveDays()`) sudah ada di model User sejak
    Fase 2/5, dipakai juga di halaman Profile & Request Cuti.

    Catatan deviasi (lihat README ronde 5): prototype `leaveCycle()`
    hitung "accrued" hasil proration bulanan di tahun pertama kerja
    (bukan flat entitlement). WSM-Office belum punya logic accrual
    bulanan itu di modul Cuti manapun (Profile & Request Cuti juga
    pakai `annual_leave_entitlement` flat) — banner ini SENGAJA
    disamakan ke pola yang sudah ada di app ini dulu, bukan re-arsitektur
    logic cuti di tengah quick win. Kalau proration bulanan dibutuhkan
    beneran, itu perubahan terpisah yang harus konsisten di 3 halaman
    sekaligus (Profile, Request Cuti, banner ini).
    ---------------------------------------------------------------------
--}}
@php
    $me = auth()->user();
    $entitlement = (int) ($me->annual_leave_entitlement ?? 0);
    $used = $me->usedAnnualLeaveDays();
    $remaining = $me->remainingAnnualLeaveDays();
    $resetInfo = $me->nextWorkAnniversaryOccurrence();
@endphp

<div class="mb-3.5 rounded-wsm-lg bg-brand-lime p-4.5 text-[#13220d] sm:p-5">
    @if ($me->join_date)
        <div class="grid grid-cols-1 items-center gap-3.5 sm:grid-cols-[1fr_auto]">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wide text-[#315111]">Paid Leave</span>
                <strong class="mt-1 block text-[34px] leading-none font-black tracking-[-1px]">{{ $remaining }}
                    hari</strong>
                <div class="mt-1.5 text-[11px] leading-relaxed text-[#13220d]/75">
                    dari {{ $entitlement }} hari tersedia · {{ $used }} sudah terpakai<br>
                    Sisa hangus & reset pada anniversary join date:
                    <strong>{{ $resetInfo ? $resetInfo['date']->translatedFormat('d F Y') : '-' }}</strong>
                </div>
            </div>
            <div
                class="grid h-16 w-16 flex-none place-items-center justify-self-start rounded-full border border-black/18 bg-white/20 text-xl font-black sm:h-19.5 sm:w-19.5 sm:justify-self-auto">
                {{ $remaining }}/{{ $entitlement }}
            </div>
        </div>
    @else
        <div>
            <strong class="text-sm font-black">Paid Leave</strong>
            <p class="mt-1 text-[11px] text-[#13220d]/75">Join date belum diset / leave tidak berlaku.</p>
        </div>
    @endif
</div>
