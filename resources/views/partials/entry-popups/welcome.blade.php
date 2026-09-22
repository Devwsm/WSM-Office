{{--
    partials/entry-popups/welcome.blade.php
    ---------------------------------------------------------------------
    Isi popup Sambutan ("Welcome to W.O.S") — kartu gelap + equalizer &
    piringan hitam berputar, CSS murni tanpa gambar atau library baru
    (lihat .entry-popup-equalizer / .entry-popup-vinyl di
    resources/css/app.css). Cuma tampil di App Mode ($door === 'app'),
    urutan pertama sebelum Peringatan (resources/js/entry-popups.js).
    Animasi mati total kalau perangkat minta
    @media (prefers-reduced-motion: reduce).

    Semua teks pakai x-text, BUKAN Blade — partial ini dirender di
    dalam <template x-if>, datanya sudah disiapkan
    App\Support\EntryPopups::forDoor() lalu di-passing sebagai JSON ke
    x-data di entry-popups.blade.php (bukan dibaca ulang dari server
    tiap ganti popup).
    ---------------------------------------------------------------------
--}}
<div class="entry-popup-welcome relative overflow-hidden rounded-t-4xl bg-ink px-6 pb-6 pt-8 text-white sm:rounded-4xl">
    <div class="entry-popup-equalizer" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="entry-popup-vinyl" aria-hidden="true">
        <span></span>
    </div>

    <p class="pr-12 text-[11px] font-black uppercase tracking-wide text-white/60" x-text="current.greeting"></p>
    <h2 :id="current.type + '-entry-popup-title'" class="mt-1 text-2xl font-black leading-tight"
        x-text="current.first_name"></h2>
    <p class="mt-4 text-[26px] font-black leading-none tracking-tight" x-text="current.title"></p>
    <p class="mt-2 text-sm text-white/70" x-text="current.subtitle"></p>
    <span class="mt-4 inline-flex items-center rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-white"
        x-text="current.role_label"></span>

    <button type="button" @click="next()" class="btn-wsm-white mt-6 w-full py-2.5! text-xs">
        <span x-text="current.button"></span>
    </button>
</div>
