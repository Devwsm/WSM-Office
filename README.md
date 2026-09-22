# WSM-Office (W.O.S 2.0) — Laravel

Sistem manajemen kantor internal Whisnu Santika Music (WSM), hasil implementasi dari prototype `WOS_2_0_App_v32` (HTML/JS satu file) ke aplikasi Laravel multi-halaman yang akan di-deploy dan diakses publik.

> **Status kode (dicek 2026-09-22):** 380 tes otomatis lulus (4.156 assertion, PHP 8.3.6, SQLite), 159 route, 42 migrasi. Isi dokumen ini dicocokkan dengan kode yang berjalan; kolom "Prototype v32" di Bab 2.1 bersumber dari audit terhadap prototype (file prototype tidak ada di repo).

**Legenda status:** ✅ ada & sesuai · ⚠️ ada tapi tidak sesuai / lebih sederhana · ❌ belum ada · ➕ tambahan (tidak ada di prototype)

---

## 1. Penjelasan Project

**WSM-Office** adalah aplikasi web internal untuk mengelola operasional kantor WSM: absensi, pengajuan izin/cuti/lembur, pelacakan pekerjaan, rapat, memo, KPI, kontrak, payroll, budget, royalti, legal, audit, dan rekrutmen. Selain area internal, aplikasi punya 5 halaman publik (beranda, tentang kami, layanan, karir, kontak) dan form lamaran kerja.

**Stack:** Laravel 13.30 · PHP 8.3 (batas cPanel Rumahweb; `vendor/` saat ini masih mewajibkan 8.4.1, lihat 4.1 no. 1) · MySQL · Tailwind CSS v4 + Vite · Alpine.js · Leaflet (peta geofence absensi) · SweetAlert2 · `maatwebsite/excel` 4.0 (export/import) · `barryvdh/laravel-dompdf` 3.1 (PDF).

**Hosting target:** shared cPanel (Rumahweb), tanpa terminal. Konsekuensinya: tidak ada cron (rekonsiliasi absensi ikut trafik web lewat `AttendanceReconciler`), tidak bisa `artisan` di server, dan file build (`public/build`) harus di-upload manual. Struktur folder di server: `public_html` hanya berisi isi folder `public/`, sisa project berada di luar, sejajar dengan `public_html` (Bab 4.0).

**Konvensi kode:** validasi lewat kelas `FormRequest` (31 kelas di `app/Http/Requests`); `/owner` untuk role `owner` dan `developer` (Dashboard Access khusus `owner`), `/app` untuk semua akun internal; akses berbasis **modul** (`dashboard_access`, level `view`/`manage`) untuk area `/dashboard`, rekap, approval, dan rekrutmen; `throttle` pada login, form publik (kontak, lamaran), absen, pengajuan, balasan memo, buka kunci dashboard, blast memo, import, dan reset password; audit log untuk aksi penting (karyawan, akses modul, approval, payroll, pengaturan kantor, import karyawan, reset password).

**Ukuran:** 159 route (76 GET, 83 tulis), 42 migrasi, 25 model, 31 FormRequest, 109 view Blade, 4 seeder, 380 tes.

**Peran & akses ringkas**

| Peran               | Area utama                                                                                                                                                                                                                                                                                                                                   |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Publik / tamu       | Beranda, Tentang, Layanan, Karir + lamar, Kontak, Login                                                                                                                                                                                                                                                                                      |
| Semua akun internal | `/app/*` — absen, riwayat, pengajuan, lembur, koreksi, kalender tim, profil, Info dari Owner                                                                                                                                                                                                                                                 |
| Owner               | Semuanya: `/owner/*` (karyawan, organisasi, akses dashboard, pengaturan kantor, pesan kontak) + semua modul dashboard + boleh memutus semua approval                                                                                                                                                                                         |
| Developer           | Akses Tingkat 2: `/owner/*` **kecuali** Dashboard Access, lihat absensi semua orang di Rekap, kelola karyawan non-Owner, reset password non-Owner, import/export karyawan. Modul dashboard-nya tetap dari akses modul yang diberikan Owner (bukan otomatis). **Tidak bisa** menyentuh akun Owner dan tidak bisa membuat Owner/Developer baru |
| Manajer             | Approval bawahan langsung + modul yang diberikan Owner                                                                                                                                                                                                                                                                                       |
| HRD                 | Rekap absensi, rekrutmen, dan modul yang diberikan Owner (sengaja tidak ikut approve izin/cuti)                                                                                                                                                                                                                                              |
| Karyawan            | `/app/*` + modul yang diberikan Owner (mis. Work Tracker `view`/`manage`)                                                                                                                                                                                                                                                                    |

**Role vs permission (sering membingungkan)**

|                    | Role                                                                                                                                                                                | Permission (akses modul)                                                       |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Apa itu            | Label jabatan di akun: `owner`, `developer`, `manajer`, `hrd`, `karyawan`                                                                                                           | Per modul: `none` / `view` / `manage` (tabel `dashboard_access`)               |
| Yang ditentukannya | Area `/owner` (Owner dan Developer; Dashboard Access khusus Owner); Owner punya akses penuh; Owner, HRD, dan Developer melihat semua orang di Rekap Absensi. Selebihnya hanya label | Menu dashboard yang muncul, dan boleh ubah data (`manage`) atau tidak (`view`) |
| Diatur di          | Karyawan → Edit → Role                                                                                                                                                              | Karyawan → Akses (hanya Owner)                                                 |

**Persetujuan izin/cuti/lembur/koreksi:** halaman persetujuan hanya bisa dibuka dengan akses modul `people`, tetapi siapa yang boleh memutuskan ditentukan oleh **atasan langsung** karyawan itu (field "Atasan Langsung"), dan Owner boleh memutus semuanya (Developer tidak otomatis: hanya untuk bawahan langsungnya). Cakupan Rekap Absensi untuk manajer adalah dirinya dan seluruh bawahan turunannya.

Akses modul bersifat **data**, bukan hard-code: pada data demo, Manajer Kanaya tidak diberi modul `work` sehingga 403 di Work Tracker — itu perilaku benar, bukan bug.

**Aturan akun Owner dan Developer** (dijaga di controller dan FormRequest, bukan cuma disembunyikan di tampilan):

- Developer boleh membuat, mengubah, menonaktifkan, dan mengaktifkan kembali akun apa pun **kecuali akun Owner**, dan hanya bisa memilih role Karyawan, Manajer, atau HRD. Akun Owner dan Developer baru hanya bisa dibuat Owner (lewat form Karyawan maupun import).
- Reset password (dashboard IT, butuh Manage `it`): akun sendiri tidak bisa direset (ganti lewat Profil), akun Owner hanya oleh Owner, akun Developer hanya oleh Owner atau Developer lain.
- Password sementara hasil reset tampil satu kali, tidak masuk audit log, dan semua sesi login lama akun itu dikeluarkan. Akun ditandai `must_change_password`: sebelum diganti, semua halaman selain Profil dan logout dialihkan ke Profil. Tanda yang sama dipasang pada akun hasil import dengan password kosong (default `password`).

**Akun testing lokal:** `php artisan db:seed --class=TestingAccountsSeeder` (jalankan setelah `DemoSeeder`; tidak ikut `DatabaseSeeder`, jangan dipakai di produksi). Membuat Ancha (`ancha@wsm.test`, manajer, Manage 10 modul, atasan langsung di puncak struktur sehingga Rekap-nya mencakup semua orang) dan Arga (`arga@wsm.test`, developer, Manage 10 modul); password keduanya `password`.

---

## 2. Perbandingan Project vs Prototype

### 2.1 Halaman & fitur

| #   | Halaman / fitur                                                                                            | Prototype v32                                                                 | Project Laravel                                                                                                                                                                                                                                       | Status                                                       | Siapa yang bisa akses                        |
| --- | ---------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ | -------------------------------------------- |
|     | **Auth & sesi**                                                                                            |                                                                               |                                                                                                                                                                                                                                                       |                                                              |                                              |
| 1   | Login                                                                                                      | Pilih nama + password                                                         | Email + password, 1 form semua role, throttle 5x/menit (429 teruji)                                                                                                                                                                                   | ✅ (beda disengaja)                                          | Tamu                                         |
| 2   | Lock Dashboard                                                                                             | `sessionStorage` (client)                                                     | Session server-side, per user                                                                                                                                                                                                                         | ✅ (lebih aman)                                              | Semua di area dashboard                      |
| 3   | Ganti password                                                                                             | Ada                                                                           | Di halaman Profil                                                                                                                                                                                                                                     | ✅                                                           | Semua akun                                   |
| 4   | Lupa/reset password                                                                                        | —                                                                             | Reset oleh tim IT: halaman Reset Password (Manage `it`) membuat password sementara yang tampil sekali, wajib diganti saat login berikutnya; sesi lama dikeluarkan; tercatat di Audit Log. Reset mandiri lewat email tetap ditunda (tunggu email asli) | ✅ (reset oleh IT)                                           | Modul `it` (Manage)                          |
|     | **Aplikasi karyawan (`/app`)**                                                                             |                                                                               |                                                                                                                                                                                                                                                       |                                                              |                                              |
| 5   | Home (kartu absensi, KPI, milestone, sisa cuti, Team Moments, My Work Tracker, Info dari Owner)            | Ada                                                                           | Ada semua                                                                                                                                                                                                                                             | ✅                                                           | Semua akun                                   |
| 6   | Absen masuk/pulang: kantor, WFH, lapangan, gigs (multi-sesi)                                               | Ada                                                                           | Ada; geofence dihitung ulang di server (Haversine), selfie (disimpan private), auto-close sesi lupa pulang                                                                                                                                            | ✅                                                           | Semua akun                                   |
| 7   | Riwayat absensi                                                                                            | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Semua akun                                   |
| 8   | Pengajuan izin/cuti + batal                                                                                | Ada                                                                           | Ada; persetujuan atasan langsung                                                                                                                                                                                                                      | ✅                                                           | Semua akun                                   |
| 9   | Lembur                                                                                                     | Ada                                                                           | Ada; tarif flat per tanggal lembur yang disetujui; boleh diajukan ulang setelah ditolak/dibatalkan (satu pengajuan aktif per tanggal)                                                                                                                 | ✅                                                           | Semua akun                                   |
| 10  | Koreksi presensi                                                                                           | Ada                                                                           | Ada + halaman approval                                                                                                                                                                                                                                | ✅                                                           | Semua akun / atasan langsung & Owner memutus |
| 11  | Kalender tim                                                                                               | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Semua akun                                   |
| 12  | Memo: baca, sembunyikan, balas thread                                                                      | Ada                                                                           | Ada (tabel `memo_reads`, `memo_thread_messages`); hanya audiens memo (memo aktif) yang bisa membaca, menyembunyikan, dan membalas                                                                                                                     | ✅                                                           | Sesuai audiens memo                          |
| 13  | Profil                                                                                                     | Ada + foto profil                                                             | Tanpa foto profil                                                                                                                                                                                                                                     | ⚠️ (foto ditunda)                                            | Semua akun                                   |
| 14  | Tema per user                                                                                              | Ada                                                                           | Belum                                                                                                                                                                                                                                                 | ❌ (ditunda)                                                 | —                                            |
|     | **Area Owner (`/owner`)**                                                                                  |                                                                               |                                                                                                                                                                                                                                                       |                                                              |                                              |
| 15  | Dashboard Owner                                                                                            | Ringkasan + ritme mingguan bisa diedit                                        | Ringkasan ada; **ritme mingguan hard-code** di controller (jam WFO-nya diambil dari Pengaturan Kantor)                                                                                                                                                | ⚠️ → dikerjakan (Bab 4.2 no. 3)                              | Owner, Developer                             |
| 16  | Kelola karyawan (CRUD, role, atasan, gaji, soft delete)                                                    | Ada                                                                           | Ada; Developer tidak bisa menyentuh akun Owner                                                                                                                                                                                                        | ✅                                                           | Owner, Developer (kecuali akun Owner)        |
| 17  | Organization (org-chart)                                                                                   | Ada                                                                           | Ada (dari `manager_id`)                                                                                                                                                                                                                               | ✅                                                           | Owner, Developer                             |
| 18  | Akses dashboard per modul (view/manage)                                                                    | Ada                                                                           | Ada, 10 modul                                                                                                                                                                                                                                         | ✅                                                           | Owner                                        |
| 19  | Pengaturan kantor (geo, jam, warna)                                                                        | Jam, break, toleransi, blok potongan, jam lembur, auto-close, geo, warna      | Lokasi & radius geofence, jam masuk/pulang normal, toleransi telat, target menit kerja, tarif potongan, warna. **Tidak ada:** blok potongan (tetap 60 mnt), jam mulai lembur, toggle auto-close                                                       | ⚠️ → dikerjakan (Bab 4.2 no. 2)                              | Owner, Developer                             |
| 20  | Executive People Overview                                                                                  | Ada                                                                           | Sengaja di-skip sejak awal: belum ada padanan halaman yang jelas (sebagian ringkasan people sudah ada di Dashboard Owner)                                                                                                                             | ❌ (disengaja)                                               | —                                            |
| 21  | Pesan kontak (dari form publik)                                                                            | —                                                                             | Ada                                                                                                                                                                                                                                                   | ➕                                                           | Owner, Developer                             |
|     | **Modul dashboard (`/dashboard`, dijaga `module:x,view/manage`)**                                          |                                                                               |                                                                                                                                                                                                                                                       |                                                              |                                              |
| 22  | Work Tracker (board, item, PIC, progres, link)                                                             | Ada                                                                           | Ada + kanban + kalender                                                                                                                                                                                                                               | ✅                                                           | Modul `work`                                 |
| 23  | Import Work Tracker CSV/XLSX                                                                               | Ada (v32)                                                                     | Ada, dengan pratinjau sebelum konfirmasi                                                                                                                                                                                                              | ✅                                                           | Modul `work` (manage)                        |
| 24  | Projects                                                                                                   | Ada                                                                           | Ada, digabung ke Work Tracker (warna per project)                                                                                                                                                                                                     | ✅                                                           | Modul `work`                                 |
| 25  | Timeline Calendar                                                                                          | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Modul `work`                                 |
| 26  | MoM / Meeting + action item                                                                                | Ada                                                                           | Ada, action item terhubung ke Work Item, cetak PDF                                                                                                                                                                                                    | ✅                                                           | Modul `work`                                 |
| 27  | Memo Forum (admin)                                                                                         | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Modul `work`                                 |
| 28  | Assign / Reminder dari dashboard                                                                           | Ada (detail persisnya belum terdokumentasi; file prototype tidak ada di repo) | Belum ada. Di kode baru ada kolom `is_reminder` pada task dan section "REMINDER / ADMIN"; belum ada aksi khusus di dashboard                                                                                                                          | ❌ (perlu dijelaskan dulu, Bab 4.2 bagian Belum dijadwalkan) | —                                            |
| 29  | Rekap absensi (+ detail per orang, koreksi manual)                                                         | Ada                                                                           | Ada + export Excel/PDF; form koreksi jam hanya tampil untuk akses Manage                                                                                                                                                                              | ✅                                                           | Modul `people` (koreksi: Manage)             |
| 30  | Approval izin/cuti, lembur, koreksi presensi                                                               | Ada + Management Override                                                     | Ada; halaman butuh akses modul `people`, yang boleh memutuskan hanya atasan langsung atau Owner (= override); HRD tidak ikut                                                                                                                          | ✅                                                           | Modul `people` + atasan langsung / Owner     |
| 31  | KPI & Performance                                                                                          | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Modul `kpi`                                  |
| 32  | Employee Contracts                                                                                         | Ada (file, tanggal, catatan)                                                  | Ada; file di disk private, dibuka lewat route berotorisasi                                                                                                                                                                                            | ✅                                                           | Modul `contracts`                            |
| 33  | Payroll                                                                                                    | Estimasi on-the-fly: hari absen × gaji÷22, kurang jam × tarif/jam, THP min 0  | Disimpan per bulan (draft → final → paid). **Tidak memotong hari tanpa absensi**, potongan = blok 60 mnt × tarif flat, total tidak dibatasi minimal 0                                                                                                 | ⚠️ → dikerjakan (Bab 4.2 no. 4)                              | Modul `payroll`                              |
| 34  | Slip payroll PDF                                                                                           | Ada                                                                           | Ada                                                                                                                                                                                                                                                   | ✅                                                           | Modul `payroll`                              |
| 35  | Project Budgeting                                                                                          | Budget vs actual per project                                                  | CRUD flat per baris                                                                                                                                                                                                                                   | ⚠️ (lebih sederhana) → dikerjakan (Bab 4.2 no. 5)            | Modul `budget`                               |
| 36  | Royalty Dashboard                                                                                          | Waterfall recoupment per lagu, ledger kuartal, sinkron Google Sheet           | CRUD flat: gross, share %, recoup, status bayar. Tanpa waterfall/ledger/sinkron                                                                                                                                                                       | ⚠️ (jauh lebih sederhana) → dikerjakan (Bab 4.2 no. 6)       | Modul `royalty`                              |
| 37  | Legal: Album Contracts & Royalty Agreements                                                                | Ada                                                                           | Ada (1 tabel, dibedakan `category`)                                                                                                                                                                                                                   | ✅                                                           | Modul `legal`                                |
| 38  | IT: Audit Log                                                                                              | Ada                                                                           | Ada (read-only)                                                                                                                                                                                                                                       | ✅                                                           | Modul `it`                                   |
| 39  | IT: System Change Log                                                                                      | Ada                                                                           | Ada (CRUD)                                                                                                                                                                                                                                            | ✅                                                           | Modul `it`                                   |
| 40  | Team Overview manajer                                                                                      | —                                                                             | Grup route kosong (TODO)                                                                                                                                                                                                                              | ❌                                                           | —                                            |
| 41  | Team Groups, editor landing page                                                                           | Ada                                                                           | Belum ada                                                                                                                                                                                                                                             | ❌ (ditunda)                                                 | —                                            |
|     | **Tambahan (tidak ada di prototype)**                                                                      |                                                                               |                                                                                                                                                                                                                                                       |                                                              |                                              |
| 42  | Rekrutmen: lowongan + pipeline pelamar + konversi jadi karyawan                                            | —                                                                             | Ada; role Owner hanya bisa dipilih oleh Owner saat konversi                                                                                                                                                                                           | ➕                                                           | Modul `recruitment` (HRD, Owner)             |
| 43  | Export & Import Center (15 export: Excel 12 + PDF 3; 4 import: Work Tracker, Karyawan, KPI, Budget)        | Hanya import Work Tracker                                                     | Ada, berbasis katalog & gerbang modul; Manajemen Karyawan (export/import) untuk Owner dan Developer                                                                                                                                                   | ➕                                                           | Sesuai modul masing-masing                   |
| 44  | Halaman publik (Beranda, Tentang, Layanan, Kontak)                                                         | Ada editor landing                                                            | Ada, tetapi **teks masih placeholder/generik**                                                                                                                                                                                                        | ⚠️                                                           | Publik                                       |
| 45  | Karir publik + form lamaran                                                                                | —                                                                             | Ada; **belum ada upload CV & link portofolio**                                                                                                                                                                                                        | ⚠️ → Bab 4.2 no. 7                                           | Publik                                       |
| 46  | Form kontak publik                                                                                         | —                                                                             | Tersimpan ke DB (`contact_messages`), throttle                                                                                                                                                                                                        | ✅                                                           | Publik                                       |
| 47  | Panduan halaman dashboard: tombol "? Panduan" + modal informasi di setiap halaman dashboard (44 panduan)   | —                                                                             | Ada; teks di `config/page_guides.php`, tombol muncul otomatis lewat layout                                                                                                                                                                            | ➕                                                           | Mengikuti akses halaman masing-masing        |
| 48  | Role Developer (akses Tingkat 2) dan Dashboard Access khusus Owner                                         | —                                                                             | Ada; aturan di Bab 1 (Aturan akun Owner dan Developer)                                                                                                                                                                                                | ➕                                                           | Developer                                    |
| 49  | Wajib ganti password sementara (hasil reset IT atau import dengan password default)                        | —                                                                             | Ada; halaman selain Profil dialihkan sampai password diganti                                                                                                                                                                                          | ➕                                                           | Akun yang ditandai                           |
| 50  | Popup informasi preview: peringatan preview di semua pintu masuk + sambutan "Welcome to W.O.S" di App Mode | —                                                                             | Ada (README Bab 4.2 no. 8); config/entry_popups.php + App\Support\EntryPopups, saklar WOS_PREVIEW_MODE/WOS_ENTRY_POPUPS                                                                                                                               | ➕                                                           | Mengikuti pintu masing-masing                |

**Hitungan status (50 baris):** ✅ 30 · ⚠️ 8 · ❌ 5 · ➕ 7.

**Estimasi kesesuaian dengan prototype** (kasar, berdasarkan bobot fitur): fitur harian inti (absensi, pengajuan, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) **±90%**; modul finansial (payroll, budget, royalty) **±45–55%**; halaman publik **belum siap tayang** karena konten placeholder.

### 2.2 Tes otomatis

Menggantikan checklist tes manual di browser (bagian A–K pada README versi commit `634b65d`). Kode seperti `C5` atau `H4` di komentar tiap file tes mengacu ke nomor butir checklist itu.

**Menjalankan:** `php artisan test` (SQLite `:memory:`, tanpa setup apa pun; `public/hot` dan `public/build` tidak perlu ada). Untuk MySQL/MariaDB: buat database kosong khusus tes lalu `DB_CONNECTION=mysql DB_DATABASE=wsm_test DB_USERNAME=... DB_PASSWORD=... php artisan test` — jangan arahkan ke database aplikasi, `RefreshDatabase` menghapus isinya.

**Hasil terakhir:** 380 tes lulus, 0 dilewati, 4.156 assertion, ±16 detik, di SQLite (PHP 8.3.6). Belum diuji ulang di MySQL/MariaDB asli setelah perubahan terbaru.

| File tes                | Tes | Mencakup (butir checklist)                                                                                                                                                                                                                                                        |
| ----------------------- | --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PublicPagesTest`       | 13  | A1–A7: halaman publik, karir, form lamar, form kontak, throttle 429, 404, halaman terproteksi → `/login`                                                                                                                                                                          |
| `AuthenticationTest`    | 20  | B1–B9, C23, F13: login salah/brute-force, redirect per role, intended URL, remember me, logout, akun nonaktif, ganti password, kunci dashboard                                                                                                                                    |
| `AttendanceFlowTest`    | 30  | C5–C15: geofence, WFH, Lapangan/Gigs multi-sesi, selfie, bentrok cuti, auto-close lupa pulang, riwayat bulanan, throttle                                                                                                                                                          |
| `EmployeeRequestsTest`  | 24  | C16–C21: cuti (kuota, akhir pekan, batal), lembur (termasuk diajukan ulang setelah ditolak/dibatalkan), koreksi presensi (validasi, batal, hanya milik sendiri)                                                                                                                   |
| `ApprovalFlowTest`      | 21  | D1–D9: setujui/tolak/batalkan, wewenang atasan langsung vs Owner vs HRD, audit log, dampak ke saldo cuti/absen/shortage, satu lembur disetujui per tanggal                                                                                                                        |
| `AttendanceRecapTest`   | 15  | E1–E5: cakupan rekap per akun, ringkasan harian, detail bulanan, koreksi manual (view vs manage, form hanya tampil untuk manage)                                                                                                                                                  |
| `OwnerAreaTest`         | 24  | F1–F12: CRUD karyawan, nonaktif/aktifkan (+ bawahan naik ke atasan), akses dashboard per modul, pengaturan kantor (16 aturan validasi), pesan kontak                                                                                                                              |
| `EmployeeAppTest`       | 21  | C1–C4, C24, K1–K4: Home, KPI/metrik, memo & inbox (audiens, aktif/nonaktif, hide/read, balas thread hanya untuk audiens), matriks modul, halaman 403                                                                                                                              |
| `WorkControlTest`       | 28  | G1–G13: Memo Forum, Work Tracker (project/task/progress), Timeline Calendar, Meetings/MoM + sync tracker + Blast                                                                                                                                                                  |
| `ManagementModulesTest` | 28  | H1–H14: KPI, kontrak, payroll (generate/regenerate/finalisasi/dibayar/hapus, lembur dihitung per tanggal), budget, royalty, legal, audit log, changelog, view vs manage                                                                                                           |
| `RecruitmentTest`       | 17  | I1–I7: lowongan (draft/terbit/tutup), pipeline pelamar, status, convert → akun (role Owner hanya untuk Owner), alur ujung ke ujung dari form publik                                                                                                                               |
| `ExportImportTest`      | 24  | J1–J10: menu per akses, semua export Excel/PDF dibuat lalu dibaca ulang, tidak ada hash password di export, template, preview → commit (KPI, budget, work tracker, karyawan), kolom wajib hilang, tanggal mustahil                                                                |
| `AccessMatrixTest`      | 27  | K1–K3: 21 URL × 5 akun seeder, smoke test seluruh GET tanpa parameter untuk 5 akun (> 200 request), sinkron dengan `DemoSeeder`                                                                                                                                                   |
| `PrivateFileAccessTest` | 18  | Akses file private (selfie, kontrak, dokumen legal)                                                                                                                                                                                                                               |
| `DeveloperRoleTest`     | 27  | Role developer: akses area Owner (kecuali Dashboard Access), aturan akun Owner/Developer (buat, ubah, nonaktifkan, aktifkan), role yang boleh dipilih, Rekap semua orang, approval hanya bawahan langsung, export/import karyawan, tanda wajib ganti password pada import default |
| `PasswordResetTest`     | 20  | Reset password IT (akses, aturan siapa boleh mereset siapa, password sementara tampil sekali dan tidak dicatat, sesi lama dihapus, throttle), wajib ganti password (pengalihan, alur login → ganti), dan `TestingAccountsSeeder`                                                  |
| `PageGuideTest`         | 11  | Peta route → panduan valid, tidak ada panduan yatim, semua halaman dashboard tanpa parameter punya tombol, label akses View/Manage/Khusus Owner/Owner & Developer, teks di-escape                                                                                                 |
| `EntryPopupsTest`       | 10  | Popup informasi preview (README Bab 4.2 no. 8): tampil per pintu & urutannya, kedua saklar, tidak muncul di halaman error/Kunci Dashboard/wajib ganti password, teks di-escape, smoke 5 akun                                                                                      |
| `ExampleTest` ×2        | 2   | Stub bawaan                                                                                                                                                                                                                                                                       |

Pendukung: `tests/TestCase.php` (mematikan Vite; meniru kolom `DATE` MySQL dan fungsi `FIELD()`/`DATE_FORMAT()` di SQLite — hanya di tes, kode aplikasi tidak disentuh untuk ini) dan `tests/Concerns/CreatesWsmFixtures.php` (5 akun standar + pengaturan kantor + waktu dibekukan ke Senin 2026-09-21).

Beberapa tes sengaja mengunci perilaku saat ini, mis. pengajuan cuti tumpang tindih belum diblokir (Bab 4.3). Kalau perilaku itu kelak diubah, tes-nya yang disesuaikan.

**Belum bisa dites otomatis:** tampilan/UI di browser (layout, modal Alpine, drag-and-drop board, responsif), izin geolocation & kamera di HP, file picker browser sungguhan (yang diuji: upload tiruan), tampilan visual file Excel/PDF (isinya dibaca ulang di tes), pengiriman email, dan MySQL asli. Hal-hal ini yang masih perlu dicek manual sebelum go-live.

### 2.3 Panduan tes untuk non-teknis: tiap tes ngapain dan apa yang dicek

**Apa itu "tes otomatis"?** Robot yang berperan jadi pengguna. Ia membuka halaman, mengisi form, menekan tombol, lalu memeriksa apakah hasilnya benar, persis yang dulu kamu lakukan manual di browser, tapi 370 skenario selesai dalam sekitar 16 detik dan tidak pernah lupa langkah. Semua dilakukan di **database sementara yang kosong** dan hilang begitu tes selesai, jadi data aplikasimu yang asli tidak tersentuh.

**Cara membaca hasil** setelah menjalankan `php artisan test`:

- **Hijau / PASS**: skenario berjalan sesuai harapan.
- **Merah / FAIL**: ada yang berubah dan tidak lagi sesuai. Artinya baru saja ada perubahan kode yang merusak sesuatu. Kalau perubahannya disengaja, tes-nya yang perlu disesuaikan; kalau tidak, ada bug yang baru muncul.
- **Kuning / SKIPPED**: tes yang sengaja dilewati karena ada masalah yang sudah diketahui tetapi belum diperbaiki. Saat ini tidak ada satu pun; kalau kelak muncul, itu pengingat untuk memperbaikinya.

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
- Lembur yang ditolak atau dibatalkan boleh diajukan lagi di tanggal yang sama; pengajuan kedua selagi yang pertama masih aktif ditolak.

**Persetujuan atasan (`ApprovalFlowTest`)**

- Atasan hanya melihat pengajuan bawahan langsungnya; Owner melihat semua; HRD tidak berwenang memutuskan.
- Menolak wajib disertai alasan, dan karyawan bisa membaca alasan itu.
- Pengajuan yang sudah diputuskan tidak bisa diputuskan dua kali.
- Setelah disetujui: jatah cuti berkurang, lembur menghapus kekurangan jam, koreksi absen langsung mengubah jam di data absen (jam aslinya disimpan).
- Setiap keputusan tercatat di audit log lengkap dengan siapa pelakunya.
- Satu karyawan hanya boleh punya satu lembur disetujui per tanggal, supaya uang lembur tidak dobel.

**Rekap absensi (`AttendanceRecapTest`)**

- Owner dan HRD melihat semua karyawan, manajer hanya timnya sendiri, karyawan biasa tidak punya akses.
- Ringkasan harian (hadir, terlambat, WFH, izin, belum absen) dihitung benar.
- Koreksi jam manual wajib disertai catatan, jam asli tetap tersimpan, dan hanya yang berhak "kelola" yang boleh mengoreksi.

**Area Owner (`OwnerAreaTest`)**

- Semua halaman Owner tertutup untuk selain Owner dan Developer (Dashboard Access hanya untuk Owner).
- Menambah, mengubah, menonaktifkan, dan mengaktifkan kembali karyawan (email tidak boleh kembar, password tersimpan terenkripsi, bawahan yang atasannya dinonaktifkan dipindah ke atasan di atasnya).
- Mengatur akses tiap modul dan memastikan efeknya langsung terasa: dicabut, langsung ditolak.
- Pengaturan kantor (lokasi, radius, jam kerja, warna) menolak nilai yang tidak masuk akal, dan perubahannya langsung dipakai absen berikutnya.
- Pesan dari form kontak publik muncul di kotak masuk Owner dan bisa ditandai sudah dibaca.

**Tampilan karyawan (`EmployeeAppTest`)**

- Home terbuka untuk semua peran; KPI yang tampil hanya milik sendiri.
- Memo: hanya memo aktif dan yang memang ditujukan kepadanya yang muncul, baik di Home maupun di Inbox. Memo bisa disembunyikan, ditandai baca, dan dibalas, tetapi hanya oleh orang yang memang termasuk audiensnya: yang bukan audiens ditolak (403) walaupun menebak alamat memonya.
- Karyawan yang tidak punya modul tertentu tidak melihat menunya dan mendapat halaman "tidak punya akses" kalau nekat membuka alamatnya.

**Work Control (`WorkControlTest`)**: memo, tracker, kalender, dan rapat.

- Membuat, mengubah, menonaktifkan, dan menghapus memo, untuk semua atau orang tertentu.
- Project dan task: dibuat, diedit, dihapus (menghapus project tidak menghapus task-nya), progress diubah lewat 6 status, dan tampil di daftar tugas si penanggung jawab.
- Rapat (MoM): peserta dan daftar tindak lanjut tersimpan; bisa dikirim otomatis jadi task; "Blast" mengubah notulen jadi memo untuk semua karyawan.
- Yang hanya punya akses lihat tidak bisa mengubah apa pun.

**Modul manajemen (`ManagementModulesTest`)**: KPI, kontrak, payroll, budget, royalty, legal, IT.

- Tiap modul: tambah, ubah, hapus, dan ditolaknya isian yang tidak wajar (angka minus, persentase lebih dari 100, tanggal selesai sebelum tanggal mulai).
- Unggah kontrak/dokumen: hanya PDF, Word, dan gambar sampai 10 MB; file lama dihapus saat diganti.
- Payroll: gaji dihitung dari gaji pokok + lembur disetujui (dihitung per tanggal, bukan per pengajuan) − potongan kekurangan jam; alurnya satu arah (draft → final → dibayar) dan yang sudah final tidak bisa diubah atau dihapus.
- Budget yang melebihi anggaran tampil sebagai selisih minus.
- Modul yang hanya boleh dilihat tidak bisa diubah.

**Rekrutmen (`RecruitmentTest`)**

- Lowongan: draft tidak tampil publik, terbit tampil, ditutup hilang lagi.
- Pelamar: bisa dicari dan difilter, statusnya bisa dimajukan.
- Pelamar yang diterima diubah jadi akun karyawan (hanya sekali) dan langsung bisa login.
- Hanya Owner yang boleh membuat akun ber-role Owner dari pelamar; HRD tidak bisa, baik lewat form (opsi Owner disembunyikan) maupun lewat kiriman langsung ke server.
- Satu skenario penuh dari awal: pelamar mengisi form → HR memproses → akun jadi → login berhasil.

**Export dan Import (`ExportImportTest`)**

- Setiap jenis laporan Excel/PDF benar-benar dibuat, lalu dibuka lagi untuk memastikan isinya benar.
- Export karyawan tidak pernah memuat password.
- Menu hanya menampilkan laporan yang boleh dilihat pengguna itu (kartu Manajemen Karyawan hanya untuk Owner dan Developer).
- Import: file dibaca dulu (pratinjau), baris yang salah ditandai dan **tidak** ikut tersimpan, baris yang benar baru masuk setelah dikonfirmasi. Konfirmasi hanya berlaku sekali dan hanya untuk orang yang mengunggah.
- File yang kehilangan kolom wajib ditolak dengan pesan kolom mana yang hilang; kolom opsional yang hilang dianggap kosong, bukan error.
- Tanggal yang mustahil (mis. 31/02/2026) ditolak dengan pesan jelas, tidak diam-diam digeser jadi 03/03/2026; tanggal sah seperti 29 Februari tahun kabisat tetap diterima.

**Role Developer (`DeveloperRoleTest`)**

- Developer bisa membuka halaman area Owner, tetapi tidak bisa membuka Dashboard Access.
- Developer bisa menambah, mengubah, menonaktifkan, dan mengaktifkan kembali akun karyawan, manajer, dan HRD, tetapi tidak bisa menyentuh akun Owner (buka form, ubah, nonaktifkan, aktifkan kembali semuanya ditolak).
- Developer tidak bisa membuat atau menaikkan akun jadi Owner atau Developer, baik lewat form maupun kiriman langsung ke server. Pilihan role di form menyesuaikan siapa yang membuka.
- Developer melihat semua orang di Rekap Absensi, tetapi hanya bisa menyetujui pengajuan bawahan langsungnya.
- Import karyawan oleh Developer menolak baris ber-role Owner atau Developer; oleh Owner diterima. Akun hasil import dengan password kosong wajib ganti password saat login pertama.
- Aturan siapa boleh mengelola atau mereset siapa dicek sebagai matriks di level model.

**Reset password dan wajib ganti password (`PasswordResetTest`)**

- Halaman Reset Password hanya bisa dibuka dengan akses Manage pada modul IT; menu dan tab-nya hanya muncul untuk mereka.
- Reset membuat password sementara acak yang tampil satu kali; kalau halaman dimuat ulang, password itu hilang dan tidak pernah masuk audit log.
- Semua sesi login lama akun yang direset dikeluarkan.
- Akun sendiri tidak bisa direset; akun Owner hanya oleh Owner; akun Developer hanya oleh Owner atau Developer lain.
- Akun yang wajib ganti password dialihkan ke Profil dari semua halaman lain (tetap bisa logout), password baru tidak boleh sama dengan yang sekarang, dan setelah diganti aplikasi terbuka lagi.
- Akun testing Ancha dan Arga dibuat lengkap dengan akses modul dan posisi di struktur, dan seeder aman dijalankan berulang.

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

### 2.4 Panduan halaman dashboard

Tiap halaman dashboard punya tombol **"? Panduan"** (melayang di kanan bawah). Ditekan, muncul modal berisi fungsi halaman, fitur yang bisa dipakai, dan hal yang perlu diketahui. Ditutup lewat ✕, tombol "Mengerti, tutup", tombol Esc, atau klik area gelap, sehingga pengguna tidak meninggalkan halaman. Modal responsif (bottom-sheet di HP, di tengah layar di desktop). Halaman modul juga menampilkan label akses pengguna ("Akses kamu: Manage" atau "View"), halaman area Owner berlabel "Owner & Developer", dan Dashboard Access berlabel "Khusus Owner".

Panduan dicari dari nama route lewat `App\Support\PageGuide`, jadi tidak ada view halaman yang diubah; satu-satunya keterkaitan di layout adalah satu baris `@include` (`partials/page-guide`). Isi: 45 panduan untuk 55 pola route, ditulis dalam bahasa Indonesia dan dicocokkan dengan perilaku kode.

**Menambah atau mengubah panduan:** edit `config/page_guides.php`. Halaman baru cukup ditambah satu baris di `routes` (nama route → kunci) dan satu entri di `guides`; `PageGuideTest` akan gagal kalau halaman dashboard baru belum punya panduan. Halaman tanpa panduan tidak menampilkan tombol dan tidak error. Setelah mengubah class Tailwind di view, jalankan ulang `npm run build` (Bab 4.1 no. 4).

**Belum termasuk:** halaman aplikasi karyawan (`/app`, layout terpisah) belum punya tombol panduan.

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
│   │   │   ├── DashboardController.php     # Landing dashboard per modul + halaman modul placeholder (masih dipakai kartu People & Recruitment, lihat 4.3)
│   │   │   ├── DashboardLockController.php # Lock/unlock dashboard (server-side)
│   │   │   ├── Budget/BudgetController.php       # CRUD project budgeting
│   │   │   ├── Contracts/ContractController.php  # CRUD kontrak karyawan + upload private + route file()
│   │   │   ├── ExportImport/
│   │   │   │   ├── ExportImportController.php    # Halaman pusat Export & Import
│   │   │   │   ├── ExportController.php          # Semua export (route generik per key/format)
│   │   │   │   └── ImportController.php          # Semua import (upload → pratinjau → konfirmasi)
│   │   │   ├── It/
│   │   │   │   ├── AuditLogController.php        # Daftar audit log (read-only)
│   │   │   │   ├── SystemChangelogController.php # CRUD changelog sistem
│   │   │   │   └── PasswordResetController.php   # Reset password karyawan oleh IT (password sementara tampil sekali)
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
│   │   ├── EnsureRole.php                  # Jaga route per role (owner/developer/manajer/hrd/karyawan)
│   │   ├── EnsureModuleAccess.php          # Jaga route per modul & level (view/manage)
│   │   ├── EnsureDashboardUnlocked.php     # Paksa layar unlock jika dashboard terkunci
│   │   └── EnsurePasswordChanged.php       # Paksa ganti password sementara (must_change_password), terpasang di grup web
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
│   └── AppServiceProvider.php              # Service provider utama (termasuk data modal Inbox memo di layout)
└── Support/
    ├── AttendanceReconciler.php            # Tutup paksa sesi lupa pulang (tanpa cron, ikut trafik web)
    ├── Geo.php                             # Jarak Haversine untuk geofence server-side
    ├── PageGuide.php                       # Cari panduan halaman dari nama route + label akses user (tombol "? Panduan")
    ├── EntryPopups.php                     # Siapkan urutan & isi popup informasi preview per pintu masuk (README Bab 4.2 no. 8)
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
    ├── entry_popups.php                    # Teks, versi, & saklar WOS_PREVIEW_MODE/WOS_ENTRY_POPUPS popup informasi preview (Bab 4.2 no. 8)
├── services.php
└── session.php                             # Session 120 menit, driver database

database/
├── factories/
│   └── UserFactory.php                     # Factory user untuk testing
├── migrations/                             # 42 migrasi berurutan (users → sesi, absensi, modul dashboard, rekrutmen, kontak, lembur)
│   ├── ..._dashboard_access.php            # Tabel akses modul
│   ├── ..._add_legal_and_it_modules_to_dashboard_access.php  # Tambah enum modul via Schema `->change()` (portabel MySQL/SQLite)
│   ├── ..._office_settings.php (2×)        # Create + tambahan kolom (nama file sama, membingungkan)
│   ├── ..._attendances.php (2×)            # Create + tambahan kolom
│   ├── ..._relax_overtime_requests_unique_index.php  # Lepas UNIQUE(user_id, date) lembur, diganti index biasa (pengajuan ulang setelah ditolak)
│   ├── ..._add_developer_role_and_must_change_password_to_users.php  # Role developer + kolom must_change_password
│   └── ...                                 # 30+ migrasi fitur lain (payroll, kpi, legal, audit, dst.)
├── seeders/
│   ├── DatabaseSeeder.php                  # Entry point seeding
│   ├── OfficeSettingSeeder.php             # Pengaturan kantor awal
│   ├── DemoSeeder.php                      # Data demo (5 user, password "password") — HANYA lokal
│   └── TestingAccountsSeeder.php           # Ancha (manajer) & Arga (developer), dijalankan manual setelah DemoSeeder — HANYA lokal
└── database.sqlite                         # Sisa setup awal (koneksi aktif MySQL) — hapus

resources/
├── css/app.css                             # Entry CSS (Tailwind v4)
├── js/
│   ├── app.js                              # Entry JS (Alpine.js)
│   ├── attendance.js                       # Geolocation, kamera selfie, peta Leaflet
│   ├── alerts.js                           # Konfirmasi SweetAlert
│   └── entry-popups.js                     # Antrean tampil + sessionStorage popup informasi preview (Bab 4.2 no. 8)
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
    │   ├── it/ (+changelog, password-resets)  # Audit log, changelog, reset password
    │   └── export-import/                  # Pusat export/import, picker, pratinjau
    ├── recruitment/                        # openings (lowongan), applications (pelamar, konversi)
    ├── memo/_thread.blade.php              # Thread balasan memo
    ├── pdf/                                # attendance-recap, meeting-minutes, payroll-slip, layout
    ├── components/org-node.blade.php       # Node org-chart rekursif
    ├── partials/flash-data.blade.php       # Data flash untuk SweetAlert
    ├── partials/page-guide.blade.php       # Tombol "? Panduan" + modal, di-include sekali dari layouts/app
    ├── partials/entry-popups.blade.php     # Popup informasi preview: shell + antrean Alpine, di-include per layout (public/employee/app/login)
    ├── partials/entry-popups/              # welcome.blade.php (Sambutan), notice.blade.php (Peringatan) — isi tiap tipe popup
    └── errors/                             # 403, 404, 500, 503

routes/
├── web.php                                 # 159 route: publik, /app, /owner, /dashboard, rekrutmen, approval
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
├── framework/views/                        # Cache Blade sisa dev, kosongkan
└── logs/laravel.log                        # ⚠ ±2 MB berisi path lokal `C:/Users/...`, jangan di-upload

tests/
├── TestCase.php                            # Dasar semua tes: withoutVite() + emulasi MySQL (DATE, FIELD, DATE_FORMAT) di SQLite
├── Concerns/CreatesWsmFixtures.php         # Akun standar (+ makeDeveloper), pengaturan kantor, waktu dibekukan (Senin 2026-09-21)
├── Feature/                                # 19 file, 379 tes (lihat tabel Bab 2.2)
│   ├── PublicPagesTest · AuthenticationTest · AttendanceFlowTest · EmployeeRequestsTest
│   ├── ApprovalFlowTest · AttendanceRecapTest · OwnerAreaTest · EmployeeAppTest
│   ├── WorkControlTest · ManagementModulesTest · RecruitmentTest · ExportImportTest · AccessMatrixTest
│   ├── PrivateFileAccessTest               # 18 tes akses file private
│   ├── PageGuideTest                       # 11 tes panduan halaman dashboard
│   ├── DeveloperRoleTest                   # 27 tes role developer
│   ├── PasswordResetTest                   # 20 tes reset password, wajib ganti password, seeder akun testing
│   ├── EntryPopupsTest                     # 10 tes popup informasi preview (README Bab 4.2 no. 8)
│   └── ExampleTest                         # Stub bawaan
└── Unit/ExampleTest.php                    # Stub bawaan — belum ada tes unit murni

(root) .env · .env.example · .gitignore · composer.json/lock · package.json · phpunit.xml · vite.config.js · .phpunit.result.cache
       AGENTS.md · CLAUDE.md               # File catatan repo, tidak perlu ikut di-deploy
```

---

---

## 4. Langkah Selanjutnya

### 4.0 Keputusan yang sudah diambil

| Topik                            | Keputusan                                                                                                                                                                                          |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Versi PHP hosting                | cPanel Rumahweb mentok di **PHP 8.3** (tidak ada 8.4). Tindakannya di 4.1 no. 1.                                                                                                                   |
| Struktur folder cPanel           | `public_html` hanya berisi isi folder `public/` project. Sisa project (`app/`, `vendor/`, `storage/`, `.env`, dst.) berada **di luar** `public_html`, sejajar dengannya. Tindakannya di 4.1 no. 3. |
| Status deployment                | Masih lokal, belum ada deploy production. Karena itu `public/build` belum dibangun (4.1 no. 4).                                                                                                    |
| Fitur yang dikerjakan berikutnya | Pengaturan Kantor, ritme mingguan Dashboard Owner, Payroll, Project Budgeting, Royalty Dashboard (4.2 no. 2–6). Payroll, Budgeting, dan Royalty ditandai penting/krusial. Urutannya di 4.4.        |
| Role developer                   | Sudah dibuat sebagai akses **Tingkat 2** (aturan di Bab 1). Ancha (office manager) memakai role `manajer` dengan Manage 10 modul (`TestingAccountsSeeder`).                                        |
| Panduan halaman dashboard        | Tampilan di HP dan desktop sudah dicek, aman (Bab 2.4).                                                                                                                                            |
| Popup informasi preview          | Selesai (2026-09-22, 4.2 no. 8): modal preview di publik+login, App Mode (+sambutan), dan Dashboard. Saat operasional, slot ini dipakai ulang untuk Himbauan.                                      |

### 4.1 Blocker deploy (wajib beres sebelum publik)

Satu tabel untuk status sekaligus tindakan. Penjelasan blocker 4, 8, dan 9 ada di bawah tabel.

| #   | Blocker                               | Apa artinya                                                                                                                                                                                                                                                       | Tindakan                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Status                                                     |
| --- | ------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------- |
| 1   | **Versi PHP**                         | Paket Symfony v8.1 di `vendor/` (`clock`, `css-selector`, `event-dispatcher`, `string`, `translation`) mewajibkan PHP ≥ 8.4.1 (`vendor/composer/platform_check.php` menolak jalan di bawahnya), padahal `composer.json` menulis `^8.3` dan hosting mentok di 8.3. | Set `config.platform.php = 8.3.0` di `composer.json`, jalankan `composer update` di lokal supaya paket Symfony turun ke 7.4, lalu `php artisan test`. Folder `vendor/` hasilnya ikut di-upload (server tanpa terminal tidak bisa `composer install`). Cek awal (2026-09-21): 370 tes lulus di PHP 8.3.6 saat platform check dimatikan; itu bukan pengganti `composer update`. **Samakan dulu konstrain `maatwebsite/excel` di `composer.json` ke `^4.0`:** sekarang tertulis `^3.1` padahal yang terpasang di lock 4.0.3, jadi `composer update` tidak akan mempertahankannya. | ⬜ keputusan ada, tinggal dikerjakan (4.4 langkah 1)       |
| 2   | **`.env` produksi**                   | `.env` yang ada khusus lokal: `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL` localhost, DB user `root`.                                                                                                                                                             | Buat `.env` baru: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, user DB khusus, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`, dan `WOS_PREVIEW_MODE=false` begitu sistem dipakai operasional (4.2 no. 8).                                                                                                                                                                                                                                                                                                                                           | ⬜                                                         |
| 3   | **Struktur folder cPanel**            | Kalau seluruh project ditaruh di `public_html`, `.env` dan `app/` bisa dibuka lewat URL (tidak ada `.htaccess` di root project).                                                                                                                                  | Sesuai keputusan (4.0): isi `public/` ke `public_html`, sisanya di luar sejajar `public_html`. Ubah 3 path di `public/index.php` (`maintenance.php`, `vendor/autoload.php`, `bootstrap/app.php`) dari `__DIR__.'/../...'` ke folder project, mis. `__DIR__.'/../wsm-office/...'`. Folder `storage/` otomatis ikut di luar `public_html`, dan itu syarat agar file private tetap aman.                                                                                                                                                                                          | ⬜ keputusan ada; path diubah saat deploy                  |
| 4   | **Aset Vite**                         | Halaman butuh file CSS/JS hasil "rakitan" Vite di `public/build/` (penjelasan di bawah). Saat ini `public/hot` ada dan `public/build` belum ada.                                                                                                                  | Hapus `public/hot`, jalankan `npm run build` di lokal, upload isi `public/build` ke `public_html/build`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | ⬜ ditunda sampai deploy production (saat ini masih lokal) |
| 5   | **File sensitif terbuka tanpa login** | Kontrak karyawan, dokumen legal, dan selfie absensi dulu disimpan di disk `public`.                                                                                                                                                                               | Dipindah ke disk private + route unduh yang mengecek modul/pemilik (catatan di bawah).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | ✅ **selesai (batch 1)**                                   |
| 6   | **Password default "password"**       | `EmployeeImport` mengisi "password" jika kolom kosong. Akun itu kini ditandai `must_change_password`, jadi dipaksa mengganti password saat login pertama.                                                                                                         | Tidak ada; sudah dikerjakan bersama reset password IT.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | ✅ **selesai**                                             |
| 7   | **Database tanpa terminal**           | `migrate` tidak bisa dijalankan di server.                                                                                                                                                                                                                        | Jalankan `migrate` + `OfficeSettingSeeder` di lokal, ekspor SQL, impor lewat phpMyAdmin. **Jangan** jalankan `DemoSeeder` dan `TestingAccountsSeeder`; keduanya hanya untuk lokal. Migrasi enum sudah portabel (MySQL/MariaDB dan SQLite).                                                                                                                                                                                                                                                                                                                                     | ⬜                                                         |
| 8   | **Bersihkan paket upload**            | Zip proyek berisi file dev/pribadi yang tidak boleh ikut ke server.                                                                                                                                                                                               | Ikuti daftar di bawah tabel.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   | ⬜                                                         |
| 9   | **Konten publik placeholder**         | Empat halaman publik masih berisi teks "Placeholder" atau teks generik.                                                                                                                                                                                           | Isi konten asli (tetap statis di Blade sesuai keputusan). Daftarnya di bawah tabel.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            | ⬜                                                         |

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

**Catatan blocker 5 (selesai):** kontrak karyawan, dokumen legal, dan selfie absensi tersimpan di `storage/app/private` dan tidak punya URL publik. Semuanya hanya bisa dibuka lewat route yang mewajibkan login + akses modul yang sama dengan halaman pemiliknya (`contracts`/`legal` level view; selfie: modul `people` dengan scope rekap, yaitu Owner/HRD semua orang, selain itu diri sendiri + bawahan). Kebutuhan `storage:link` hilang, jadi tidak perlu terminal di server. Tes: `PrivateFileAccessTest` (18 tes).

**Yang perlu kamu lakukan di lokal sebelum deploy:** jalankan `php artisan files:privatize --dry-run` untuk melihat rencana, lalu `php artisan files:privatize` (memindahkan file lama dari disk publik ke private; path di database tidak berubah). Jika belum ada file lama, hasilnya 0 file dan tidak ada yang perlu dilakukan.

### 4.2 Fitur yang akan dikerjakan

Butir 2–6 dan 8 adalah keputusan 2026-09-21 ("eksekusi"); butir 7 keputusan sebelumnya; butir 1 sudah selesai. Urutan pengerjaannya ada di 4.4 (butir 8 dikerjakan paling dulu).

1. ✅ **Reset password IT, role developer, wajib ganti password, dan seeder akun testing:** selesai. Lihat Bab 1 (aturan akun), Bab 2.1 (no. 4, 48, 49), dan Bab 2.2.
2. **Pengaturan Kantor:** jadikan blok potongan (sekarang tetap 60 menit), jam mulai lembur, dan toggle auto-close sesi lupa pulang bisa diatur, agar sejajar dengan prototype. Dikerjakan sebelum Payroll karena Payroll memakai blok potongan.
3. **Dashboard Owner:** ritme mingguan (fokus dan mode WFO/WFH per hari) sekarang tertulis langsung di controller; pindahkan ke database dan buat bisa diedit Owner.
4. **Payroll (penting).** Semua urusan payroll ada di butir ini:
    - Potong hari tanpa absensi ("alpha"): hari kerja tanpa sesi absen dan tanpa izin/cuti/lembur disetujui, dipotong `gaji ÷ hari kerja`.
    - Total dibatasi minimal 0 (sekarang bisa negatif).
    - Payroll `finalized` boleh dibuka kembali (dengan audit log) sampai berstatus `paid`, supaya tetap bisa dikoreksi sampai hari gaji.
    - Tarif potongan kurang jam default-nya 0; kalau belum diisi di Pengaturan Kantor, potongan diam-diam nol (form generate sudah menampilkan peringatan).
    - Setelah selesai, perbarui baris Payroll di Bab 2.1 (no. 33) dan tambahkan tes otomatisnya (sekarang karyawan tanpa satu pun absensi tetap dibayar penuh).
5. **Project Budgeting (krusial):** naikkan dari CRUD flat per baris ke budget vs actual per project seperti prototype. Rincian kekurangannya dicocokkan dengan prototype v32 saat mulai dikerjakan (prototype tidak ada di repo).
6. **Royalty Dashboard (krusial):** tambahkan yang belum ada dibanding prototype: waterfall recoupment per lagu, ledger kuartal, dan sinkron Google Sheet. Ini yang terbesar, jadi dikerjakan terakhir di antara fitur keuangan.
7. **Lamaran kerja: upload CV + link portofolio.** Tambah kolom `cv_path`, `portfolio_url` di `job_applications`, validasi (pdf/doc, ukuran maks), simpan di disk **private**, tampilkan di panel pelamar dan export.
8. **Popup informasi preview (tahap testing).** Kecil dan tidak menyentuh paket, jadi dikerjakan **lebih dulu** dari butir lain. Selama masih tahap pengembangan, tiap pintu masuk menampilkan modal yang memberi tahu bahwa sistem belum bisa dipakai untuk operasional, data yang masuk hanya untuk testing, dan data itu akan di-reset sebelum sistem dipakai operasional. **Status: selesai (2026-09-22)** — 380/380 tes lulus (10 tes baru di `EntryPopupsTest`), `npm run build` sukses.

    | Pintu masuk                                                                 | Popup (urutan tampil)                                                        | Muncul                            |
    | --------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | --------------------------------- |
    | Publik: 5 halaman publik dan halaman login                                  | Peringatan preview                                                           | Sekali per sesi browser (per tab) |
    | App Mode (`/app/*`)                                                         | 1. Sambutan "Welcome to W.O.S", 2. Peringatan preview                        | Sekali per login                  |
    | Dashboard (`/dashboard`, `/owner/*`, rekap absensi, persetujuan, rekrutmen) | Peringatan preview (versi dashboard: semua data di dashboard hanya data uji) | Sekali per login                  |
    - **Aturan tampil:** ditandai di `sessionStorage` browser dengan kunci gabungan pintu + popup + versi + token sesi (hash ID sesi login). Login ulang, akun lain, atau tab baru = muncul lagi. Ditutup lewat tombol, ✕, Esc, atau klik area gelap = dianggap sudah dibaca. Teks diubah → naikkan `version` di config supaya muncul lagi.
    - **Catatan Owner:** setelah login Owner mendarat di `/owner/dashboard`, jadi Owner melihat Peringatan versi dashboard dulu; Sambutan baru muncul saat Owner membuka App Mode lewat "← App Saya".
    - **Tidak muncul di:** halaman error, layar Kunci Dashboard, dan selama akun masih wajib ganti password (supaya tidak menumpuk dengan pengalihan ke Profil).
    - **Saklar (`.env`):** `WOS_PREVIEW_MODE` (default `true`) mematikan Peringatan preview di semua pintu sekaligus; wajib di-set `false` saat sistem mulai dipakai operasional (4.1 no. 2). `WOS_ENTRY_POPUPS` (default `true`) mematikan semua popup; `phpunit.xml` mengisinya `false` supaya 370 tes yang ada tidak berubah.
    - **Struktur kode:** teks, urutan, dan versi di `config/entry_popups.php`; pemilih popup `App\Support\EntryPopups` (pola sama dengan `PageGuide`); tampilan `partials/entry-popups.blade.php` + isi per jenis (`entry-popups/welcome`, `entry-popups/notice`); antrean dan `sessionStorage` di `resources/js/entry-popups.js` (`Alpine.data`); animasi di `resources/css/app.css`. Di-include di `layouts/public`, `layouts/employee`, `layouts/app`, dan `auth/login`. Butuh `npm run build` (4.1 no. 4).
    - **Isi Peringatan preview** (bahasa santai): (1) sistem masih preview dan belum untuk operasional, silakan dicoba dulu; (2) data yang dimasukkan hanya untuk testing; (3) data testing akan di-reset saat sistem siap dipakai operasional, jadi jangan memasukkan data asli atau rahasia.
    - **Sambutan:** kartu gelap dengan equalizer dan piringan hitam berputar (CSS murni, tanpa gambar atau library baru), sapaan menurut jam (WIB), nama depan, dan label role; animasi mati bila perangkat memilih `prefers-reduced-motion`.
    - **Tes:** `EntryPopupsTest` (tampil per pintu, urutan Sambutan → Peringatan, kedua saklar, tidak muncul di halaman error/Kunci Dashboard/wajib ganti password, teks di-escape, smoke 5 akun). Yang tetap manual: antrean + `sessionStorage`, animasi, tampilan di HP.
    - **Tahap 2 (saat operasional, belum dikerjakan):** setelah `WOS_PREVIEW_MODE=false`, mesin popup yang sama dipakai untuk **Himbauan** yang wajib diketahui semua karyawan: kategori baru di Memo Forum (`type = himbauan`), tampil di App Mode setelah Sambutan sampai karyawan menekan "Saya sudah baca". `memo_reads.read_at` terisi otomatis saat Inbox dibuka, jadi konfirmasi butuh kolom sendiri (`acknowledged_at`); penerima memakai `audience` yang sudah ada. Masih terbuka: siapa boleh membuat Himbauan, apakah Sambutan tetap tampil, dan perlu tidaknya daftar "belum baca" untuk Owner.

**Belum dijadwalkan (butuh keputusan atau penjelasan):**

- **Assign / Reminder dari dashboard** (Bab 2.1 no. 28): maksud persisnya belum terdokumentasi karena file prototype tidak ada di repo. Yang ada di kode hanya kolom `is_reminder` pada task dan section "REMINDER / ADMIN" di Work Tracker. Perlu dijelaskan dulu fungsi yang diinginkan sebelum masuk daftar.
- **Executive People Overview** (Bab 2.1 no. 20): sengaja di-skip sejak awal karena belum ada padanan halaman yang jelas. Putuskan: tetap tidak dibuat, atau tentukan isinya.
- **Tampilan teks:** UI berbahasa Inggris, teks penting/rawan salah paham berbahasa Indonesia. Saat ini banyak label campur; rapikan saat mengisi konten publik.
- **Ditunda sesuai keputusan lama:** foto profil, tema per user, editor landing, Team Groups, reset password mandiri lewat email (tunggu email asli).

### 4.3 Kualitas & keamanan (sebaiknya sebelum/segera setelah go-live)

**Bug dan keterbatasan yang diketahui**

- **Kartu modul People dan Recruitment di `/dashboard` salah arah.** `DashboardController::index()` belum memetakan keduanya ke halaman aslinya (`attendance.recap.index` dan `recruitment.openings.index`), sehingga kartunya membuka halaman placeholder "Modul ini belum dibangun". Lewat sidebar keduanya normal. Perbaikannya menambah dua baris di `match()`.
- **Export Rekap Absensi tidak dibatasi scope tim.** Halaman Rekap membatasi manajer ke timnya, tetapi export Excel/PDF (`AttendanceRecapExport`) membaca absensi semua karyawan, jadi pemegang akses `people` bisa mengekspor absensi orang di luar timnya lewat Export & Import.
- **Pengajuan cuti tumpang tindih tidak diblokir:** dua pengajuan di tanggal yang sama sama-sama tersimpan (dikunci oleh tes `test_overlapping_leave_requests_are_currently_not_blocked`).

**Lainnya**

- **Tes otomatis:** 370 tes lulus di 19 file (Bab 2.2); jalankan sebelum tiap deploy. Belum ada tes unit murni dan belum ada tes browser (Dusk/Playwright) untuk UI, geolocation, dan kamera.
- **Rate limit login** sudah ada, tetapi tambahkan honeypot atau captcha sederhana pada form kontak & lamaran (saat ini hanya throttle per IP).
- **Security header** (CSP, X-Frame-Options, HSTS) belum ada; tambahkan lewat middleware atau `.htaccess`.
- **Log:** set `LOG_LEVEL=warning` dan rotasi harian di produksi.
- **Migrasi:** dua pasang migrasi bernama sama (`office_settings`, `attendances` create + alter) sebaiknya diberi nama yang membedakan; tidak memengaruhi fungsi.
- **Route manajer kosong** (`Team Overview`) atau isi, atau hapus.

### 4.4 Urutan kerja yang disarankan

Alasan urutan: langkah 1 mengubah versi paket, jadi semua tes berikutnya harus jalan di versi final; langkah 2 harus sebelum 3 karena Payroll memakai blok potongan dari Pengaturan Kantor; Royalty paling besar sehingga paling akhir.

**Sudah dikerjakan lebih dulu:** Popup informasi preview (4.2 no. 8) — selesai 2026-09-22, 380/380 tes lulus. Langkah 1 di bawah menjalankan ulang seluruh tes (termasuk `EntryPopupsTest`) di versi paket final.

1. **Turunkan target PHP ke 8.3** (4.1 no. 1): samakan konstrain `maatwebsite/excel` ke `^4.0`, set platform di `composer.json`, `composer update`, jalankan `php artisan test`.
2. **Pengaturan Kantor dan ritme mingguan Dashboard Owner** (4.2 no. 2 dan 3).
3. **Payroll** (4.2 no. 4), lalu perbarui baris Payroll di Bab 2.1 dan tambahkan tesnya.
4. **Project Budgeting** (4.2 no. 5).
5. **Royalty Dashboard** (4.2 no. 6).
6. **Upload CV dan link portofolio** (4.2 no. 7).
7. **Perbaiki bug dan keterbatasan yang diketahui** (4.3): kartu People/Recruitment di `/dashboard`, scope export Rekap Absensi, dan pengajuan cuti tumpang tindih.
8. **Persiapan deploy:** isi konten publik (4.1 no. 9), `.env` produksi (no. 2, termasuk `WOS_PREVIEW_MODE` sesuai tahap), ubah path `public/index.php` sesuai struktur cPanel (no. 3), `npm run build` (no. 4), ekspor SQL lalu impor lewat phpMyAdmin (no. 7), bersihkan paket upload (no. 8).
9. **Uji manual di staging/subdomain** per halaman per role (termasuk HP untuk absensi), baru buka ke publik.

---

## 5. Ringkasan

WSM-Office sudah berjalan utuh dan stabil secara teknis: 370 tes otomatis lulus dan menutup seluruh checklist manual A–K. Fitur inti harian dari prototype (absensi lengkap dengan geofence dan selfie, izin/cuti/lembur/koreksi dengan approval, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) sudah ada, ditambah rekrutmen, pusat Export/Import, dan panduan halaman yang tidak ada di prototype.

Kesenjangan terbesar ada di **modul finansial** (Payroll, Budget, Royalty; Bab 4.2 no. 4–6) serta halaman publik dan form lamaran yang belum siap tayang (konten placeholder, belum ada upload CV/portofolio). Yang menahan go-live bukan kekurangan fitur, tetapi kesiapan deploy: 7 dari 9 blocker di Bab 4.1 masih terbuka (versi PHP, `.env` produksi, struktur folder cPanel, aset Vite, database tanpa terminal, pembersihan paket, konten publik); blocker file sensitif dan password default sudah selesai.

**Ringkas:** fungsional ±80% dari prototype (±90% untuk fitur harian, ±50% untuk finansial).
