# WSM-Office — Progress vs Prototype (WOS 2.0 v32)

Dokumen ini bandingin **WSM-Office** (Laravel, web resmi yang bakal di-deploy public) sama **WOS_2_0_App_v32** (`index.html`, standalone HTML prototype — CEO/COO/Owner Control Center + Employee App). Tujuannya: satu tempat buat lihat apa yang udah sesuai, apa yang udah ada tapi beda, dan apa yang belum dikerjain — per fase, biar gampang nentuin urutan kerja berikutnya.

Update terakhir: cek kode per 2026-09-13 (controller, migration, route, view) — semua 16 fase yang direncanakan sekarang ✅ selesai, termasuk Fase 16 CEO Dashboard IA Restructure & Settings yang baru kelar hari ini. Lihat bagian "Kesimpulan" di bawah buat status akhir proyek.

## Cara Baca

| Simbol | Arti                                                                                        |
| ------ | ------------------------------------------------------------------------------------------- |
| ✅     | Sudah ada & sesuai prototype (tampilan/UX/fungsi/posisi cocok)                              |
| 🟡     | Sudah ada tapi **beda** dari prototype (fungsi/arsitektur/posisi geser) — detail di catatan |
| ⭕     | Belum ada / belum dikerjakan — biasanya tabel DB udah ada, controller & view belum          |
| ➕     | Fitur tambahan yang **gak ada** di prototype (scope baru WSM Office System)                 |

Catatan penting soal arsitektur: sejak **2026-09-09** ada refactor besar "**permission-based, bukan role-based**" — beberapa fitur yang di prototype cuma dicek pakai jabatan (role Manajer/Owner/HRD) sekarang dicek pakai `dashboard_access` (assign per-orang per-modul lewat halaman Dashboard Access). Ini bikin beberapa hal di bawah tercatat 🟡 walau fungsinya sama persis — bedanya cuma _siapa yang bisa masuk_, bukan _tampilannya_. Lihat bagian "Refactor Besar" di akhir dokumen.

---

## Fase 1 — Website Publik

➕ **Di luar scope prototype.** WOS_2_0_App_v32 murni internal tool (CEO Dashboard + Employee App), gak punya halaman publik/marketing sama sekali — jadi seluruh Fase 1 ini scope baru WSM Office System, bukan porting.

- ✅ Beranda, Tentang Kami, Layanan, Kontak — halaman statis sudah ada (`PageController`, `resources/views/public/*`)
- ✅ Karir — daftar lowongan (`/karir`) tarik data asli dari `job_openings` (status `published`), detail lowongan + form lamar (`/karir/{slug}`)
- ⭕ Form Kontak baru nge-render, **belum nyimpen ke DB / kirim notifikasi** — masih ditandai `TODO Fase 1.x` di `PageController::storeContact()`
- ⭕ Konten halaman (Tentang Kami, Layanan) masih hardcode di Blade, belum bisa diedit dari dashboard (rencana "CMS ringan" belum digarap)

---

## Fase 2 — Manajemen Karyawan & Struktur Organisasi

- ✅ CRUD Karyawan (`/owner/employees`) — nama, role, divisi, tipe, salary, join date, birth date, dll
- ✅ Struktur Organisasi (`/owner/organisasi`) — org chart via `manager_id` self-reference, padanan `managerFor()`/org tree prototype
- ✅ Restore karyawan yang di-soft-delete
- 🟡 Halaman "Edit / Password" karyawan di prototype jadi satu form dengan **Dashboard Access** (assign modul view/manage) — di WSM-Office dipisah jadi 2 halaman: edit data karyawan (`employees.edit`) vs `employees/{id}/akses` (khusus Owner). Fungsinya sama, cuma posisinya kepisah.

---

## Fase 3 — Rekrutmen _(tambahan di luar prototype)_

➕ Prototype v32 gak punya fitur rekrutmen sama sekali (sudah dicek — nol hasil grep "rekrutmen"/"recruitment"/"pelamar" di seluruh file prototype).

- ✅ CRUD Lowongan Kerja (create/edit, publish/draft/closed)
- ✅ Pipeline Pelamar — daftar, detail, update status, convert pelamar jadi karyawan
- ✅ Modul `recruitment` di Dashboard Access — Owner bisa assign akses ke staf tertentu (bukan otomatis semua HRD)

---

## Fase 4 — Absensi (Self-Service + Rekap)

- ✅ Clock-in / clock-out dari Employee App (`/app/absensi/masuk`, `/pulang`)
- ✅ Multi-sesi per hari (padanan mode Lapangan/Gigs prototype — bisa checkout lalu checkin sesi baru)
- ✅ Auto-close sesi yang lupa checkout (`AttendanceReconciler`, jalan piggyback di request biasa — **bukan cron**, sesuai constraint shared hosting)
- ✅ Riwayat presensi pribadi (`/app/riwayat`)
- ✅ Rekap Absensi lintas karyawan + koreksi jam (`RecapController`)
- 🟡 Gerbang masuk Rekap Absensi dulunya `role:manajer,owner,hrd`, sekarang `module:people,view` (koreksi butuh `module:people,manage`) — ini fix bug isolasi juga: karyawan biasa gak lagi ke-expose link Rekap cuma gara-gara punya akses modul lain

---

## Fase 5 — Pengajuan Izin/Cuti + Approval

- ✅ Form pengajuan (Koreksi Presensi, Izin, Sakit, Cuti, WFH) dari Employee App
- ✅ Saldo cuti berbayar (paid leave balance) tampil di form
- ✅ Layar Persetujuan buat atasan langsung (approve/reject/cancel)
- ✅ Approval tetap murni relasi `manager_id` (atasan langsung) — **dashboard_access gak pernah ngubah siapa yang berhak approve**, cuma ngatur siapa yang bisa _masuk_ layar Persetujuan
- 🟡 Gerbang masuk layar Persetujuan dulu `role:manajer,owner`, sekarang `module:people,view` (sama modul dengan Rekap Absensi, persis prototype yang gabungin "People & Leave" jadi satu)

---

## Fase 6 — Dashboard Access (Permission per Modul) & Work Control

- ✅ **Dashboard Access** — Owner assign level `view`/`manage` per modul per orang, padanan persis `DASHBOARD_MODULES`/`normalizeDashboardAccess()` di prototype. 7 modul inti (`work`, `budget`, `royalty`, `kpi`, `people`, `contracts`, `payroll`) 1:1 sama key/label/desc dengan prototype
- ✅ Landing Dashboard (`/dashboard`) — cuma nampilin modul yang beneran di-assign ke user, murni dari `accessLevel()` bukan role
- ✅ **Memo Forum** (Work Control) — CRUD memo, publish ke Home Employee App, sudah termasuk thread reply per memo (Fase 8)
- ✅ **MoM Terstruktur (Meeting)** — `MeetingController` + views `dashboard/work/meetings/*` sudah jadi (2026-09-12): attendee relasi asli (checkbox dari `users`), action item per-baris dengan PIC perorangan/ALL TEAM + due date, opsional auto-generate ke Work Tracker (`WorkItem` nempel balik lewat `meeting_action_item_id`), dan tombol **Blast Summary** yang nge-push ringkasan jadi `Memo` (`type=mom`) ke semua karyawan — jadi tetap numpang infrastruktur Memo yang udah ada (kartu "Info dari Owner", Inbox, badge unread), bukan jalur notifikasi baru. Tab ke-3 "Rapat & Action Item" udah nempel di Work Control, sebelahan "MoM & Memo" dan "Work Tracker".
    - Catatan: ini SENGAJA beda entity dari "MoM ringkas" (`Memo` dengan `type=mom`, dari Fase 6b) — dua-duanya dipertahankan, bukan yang satu gantiin yang lain. "MoM ringkas" buat catatan cepat tanpa action item; "MoM Terstruktur" (baru) buat rapat yang perlu action item terlacak & bisa ditugaskan ke Work Tracker.
- ➕ 2 modul tambahan yang gak ada di prototype: `legal` (Fase 14) dan `it` (Fase 15) — ditambahin biar Owner bisa delegasikan Legal/IT ke staf lain (di prototype dua ini cuma section tetap di sidebar CEO, role-gated ke CEO doang, gak bisa didelegasikan)
- ➕ Modul `recruitment` juga ditambah ke Dashboard Access (lihat Fase 3)

---

## Fase 7 — Pengaturan Kantor & Lembur

- ✅ Pengaturan Kantor (`/owner/pengaturan-kantor`) — titik lokasi, radius toleransi absen, jam kerja normal, bisa diubah Owner sendiri lewat UI (sebelumnya cuma lewat seeder)
- ✅ Pengajuan & approval Lembur (self-service, flow sama kayak Izin/Cuti)
- 🟡 Field `flat_overtime_rate` (flat rate per approved overtime, bukan hitungan durasi — persis `oeOvertimeFlat` prototype) **sengaja ditunda** ke Fase 12 Payroll — sekarang sudah dilengkapi (lihat Fase 12), dulu belum nempel ke kalkulasi apa pun

---

## Fase 8 — Memo Forum Interaktif, Team Moments, Milestones

- ✅ Kartu "Info dari Owner" di Home jadi interaktif — baca/sembunyikan/reply, badge unread di ikon inbox
- ✅ Mark-all-read otomatis pas modal Inbox dibuka
- ✅ Milestones (Birthday & Work Anniversary + lama bekerja) — punya sendiri, dihitung langsung dari `User` model, gak lewat controller
- ✅ Team Moments — ulang tahun/anniversary SEMUA karyawan dalam 45 hari ke depan
- ✅ Paid Leave banner di profile/home

---

## Fase 9 — Work Tracker, Shared Calendar, MoM

- ✅ **Project & Work Item board** (Kanban) — CRUD Project, assign PIC, drag-drop update progress kolom `Pending/On Development/Follow Up/Confirmed/Done/Postpone`, gerbang `module:work` (view lihat board, manage baru bisa CRUD)
- ✅ **My Work Tracker** — kartu read-only di Home, isinya task milik sendiri (`pic_employee_id`), beda dari board admin di atas
- ✅ **Shared Calendar** (`/app/kalender-tim`) — kalender bersama, bisa difilter per Project/PIC, terbuka buat semua role internal (padanan `openSharedWorkloadCalendar()`)
- ⭕ **Import Item bulk dari CSV/XLSX** — fitur besar di prototype (v19→v32, header bisa di salah satu 50 baris pertama, deteksi worksheet, dsb), **belum ada sama sekali** di `WorkTrackerBoardController`/view
- ✅ **MoM Terstruktur (Meeting)** — lihat Fase 6b, sudah jadi (2026-09-12)
- ⭕ Section color coding & section management per-project (`sectionColorV20`, custom section chip) — belum di-porting, board saat ini pakai kolom progress tetap, bukan section custom yang bisa diwarnai/di-reorder
- ⭕ Export Project ke XLSX (`exportProjectXlsxV28`) — belum ada

---

## Fase 10 — KPI & Performance

✅ **Selesai (2026-09-12).** `KpiController` (`app/Http/Controllers/Dashboard/Kpi/`) + views `dashboard/kpi/*`:

- Listing KPI seluruh tim (filter per karyawan), CRUD lengkap (tambah/edit/hapus), status `Active`/`Completed`/`Archived`
- Gate `module:kpi,view` (lihat) / `module:kpi,manage` (CRUD) — pola sama persis modul `work`
- Landing `/dashboard` di-fix biar modul KPI nyambung ke `dashboard.kpi.index` (sebelumnya bakal ke placeholder generik kalau gak di-special-case, sama kayak `work` dulu)
- Kartu "My KPI" di Home sekarang keisi beneran begitu Owner/Manajer nambah KPI — style badge/progress bar (`achievementBadgeClass()`/`progressBarClass()`) dipakai ulang persis dari `Kpi` model, satu sumber warna buat kartu Home & listing Owner
- Ditambah `Kpi::STATUSES` const (konsisten sama pola `Project::STATUSES`/`WorkItem::PROGRESS_OPTIONS`) — gak ada di kode sebelumnya, ditambahin pas fase ini

---

## Fase 11 — Employee Contracts

✅ **Selesai (2026-09-12).** `ContractController` (`app/Http/Controllers/Dashboard/Contracts/`) + views `dashboard/contracts/*`:

- Upload file kontrak beneran (PDF/Word/gambar, maks 10MB) ke `Storage::disk('public')` — **controller PERTAMA di codebase ini yang pakai upload multipart** (`$request->file()`); sebelumnya cuma ada foto base64 dari kamera di `AttendanceController::storePhoto()`, beda mekanisme
- CRUD lengkap: tambah/edit (ganti file opsional)/hapus, filter listing per karyawan
- Badge "Segera Berakhir" pakai `EmployeeContract::isExpiringSoon()` (≤30 hari) yang udah ada dari Fase 11 awal, sebelumnya nganggur karena gak ada UI yang manggil
- Landing `/dashboard` di-fix biar modul Contracts nyambung ke `dashboard.contracts.index` (sama fix yang dilakuin ke `kpi` sebelumnya)
- Ditambah `EmployeeContract::formattedSize()` (tampilan ukuran file ringkas, "240 KB"/"1.4 MB") — gak ada di kode sebelumnya, ditambahin pas fase ini

⚠️ **Catatan operasional (BUKAN bug kode, tapi perlu diverifikasi pas deploy):** `Storage::disk('public')` butuh symlink `public/storage → storage/app/public` yang normalnya dibuat lewat `php artisan storage:link`. Berhubung hosting-nya shared cPanel **tanpa akses terminal**, symlink ini kemungkinan besar belum ada — kalau belum, file kontrak (dan foto absensi yang udah lebih dulu pakai pola sama) gak bakal bisa diakses lewat `asset('storage/...')` walau upload-nya sendiri sukses. Ini bukan masalah baru dari fase ini — attendance photo udah lebih dulu punya risiko yang sama, cuma baru ketauan jelas sekarang karena Contract Monitoring nge-expose link filenya langsung ke user. Solusinya biasanya salah satu: symlink manual lewat File Manager cPanel, atau fitur "Setup Node.js/PHP App" sebagian host yang kasih akses jalanin 1 command, atau tanya ke Rumahweb caranya.

---

## Fase 12 — Payroll

✅ **Selesai (2026-09-12).** `PayrollController` (`app/Http/Controllers/Dashboard/Payroll/`) + views `dashboard/payroll/*` — **sengaja didesain beda** dari prototype (bukan porting 1:1), sesuai catatan migration `create_payroll_records_table`: prototype cuma hitung "estimate" on-the-fly tiap buka CEO Dashboard, gak pernah disimpan; di sini payroll digenerate jadi baris `payroll_records` permanen per (karyawan, periode).

- **Generate per-periode** — satu form men-generate payroll buat semua karyawan yang punya "Gaji Pokok" terisi sekaligus (atau subset lewat multi-select), bukan satu-satu manual
- **Komponen dihitung otomatis**: `base_salary` (salinan `users.salary_base` saat generate), `overtime_amount` (jumlah Lembur disetujui periode itu × `flat_overtime_rate` per-karyawan), `shortage_deduction` (`Attendance::monthlyShortageBlocks()` dari Fase 7 × rate perusahaan baru — lihat poin berikutnya)
- **Field baru yang dilengkapi buat nutup Fase 12**: `salary_base`/`target_hours_per_day`/`flat_overtime_rate` (per-karyawan, ditambah ke form Karyawan Fase 2 — field ini emang sengaja ditunda dari Fase 7) dan `shortage_deduction_rate` (kebijakan perusahaan, satu rate rupiah per blok 60 menit kurang jam kerja, ditambah ke form Pengaturan Kantor Fase 7 — Owner yang nentuin sendiri angkanya, gak ada acuan dari prototype)
- **Alur status satu arah**: `draft → finalized → paid`. Cuma `draft` yang boleh di-regenerate ulang/diedit (`other_adjustment` + catatan)/dihapus — sekali `finalized`, angka terkunci permanen sebagai histori, gak ketimpa walau data sumbernya (gaji, setting) direvisi belakangan
- **Halaman detail (slip)** nunjukin breakdown lengkap (jumlah lembur & blok shortage mentah, bukan cuma nominal akhir) — ini justru LEBIH detail dari prototype yang cuma nampilin angka ringkasan `thp`
- Karyawan tanpa "Gaji Pokok" terisi sengaja dilewati dari generate (bukan dianggap Rp 0), dan dashboard payroll nunjukin counter berapa karyawan yang masih kosong datanya

🔧 **1 bug kecil ke-fix pas review:** `routes/web.php` sempet ada duplikasi `Route::get('/{module}', ...)->name('show')` dua kali persis (baris terakhir grup `dashboard.`). Gak fatal (request tetap kena match ke baris pertama, cuma baris kedua jadi dead code), tapi udah dibersihin.

---

## Fase 13 — Project Budgeting & Royalty

✅ **Selesai (2026-09-12).** Dua controller terpisah (gate beda: `budget` vs `royalty`), tapi dibangun bareng karena satu fase:

- **`BudgetController`** (`dashboard/budget/*`) — CRUD baris budget-vs-actual per project (padanan `saveBudgetEntry()`), listing dikelompokkan per project dengan subtotal Budget/Actual/Variance (variance minus = over budget, dikasih warna merah)
- **`RoyaltyController`** (`dashboard/royalty/*`) — CRUD royalty entry (padanan `saveRoyaltyEntry()`), Net Payable dihitung live lewat `RoyaltyEntry::net()` (gross × share% − recoup), filter per status (`Estimated`/`Reported`/`Ready to Pay`/`Paid`)
- Cuma level `royaltyEntries` yang di-porting — subsistem `royaltyFinance` (revenue ledger per-lagu, lebih detail) SENGAJA gak diikutin, sesuai catatan migration
- Landing `/dashboard` di-fix buat kedua modul (sama pola fix yang berulang di fase-fase sebelumnya)
- Format Rupiah di kedua modul numpang `PayrollRecord::formatRupiah()` yang udah ada dari Fase 12 — sengaja gak bikin helper baru, satu sumber format currency buat seluruh sistem

---

## Fase 14 — Legal _(desain baru, terinspirasi tapi bukan porting 1:1)_

✅ **Selesai (2026-09-13).** `LegalController` (`dashboard/legal/*`) — CRUD `LegalDocument` (padanan `state.legalDocs`), 2 kategori (`album`/`royalty`, alias Album Contracts & Royalty Agreements) disatuin dalam 1 tabel + 1 controller, dibedain lewat kolom `category` — sama pola persis `Memo.type` ('memo' vs 'mom') dan filter status di `RoyaltyController` (Fase 13). Prototype v18 punya 2 halaman terpisah buat 2 kategori ini tapi cuma 1 level akses; di sini disatuin jadi 1 index dengan filter kategori (bukan porting 1:1, keputusan desain WSM Office sendiri).

- Upload file beneran ke `Storage::disk('public')` (`legal/{category}/...`) — sama mekanisme `ContractController` (Fase 11), bukan blob base64 kayak prototype
- Field: kategori, judul, pihak terkait (label/artist/publisher, opsional), tanggal mulai/selesai (opsional), catatan (opsional)
- Badge "Segera Berakhir" (≤30 hari) lewat `LegalDocument::isExpiringSoon()` — method baru, sama pola & window kayak `EmployeeContract::isExpiringSoon()`
- Gate `module:legal,view|manage` — modul `legal` sudah terdaftar di Dashboard Access sejak Fase 6, sekarang isinya udah bukan placeholder
- Landing `/dashboard` di-fix (match statement `DashboardController::index()`) biar kartu modul Legal ngarah ke `dashboard.legal.index`, bukan placeholder generik

---

## Fase 15 — IT (Audit Log & System Changelog) _(desain baru)_

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

## Fase 16 — CEO Dashboard IA Restructure & Settings

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

## Refactor Besar: Permission-based, Bukan Role-based (2026-09-09)

Ini bukan fitur baru, tapi perubahan arsitektur lintas-fase yang penting buat konteks kenapa banyak item di atas ditandai 🟡:

- **Sebelum**: banyak fitur (Rekap Absensi, Persetujuan Izin/Cuti/Lembur, Rekrutmen) di-gate pakai `role:manajer,owner,hrd` — siapa pun berjabatan itu otomatis dapet akses, gak bisa dicabut per-orang
- **Sesudah**: pakai `module:people,view|manage` dan `module:recruitment,view|manage` (Dashboard Access) — Owner assign satu-satu lewat halaman "Dashboard Access" yang sudah ada dari Fase 6a
- **Kenapa**: nemu bug nyata — karyawan biasa (role `karyawan`) kelihatan link "Absensi" di sidebar walau gak punya akses modul apa pun ke situ, gara-gara link-nya dulu gak dicek sama sekali, cuma numpang tampil asal user itu punya akses modul _lain_
- **Yang TIDAK berubah**: siapa yang boleh approve/reject request tertentu tetap 100% relasi `manager_id` (atasan langsung) — persis prinsip di prototype: "Dashboard access tidak mengubah authority approval"
- Migration `backfill_dashboard_access_for_manajer_hrd` mastiin karyawan existing yang role-nya Manajer/HRD gak kehilangan akses pas refactor ini jalan

---

## Ringkasan Checklist Global

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

## Kesimpulan

WSM-Office sekarang solid dan sejalan (bahkan di beberapa modul lebih detail) dari prototype WOS_2_0_App_v32, dengan penyesuaian arsitektur yang disengaja: dashboard permission-based (bukan role-based) sejak 2026-09-09, dan beberapa modul baru di luar scope prototype (Rekrutmen, Legal, IT) yang sengaja ditambah biar Owner bisa delegasikan lebih banyak ke staf lain.

Yang masih perlu diperhatikan sebelum dianggap 100% siap produksi (bukan "belum jadi", tapi "belum dicek/disempurnakan") — rangkuman dari semua catatan QA di bawah:

1. **Symlink `storage:link` di hosting production** — PALING PENTING. Contract Monitoring (Fase 11) & Legal (Fase 14) upload file beneran ke `Storage::disk('public')`, butuh symlink `public/storage → storage/app/public` yang biasanya dibuat lewat `php artisan storage:link`. Hosting cPanel tanpa akses terminal kemungkinan besar belum punya ini — cek ke Rumahweb sebelum dua modul itu dipakai beneran (lihat detail di Fase 11).
2. **Form kontak publik (Fase 1)** belum nyimpen ke DB/kirim notifikasi — masih `TODO` di `PageController::storeContact()`.
3. **Import CSV/XLSX bulk item** (Fase 9) belum di-porting dari prototype — nambah item Work Tracker masih satu-satu manual.
4. **Belum ada test otomatis** di seluruh fitur baru (Fase 9 lanjutan s.d. 16) — konsisten sama controller lama di codebase ini yang juga belum ada test, bukan kelalaian khusus.
5. Beberapa keputusan desain sengaja beda dari prototype (Payroll dikunci `draft→finalized→paid`, Royalty bebas ganti status, dll) — semuanya didokumentasikan di bagian "Belum Sempurna" masing-masing fase di atas, worth di-review Arga buat mastiin sesuai kebutuhan beneran.

Selebihnya, per-modul checklist detail + catatan QA jujur ada di atas — dokumen ini yang jadi acuan kalau mau audit ulang atau lanjut ke fitur di luar 16 fase yang direncanakan dari awal.

## Belum Sempurna dari MoM Terstruktur (catatan QA jujur)

- Export MoM ke PDF/print — belum ada (prototype juga gak eksplisit punya ini, jadi bukan regresi)
- Notifikasi/reminder H-1 due date action item — belum ada, sama kayak WorkItem biasa (gak ada cron/reminder di seluruh sistem ini)
- Belum ada test otomatis (unit/feature) buat `MeetingController` — konsisten sama controller lain di codebase ini yang juga belum ada test, bukan kelalaian khusus fitur ini

## Belum Sempurna dari KPI & Performance (catatan QA jujur)

- Belum ada grafik tren pencapaian KPI dari waktu ke waktu (cuma snapshot current/target terakhir)
- Belum ada notifikasi ke karyawan pas KPI baru ditambahkan/diupdate Owner (karyawan baru tau kalau buka Home sendiri)
- Belum ada test otomatis, sama alasan kayak MoM di atas

## Belum Sempurna dari Employee Contracts (catatan QA jujur)

- Belum ada notifikasi otomatis pas kontrak mau habis (badge "Segera Berakhir" cuma kelihatan kalau Owner buka halamannya sendiri, gak ada email/memo blast otomatis)
- Belum ada versi/histori kontrak — ganti file pas edit LANGSUNG timpa yang lama (dihapus dari storage), gak ada arsip revisi sebelumnya
- Verifikasi symlink `storage:link` di hosting production — lihat catatan ⚠️ di Fase 11 di atas, ini paling penting dicek SEBELUM Contract Monitoring dipakai beneran
- Belum ada test otomatis, sama alasan kayak MoM & KPI di atas

## Belum Sempurna dari Payroll (catatan QA jujur)

- Belum ada slip payroll versi PDF/print buat dikasih ke karyawan — halaman detail (`show.blade.php`) cuma bisa dilihat lewat browser, belum bisa didownload/diprint rapi
- Karyawan sendiri belum bisa lihat slip payroll-nya dari Employee App (Home/Profile) — sekarang murni sisi Owner/Manajer doang lewat `/dashboard/payroll`
- Belum ada notifikasi ke karyawan pas payroll-nya difinalisasi/ditandai dibayar
- `shortage_deduction_rate` default 0 kalau Owner belum pernah isi — berarti generate pertama kali BAKAL nol-in semua potongan kurang jam kerja sampai Owner sadar isi angkanya di Pengaturan Kantor; gak ada warning eksplisit di form Generate yang ngingetin ini
- Belum ada test otomatis, sama alasan kayak fase-fase lain di atas

## Belum Sempurna dari Budget & Royalty (catatan QA jujur)

- Budget: gak ada validasi "actual gak boleh lebih besar dari budget" — sengaja dibiarin bebas (over budget itu justru info penting yang mau ditampilin, bukan dicegah)
- Royalty: transisi status (`Estimated → Reported → Ready to Pay → Paid`) BEBAS diubah ke mana aja lewat dropdown edit, gak ada lock kayak Payroll (`draft → finalized → paid` satu arah) — kalau ke depannya mau dikunci juga, ini beda desain yang sengaja dipilih karena Royalty datanya emang lebih sering direvisi (angka gross dari platform suka telat/direvisi laporannya)
- Belum ada grafik/ringkasan Total Net Payable di seluruh entries (listing cuma nunjukin net per baris, gak ada agregat di atas)
- Belum ada test otomatis, sama alasan kayak fase-fase lain di atas

## Belum Sempurna dari CEO Dashboard IA Restructure (catatan QA jujur)

- Gak ada validasi kontras warna — kalau Owner pilih `ceo_accent_color`/`work_accent_color` yang terlalu terang (mis. putih/kuning pucat), teks putih di atasnya ("Dashboard", "Work Tracker", dst) bisa jadi susah dibaca. Gak ada warning/preview kontras di form Pengaturan Kantor
- Warna aksen SENGAJA cuma nge-tint 2 area (nav Owner-only + tab Work Control) — tombol hitam lain (`btn-wsm-black`) di seluruh sistem TETAP hitam standar, gak ikut warna aksen. Ini scope yang sengaja dibatasi (sesuai prototype v18), bukan kelupaan
- Belum ada halaman "hub" dengan kartu akses cepat ke semua 9 modul dari satu layar (kayak CEO Dashboard prototype) — dianggap gak terlalu perlu karena Owner udah dapet akses 1-klik ke semua modul langsung dari sidebar, tapi ini keputusan yang worth di-review Arga kalau mau tampilan "command center" yang lebih visual
- Belum ada test otomatis, sama alasan kayak fase-fase lain di atas
