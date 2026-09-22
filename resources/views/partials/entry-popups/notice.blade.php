{{--
    partials/entry-popups/notice.blade.php
    ---------------------------------------------------------------------
    Isi popup Peringatan preview: "masih preview / data cuma buat
    testing / bakal direset saat operasional". Tampil di ketiga pintu
    masuk; teks poin beda tipis untuk Dashboard ($door === 'dashboard',
    lihat App\Support\EntryPopups::notice() -> config('entry_popups.notice.dashboard_body'))
    biar terasa relevan sama konteks halaman ("data DI DASHBOARD ini").

    Sama seperti welcome.blade.php: semua teks pakai x-text, bukan
    Blade, karena dirender di dalam <template x-if>.
    ---------------------------------------------------------------------
--}}
<div class="px-6 pb-5 pt-7">
    <p class="pr-12 text-[11px] font-black uppercase tracking-wide text-muted">Info Sistem</p>
    <h2 :id="current.type + '-entry-popup-title'" class="mt-1 text-xl font-black leading-tight" x-text="current.title">
    </h2>

    <ul class="mt-4 grid gap-2.5">
        <template x-for="(line, index) in current.body" :key="index">
            <li
                class="flex gap-2.5 rounded-2xl border border-line bg-white px-3.5 py-3 text-[13px] leading-relaxed text-[#5e5951]">
                <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full bg-ink" aria-hidden="true"></span>
                <span x-text="line"></span>
            </li>
        </template>
    </ul>

    <button type="button" @click="next()" class="btn-wsm-black mt-5 w-full py-2.5! text-xs">
        <span x-text="current.button"></span>
    </button>
</div>
