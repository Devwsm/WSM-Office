# WSM-Office — README

Dokumen ini adalah acuan kerja WSM-Office (Laravel, web resmi Whisnu Santika Music yang bakal deploy public), disusun ulang biar urutannya: status sekarang → rencana & keputusan berikutnya → flow per jenis user → peta file/arsitektur → arsip detail per-fase. Prototype pembanding tetap WOS_2_0_App_v32 (`index.html` standalone).

---

## 1. Status Saat Ini

- **Semua 16 fase yang direncanakan dari awal sudah ✅ selesai** (cek kode terakhir 2026-09-13) — publik, karyawan, rekrutmen, absensi, izin/cuti, dashboard access, work control, pengaturan kantor, memo/milestones, work tracker, KPI, kontrak, payroll, budget/royalty, legal, IT (audit log & changelog).
- Arsitektur akses sudah **permission-based** (`dashboard_access`, per-user per-modul, level `view`/`manage`), bukan role-based lagi sejak 2026-09-09 — role (`karyawan`/`manajer`/`owner`/`hrd`) masih dipakai buat hal dasar (login, App Mobile), tapi akses ke 9 modul dashboard (`work`, `kpi`, `people`, `contracts`, `payroll`, `budget`, `royalty`, `legal`, `it`) diatur satu-satu oleh Owner.
- Semua user (termasuk Owner) mendarat di **App Mobile** dulu; dari situ ada 1 tombol "Role Dashboard" yang muncul kalau user punya minimal 1 akses modul.
- Item yang tadinya jadi PR (dari "Kesimpulan" README versi lama) sekarang statusnya:
    1. **Symlink `storage:link` di hosting production** — ✅ **sudah dipasang** (dikonfirmasi user). Upload file Contract Monitoring & Legal sekarang aman diakses lewat `asset('storage/...')`.
    2. **Form kontak publik** — masih ⭕ cuma render, belum nyimpen ke DB. Sudah ada arah keputusan, lihat §2.
    3. **Import/Export bulk** — masih ⭕ belum ada sama sekali (Work Tracker). Arah keputusan: digabung 1 halaman, lihat §2.
    4. **Test otomatis** — masih ⭕ di seluruh sistem, menyusul (belum jadi prioritas sekarang).
    5. **UI/UX & mobile-friendliness** — ini yang jadi **prioritas utama fase berikutnya**, scope-nya proyek-lebar bukan cuma tempelan per modul. Lihat §2.

Ringkasan checklist per-fase (versi lengkap ada di §5) tetap semuanya ✅, minus 2 catatan minor (form kontak & import/export) yang statusnya di atas.

---

## 2. Rencana Selanjutnya

### 2.1 Prioritas #1: UI/UX jauh lebih mudah dipakai & mobile-friendly

Ini keputusan nomor 5 dari user: penyesuaian UI/UX **tidak diperlakukan sebagai polesan per-modul satu-satu**, tapi sebagai **overhaul lintas-project** — karena semua modul dashboard (`work`, `kpi`, `people`, dst) numpang 1 layout (`layouts/app.blade.php`) dan semua fitur karyawan numpang 1 layout (`layouts/employee.blade.php`), perbaikan di level layout/komponen otomatis ngangkat semua modul sekaligus, lebih efisien daripada benerin satu-satu.

Rencana kerja (diusulkan, urutan prioritas):

1. **Audit responsive di 2 layout inti dulu** (`layouts/app.blade.php` untuk dashboard/Owner, `layouts/employee.blade.php` untuk App Mobile) — sidebar dashboard kemungkinan besar belum punya pola mobile yang bener (collapse/hamburger/bottom-sheet), sementara App Mobile sudah lebih mobile-native dari awal (bottom-nav) jadi risikonya lebih rendah.
2. **Standarisasi komponen berulang** (tombol, badge status, form input, tabel/list, modal, empty state) jadi partial/komponen Blade yang dipakai bareng semua modul — supaya benerin 1 komponen = kebenerin di 9 modul sekaligus, bukan 9x kerjaan terpisah.
3. **Tabel-tabel admin (Rekap Absensi, Payroll, Budget, Royalty, Contracts, Legal, Audit Log)** kemungkinan besar masih layout tabel desktop biasa — perlu pola scroll-horizontal/stacked-card di layar sempit.
4. **Form-form panjang** (Karyawan, Payroll Generate, Meeting) perlu dicek spacing & ukuran tap-target di mobile.
5. **Konsistensi warna aksen** (`ceo_accent_color`/`work_accent_color`, Fase 16) — dipertimbangkan ulang apakah scope-nya diperluas pas overhaul ini jalan, bukan cuma 2 area seperti sekarang.

Ini **belum dieksekusi**, baru arah kerja — sengaja didaftar dulu di README biar jadi acuan sebelum coding jalan.

### 2.2 Keputusan per-item yang sudah dibantu diputuskan

Menjawab poin-poin README versi lama:

| #   | Item                          | Keputusan                                                                                                                                                                                                                                                                                                                                                                                                  |
| --- | ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Symlink `storage:link`        | Sudah terpasang di hosting — tidak perlu tindakan lagi, upload file Contract/Legal aman dipakai produksi.                                                                                                                                                                                                                                                                                                  |
| 2   | Form kontak publik            | Disambungkan ke DB (tabel `contact_messages` baru: nama, kontak, pesan, status baru/dibaca, timestamp) + dibuatkan halaman tersendiri di dashboard (bukan numpang modul lain) — kandidat modul baru `inbox-publik` atau ditaruh di bawah akses Owner dulu sebelum didelegasikan lewat Dashboard Access.                                                                                                    |
| 3   | Import/Export bulk            | Digabung jadi **1 halaman terpusat** ("Data Import/Export"), bukan tersebar per-modul. Halaman ini jadi hub: pilih modul tujuan (Work Tracker item dulu sebagai yang pertama, karena ini yang paling jelas kebutuhannya dari prototype) → upload CSV/XLSX → preview → commit. Modul lain (Budget, Payroll, dll) bisa nyusul nempel ke hub yang sama nanti, bukan bikin halaman import terpisah-pisah lagi. |
| 4   | Test otomatis                 | Menyusul — belum jadi prioritas sekarang, tetap dicatat sebagai utang teknis (lihat §5 bagian "Belum Sempurna").                                                                                                                                                                                                                                                                                           |
| 5   | Keputusan UI/UX               | Diperlakukan sebagai inisiatif lintas-project (layout & komponen bersama), bukan per-modul — detail rencana di §2.1.                                                                                                                                                                                                                                                                                       |
| 6   | Catatan "Belum Sempurna" lain | Diputuskan satu-satu di bawah, prinsipnya: **quick win dieksekusi sekarang, yang butuh scope besar/butuh data produksi nyata ditunda ke backlog dengan alasan jelas.**                                                                                                                                                                                                                                     |

Keputusan detail per catatan "Belum Sempurna" (poin 6):

- **Payroll — `shortage_deduction_rate` default 0 tanpa warning** → **quick win, layak dieksekusi duluan**: tambah warning eksplisit di form Generate Payroll kalau rate ini masih 0, biar Owner sadar sebelum generate massal yang salah.
- **Payroll — karyawan belum bisa lihat slip sendiri dari App Mobile** → **prioritas tinggi**, karena ini langsung nyambung ke prioritas mobile-friendly (§2.1): tambah kartu "Slip Gaji" read-only di Profile/Home App Mobile.
- **Payroll — slip PDF/print** → ditunda (butuh library PDF baru), dicatat di backlog.
- **Contracts — notifikasi kontrak mau habis, versioning file** → ditunda, cukup badge "Segera Berakhir" yang sudah ada dulu; versioning butuh keputusan storage yang lebih besar (histori file), bukan quick win.
- **KPI — grafik tren, notifikasi update KPI** → ditunda, bukan blocker fungsional.
- **MoM Terstruktur — export PDF, reminder H-1** → ditunda, prototype juga tidak punya ini secara eksplisit.
- **Budget & Royalty — desain status/validasi yang sengaja beda dari prototype** → **dipertahankan apa adanya** (over-budget sengaja tidak dicegah, status Royalty sengaja bebas diubah) — ini karakteristik bisnis nyata (data platform royalti emang sering direvisi), bukan bug.
- **CEO Dashboard — validasi kontras warna aksen, halaman hub 9 modul** → ditunda; validasi kontras kemungkinan otomatis lebih relevan pas overhaul UI/UX (§2.1) jalan, jadi digabung ke situ saja daripada dikerjain terpisah duluan.
- **Form kontak & import/export** → sudah diputuskan di atas (bukan lagi item nunggu).

---

## 3. Flow per POV (Point of View)

### 3.1 Pengunjung Publik (belum login)

`Beranda` → jelajah `Tentang Kami` / `Layanan` → `Karir` (lihat daftar lowongan aktif dari `job_openings`) → buka detail lowongan → isi form lamaran (nama, kontak, pesan — upload CV menyusul) → **atau** `Kontak` → isi form → (setelah keputusan §2.2) tersimpan ke `contact_messages`, muncul di halaman dashboard khusus buat ditindaklanjuti.

### 3.2 Owner

Login → mendarat di **App Mobile** (sama seperti karyawan biasa — absen sendiri, ajukan izin/cuti/lembur sendiri, lihat Home/Inbox/Profile) → tombol **"Role Dashboard"** selalu muncul karena Owner otomatis `manage` semua modul → masuk ke sidebar dashboard yang berisi SEMUA 9 modul sekaligus, plus 3 halaman khusus Owner: **Karyawan** (CRUD + restore), **Struktur Organisasi**, **Pengaturan Kantor** (termasuk warna aksen, radius absen, rate lembur/potongan), dan **Dashboard Access** (satu-satunya yang boleh assign/ubah akses modul orang lain). Dari dashboard ada tombol balik "App Saya" ke App Mobile.

### 3.3 Karyawan biasa (tanpa akses modul apa pun)

Login → App Mobile saja: **Home** (metric jam kerja, milestone, memo dari Owner, KPI pribadi, Work Tracker pribadi), **Riwayat** presensi, **Request** (ajukan Izin/Cuti/Sakit/WFH/Lembur — approval ke atasan langsung via `manager_id`, terlepas dari akses modul apa pun), **Profile**, **Inbox** (memo, bisa reply/tandai baca). **Tidak ada** tombol "Role Dashboard" karena tidak ada modul yang di-assign — konsisten dengan fix isolasi role-based → permission-based di Fase 6.

### 3.4 Karyawan dengan akses modul tertentu (delegated access — mis. Manajer, HRD, atau staf yang di-assign Owner)

Sama seperti §3.3 (tetap start dari App Mobile untuk urusan pribadi), **plus** tombol "Role Dashboard" muncul kalau minimal punya 1 modul `view`/`manage` — begitu masuk, sidebar dashboard cuma menampilkan modul yang di-assign ke dia (bukan semua 9 seperti Owner). Contoh pola nyata:

- **Manajer** (biasanya modul `people`) → lihat Rekap Absensi tim + Persetujuan Izin/Cuti/Lembur bawahannya.
- **HRD** (biasanya modul `recruitment`, kadang `people`) → kelola Lowongan Kerja + Pipeline Pelamar.
- **Staf lain** yang didelegasikan modul spesifik (mis. `legal`, `it`, `budget`) → cuma lihat/kelola modul itu saja, gate `module:<nama>,view|manage` yang menentukan, bukan jabatan.

---

## 4. Arsitektur File

Struktur file utama beserta penjelasan singkat tiap file/folder:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Public/
│   │   │   └── PageController.php              # Semua halaman publik + form kontak & lamaran kerja
│   │   ├── Auth/
│   │   │   └── LoginController.php             # Login/logout
│   │   ├── Employee/
│   │   │   ├── HomeController.php              # Home App Mobile (metric, memo, milestone, KPI, work tracker pribadi)
│   │   │   ├── AttendanceController.php        # Clock-in/out, riwayat presensi pribadi
│   │   │   ├── LeaveRequestController.php      # Ajukan/batalkan izin-cuti (App Mobile)
│   │   │   ├── OvertimeRequestController.php   # Ajukan/batalkan lembur (App Mobile)
│   │   │   ├── ProfileController.php           # Halaman Profile
│   │   │   ├── MemoInteractionController.php   # Baca/sembunyikan/balas memo, mark-all-read
│   │   │   └── WorkTrackerController.php       # Kalender tim & work tracker sisi karyawan
│   │   ├── Owner/
│   │   │   ├── EmployeeController.php          # CRUD karyawan + restore
│   │   │   ├── OrganizationController.php      # Struktur organisasi (org chart)
│   │   │   ├── DashboardAccessController.php   # Assign/ubah akses modul per-orang (khusus Owner)
│   │   │   ├── OfficeSettingController.php     # Pengaturan kantor (lokasi, radius, jam kerja, warna aksen, rate)
│   │   │   └── DashboardController.php         # Dashboard ringkas Owner (Tugas Berjalan, Kontrak Akan Habis)
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php         # Landing /dashboard — routing kartu modul sesuai akses user
│   │   │   ├── DashboardLockController.php     # Kunci/buka layar dashboard
│   │   │   ├── Work/
│   │   │   │   ├── MemoController.php          # CRUD Memo/MoM ringkas (Work Control)
│   │   │   │   ├── MeetingController.php       # MoM Terstruktur — meeting, action item, blast ke Memo
│   │   │   │   └── WorkTrackerBoardController.php  # Board Kanban Project & Work Item
│   │   │   ├── Kpi/
│   │   │   │   └── KpiController.php           # CRUD KPI & Performance
│   │   │   ├── Contracts/
│   │   │   │   └── ContractController.php      # CRUD Employee Contracts + upload file
│   │   │   ├── Payroll/
│   │   │   │   └── PayrollController.php       # Generate/update/finalize/markPaid/destroy payroll
│   │   │   ├── Budget/
│   │   │   │   └── BudgetController.php        # CRUD baris budget vs actual per project
│   │   │   ├── Royalty/
│   │   │   │   └── RoyaltyController.php       # CRUD royalty entry + net payable
│   │   │   ├── Legal/
│   │   │   │   └── LegalController.php         # CRUD dokumen legal (album/royalty) + upload file
│   │   │   └── It/
│   │   │       ├── AuditLogController.php      # Listing Audit Log (read-only)
│   │   │       └── SystemChangelogController.php  # CRUD System Changelog
│   │   ├── Attendance/
│   │   │   └── RecapController.php             # Rekap absensi lintas karyawan + koreksi
│   │   ├── Approval/
│   │   │   ├── LeaveRequestController.php      # Approve/reject/cancel izin-cuti bawahan
│   │   │   └── OvertimeRequestController.php   # Approve/reject/cancel lembur bawahan
│   │   └── Recruitment/
│   │       ├── JobOpeningController.php        # CRUD lowongan kerja
│   │       └── JobApplicationController.php    # Pipeline pelamar + convert jadi karyawan
│   ├── Middleware/
│   │   ├── EnsureRole.php                      # Gate berdasarkan role login (App Mobile & Owner/Manajer dasar)
│   │   ├── EnsureModuleAccess.php              # Gate module:<nama>,view|manage dari dashboard_access (permission-based)
│   │   └── EnsureDashboardUnlocked.php         # Gate layar kunci dashboard sebelum masuk sisi manajemen
│   └── Requests/                               # Form Request per modul (validasi) — 1:1 nama sama controller pemanggilnya
├── Models/
│   ├── User.php                                # Karyawan/Owner — relasi manager_id (approval), data payroll
│   ├── Attendance.php                          # Sesi presensi (clock-in/out, multi-sesi, shortage blocks)
│   ├── LeaveRequest.php                        # Pengajuan izin-cuti + status approval
│   ├── OvertimeRequest.php                     # Pengajuan lembur + status approval
│   ├── DashboardAccess.php                     # Akses modul per-user (inti sistem permission-based)
│   ├── OfficeSetting.php                       # Setting kantor (lokasi, radius, jam kerja, warna aksen, rate)
│   ├── Memo.php                                # Memo/MoM ringkas
│   ├── MemoRead.php                            # Status baca memo per-user
│   ├── MemoThreadMessage.php                   # Reply thread di bawah memo
│   ├── Meeting.php                             # MoM Terstruktur
│   ├── MeetingActionItem.php                   # Action item per-meeting (PIC + due date)
│   ├── Project.php                             # Project di Work Tracker
│   ├── WorkItem.php                            # Task/item di board Kanban
│   ├── Kpi.php                                 # KPI & Performance per karyawan
│   ├── EmployeeContract.php                    # Kontrak karyawan + file upload
│   ├── PayrollRecord.php                       # Payroll per periode per karyawan (draft→finalized→paid)
│   ├── ProjectBudget.php                       # Baris budget vs actual per project
│   ├── RoyaltyEntry.php                        # Entry royalti (gross, share%, recoup, net)
│   ├── LegalDocument.php                       # Dokumen legal (album/royalty) + file upload
│   ├── AuditLog.php                            # Log aksi penting (read-only dari UI)
│   ├── SystemChangelog.php                     # Catatan rilis/perubahan sistem
│   ├── JobOpening.php                          # Lowongan kerja
│   └── JobApplication.php                      # Pelamar kerja + status pipeline
├── Providers/
│   └── AppServiceProvider.php                  # Service provider bawaan Laravel (belum ada kustomisasi)
└── Support/
    ├── AttendanceReconciler.php                # Auto-close sesi absen lupa checkout (piggyback di request biasa, bukan cron)
    └── Geo.php                                 # Helper hitung jarak/radius lokasi absen

database/
├── database.sqlite                             # DB lokal buat dev (produksi rencana pakai MySQL cPanel)
├── factories/
│   └── UserFactory.php                         # Factory buat testing/seeding user
├── migrations/                                 # 36 file — urut sesuai riwayat perubahan skema tiap fase
└── seeders/
    ├── DatabaseSeeder.php                      # Entry point seeding, panggil seeder lain
    ├── DemoSeeder.php                          # Data contoh (karyawan, project, dst) buat demo/testing
    └── OfficeSettingSeeder.php                 # Seed data kantor awal (lokasi asli, radius, jam kerja)

resources/
├── css/                                        # Entry point CSS (Tailwind)
├── js/                                         # Entry point JS
└── views/
    ├── layouts/
    │   ├── app.blade.php                       # Shell dashboard — dipakai SEMUA modul dashboard + halaman Owner
    │   ├── employee.blade.php                  # Shell App Mobile (bottom-nav) — dipakai semua karyawan
    │   ├── public.blade.php                    # Shell halaman publik
    │   └── error.blade.php                     # Shell halaman error custom
    ├── public/                                 # Beranda, Tentang, Layanan, Karir (+detail), Kontak
    ├── auth/                                   # Halaman login
    ├── employee/                               # Home, Riwayat, Profile + partial (_kpi, _milestones, dst)
    │   ├── attendance/                         # Riwayat presensi
    │   ├── leave/                              # Form & riwayat Izin/Cuti
    │   ├── overtime/                           # Form & riwayat Lembur
    │   └── work-tracker/                       # Kalender Tim (sisi App Mobile)
    ├── owner/                                  # Dashboard ringkas, Karyawan, Struktur Organisasi, Pengaturan Kantor
    │   ├── employees/                          # Index/create/edit/form karyawan
    │   └── dashboard-access/                   # Assign akses modul per-orang
    ├── dashboard/                              # 1 folder per modul dashboard
    │   ├── work/                               # Memo/MoM ringkas + Work Control
    │   │   ├── tracker/                        # Board Kanban Project & Work Item
    │   │   └── meetings/                       # MoM Terstruktur (meeting + action item)
    │   ├── kpi/                                # KPI & Performance
    │   ├── contracts/                          # Employee Contracts
    │   ├── payroll/                            # Payroll
    │   ├── budget/                             # Project Budgeting
    │   ├── royalty/                            # Royalty
    │   ├── legal/                              # Dokumen Legal
    │   └── it/                                 # Audit Log
    │       └── changelog/                      # System Changelog
    ├── attendance/recap/                       # Rekap Absensi lintas karyawan + koreksi
    ├── approval/
    │   ├── leave/                              # Layar Persetujuan Izin/Cuti
    │   └── overtime/                           # Layar Persetujuan Lembur
    ├── recruitment/
    │   ├── openings/                           # CRUD lowongan kerja
    │   └── applications/                       # Pipeline pelamar
    ├── memo/                                   # Partial thread reply memo
    ├── components/                             # org-node.blade.php (kartu org chart)
    ├── partials/                               # flash-data.blade.php (data flash buat JS/toast)
    └── errors/                                 # Halaman custom 403/404/500/503

routes/
└── web.php                                     # Semua route web, dikelompokkan per area:
                                                 #   public.*        → halaman publik, tanpa auth
                                                 #   employee.*      → App Mobile (prefix /app, semua role login)
                                                 #   dashboard.lock.*→ layar kunci sebelum masuk sisi manajemen
                                                 #   manajer.* / owner.* → halaman khusus Manajer/Owner
                                                 #   recruitment.*   → gate module:recruitment,view|manage
                                                 #   attendance.recap.*, approval.leave.*, approval.overtime.* → gate module:people
                                                 #   dashboard.*     → 9 modul dashboard, tiap grup gate module:<nama>,view|manage sendiri
```

---

## 5. Fase-Fase & Dokumentasi Sebelumnya (Arsip)

Riwayat pengerjaan per-fase (Fase 1–16), termasuk catatan QA "Belum Sempurna" jujur per modul, refactor besar permission-based, dan ringkasan checklist global — semuanya masih valid dan disimpan apa adanya di bawah ini sebagai arsip referensi, supaya histori keputusan gak hilang.

### Fase 1 — Website Publik

➕ **Di luar scope prototype.** WOS_2_0_App_v32 murni internal tool (CEO Dashboard + Employee App), gak punya halaman publik/marketing sama sekali — jadi seluruh Fase 1 ini scope baru WSM Office System, bukan porting.

- ✅ Beranda, Tentang Kami, Layanan, Kontak — halaman statis sudah ada (`PageController`, `resources/views/public/*`)
- ✅ Karir — daftar lowongan (`/karir`) tarik data asli dari `job_openings` (status `published`), detail lowongan + form lamar (`/karir/{slug}`)
- ⭕ Form Kontak baru nge-render, **belum nyimpen ke DB / kirim notifikasi** — masih ditandai `TODO Fase 1.x` di `PageController::storeContact()` (arah keputusan sudah ada, lihat §2.2)
- ⭕ Konten halaman (Tentang Kami, Layanan) masih hardcode di Blade, belum bisa diedit dari dashboard (rencana "CMS ringan" belum digarap)

---

### Fase 2 — Manajemen Karyawan & Struktur Organisasi

- ✅ CRUD Karyawan (`/owner/employees`) — nama, role, divisi, tipe, salary, join date, birth date, dll
- ✅ Struktur Organisasi (`/owner/organisasi`) — org chart via `manager_id` self-reference, padanan `managerFor()`/org tree prototype
- ✅ Restore karyawan yang di-soft-delete
- 🟡 Halaman "Edit / Password" karyawan di prototype jadi satu form dengan **Dashboard Access** (assign modul view/manage) — di WSM-Office dipisah jadi 2 halaman: edit data karyawan (`employees.edit`) vs `employees/{id}/akses` (khusus Owner). Fungsinya sama, cuma posisinya kepisah.

---

### Fase 3 — Rekrutmen _(tambahan di luar prototype)_

➕ Prototype v32 gak punya fitur rekrutmen sama sekali (sudah dicek — nol hasil grep "rekrutmen"/"recruitment"/"pelamar" di seluruh file prototype).

- ✅ CRUD Lowongan Kerja (create/edit, publish/draft/closed)
- ✅ Pipeline Pelamar — daftar, detail, update status, convert pelamar jadi karyawan
- ✅ Modul `recruitment` di Dashboard Access — Owner bisa assign akses ke staf tertentu (bukan otomatis semua HRD)

---

### Fase 4 — Absensi (Self-Service + Rekap)

- ✅ Clock-in / clock-out dari Employee App (`/app/absensi/masuk`, `/pulang`)
- ✅ Multi-sesi per hari (padanan mode Lapangan/Gigs prototype — bisa checkout lalu checkin sesi baru)
- ✅ Auto-close sesi yang lupa checkout (`AttendanceReconciler`, jalan piggyback di request biasa — **bukan cron**, sesuai constraint shared hosting)
- ✅ Riwayat presensi pribadi (`/app/riwayat`)
- ✅ Rekap Absensi lintas karyawan + koreksi jam (`RecapController`)
- 🟡 Gerbang masuk Rekap Absensi dulunya `role:manajer,owner,hrd`, sekarang `module:people,view` (koreksi butuh `module:people,manage`) — ini fix bug isolasi juga: karyawan biasa gak lagi ke-expose link Rekap cuma gara-gara punya akses modul lain

---

### Fase 5 — Pengajuan Izin/Cuti + Approval

- ✅ Form pengajuan (Koreksi Presensi, Izin, Sakit, Cuti, WFH) dari Employee App
- ✅ Saldo cuti berbayar (paid leave balance) tampil di form
- ✅ Layar Persetujuan buat atasan langsung (approve/reject/cancel)
- ✅ Approval tetap murni relasi `manager_id` (atasan langsung) — **dashboard_access gak pernah ngubah siapa yang berhak approve**, cuma ngatur siapa yang bisa _masuk_ layar Persetujuan
- 🟡 Gerbang masuk layar Persetujuan dulu `role:manajer,owner`, sekarang `module:people,view` (sama modul dengan Rekap Absensi, persis prototype yang gabungin "People & Leave" jadi satu)

---

### Fase 6 — Dashboard Access (Permission per Modul) & Work Control

- ✅ **Dashboard Access** — Owner assign level `view`/`manage` per modul per orang, padanan persis `DASHBOARD_MODULES`/`normalizeDashboardAccess()` di prototype. 7 modul inti (`work`, `budget`, `royalty`, `kpi`, `people`, `contracts`, `payroll`) 1:1 sama key/label/desc dengan prototype
- ✅ Landing Dashboard (`/dashboard`) — cuma nampilin modul yang beneran di-assign ke user, murni dari `accessLevel()` bukan role
- ✅ **Memo Forum** (Work Control) — CRUD memo, publish ke Home Employee App, sudah termasuk thread reply per memo (Fase 8)
- ✅ **MoM Terstruktur (Meeting)** — `MeetingController` + views `dashboard/work/meetings/*` sudah jadi (2026-09-12): attendee relasi asli (checkbox dari `users`), action item per-baris dengan PIC perorangan/ALL TEAM + due date, opsional auto-generate ke Work Tracker (`WorkItem` nempel balik lewat `meeting_action_item_id`), dan tombol **Blast Summary** yang nge-push ringkasan jadi `Memo` (`type=mom`) ke semua karyawan — jadi tetap numpang infrastruktur Memo yang udah ada (kartu "Info dari Owner", Inbox, badge unread), bukan jalur notifikasi baru. Tab ke-3 "Rapat & Action Item" udah nempel di Work Control, sebelahan "MoM & Memo" dan "Work Tracker".
    - Catatan: ini SENGAJA beda entity dari "MoM ringkas" (`Memo` dengan `type=mom`, dari Fase 6b) — dua-duanya dipertahankan, bukan yang satu gantiin yang lain. "MoM ringkas" buat catatan cepat tanpa action item; "MoM Terstruktur" (baru) buat rapat yang perlu action item terlacak & bisa ditugaskan ke Work Tracker.
- ➕ 2 modul tambahan yang gak ada di prototype: `legal` (Fase 14) dan `it` (Fase 15) — ditambahin biar Owner bisa delegasikan Legal/IT ke staf lain (di prototype dua ini cuma section tetap di sidebar CEO, role-gated ke CEO doang, gak bisa didelegasikan)
- ➕ Modul `recruitment` juga ditambah ke Dashboard Access (lihat Fase 3)

---

### Fase 7 — Pengaturan Kantor & Lembur

- ✅ Pengaturan Kantor (`/owner/pengaturan-kantor`) — titik lokasi, radius toleransi absen, jam kerja normal, bisa diubah Owner sendiri lewat UI (sebelumnya cuma lewat seeder)
- ✅ Pengajuan & approval Lembur (self-service, flow sama kayak Izin/Cuti)
- 🟡 Field `flat_overtime_rate` (flat rate per approved overtime, bukan hitungan durasi — persis `oeOvertimeFlat` prototype) **sengaja ditunda** ke Fase 12 Payroll — sekarang sudah dilengkapi (lihat Fase 12), dulu belum nempel ke kalkulasi apa pun

---

### Fase 8 — Memo Forum Interaktif, Team Moments, Milestones

- ✅ Kartu "Info dari Owner" di Home jadi interaktif — baca/sembunyikan/reply, badge unread di ikon inbox
- ✅ Mark-all-read otomatis pas modal Inbox dibuka
- ✅ Milestones (Birthday & Work Anniversary + lama bekerja) — punya sendiri, dihitung langsung dari `User` model, gak lewat controller
- ✅ Team Moments — ulang tahun/anniversary SEMUA karyawan dalam 45 hari ke depan
- ✅ Paid Leave banner di profile/home

---

### Fase 9 — Work Tracker, Shared Calendar, MoM

- ✅ **Project & Work Item board** (Kanban) — CRUD Project, assign PIC, drag-drop update progress kolom `Pending/On Development/Follow Up/Confirmed/Done/Postpone`, gerbang `module:work` (view lihat board, manage baru bisa CRUD)
- ✅ **My Work Tracker** — kartu read-only di Home, isinya task milik sendiri (`pic_employee_id`), beda dari board admin di atas
- ✅ **Shared Calendar** (`/app/kalender-tim`) — kalender bersama, bisa difilter per Project/PIC, terbuka buat semua role internal (padanan `openSharedWorkloadCalendar()`)
- ⭕ **Import Item bulk dari CSV/XLSX** — fitur besar di prototype (v19→v32, header bisa di salah satu 50 baris pertama, deteksi worksheet, dsb), **belum ada sama sekali** di `WorkTrackerBoardController`/view (arah keputusan sudah ada, lihat §2.2)
- ✅ **MoM Terstruktur (Meeting)** — lihat Fase 6b, sudah jadi (2026-09-12)
- ⭕ Section color coding & section management per-project (`sectionColorV20`, custom section chip) — belum di-porting, board saat ini pakai kolom progress tetap, bukan section custom yang bisa diwarnai/di-reorder
- ⭕ Export Project ke XLSX (`exportProjectXlsxV28`) — belum ada

---

### Fase 10 — KPI & Performance

✅ **Selesai (2026-09-12).** `KpiController` (`app/Http/Controllers/Dashboard/Kpi/`) + views `dashboard/kpi/*`:

- Listing KPI seluruh tim (filter per karyawan), CRUD lengkap (tambah/edit/hapus), status `Active`/`Completed`/`Archived`
- Gate `module:kpi,view` (lihat) / `module:kpi,manage` (CRUD) — pola sama persis modul `work`
- Landing `/dashboard` di-fix biar modul KPI nyambung ke `dashboard.kpi.index` (sebelumnya bakal ke placeholder generik kalau gak di-special-case, sama kayak `work` dulu)
- Kartu "My KPI" di Home sekarang keisi beneran begitu Owner/Manajer nambah KPI — style badge/progress bar (`achievementBadgeClass()`/`progressBarClass()`) dipakai ulang persis dari `Kpi` model, satu sumber warna buat kartu Home & listing Owner
- Ditambah `Kpi::STATUSES` const (konsisten sama pola `Project::STATUSES`/`WorkItem::PROGRESS_OPTIONS`) — gak ada di kode sebelumnya, ditambahin pas fase ini

---

### Fase 11 — Employee Contracts

✅ **Selesai (2026-09-12).** `ContractController` (`app/Http/Controllers/Dashboard/Contracts/`) + views `dashboard/contracts/*`:

- Upload file kontrak beneran (PDF/Word/gambar, maks 10MB) ke `Storage::disk('public')` — **controller PERTAMA di codebase ini yang pakai upload multipart** (`$request->file()`); sebelumnya cuma ada foto base64 dari kamera di `AttendanceController::storePhoto()`, beda mekanisme
- CRUD lengkap: tambah/edit (ganti file opsional)/hapus, filter listing per karyawan
- Badge "Segera Berakhir" pakai `EmployeeContract::isExpiringSoon()` (≤30 hari) yang udah ada dari Fase 11 awal, sebelumnya nganggur karena gak ada UI yang manggil
- Landing `/dashboard` di-fix biar modul Contracts nyambung ke `dashboard.contracts.index` (sama fix yang dilakuin ke `kpi` sebelumnya)
- Ditambah `EmployeeContract::formattedSize()` (tampilan ukuran file ringkas, "240 KB"/"1.4 MB") — gak ada di kode sebelumnya, ditambahin pas fase ini

⚠️ **Catatan operasional (BUKAN bug kode, tapi perlu diverifikasi pas deploy):** `Storage::disk('public')` butuh symlink `public/storage → storage/app/public` yang normalnya dibuat lewat `php artisan storage:link`. Berhubung hosting-nya shared cPanel **tanpa akses terminal**, symlink ini kemungkinan besar belum ada — kalau belum, file kontrak (dan foto absensi yang udah lebih dulu pakai pola sama) gak bakal bisa diakses lewat `asset('storage/...')` walau upload-nya sendiri sukses. Ini bukan masalah baru dari fase ini — attendance photo udah lebih dulu punya risiko yang sama, cuma baru ketauan jelas sekarang karena Contract Monitoring nge-expose link filenya langsung ke user. Solusinya biasanya salah satu: symlink manual lewat File Manager cPanel, atau fitur "Setup Node.js/PHP App" sebagian host yang kasih akses jalanin 1 command, atau tanya ke Rumahweb caranya.

---

### Fase 12 — Payroll

✅ **Selesai (2026-09-12).** `PayrollController` (`app/Http/Controllers/Dashboard/Payroll/`) + views `dashboard/payroll/*` — **sengaja didesain beda** dari prototype (bukan porting 1:1), sesuai catatan migration `create_payroll_records_table`: prototype cuma hitung "estimate" on-the-fly tiap buka CEO Dashboard, gak pernah disimpan; di sini payroll digenerate jadi baris `payroll_records` permanen per (karyawan, periode).

- **Generate per-periode** — satu form men-generate payroll buat semua karyawan yang punya "Gaji Pokok" terisi sekaligus (atau subset lewat multi-select), bukan satu-satu manual
- **Komponen dihitung otomatis**: `base_salary` (salinan `users.salary_base` saat generate), `overtime_amount` (jumlah Lembur disetujui periode itu × `flat_overtime_rate` per-karyawan), `shortage_deduction` (`Attendance::monthlyShortageBlocks()` dari Fase 7 × rate perusahaan baru — lihat poin berikutnya)
- **Field baru yang dilengkapi buat nutup Fase 12**: `salary_base`/`target_hours_per_day`/`flat_overtime_rate` (per-karyawan, ditambah ke form Karyawan Fase 2 — field ini emang sengaja ditunda dari Fase 7) dan `shortage_deduction_rate` (kebijakan perusahaan, satu rate rupiah per blok 60 menit kurang jam kerja, ditambah ke form Pengaturan Kantor Fase 7 — Owner yang nentuin sendiri angkanya, gak ada acuan dari prototype)
- **Alur status satu arah**: `draft → finalized → paid`. Cuma `draft` yang boleh di-regenerate ulang/diedit (`other_adjustment` + catatan)/dihapus — sekali `finalized`, angka terkunci permanen sebagai histori, gak ketimpa walau data sumbernya (gaji, setting) direvisi belakangan
- **Halaman detail (slip)** nunjukin breakdown lengkap (jumlah lembur & blok shortage mentah, bukan cuma nominal akhir) — ini justru LEBIH detail dari prototype yang cuma nampilin angka ringkasan `thp`
- Karyawan tanpa "Gaji Pokok" terisi sengaja dilewati dari generate (bukan dianggap Rp 0), dan dashboard payroll nunjukin counter berapa karyawan yang masih kosong datanya

🔧 **1 bug kecil ke-fix pas review:** `routes/web.php` sempet ada duplikasi `Route::get('/{module}', ...)->name('show')` dua kali persis (baris terakhir grup `dashboard.`). Gak fatal (request tetap kena match ke baris pertama, cuma baris kedua jadi dead code), tapi udah dibersihin.

---

### Fase 13 — Project Budgeting & Royalty

✅ **Selesai (2026-09-12).** Dua controller terpisah (gate beda: `budget` vs `royalty`), tapi dibangun bareng karena satu fase:

- **`BudgetController`** (`dashboard/budget/*`) — CRUD baris budget-vs-actual per project (padanan `saveBudgetEntry()`), listing dikelompokkan per project dengan subtotal Budget/Actual/Variance (variance minus = over budget, dikasih warna merah)
- **`RoyaltyController`** (`dashboard/royalty/*`) — CRUD royalty entry (padanan `saveRoyaltyEntry()`), Net Payable dihitung live lewat `RoyaltyEntry::net()` (gross × share% − recoup), filter per status (`Estimated`/`Reported`/`Ready to Pay`/`Paid`)
- Cuma level `royaltyEntries` yang di-porting — subsistem `royaltyFinance` (revenue ledger per-lagu, lebih detail) SENGAJA gak diikutin, sesuai catatan migration
- Landing `/dashboard` di-fix buat kedua modul (sama pola fix yang berulang di fase-fase sebelumnya)
- Format Rupiah di kedua modul numpang `PayrollRecord::formatRupiah()` yang udah ada dari Fase 12 — sengaja gak bikin helper baru, satu sumber format currency buat seluruh sistem

---

### Fase 14 — Legal _(desain baru, terinspirasi tapi bukan porting 1:1)_

✅ **Selesai (2026-09-13).** `LegalController` (`dashboard/legal/*`) — CRUD `LegalDocument` (padanan `state.legalDocs`), 2 kategori (`album`/`royalty`, alias Album Contracts & Royalty Agreements) disatuin dalam 1 tabel + 1 controller, dibedain lewat kolom `category` — sama pola persis `Memo.type` ('memo' vs 'mom') dan filter status di `RoyaltyController` (Fase 13). Prototype v18 punya 2 halaman terpisah buat 2 kategori ini tapi cuma 1 level akses; di sini disatuin jadi 1 index dengan filter kategori (bukan porting 1:1, keputusan desain WSM Office sendiri).

- Upload file beneran ke `Storage::disk('public')` (`legal/{category}/...`) — sama mekanisme `ContractController` (Fase 11), bukan blob base64 kayak prototype
- Field: kategori, judul, pihak terkait (label/artist/publisher, opsional), tanggal mulai/selesai (opsional), catatan (opsional)
- Badge "Segera Berakhir" (≤30 hari) lewat `LegalDocument::isExpiringSoon()` — method baru, sama pola & window kayak `EmployeeContract::isExpiringSoon()`
- Gate `module:legal,view|manage` — modul `legal` sudah terdaftar di Dashboard Access sejak Fase 6, sekarang isinya udah bukan placeholder
- Landing `/dashboard` di-fix (match statement `DashboardController::index()`) biar kartu modul Legal ngarah ke `dashboard.legal.index`, bukan placeholder generik

---

### Fase 15 — IT (Audit Log & System Changelog) _(desain baru)_

✅ **Selesai (2026-09-13, termasuk instrumentasi).** 2 sub-halaman, sama pola `work` (tracker/meetings) — 1 gate `module:it`, landing `/dashboard/it` = Audit Log:

- **Audit Log** (`AuditLogController`, `dashboard/it/index`) — listing `AuditLog`, READ-ONLY total (gak ada create/edit/delete dari UI, cuma gate `view`). Search sederhana (action/detail/nama).
- **System Changelog** (`SystemChangelogController`, `dashboard/it/changelog/*`) — CRUD penuh, padanan `saveCustomChangeLog()`. `modules` (pisah koma) & `changes` (1 baris = 1 bullet) tetap input teks biasa di form, di-split jadi array (kolom JSON) di controller — sama persis behaviour prototype. Filter per status (`Planned`/`Released`).
- Landing `/dashboard` di-fix (match statement `DashboardController::index()`) biar kartu modul IT ngarah ke `dashboard.it.index`.

**Instrumentasi `AuditLog::record()` (2026-09-13)** — SENGAJA cuma nyambungin ke aksi-aksi "penting" (istilah dari catatan sebelumnya), BUKAN semua mutasi di semua controller (itu gak akan pernah selesai & bikin log berisik nyampur hal remeh kayak edit catatan draft payroll tiap detik). Yang udah dicatat:

- **`Owner\EmployeeController`** — tambah/edit/nonaktifkan/aktifkan-kembali karyawan (paling sensitif: langsung ubah siapa punya akun/role apa)
- **`Owner\DashboardAccessController::update()`** — perubahan akses modul per-user (ringkasan modul:level yang di-set masuk ke `detail`)
- **`Approval\LeaveRequestController`** — approve/reject/cancel izin-cuti
- **`Approval\OvertimeRequestController`** — approve/reject/cancel lembur
- **`Dashboard\Payroll\PayrollController`** — generate (ringkasan jumlah + periode), update (penyesuaian manual), finalize, markPaid, destroy (data finansial, semua status transition dicatat)
- **`Owner\OfficeSettingController::update()`** — pengaturan kantor (acuan absensi/lembur/payroll seluruh sistem)

**BELUM diinstrumentasi** (keputusan sadar, bukan kelupaan): CRUD di modul Legal/Contracts/Budget/Royalty/KPI/Work/Recruitment, login/logout, dan lock/unlock dashboard (aksi UI rutin, bukan perubahan data) — kalau nanti dianggap perlu, tinggal tambah `AuditLog::record()` 1-2 baris di controller terkait, pola udah jelas dari 6 controller di atas.

### Fase 16 — CEO Dashboard IA Restructure & Settings

✅ **Selesai (2026-09-13)**, dengan koreksi penting dari catatan lama di atas: pas dicek ulang kodenya, **"satu shell navigasi terpadu" ternyata udah ada dari lama** — bukan sengaja direstrukturisasi ulang di fase ini, tapi kebentuk ORGANIK sepanjang Fase 6a–15. `layouts/app.blade.php` udah jadi satu sidebar yang dipakai bareng oleh SEMUA halaman Owner (`/owner/*`) DAN semua 9 modul dashboard (`work`/`kpi`/`contracts`/`payroll`/`budget`/`royalty`/`legal`/`it`, plus `people` lewat Absensi & Persetujuan) — tiap fase nambahin link modulnya sendiri ke sidebar yang sama, bukan bikin shell baru. Owner otomatis lihat SEMUA modul itu langsung di sidebar (satu klik, gak perlu ke halaman hub terpisah), karena `accessLevel()` Owner hardcode 'manage' semua modul. Catatan lama di README ini ("Owner area masih terpisah-pisah tanpa sidebar navigasi terpadu") udah GAK AKURAT — dikoreksi di update ini.

Yang BENERAN dikerjain di fase ini:

- **2 kartu Owner Dashboard yang dari Fase 0 masih placeholder ("—", "Data aktif mulai Fase 7"/"Fase 9") akhirnya diisi data beneran** — "Tugas Berjalan" (`WorkItem` yang belum Done/Postpone) dan "Kontrak Akan Habis" (`EmployeeContract::isExpiringSoon()`, ≤30 hari). Ini murni bug lama yang kelewat — fitur-fitur yang direferensikan (Work Tracker Fase 9, Contracts Fase 11) udah lama kelar, cuma kartunya gak pernah balik di-update.
- **Warna aksen (`ceo_accent_color`/`work_accent_color`) akhirnya beneran dipakai** — sebelumnya cuma kolom DB nganggur dari Fase 7:
    - Ditambah ke form Pengaturan Kantor (color picker + validasi hex ketat `#RRGGBB`)
    - Diinject sebagai CSS custom property (`--ceo-accent`/`--work-accent`) di `<head>` layout
    - `--ceo-accent` nge-tint pill aktif 4 link Owner-only (Dashboard/Karyawan/Struktur Organisasi/Pengaturan Kantor)
    - `--work-accent` nge-tint tab aktif di Work Control (3 tempat: `dashboard/work/index.blade.php`, `tracker/index.blade.php`, `meetings/index.blade.php`) + pill modul "work" di sidebar
    - **Sengaja dibatasi cuma 2 area itu** — modul lain (KPI, Payroll, Budget, dst) tetap hitam standar (`bg-ink`), sesuai prototype v18 yang emang cuma CEO Dashboard & Work Control yang punya accent color terpisah, bukan semua halaman

---

### Refactor Besar: Permission-based, Bukan Role-based (2026-09-09)

Ini bukan fitur baru, tapi perubahan arsitektur lintas-fase yang penting buat konteks kenapa banyak item di atas ditandai 🟡:

- **Sebelum**: banyak fitur (Rekap Absensi, Persetujuan Izin/Cuti/Lembur, Rekrutmen) di-gate pakai `role:manajer,owner,hrd` — siapa pun berjabatan itu otomatis dapet akses, gak bisa dicabut per-orang
- **Sesudah**: pakai `module:people,view|manage` dan `module:recruitment,view|manage` (Dashboard Access) — Owner assign satu-satu lewat halaman "Dashboard Access" yang sudah ada dari Fase 6a
- **Kenapa**: nemu bug nyata — karyawan biasa (role `karyawan`) kelihatan link "Absensi" di sidebar walau gak punya akses modul apa pun ke situ, gara-gara link-nya dulu gak dicek sama sekali, cuma numpang tampil asal user itu punya akses modul _lain_
- **Yang TIDAK berubah**: siapa yang boleh approve/reject request tertentu tetap 100% relasi `manager_id` (atasan langsung) — persis prinsip di prototype: "Dashboard access tidak mengubah authority approval"
- Migration `backfill_dashboard_access_for_manajer_hrd` mastiin karyawan existing yang role-nya Manajer/HRD gak kehilangan akses pas refactor ini jalan

---

### Ringkasan Checklist Global

| Fase | Modul                                                          | Status                                  |
| ---- | -------------------------------------------------------------- | --------------------------------------- |
| 1    | Website Publik                                                 | ✅ sebagian (form kontak belum nyimpen) |
| 2    | Karyawan & Struktur Organisasi                                 | ✅                                      |
| 3    | Rekrutmen                                                      | ➕ ✅                                   |
| 4    | Absensi (self-service + rekap)                                 | ✅                                      |
| 5    | Izin/Cuti + Approval                                           | ✅                                      |
| 6a   | Dashboard Access (permission)                                  | ✅                                      |
| 6b   | Work Control → Memo (ringkas, termasuk `type=mom`)             | ✅                                      |
| 6b/9 | Work Control → MoM Terstruktur (Meeting + action item + blast) | ✅                                      |
| 7    | Pengaturan Kantor & Lembur                                     | ✅                                      |
| 8    | Memo interaktif, Team Moments, Milestones                      | ✅                                      |
| 9    | Work Tracker board & Shared Calendar                           | ✅                                      |
| 9    | Import CSV/XLSX bulk item                                      | ⭕                                      |
| 10   | KPI & Performance                                              | ✅                                      |
| 11   | Employee Contracts                                             | ✅                                      |
| 12   | Payroll                                                        | ✅                                      |
| 13   | Project Budgeting & Royalty                                    | ✅                                      |
| 14   | Legal                                                          | ✅                                      |
| 15   | IT (Audit Log & Changelog)                                     | ✅                                      |
| 16   | CEO Dashboard IA Restructure                                   | ✅                                      |
| —    | Refactor permission-based                                      | ✅                                      |

**Semua 16 fase yang direncanakan sekarang ✅ selesai** (2 catatan minor gak ngeblok: form kontak publik Fase 1 belum nyimpen ke DB, import CSV/XLSX bulk item Fase 9 belum di-porting — dua-duanya kecil, dicatat di bagian QA masing-masing). Audit Log beneran keisi lewat instrumentasi `AuditLog::record()` di 6 controller berdampak paling besar (karyawan, dashboard access, approval izin/cuti/lembur, payroll, pengaturan kantor). Sidebar navigasi ternyata udah terpadu dari lama (terbentuk organik sepanjang Fase 6a–15, bukan direstrukturisasi ulang di Fase 16) — koreksi dari catatan lama README ini.

### Kesimpulan

WSM-Office sekarang solid dan sejalan (bahkan di beberapa modul lebih detail) dari prototype WOS_2_0_App_v32, dengan penyesuaian arsitektur yang disengaja: dashboard permission-based (bukan role-based) sejak 2026-09-09, dan beberapa modul baru di luar scope prototype (Rekrutmen, Legal, IT) yang sengaja ditambah biar Owner bisa delegasikan lebih banyak ke staf lain.

Semua catatan QA "belum sempurna" yang dulu ada di sini per-modul (symlink, form kontak, import/export, test otomatis, payroll slip mobile, kontras warna aksen, dll) **sudah diputuskan arahnya** — lihat tabel keputusan di §2.2, gak diulang lagi di sini biar gak ada dua versi jawaban yang beda.
