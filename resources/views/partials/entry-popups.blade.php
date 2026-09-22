{{--
    partials/entry-popups.blade.php
    ---------------------------------------------------------------------
    Popup informasi preview (README Bab 4.2 no. 8): peringatan "masih
    preview, data cuma buat testing" di semua pintu masuk, + Sambutan
    "Welcome to W.O.S" khusus App Mode sebelum peringatannya. Di-include
    SEKALI per layout dengan `$door` yang sesuai:
        @include('partials.entry-popups', ['door' => 'public'])     -- layouts.public, auth/login
        @include('partials.entry-popups', ['door' => 'app'])        -- layouts.employee
        @include('partials.entry-popups', ['door' => 'dashboard'])  -- layouts.app
    Tidak ada view halaman yang perlu diubah.

    Isi & urutan popup per pintu ada di config/entry_popups.php,
    disiapkan App\Support\EntryPopups::forDoor() (pola sama dengan
    PageGuide). Antrean tampil (welcome dulu baru notice) + penanda
    sudah-dibaca di sessionStorage browser diurus
    Alpine.data('entryPopups', ...) di resources/js/entry-popups.js.
    Kunci sessionStorage-nya gabungan pintu + popup + versi + token sesi
    login (potongan hash session ID Laravel) — login ulang, ganti akun,
    atau tab baru bikin muncul lagi; teks diubah -> naikkan `version` di
    config biar muncul lagi juga.

    JANGAN include partial ini di layouts/error.blade.php atau
    dashboard/locked.blade.php (dua-duanya sengaja <html> sendiri, tidak
    extends layout apa pun) — dan JANGAN render selama user masih wajib
    ganti password (guard-nya ada di layouts/employee.blade.php, lihat
    catatan @unless di situ), biar tidak menumpuk dengan pengalihan
    paksa ke Profil (EnsurePasswordChanged).

    Pola modal SAMA dengan partials/page-guide.blade.php: bottom-sheet
    di HP, di tengah layar di desktop. Tutup lewat tombol utama, ✕, Esc,
    atau klik area gelap — SEMUANYA dianggap "sudah dibaca" (next()),
    bukan cuma tutup sementara; popup sesudahnya (kalau ada) langsung
    lanjut ditampilkan.
    ---------------------------------------------------------------------
--}}
@php
    $entryPopupsData = \App\Support\EntryPopups::forDoor($door, auth()->user());
@endphp

@if ($entryPopupsData['popups'] !== [])
    <div x-data="entryPopups({
        popups: @js($entryPopupsData['popups']),
        door: @js($door),
        token: @js(substr(hash('crc32b', session()->getId()), 0, 8)),
    })" x-init="init()" x-cloak class="print:hidden">
        <template x-if="current">
            <div x-show="open" x-transition.opacity.duration.150ms style="display:none" @click.self="close()"
                @keydown.escape.window="close()"
                class="fixed inset-0 z-60 grid place-items-end bg-black/45 p-0 sm:place-items-center sm:p-4">

                <div role="dialog" aria-modal="true" :aria-labelledby="current.type + '-entry-popup-title'"
                    class="relative flex max-h-[88vh] w-full flex-col overflow-hidden rounded-t-4xl bg-cream sm:max-w-md sm:rounded-4xl">

                    <button type="button" @click="close()" aria-label="Tutup"
                        class="absolute right-4 top-4 z-10 grid h-9 w-9 place-items-center rounded-2xl bg-white/90 text-sm font-black text-ink shadow-sm">✕</button>

                    <template x-if="current.type === 'welcome'">
                        @include('partials.entry-popups.welcome')
                    </template>
                    <template x-if="current.type === 'notice'">
                        @include('partials.entry-popups.notice')
                    </template>
                </div>
            </div>
        </template>
    </div>
@endif
