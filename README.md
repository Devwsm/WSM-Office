# WSM-Office — Progress vs Prototype (WOS 2.0 v32)

Dokumen ini bandingin **WSM-Office** (Laravel, web resmi yang bakal di-deploy public) sama **WOS_2_0_App_v32** (`index.html`, standalone HTML prototype — CEO/COO/Owner Control Center + Employee App). Tujuannya: satu tempat buat lihat apa yang udah sesuai, apa yang udah ada tapi beda, dan apa yang belum dikerjain — per fase, biar gampang nentuin urutan kerja berikutnya.

Update terakhir: cek kode per 2026-09-12 (controller, migration, route, view).

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
- ⭕ **MoM (Minutes of Meeting)** — bagian dari "Work Control" yang sama di prototype (`saveMom`, deskripsi modul "Project, tracker, timeline, **MoM**, memo"), di WSM-Office tabelnya (`meetings`, `meeting_attendees`, `meeting_action_items`) sudah ada tapi **belum ada controller & view sama sekali**. Ini bagian Work Control yang paling ketinggalan.
- ➕ 2 modul tambahan yang gak ada di prototype: `legal` (Fase 14) dan `it` (Fase 15) — ditambahin biar Owner bisa delegasikan Legal/IT ke staf lain (di prototype dua ini cuma section tetap di sidebar CEO, role-gated ke CEO doang, gak bisa didelegasikan)
- ➕ Modul `recruitment` juga ditambah ke Dashboard Access (lihat Fase 3)

---

## Fase 7 — Pengaturan Kantor & Lembur

- ✅ Pengaturan Kantor (`/owner/pengaturan-kantor`) — titik lokasi, radius toleransi absen, jam kerja normal, bisa diubah Owner sendiri lewat UI (sebelumnya cuma lewat seeder)
- ✅ Pengajuan & approval Lembur (self-service, flow sama kayak Izin/Cuti)
- 🟡 Field `overtime_flat_rate` (flat rate per approved overtime, bukan hitungan durasi — persis `oeOvertimeFlat` prototype) **sengaja ditunda** ke Fase 12 Payroll, jadi belum nempel ke kalkulasi apa pun sekarang

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
- ⭕ **MoM (Minutes of Meeting)** — lihat Fase 6, tabelnya ada tapi belum ada UI
- ⭕ Section color coding & section management per-project (`sectionColorV20`, custom section chip) — belum di-porting, board saat ini pakai kolom progress tetap, bukan section custom yang bisa diwarnai/di-reorder
- ⭕ Export Project ke XLSX (`exportProjectXlsxV28`) — belum ada

---

## Fase 10 — KPI & Performance

⭕ **Belum dikerjakan.** Tabel `kpis` sudah ada (padanan `state.kpis`/`saveKpi`), model `Kpi` sudah ada, kartu "My KPI" sudah muncul di Home Employee App — tapi:

- Belum ada UI Owner buat input/kelola KPI per karyawan (satu-satunya cara isi data sekarang lewat `tinker`/seeder manual)
- Belum ada controller/route untuk modul `kpi` di Dashboard (masih lewat placeholder generik `ModuleDashboardController::show`)
- Kartu "My KPI" otomatis kelihatan kosong sampai fase ini selesai

---

## Fase 11 — Employee Contracts

⭕ **Belum dikerjakan.** Tabel `employee_contracts` sudah ada (padanan `state.contracts`/`saveContract`), tapi belum ada controller/view — modul `contracts` masih placeholder generik. Prototype punya: status kontrak per karyawan, upload file kontrak, badge status di daftar karyawan (`contractStatusMarkup`) — semua ini belum ada representasinya di WSM-Office.

---

## Fase 12 — Payroll

⭕ **Belum dikerjakan.** Tabel `payroll_records` + field payroll di `users` (termasuk `overtime_flat_rate` dari Fase 7) sudah disiapkan, tapi belum ada controller/view. Catatan dari migration: breakdown Payroll di WSM-Office **sengaja didesain beda** dari prototype (prototype cuma nampilin angka ringkasan di tabel, breakdown detailnya belum ada acuan) — jadi pas fase ini digarap, kemungkinan besar hasilnya bakal 🟡 (beda), bukan porting 1:1.

---

## Fase 13 — Project Budgeting & Royalty

⭕ **Belum dikerjakan.** Tabel `project_budgets` (padanan `saveBudgetEntry`) dan `royalty_entries` (padanan `saveRoyaltyEntry`) sudah ada, belum ada controller/view. Modul `budget` & `royalty` di Dashboard masih placeholder generik.

---

## Fase 14 — Legal _(desain baru, terinspirasi tapi bukan porting 1:1)_

⭕ **Belum dikerjakan** dari sisi controller/view. Tabel `legal_documents` sudah ada (padanan `state.legalDocs`), sengaja dipisah dari `employee_contracts` (Fase 11) karena beda konteks (Album Contracts & Royalty Agreements vs kontrak kerja karyawan). Modul `legal` sudah terdaftar di Dashboard Access (➕ tambahan, lihat Fase 6) tapi isinya masih placeholder.

---

## Fase 15 — IT (Audit Log & System Changelog) _(desain baru)_

⭕ **Belum dikerjakan.** Tabel `audit_logs` (padanan `auditV18()`) dan `system_changelogs` (padanan `state.systemChangelogCustom`, mirip panel "Changelog" yang ada di prototype) sudah disiapkan. Modul `it` sudah terdaftar di Dashboard Access tapi belum ada controller/view.

---

## Fase 16 — CEO Dashboard IA Restructure & Settings

🟡 **Baru sebagian kecil.** Kolom `ceo_accent_color`/`work_accent_color` di `office_settings` sudah ditambah (padanan pengaturan warna di prototype v18), tapi ini baru "satu-satunya bagian yang butuh kolom baru" — sisa restrukturisasi IA Owner Dashboard (sidebar CEO prototype: Dashboard, Attendance, Requests, KPI & Performance, Work Tracker, Memo Blast, Karyawan, Contracts, Payroll, Settings — satu shell navigasi terpadu) **belum digarap**. Saat ini Owner area di WSM-Office masih berupa halaman-halaman terpisah (`/owner/dashboard`, `/owner/employees`, `/owner/organisasi`, dst) tanpa sidebar navigasi CEO Control Center yang menyatukan semua modul seperti di prototype.

- 🟡 Owner Dashboard saat ini (`/owner/dashboard`) cuma kartu ringkasan (total karyawan, hadir hari ini, pengajuan pending — digabung izin/cuti pending + absen lupa checkout jadi satu angka). Prototype CEO Dashboard jauh lebih kaya: stat cards + akses cepat ke semua 10 modul dari satu sidebar.

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

| Fase | Modul                                     | Status                                  |
| ---- | ----------------------------------------- | --------------------------------------- |
| 1    | Website Publik                            | ✅ sebagian (form kontak belum nyimpen) |
| 2    | Karyawan & Struktur Organisasi            | ✅                                      |
| 3    | Rekrutmen                                 | ➕ ✅                                   |
| 4    | Absensi (self-service + rekap)            | ✅                                      |
| 5    | Izin/Cuti + Approval                      | ✅                                      |
| 6a   | Dashboard Access (permission)             | ✅                                      |
| 6b   | Work Control → Memo                       | ✅                                      |
| 6b   | Work Control → MoM                        | ⭕                                      |
| 7    | Pengaturan Kantor & Lembur                | ✅                                      |
| 8    | Memo interaktif, Team Moments, Milestones | ✅                                      |
| 9    | Work Tracker board & Shared Calendar      | ✅                                      |
| 9    | Import CSV/XLSX bulk item                 | ⭕                                      |
| 10   | KPI & Performance                         | ⭕ (tabel only)                         |
| 11   | Employee Contracts                        | ⭕ (tabel only)                         |
| 12   | Payroll                                   | ⭕ (tabel only)                         |
| 13   | Project Budgeting & Royalty               | ⭕ (tabel only)                         |
| 14   | Legal                                     | ⭕ (tabel only)                         |
| 15   | IT (Audit Log & Changelog)                | ⭕ (tabel only)                         |
| 16   | CEO Dashboard IA Restructure              | 🟡 sebagian kecil                       |
| —    | Refactor permission-based                 | ✅                                      |

**Pola yang kelihatan:** Fase 1–9 (fondasi: publik, karyawan, absensi, izin, dashboard access, work control, kalender) sudah solid dan sesuai prototype (dengan penyesuaian arsitektur permission-based). Fase 10–15 (KPI, Contracts, Payroll, Budget, Royalty, Legal, IT) **data layer-nya udah lengkap duluan** (17 migration, 12 model) tapi **controller & view-nya belum ada satu pun** — jadi kerjaan berikutnya murni "bangun UI di atas tabel yang udah siap", bukan desain ulang dari nol.

## Rekomendasi Urutan Kerja Berikutnya

1. **MoM (Meeting)** — nutup Fase 6b/9, paling deket karena satu ekosistem sama Memo & Work Tracker yang udah jadi
2. **KPI (Fase 10)** — kartu "My KPI" di Home udah nunggu data, dampak ke UX Employee App paling langsung kerasa
3. **Contracts (Fase 11)** lalu **Payroll (Fase 12)** — payroll butuh `overtime_flat_rate` yang udah disiapkan dari Fase 7
4. **Budget & Royalty (Fase 13)**, **Legal (Fase 14)**, **IT (Fase 15)** — bisa nyusul, dampak ke UX harian lebih kecil
5. **CEO Dashboard IA Restructure (Fase 16)** — baiknya dikerjain setelah modul-modul di atas ada isinya, biar sidebar/shell yang dibangun langsung nyambung ke halaman yang beneran ada, bukan bikin shell duluan lalu nunggu isi
