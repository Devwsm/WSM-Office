{{--
    employee/_work-tracker.blade.php
    ---------------------------------------------------------------------
    Fase 9 (2026-09-09) — "My Work Tracker", padanan
    `employeeTasksMarkup()` versi PALING AKHIR di prototype (baris
    ~1857 WOS_2_0_STANDALONE_v32.html). Embedded langsung di Home, sama
    pola Milestones/My KPI/Team Moments — data (`$openWorkItems`,
    `$doneWorkItemsCount`) dihitung di HomeController, bukan di sini.

    KOREKSI (lihat README): versi final ini TIDAK punya filter Project/
    Category/Progress — audit sebelumnya salah ambil dari versi v12
    yang sudah digantikan. Yang ada cuma daftar item open (max 8) +
    tombol "Shared Calendar" (WorkTrackerController::calendar()).

    Section ini TIDAK disembunyikan walau `$openWorkItems` kosong (beda
    dari Team Moments) — prototype tetap nampilin section + pesan
    "Tidak ada item aktif" (lihat `taskCardMarkup` fallback), karena "My
    Work Tracker" adalah bagian tetap dari Home App Mode, bukan
    notifikasi kondisional kayak Team Moments.
    ---------------------------------------------------------------------
--}}
<div id="my-work-tracker" class="employee-section mb-3.5 scroll-mt-4">
    <div class="employee-section-head mb-2.5 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-black">My Work Tracker</h2>
            <span class="text-[11px] text-muted">
                {{ $openWorkItems->count() }} open · {{ $doneWorkItemsCount }} done
            </span>
        </div>
        <a href="{{ route('employee.workTracker.calendar') }}"
            class="flex-none rounded-2xl border border-line bg-white px-3 py-2 text-[10px] font-extrabold text-ink">
            ▦ Shared Calendar
        </a>
    </div>

    <div>
        @forelse ($openWorkItems->take(8) as $item)
            @include('employee._work-item-card', ['item' => $item])
        @empty
            <div class="card-wsm-white text-center text-xs text-muted">
                Tidak ada item aktif.
            </div>
        @endforelse
    </div>

    {{-- 2026-10-11 — riwayat task selesai. Ubah statusnya untuk membuka kembali
        task yang tidak sengaja ditandai Done. --}}
    @if (($doneWorkItems ?? collect())->isNotEmpty())
        <details class="mt-2 rounded-2xl border border-line bg-white/60 p-3">
            <summary class="cursor-pointer text-[11px] font-extrabold text-muted">
                Lihat item selesai ({{ $doneWorkItems->count() }} terbaru dari {{ $doneWorkItemsCount }})
            </summary>
            <p class="mb-2 mt-2 text-[10px] text-muted">Salah tandai selesai? Ubah statusnya untuk membuka kembali.</p>
            @foreach ($doneWorkItems as $item)
                @include('employee._work-item-card', ['item' => $item])
            @endforeach
        </details>
    @endif
</div>
