# WSM-Office (W.O.S 2.0) — Laravel

Sistem manajemen kantor internal Whisnu Santika Music (WSM), hasil implementasi dari prototype `WOS_2_0_App_v32` (HTML/JS satu file) ke aplikasi Laravel multi-halaman yang akan di-deploy dan diakses publik.

> **Audit dilakukan 2026-09-19** terhadap `WSM-Office.zip` (snapshot 2026-09-19) dan `WOS_2_0_App_v32.zip`. Metode: baca kode + tes langsung (lihat bagian _Yang diuji_ di Bab 2). Semua klaim di dokumen ini berasal dari kode atau hasil tes, bukan asumsi.
>
> **Pembaruan 2026-09-19 (batch 1):** blocker deploy #5 _File sensitif terbuka tanpa login_ sudah **selesai dan teruji** — lihat Bab 4.0.

**Legenda status:** ✅ ada & sesuai · ⚠️ ada tapi tidak sesuai / lebih sederhana · ❌ belum ada · ➕ tambahan (tidak ada di prototype)

---

## 1. Penjelasan Project

**WSM-Office** adalah aplikasi web internal untuk mengelola operasional kantor WSM: absensi, pengajuan izin/cuti/lembur, pelacakan pekerjaan, rapat, memo, KPI, kontrak, payroll, budget, royalti, legal, audit, dan rekrutmen. Selain area internal, aplikasi punya 5 halaman publik (beranda, tentang kami, layanan, karir, kontak) dan form lamaran kerja.

**Stack:** Laravel 13.30 · PHP (lihat catatan versi di Bab 4) · MySQL · Tailwind CSS v4 + Vite · Alpine.js · Leaflet (peta geofence absensi) · SweetAlert2 · Bootstrap Icons · `maatwebsite/excel` 4.0 (export/import) · `barryvdh/laravel-dompdf` 3.1 (PDF).

**Hosting target:** shared cPanel (Rumahweb), tanpa terminal. Konsekuensinya: tidak ada cron (rekonsiliasi absensi ikut trafik web lewat `AttendanceReconciler`), tidak bisa `artisan` di server, dan file build (`public/build`) harus di-upload manual.

**Konvensi kode:** primary key `id_<tabel>` tidak dipakai di snapshot ini (memakai `id` standar); validasi lewat kelas `FormRequest` (31 kelas di `app/Http/Requests`); akses berbasis **role** (`owner`, `manajer`, `hrd`, `karyawan`) untuk area `/owner` dan `/app`, dan akses berbasis **modul** (`dashboard_access`, level `view`/`manage`) untuk area `/dashboard`, rekap, approval, dan rekrutmen; `throttle` pada route tulis; audit log untuk aksi sensitif (payroll dll).

**Ukuran:** 153 route (71 GET, 82 tulis), 180 file PHP, 40 migrasi, 25 model, 104 view Blade, 3 seeder.

**Peran & akses ringkas**

| Peran               | Area utama                                                                                                                                           |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Publik / tamu       | Beranda, Tentang, Layanan, Karir + lamar, Kontak, Login                                                                                              |
| Semua akun internal | `/app/*` — absen, riwayat, pengajuan, lembur, koreksi, kalender tim, profil, Info dari Owner                                                         |
| Owner               | Semuanya: `/owner/*` (karyawan, organisasi, akses dashboard, pengaturan kantor, pesan kontak) + semua modul dashboard + boleh memutus semua approval |
| Manajer             | Approval bawahan langsung + modul yang diberikan Owner                                                                                               |
| HRD                 | Rekap absensi, rekrutmen, dan modul yang diberikan Owner (sengaja tidak ikut approve izin/cuti)                                                      |
| Karyawan            | `/app/*` + modul yang diberikan Owner (mis. Work Tracker `view`/`manage`)                                                                            |

Akses modul bersifat **data**, bukan hard-code: pada data demo, Manajer Kanaya tidak diberi modul `work` sehingga 403 di Work Tracker — itu perilaku benar, bukan bug.

---

## 2. Perbandingan Project vs Prototype

### 2.1 Halaman & fitur

| #                                                                 | Halaman / fitur                                                                                          | Prototype v32                                                                | Project Laravel                                                                                                                                       | Status                    | Siapa yang bisa akses                 |
| ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ------------------------------------- |
| **Auth & sesi**                                                   |                                                                                                          |                                                                              |                                                                                                                                                       |                           |                                       |
| 1                                                                 | Login                                                                                                    | Pilih nama + password                                                        | Email + password, 1 form semua role, throttle (429 teruji)                                                                                            | ✅ (beda disengaja)       | Tamu                                  |
| 2                                                                 | Lock Dashboard                                                                                           | `sessionStorage` (client)                                                    | Session server-side, per user                                                                                                                         | ✅ (lebih aman)           | Semua di area dashboard               |
| 3                                                                 | Ganti password                                                                                           | Ada                                                                          | Di halaman Profil                                                                                                                                     | ✅                        | Semua akun                            |
| 4                                                                 | Lupa/reset password                                                                                      | —                                                                            | Belum ada                                                                                                                                             | ❌ (ditunda)              | —                                     |
| **Aplikasi karyawan (`/app`)**                                    |                                                                                                          |                                                                              |                                                                                                                                                       |                           |                                       |
| 5                                                                 | Home (kartu absensi, KPI, milestone, sisa cuti, Team Moments, My Work Tracker, Info dari Owner)          | Ada                                                                          | Ada semua                                                                                                                                             | ✅                        | Semua akun                            |
| 6                                                                 | Absen masuk/pulang: kantor, WFH, lapangan, gigs (multi-sesi)                                             | Ada                                                                          | Ada; geofence dihitung ulang di server (Haversine), selfie (disimpan private), auto-close sesi lupa pulang                                            | ✅                        | Semua akun                            |
| 7                                                                 | Riwayat absensi                                                                                          | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Semua akun                            |
| 8                                                                 | Pengajuan izin/cuti + batal                                                                              | Ada                                                                          | Ada; persetujuan atasan langsung                                                                                                                      | ✅                        | Semua akun                            |
| 9                                                                 | Lembur                                                                                                   | Ada                                                                          | Ada; tarif flat per pengajuan disetujui                                                                                                               | ✅                        | Semua akun                            |
| 10                                                                | Koreksi presensi                                                                                         | Ada                                                                          | Ada + halaman approval                                                                                                                                | ✅                        | Semua akun / Manajer & Owner memutus  |
| 11                                                                | Kalender tim                                                                                             | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Semua akun                            |
| 12                                                                | Memo: baca, sembunyikan, balas thread                                                                    | Ada                                                                          | Ada (tabel `memo_reads`, `memo_thread_messages`)                                                                                                      | ✅                        | Sesuai audiens memo                   |
| 13                                                                | Profil                                                                                                   | Ada + foto profil                                                            | Tanpa foto profil                                                                                                                                     | ⚠️ (foto ditunda)         | Semua akun                            |
| 14                                                                | Tema per user                                                                                            | Ada                                                                          | Belum                                                                                                                                                 | ❌ (ditunda)              | —                                     |
| **Area Owner (`/owner`)**                                         |                                                                                                          |                                                                              |                                                                                                                                                       |                           |                                       |
| 15                                                                | Dashboard Owner                                                                                          | Ringkasan + ritme mingguan bisa diedit                                       | Ringkasan ada; **ritme mingguan hard-code** di controller                                                                                             | ⚠️                        | Owner                                 |
| 16                                                                | Kelola karyawan (CRUD, role, atasan, gaji, soft delete)                                                  | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Owner                                 |
| 17                                                                | Organization (org-chart)                                                                                 | Ada                                                                          | Ada (dari `manager_id`)                                                                                                                               | ✅                        | Owner                                 |
| 18                                                                | Akses dashboard per modul (view/manage)                                                                  | Ada                                                                          | Ada, 10 modul                                                                                                                                         | ✅                        | Owner                                 |
| 19                                                                | Pengaturan kantor (geo, jam, warna)                                                                      | Jam, break, toleransi, blok potongan, jam lembur, auto-close, geo, warna     | Geo, jam kerja, tarif potongan, warna. **Tidak ada:** blok potongan (fix 60 mnt), jam mulai lembur, toggle auto-close                                 | ⚠️                        | Owner                                 |
| 20                                                                | Executive People Overview                                                                                | Ada                                                                          | Sengaja tidak dibuat                                                                                                                                  | ❌ (disengaja)            | —                                     |
| 21                                                                | Pesan kontak (dari form publik)                                                                          | —                                                                            | Ada                                                                                                                                                   | ➕                        | Owner                                 |
| **Modul dashboard (`/dashboard`, dijaga `module:x,view/manage`)** |                                                                                                          |                                                                              |                                                                                                                                                       |                           |                                       |
| 22                                                                | Work Tracker (board, item, PIC, progres, link)                                                           | Ada                                                                          | Ada + kanban + kalender                                                                                                                               | ✅                        | Modul `work`                          |
| 23                                                                | Import Work Tracker CSV/XLSX                                                                             | Ada (v32)                                                                    | Ada, dengan pratinjau sebelum konfirmasi                                                                                                              | ✅                        | Modul `work` (manage)                 |
| 24                                                                | Projects                                                                                                 | Ada                                                                          | Ada, digabung ke Work Tracker (warna per project)                                                                                                     | ✅                        | Modul `work`                          |
| 25                                                                | Timeline Calendar                                                                                        | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `work`                          |
| 26                                                                | MoM / Meeting + action item                                                                              | Ada                                                                          | Ada, action item terhubung ke Work Item, cetak PDF                                                                                                    | ✅                        | Modul `work`                          |
| 27                                                                | Memo Forum (admin)                                                                                       | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `work`                          |
| 28                                                                | Assign / Reminder dari dashboard                                                                         | Ada                                                                          | Belum ada                                                                                                                                             | ❌                        | —                                     |
| 29                                                                | Rekap absensi (+ detail per orang, koreksi manual)                                                       | Ada                                                                          | Ada + export Excel/PDF                                                                                                                                | ✅                        | Modul `people`                        |
| 30                                                                | Approval izin/cuti, lembur, koreksi presensi                                                             | Ada + Management Override                                                    | Ada; Owner boleh memutus siapa saja (= override); HRD tidak ikut                                                                                      | ✅                        | Manajer (bawahan), Owner              |
| 31                                                                | KPI & Performance                                                                                        | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `kpi`                           |
| 32                                                                | Employee Contracts                                                                                       | Ada (file, tanggal, catatan)                                                 | Ada; file di disk private, dibuka lewat route berotorisasi                                                                                            | ✅                        | Modul `contracts`                     |
| 33                                                                | Payroll                                                                                                  | Estimasi on-the-fly: hari absen × gaji÷22, kurang jam × tarif/jam, THP min 0 | Disimpan per bulan (draft → final → paid). **Tidak memotong hari tanpa absensi**, potongan = blok 60 mnt × tarif flat, total tidak dibatasi minimal 0 | ⚠️                        | Modul `payroll`                       |
| 34                                                                | Slip payroll PDF                                                                                         | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `payroll`                       |
| 35                                                                | Project Budgeting                                                                                        | Budget vs actual per project                                                 | CRUD flat per baris                                                                                                                                   | ⚠️ (lebih sederhana)      | Modul `budget`                        |
| 36                                                                | Royalty Dashboard                                                                                        | Waterfall recoupment per lagu, ledger kuartal, sinkron Google Sheet          | CRUD flat: gross, share %, recoup, status bayar. Tanpa waterfall/ledger/sinkron                                                                       | ⚠️ (jauh lebih sederhana) | Modul `royalty`                       |
| 37                                                                | Legal: Album Contracts & Royalty Agreements                                                              | Ada                                                                          | Ada (1 tabel, dibedakan `category`)                                                                                                                   | ✅                        | Modul `legal`                         |
| 38                                                                | IT: Audit Log                                                                                            | Ada                                                                          | Ada (read-only)                                                                                                                                       | ✅                        | Modul `it`                            |
| 39                                                                | IT: System Change Log                                                                                    | Ada                                                                          | Ada (CRUD)                                                                                                                                            | ✅                        | Modul `it`                            |
| 40                                                                | Team Overview manajer                                                                                    | —                                                                            | Grup route kosong (TODO)                                                                                                                              | ❌                        | —                                     |
| 41                                                                | Team Groups, editor landing page                                                                         | Ada                                                                          | Belum ada                                                                                                                                             | ❌ (ditunda)              | —                                     |
| **Tambahan (tidak ada di prototype)**                             |                                                                                                          |                                                                              |                                                                                                                                                       |                           |                                       |
| 42                                                                | Rekrutmen: lowongan + pipeline pelamar + konversi jadi karyawan                                          | —                                                                            | Ada                                                                                                                                                   | ➕                        | Modul `recruitment` (HRD, Owner)      |
| 43                                                                | Export & Import Center (14 export: Excel 11 + PDF 3; 4 import: Work Tracker, Karyawan, KPI, Budget)      | Hanya import Work Tracker                                                    | Ada, berbasis katalog & gerbang modul                                                                                                                 | ➕                        | Sesuai modul masing-masing            |
| 44                                                                | Halaman publik (Beranda, Tentang, Layanan, Kontak)                                                       | Ada editor landing                                                           | Ada, tetapi **teks masih placeholder**                                                                                                                | ⚠️                        | Publik                                |
| 45                                                                | Karir publik + form lamaran                                                                              | —                                                                            | Ada; **belum ada upload CV & link portofolio**                                                                                                        | ⚠️                        | Publik                                |
| 46                                                                | Form kontak publik                                                                                       | —                                                                            | Tersimpan ke DB (`contact_messages`), throttle                                                                                                        | ✅                        | Publik                                |
| 47                                                                | Panduan halaman dashboard: tombol "? Panduan" + modal informasi di setiap halaman dashboard (44 panduan) | —                                                                            | Ada; teks di `config/page_guides.php`, tombol muncul otomatis lewat layout                                                                            | ➕                        | Mengikuti akses halaman masing-masing |

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
| Generate payroll untuk karyawan tanpa satu pun absensi di bulan itu                                                         | ⚠️ potongan = 0, gaji penuh Rp 6.500.000 dibayar                                                                                                                                                                      |
| **Batch 1** — `PrivateFileAccessTest` (20 tes, 77 assertion, termasuk 2 stub bawaan) di SQLite `:memory:` dan MariaDB       | ✅ lulus: tamu → `/login`; tanpa modul → 403; scope selfie mengikuti rekap; file tersimpan hanya di disk private; hapus membersihkan salinan lama; path traversal ditolak; halaman tidak lagi memuat link `/storage/` |
| **Batch 1** — uji mutasi (sengaja merusak: gerbang modul dicabut, scope selfie dicabut, view kembali ke `/storage/`)        | ✅ tiap kerusakan ditangkap tepat 1 tes; kode dipulihkan, 18/18 lulus lagi                                                                                                                                            |
| **Batch 1** — Intelephense (language server, semua severity) pada 10 file PHP yang diubah/ditambah                          | ✅ 0 diagnostik (sebelumnya 12 `P1013` di `PrivateFile.php` dan tes; sudah diperbaiki dengan tipe `FilesystemAdapter`)                                                                                                |
| **Batch 1** — smoke test ulang 60 URL × 6 peran setelah perubahan                                                           | ✅ 0 error 500, hasil identik dengan sebelum perubahan                                                                                                                                                                |

**Belum diuji otomatis:** tampilan/UI di browser (layout, modal Alpine, drag-and-drop board, responsif), izin geolocation & kamera di HP, file picker browser sungguhan (yang diuji: upload tiruan), tampilan visual file Excel/PDF (isinya sudah dibaca ulang di tes), pengiriman email, dan MySQL asli (diganti MariaDB 10.11). Sejak 2026-09-20 seluruh checklist manual A–K yang berupa input/edit/hapus sudah otomatis (lihat 2.3); yang tersisa untuk dicek manual sebelum go-live hanya hal-hal di atas.

### 2.3 Tes otomatis (Batch 2 — 2026-09-20)

Menggantikan checklist tes manual di browser (bagian A–K pada README versi commit `634b65d`). Kode seperti `C5` atau `H4` di komentar tiap file tes mengacu ke nomor butir checklist itu.

**Menjalankan:** `php artisan test` (SQLite `:memory:`, tanpa setup apa pun; `public/hot` dan `public/build` tidak perlu ada). Untuk MySQL/MariaDB: buat database kosong khusus tes lalu `DB_CONNECTION=mysql DB_DATABASE=wsm_test DB_USERNAME=... DB_PASSWORD=... php artisan test` — jangan arahkan ke database aplikasi, `RefreshDatabase` menghapus isinya.

**Hasil:** 301 tes = **296 lulus + 5 dilewati (skip) yang sengaja mendokumentasikan celah yang belum diperbaiki**, 2.409 assertion, ±16 detik (SQLite) / ±18 detik (MariaDB). Hasil identik di SQLite dan MariaDB 10.11 (PHP 8.4.25). _Batch 3 (Bab 2.5) menambah `PageGuideTest`: total kini 311 tes, lihat 2.5._

| File tes                | Tes | Mencakup (butir checklist)                                                                                                                                                   |
| ----------------------- | --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PublicPagesTest`       | 13  | A1–A7: halaman publik, karir, form lamar, form kontak, throttle 429, 404, halaman terproteksi → `/login`                                                                     |
| `AuthenticationTest`    | 20  | B1–B9, C23, F13: login salah/brute-force, redirect per role, intended URL, remember me, logout, akun nonaktif, ganti password, kunci dashboard                               |
| `AttendanceFlowTest`    | 30  | C5–C15: geofence, WFH, Lapangan/Gigs multi-sesi, selfie, bentrok cuti, auto-close lupa pulang, riwayat bulanan, throttle                                                     |
| `EmployeeRequestsTest`  | 24  | C16–C21: cuti (kuota, akhir pekan, batal), lembur, koreksi presensi (validasi, batal, hanya milik sendiri)                                                                   |
| `ApprovalFlowTest`      | 20  | D1–D9: setujui/tolak/batalkan, wewenang atasan langsung vs Owner vs HRD, audit log, dampak ke saldo cuti/absen/shortage                                                      |
| `AttendanceRecapTest`   | 13  | E1–E5: cakupan rekap per akun, ringkasan harian, detail bulanan, koreksi manual (view vs manage)                                                                             |
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

| #   | Langkah                                                                                     | Status                               |
| --- | ------------------------------------------------------------------------------------------- | ------------------------------------ |
| 1   | Petakan halaman dashboard dan baca fitur tiap halaman langsung dari kode                    | ✅                                   |
| 2   | Peta route → panduan dan teks 44 panduan (`config/page_guides.php`, 54 pola route)          | ✅                                   |
| 3   | Pencari panduan (`PageGuide`), tombol + modal (`partials/page-guide`), di-include di layout | ✅                                   |
| 4   | Tes otomatis `PageGuideTest` + seluruh tes lama tetap hijau                                 | ✅ 311 tes: 306 lulus + 5 skip       |
| 5   | Cek tampilan di layar HP dan desktop sungguhan                                              | ⬜ perlu mata manusia                |
| 6   | `npm run build` lalu upload ulang `public/build` (ada class Tailwind baru)                  | ⬜ dilakukan di lokal sebelum deploy |

**Hasil:** modal responsif (bottom-sheet di HP, di tengah layar di desktop), isi panjang scroll di dalam modal sementara judul dan tombol tutup tetap terlihat. Panduan dicari dari nama route, jadi **tidak ada view halaman yang diubah**; satu-satunya perubahan di layout adalah satu baris `@include`. Halaman modul juga menampilkan label akses pengguna ("Akses kamu: Manage" atau "View"), halaman Owner berlabel "Khusus Owner". Teks panduan ditulis dalam bahasa Indonesia dan dicocokkan dengan perilaku kode yang ada. Diuji di SQLite (PHP 8.3.6): 311 tes, 3.661 assertion, ±15 detik; belum diuji ulang di MariaDB.

**Menambah atau mengubah panduan:** edit `config/page_guides.php`. Halaman baru cukup ditambah satu baris di `routes` (nama route → kunci) dan satu entri di `guides`; `PageGuideTest` akan gagal kalau halaman dashboard baru belum punya panduan. Halaman tanpa panduan tidak menampilkan tombol dan tidak error.

**Belum termasuk:** halaman aplikasi karyawan (`/app`, layout terpisah) belum punya tombol panduan.

**Ditemukan saat menulis panduan (belum diperbaiki):** form "Koreksi jam absen" di `attendance/recap/show.blade.php` tampil untuk semua yang bisa membuka halaman, termasuk akses People level View, padahal route penyimpanannya mewajibkan Manage (akan kena 403). Panduan sudah menyebut bahwa koreksi butuh akses Manage.

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
├── Feature/                                # 16 file, 310 tes (lihat tabel Bab 2.3 dan 2.5)
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

### 4.0 Status pengerjaan blocker

| #    | Blocker                                                      | Status                                                                                     |
| ---- | ------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| 1    | Versi PHP                                                    | ⬜ menunggu keputusan versi PHP Rumahweb                                                   |
| 2    | `.env` produksi                                              | ⬜                                                                                         |
| 3    | Struktur folder cPanel                                       | ⬜ (file private hanya aman jika ini benar: folder `storage/` harus di luar `public_html`) |
| 4    | Aset Vite                                                    | ⬜                                                                                         |
| 5 ✅ | **File sensitif terbuka tanpa login** — _SELESAI, lihat 4.0_ | ✅ **Selesai (batch 1)**                                                                   |
| 6    | Password default "password"                                  | ⬜                                                                                         |
| 7    | Database tanpa terminal                                      | ⬜                                                                                         |
| 8    | Bersihkan paket upload                                       | ⬜                                                                                         |
| 9    | Konten publik placeholder                                    | ⬜                                                                                         |

**Hasil batch 1 (blocker #5):** kontrak karyawan, dokumen legal, dan selfie absensi kini tersimpan di `storage/app/private` dan tidak punya URL publik. Semuanya hanya bisa dibuka lewat route yang mewajibkan login + akses modul yang sama dengan halaman pemiliknya (`contracts`/`legal` level view; selfie: modul `people` dengan scope rekap, yaitu Owner/HRD semua orang, selain itu diri sendiri + bawahan). File yang sudah pernah diupload ke disk publik dipindah sekali dengan `php artisan files:privatize` (path di database tidak berubah). Selfie baru juga divalidasi: harus gambar sungguhan dan maksimal 4 MB. Kebutuhan `storage:link` hilang, jadi tidak perlu terminal di server. Bukti: 18 tes otomatis + uji mutasi + smoke test ulang (Bab 2.2).

**Perbaikan susulan (tes gagal di SQLite):** `php artisan test` di lokal gagal semua karena dua migrasi (`add_legal_and_it_modules_to_dashboard_access` dan `add_recruitment_module_to_dashboard_access`) memakai raw `ALTER TABLE ... MODIFY`, sintaks khusus MySQL, sedangkan `phpunit.xml` memakai SQLite. Keduanya diganti ke `Schema::table()->enum()->change()` bawaan Laravel. Terverifikasi: skema tabel `dashboard_access` di MariaDB identik dengan sebelumnya (`SHOW CREATE TABLE` di-diff), rollback dan migrate ulang jalan, dan 20 tes lulus di SQLite serta MariaDB.

**Yang perlu kamu lakukan di lokal sebelum deploy:** jalankan `php artisan files:privatize --dry-run` untuk melihat rencana, lalu `php artisan files:privatize`. Jika belum ada file lama, hasilnya 0 file dan tidak ada yang perlu dilakukan.

### 4.1 Blocker deploy (wajib beres sebelum publik)

| #   | Masalah                               | Bukti                                                                                                                                                                                       | Tindakan                                                                                                                                                                                   |
| --- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | **Versi PHP**                         | `vendor/composer/platform_check.php` menolak PHP < 8.4.1 karena `symfony/clock`, `css-selector`, `event-dispatcher`, `string`, `translation` v8.1.x, padahal `composer.json` menulis `^8.3` | Cek apakah Rumahweb menyediakan PHP 8.4. Jika hanya 8.3: set `config.platform.php = 8.3.0` lalu `composer update` agar paket Symfony turun ke 7.4                                          |
| 2   | **`.env` produksi**                   | Isi saat ini `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL` localhost, DB user `root`                                                                                                         | Buat `.env` baru: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, user DB khusus, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`                                    |
| 3   | **Struktur folder cPanel**            | Tidak ada `.htaccess` di root; jika project diletakkan utuh di `public_html`, `.env` dan `app/` bisa diakses lewat URL                                                                      | Taruh project di luar `public_html`, isi `public/` ke `public_html`, sesuaikan path di `index.php`                                                                                         |
| 4   | **Aset Vite**                         | `public/hot` ada, `public/build` tidak ada                                                                                                                                                  | Hapus `hot`, jalankan `npm run build` lokal, upload `public/build`                                                                                                                         |
| 5   | **File sensitif terbuka tanpa login** | Kontrak karyawan, dokumen legal, selfie absensi disimpan di disk `public` dan dilink `asset('storage/...')`                                                                                 | Pindah ke disk `local` (private) + route unduh yang mengecek modul/pemilik. Sekaligus menghilangkan kebutuhan `storage:link` yang tidak bisa dijalankan tanpa terminal                     |
| 6   | **Password default "password"**       | `EmployeeImport` mengisi "password" jika kosong; tidak ada paksaan ganti                                                                                                                    | Tambah kolom `must_change_password` dan redirect paksa ke ganti password saat login pertama                                                                                                |
| 7   | **Database tanpa terminal**           | `migrate` tidak bisa dijalankan di server                                                                                                                                                   | Jalankan `migrate` + `OfficeSettingSeeder` lokal, ekspor SQL, impor via phpMyAdmin. **Jangan** jalankan `DemoSeeder`. Migrasi enum sekarang portable (MySQL/MariaDB dan SQLite), lihat 4.0 |
| 8   | **Bersihkan paket upload**            | Zip berisi `.git`, `node_modules`, `laravel.log`, `database.sqlite`, cache view, `fonts-manifest.dev.json`, `AGENTS.md`, `CLAUDE.md`                                                        | Jangan di-upload                                                                                                                                                                           |
| 9   | **Konten publik placeholder**         | Beranda, Tentang, Layanan, Kontak masih berisi teks "Placeholder" (alamat, telepon, email)                                                                                                  | Isi konten asli (tetap statis di Blade sesuai keputusan)                                                                                                                                   |

### 4.2 Fitur sesuai keputusan yang belum dibangun

1. **Payroll potong hari tanpa absensi** dan tetap bisa dikoreksi sampai hari gaji. Rekomendasi: hitung hari kerja tanpa sesi absen dan tanpa izin/cuti/lembur disetujui sebagai "alpha"; potong dengan `gaji ÷ hari kerja`; batasi total minimal 0; izinkan `finalized` dibuka kembali (dengan audit log) sampai status `paid`.
2. **Lamaran kerja: upload CV + link portofolio.** Tambah kolom `cv_path`, `portfolio_url` di `job_applications`, validasi (pdf/doc, ukuran maks), simpan di disk **private**, tampilkan di panel pelamar dan export.
3. **Prioritas harian (absensi & Work Tracker):** fitur di sisi ini sudah ±90% sesuai; sisa gap utama adalah _Assign/Reminder_ dari dashboard dan ritme mingguan yang masih hard-code.
4. **Tampilan teks:** UI berbahasa Inggris, teks penting/rawan salah paham berbahasa Indonesia. Saat ini banyak label campur; rapikan saat mengisi konten publik.

### 4.3 Kualitas & keamanan (sebaiknya sebelum/segera setelah go-live)

- **Tes otomatis:** 311 tes (306 lulus, 5 skip terdokumentasi) di 17 file, jalan dengan `php artisan test` di SQLite `:memory:` maupun MySQL/MariaDB — cakupan lengkap di Bab 2.3. Jalankan sebelum tiap deploy. Perbaiki 5 celah yang di-skip (satu di antaranya soal keamanan: HRD bisa membuat akun Owner lewat convert pelamar), lalu hapus baris `markTestSkipped`-nya. Belum ada tes unit murni dan belum ada tes browser (Dusk/Playwright) untuk UI, geolocation, dan kamera.
- **Rate limit login** sudah ada, tetapi tambahkan honeypot atau captcha sederhana pada form kontak & lamaran (saat ini hanya throttle per IP).
- **Security header** (CSP, X-Frame-Options, HSTS) belum ada; tambahkan lewat middleware atau `.htaccess`.
- **Tarif potongan kekurangan jam** default 0: jika Owner belum mengisinya di Pengaturan Kantor, potongan diam-diam nol (form generate payroll sudah menampilkan peringatan).
- **Log:** set `LOG_LEVEL=warning` dan rotasi harian di produksi.
- **Migrasi:** dua pasang migrasi bernama sama (`office_settings`, `attendances` create + alter) sebaiknya diberi nama yang membedakan; tidak memengaruhi fungsi.
- **Route manajer kosong** (`Team Overview`) atau isi, atau hapus.
- **Pengaturan kantor:** jadikan blok potongan, jam lembur, dan auto-close bisa diatur agar sejajar dengan prototype.
- **Ditunda (sesuai keputusan):** foto profil, tema per user, editor landing, Team Groups, reset password mandiri (tunggu email asli).
- **Budget & Royalty yang setara prototype** (waterfall recoupment, ledger kuartal, sinkron Google Sheet): fitur terbesar yang tertinggal, tetapi diturunkan prioritasnya.

### 4.4 Urutan kerja yang disarankan

1. Putuskan versi PHP hosting (blocker 1) — ini menentukan sisa langkah.
2. ~~Blocker 5 (file private)~~ ✅ selesai. Berikutnya blocker 6 (ganti password paksa) — perubahan kode kecil dengan risiko besar.
3. Payroll (4.2 no. 1) dan CV upload (4.2 no. 2).
4. Isi konten publik, siapkan `.env` produksi, `npm run build`, impor SQL.
5. Uji manual di browser per halaman per role (termasuk HP untuk absensi) di staging/subdomain, baru buka ke publik.

---

## 5. Kesimpulan

WSM-Office **sudah menjadi aplikasi yang utuh dan stabil secara teknis**: seluruh 180 file PHP lolos lint, 40 migrasi dan seeder berjalan, dan 360 request uji (60 halaman × 6 sudut pandang) tidak menghasilkan satu pun error 500, dengan pembatasan akses yang berperilaku sesuai rancangan. Fitur inti harian dari prototype (absensi lengkap dengan geofence dan selfie, izin/cuti/lembur/koreksi dengan approval, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) sudah ada dan sesuai, ditambah rekrutmen, pusat Export/Import, dan panduan halaman (tombol "? Panduan" di tiap halaman dashboard) yang tidak ada di prototype.

Kesenjangan terbesar ada di **modul finansial**: Payroll ada tetapi memakai aturan potongan yang berbeda dan belum memotong hari tanpa absensi, sementara Budget dan Royalty baru berupa CRUD sederhana dibanding mesin recoupment di prototype. Halaman publik dan form lamaran juga belum siap tayang (konten placeholder, belum ada upload CV/portofolio).

Yang membuat aplikasi **belum layak dibuka ke publik saat ini** bukan kekurangan fitur, tetapi kesiapan deploy: kebutuhan PHP 8.4.1, `.env` mode lokal/debug, struktur folder cPanel, aset Vite yang belum dibangun, serta password default "password" untuk karyawan hasil import. Semuanya bisa diselesaikan dalam skala hari, bukan minggu.

**Ringkas:** fungsional ±80% dari prototype (±90% untuk fitur harian, ±50% untuk finansial); kesiapan produksi masih perlu 8 blocker tersisa (dari 9) pada Bab 4.1 sebelum go-live; blocker #5 sudah selesai dan teruji; tes otomatis kini menutup seluruh checklist manual A–K (311 tes, termasuk 10 tes panduan halaman) dan sudah menemukan + memperbaiki 5 bug (Bab 2.3).
