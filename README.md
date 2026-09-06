# WSM Office System

Sistem internal untuk **Whisnu Santika Music (WSM)** — absensi, cuti/izin,
manajemen karyawan, project & KPI, kontrak kerja, payroll, hingga rekrutmen
(landing page publik + karir). Dibangun dengan Laravel + Tailwind CSS v4 +
Alpine.js, mengikuti konvensi stack yang sama dengan project lain (Mavnus,
Map of Feelings).

> Status saat ini: **Fase 0 (Fondasi) selesai**, **Fase 1 (Landing Page &
> Company Profile) selesai** — Beranda, Tentang Kami, Layanan, Karir,
> Kontak sudah ada route + view (konten masih hardcode, form kontak belum
> simpan ke DB). **Fase 2 (Manajemen Karyawan & Struktur Organisasi)
> selesai** — CRUD karyawan/manajer/HRD lengkap dengan soft delete
> ("nonaktifkan"/aktifkan lagi), assign atasan (`manager_id`), filter +
> search, dan halaman org-chart. Role `hrd` sudah resmi masuk enum
> `users.role` (dimajukan dari rencana awal Fase 3). **Fase 3 (Rekrutmen)
> selesai** — HRD & Owner kelola lowongan (`job_openings`, draft/
> published/closed, slug otomatis buat link publik), halaman Karir publik
> sekarang nampilin lowongan asli + halaman detail & form lamaran
> (`job_applications`, masih teks: nama/email/telepon/pesan — upload CV
> menyusul), panel Pelamar dengan pipeline status (Baru → Ditinjau →
> Interview → Ditawari → Diterima/Ditolak) + catatan internal, dan tombol
> "Terima & Buatkan Akun" buat convert pelamar diterima jadi akun
> karyawan asli. HRD sekarang landing ke `/rekrutmen/pelamar` setelah
> login (bukan `/app/home` lagi). **Fase 4 (Absensi) selesai** — absen
> masuk/pulang buat semua role internal (Karyawan/Manajer/HRD/Owner
> sama-sama absen), mode Kantor (radius dicek) atau WFH (radius
> di-skip), "Test Lokasi" + modal konfirmasi nampilin mini map (Leaflet +
> OpenStreetMap, tanpa API key) berisi jarak & radius sebelum absen
> beneran tersimpan, foto selfie opsional langsung dari kamera browser
> (dikompres di `<canvas>`, bukan upload galeri), riwayat absensi
> bulanan per karyawan, dan halaman rekap buat Manajer (bawahan turunan)
> /HRD/Owner (semua karyawan) lengkap dengan link Google Maps + thumbnail
> foto per absen. Titik lokasi kantor & aturan jam kerja ada di tabel
> singleton `office_settings` (diisi placeholder lewat
> `OfficeSettingSeeder` — **wajib diganti ke koordinat kantor asli**
> sebelum dipakai beneran). Koreksi/approval absen manual SENGAJA belum
> ada — nyambung ke Fase 5. **Fase 5 (Izin/Cuti & Approval) selesai** —
> karyawan ajukan Cuti Tahunan/Izin Sakit/Izin Pribadi/Lainnya (cuma
> tanggal hari ini & ke depan, gak bisa backdate), diputuskan atasan
> langsung (`manager_id`) lewat Manajer, dengan Owner bisa lihat &
> memutuskan SEMUA pengajuan kapan aja (bukan cuma fallback — beda
> sengaja dari scope rekap absensi Fase 4 yang pakai bawahan turunan,
> approval Fase 5 cuma bawahan LANGSUNG). HRD sengaja TIDAK ikut
> approval. Saldo cuti tahunan (`users.annual_leave_entitlement`)
> otomatis kepotong pas disetujui, dihitung hari kerja Senin-Jumat aja,
> tombol ajukan otomatis disable kalau sisa saldo gak cukup. Ditolak
> wajib alasan (karyawan boleh ajukan ulang), dibatalkan (oleh karyawan
> sendiri ATAU Manajer/Owner) juga wajib alasan — semua aksi berdampak
> (setuju/tolak/batalkan/koreksi) dipasangi konfirmasi `data-confirm`.
> Hari yang izin/cutinya disetujui bikin tombol absen ilang dari Home
> (dicek ganda di server, bukan cuma sembunyi UI). Koreksi absen manual
> yang ditunda dari Fase 4 juga masuk sini — Manajer/Owner cuma bisa
> edit JAM (bukan override status jadi izin/cuti manual), tersimpan jam
> asli sebelum dikoreksi + siapa/kapan yang koreksi, dan karyawan bisa
> lihat catatan itu di riwayatnya sendiri (transparan). Dashboard Owner
> kartu "Pengajuan Pending" gabungan izin/cuti pending + absen yang
> butuh perhatian (lupa checkout). Fase 6 ke atas (MoM & Memos, dst.)
> belum mulai.
>
> **Perbaikan pasca-Fase 4:** `config/app.php` timezone dibetulin dari
> default Laravel (`UTC`) ke `Asia/Jakarta` — sebelum ini semua jam
> (absen, `now()`, dsb.) kegeser 7 jam ke belakang dari WIB. **Kalau
> kamu sempat testing sebelum fix ini, hapus data absen yang kejadian pas
> masih `UTC`** (`php artisan migrate:fresh --seed` paling gampang) biar
> nggak campur data jam yang salah. Juga dibetulin warning Intelephense
> "Undefined method 'isHrd()'" di `RecapController` (cuma soal tipe data
> buat editor, bukan bug jalan/nggaknya kode).
>
> **Update tambahan (di luar nomor fase, cross-cutting):** semua alert,
> validasi, dan konfirmasi aksi destruktif (keluar akun, nonaktifkan/
> aktifkan karyawan, buatkan akun dari pelamar) sudah pindah dari
> `alert()`/`confirm()` bawaan browser ke SweetAlert2 lewat
> `resources/js/alerts.js` — lihat bagian
> [Alert & Konfirmasi (SweetAlert)](#alert--konfirmasi-sweetalert).
> Halaman error custom (`404`, `403`, `500`, `503`) juga sudah ada,
> termasuk `503` yang otomatis beda tampilan saat `php artisan down`
> (maintenance) vs gangguan layanan biasa — lihat
> [Halaman Error Custom](#halaman-error-custom).

## Tech Stack

- Laravel (latest) + PHP
- Tailwind CSS v4 (CSS-first config via `@theme` di `resources/css/app.css`, tanpa `tailwind.config.js`)
- Alpine.js untuk interaksi ringan (sidebar toggle, dsb.) + widget yang lebih kompleks (mis. absen — geolocation, map, kompresi foto)
- Leaflet + OpenStreetMap (tanpa API key) untuk mini map absen (Fase 4) — lihat `resources/js/attendance.js`
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
- Widget absen (Fase 4): `.mode-toggle-wsm` + `.mode-toggle-wsm-btn` (toggle Kantor/WFH), `.geo-status-wsm` (kartu status geo tag), `.wsm-map` (container mini map Leaflet)

Dua layout yang sudah disesuaikan:

- `resources/views/layouts/app.blade.php` — shell **Owner/Manajer**, sidebar
  paper dengan brand mark hitam, nav item aktif jadi pill hitam (meniru
  `.owner-sidebar` di prototype).
- `resources/views/layouts/employee.blade.php` — shell **Karyawan** gaya
  mobile app, hero besar + bottom nav pill melayang (meniru
  `.employee-shell` + `.bottom-nav` di prototype).

Halaman `auth/login.blade.php`, `employee/home.blade.php`,
`owner/dashboard.blade.php`, halaman-halaman Fase 2
(`owner/employees/*.blade.php`, `owner/organization/index.blade.php`,
komponen `components/org-node.blade.php` untuk render node org-chart
rekursif), halaman-halaman Fase 3 (`public/careers/*.blade.php`,
`recruitment/*.blade.php`), serta halaman-halaman Fase 4
(`employee/attendance/history.blade.php`, `attendance/recap/*.blade.php`),
serta halaman-halaman Fase 5 (`employee/leave/index.blade.php`,
`approval/leave/index.blade.php`)
sudah ikut disesuaikan (kartu, tombol, stat card warna).
Halaman lain yang belum dibuat (Fase 6 ke atas) tinggal pakai class-class
di atas supaya konsisten — jangan balik pakai `bg-white border
rounded-xl` polos lagi.

## Roadmap Modul & Role

Sistem punya bagian **publik** (landing page + karir, tanpa login) dan
**internal** (4 role dengan akun: Karyawan, Manajer, HRD, Owner). Awalnya
prototype `absensi_wsm`/W.O.S 2.0 cuma didesain untuk sistem internal;
scope sekarang diperluas jadi web publik + rekrutmen, jadi HRD wajib jadi
role beneran (bukan cuma wacana di dokumen breakdown).

Role di kolom `users.role` sudah 4 sejak Fase 2: `owner`, `manajer`,
`karyawan`, `hrd` (dimajukan dari rencana awal "nyusul di Fase 3" karena
ternyata langsung dibutuhkan begitu CRUD karyawan digarap). Manajer
sendiri adalah role eksplisit yang di-assign Owner (bukan status otomatis
dari org-chart) — org-chart tetap dipakai untuk menentukan siapa manajer
dari siapa (approval cuti/izin nanti).

Urutan fase development:

0. **Fondasi** — Laravel, Tailwind, Vite, login multi-role, middleware akses. ✅
1. **Landing Page & Company Profile** — Beranda, Tentang Kami, Layanan, Karir, Kontak (publik, tanpa login). ✅ _(route + view sudah ada; konten masih hardcode di Blade, bukan CMS — itu baru Fase 12. Form kontak baru flash message, belum simpan ke tabel/kirim email. Halaman Karir sengaja tampil "belum ada lowongan" karena data pipeline lowongan asli baru Fase 3.)_
2. **Manajemen Karyawan & Struktur Organisasi** — CRUD karyawan, assign role, org-chart. ✅ _(`Owner\EmployeeController` — index dengan filter role/search/nonaktif + pagination, create/edit/update, soft-delete lewat `destroy` yang dilabeli "nonaktifkan" di UI + `restore` untuk aktifkan lagi; bawahan otomatis dioper ke atasan-di-atasnya kalau manager-nya dinonaktifkan. `Owner\OrganizationController` bangun tree org-chart dari `manager_id` di memori (belum perlu CTE, jumlah karyawan masih kecil), di-render rekursif lewat komponen `components/org-node.blade.php`. Validasi lewat `StoreEmployeeRequest`/`UpdateEmployeeRequest`. `DemoSeeder` isi 1 Owner + 1 Manajer + 1 HRD + 2 Karyawan buat coba langsung. Belum ada: foto profil karyawan, riwayat perubahan role/atasan, halaman detail per karyawan.)_
3. **Rekrutmen (HRD)** — kelola lowongan (nyambung ke halaman Karir), form lamaran publik, pipeline pelamar, convert ke karyawan. ✅ _(`Recruitment\JobOpeningController` (resource, slug otomatis dari judul) + `Recruitment\JobApplicationController` (index/show/updateStatus/convert). Halaman Karir publik (`PageController::careers`/`careerShow`/`careerApply`) sekarang nampilin lowongan status "Tayang" beneran, bukan hardcode lagi. Pipeline status di `JobApplication::STATUSES`. HRD landing ke `/rekrutmen/pelamar` setelah login. Belum ada: upload CV, notifikasi email ke pelamar.)_
4. **Absensi** — clock in/out, riwayat, rekap. ✅ _(`Employee\AttendanceController` — `clockIn()`/`clockOut()` hitung ulang jarak dari kantor di server (`App\Support\Geo::distanceMeters`, Haversine) biar nggak percaya koordinat mentah dari browser, `history()` buat riwayat bulanan sendiri. `Attendance\RecapController` — rekap harian + detail bulanan per karyawan, scope Manajer dibatasi ke bawahan turunan (`scopedUsers()`, sama polanya dengan tree org-chart Fase 2), Owner/HRD lihat semua. Status (`Hadir`/`Terlambat`/`Kurang Jam Kerja`/`Sedang Bekerja`/`Lupa Absen Pulang`) dihitung on-the-fly di model `Attendance`, bukan kolom DB, biar nggak basi kalau `office_settings` diubah. Widget di `employee/home.blade.php` (Alpine component `attendanceWidget`, `resources/js/attendance.js`) urus geolocation, mini map Leaflet (marker kantor + user + lingkaran radius), kompresi foto selfie client-side, dan modal konfirmasi. Koreksi absen manual pindah & selesai di Fase 5. Belum ada: mode Lapangan/Event, halaman Settings buat Owner ubah lokasi/radius dari UI — nyusul Fase 12.)_
5. **Izin/Cuti & Approval** — ke Manajer, fallback Owner. ✅ _(`LeaveRequest` model — 4 jenis (`cuti_tahunan`/`izin_sakit`/`izin_pribadi`/`lainnya`), `countWorkDays()` hitung hari kerja Senin-Jumat, `approveBy()`/`rejectBy()`/`cancelBy()` sebagai state transition biar logic-nya gak keulang di 2 controller. `Employee\LeaveRequestController` — ajukan (cuma hari ini/ke depan, saldo cuti tahunan divalidasi server di `StoreLeaveRequestRequest`) + riwayat + batalkan sendiri. `Approval\LeaveRequestController` — **scope beda dari rekap absensi**: Manajer cuma bawahan LANGSUNG (`manager_id` persis dia, bukan turunan), Owner bisa lihat & putuskan siapa aja kapan aja (approver tercatat siapa yang beneran mutusin). HRD sengaja TIDAK dikasih akses approval. Ditolak wajib alasan (`decision_note`), dibatalkan wajib alasan (`cancellation_reason`, oleh karyawan sendiri ATAU Manajer/Owner) — dua-duanya lewat `CancelLeaveRequestRequest`/`RejectLeaveRequestRequest`. Hari yang izin/cutinya disetujui: tombol absen ilang dari Home (`LeaveRequest::approvedFor()` dicek di `HomeController` buat UI DAN di `AttendanceController::clockIn()` buat validasi server — bukan cuma sembunyi tombol doang), dan muncul badge "Cuti"/"Izin" (bukan "Belum Absen") di rekap `RecapController`. Koreksi absen manual (`RecapController::correct()`, `CorrectAttendanceRequest`) — cuma edit jam, nyimpen `original_clock_in_at`/`original_clock_out_at` (kesisi sekali di koreksi pertama) + siapa/kapan/alasan, dan karyawan bisa lihat catatan itu transparan di riwayatnya sendiri. Semua aksi berdampak (setuju/tolak/batalkan/koreksi) dipasangi `data-confirm`. Belum ada: notifikasi real-time/email (baru badge count, itupun masih ditunda), approval berjenjang (mis. HRD ikut approve cuti tahunan).)_
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

## Flow yang Sudah Berjalan (per Role)

Ringkasan yang bisa langsung dicoba klik-klik per role, bukan cuma daftar
fitur — biar gampang dites urut dari login sampai selesai.

**Publik** (tanpa login)

- ✅ Buka Beranda/Tentang Kami/Layanan, kirim pesan lewat Kontak (flash
  message aja, belum tersimpan ke tabel)
- ✅ Buka Karir → daftar lowongan status "Tayang" (data asli dari DB)
- ✅ Buka detail lowongan → kirim lamaran (nama, email, telepon, pesan)

**Karyawan** (Manajer/HRD/Owner juga jalanin flow ini buat absen diri
sendiri — satu tabel `users`, semua role internal absen dengan cara yang
sama)

- ✅ Login → Home nampilin status kehadiran hari ini
- ✅ Pilih mode Kantor/WFH + catatan opsional
- ✅ "Test Lokasi" → cek mini map & radius sebelum absen beneran
- ✅ Absen Masuk — modal konfirmasi (map + jarak + radius), foto selfie
  opsional dari kamera langsung
- ✅ Absen Pulang — pola konfirmasi sama
- ✅ Riwayat absensi bulanan sendiri (navigasi bulan), termasuk catatan
  transparan kalau ada absen yang dikoreksi Manajer/Owner (siapa, kapan,
  kenapa, jam aslinya berapa)
- ✅ Banner reminder kalau lupa absen pulang hari sebelumnya
- ✅ Menu Request → ajukan Cuti Tahunan/Izin Sakit/Izin Pribadi/Lainnya,
  lihat sisa saldo cuti tahunan, tombol ajukan otomatis disable kalau
  saldo kurang
- ✅ Batalkan pengajuan sendiri (pending atau yang udah disetujui) —
  wajib isi alasan
- ✅ Kalau hari ini lagi izin/cuti disetujui: tombol absen ilang, kartu
  Home ganti jadi info izin/cuti
- ❌ Koreksi absen sendiri kalau salah (harus lewat Manajer/Owner) — di
  luar scope, karyawan cuma bisa "lapor", bukan edit sendiri

**Manajer** (semua flow Karyawan di atas, ditambah)

- ✅ Menu Absensi di sidebar → rekap harian **bawahan turunan sendiri
  aja** (bukan seluruh perusahaan)
- ✅ Klik nama karyawan → riwayat bulanan (jam, radius, link Google
  Maps, foto selfie)
- ✅ Menu Persetujuan di sidebar → izin/cuti bawahan **LANGSUNG aja**
  (beda dari scope rekap absensi di atas yang bawahan turunan — ini
  kesepakatan khusus Fase 5)
- ✅ Setujui (satu klik + konfirmasi) atau Tolak (wajib alasan) pengajuan
  pending
- ✅ Batalkan izin/cuti bawahan yang udah disetujui — wajib alasan
- ✅ Koreksi jam absen bawahan dari halaman riwayat (edit jam masuk/
  pulang + catatan alasan wajib, karyawan bisa lihat catatannya)
- ❌ Approval berjenjang / ikut campur approval Owner

**HRD** (semua flow Karyawan di atas, ditambah)

- ✅ Landing langsung ke halaman Pelamar setelah login
- ✅ Kelola lowongan (buat/edit/hapus, draft/tayang/tutup)
- ✅ Kelola pipeline pelamar (ubah status, catatan internal, convert
  jadi akun karyawan)
- ✅ Menu Absensi → rekap **SEMUA karyawan** (beda dari Manajer yang
  cuma lihat timnya)
- ❌ Approval izin/cuti (sengaja gak dikasih — kesepakatan Fase 5: cuma
  Manajer & Owner), upload CV pelamar, notifikasi email

**Owner** (semua flow HRD & Manajer di atas, ditambah)

- ✅ Dashboard: kehadiran hari ini (X/Y karyawan), "Pengajuan Pending"
  gabungan izin/cuti pending + absen yang butuh perhatian (lupa
  checkout)
- ✅ CRUD karyawan penuh (tambah/edit/nonaktifkan/aktifkan lagi)
- ✅ Struktur organisasi (org-chart dari `manager_id`)
- ✅ Menu Absensi → rekap semua karyawan (sama seperti HRD) + koreksi
  jam absen siapa aja
- ✅ Menu Persetujuan → lihat & putuskan pengajuan **SIAPA AJA**, kapan
  aja — termasuk yang harusnya diurus Manajer (tercatat "disetujui oleh
  Owner" biar jelas siapa yang beneran mutusin)
- ❌ Atur lokasi kantor/radius/jam kerja dari UI (masih lewat seeder,
  UI-nya baru Fase 12)

## Alert & Konfirmasi (SweetAlert)

Semua notifikasi (flash message sukses/gagal, ringkasan error validasi)
dan semua konfirmasi sebelum aksi destruktif sekarang pakai
**SweetAlert2**, dipusatkan di `resources/js/alerts.js` (di-_import_ dari
`resources/js/app.js`, jalan otomatis di semua halaman yang me-load
`@vite(['resources/js/app.js'])`).

**Flash & validasi otomatis** — cukup taruh
`@include('partials.flash-data')` sekali di layout (sudah ada di
`layouts.app`, `layouts.employee`, `layouts.public`, dan
`auth/login.blade.php`). Partial itu nulis `session('status')`,
`session('error')`, `session('warning')`, dan `$errors->all()` jadi JSON;
`alerts.js` yang baca lalu tampilkan:

- `session('status')` → toast hijau
- `session('error')` / `session('warning')` → toast merah/kuning
- 1 pesan validasi → toast merah
- 2+ pesan validasi → popup checklist "Ada isian yang belum sesuai"

Kalau bikin controller/halaman baru yang pakai layout di atas, flash
message otomatis kepakai — tidak perlu nulis blade banner manual lagi.

**Konfirmasi sebelum submit** — tinggal tambah atribut `data-confirm` di
`<form>`, tidak perlu JS tambahan:

```blade
<form method="POST" action="{{ route('owner.employees.destroy', $employee) }}"
    data-confirm="{{ $employee->name }} tidak akan bisa login lagi, tapi riwayat datanya tetap tersimpan."
    data-confirm-title="Nonaktifkan {{ $employee->name }}?"
    data-confirm-button="Ya, nonaktifkan" data-confirm-danger="1">
    @csrf
    @method('DELETE')
    <button type="submit">Nonaktifkan</button>
</form>
```

Atribut yang tersedia: `data-confirm` (teks isi), `data-confirm-title`,
`data-confirm-button`, `data-cancel-button`, `data-confirm-danger="1"`
(tombol konfirmasi jadi merah, dipakai untuk aksi yang sifatnya
menghapus/menonaktifkan). Sudah dipakai di: form keluar akun
(`layouts.app`, `layouts.employee`) dan form nonaktifkan/aktifkan
karyawan (`owner/employees/index.blade.php`).

Untuk manggil dari JS langsung (mis. dalam Alpine `@click`), ada
`window.WsmAlert` dengan method `success()`, `error()`, `warning()`,
`validationSummary(messages)`, dan `confirm({ title, text, ... })`
(return Promise, lihat isi `alerts.js` untuk detail).

## Halaman Error Custom

Ada di `resources/views/errors/` (`404`, `403`, `500`, `503`) + layout
mandiri `resources/views/layouts/error.blade.php` (tidak `extends
layouts.app`, karena error bisa kejadian sebelum ada user login). Gaya
visualnya sudah disamakan ke brand WSM (lihat token desain di atas).

Catatan penting soal kapan halaman ini benar-benar muncul:

- `404`, `403`, `503` → langsung kepakai kapan saja, termasuk saat
  `APP_DEBUG=true` (dev lokal). Untuk coba `403`, akses halaman yang
  butuh role lain dari akun yang lagi login; untuk `404`, akses URL
  ngasal; untuk `503`, jalankan `php artisan down`.
- `500` (generic `Throwable`, bukan `abort(500)`) → **hanya** muncul
  kalau `APP_DEBUG=false` di `.env`. Selama dev lokal (`APP_DEBUG=true`)
  Laravel selalu nunjukin halaman Ignition/Whoops yang detail, itu
  perilaku bawaan framework, bukan berarti custom view-nya salah/tidak
  kepasang. Kalau mau coba tampilannya di lokal: set sementara
  `APP_DEBUG=false` lalu picu error apa saja, atau panggil
  `abort(500)` di satu route buat tes.
- `503` otomatis beda konten: `resources/views/errors/503.blade.php`
  cek `app()->isDownForMaintenance()` — kalau `true` (lagi
  `php artisan down`) tampil "Sedang Maintenance" + auto-reload tiap 30
  detik; kalau bukan (503 dari sumber lain) tampil "Layanan Tidak
  Tersedia" generik.

- `Auth::id()` Facade, bukan `auth()->id()` helper (kompatibilitas Intelephense)
- `asset('storage/...')`, bukan `Storage::disk('public')->url()`
- Role dicek lewat middleware `role:...` (`App\Http\Middleware\EnsureRole`), bukan Gate/Policy terpisah, untuk sekarang
- Satu tabel `users` untuk semua role internal (dibedakan kolom `role`), `manager_id` self-reference untuk alur approval
- Route dikelompokkan per role di `routes/web.php` — halaman baru masuk ke grup yang sesuai, jangan lepas di luar grup
- Notifikasi & konfirmasi pakai SweetAlert (`resources/js/alerts.js`), **jangan** balik pakai `alert()`/`confirm()` bawaan browser — lihat [Alert & Konfirmasi (SweetAlert)](#alert--konfirmasi-sweetalert)
- Input tanggal yang secara logis tidak boleh di masa depan (mis. `birth_date`) dikasih atribut `max` di sisi HTML selain validasi server (`before:today` dsb.) — biar salah ketik ketauan sebelum submit, bukan cuma setelah
- Model yang punya halaman publik dengan URL berbasis slug (mis. `JobOpening`) override `getRouteKeyName()` jadi `'slug'` — jangan cuma taruh `{param:slug}` di routes/web.php doang, soalnya `route()` helper generate URL pakai `getRouteKeyName()` model, bukan suffix binding di route
- Status yang bisa dihitung ulang dari data lain (mis. status kehadiran di `Attendance`) sengaja TIDAK disimpan sebagai kolom DB — dihitung lewat accessor di model, biar nggak ada data basi kalau aturan/pengaturan berubah belakangan
- Data sensitif yang dikirim dari browser (koordinat GPS, dsb.) selalu dihitung ulang/divalidasi di server (`App\Support\Geo`), jangan percaya begitu saja angka yang dikirim JS — bisa dimanipulasi user
- Kompresi gambar (mis. foto selfie absen) dilakukan di browser lewat `<canvas>`, bukan library PHP (Intervention/GD) di server — sesuai batasan hosting cPanel tanpa terminal, hindari nambah dependency yang butuh extension khusus kalau bisa dihindari
- **Scope "bawahan Manajer" BEDA-BEDA per modul, ini sengaja bukan bug**: rekap absensi (`Attendance\RecapController::scopedUsers()`) pakai bawahan TURUNAN (rekursif, ikut cucu-cicit di org-chart), sedangkan approval izin/cuti (`Approval\LeaveRequestController::canDecide()`) pakai bawahan LANGSUNG doang (`manager_id` persis Manajer tsb). Dua keputusan beda yang diambil terpisah pas breakdown fase — kalau nambah modul baru yang ada konsep "scope Manajer", jangan asumsikan otomatis sama, konfirmasi dulu mana yang dimaksud
- State transition (approve/reject/cancel di `LeaveRequest`, dst.) ditaruh sebagai method di model (`approveBy()`, `rejectBy()`, `cancelBy()`), bukan logic mentah di controller — biar gak keulang nulis hal yang sama pas dipanggil dari 2 controller berbeda (Employee & Approval)

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=OfficeSettingSeeder   # WAJIB — isi office_settings (lokasi kantor, radius, jam kerja)
php artisan db:seed --class=DemoSeeder   # opsional: isi 1 Owner, 1 Manajer, 1 HRD, 2 Karyawan buat testing
php artisan storage:link   # wajib buat Fase 4 — foto selfie absen disimpan di storage/app/public
npm install
npm run dev   # atau: npm run build
```

> ⚠️ **Sebelum absen bisa dipakai beneran**, buka
> `database/seeders/OfficeSettingSeeder.php` dan ganti `latitude`,
> `longitude`, `address`, `office_name` sesuai lokasi kantor WSM yang
> sebenarnya (nilai sekarang masih placeholder titik Monas), lalu
> jalankan ulang `php artisan db:seed --class=OfficeSettingSeeder`.
> `radius_meters`/`work_start_time`/`late_tolerance_minutes` juga bisa
> disesuaikan di file yang sama.

Akun demo dari `DemoSeeder` (password semua `password`, **ganti sebelum
pakai beneran**):

| Role     | Email              | Nama           |
| -------- | ------------------ | -------------- |
| Owner    | `owner@wsm.local`  | Whisnu Santika |
| Manajer  | `kanaya@wsm.local` | Kanaya         |
| HRD      | `rania@wsm.local`  | Rania          |
| Karyawan | `aldora@wsm.local` | Aldora         |
| Karyawan | `gepeng@wsm.local` | Gepeng         |

Local dev pakai Laragon (Windows) + SQLite. Sebelum deploy ke cPanel,
jalankan `npm run build` lalu upload file yang berubah + folder
`public/build/` — jangan pernah sentuh database live secara langsung.

## Langkah Selanjutnya

Fase 0–5 sudah selesai di sisi kode. **Fase 4 udah dikonfirmasi jalan
sempurna di environment asli** (Laragon, sudah di-commit). **Fase 5
belum sempat dites end-to-end** (sandbox yang dipakai nulis kode ini
cuma punya PHP 8.3, project butuh 8.4 — jadi `php artisan migrate`
beneran + klik-klik alurnya perlu dicoba sendiri dulu). Checklist:

1. **Migrate dari nol**: `php artisan migrate:fresh --seed` (ada 2
   migration baru: `leave_requests` + kolom koreksi di `attendances`).
   `php artisan storage:link` kalau session Laragon-nya baru/beda dari
   sebelumnya.
2. **Uji alur pengajuan sebagai Karyawan**:
    - Buka menu Request → coba pilih "Cuti Tahunan", isi tanggal
      melebihi sisa saldo → tombol "Kirim Pengajuan" harus otomatis
      disable, muncul teks "Melebihi sisa cuti tahunan kamu".
    - Ajukan yang valid (dalam kuota) → submit → cek muncul di riwayat
      dengan status "Pending".
    - Coba tombol "Batalkan pengajuan" → isi alasan → cek status
      berubah jadi "Dibatalkan" + alasan kelihatan.
3. **Login sebagai Manajer** (yang punya bawahan langsung) → menu
   Persetujuan:
    - Pastikan CUMA muncul pengajuan bawahan LANGSUNG (test: pengajuan
      dari karyawan yang manajernya BUKAN kamu harusnya nggak
      kelihatan).
    - Klik "Setujui" → konfirmasi muncul → cek status berubah,
      `approver_id` keisi nama Manajer tsb.
    - Coba "Tolak" pengajuan lain → alasan wajib diisi (coba submit
      kosong, harus ke-reject validasi) → cek karyawan yang bersangkutan
      bisa lihat alasan itu di riwayatnya.
4. **Login sebagai Owner** → menu Persetujuan harus nampilin SEMUA
   pengajuan (termasuk punya karyawan yang Manajer-nya orang lain) →
   coba approve salah satu → cek di riwayat karyawan tulisannya
   "Disetujui oleh [nama Owner]", bukan nama manajer aslinya.
5. **Cek saldo cuti kepotong bener**: setelah approve 1 pengajuan Cuti
   Tahunan senilai N hari kerja, buka lagi menu Request karyawan
   tsb → "Sisa Cuti Tahunan" harus berkurang N dari jatah awal
   (`annual_leave_entitlement`, default 12).
6. **Cek blokir absen pas cuti**: approve satu pengajuan buat tanggal
   HARI INI → login sebagai karyawan itu → buka Home → tombol absen
   harus ilang, ganti jadi kartu "Kamu sedang [jenis] hari ini". Coba
   juga akses langsung endpoint clock-in (kalau bisa via Postman/curl)
   buat mastiin server nolak juga, bukan cuma UI yang disembunyiin.
7. **Uji koreksi absen**: dari halaman riwayat karyawan (`Absensi →
Riwayat →`), klik "Koreksi jam absen" di salah satu baris → ubah jam
   → alasan wajib diisi → simpan → cek baris di riwayat KARYAWAN (bukan
   cuma sisi Manajer/Owner) muncul catatan "Dikoreksi oleh..." beserta
   jam aslinya.
8. **Cek rekap harian**: buka `attendance.recap.index` di tanggal
   karyawan yang lagi cuti → badge-nya harus nunjukin jenis cuti/izin
   (bukan "Belum Absen"), dan stat card "Izin/Cuti" ikut kehitung.
9. Kalau semua di atas beres, lanjut ke **Fase 6: MoM & Memo** — catatan
   rapat dan pengumuman internal dari Owner/Manajer ke tim.
