<?php

/**
 * config/entry_popups.php
 * ---------------------------------------------------------------------
 * Popup informasi yang tampil saat buka Website (publik + login), App
 * Mode, dan Dashboard — lihat README Bab 4.2 no. 8. Isi & urutan
 * dipetakan App\Support\EntryPopups, markup ada di
 * partials/entry-popups.blade.php + entry-popups/{welcome,notice},
 * antrean & sessionStorage diurus resources/js/entry-popups.js.
 *
 * Dua saklar:
 * - `enabled`      : mematikan SEMUA popup (welcome + notice) sekaligus.
 *                    `phpunit.xml` mengisinya `false` supaya tes yang
 *                    sudah ada tidak perlu tahu soal popup ini.
 * - `preview_mode` : mematikan Peringatan preview doang (Sambutan App
 *                    Mode tetap ada). WAJIB di-set `false` di `.env`
 *                    produksi begitu WOS mulai dipakai operasional
 *                    (README Bab 4.1 no. 2) — baru itu artinya "sudah
 *                    dipakai operasional", bukan cuma sudah di-deploy.
 *
 * `version` — satu angka dipakai bareng oleh semua popup di bawah.
 * Ubah teks apa pun di sini? Naikkan angka ini juga, supaya popup yang
 * sudah "dibaca" (tersimpan di sessionStorage browser) muncul lagi.
 * ---------------------------------------------------------------------
 */

return [

    'enabled' => env('WOS_ENTRY_POPUPS', true),

    'preview_mode' => env('WOS_PREVIEW_MODE', true),

    'version' => 1,

    /*
     * Peringatan preview — tampil di ketiga pintu masuk (publik+login,
     * App Mode, Dashboard). `dashboard_body` menggantikan `body` khusus
     * di pintu Dashboard, biar isinya terasa relevan sama konteks
     * halaman ("data DI DASHBOARD ini").
     */
    'notice' => [
        'title' => 'Masih tahap uji coba',
        'body' => [
            'Website ini masih dalam pengembangan dan belum dipakai untuk operasional harian — silakan dicoba-coba dulu, santai aja.',
            'Data yang kamu masukkan sekarang cuma dipakai untuk testing.',
            'Data testing ini bakal direset begitu sistem sudah siap dipakai operasional, jadi jangan masukkan data asli atau rahasia dulu ya.',
        ],
        'dashboard_body' => [
            'Dashboard ini masih dalam pengembangan dan belum dipakai untuk operasional harian — silakan dicoba-coba dulu, santai aja.',
            'Semua data di dashboard ini — Karyawan, Payroll, Memo, dan lainnya — cuma data uji buat testing.',
            'Data testing ini bakal direset begitu sistem sudah siap dipakai operasional, jadi jangan masukkan data asli atau rahasia dulu ya.',
        ],
        'button' => 'Oke, mengerti',
    ],

    /*
     * Sambutan — cuma tampil di App Mode ($door === 'app'), sebelum
     * Peringatan. Sapaan per jam & nama depan disiapkan di
     * App\Support\EntryPopups (bukan di sini, karena butuh jam berjalan
     * & data user yang login).
     */
    'welcome' => [
        'title' => 'Welcome to W.O.S',
        'subtitle' => 'Whisnu Santika Music — Office System',
        'button' => 'Lanjut',
    ],

];