# WSM Office System

Sistem internal untuk **Whisnu Santika Music (WSM)** — absensi, cuti/izin,
manajemen karyawan, project & KPI, kontrak kerja, payroll, hingga rekrutmen
(landing page publik + karir). Dibangun dengan Laravel + Tailwind CSS v4 +
Alpine.js, mengikuti konvensi stack yang sama dengan project lain (Mavnus,
Map of Feelings).

> Status saat ini: **Fase 0 (Fondasi) selesai**, **Fase 1 (Landing Page &
> Company Profile) sudah jalan** — Beranda, Tentang Kami, Layanan, Karir,
> Kontak sudah ada route + view (konten masih hardcode, form kontak belum
> simpan ke DB). Fase 2 ke atas (Manajemen Karyawan, Rekrutmen, dst.) belum
> mulai — internal masih shell login + dashboard placeholder untuk role
> Owner & Karyawan/Manajer.

## Tech Stack

- Laravel (latest) + PHP
- Tailwind CSS v4 (CSS-first config via `@theme` di `resources/css/app.css`, tanpa `tailwind.config.js`)
- Alpine.js untuk interaksi ringan (sidebar toggle, dsb.)
- Vite + `laravel-vite-plugin` (font di-_bundle_ lewat Bunny Fonts, bukan Google Fonts CDN)
- MySQL di produksi (SQLite untuk dev lokal saat ini)
- Deploy target: shared hosting cPanel/Rumahweb tanpa akses terminal — sama seperti Mavnus & Map of Feelings, jadi hindari dependency yang butuh compile/CLI di server.

## Desain — Disamakan dengan Prototype W.O.S 2.0

Tampilan lama (default Tailwind gray/putih polos) sudah diganti supaya
konsisten dengan prototype desain **W.O.S 2.0** (`WOS_2_0_STANDALONE_v13.html`,
lihat folder `absensi_wsm` yang dikirim terpisah). Prototype itu adalah
acuan visual (bukan kode yang dipakai langsung — dia HTML/CSS/JS standalone
tanpa Laravel), jadi setiap kali menambah halaman baru, cocokkan gaya ke
prototype tersebut dulu sebelum ngoding.

Token desain didefinisikan di `resources/css/app.css` lewat blok `@theme`
Tailwind v4 (otomatis jadi utility class, contoh: `--color-cream` → class
`bg-cream`/`text-cream`):

| Token                  | Hex       | Kegunaan                                          |
| ---------------------- | --------- | ------------------------------------------------- |
| `--color-cream`        | `#f2efe7` | Background utama seluruh halaman                  |
| `--color-paper`        | `#fbf9f4` | Background kartu/sidebar sekunder                 |
| `--color-ink`          | `#101010` | Teks utama, tombol hitam, sidebar item aktif      |
| `--color-muted`        | `#8b867e` | Teks sekunder                                     |
| `--color-line`         | `#e5dfd5` | Border kartu/input                                |
| `--color-brand-blue`   | `#3558f4` | Aksen biru (kartu status, badge, link)            |
| `--color-brand-yellow` | `#deb92e` | Aksen kuning (stat card)                          |
| `--color-brand-green`  | `#27c84d` | Aksen hijau (stat card, tombol center bottom-nav) |
| `--color-brand-lime`   | `#b4ef4b` | Aksen lime (stat card)                            |
| `--color-brand-red`    | `#f16c61` | Aksen merah (error/badge)                         |

Font: **Inter** (sebelumnya Instrument Sans), di-load lewat Bunny Fonts di
`vite.config.js`. Radius kartu besar (22–28px), tombol pill penuh
(`rounded-full`), heading tebal & rapat (`font-black`, `tracking-tight`).

Class siap pakai (di `@layer components`, `resources/css/app.css`):

- Tombol: `.btn-wsm-black`, `.btn-wsm-white`, `.btn-wsm-blue`, `.btn-wsm-red`
- Kartu: `.card-wsm` (paper bg), `.card-wsm-white` (white bg)
- Stat card warna solid: `.stat-wsm-blue`, `.stat-wsm-yellow`, `.stat-wsm-green`, `.stat-wsm-lime`
- Badge: `.badge-wsm-green`, `.badge-wsm-yellow`, `.badge-wsm-red`, `.badge-wsm-blue`, `.badge-wsm-gray`
- Form: `.field-label-wsm`, `.input-wsm`
- Bottom nav (layout Karyawan): `.bottom-nav-wsm`, `.bottom-nav-wsm-item` (+ modifier `.active`, `.center`)

Dua layout yang sudah disesuaikan:

- `resources/views/layouts/app.blade.php` — shell **Owner/Manajer**, sidebar
  paper dengan brand mark hitam, nav item aktif jadi pill hitam (meniru
  `.owner-sidebar` di prototype).
- `resources/views/layouts/employee.blade.php` — shell **Karyawan** gaya
  mobile app, hero besar + bottom nav pill melayang (meniru
  `.employee-shell` + `.bottom-nav` di prototype).

Halaman `auth/login.blade.php`, `employee/home.blade.php`, dan
`owner/dashboard.blade.php` sudah ikut disesuaikan (kartu, tombol, stat
card warna). Halaman lain yang belum dibuat (Fase 1 ke atas) tinggal pakai
class-class di atas supaya konsisten — jangan balik pakai `bg-white
border rounded-xl` polos lagi.

## Roadmap Modul & Role

Sistem punya bagian **publik** (landing page + karir, tanpa login) dan
**internal**. Role saat ini di kolom `users.role` baru 3: `owner`,
`manajer`, `karyawan` — HRD **belum ada sebagai role terpisah** di
database/middleware, meski disebut sebagai salah satu dari "4 role" di
dokumen breakdown awal. Kemungkinan HRD nanti jadi salah satu hak akses
Owner-assign (mirip Manajer) begitu Fase 3 (Rekrutmen) digarap — perlu
diperjelas sebelum migration role ditambah. Manajer sendiri adalah role
eksplisit yang di-assign Owner (bukan status otomatis dari org-chart) —
org-chart tetap dipakai untuk menentukan siapa manajer dari siapa
(approval cuti/izin).

Urutan fase development:

0. **Fondasi** — Laravel, Tailwind, Vite, login multi-role, middleware akses. ✅
1. **Landing Page & Company Profile** — Beranda, Tentang Kami, Layanan, Karir, Kontak (publik, tanpa login). ✅ _(route + view sudah ada; konten masih hardcode di Blade, bukan CMS — itu baru Fase 12. Form kontak baru flash message, belum simpan ke tabel/kirim email. Halaman Karir sengaja tampil "belum ada lowongan" karena data pipeline lowongan asli baru Fase 3.)_
2. **Manajemen Karyawan & Struktur Organisasi** — CRUD karyawan, assign role, org-chart
3. **Rekrutmen (HRD)** — kelola lowongan (nyambung ke halaman Karir), form lamaran publik, pipeline pelamar, convert ke karyawan
4. **Absensi** — clock in/out, riwayat, rekap
5. **Izin/Cuti & Approval** — ke Manajer, fallback Owner
6. **MoM & Memo**
7. **Task & Project Tracker**
8. **KPI & Performance**
9. **Kontrak Kerja**
10. **Payroll**
11. **Project Budgeting & Royalty**
12. **Dashboard Access & CMS Landing Page** — Owner atur akses granular per modul + edit konten landing page tanpa sentuh kode
13. **Keamanan, Testing, Deployment** — staging/production terpisah, backup otomatis, monitoring

Detail lengkap tiap fase dan peta halaman per role ada di dokumen breakdown
project (dibagikan terpisah oleh tim, bukan bagian repo ini).

## Konvensi Kode

- `Auth::id()` Facade, bukan `auth()->id()` helper (kompatibilitas Intelephense)
- `asset('storage/...')`, bukan `Storage::disk('public')->url()`
- Role dicek lewat middleware `role:...` (`App\Http\Middleware\EnsureRole`), bukan Gate/Policy terpisah, untuk sekarang
- Satu tabel `users` untuk semua role internal (dibedakan kolom `role`), `manager_id` self-reference untuk alur approval
- Route dikelompokkan per role di `routes/web.php` — halaman baru masuk ke grup yang sesuai, jangan lepas di luar grup

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev   # atau: npm run build
```

Local dev pakai Laragon (Windows) + SQLite. Sebelum deploy ke cPanel,
jalankan `npm run build` lalu upload file yang berubah + folder
`public/build/` — jangan pernah sentuh database live secara langsung.
