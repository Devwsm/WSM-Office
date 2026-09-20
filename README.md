# WSM-Office (W.O.S 2.0) — Laravel

Sistem manajemen kantor internal Whisnu Santika Music (WSM), hasil implementasi dari prototype `WOS_2_0_App_v32` (HTML/JS satu file) ke aplikasi Laravel multi-halaman yang akan di-deploy dan diakses publik.

> **Audit dilakukan 2026-09-19** terhadap `WSM-Office.zip` (snapshot 2026-09-19) dan `WOS_2_0_App_v32.zip`. Metode: baca kode + tes langsung (lihat bagian _Yang diuji_ di Bab 2). Semua klaim di dokumen ini berasal dari kode atau hasil tes, bukan asumsi.

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

| #                                                                 | Halaman / fitur                                                                                     | Prototype v32                                                                | Project Laravel                                                                                                                                       | Status                    | Siapa yang bisa akses                |
| ----------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ------------------------------------ |
| **Auth & sesi**                                                   |                                                                                                     |                                                                              |                                                                                                                                                       |                           |                                      |
| 1                                                                 | Login                                                                                               | Pilih nama + password                                                        | Email + password, 1 form semua role, throttle (429 teruji)                                                                                            | ✅ (beda disengaja)       | Tamu                                 |
| 2                                                                 | Lock Dashboard                                                                                      | `sessionStorage` (client)                                                    | Session server-side, per user                                                                                                                         | ✅ (lebih aman)           | Semua di area dashboard              |
| 3                                                                 | Ganti password                                                                                      | Ada                                                                          | Di halaman Profil                                                                                                                                     | ✅                        | Semua akun                           |
| 4                                                                 | Lupa/reset password                                                                                 | —                                                                            | Belum ada                                                                                                                                             | ❌ (ditunda)              | —                                    |
| **Aplikasi karyawan (`/app`)**                                    |                                                                                                     |                                                                              |                                                                                                                                                       |                           |                                      |
| 5                                                                 | Home (kartu absensi, KPI, milestone, sisa cuti, Team Moments, My Work Tracker, Info dari Owner)     | Ada                                                                          | Ada semua                                                                                                                                             | ✅                        | Semua akun                           |
| 6                                                                 | Absen masuk/pulang: kantor, WFH, lapangan, gigs (multi-sesi)                                        | Ada                                                                          | Ada; geofence dihitung ulang di server (Haversine), selfie, auto-close sesi lupa pulang                                                               | ✅                        | Semua akun                           |
| 7                                                                 | Riwayat absensi                                                                                     | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Semua akun                           |
| 8                                                                 | Pengajuan izin/cuti + batal                                                                         | Ada                                                                          | Ada; persetujuan atasan langsung                                                                                                                      | ✅                        | Semua akun                           |
| 9                                                                 | Lembur                                                                                              | Ada                                                                          | Ada; tarif flat per pengajuan disetujui                                                                                                               | ✅                        | Semua akun                           |
| 10                                                                | Koreksi presensi                                                                                    | Ada                                                                          | Ada + halaman approval                                                                                                                                | ✅                        | Semua akun / Manajer & Owner memutus |
| 11                                                                | Kalender tim                                                                                        | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Semua akun                           |
| 12                                                                | Memo: baca, sembunyikan, balas thread                                                               | Ada                                                                          | Ada (tabel `memo_reads`, `memo_thread_messages`)                                                                                                      | ✅                        | Sesuai audiens memo                  |
| 13                                                                | Profil                                                                                              | Ada + foto profil                                                            | Tanpa foto profil                                                                                                                                     | ⚠️ (foto ditunda)         | Semua akun                           |
| 14                                                                | Tema per user                                                                                       | Ada                                                                          | Belum                                                                                                                                                 | ❌ (ditunda)              | —                                    |
| **Area Owner (`/owner`)**                                         |                                                                                                     |                                                                              |                                                                                                                                                       |                           |                                      |
| 15                                                                | Dashboard Owner                                                                                     | Ringkasan + ritme mingguan bisa diedit                                       | Ringkasan ada; **ritme mingguan hard-code** di controller                                                                                             | ⚠️                        | Owner                                |
| 16                                                                | Kelola karyawan (CRUD, role, atasan, gaji, soft delete)                                             | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Owner                                |
| 17                                                                | Organization (org-chart)                                                                            | Ada                                                                          | Ada (dari `manager_id`)                                                                                                                               | ✅                        | Owner                                |
| 18                                                                | Akses dashboard per modul (view/manage)                                                             | Ada                                                                          | Ada, 10 modul                                                                                                                                         | ✅                        | Owner                                |
| 19                                                                | Pengaturan kantor (geo, jam, warna)                                                                 | Jam, break, toleransi, blok potongan, jam lembur, auto-close, geo, warna     | Geo, jam kerja, tarif potongan, warna. **Tidak ada:** blok potongan (fix 60 mnt), jam mulai lembur, toggle auto-close                                 | ⚠️                        | Owner                                |
| 20                                                                | Executive People Overview                                                                           | Ada                                                                          | Sengaja tidak dibuat                                                                                                                                  | ❌ (disengaja)            | —                                    |
| 21                                                                | Pesan kontak (dari form publik)                                                                     | —                                                                            | Ada                                                                                                                                                   | ➕                        | Owner                                |
| **Modul dashboard (`/dashboard`, dijaga `module:x,view/manage`)** |                                                                                                     |                                                                              |                                                                                                                                                       |                           |                                      |
| 22                                                                | Work Tracker (board, item, PIC, progres, link)                                                      | Ada                                                                          | Ada + kanban + kalender                                                                                                                               | ✅                        | Modul `work`                         |
| 23                                                                | Import Work Tracker CSV/XLSX                                                                        | Ada (v32)                                                                    | Ada, dengan pratinjau sebelum konfirmasi                                                                                                              | ✅                        | Modul `work` (manage)                |
| 24                                                                | Projects                                                                                            | Ada                                                                          | Ada, digabung ke Work Tracker (warna per project)                                                                                                     | ✅                        | Modul `work`                         |
| 25                                                                | Timeline Calendar                                                                                   | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `work`                         |
| 26                                                                | MoM / Meeting + action item                                                                         | Ada                                                                          | Ada, action item terhubung ke Work Item, cetak PDF                                                                                                    | ✅                        | Modul `work`                         |
| 27                                                                | Memo Forum (admin)                                                                                  | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `work`                         |
| 28                                                                | Assign / Reminder dari dashboard                                                                    | Ada                                                                          | Belum ada                                                                                                                                             | ❌                        | —                                    |
| 29                                                                | Rekap absensi (+ detail per orang, koreksi manual)                                                  | Ada                                                                          | Ada + export Excel/PDF                                                                                                                                | ✅                        | Modul `people`                       |
| 30                                                                | Approval izin/cuti, lembur, koreksi presensi                                                        | Ada + Management Override                                                    | Ada; Owner boleh memutus siapa saja (= override); HRD tidak ikut                                                                                      | ✅                        | Manajer (bawahan), Owner             |
| 31                                                                | KPI & Performance                                                                                   | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `kpi`                          |
| 32                                                                | Employee Contracts                                                                                  | Ada (file, tanggal, catatan)                                                 | Ada; **file di disk publik** (lihat Bab 4)                                                                                                            | ✅ (⚠️ keamanan)          | Modul `contracts`                    |
| 33                                                                | Payroll                                                                                             | Estimasi on-the-fly: hari absen × gaji÷22, kurang jam × tarif/jam, THP min 0 | Disimpan per bulan (draft → final → paid). **Tidak memotong hari tanpa absensi**, potongan = blok 60 mnt × tarif flat, total tidak dibatasi minimal 0 | ⚠️                        | Modul `payroll`                      |
| 34                                                                | Slip payroll PDF                                                                                    | Ada                                                                          | Ada                                                                                                                                                   | ✅                        | Modul `payroll`                      |
| 35                                                                | Project Budgeting                                                                                   | Budget vs actual per project                                                 | CRUD flat per baris                                                                                                                                   | ⚠️ (lebih sederhana)      | Modul `budget`                       |
| 36                                                                | Royalty Dashboard                                                                                   | Waterfall recoupment per lagu, ledger kuartal, sinkron Google Sheet          | CRUD flat: gross, share %, recoup, status bayar. Tanpa waterfall/ledger/sinkron                                                                       | ⚠️ (jauh lebih sederhana) | Modul `royalty`                      |
| 37                                                                | Legal: Album Contracts & Royalty Agreements                                                         | Ada                                                                          | Ada (1 tabel, dibedakan `category`)                                                                                                                   | ✅                        | Modul `legal`                        |
| 38                                                                | IT: Audit Log                                                                                       | Ada                                                                          | Ada (read-only)                                                                                                                                       | ✅                        | Modul `it`                           |
| 39                                                                | IT: System Change Log                                                                               | Ada                                                                          | Ada (CRUD)                                                                                                                                            | ✅                        | Modul `it`                           |
| 40                                                                | Team Overview manajer                                                                               | —                                                                            | Grup route kosong (TODO)                                                                                                                              | ❌                        | —                                    |
| 41                                                                | Team Groups, editor landing page                                                                    | Ada                                                                          | Belum ada                                                                                                                                             | ❌ (ditunda)              | —                                    |
| **Tambahan (tidak ada di prototype)**                             |                                                                                                     |                                                                              |                                                                                                                                                       |                           |                                      |
| 42                                                                | Rekrutmen: lowongan + pipeline pelamar + konversi jadi karyawan                                     | —                                                                            | Ada                                                                                                                                                   | ➕                        | Modul `recruitment` (HRD, Owner)     |
| 43                                                                | Export & Import Center (14 export: Excel 11 + PDF 3; 4 import: Work Tracker, Karyawan, KPI, Budget) | Hanya import Work Tracker                                                    | Ada, berbasis katalog & gerbang modul                                                                                                                 | ➕                        | Sesuai modul masing-masing           |
| 44                                                                | Halaman publik (Beranda, Tentang, Layanan, Kontak)                                                  | Ada editor landing                                                           | Ada, tetapi **teks masih placeholder**                                                                                                                | ⚠️                        | Publik                               |
| 45                                                                | Karir publik + form lamaran                                                                         | —                                                                            | Ada; **belum ada upload CV & link portofolio**                                                                                                        | ⚠️                        | Publik                               |
| 46                                                                | Form kontak publik                                                                                  | —                                                                            | Tersimpan ke DB (`contact_messages`), throttle                                                                                                        | ✅                        | Publik                               |

**Hitungan status (46 baris):** ✅ 29 · ⚠️ 8 · ❌ 6 · ➕ 3 (pesan kontak, rekrutmen, Export & Import).

**Estimasi kesesuaian dengan prototype** (kasar, berdasarkan bobot fitur): fitur harian inti (absensi, pengajuan, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) **±90%**; modul finansial (payroll, budget, royalty) **±45–55%**; halaman publik **belum siap tayang** karena konten placeholder.

### 2.2 Yang diuji (tes langsung pada 2026-09-19)

| Tes                                                                                                                         | Hasil                                                                                                    |
| --------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| `php -l` seluruh 180 file PHP                                                                                               | ✅ 0 error sintaks                                                                                       |
| `migrate:fresh --seed` di MariaDB 10.11 (40 migrasi + 3 seeder)                                                             | ✅ sukses                                                                                                |
| Smoke test 60 URL GET × 6 sudut pandang (tamu, Owner, Manajer, Karyawan work-manage, Karyawan work-view, HRD) = 360 request | ✅ 0 error 500; tamu diarahkan ke `/login` di semua halaman terproteksi; 403/200 mengikuti matriks akses |
| Login benar / salah / brute-force                                                                                           | ✅ redirect sesuai role; 429 setelah beberapa percobaan                                                  |
| Form kontak publik                                                                                                          | ✅ tersimpan ke `contact_messages`                                                                       |
| Form lamaran kerja dengan `cv` + `portfolio_url`                                                                            | ⚠️ lamaran tersimpan, **CV dan link portofolio diabaikan** (kolom tidak ada di tabel)                    |
| Generate payroll untuk karyawan tanpa satu pun absensi di bulan itu                                                         | ⚠️ potongan = 0, gaji penuh Rp 6.500.000 dibayar                                                         |

**Belum diuji:** tampilan/UI di browser, geolocation & kamera di HP, upload file nyata, isi file hasil export Excel/PDF, alur import dengan file nyata, email, dan PHP 8.4 (aplikasi diuji di PHP 8.3 dengan `platform_check` dimatikan; MySQL asli diganti MariaDB). Tes browser manual per halaman tetap diperlukan sebelum go-live.

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
│   │   │   └── RecapController.php         # Rekap absensi + detail per orang (modul people)
│   │   ├── Auth/
│   │   │   └── LoginController.php         # Login/logout 1 form semua role, redirect per role
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php     # Landing dashboard per modul + halaman modul placeholder
│   │   │   ├── DashboardLockController.php # Lock/unlock dashboard (server-side)
│   │   │   ├── Budget/BudgetController.php       # CRUD project budgeting
│   │   │   ├── Contracts/ContractController.php  # CRUD kontrak karyawan + upload file
│   │   │   ├── ExportImport/
│   │   │   │   ├── ExportImportController.php    # Halaman pusat Export & Import
│   │   │   │   ├── ExportController.php          # Semua export (route generik per key/format)
│   │   │   │   └── ImportController.php          # Semua import (upload → pratinjau → konfirmasi)
│   │   │   ├── It/
│   │   │   │   ├── AuditLogController.php        # Daftar audit log (read-only)
│   │   │   │   └── SystemChangelogController.php # CRUD changelog sistem
│   │   │   ├── Kpi/KpiController.php             # CRUD KPI seluruh tim
│   │   │   ├── Legal/LegalController.php         # CRUD Album Contracts & Royalty Agreements
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
    └── ExportImport/
        ├── ExportCatalog.php               # Sumber tunggal: modul mana punya export/import + gerbang aksesnya
        └── ImportPreviewService.php        # Simpan upload sementara & pratinjau sebelum konfirmasi

bootstrap/
├── app.php                                 # Konfigurasi app, alias middleware, routing
└── cache/                                  # Cache framework

config/
├── app.php                                 # Timezone Asia/Jakarta, locale
├── auth.php · cache.php · database.php     # Konfigurasi bawaan Laravel
├── filesystems.php                         # Disk `public` dipakai upload kontrak/legal/selfie
├── logging.php · mail.php · queue.php
├── services.php
└── session.php                             # Session 120 menit, driver database

database/
├── factories/
│   └── UserFactory.php                     # Factory user untuk testing
├── migrations/                             # 40 migrasi berurutan (users → sesi, absensi, modul dashboard, rekrutmen, kontak)
│   ├── ..._dashboard_access.php            # Tabel akses modul
│   ├── ..._add_legal_and_it_modules_to_dashboard_access.php  # ALTER ... MODIFY ENUM — khusus MySQL/MariaDB
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
    └── errors/                             # 403, 404, 500, 503

routes/
├── web.php                                 # 153 route: publik, /app, /owner, /dashboard, rekrutmen, approval
├── auth.php                                # Login/logout
└── console.php                             # Definisi command console

public/
├── index.php                               # Front controller
├── robots.txt
├── favicon.ico
├── hot                                     # ⚠ HAPUS: penanda dev-server Vite (merusak produksi)
├── fonts-manifest.dev.json                 # File dev, hapus
└── build/                                  # ⚠ BELUM ADA: hasil `npm run build`, wajib di-upload

storage/
├── app/public/                             # Upload: selfie absensi, kontrak, dokumen legal (perlu dipindah ke private)
├── framework/views/                        # Cache Blade (108 file sisa dev, kosongkan)
└── logs/laravel.log                        # ⚠ ±2 MB berisi path lokal `C:/Users/...`, jangan di-upload

tests/
├── Feature/ExampleTest.php                 # Stub bawaan (menguji GET / = 200)
└── Unit/ExampleTest.php                    # Stub bawaan — belum ada tes nyata

(root) .env · .env.example · .gitignore · composer.json/lock · package.json · phpunit.xml · vite.config.js
       AGENTS.md · CLAUDE.md               # File catatan repo, tidak perlu ikut di-deploy
       README.md                            # Kosong (0 byte) di snapshot yang diaudit; digantikan file ini
```

---

## 4. Langkah Selanjutnya

### 4.1 Blocker deploy (wajib beres sebelum publik)

| #   | Masalah                               | Bukti                                                                                                                                                                                       | Tindakan                                                                                                                                                                                                                                           |
| --- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Versi PHP**                         | `vendor/composer/platform_check.php` menolak PHP < 8.4.1 karena `symfony/clock`, `css-selector`, `event-dispatcher`, `string`, `translation` v8.1.x, padahal `composer.json` menulis `^8.3` | Cek apakah Rumahweb menyediakan PHP 8.4. Jika hanya 8.3: set `config.platform.php = 8.3.0` lalu `composer update` agar paket Symfony turun ke 7.4                                                                                                  |
| 2   | **`.env` produksi**                   | Isi saat ini `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL` localhost, DB user `root`                                                                                                         | Buat `.env` baru: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, user DB khusus, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`                                                                                            |
| 3   | **Struktur folder cPanel**            | Tidak ada `.htaccess` di root; jika project diletakkan utuh di `public_html`, `.env` dan `app/` bisa diakses lewat URL                                                                      | Taruh project di luar `public_html`, isi `public/` ke `public_html`, sesuaikan path di `index.php`                                                                                                                                                 |
| 4   | **Aset Vite**                         | `public/hot` ada, `public/build` tidak ada                                                                                                                                                  | Hapus `hot`, jalankan `npm run build` lokal, upload `public/build`                                                                                                                                                                                 |
| 5   | **File sensitif terbuka tanpa login** | Kontrak karyawan, dokumen legal, selfie absensi disimpan di disk `public` dan dilink `asset('storage/...')`                                                                                 | Pindah ke disk `local` (private) + route unduh yang mengecek modul/pemilik. Sekaligus menghilangkan kebutuhan `storage:link` yang tidak bisa dijalankan tanpa terminal                                                                             |
| 6   | **Password default "password"**       | `EmployeeImport` mengisi "password" jika kosong; tidak ada paksaan ganti                                                                                                                    | Tambah kolom `must_change_password` dan redirect paksa ke ganti password saat login pertama                                                                                                                                                        |
| 7   | **Database tanpa terminal**           | `migrate` tidak bisa dijalankan di server                                                                                                                                                   | Jalankan `migrate` + `OfficeSettingSeeder` lokal, ekspor SQL, impor via phpMyAdmin. **Jangan** jalankan `DemoSeeder`. Catatan: migrasi `..._add_legal_and_it_modules...` memakai `MODIFY ENUM`, jadi hanya jalan di MySQL/MariaDB, tidak di SQLite |
| 8   | **Bersihkan paket upload**            | Zip berisi `.git`, `node_modules`, `laravel.log`, `database.sqlite`, cache view, `fonts-manifest.dev.json`, `AGENTS.md`, `CLAUDE.md`                                                        | Jangan di-upload                                                                                                                                                                                                                                   |
| 9   | **Konten publik placeholder**         | Beranda, Tentang, Layanan, Kontak masih berisi teks "Placeholder" (alamat, telepon, email)                                                                                                  | Isi konten asli (tetap statis di Blade sesuai keputusan)                                                                                                                                                                                           |

### 4.2 Fitur sesuai keputusan yang belum dibangun

1. **Payroll potong hari tanpa absensi** dan tetap bisa dikoreksi sampai hari gaji. Rekomendasi: hitung hari kerja tanpa sesi absen dan tanpa izin/cuti/lembur disetujui sebagai "alpha"; potong dengan `gaji ÷ hari kerja`; batasi total minimal 0; izinkan `finalized` dibuka kembali (dengan audit log) sampai status `paid`.
2. **Lamaran kerja: upload CV + link portofolio.** Tambah kolom `cv_path`, `portfolio_url` di `job_applications`, validasi (pdf/doc, ukuran maks), simpan di disk **private**, tampilkan di panel pelamar dan export.
3. **Prioritas harian (absensi & Work Tracker):** fitur di sisi ini sudah ±90% sesuai; sisa gap utama adalah _Assign/Reminder_ dari dashboard dan ritme mingguan yang masih hard-code.
4. **Tampilan teks:** UI berbahasa Inggris, teks penting/rawan salah paham berbahasa Indonesia. Saat ini banyak label campur; rapikan saat mengisi konten publik.

### 4.3 Kualitas & keamanan (sebaiknya sebelum/segera setelah go-live)

- **Tes otomatis:** baru 2 stub bawaan. Minimal tulis Feature test untuk: matriks akses per role, alur absen, approval, generate payroll, import karyawan. Smoke test yang dipakai saat audit ini bisa dijadikan dasarnya.
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
2. Blocker 5 (file private) dan 6 (ganti password paksa) — perubahan kode terkecil dengan risiko terbesar.
3. Payroll (4.2 no. 1) dan CV upload (4.2 no. 2).
4. Isi konten publik, siapkan `.env` produksi, `npm run build`, impor SQL.
5. Uji manual di browser per halaman per role (termasuk HP untuk absensi) di staging/subdomain, baru buka ke publik.

---

## 5. Kesimpulan

WSM-Office **sudah menjadi aplikasi yang utuh dan stabil secara teknis**: seluruh 180 file PHP lolos lint, 40 migrasi dan seeder berjalan, dan 360 request uji (60 halaman × 6 sudut pandang) tidak menghasilkan satu pun error 500, dengan pembatasan akses yang berperilaku sesuai rancangan. Fitur inti harian dari prototype (absensi lengkap dengan geofence dan selfie, izin/cuti/lembur/koreksi dengan approval, Work Tracker, meeting, memo, KPI, kontrak, legal, audit) sudah ada dan sesuai, ditambah rekrutmen serta pusat Export/Import yang tidak ada di prototype.

Kesenjangan terbesar ada di **modul finansial**: Payroll ada tetapi memakai aturan potongan yang berbeda dan belum memotong hari tanpa absensi, sementara Budget dan Royalty baru berupa CRUD sederhana dibanding mesin recoupment di prototype. Halaman publik dan form lamaran juga belum siap tayang (konten placeholder, belum ada upload CV/portofolio).

Yang membuat aplikasi **belum layak dibuka ke publik saat ini** bukan kekurangan fitur, tetapi kesiapan deploy: kebutuhan PHP 8.4.1, `.env` mode lokal/debug, struktur folder cPanel, aset Vite yang belum dibangun, file kontrak dan selfie yang bisa diakses tanpa login, serta password default "password" untuk karyawan hasil import. Semuanya bisa diselesaikan dalam skala hari, bukan minggu.

**Ringkas:** fungsional ±80% dari prototype (±90% untuk fitur harian, ±50% untuk finansial); kesiapan produksi masih perlu 9 blocker pada Bab 4.1 sebelum go-live.
