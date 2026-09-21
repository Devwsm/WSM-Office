# WSM-Office (W.O.S 2.0) — Laravel

Sistem manajemen kantor internal Whisnu Santika Music (WSM), hasil implementasi dari prototype `WOS_2_0_App_v32` (HTML/JS satu file) ke aplikasi Laravel multi-halaman yang akan di-deploy dan diakses publik.

> **Audit dilakukan 2026-09-19** terhadap `WSM-Office.zip` (snapshot 2026-09-19) dan `WOS_2_0_App_v32.zip`. Metode: baca kode + tes langsung (lihat bagian _Yang diuji_ di Bab 2). Semua klaim di dokumen ini berasal dari kode atau hasil tes, bukan asumsi.
>
> **Pembaruan 2026-09-19 (batch 1):** blocker deploy #5 _File sensitif terbuka tanpa login_ sudah **selesai dan teruji** — lihat Bab 4.1.
>
> **Pembaruan 2026-09-21:** panduan halaman dashboard (Bab 2.5), perbaikan form koreksi absen (Bab 2.6), serta keputusan hosting (PHP 8.3, struktur folder cPanel) dan daftar fitur yang dikerjakan berikutnya (Bab 4).

**Legenda status:** ✅ ada & sesuai · ⚠️ ada tapi tidak sesuai / lebih sederhana · ❌ belum ada · ➕ tambahan (tidak ada di prototype)

---

## 1. Penjelasan Project

**WSM-Office** adalah aplikasi web internal untuk mengelola operasional kantor WSM: absensi, pengajuan izin/cuti/lembur, pelacakan pekerjaan, rapat, memo, KPI, kontrak, payroll, budget, royalti, legal, audit, dan rekrutmen. Selain area internal, aplikasi punya 5 halaman publik (beranda, tentang kami, layanan, karir, kontak) dan form lamaran kerja.

**Stack:** Laravel 13.30 · PHP 8.3 (batas cPanel Rumahweb; `vendor/` saat ini masih mewajibkan 8.4.1, lihat 4.1 no. 1) · MySQL · Tailwind CSS v4 + Vite · Alpine.js · Leaflet (peta geofence absensi) · SweetAlert2 · Bootstrap Icons · `maatwebsite/excel` 4.0 (export/import) · `barryvdh/laravel-dompdf` 3.1 (PDF).

**Hosting target:** shared cPanel (Rumahweb), tanpa terminal. Konsekuensinya: tidak ada cron (rekonsiliasi absensi ikut trafik web lewat `AttendanceReconciler`), tidak bisa `artisan` di server, dan file build (`public/build`) harus di-upload manual. Struktur folder di server: `public_html` hanya berisi isi folder `public/`, sisa project berada di luar, sejajar dengan `public_html` (Bab 4.0).

**Konvensi kode:** primary key `id_<tabel>` tidak dipakai di snapshot ini (memakai `id` standar); validasi lewat kelas `FormRequest` (31 kelas di `app/Http/Requests`); akses berbasis **role** (`owner`, `manajer`, `hrd`, `karyawan`) untuk area `/owner` dan `/app`, dan akses berbasis **modul** (`dashboard_access`, level `view`/`manage`) untuk area `/dashboard`, rekap, approval, dan rekrutmen; `throttle` pada route tulis; audit log untuk aksi sensitif (payroll dll).

**Ukuran:** 153 route (71 GET, 82 tulis), 180 file PHP, 40 migrasi, 25 model, 104 view Blade, 3 seeder.

**Peran & akses ringkas**

| Peran                                | Area utama                                                                                                                                                                                                                                                |
| ------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Publik / tamu                        | Beranda, Tentang, Layanan, Karir + lamar, Kontak, Login                                                                                                                                                                                                   |
| Semua akun internal                  | `/app/*` — absen, riwayat, pengajuan, lembur, koreksi, kalender tim, profil, Info dari Owner                                                                                                                                                              |
| Owner                                | Semuanya: `/owner/*` (karyawan, organisasi, akses dashboard, pengaturan kantor, pesan kontak) + semua modul dashboard + boleh memutus semua approval                                                                                                      |
| Manajer                              | Approval bawahan langsung + modul yang diberikan Owner                                                                                                                                                                                                    |
| HRD                                  | Rekap absensi, rekrutmen, dan modul yang diberikan Owner (sengaja tidak ikut approve izin/cuti)                                                                                                                                                           |
| Karyawan                             | `/app/*` + modul yang diberikan Owner (mis. Work Tracker `view`/`manage`)                                                                                                                                                                                 |
| Developer _(rencana, Bab 4.2 no. 1)_ | Manage 10 modul dashboard, lihat absensi semua orang, reset password non-Owner, plus halaman Karyawan, Struktur Organisasi, Pengaturan Kantor, Pesan Kontak, Dashboard Owner. **Tidak bisa** menyentuh akun Owner dan tidak bisa membuka Dashboard Access |

**Role vs permission (sering membingungkan)**

|                    | Role                                                                                                                                    | Permission (akses modul)                                                       |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Apa itu            | Label jabatan di akun: `owner`, `manajer`, `hrd`, `karyawan` (`developer` menyusul)                                                     | Per modul: `none` / `view` / `manage` (tabel `dashboard_access`)               |
| Yang ditentukannya | Area `/owner`; Owner punya akses penuh; Owner dan HRD melihat semua orang di Rekap Absensi (developer menyusul). Selebihnya hanya label | Menu dashboard yang muncul, dan boleh ubah data (`manage`) atau tidak (`view`) |
| Diatur di          | Karyawan → Edit → Role                                                                                                                  | Karyawan → Akses (hanya Owner)                                                 |

**Persetujuan izin/cuti/lembur/koreksi bukan soal role maupun permission:** yang berhak adalah **atasan langsung** karyawan itu (field "Atasan Langsung"), dan Owner boleh memutus semuanya. Cakupan Rekap Absensi untuk manajer adalah dirinya dan seluruh bawahan turunannya.

Akses modul bersifat **data**, bukan hard-code: pada data demo, Manajer Kanaya tidak diberi modul `work` sehingga 403 di Work Tracker — itu perilaku benar, bukan bug.

---

## 2. Perbandingan Project vs Prototype

### 2.1 Halaman & fitur

| #                                                                 | Halaman / fitur                                                                                          | Prototype v32                                                                 | Project Laravel                                                                                                                                       | Status                                                       | Siapa yang bisa akses                 |
| ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ | ------------------------------------- |
| **Auth & sesi**                                                   |                                                                                                          |                                                                               |                                                                                                                                                       |                                                              |                                       |
| 1                                                                 | Login                                                                                                    | Pilih nama + password                                                         | Email + password, 1 form semua role, throttle (429 teruji)                                                                                            | ✅ (beda disengaja)                                          | Tamu                                  |
| 2                                                                 | Lock Dashboard                                                                                           | `sessionStorage` (client)                                                     | Session server-side, per user                                                                                                                         | ✅ (lebih aman)                                              | Semua di area dashboard               |
| 3                                                                 | Ganti password                                                                                           | Ada                                                                           | Di halaman Profil                                                                                                                                     | ✅                                                           | Semua akun                            |
| 4                                                                 | Lupa/reset password                                                                                      | —                                                                             | Belum ada. **Akan dibuat di dashboard IT** (Bab 4.2 no. 1); reset mandiri lewat email tetap ditunda (tunggu email asli)                               | ❌ → dikerjakan                                              | Modul `it` (rencana)                  |
| **Aplikasi karyawan (`/app`)**                                    |                                                                                                          |                                                                               |                                                                                                                                                       |                                                              |                                       |
| 5                                                                 | Home (kartu absensi, KPI, milestone, sisa cuti, Team Moments, My Work Tracker, Info dari Owner)          | Ada                                                                           | Ada semua                                                                                                                                             | ✅                                                           | Semua akun                            |
| 6                                                                 | Absen masuk/pulang: kantor, WFH, lapangan, gigs (multi-sesi)                                             | Ada                                                                           | Ada; geofence dihitung ulang di server (Haversine), selfie (disimpan private), auto-close sesi lupa pulang                                            | ✅                                                           | Semua akun                            |
| 7                                                                 | Riwayat absensi                                                                                          | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Semua akun                            |
| 8                                                                 | Pengajuan izin/cuti + batal                                                                              | Ada                                                                           | Ada; persetujuan atasan langsung                                                                                                                      | ✅                                                           | Semua akun                            |
| 9                                                                 | Lembur                                                                                                   | Ada                                                                           | Ada; tarif flat per pengajuan disetujui                                                                                                               | ✅                                                           | Semua akun                            |
| 10                                                                | Koreksi presensi                                                                                         | Ada                                                                           | Ada + halaman approval                                                                                                                                | ✅                                                           | Semua akun / Manajer & Owner memutus  |
| 11                                                                | Kalender tim                                                                                             | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Semua akun                            |
| 12                                                                | Memo: baca, sembunyikan, balas thread                                                                    | Ada                                                                           | Ada (tabel `memo_reads`, `memo_thread_messages`)                                                                                                      | ✅                                                           | Sesuai audiens memo                   |
| 13                                                                | Profil                                                                                                   | Ada + foto profil                                                             | Tanpa foto profil                                                                                                                                     | ⚠️ (foto ditunda)                                            | Semua akun                            |
| 14                                                                | Tema per user                                                                                            | Ada                                                                           | Belum                                                                                                                                                 | ❌ (ditunda)                                                 | —                                     |
| **Area Owner (`/owner`)**                                         |                                                                                                          |                                                                               |                                                                                                                                                       |                                                              |                                       |
| 15                                                                | Dashboard Owner                                                                                          | Ringkasan + ritme mingguan bisa diedit                                        | Ringkasan ada; **ritme mingguan hard-code** di controller                                                                                             | ⚠️ → dikerjakan (Bab 4.2 no. 3)                              | Owner                                 |
| 16                                                                | Kelola karyawan (CRUD, role, atasan, gaji, soft delete)                                                  | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Owner                                 |
| 17                                                                | Organization (org-chart)                                                                                 | Ada                                                                           | Ada (dari `manager_id`)                                                                                                                               | ✅                                                           | Owner                                 |
| 18                                                                | Akses dashboard per modul (view/manage)                                                                  | Ada                                                                           | Ada, 10 modul                                                                                                                                         | ✅                                                           | Owner                                 |
| 19                                                                | Pengaturan kantor (geo, jam, warna)                                                                      | Jam, break, toleransi, blok potongan, jam lembur, auto-close, geo, warna      | Geo, jam kerja, tarif potongan, warna. **Tidak ada:** blok potongan (fix 60 mnt), jam mulai lembur, toggle auto-close                                 | ⚠️ → dikerjakan (Bab 4.2 no. 2)                              | Owner                                 |
| 20                                                                | Executive People Overview                                                                                | Ada                                                                           | Sengaja di-skip sejak awal: belum ada padanan halaman yang jelas di app ini (sebagian ringkasan people sudah ada di Dashboard Owner)                  | ❌ (disengaja)                                               | —                                     |
| 21                                                                | Pesan kontak (dari form publik)                                                                          | —                                                                             | Ada                                                                                                                                                   | ➕                                                           | Owner                                 |
| **Modul dashboard (`/dashboard`, dijaga `module:x,view/manage`)** |                                                                                                          |                                                                               |                                                                                                                                                       |                                                              |                                       |
| 22                                                                | Work Tracker (board, item, PIC, progres, link)                                                           | Ada                                                                           | Ada + kanban + kalender                                                                                                                               | ✅                                                           | Modul `work`                          |
| 23                                                                | Import Work Tracker CSV/XLSX                                                                             | Ada (v32)                                                                     | Ada, dengan pratinjau sebelum konfirmasi                                                                                                              | ✅                                                           | Modul `work` (manage)                 |
| 24                                                                | Projects                                                                                                 | Ada                                                                           | Ada, digabung ke Work Tracker (warna per project)                                                                                                     | ✅                                                           | Modul `work`                          |
| 25                                                                | Timeline Calendar                                                                                        | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Modul `work`                          |
| 26                                                                | MoM / Meeting + action item                                                                              | Ada                                                                           | Ada, action item terhubung ke Work Item, cetak PDF                                                                                                    | ✅                                                           | Modul `work`                          |
| 27                                                                | Memo Forum (admin)                                                                                       | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Modul `work`                          |
| 28                                                                | Assign / Reminder dari dashboard                                                                         | Ada (detail persisnya belum terdokumentasi; file prototype tidak ada di repo) | Belum ada. Di kode baru ada kolom `is_reminder` pada task dan section "REMINDER / ADMIN"; belum ada aksi khusus di dashboard                          | ❌ (perlu dijelaskan dulu, Bab 4.2 bagian Belum dijadwalkan) | —                                     |
| 29                                                                | Rekap absensi (+ detail per orang, koreksi manual)                                                       | Ada                                                                           | Ada + export Excel/PDF                                                                                                                                | ✅                                                           | Modul `people`                        |
| 30                                                                | Approval izin/cuti, lembur, koreksi presensi                                                             | Ada + Management Override                                                     | Ada; Owner boleh memutus siapa saja (= override); HRD tidak ikut                                                                                      | ✅                                                           | Manajer (bawahan), Owner              |
| 31                                                                | KPI & Performance                                                                                        | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Modul `kpi`                           |
| 32                                                                | Employee Contracts                                                                                       | Ada (file, tanggal, catatan)                                                  | Ada; file di disk private, dibuka lewat route berotorisasi                                                                                            | ✅                                                           | Modul `contracts`                     |
| 33                                                                | Payroll                                                                                                  | Estimasi on-the-fly: hari absen × gaji÷22, kurang jam × tarif/jam, THP min 0  | Disimpan per bulan (draft → final → paid). **Tidak memotong hari tanpa absensi**, potongan = blok 60 mnt × tarif flat, total tidak dibatasi minimal 0 | ⚠️ → dikerjakan (Bab 4.2 no. 4)                              | Modul `payroll`                       |
| 34                                                                | Slip payroll PDF                                                                                         | Ada                                                                           | Ada                                                                                                                                                   | ✅                                                           | Modul `payroll`                       |
| 35                                                                | Project Budgeting                                                                                        | Budget vs actual per project                                                  | CRUD flat per baris                                                                                                                                   | ⚠️ (lebih sederhana) → dikerjakan (Bab 4.2 no. 5)            | Modul `budget`                        |
| 36                                                                | Royalty Dashboard                                                                                        | Waterfall recoupment per lagu, ledger kuartal, sinkron Google Sheet           | CRUD flat: gross, share %, recoup, status bayar. Tanpa waterfall/ledger/sinkron                                                                       | ⚠️ (jauh lebih sederhana) → dikerjakan (Bab 4.2 no. 6)       | Modul `royalty`                       |
| 37                                                                | Legal: Album Contracts & Royalty Agreements                                                              | Ada                                                                           | Ada (1 tabel, dibedakan `category`)                                                                                                                   | ✅                                                           | Modul `legal`                         |
| 38                                                                | IT: Audit Log                                                                                            | Ada                                                                           | Ada (read-only)                                                                                                                                       | ✅                                                           | Modul `it`                            |
| 39                                                                | IT: System Change Log                                                                                    | Ada                                                                           | Ada (CRUD)                                                                                                                                            | ✅                                                           | Modul `it`                            |
| 40                                                                | Team Overview manajer                                                                                    | —                                                                             | Grup route kosong (TODO)                                                                                                                              | ❌                                                           | —                                     |
| 41                                                                | Team Groups, editor landing page                                                                         | Ada                                                                           | Belum ada                                                                                                                                             | ❌ (ditunda)                                                 | —                                     |
| **Tambahan (tidak ada di prototype)**                             |                                                                                                          |                                                                               |                                                                                                                                                       |                                                              |                                       |
| 42                                                                | Rekrutmen: lowongan + pipeline pelamar + konversi jadi karyawan                                          | —                                                                             | Ada                                                                                                                                                   | ➕                                                           | Modul `recruitment` (HRD, Owner)      |
| 43                                                                | Export & Import Center (14 export: Excel 11 + PDF 3; 4 import: Work Tracker, Karyawan, KPI, Budget)      | Hanya import Work Tracker                                                     | Ada, berbasis katalog & gerbang modul                                                                                                                 | ➕                                                           | Sesuai modul masing-masing            |
| 44                                                                | Halaman publik (Beranda, Tentang, Layanan, Kontak)                                                       | Ada editor landing                                                            | Ada, tetapi **teks masih placeholder**                                                                                                                | ⚠️                                                           | Publik                                |
| 45                                                                | Karir publik + form lamaran                                                                              | —                                                                             | Ada; **belum ada upload CV & link portofolio**                                                                                                        | ⚠️ → Bab 4.2 no. 7                                           | Publik                                |
| 46                                                                | Form kontak publik                                                                                       | —                                                                             | Tersimpan ke DB (`contact_messages`), throttle                                                                                                        | ✅                                                           | Publik                                |
| 47                                                                | Panduan halaman dashboard: tombol "? Panduan" + modal informasi di setiap halaman dashboard (44 panduan) | —                                                                             | Ada; teks di `config/page_guides.php`, tombol muncul otomatis lewat layout                                                                            | ➕                                                           | Mengikuti akses halaman masing-masing |

**Hitungan status (47 baris):** ✅ 29 · ⚠️ 8 · ❌ 6 · ➕ 4 (pesan kontak, rekrutmen, Export & Import, panduan halaman).

**Estimasi kesesuaian dengan prototype** (kasar, berdasarkan bobot fitur): fitur harian inti (absensi, pengajuan, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) **±90%**; modul finansial (payroll, budget, royalty) **±45–55%**; halaman publik **belum siap tayang** karena konten placeholder.

### 2.2 Yang diuji (tes langsung pada 2026-09-19)

| Tes                                                                                                                         | Hasil                                                                                                                                                                                                                 |
| --------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `php -l` seluruh 180 file PHP                                                                                               | ✅ 0 error sintaks                                                                                                                                                                                                    |
| `migrate:fresh --seed` di MariaDB 10.11 (40 migrasi + 3 seeder)                                                             | ✅ sukses                                                                                                                                                                                                             |
| Smoke test 60 URL GET × 6 sudut pandang (tamu, Owner, Manajer, Karyawan work-manage, Karyawan work-view, HRD) = 360 request | ✅ 0 error 500; tamu diarahkan ke `/login` di semua halaman terproteksi; 403/200 mengikuti matriks akses                                                                                                              |
| Login benar / salah / brute-force                                                                                           | ✅ redirect sesuai role; 429 setelah beberapa percobaan                                                                                                                                                               |
| Form kontak publik                                                                                                          | ✅ tersimpan ke `contact_messages`                                                                                                                                                                                    |
| Form lamaran kerja dengan `cv` + `portfolio_url`                                                                            | ⚠️ lamaran tersimpan, **CV dan link portofolio diabaikan** (kolom tidak ada di tabel)                                                                                                                                 |
| Generate payroll untuk karyawan tanpa satu pun absensi di bulan itu                                                         | ⚠️ potongan = 0, gaji penuh Rp 6.500.000 dibayar. **Akan diubah lewat Payroll (Bab 4.2 no. 4); baris ini diperbarui setelah dikerjakan**                                                                              |
| **Batch 1** — `PrivateFileAccessTest` (20 tes, 77 assertion, termasuk 2 stub bawaan) di SQLite `:memory:` dan MariaDB       | ✅ lulus: tamu → `/login`; tanpa modul → 403; scope selfie mengikuti rekap; file tersimpan hanya di disk private; hapus membersihkan salinan lama; path traversal ditolak; halaman tidak lagi memuat link `/storage/` |
| **Batch 1** — uji mutasi (sengaja merusak: gerbang modul dicabut, scope selfie dicabut, view kembali ke `/storage/`)        | ✅ tiap kerusakan ditangkap tepat 1 tes; kode dipulihkan, 18/18 lulus lagi                                                                                                                                            |
| **Batch 1** — Intelephense (language server, semua severity) pada 10 file PHP yang diubah/ditambah                          | ✅ 0 diagnostik (sebelumnya 12 `P1013` di `PrivateFile.php` dan tes; sudah diperbaiki dengan tipe `FilesystemAdapter`)                                                                                                |
| **Batch 1** — smoke test ulang 60 URL × 6 peran setelah perubahan                                                           | ✅ 0 error 500, hasil identik dengan sebelum perubahan                                                                                                                                                                |

**Belum diuji otomatis:** tampilan/UI di browser (layout, modal Alpine, drag-and-drop board, responsif), izin geolocation & kamera di HP, file picker browser sungguhan (yang diuji: upload tiruan), tampilan visual file Excel/PDF (isinya sudah dibaca ulang di tes), pengiriman email, dan MySQL asli (diganti MariaDB 10.11). Sejak 2026-09-20 seluruh checklist manual A–K yang berupa input/edit/hapus sudah otomatis (lihat 2.3); yang tersisa untuk dicek manual sebelum go-live hanya hal-hal di atas.

### 2.3 Tes otomatis (Batch 2 — 2026-09-20)

Menggantikan checklist tes manual di browser (bagian A–K pada README versi commit `634b65d`). Kode seperti `C5` atau `H4` di komentar tiap file tes mengacu ke nomor butir checklist itu.

**Menjalankan:** `php artisan test` (SQLite `:memory:`, tanpa setup apa pun; `public/hot` dan `public/build` tidak perlu ada). Untuk MySQL/MariaDB: buat database kosong khusus tes lalu `DB_CONNECTION=mysql DB_DATABASE=wsm_test DB_USERNAME=... DB_PASSWORD=... php artisan test` — jangan arahkan ke database aplikasi, `RefreshDatabase` menghapus isinya.

**Hasil:** 301 tes = **296 lulus + 5 dilewati (skip) yang sengaja mendokumentasikan celah yang belum diperbaiki**, 2.409 assertion, ±16 detik (SQLite) / ±18 detik (MariaDB). Hasil identik di SQLite dan MariaDB 10.11 (PHP 8.4.25). _Batch 3 (Bab 2.5) menambah `PageGuideTest` dan Batch 4 (Bab 2.6) 2 tes di `AttendanceRecapTest`: total kini 313 tes._

| File tes                | Tes | Mencakup (butir checklist)                                                                                                                                                   |
| ----------------------- | --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PublicPagesTest`       | 13  | A1–A7: halaman publik, karir, form lamar, form kontak, throttle 429, 404, halaman terproteksi → `/login`                                                                     |
| `AuthenticationTest`    | 20  | B1–B9, C23, F13: login salah/brute-force, redirect per role, intended URL, remember me, logout, akun nonaktif, ganti password, kunci dashboard                               |
| `AttendanceFlowTest`    | 30  | C5–C15: geofence, WFH, Lapangan/Gigs multi-sesi, selfie, bentrok cuti, auto-close lupa pulang, riwayat bulanan, throttle                                                     |
| `EmployeeRequestsTest`  | 24  | C16–C21: cuti (kuota, akhir pekan, batal), lembur, koreksi presensi (validasi, batal, hanya milik sendiri)                                                                   |
| `ApprovalFlowTest`      | 20  | D1–D9: setujui/tolak/batalkan, wewenang atasan langsung vs Owner vs HRD, audit log, dampak ke saldo cuti/absen/shortage                                                      |
| `AttendanceRecapTest`   | 15  | E1–E5: cakupan rekap per akun, ringkasan harian, detail bulanan, koreksi manual (view vs manage)                                                                             |
| `OwnerAreaTest`         | 24  | F1–F12: CRUD karyawan, nonaktif/aktifkan (+ bawahan naik ke atasan), akses dashboard per modul, pengaturan kantor (16 aturan validasi), pesan kontak                         |
| `EmployeeAppTest`       | 20  | C1–C4, C24, K1–K4: Home, KPI/metrik, memo & inbox (audiens, aktif/nonaktif, hide/read), balas thread, matriks modul, halaman 403                                             |
| `WorkControlTest`       | 28  | G1–G13: Memo Forum, Work Tracker (project/task/progress), Timeline Calendar, Meetings/MoM + sync tracker + Blast                                                             |
| `ManagementModulesTest` | 27  | H1–H14: KPI, kontrak, payroll (generate/regenerate/finalisasi/dibayar/hapus), budget, royalty, legal, audit log, changelog, view vs manage                                   |
| `RecruitmentTest`       | 15  | I1–I7: lowongan (draft/terbit/tutup), pipeline pelamar, status, convert → akun, alur ujung ke ujung dari form publik                                                         |
| `ExportImportTest`      | 20  | J1–J10: menu per akses, semua export Excel/PDF dibuat lalu dibaca ulang, tidak ada hash password di export, template, preview → commit (KPI, budget, work tracker, karyawan) |
| `AccessMatrixTest`      | 27  | K1–K3: 21 URL × 5 akun seeder, smoke test seluruh GET tanpa parameter untuk 5 akun (> 200 request), sinkron dengan `DemoSeeder`                                              |
| `PrivateFileAccessTest` | 18  | (Batch 1) akses file private                                                                                                                                                 |
| `PageGuideTest`         | 10  | (Batch 3) peta route → panduan valid, tidak ada panduan yatim, semua halaman dashboard tanpa parameter punya tombol, label akses View/Manage/Khusus Owner, teks di-escape    |
| `ExampleTest` ×2        | 2   | stub bawaan                                                                                                                                                                  |

Pendukung: `tests/TestCase.php` (mematikan Vite; meniru kolom `DATE` MySQL dan fungsi `FIELD()`/`DATE_FORMAT()` di SQLite — hanya di tes, kode aplikasi tidak disentuh untuk ini) dan `tests/Concerns/CreatesWsmFixtures.php` (5 akun standar + pengaturan kantor + waktu dibekukan ke Senin 2026-09-21).

**Kualitas kode tes:** Intelephense (semua tingkat: error, warning, hint) melaporkan **0 diagnostik** di seluruh 17 file tes dan di 15 file `app/` yang diubah.

**Bug aplikasi yang ditemukan tes dan sudah diperbaiki** (perubahan kecil, tinggal di-review; 15 file `app/`):

1. **Data keputusan tidak tersimpan** — `LeaveRequest`, `OvertimeRequest`, `AttendanceCorrectionRequest` tidak mendaftarkan `approver_id`, `decided_at`, `decision_note`, `cancelled_by/at`, `cancellation_reason` (dan `applied_attendance_id`) di `#[Fillable]`, sehingga `update()` membuangnya diam-diam: alasan penolakan/pembatalan tidak pernah sampai ke karyawan, dan siapa yang memutuskan tidak tercatat.
2. **Inbox memo bocor** — modal Inbox (`AppServiceProvider`) menampilkan SEMUA memo, termasuk yang dinonaktifkan dan yang ditujukan ke karyawan tertentu, plus menghitung badge dari memo yang tidak boleh dilihat. Kini memakai `active()` + `visibleTo()` seperti kartu Home.
3. **Error 500 di form Task / MoM** — kolom `work_items.section` NOT NULL, tetapi form Task boleh dikosongkan dan sinkron action item MoM → tracker mengirim `null`. Kini `WorkItem` menyimpan string kosong; `nextItemNo()` disesuaikan.
4. **Bulan meluap pada tanggal 29–31** — `Carbon::createFromFormat('Y-m', …)` memakai hari ini; di tanggal 31, `?bulan=2026-11` menjadi Desember (payroll, riwayat, rekap, export ikut salah bulan). Kini `'!Y-m'` di 14 tempat.
5. **`?month=` ngawur di kalender → 500** — kini jatuh balik ke bulan ini.

**Celah yang belum diperbaiki (tes di-skip, hapus baris `markTestSkipped` setelah diperbaiki):**

| Tes yang di-skip                                                                              | Masalah                                                                                                                       |
| --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| `EmployeeRequestsTest::test_same_date_can_be_resubmitted_after_reject_or_cancel`              | `unique(user_id, date)` di `overtime_requests`: setelah lembur ditolak/dibatalkan, mengajukan lagi di tanggal yang sama → 500 |
| `RecruitmentTest::test_hr_cannot_create_an_owner_account_through_convert`                     | **Keamanan:** convert pelamar menerima `role=owner`; HRD bisa membuat akun Owner                                              |
| `EmployeeAppTest::test_user_outside_the_audience_cannot_reply_to_a_targeted_memo`             | endpoint balas/tandai memo tidak memeriksa audiens                                                                            |
| `ExportImportTest::test_import_with_missing_optional_columns_is_reported_instead_of_crashing` | file import tanpa kolom opsional → 500, bukan pesan error                                                                     |
| `ExportImportTest::test_impossible_dates_are_rejected_instead_of_silently_rolled_over`        | `31/02/2026` diterima dan digulung jadi 03/03/2026                                                                            |

Tes karakterisasi (mengunci perilaku saat ini, sengaja lulus): pengajuan cuti tumpang tindih **tidak** diblokir (C19), dan ganti password **tidak** me-logout pengguna (checklist lama menulis sebaliknya).

### 2.4 Panduan tes untuk non-teknis: tiap tes ngapain dan apa yang dicek

**Apa itu "tes otomatis"?** Robot yang berperan jadi pengguna. Ia membuka halaman, mengisi form, menekan tombol, lalu memeriksa apakah hasilnya benar, persis yang dulu kamu lakukan manual di browser, tapi 301 skenario (5 di antaranya sengaja dilewati) selesai dalam sekitar 16 detik dan tidak pernah lupa langkah. Semua dilakukan di **database sementara yang kosong** dan hilang begitu tes selesai, jadi data aplikasimu yang asli tidak tersentuh.

**Cara membaca hasil** setelah menjalankan `php artisan test`:

- **Hijau / PASS**: skenario berjalan sesuai harapan.
- **Merah / FAIL**: ada yang berubah dan tidak lagi sesuai. Artinya baru saja ada perubahan kode yang merusak sesuatu. Kalau perubahannya disengaja, tes-nya yang perlu disesuaikan; kalau tidak, ada bug yang baru muncul.
- **Kuning / SKIPPED**: masalah yang sudah diketahui tetapi belum diperbaiki (5 buah, lihat tabel di Bab 2.3). Robot sengaja tidak menjalankannya supaya hasil tetap bersih, tetapi pengingatnya tetap tampil.

**Kapan dijalankan?** Setiap selesai mengubah kode dan **wajib sebelum deploy**. Ini pengganti membuka semua halaman satu per satu.

#### Yang dicek tiap file

**Halaman publik (`PublicPagesTest`)**: robot berperan sebagai pengunjung yang belum login.

- Membuka beranda, tentang kami, layanan, karir, dan kontak: semua harus terbuka.
- Lowongan berstatus draft atau sudah ditutup tidak boleh terlihat pengunjung.
- Form lamaran dan form kontak menolak isian kosong atau email ngawur, dan menyimpan isian yang benar.
- Kalau ada yang menekan kirim berkali-kali dalam semenit (spam), sistem menolak.
- Alamat yang tidak ada menampilkan halaman "tidak ditemukan", dan halaman internal mengarahkan tamu ke login.

**Login dan keamanan akun (`AuthenticationTest`)**

- Password salah ditolak dengan pesan jelas; percobaan berulang diblokir sementara.
- Setiap peran mendarat di halaman yang benar setelah login: Owner ke dasbor Owner, yang lain ke Home.
- Setelah login, pengguna dikembalikan ke halaman yang tadi ia tuju.
- "Ingat saya", logout, dan akun nonaktif (tidak bisa login sampai diaktifkan kembali).
- Ganti password: password lama harus benar, password baru minimal 8 karakter dan harus diketik dua kali sama.
- Kunci dasbor: dasbor bisa dikunci dan hanya terbuka lagi dengan password.

**Absen harian (`AttendanceFlowTest`)**: robot menekan tombol absen masuk dan pulang.

- Absen dari kantor di dalam jarak yang ditentukan dianggap sah; di luar jarak tetap tercatat tetapi diberi tanda.
- WFH tidak dihitung jaraknya. Kantor dan WFH hanya boleh satu sesi sehari; Lapangan/Gigs boleh beberapa sesi, asal yang sebelumnya sudah ditutup.
- Absen pulang tanpa absen masuk ditolak. Selfie tersimpan tertutup (tidak bisa dibuka lewat alamat web), dan absen tetap jalan tanpa selfie.
- Tidak bisa absen saat sedang izin/cuti yang sudah disetujui.
- Lupa absen pulang: sistem menutup otomatis di jam selesai kerja (atau tengah malam untuk lembur/lapangan).
- Riwayat hanya menampilkan data milik sendiri, per bulan.

**Pengajuan karyawan (`EmployeeRequestsTest`)**: cuti, lembur, dan koreksi absen.

- Tanggal yang sudah lewat ditolak; akhir pekan tidak dihitung sebagai hari cuti.
- Cuti tahunan tidak boleh melebihi sisa jatah, sedangkan sakit/izin pribadi tidak memotong jatah.
- Pengajuan bisa dibatalkan (harus ada alasan) dan jatah cuti kembali.
- Tidak bisa membatalkan pengajuan orang lain.

**Persetujuan atasan (`ApprovalFlowTest`)**

- Atasan hanya melihat pengajuan bawahan langsungnya; Owner melihat semua; HRD tidak berwenang memutuskan.
- Menolak wajib disertai alasan, dan karyawan bisa membaca alasan itu.
- Pengajuan yang sudah diputuskan tidak bisa diputuskan dua kali.
- Setelah disetujui: jatah cuti berkurang, lembur menghapus kekurangan jam, koreksi absen langsung mengubah jam di data absen (jam aslinya disimpan).
- Setiap keputusan tercatat di audit log lengkap dengan siapa pelakunya.

**Rekap absensi (`AttendanceRecapTest`)**

- Owner dan HRD melihat semua karyawan, manajer hanya timnya sendiri, karyawan biasa tidak punya akses.
- Ringkasan harian (hadir, terlambat, WFH, izin, belum absen) dihitung benar.
- Koreksi jam manual wajib disertai catatan, jam asli tetap tersimpan, dan hanya yang berhak "kelola" yang boleh mengoreksi.

**Area Owner (`OwnerAreaTest`)**

- Semua halaman Owner tertutup untuk orang lain.
- Menambah, mengubah, menonaktifkan, dan mengaktifkan kembali karyawan (email tidak boleh kembar, password tersimpan terenkripsi, bawahan yang atasannya dinonaktifkan dipindah ke atasan di atasnya).
- Mengatur akses tiap modul dan memastikan efeknya langsung terasa: dicabut, langsung ditolak.
- Pengaturan kantor (lokasi, radius, jam kerja, warna) menolak nilai yang tidak masuk akal, dan perubahannya langsung dipakai absen berikutnya.
- Pesan dari form kontak publik muncul di kotak masuk Owner dan bisa ditandai sudah dibaca.

**Tampilan karyawan (`EmployeeAppTest`)**

- Home terbuka untuk semua peran; KPI yang tampil hanya milik sendiri.
- Memo: hanya memo aktif dan yang memang ditujukan kepadanya yang muncul, baik di Home maupun di Inbox. Memo bisa disembunyikan, ditandai baca, dan dibalas.
- Karyawan yang tidak punya modul tertentu tidak melihat menunya dan mendapat halaman "tidak punya akses" kalau nekat membuka alamatnya.

**Work Control (`WorkControlTest`)**: memo, tracker, kalender, dan rapat.

- Membuat, mengubah, menonaktifkan, dan menghapus memo, untuk semua atau orang tertentu.
- Project dan task: dibuat, diedit, dihapus (menghapus project tidak menghapus task-nya), progress diubah lewat 6 status, dan tampil di daftar tugas si penanggung jawab.
- Rapat (MoM): peserta dan daftar tindak lanjut tersimpan; bisa dikirim otomatis jadi task; "Blast" mengubah notulen jadi memo untuk semua karyawan.
- Yang hanya punya akses lihat tidak bisa mengubah apa pun.

**Modul manajemen (`ManagementModulesTest`)**: KPI, kontrak, payroll, budget, royalty, legal, IT.

- Tiap modul: tambah, ubah, hapus, dan ditolaknya isian yang tidak wajar (angka minus, persentase lebih dari 100, tanggal selesai sebelum tanggal mulai).
- Unggah kontrak/dokumen: hanya PDF, Word, dan gambar sampai 10 MB; file lama dihapus saat diganti.
- Payroll: gaji dihitung dari gaji pokok + lembur disetujui − potongan kekurangan jam; alurnya satu arah (draft → final → dibayar) dan yang sudah final tidak bisa diubah atau dihapus.
- Budget yang melebihi anggaran tampil sebagai selisih minus.
- Modul yang hanya boleh dilihat tidak bisa diubah.

**Rekrutmen (`RecruitmentTest`)**

- Lowongan: draft tidak tampil publik, terbit tampil, ditutup hilang lagi.
- Pelamar: bisa dicari dan difilter, statusnya bisa dimajukan.
- Pelamar yang diterima diubah jadi akun karyawan (hanya sekali) dan langsung bisa login.
- Satu skenario penuh dari awal: pelamar mengisi form → HR memproses → akun jadi → login berhasil.

**Export dan Import (`ExportImportTest`)**

- Setiap jenis laporan Excel/PDF benar-benar dibuat, lalu dibuka lagi untuk memastikan isinya benar.
- Export karyawan tidak pernah memuat password.
- Menu hanya menampilkan laporan yang boleh dilihat pengguna itu.
- Import: file dibaca dulu (pratinjau), baris yang salah ditandai dan **tidak** ikut tersimpan, baris yang benar baru masuk setelah dikonfirmasi. Konfirmasi hanya berlaku sekali dan hanya untuk orang yang mengunggah.

**Matriks hak akses (`AccessMatrixTest`)**: paling mirip "cek semua pintu".

- Memakai data demo lengkap. Untuk 21 alamat penting, dicek apa yang terjadi bila dibuka oleh 5 akun berbeda (Owner, Manajer, HRD, dua karyawan) dan oleh tamu.
- Semua halaman yang bisa dibuka tanpa parameter dicoba oleh kelima akun (lebih dari 200 percobaan) untuk memastikan tidak ada yang menampilkan halaman error.
- Memastikan data uji yang dipakai tes lain tetap sama dengan data demo asli.

**Akses file privat (`PrivateFileAccessTest`)**: selfie, kontrak, dan dokumen legal hanya bisa dibuka oleh yang berhak, dan tidak bisa ditebak lewat alamat web.

**Panduan halaman (`PageGuideTest`)**: robot memastikan tombol "? Panduan" ada di setiap halaman dashboard.

- Setiap halaman dashboard yang bisa dibuka tanpa parameter dicek: tombol dan isi panduannya harus ada. Kalau kelak ada halaman baru yang lupa didaftarkan panduannya, tes ini yang gagal.
- Isi panduan diperiksa lengkap (judul, ringkasan, butir penjelasan) dan tidak ada panduan yang tidak terpakai.
- Label "Akses kamu: View / Manage" sesuai akun yang membuka, dan halaman khusus Owner berlabel "Khusus Owner".
- Halaman yang bukan dashboard (Home karyawan, halaman publik) tidak memunculkan tombol.
- Teks panduan yang mengandung kode HTML/script tidak dijalankan, hanya ditampilkan sebagai teks.

**Yang tidak bisa dicek robot** dan tetap perlu mata manusia sebelum go-live: tampilan di layar HP dan berbagai ukuran layar, izin lokasi dan kamera di HP sungguhan, tampilan visual file Excel/PDF (isi datanya sudah dicek, tampilannya belum), tombol dan animasi yang bergantung pada JavaScript, serta pengiriman email.

### 2.5 Panduan halaman dashboard (Batch 3 — 2026-09-21)

Fitur baru: tiap halaman dashboard punya tombol **"? Panduan"** (melayang di kanan bawah). Ditekan, muncul modal berisi fungsi halaman, fitur yang bisa dipakai, dan hal yang perlu diketahui. Ditutup lewat ✕, tombol "Mengerti, tutup", tombol Esc, atau klik area gelap, sehingga pengguna tidak meninggalkan halaman.

| #   | Langkah                                                                                     | Status                                                                                       |
| --- | ------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| 1   | Petakan halaman dashboard dan baca fitur tiap halaman langsung dari kode                    | ✅                                                                                           |
| 2   | Peta route → panduan dan teks 44 panduan (`config/page_guides.php`, 54 pola route)          | ✅                                                                                           |
| 3   | Pencari panduan (`PageGuide`), tombol + modal (`partials/page-guide`), di-include di layout | ✅                                                                                           |
| 4   | Tes otomatis `PageGuideTest` + seluruh tes lama tetap hijau                                 | ✅ 311 tes: 306 lulus + 5 skip                                                               |
| 5   | Cek tampilan di layar HP dan desktop sungguhan                                              | ✅ aman (dicek manual)                                                                       |
| 6   | `npm run build` lalu upload ulang `public/build` (ada class Tailwind baru)                  | ⏸ belum dilakukan: masih deployment lokal. Dikerjakan saat deploy production (Bab 4.1 no. 4) |

**Hasil:** modal responsif (bottom-sheet di HP, di tengah layar di desktop), isi panjang scroll di dalam modal sementara judul dan tombol tutup tetap terlihat. Panduan dicari dari nama route, jadi **tidak ada view halaman yang diubah**; satu-satunya perubahan di layout adalah satu baris `@include`. Halaman modul juga menampilkan label akses pengguna ("Akses kamu: Manage" atau "View"), halaman Owner berlabel "Khusus Owner". Teks panduan ditulis dalam bahasa Indonesia dan dicocokkan dengan perilaku kode yang ada. Diuji di SQLite (PHP 8.3.6): 311 tes, 3.661 assertion, ±15 detik; belum diuji ulang di MariaDB.

**Menambah atau mengubah panduan:** edit `config/page_guides.php`. Halaman baru cukup ditambah satu baris di `routes` (nama route → kunci) dan satu entri di `guides`; `PageGuideTest` akan gagal kalau halaman dashboard baru belum punya panduan. Halaman tanpa panduan tidak menampilkan tombol dan tidak error.

**Belum termasuk:** halaman aplikasi karyawan (`/app`, layout terpisah) belum punya tombol panduan.

**Ditemukan saat menulis panduan:** form "Koreksi jam absen" tampil untuk akses People level View padahal simpannya 403. Sudah diperbaiki di Bab 2.6.

### 2.6 Perbaikan susulan: form Koreksi jam absen (Batch 4 — 2026-09-21)

| #   | Langkah                                                                                          | Status                                  |
| --- | ------------------------------------------------------------------------------------------------ | --------------------------------------- |
| 1   | Tulis 2 tes dulu (level View tidak boleh melihat form, Manage boleh) dan pastikan tes View gagal | ✅ gagal sebelum perbaikan              |
| 2   | Bungkus form koreksi di `attendance/recap/show.blade.php` dengan cek `canManageModule('people')` | ✅                                      |
| 3   | Uji mutasi: cek dikembalikan jadi `@if (true)` → tes View harus gagal lagi                       | ✅ gagal, lalu hijau setelah dipulihkan |
| 4   | Seluruh tes                                                                                      | ✅ 313 tes: 308 lulus + 5 skip          |
| 5   | Sesuaikan teks Panduan "Riwayat Absensi Karyawan" (tombol koreksi hanya untuk Manage)            | ✅                                      |

**Hasil:** pengguna People level View tetap bisa membuka riwayat absensi bawahannya, tetapi tidak lagi melihat tombol/form "Koreksi jam absen" yang ujungnya 403. Keamanan tidak berubah: route simpan tetap dijaga `module:people,manage` dan scope tim tetap dicek di controller (tes lama `test_view_only_users_cannot_correct_even_by_posting_directly` dan `test_manager_with_manage_access_is_still_limited_to_her_team` tetap hijau). Diuji di SQLite (PHP 8.3.6); belum diuji ulang di MariaDB.

**Catatan tes:** frasa "Koreksi jam absen" juga muncul di modal Panduan halaman, jadi tes memeriksa teks lain yang khusus form ("Simpan Koreksi", placeholder alasan, dan URL simpan).

---

## 3. Struktur File

```
app/
├── Exports/                                # Export Excel (maatwebsite/excel), dipanggil ExportController
│   ├── BaseExport.php                      # Kerangka dasar semua export per modul
│   ├── TemplateExport.php                  # Generator template Excel kosong untuk tombol "Download Template"
│   ├── AttendanceRecapExport.php           # Rekap absensi
│   ├── AuditLogExport.php                  # Audit log
│   ├── EmployeeContractExport.php          # Kontrak karyawan
│   ├── EmployeeExport.php                  # Data karyawan
│   ├── JobApplicationExport.php            # Pelamar rekrutmen
│   ├── KpiExport.php                       # KPI
│   ├── LeaveRequestExport.php              # Rekap izin/cuti tim
│   ├── LegalDocumentExport.php             # Dokumen legal
│   ├── PayrollExport.php                   # Payroll (Excel)
│   ├── ProjectBudgetExport.php             # Project budgeting
│   ├── RoyaltyEntryExport.php              # Royalty
│   └── WorkItemExport.php                  # Work Tracker
├── Imports/                                # Import Excel/CSV dengan pratinjau
│   ├── BaseImport.php                      # Kerangka dasar semua import
│   ├── WorkItemImport.php                  # Import Work Tracker (section, PIC, progres, tanggal)
│   ├── EmployeeImport.php                  # Import karyawan (password kosong → "password")
│   ├── KpiImport.php                       # Import KPI
│   └── ProjectBudgetImport.php             # Import budget
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php                  # Base controller
│   │   ├── Approval/
│   │   │   ├── LeaveRequestController.php               # Manajer/Owner approve-reject-batal izin/cuti
│   │   │   ├── OvertimeRequestController.php            # Approval lembur
│   │   │   └── AttendanceCorrectionRequestController.php # Approval koreksi presensi
│   │   ├── Attendance/
│   │   │   └── RecapController.php         # Rekap absensi + detail per orang + selfie private (modul people)
│   │   ├── Auth/
│   │   │   └── LoginController.php         # Login/logout 1 form semua role, redirect per role
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php     # Landing dashboard per modul + halaman modul placeholder
│   │   │   ├── DashboardLockController.php # Lock/unlock dashboard (server-side)
│   │   │   ├── Budget/BudgetController.php       # CRUD project budgeting
│   │   │   ├── Contracts/ContractController.php  # CRUD kontrak karyawan + upload private + route file()
│   │   │   ├── ExportImport/
│   │   │   │   ├── ExportImportController.php    # Halaman pusat Export & Import
│   │   │   │   ├── ExportController.php          # Semua export (route generik per key/format)
│   │   │   │   └── ImportController.php          # Semua import (upload → pratinjau → konfirmasi)
│   │   │   ├── It/
│   │   │   │   ├── AuditLogController.php        # Daftar audit log (read-only)
│   │   │   │   └── SystemChangelogController.php # CRUD changelog sistem
│   │   │   ├── Kpi/KpiController.php             # CRUD KPI seluruh tim
│   │   │   ├── Legal/LegalController.php         # CRUD Album Contracts & Royalty Agreements + route file()
│   │   │   ├── Payroll/PayrollController.php     # Generate/final/bayar payroll bulanan
│   │   │   ├── Royalty/RoyaltyController.php     # CRUD royalty entry
│   │   │   └── Work/
│   │   │       ├── WorkTrackerBoardController.php # Work Tracker admin: kanban, item, project
│   │   │       ├── CalendarController.php         # Timeline Calendar
│   │   │       ├── MeetingController.php          # MoM terstruktur + action item + PDF
│   │   │       └── MemoController.php             # Memo Forum sisi admin
│   │   ├── Employee/
│   │   │   ├── HomeController.php                 # Home karyawan: kartu absensi, KPI, milestone, dll
│   │   │   ├── AttendanceController.php           # Absen masuk/pulang, geofence, selfie, multi-sesi
│   │   │   ├── AttendanceCorrectionController.php # Ajukan koreksi presensi
│   │   │   ├── LeaveRequestController.php         # Ajukan/batal izin & cuti
│   │   │   ├── OvertimeRequestController.php      # Ajukan lembur
│   │   │   ├── MemoInteractionController.php      # Baca/sembunyikan/balas memo
│   │   │   ├── ProfileController.php              # Profil + ganti password
│   │   │   └── WorkTrackerController.php          # My Work Tracker sisi karyawan
│   │   ├── Owner/
│   │   │   ├── DashboardController.php            # Ringkasan Owner
│   │   │   ├── EmployeeController.php             # CRUD karyawan, role, atasan, gaji
│   │   │   ├── OrganizationController.php         # Org-chart
│   │   │   ├── DashboardAccessController.php      # Atur akses modul per user
│   │   │   ├── OfficeSettingController.php        # Pengaturan kantor (geo, jam, tarif, warna)
│   │   │   └── ContactMessageController.php       # Baca pesan dari form kontak publik
│   │   ├── Public/
│   │   │   └── PageController.php                 # Beranda, Tentang, Layanan, Karir, Kontak, lamar, kirim pesan
│   │   └── Recruitment/
│   │       ├── JobOpeningController.php           # CRUD lowongan
│   │       └── JobApplicationController.php       # Pipeline pelamar + konversi jadi karyawan
│   ├── Middleware/
│   │   ├── EnsureRole.php                  # Jaga route per role (owner/manajer/hrd/karyawan)
│   │   ├── EnsureModuleAccess.php          # Jaga route per modul & level (view/manage)
│   │   └── EnsureDashboardUnlocked.php     # Paksa layar unlock jika dashboard terkunci
│   └── Requests/                           # 31 FormRequest (validasi)
│       ├── Approval/                       # Alasan tolak izin/cuti
│       ├── Attendance/                     # Koreksi absensi manual (admin)
│       ├── Dashboard/                      # Budget, Contracts, It, Kpi, Legal, Payroll, Royalty, Work, Unlock
│       ├── Employee/                       # Clock in/out, izin, lembur, koreksi, ganti password
│       ├── Memo/                           # Balasan thread memo
│       ├── Owner/                          # Karyawan (store/update), pengaturan kantor
│       ├── Public/StoreJobApplicationRequest.php  # Validasi lamaran (tanpa CV/portofolio)
│       └── Recruitment/                    # Lowongan, status & konversi pelamar
├── Models/                                 # 25 model (nama = tabel)
│   ├── User.php                            # Akun (role, atasan, gaji, soft delete, accessLevel())
│   ├── DashboardAccess.php                 # Akses modul per user (view/manage)
│   ├── OfficeSetting.php                   # Pengaturan kantor (1 baris)
│   ├── Attendance.php                      # Sesi absensi + hitung blok kekurangan jam
│   ├── AttendanceCorrectionRequest.php     # Pengajuan koreksi presensi
│   ├── LeaveRequest.php                    # Izin/cuti
│   ├── OvertimeRequest.php                 # Lembur
│   ├── Memo.php / MemoRead.php / MemoThreadMessage.php  # Memo, status baca, thread balasan
│   ├── Project.php / WorkItem.php          # Project & item Work Tracker
│   ├── Meeting.php / MeetingActionItem.php # Rapat & action item
│   ├── Kpi.php                             # KPI
│   ├── EmployeeContract.php                # Kontrak karyawan
│   ├── PayrollRecord.php                   # Payroll bulanan (draft/finalized/paid)
│   ├── ProjectBudget.php                   # Baris budget vs actual
│   ├── RoyaltyEntry.php                    # Baris royalty
│   ├── LegalDocument.php                   # Dokumen legal
│   ├── AuditLog.php / SystemChangelog.php  # Audit & changelog sistem
│   ├── JobOpening.php / JobApplication.php # Lowongan & lamaran
│   └── ContactMessage.php                  # Pesan dari form kontak
├── Providers/
│   └── AppServiceProvider.php              # Service provider utama
└── Support/
    ├── AttendanceReconciler.php            # Tutup paksa sesi lupa pulang (tanpa cron, ikut trafik web)
    ├── Geo.php                             # Jarak Haversine untuk geofence server-side
    ├── PageGuide.php                       # Cari panduan halaman dari nama route + label akses user (tombol "? Panduan")
    ├── PrivateFile.php                     # Simpan/hapus/alirkan file sensitif di disk private (kontrak, legal, selfie)
    └── ExportImport/
        ├── ExportCatalog.php               # Sumber tunggal: modul mana punya export/import + gerbang aksesnya
        └── ImportPreviewService.php        # Simpan upload sementara & pratinjau sebelum konfirmasi

bootstrap/
├── app.php                                 # Konfigurasi app, alias middleware, routing
└── cache/                                  # Cache framework

config/
├── app.php                                 # Timezone Asia/Jakarta, locale
├── auth.php · cache.php · database.php     # Konfigurasi bawaan Laravel
├── filesystems.php                         # Disk `local` (private) untuk file sensitif; `serve` dimatikan
├── logging.php · mail.php · queue.php
├── page_guides.php                         # Peta route → panduan + teks panduan tiap halaman dashboard (edit di sini)
├── services.php
└── session.php                             # Session 120 menit, driver database

database/
├── factories/
│   └── UserFactory.php                     # Factory user untuk testing
├── migrations/                             # 40 migrasi berurutan (users → sesi, absensi, modul dashboard, rekrutmen, kontak)
│   ├── ..._dashboard_access.php            # Tabel akses modul
│   ├── ..._add_legal_and_it_modules_to_dashboard_access.php  # Tambah enum modul via Schema `->change()` (portable, sebelumnya raw MODIFY)
│   ├── ..._office_settings.php (2×)        # Create + tambahan kolom (nama file sama, membingungkan)
│   ├── ..._attendances.php (2×)            # Create + tambahan kolom
│   └── ...                                 # 30+ migrasi fitur lain (payroll, kpi, legal, audit, dst.)
├── seeders/
│   ├── DatabaseSeeder.php                  # Entry point seeding
│   ├── OfficeSettingSeeder.php             # Pengaturan kantor awal
│   └── DemoSeeder.php                      # Data demo (5 user, password "password") — HANYA lokal
└── database.sqlite                         # Sisa setup awal (koneksi aktif MySQL) — hapus

resources/
├── css/app.css                             # Entry CSS (Tailwind v4)
├── js/
│   ├── app.js                              # Entry JS (Alpine.js)
│   ├── attendance.js                       # Geolocation, kamera selfie, peta Leaflet
│   └── alerts.js                           # Konfirmasi SweetAlert
└── views/
    ├── layouts/
    │   ├── app.blade.php                   # Layout dashboard (sidebar bernomor per bagian, per hak akses)
    │   ├── employee.blade.php              # Layout app-mobile karyawan (bottom-nav)
    │   ├── public.blade.php                # Layout halaman publik
    │   └── error.blade.php                 # Layout halaman error
    ├── auth/login.blade.php                # Halaman login
    ├── public/                             # home, about, services, careers, career-show (form lamaran), contact
    ├── employee/                           # home, profil, riwayat, leave, overtime, attendance-correction, work-tracker, widget (_kpi, _milestones, _paid-leave, _team-moments)
    ├── approval/                           # leave, overtime, attendance-correction
    ├── attendance/recap/                   # index, show (rekap & detail per orang)
    ├── owner/                              # dashboard, employees, organization, dashboard-access, office-settings, contact-messages
    ├── dashboard/
    │   ├── index · module · locked         # Landing dashboard, placeholder modul, layar terkunci
    │   ├── work/ (+meetings, tracker)      # Work Tracker, kalender, rapat
    │   ├── budget · royalty · kpi · contracts · legal/   # CRUD masing-masing (index/create/edit/_form)
    │   ├── payroll/                        # index, show
    │   ├── it/ (+changelog)                # Audit log & changelog
    │   └── export-import/                  # Pusat export/import, picker, pratinjau
    ├── recruitment/                        # openings (lowongan), applications (pelamar, konversi)
    ├── memo/_thread.blade.php              # Thread balasan memo
    ├── pdf/                                # attendance-recap, meeting-minutes, payroll-slip, layout
    ├── components/org-node.blade.php       # Node org-chart rekursif
    ├── partials/flash-data.blade.php       # Data flash untuk SweetAlert
    ├── partials/page-guide.blade.php       # Tombol "? Panduan" + modal, di-include sekali dari layouts/app
    └── errors/                             # 403, 404, 500, 503

routes/
├── web.php                                 # 153 route: publik, /app, /owner, /dashboard, rekrutmen, approval
├── auth.php                                # Login/logout
└── console.php                             # Command `files:privatize` (pindah file lama ke disk private)

public/
├── index.php                               # Front controller
├── robots.txt
├── favicon.ico
├── hot                                     # ⚠ HAPUS: penanda dev-server Vite (merusak produksi)
├── fonts-manifest.dev.json                 # File dev, hapus
└── build/                                  # ⚠ BELUM ADA: hasil `npm run build`, wajib di-upload

storage/
├── app/private/                            # Selfie absensi, kontrak, dokumen legal (tidak punya URL publik)
├── app/public/                             # Kosong; tidak dipakai file sensitif lagi (tidak perlu `storage:link`)
├── framework/views/                        # Cache Blade (108 file sisa dev, kosongkan)
└── logs/laravel.log                        # ⚠ ±2 MB berisi path lokal `C:/Users/...`, jangan di-upload

tests/
├── TestCase.php                            # Dasar semua tes: withoutVite() + emulasi MySQL (DATE, FIELD, DATE_FORMAT) di SQLite
├── Concerns/CreatesWsmFixtures.php         # Akun standar, pengaturan kantor, waktu dibekukan (Senin 2026-09-21)
├── Feature/                                # 16 file, 312 tes (lihat tabel Bab 2.3, 2.5, 2.6)
│   ├── PublicPagesTest · AuthenticationTest · AttendanceFlowTest · EmployeeRequestsTest
│   ├── ApprovalFlowTest · AttendanceRecapTest · OwnerAreaTest · EmployeeAppTest
│   ├── WorkControlTest · ManagementModulesTest · RecruitmentTest · ExportImportTest · AccessMatrixTest
│   ├── PrivateFileAccessTest               # Batch 1: 18 tes akses file private
│   ├── PageGuideTest                       # Batch 3: 10 tes panduan halaman dashboard
│   └── ExampleTest                         # Stub bawaan
└── Unit/ExampleTest.php                    # Stub bawaan — belum ada tes unit murni

(root) .env · .env.example · .gitignore · composer.json/lock · package.json · phpunit.xml · vite.config.js
       AGENTS.md · CLAUDE.md               # File catatan repo, tidak perlu ikut di-deploy
       README.md                            # Kosong (0 byte) di snapshot yang diaudit; digantikan file ini
```

---

## 4. Langkah Selanjutnya

### 4.0 Keputusan yang sudah diambil

| Topik                            | Keputusan                                                                                                                                                                                                                    |
| -------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Versi PHP hosting                | cPanel Rumahweb mentok di **PHP 8.3** (tidak ada 8.4). Tindakannya di 4.1 no. 1.                                                                                                                                             |
| Struktur folder cPanel           | `public_html` hanya berisi isi folder `public/` project. Sisa project (`app/`, `vendor/`, `storage/`, `.env`, dst.) berada **di luar** `public_html`, sejajar dengannya. Tindakannya di 4.1 no. 3.                           |
| Status deployment                | Masih lokal, belum ada deploy production. Karena itu `public/build` belum dibangun (4.1 no. 4).                                                                                                                              |
| Fitur yang dikerjakan berikutnya | Reset password + seeder testing, Pengaturan Kantor, ritme mingguan Dashboard Owner, Payroll, Project Budgeting, Royalty Dashboard (4.2 no. 1–6). Payroll, Budgeting, dan Royalty ditandai penting/krusial. Urutannya di 4.4. |
| Role developer                   | Dibuat sebagai role baru dengan akses **Tingkat 2** (rincian di 4.2 no. 1). Ancha (office manager) memakai role `manajer` dengan Manage 10 modul.                                                                            |
| Panduan halaman dashboard        | Tampilan di HP dan desktop sudah dicek, aman (Bab 2.5).                                                                                                                                                                      |

### 4.1 Blocker deploy (wajib beres sebelum publik)

Satu tabel untuk status sekaligus tindakan. Penjelasan blocker 4, 8, dan 9 ada di bawah tabel.

| #   | Blocker                               | Apa artinya                                                                                                                                                                                                                                                       | Tindakan                                                                                                                                                                                                                                                                                                                                                                              | Status                                                     |
| --- | ------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| 1   | **Versi PHP**                         | Paket Symfony v8.1 di `vendor/` (`clock`, `css-selector`, `event-dispatcher`, `string`, `translation`) mewajibkan PHP ≥ 8.4.1 (`vendor/composer/platform_check.php` menolak jalan di bawahnya), padahal `composer.json` menulis `^8.3` dan hosting mentok di 8.3. | Set `config.platform.php = 8.3.0` di `composer.json`, jalankan `composer update` di lokal supaya paket Symfony turun ke 7.4, lalu `php artisan test`. Folder `vendor/` hasilnya ikut di-upload (server tanpa terminal tidak bisa `composer install`). Cek awal (2026-09-21): 313 tes lulus di PHP 8.3.6 saat platform check dimatikan; itu bukan pengganti `composer update`.         | ⬜ keputusan ada, tinggal dikerjakan (4.4 langkah 1)       |
| 2   | **`.env` produksi**                   | `.env` yang ada khusus lokal: `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL` localhost, DB user `root`.                                                                                                                                                             | Buat `.env` baru: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, user DB khusus, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`.                                                                                                                                                                                                                              | ⬜                                                         |
| 3   | **Struktur folder cPanel**            | Kalau seluruh project ditaruh di `public_html`, `.env` dan `app/` bisa dibuka lewat URL (tidak ada `.htaccess` di root project).                                                                                                                                  | Sesuai keputusan (4.0): isi `public/` ke `public_html`, sisanya di luar sejajar `public_html`. Ubah 3 path di `public/index.php` (`maintenance.php`, `vendor/autoload.php`, `bootstrap/app.php`) dari `__DIR__.'/../...'` ke folder project, mis. `__DIR__.'/../wsm-office/...'`. Folder `storage/` otomatis ikut di luar `public_html`, dan itu syarat agar file private tetap aman. | ⬜ keputusan ada; path diubah saat deploy                  |
| 4   | **Aset Vite**                         | Halaman butuh file CSS/JS hasil "rakitan" Vite di `public/build/` (penjelasan di bawah). Saat ini `public/hot` ada dan `public/build` belum ada.                                                                                                                  | Hapus `public/hot`, jalankan `npm run build` di lokal, upload isi `public/build` ke `public_html/build`.                                                                                                                                                                                                                                                                              | ⬜ ditunda sampai deploy production (saat ini masih lokal) |
| 5   | **File sensitif terbuka tanpa login** | Kontrak karyawan, dokumen legal, dan selfie absensi dulu disimpan di disk `public`.                                                                                                                                                                               | Dipindah ke disk private + route unduh yang mengecek modul/pemilik (catatan di bawah).                                                                                                                                                                                                                                                                                                | ✅ **selesai (batch 1)**                                   |
| 6   | **Password default "password"**       | `EmployeeImport` mengisi "password" jika kolom kosong, dan tidak ada paksaan ganti.                                                                                                                                                                               | Tambah kolom `must_change_password` dan redirect paksa ke ganti password saat login pertama. Dikerjakan bersama reset password (4.2 no. 1).                                                                                                                                                                                                                                           | ⬜                                                         |
| 7   | **Database tanpa terminal**           | `migrate` tidak bisa dijalankan di server.                                                                                                                                                                                                                        | Jalankan `migrate` + `OfficeSettingSeeder` di lokal, ekspor SQL, impor lewat phpMyAdmin. **Jangan** jalankan `DemoSeeder` dan seeder testing baru (4.2 no. 1); keduanya hanya untuk lokal. Migrasi enum sudah portabel (MySQL/MariaDB dan SQLite).                                                                                                                                    | ⬜                                                         |
| 8   | **Bersihkan paket upload**            | Zip proyek berisi file dev/pribadi yang tidak boleh ikut ke server.                                                                                                                                                                                               | Ikuti daftar di bawah tabel.                                                                                                                                                                                                                                                                                                                                                          | ⬜                                                         |
| 9   | **Konten publik placeholder**         | Empat halaman publik masih berisi teks "Placeholder" atau teks generik.                                                                                                                                                                                           | Isi konten asli (tetap statis di Blade sesuai keputusan). Daftarnya di bawah tabel.                                                                                                                                                                                                                                                                                                   | ⬜                                                         |

**Blocker 4 (Aset Vite) dalam bahasa sederhana:** browser tidak memakai kode CSS Tailwind dan JavaScript Alpine apa adanya; Vite "merakitnya" menjadi file jadi di folder `public/build/`. Server cPanel tanpa terminal tidak bisa menjalankan Vite, jadi rakitannya dibuat di komputer lokal (`npm run build`) lalu folder hasilnya di-upload. `public/hot` adalah penanda "server dev Vite sedang jalan": kalau ikut ter-upload, Laravel mencari CSS/JS ke alamat localhost dan halaman tampil tanpa gaya. Tanpa `public/build`, halaman menampilkan error `Vite manifest not found`. Setiap ada perubahan tampilan (termasuk class Tailwind baru), rakit ulang dan upload ulang. Tes otomatis tidak butuh ini karena `tests/TestCase.php` mematikan Vite.

**Blocker 8, yang jangan di-upload:**

- `.git/` dan `node_modules/`
- `.env` lokal (ganti dengan `.env` produksi; `.env.example` boleh)
- `database/database.sqlite`
- `storage/logs/laravel.log` (±2 MB, memuat path lokal `C:/Users/...`)
- isi `storage/framework/views/` (cache Blade), bila ada
- `public/hot` dan `public/fonts-manifest.dev.json`
- `.phpunit.result.cache`
- `AGENTS.md` dan `CLAUDE.md` (catatan repo)
- `tests/`, `phpunit.xml`, `package.json`, `package-lock.json`, `vite.config.js`: tidak dipakai di server (hanya untuk tes dan build lokal), boleh dilewati

Yang **tetap di-upload:** `vendor/` (server tanpa Composer) dan hasil `npm run build` (`public/build`, ke `public_html/build`).

**Blocker 9, teks yang perlu diganti:**

- **Beranda:** teks pengantar dan label hero, tiga kartu "Yang kami kerjakan", dan kalimat penjelas "Ringkasan singkat layanan/keunggulan tim..." (masih generik).
- **Tentang:** teks pengantar, Visi, Misi, dan timeline "Perjalanan Kami" (dua baris "Tahun —" / "Tonggak sejarah placeholder").
- **Layanan:** kalimat pengantar dan empat deskripsi (Produksi Musik, Kampanye & Promosi, Arahan Kreatif, Manajemen Tim).
- **Kontak:** alamat kantor, nomor telepon, dan alamat email.

**Catatan blocker 5 (selesai, batch 1)**

**Hasil:** kontrak karyawan, dokumen legal, dan selfie absensi kini tersimpan di `storage/app/private` dan tidak punya URL publik. Semuanya hanya bisa dibuka lewat route yang mewajibkan login + akses modul yang sama dengan halaman pemiliknya (`contracts`/`legal` level view; selfie: modul `people` dengan scope rekap, yaitu Owner/HRD semua orang, selain itu diri sendiri + bawahan). File yang sudah pernah diupload ke disk publik dipindah sekali dengan `php artisan files:privatize` (path di database tidak berubah). Selfie baru juga divalidasi: harus gambar sungguhan dan maksimal 4 MB. Kebutuhan `storage:link` hilang, jadi tidak perlu terminal di server. Bukti: 18 tes otomatis + uji mutasi + smoke test ulang (Bab 2.2).

**Perbaikan susulan (tes gagal di SQLite):** `php artisan test` di lokal gagal semua karena dua migrasi (`add_legal_and_it_modules_to_dashboard_access` dan `add_recruitment_module_to_dashboard_access`) memakai raw `ALTER TABLE ... MODIFY`, sintaks khusus MySQL, sedangkan `phpunit.xml` memakai SQLite. Keduanya diganti ke `Schema::table()->enum()->change()` bawaan Laravel. Terverifikasi: skema tabel `dashboard_access` di MariaDB identik dengan sebelumnya (`SHOW CREATE TABLE` di-diff), rollback dan migrate ulang jalan, dan 20 tes lulus di SQLite serta MariaDB.

**Yang perlu kamu lakukan di lokal sebelum deploy:** jalankan `php artisan files:privatize --dry-run` untuk melihat rencana, lalu `php artisan files:privatize`. Jika belum ada file lama, hasilnya 0 file dan tidak ada yang perlu dilakukan.

### 4.2 Fitur yang akan dikerjakan

Butir 1–6 adalah keputusan 2026-09-21 ("eksekusi"); butir 7 keputusan sebelumnya. Urutan pengerjaannya ada di 4.4.

1. **Reset password dari dashboard IT, role developer, dan seeder testing baru.** Keputusan 2026-09-21 sudah lengkap; tinggal dikerjakan.
    - **Reset password (modul IT, butuh Manage `it`):** halaman baru berisi daftar karyawan + tombol Reset. Password sementara tampil sekali, `must_change_password` menyala sehingga wajib diganti saat login berikutnya (sekaligus menutup blocker 6), dan tercatat di Audit Log. Akun **Owner hanya bisa direset oleh Owner**. Reset mandiri lewat email tetap ditunda sampai ada email asli.
    - **Role `developer` (Tingkat 2, seperti Owner tanpa tiga hal sensitif).** Bisa: Manage ke 10 modul dashboard (lewat akses modul), lihat absensi semua orang di Rekap, reset password non-Owner, kelola Karyawan (tambah, ubah, nonaktifkan), Struktur Organisasi, Pengaturan Kantor, Pesan Kontak, Dashboard Owner, dan import/export karyawan. **Tidak bisa:** membuat, mengubah, mereset, atau menonaktifkan akun Owner (termasuk menjadikan orang lain Owner), membuka Dashboard Access, dan memutus persetujuan orang yang bukan bawahan langsungnya.
    - **Ancha (office manager):** role `manajer`, Manage ke 10 modul. Supaya Rekap Absensi-nya mencakup semua orang tanpa mengubah kode, jadikan Ancha "Atasan Langsung" di puncak struktur (di bawah Owner); persetujuan yang bisa ia putus tetap hanya bawahan langsungnya.
    - **Seeder testing baru** (terpisah, hanya untuk lokal, tidak ikut ke produksi): akun Ancha dan Arga (`ancha@wsm.test` dan `arga@wsm.test`, password `password`) beserta akses modulnya dan posisi Ancha di struktur.
    - **Yang perlu diubah di kode:** migrasi enum `users.role` (pola portabel `->change()` yang sudah dipakai), tiga kelas Request (`Rule::in` role), `isDeveloper()` dan label/badge role, redirect login, daftar `role:` di route (`/app`, `dashboard-lock`, `dashboard`), grup route `owner.` dipisah menjadi `role:owner,developer` dan `role:owner` (khusus Dashboard Access), sidebar (empat pengecekan `isOwner()` untuk area Owner), `scopedUsers()` di `RecapController` (`isOwner() || isHrd() || isDeveloper()`), gerbang import/export karyawan di `ExportCatalog` (sekarang khusus Owner), dan tombol Reset Password di sidebar IT.
    - **Guard akun Owner:** hanya Owner yang boleh membuat atau mengubah akun ber-role `owner`. Aturan yang sama dipasang di form Karyawan, konversi pelamar, dan import karyawan. Sekaligus menutup celah "HRD bisa membuat akun Owner lewat convert pelamar" (satu dari 5 tes yang di-skip).
    - **Efek samping "lihat semua" untuk developer:** ikut melebar ke foto selfie, koreksi absen, dan export rekap, karena semuanya memakai scope yang sama. Manajer biasa tidak berubah (tes "manajer dengan Manage tetap terbatas timnya" tetap berlaku).
    - **Panduan dan tes:** halaman Reset Password wajib punya panduan (`PageGuideTest` akan gagal kalau belum). Label akses di panduan halaman `/owner` berubah dari "Khusus Owner" menjadi "Owner & Developer". Tes baru mencakup: kolom `must_change_password`, matriks akses developer (boleh dan tidak boleh), guard akun Owner, aturan reset, dan seeder.
2. **Pengaturan Kantor:** jadikan blok potongan (sekarang tetap 60 menit), jam mulai lembur, dan toggle auto-close sesi lupa pulang bisa diatur, agar sejajar dengan prototype. Dikerjakan sebelum Payroll karena Payroll memakai blok potongan.
3. **Dashboard Owner:** ritme mingguan (fokus dan mode WFO/WFH per hari) sekarang tertulis langsung di controller; pindahkan ke database dan buat bisa diedit Owner.
4. **Payroll (penting).** Semua urusan payroll ada di butir ini:
    - Potong hari tanpa absensi ("alpha"): hari kerja tanpa sesi absen dan tanpa izin/cuti/lembur disetujui, dipotong `gaji ÷ hari kerja`.
    - Total dibatasi minimal 0 (sekarang bisa negatif).
    - Payroll `finalized` boleh dibuka kembali (dengan audit log) sampai berstatus `paid`, supaya tetap bisa dikoreksi sampai hari gaji.
    - Tarif potongan kurang jam default-nya 0; kalau belum diisi di Pengaturan Kantor, potongan diam-diam nol (form generate sudah menampilkan peringatan).
    - Setelah selesai, perbarui baris uji "karyawan tanpa satu pun absensi" di Bab 2.2 (sekarang tetap dibayar penuh Rp 6.500.000).
5. **Project Budgeting (krusial):** naikkan dari CRUD flat per baris ke budget vs actual per project seperti prototype. Rincian kekurangannya dicocokkan dengan prototype v32 saat mulai dikerjakan (prototype tidak ada di repo).
6. **Royalty Dashboard (krusial):** tambahkan yang belum ada dibanding prototype: waterfall recoupment per lagu, ledger kuartal, dan sinkron Google Sheet. Ini yang terbesar, jadi dikerjakan terakhir di antara fitur keuangan.
7. **Lamaran kerja: upload CV + link portofolio.** Tambah kolom `cv_path`, `portfolio_url` di `job_applications`, validasi (pdf/doc, ukuran maks), simpan di disk **private**, tampilkan di panel pelamar dan export.

**Belum dijadwalkan (butuh keputusan atau penjelasan):**

- **Assign / Reminder dari dashboard** (Bab 2.1 no. 28): maksud persisnya belum terdokumentasi karena file prototype tidak ada di repo. Yang ada di kode hanya kolom `is_reminder` pada task dan section "REMINDER / ADMIN" di Work Tracker. Perlu dijelaskan dulu fungsi yang diinginkan sebelum masuk daftar.
- **Executive People Overview** (Bab 2.1 no. 20): sengaja di-skip sejak awal karena belum ada padanan halaman yang jelas. Putuskan: tetap tidak dibuat, atau tentukan isinya.
- **Tampilan teks:** UI berbahasa Inggris, teks penting/rawan salah paham berbahasa Indonesia. Saat ini banyak label campur; rapikan saat mengisi konten publik.
- **Ditunda sesuai keputusan lama:** foto profil, tema per user, editor landing, Team Groups, reset password mandiri lewat email (tunggu email asli).

### 4.3 Kualitas & keamanan (sebaiknya sebelum/segera setelah go-live)

- **Tes otomatis:** 313 tes (308 lulus, 5 skip terdokumentasi) di 17 file, jalan dengan `php artisan test` di SQLite `:memory:` maupun MySQL/MariaDB — cakupan lengkap di Bab 2.3. Jalankan sebelum tiap deploy. Perbaiki 5 celah yang di-skip (satu di antaranya soal keamanan: HRD bisa membuat akun Owner lewat convert pelamar), lalu hapus baris `markTestSkipped`-nya. Belum ada tes unit murni dan belum ada tes browser (Dusk/Playwright) untuk UI, geolocation, dan kamera.
- **Rate limit login** sudah ada, tetapi tambahkan honeypot atau captcha sederhana pada form kontak & lamaran (saat ini hanya throttle per IP).
- **Security header** (CSP, X-Frame-Options, HSTS) belum ada; tambahkan lewat middleware atau `.htaccess`.
- **Log:** set `LOG_LEVEL=warning` dan rotasi harian di produksi.
- **Migrasi:** dua pasang migrasi bernama sama (`office_settings`, `attendances` create + alter) sebaiknya diberi nama yang membedakan; tidak memengaruhi fungsi.
- **Route manajer kosong** (`Team Overview`) atau isi, atau hapus.

### 4.4 Urutan kerja yang disarankan

Alasan urutan: langkah 1 mengubah versi paket, jadi semua tes berikutnya harus jalan di versi final; langkah 3 harus sebelum 4 karena Payroll memakai blok potongan dari Pengaturan Kantor; Royalty paling besar sehingga paling akhir.

1. **Turunkan target PHP ke 8.3** (4.1 no. 1): set platform di `composer.json`, `composer update`, jalankan `php artisan test`.
2. **Password dan role developer** (4.2 no. 1 dan 4.1 no. 6), berurutan:
    1. kolom `must_change_password` + redirect paksa saat login;
    2. role `developer` (migrasi, route, sidebar, aturan Rekap) + guard akun Owner;
    3. halaman Reset Password di modul IT;
    4. seeder testing Ancha dan Arga;
    5. tes, panduan halaman, dan README.
3. **Pengaturan Kantor dan ritme mingguan Dashboard Owner** (4.2 no. 2 dan 3).
4. **Payroll** (4.2 no. 4), lalu perbarui baris uji di Bab 2.2.
5. **Project Budgeting** (4.2 no. 5).
6. **Royalty Dashboard** (4.2 no. 6).
7. **Upload CV dan link portofolio** (4.2 no. 7).
8. **Tutup celah yang di-skip di tes** (4.3). Yang soal HRD membuat akun Owner lewat convert pelamar sudah ikut tertutup di langkah 2; sisanya dikerjakan di sini.
9. **Persiapan deploy:** isi konten publik (4.1 no. 9), `.env` produksi (no. 2), ubah path `public/index.php` sesuai struktur cPanel (no. 3), `npm run build` (no. 4), ekspor SQL lalu impor lewat phpMyAdmin (no. 7), bersihkan paket upload (no. 8).
10. **Uji manual di staging/subdomain** per halaman per role (termasuk HP untuk absensi), baru buka ke publik.

---

## 5. Kesimpulan

WSM-Office **sudah menjadi aplikasi yang utuh dan stabil secara teknis**: seluruh 180 file PHP lolos lint, 40 migrasi dan seeder berjalan, dan 360 request uji (60 halaman × 6 sudut pandang) tidak menghasilkan satu pun error 500, dengan pembatasan akses yang berperilaku sesuai rancangan. Fitur inti harian dari prototype (absensi lengkap dengan geofence dan selfie, izin/cuti/lembur/koreksi dengan approval, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) sudah ada dan sesuai, ditambah rekrutmen, pusat Export/Import, dan panduan halaman (tombol "? Panduan" di tiap halaman dashboard) yang tidak ada di prototype.

Kesenjangan terbesar ada di **modul finansial**: Payroll ada tetapi memakai aturan potongan yang berbeda dan belum memotong hari tanpa absensi, sementara Budget dan Royalty baru berupa CRUD sederhana dibanding mesin recoupment di prototype. Ketiganya sudah dijadwalkan (Bab 4.2 no. 4–6). Halaman publik dan form lamaran juga belum siap tayang (konten placeholder, belum ada upload CV/portofolio).

Yang membuat aplikasi **belum layak dibuka ke publik saat ini** bukan kekurangan fitur, tetapi kesiapan deploy: paket `vendor/` yang masih mewajibkan PHP 8.4.1 padahal hosting mentok 8.3 (keputusan sudah ada), `.env` mode lokal/debug, penyesuaian `public/index.php` untuk struktur folder cPanel (keputusan sudah ada), aset Vite yang belum dibangun (menunggu deploy production), serta password default "password" untuk karyawan hasil import. Semuanya bisa diselesaikan dalam skala hari, bukan minggu.

**Ringkas:** fungsional ±80% dari prototype (±90% untuk fitur harian, ±50% untuk finansial); kesiapan produksi masih perlu 8 blocker tersisa (dari 9) pada Bab 4.1 sebelum go-live (dua di antaranya, PHP 8.3 dan struktur cPanel, keputusannya sudah diambil); blocker #5 sudah selesai dan teruji; tes otomatis kini menutup seluruh checklist manual A–K (313 tes, termasuk 10 tes panduan halaman dan 2 tes form koreksi absen) dan sudah menemukan + memperbaiki 5 bug (Bab 2.3).
