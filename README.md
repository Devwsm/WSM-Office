# WSM Office System — Status & Roadmap

> Audit dilakukan dengan membandingkan langsung kode `WSM-Office.zip` (Laravel, web publik + sistem internal) terhadap `WOS_2_0_App_v32.zip` (prototype HTML/JS, `index.html` / `WOS_2_0_STANDALONE_v32.html`, versi final = definisi fungsi yang paling bawah di file karena JS di prototype saling menimpa fungsi bernama sama).

---

## 1. Status Project Saat Ini

**≈ 93% selesai** menuju paritas penuh dengan prototype v32 + fitur tambahan yang tidak ada di prototype (web publik, rekrutmen, Legal, IT/Audit Log — lihat catatan di kode).

| Area                                             | Progres             | Catatan                                                                                                                          |
| ------------------------------------------------ | ------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Web publik (Home/About/Services/Careers/Contact) | ✅ 100%             | Sudah ada, form kontak sudah nyambung DB + halaman kelola di dashboard                                                           |
| Autentikasi & error pages (403/404/500/503)      | ✅ 100%             | 503 dibedakan untuk maintenance mode                                                                                             |
| App Mode karyawan (mobile-first, semua user)     | 🟡 ~90%             | Fungsional & sudah dipolish beberapa ronde; masih ada gap kecil (lihat §3)                                                       |
| CEO Dashboard (Owner)                            | 🟡 ~90%             | Semua 7 grup sidebar & modul ada; belum di-audit visual detail vs prototype di sesi manapun                                      |
| Dashboard karyawan berakses/delegated            | 🟡 ~85%             | Arsitektur permission-based sudah jalan (1 layout dipakai bareng, bukan dashboard terpisah); belum pernah di-audit visual khusus |
| 16 Fase besar (lihat §5)                         | ✅ 16/16 dieksekusi | Semua modul dashboard_access (10 modul) punya controller + view                                                                  |
| Import/Export terpusat                           | ❌ 0%               | Diputuskan digabung 1 halaman, tapi belum ada implementasi sama sekali di kode                                                   |
| Automated test                                   | ❌ 0%               | Cuma skeleton default Laravel (3 file), belum ada test custom                                                                    |

Kesimpulan cepat: **logika bisnis & data sudah solid dan sudah battle-tested (migrate:fresh --seed + cek manual lolos)**. Sisa pekerjaan besar didominasi **polish UI/UX** (terutama CEO Dashboard & dashboard delegated yang belum sempat diaudit sedetail App Mode) dan **2 fitur yang memang belum pernah dikerjakan** (import/export terpusat, automated test).

---

## 2. Langkah Berikutnya (Prioritas)

Urutan prioritas mengikuti alasan akses: makin banyak yang kepakai, makin dulu dikerjakan.

### Prioritas 1 — App Mode Karyawan (semua karyawan pakai ini)

- [ ] Cek ulang halaman **Profile** vs prototype: belum ada kartu Milestones & leave banner kaya (masih 1 baris teks) — **ini temuan lama yang perlu dikonfirmasi ulang** karena riwayat audit sempat tidak konsisten antar upload zip.
- [ ] Cek ulang struktur halaman **Request**: prototype gabung semua jenis request (Koreksi/Izin/Sakit/Cuti/WFH/Lembur) jadi 1 form + 1 riwayat; WSM-Office pecah jadi 2 halaman (`leave/index` & `overtime/index`). "Koreksi Presensi" juga belum bisa diajukan sendiri oleh karyawan (cuma lewat Manajer/Owner via Rekap).
- [ ] Validasi ulang posisi kartu Memo & metric-grid Home — riwayat berubah-ubah di beberapa ronde audit sebelumnya, pastikan versi yang di-deploy adalah versi final yang benar.

### Prioritas 2 — CEO Dashboard (Owner, karena mencakup keseluruhan sistem)

- [ ] Audit visual menyeluruh tiap halaman (Executive Overview, Organization, Work Control, Finance, Royalty, HR Admin, Legal, IT) vs tampilan asli prototype — belum pernah dilakukan seteliti App Mode.
- [ ] "Executive People Overview" di prototype sengaja di-skip dan digabung ke `owner.dashboard` biasa — perlu keputusan final apakah ini cukup atau perlu halaman terpisah.
- [ ] Cek Timeline Calendar sisi Owner: saat ini link sidebar Owner mengarah ke kalender yang sama dengan employee (`employee.workTracker.calendar`) — perlu dipastikan tampilannya juga pas untuk konteks Owner (bukan cuma reuse mentah).

### Prioritas 3 — Dashboard Karyawan Berakses Tertentu (delegated access)

- [ ] Audit visual per modul (Work Control, Project Budgeting, Royalty, KPI, People & Leave, Contract Monitoring, Payroll) dari sudut pandang user **bukan Owner** yang di-assign akses View/Manage — pastikan sidebar & tampilan modul rapi saat kombinasi akses berbeda-beda (misal cuma punya akses ke 1-2 modul saja).
- [ ] Cek entry-point "Role Dashboard" (tombol di App Mode) tampil benar untuk kombinasi akses campuran, dan tombol "App Saya" untuk balik ke App Mode konsisten di semua modul.

### Backlog (belum prioritas, sudah disepakati dengan user)

- Halaman Import/Export terpusat (1 halaman untuk semua modul, bukan tersebar).
- Automated test (unit/feature) untuk controller-controller inti.

---

## 3. List Halaman & Fitur

Legenda: ✅ Sudah jalan sesuai/lebih baik dari prototype · 🟡 Jalan tapi perlu diperbaiki/dikembangkan · ❌ Belum ada · 💡 Saran tambahan (di luar prototype)

### 3.1 Web Publik

| Halaman                          | Status | Catatan                                                             |
| -------------------------------- | ------ | ------------------------------------------------------------------- |
| Home, About, Services            | ✅     |                                                                     |
| Careers (list + detail lowongan) | ✅     | Terhubung ke modul Rekrutmen HRD                                    |
| Form Lamaran Publik              | ✅     | Sesuai spek Fase 3: teks saja (nama/kontak/pesan), upload CV belum  |
| Contact                          | ✅     | Form publik nyambung DB, dikelola di `owner.contact-messages.index` |
| 💡 Upload CV di form lamaran     | ❌     | Sudah disepakati "menyusul", belum dikerjakan                       |

### 3.2 App Mode (Karyawan — semua role)

| Halaman/Fitur                             | Status | Catatan                                                                                                                                                                                 |
| ----------------------------------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Home (hero, absen, metric-grid jam kerja) | ✅     | Metric-grid "Working Hours Today"/"This Month" sudah ada di HomeController                                                                                                              |
| Kartu Memo "Info dari Owner"              | ✅     | Posisi persis setelah Hero, ring biru kalau unread                                                                                                                                      |
| My KPI, Milestones, Team Moments          | ✅     |                                                                                                                                                                                         |
| My Work Tracker (embedded di Home)        | ✅     | Sudah dikonfirmasi TIDAK perlu filter Project/Category/Progress — versi final prototype juga tanpa filter, audit lama yang bilang "ada filter" ternyata salah baca versi lama prototype |
| Shared Workload Calendar                  | ✅     | Full month grid, filter Project/PIC, weekly rhythm ambil jam kerja asli dari OfficeSetting                                                                                              |
| Inbox header (mail icon + badge)          | ✅     | Akses dari halaman manapun                                                                                                                                                              |
| Riwayat Presensi                          | ✅     |                                                                                                                                                                                         |
| Request & Correction                      | 🟡     | Pecah 2 halaman (leave & overtime) bukan 1 form gabungan seperti prototype; "Koreksi Presensi" self-service belum ada                                                                   |
| Halaman Lembur — WFO Overtime Rule box    | ✅     | Flat rate karyawan & aturan jam 20:00 sudah ditampilkan                                                                                                                                 |
| Profile                                   | 🟡     | Belum ada kartu Milestones; leave info masih 1 baris teks, bukan banner kaya seperti di Home                                                                                            |
| Role Dashboard entry point                | ✅     | 1 tombol di header, bukan item bottom-nav — sesuai keputusan desain                                                                                                                     |

### 3.3 CEO Dashboard (Owner)

| Halaman/Fitur                                                               | Status | Catatan                                                                                                                                                                                                                                                                                               |
| --------------------------------------------------------------------------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Sidebar 7 grup bernomor (PEOPLE s/d IT)                                     | ✅     | Struktur & urutan sudah dicocokkan ke `v18OwnerNav()` final prototype                                                                                                                                                                                                                                 |
| Executive Overview / ringkasan                                              | ✅     | Kartu-kartu placeholder lama ("Data aktif mulai Fase 7/9") sudah diisi data asli                                                                                                                                                                                                                      |
| Organization (struktur organisasi)                                          | ✅     |                                                                                                                                                                                                                                                                                                       |
| Work Control (Tracker, Timeline, Rapat, Memo Forum)                         | ✅     | Work Tracker dibangun sebagai kanban board (beda dari grouped-list prototype — keputusan eksplisit)                                                                                                                                                                                                   |
| Project Budgeting                                                           | ✅     |                                                                                                                                                                                                                                                                                                       |
| Royalty Dashboard                                                           | ✅     |                                                                                                                                                                                                                                                                                                       |
| Attendance, Requests (approval), KPI, Karyawan & Access, Contracts, Payroll | ✅     |                                                                                                                                                                                                                                                                                                       |
| HR/Geo/Color Settings                                                       | ✅     | Posisi & styling sidebar sudah dibetulkan (pola konsisten dgn section lain)                                                                                                                                                                                                                           |
| Legal (Song/Album, Royalty Agreements)                                      | ✅     | 1 controller, dibedakan lewat query `category`                                                                                                                                                                                                                                                        |
| IT (Audit Logs, System Change Log)                                          | 🟡     | View & controller ada, tapi `AuditLog::record()` cuma disambungkan ke 6 controller "aksi penting" (karyawan, akses modul, approval izin/lembur, payroll, office settings) — modul lain (Legal/Contracts/Budget/Royalty/KPI/Work/Recruitment, login/logout, lock/unlock) sengaja belum tercatat di log |
| Audit visual detail vs prototype                                            | ❌     | Belum pernah dilakukan (baru App Mode yang sudah diaudit visual berkali-kali)                                                                                                                                                                                                                         |

### 3.4 Dashboard Karyawan Berakses Tertentu (delegated)

| Halaman/Fitur                                                                                                            | Status | Catatan                                                                                                                                              |
| ------------------------------------------------------------------------------------------------------------------------ | ------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Arsitektur permission-based (per-user per-modul, No Access/View/Manage)                                                  | ✅     | Sudah menggantikan sistem role-based lama sejak Fase 6                                                                                               |
| Semua 10 modul (`work, budget, royalty, kpi, people, contracts, payroll, legal, it, recruitment`) sudah punya view nyata | ✅     | `legal`, `it`, `recruitment` adalah modul tambahan WSM Office di luar prototype                                                                      |
| Sidebar menyesuaikan kombinasi akses user                                                                                | ✅     | Grup header cuma nongol kalau minimal 1 item di bawahnya kelihatan                                                                                   |
| Preview tampilan dari sudut pandang delegated user (bukan Owner)                                                         | ❌     | Belum pernah divalidasi end-to-end — risiko: layout/behaviour yang cuma dicek dari akun Owner (full access) bisa beda saat user cuma punya 1-2 modul |
| 💡 Kolom warna project & "weekly rhythm" per hari di DB                                                                  | ❌     | Saat ini hardcoded/deterministik di kalender (bukan bug, keputusan sadar karena skema DB belum punya kolom ini)                                      |

### 3.5 Sistem/Infrastruktur

| Item                                             | Status | Catatan                                                        |
| ------------------------------------------------ | ------ | -------------------------------------------------------------- |
| SweetAlert untuk semua alert/validasi/konfirmasi | ✅     |                                                                |
| Halaman error custom 403/404/500/503             | ✅     |                                                                |
| Import/Export terpusat                           | ❌     | Disepakati 1 halaman untuk semua modul, belum ada implementasi |
| Automated test                                   | ❌     | Cuma skeleton Laravel default                                  |
| Storage symlink (upload file)                    | ✅     | Sudah terpasang di hosting                                     |

---

## 4. Flow per POV

### 4.1 Publik (belum login)

`Home` → jelajah `About` / `Services` → lihat `Careers` → buka detail lowongan → isi **Form Lamaran** (teks) → masuk ke pipeline HRD (`Baru`).
Atau: `Contact` → isi form → masuk ke `owner.contact-messages.index` untuk ditindaklanjuti Owner/HRD.

### 4.2 Karyawan Biasa (semua karyawan, tanpa akses dashboard modul)

1. Login → langsung ke **App Mode** (`employee.home`), bukan dashboard apapun.
2. Home: absen (check-in/out), lihat metric jam kerja, KPI pribadi, memo dari Owner, milestone, Team Moments, My Work Tracker (kalau ada task), akses Inbox lewat header.
3. Navigasi bottom-nav: Home → Riwayat (histori presensi) → Request (ajukan izin/cuti/WFH/lembur — 2 halaman terpisah) → Profile (ganti password, data diri, foto).
4. **Tidak ada** tombol "Role Dashboard" karena tidak punya akses modul apapun (`hasAnyDashboardAccess() === false`).

### 4.3 Karyawan dengan Akses Tertentu (delegated — misal Manajer/HRD dapat akses 1-2 modul)

1. Login → tetap mendarat di **App Mode** dulu (sama seperti karyawan biasa) — keputusan desain: semua orang mulai dari App Mode.
2. Di header App Mode muncul tombol **"Role Dashboard"** (karena `hasAnyDashboardAccess() === true`).
3. Klik tombol → masuk ke layout dashboard (`layouts/app.blade.php`) dengan sidebar yang **cuma menampilkan grup & item sesuai modul yang di-assign** (misal cuma "2 · WORK CONTROL" kalau cuma dapat akses `work`).
4. Selesai kerja di dashboard → tombol balik ke **"App Saya"** untuk kembali ke App Mode (perlu App Mode juga untuk absen/cuti pribadi).
5. Level akses (`View` vs `Manage`) menentukan apakah tombol create/edit/delete muncul di modul tsb.

### 4.4 Owner

1. Login → **juga** mendarat di App Mode dulu (sama seperti karyawan lain — Owner tetap perlu absen/cuti sendiri).
2. Tombol "Role Dashboard" di header → masuk ke **CEO Dashboard**, tapi sidebar Owner otomatis full-manage semua 10 modul + section khusus Owner-only: `Organization`, `Karyawan & Access`, `HR/Geo/Color Settings`.
3. Owner adalah satu-satunya role yang bisa assign/ubah akses dashboard modul milik orang lain (lewat `owner.employees` → edit → set access level per modul).
4. Owner juga punya tombol balik ke "App Saya" (App Mode), sama seperti delegated user.

---

## 5. Fase-Fase

- ✅ **Fase 1** — Sistem internal dasar (autentikasi, struktur awal)
- ✅ **Fase 2** — Perluasan scope: web publik + landing rekrutmen
- ✅ **Fase 3** — Rekrutmen (HRD): form lamaran publik, pipeline 5 tahap, akses HRD & Owner
- ✅ **Fase 4** — SweetAlert (semua alert/validasi/konfirmasi) + halaman error custom (403/404/500/503)
- ✅ **Fase 5** — QA menyeluruh (migrate:fresh --seed, cek manual Edit Karyawan/Office Settings/auto-close/Izin-Cuti/Lembur) — lolos end-to-end
- ✅ **Fase 6** — Refactor ke sistem akses **permission-based** (per-user per-modul: No Access/View/Manage), gantikan role-based lama; fix gap navigasi Manajer; entry point 1 tombol "Role Dashboard"
- ✅ **Fase 7** — Payroll records disiapkan (skema data)
- 🟡 **Fase 8** — _(tidak ada catatan terpisah — kemungkinan tergabung ke fase lain di histori commit)_
- ✅ **Fase 9** — Inbox header, Work Tracker admin (kanban board — beda sengaja dari prototype), Minutes of Meeting, sidebar stretch fix, auto-mark-read inbox
- ⏭️ **Fase 10** — KPI bulk-assign — **di-skip sengaja** atas permintaan user, belum dikerjakan
- ✅ **Fase 11** — Employee Contracts Management
- ✅ **Fase 12** — Payroll (generate/update/finalize/markPaid/destroy), deduction rate global di Office Settings
- ✅ **Fase 13** — Budget & Royalty
- ✅ **Fase 14** — Legal & Compliance (Album Contracts + Royalty Agreements, 1 controller 2 kategori)
- 🟡 **Fase 15** — IT (Audit Log read-only + System Changelog CRUD) — **view & controller lengkap**, tapi instrumentasi `AuditLog::record()` cuma di 6 controller aksi penting, belum menyeluruh (keputusan sadar, bukan kelupaan)
- 🟡 **Fase 16** — CEO Dashboard IA Restructure + polish UI/UX proyek-lebar — **App Mode sudah diaudit & dipolish berkali-kali (beberapa ronde)**; **CEO Dashboard & dashboard delegated belum pernah diaudit visual sedetail App Mode** — ini fase yang sedang berjalan sekarang

---

## 6. Struktur Kode Project

```
WSM-Office/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php          — Login/logout karyawan & owner (1 sistem auth sama)
│   │   │   ├── Public/PageController.php         — Halaman publik (Home/About/Services/Careers/Contact)
│   │   │   ├── ContactMessageController.php      — Terima form kontak publik → simpan DB
│   │   │   ├── Recruitment/
│   │   │   │   ├── JobOpeningController.php      — CRUD lowongan kerja (HRD/Owner)
│   │   │   │   └── JobApplicationController.php  — Pipeline pelamar 5 tahap (Baru→Diterima/Ditolak)
│   │   │   ├── Employee/
│   │   │   │   ├── HomeController.php            — Home App Mode: absen, metric jam kerja, agregasi kartu
│   │   │   │   ├── AttendanceController.php      — Check-in/out, riwayat presensi
│   │   │   │   ├── LeaveRequestController.php    — Ajukan izin/cuti (sisi karyawan)
│   │   │   │   ├── OvertimeRequestController.php — Ajukan lembur (sisi karyawan)
│   │   │   │   ├── ProfileController.php         — Profile, ganti password, upload foto
│   │   │   │   ├── WorkTrackerController.php     — Shared Workload Calendar (full month grid)
│   │   │   │   └── MemoInteractionController.php — Balas/baca memo dari Owner (thread)
│   │   │   ├── Approval/
│   │   │   │   ├── LeaveRequestController.php    — Approve/reject izin-cuti bawahan (Manajer/HRD/Owner)
│   │   │   │   └── OvertimeRequestController.php — Approve/reject lembur bawahan
│   │   │   ├── Attendance/RecapController.php    — Rekap & koreksi presensi tim (Manajer/HRD/Owner)
│   │   │   ├── Owner/
│   │   │   │   ├── DashboardController.php       — Ringkasan utama CEO Dashboard (kartu statistik)
│   │   │   │   ├── EmployeeController.php        — CRUD karyawan + assign dashboard access per modul
│   │   │   │   ├── DashboardAccessController.php — Ubah level akses modul (View/Manage/None) per user
│   │   │   │   ├── OfficeSettingController.php   — Jam kerja, lokasi kantor, warna tema, dsb
│   │   │   │   └── OrganizationController.php    — Struktur organisasi
│   │   │   └── Dashboard/
│   │   │       ├── DashboardController.php       — Landing modul dashboard generik (permission-based)
│   │   │       ├── DashboardLockController.php   — Lock/unlock CEO Dashboard
│   │   │       ├── Work/
│   │   │       │   ├── WorkTrackerBoardController.php — Kanban board Work Tracker (admin)
│   │   │       │   ├── MeetingController.php          — Minutes of Meeting + action items
│   │   │       │   └── MemoController.php             — Memo Forum (broadcast dari Owner/Manajemen)
│   │   │       ├── Budget/BudgetController.php   — Project Budgeting (budget vs actual)
│   │   │       ├── Royalty/RoyaltyController.php — Royalty, share, recoupment
│   │   │       ├── Kpi/KpiController.php         — KPI & Performance seluruh tim
│   │   │       ├── Contracts/ContractController.php — Status & file kontrak karyawan
│   │   │       ├── Payroll/PayrollController.php — Generate/finalize/markPaid payroll
│   │   │       ├── Legal/LegalController.php     — Song/Album Contracts + Royalty Agreements
│   │   │       └── It/
│   │   │           ├── AuditLogController.php        — Lihat audit log (read-only)
│   │   │           └── SystemChangelogController.php — CRUD changelog sistem
│   │   └── Middleware/
│   │       ├── EnsureRole.php             — Guard akses berdasar role lama (dipakai di Kelola Tim/rekap)
│   │       ├── EnsureModuleAccess.php     — Guard akses berdasar dashboard_access (View/Manage per modul)
│   │       └── EnsureDashboardUnlocked.php — Guard CEO Dashboard harus dalam status "unlocked"
│   └── Models/
│       ├── User.php              — Karyawan/Owner; punya accessLevel(), canViewModule(), canManageModule()
│       ├── Attendance.php        — Record absen (check-in/out, geo, mode WFO/WFH)
│       ├── LeaveRequest.php / OvertimeRequestController-related model OvertimeRequest — Request izin/cuti/lembur
│       ├── DashboardAccess.php   — Daftar 10 modul + level akses per user
│       ├── WorkItem.php / Project.php — Task & project Work Tracker
│       ├── Meeting.php / MeetingActionItem.php — MoM & action item
│       ├── Memo.php / MemoThreadMessage.php / MemoRead.php — Memo Forum + thread balasan + status baca
│       ├── ProjectBudget.php     — Data Project Budgeting
│       ├── RoyaltyEntry.php      — Data Royalty Dashboard
│       ├── Kpi.php               — Data KPI karyawan
│       ├── EmployeeContract.php  — Kontrak kerja karyawan
│       ├── PayrollRecord.php     — Slip & status payroll per karyawan per bulan
│       ├── LegalDocument.php     — Dokumen Legal (album/royalty), isExpiringSoon()
│       ├── AuditLog.php          — Log aksi penting (record() helper, disambungkan parsial)
│       ├── SystemChangelog.php   — Riwayat perubahan sistem (ditampilkan di IT)
│       ├── OfficeSetting.php     — Jam kerja, lokasi kantor, rate potongan, tema warna (singleton)
│       ├── JobOpening.php / JobApplication.php — Lowongan & pelamar
│       └── Contactmessage.php    — Pesan dari form kontak publik
├── database/
│   ├── migrations/        — Semua skema tabel (attendance, dashboard_access, payroll_records, dst)
│   └── seeders/
│       ├── DemoSeeder.php          — Data dummy realistis (karyawan, task, project, dst) buat testing
│       └── OfficeSettingSeeder.php — Seed alamat kantor asli (Jl. Raya Tapos No.43, Depok) & jam kerja default
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php       — Layout dashboard (dipakai bareng Owner & delegated user, sidebar dinamis)
│   │   ├── employee.blade.php  — Layout App Mode (mobile-first, bottom-nav)
│   │   ├── public.blade.php    — Layout web publik
│   │   └── error.blade.php     — Layout halaman error custom
│   ├── public/          — Home/About/Services/Careers/Contact (web publik)
│   ├── auth/login.blade.php
│   ├── employee/        — Semua halaman App Mode (home, leave, overtime, profile, work-tracker, attendance)
│   ├── owner/            — Halaman khusus Owner (dashboard, employees, organization, office-settings)
│   ├── dashboard/        — Halaman per modul (work, budget, royalty, kpi, contracts, payroll, legal, it)
│   ├── approval/         — Halaman approval izin & lembur (Manajer/HRD/Owner)
│   ├── attendance/recap/ — Rekap & koreksi presensi tim
│   ├── recruitment/      — Kelola lowongan & pipeline pelamar (HRD/Owner)
│   ├── memo/_thread.blade.php — Komponen thread balasan memo
│   └── errors/           — 403/404/500/503
└── routes/web.php        — ±140 route terdaftar, digroup per middleware (auth/role/module-access)
```

---

## Catatan Metodologi Audit

Perbandingan dilakukan dengan membaca **fungsi versi paling akhir** di `index.html` prototype (fungsi JS di file ini saling menimpa nama yang sama — versi yang dipakai browser adalah definisi paling bawah/terakhir di file, bukan yang pertama muncul). Beberapa temuan audit sesi-sesi sebelumnya ternyata salah karena membaca versi fungsi yang sudah digantikan (contoh: filter Work Tracker, Shared Calendar 14-hari) — sudah dikoreksi di sesi ini dan tercatat sebagai komentar di kode terkait.
