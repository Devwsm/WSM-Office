# WSM Office System

Sistem manajemen internal untuk **Whisnu Santika Music (WSM)** — dibangun dengan Laravel, menggantikan prototype HTML/JS statis (`WOS_2_0_App_v32`) menjadi aplikasi web sungguhan yang siap dipakai sehari-hari dan diakses lewat internet oleh seluruh tim.

---

## 1. Tentang Project Ini (Ringkas)

Bayangkan WSM Office System sebagai **satu pintu masuk** untuk segala urusan administrasi kantor musik ini — menggantikan kombinasi Excel, grup chat, dan kertas yang biasanya dipakai kantor kecil.

Dengan sistem ini, tim WSM bisa:

- **Absen** masuk/pulang lewat HP (dicek lokasinya, biar gak bisa titip absen dari rumah kalau memang harus di kantor).
- **Mengajukan izin, cuti, lembur**, atau **koreksi absen** (kalau lupa tap), lalu atasan langsung tinggal klik setuju/tolak.
- **Mengelola pekerjaan tim** — siapa mengerjakan apa, untuk project apa (misalnya rilis album atau produksi merchandise), lewat papan tugas ala Trello.
- **Mencatat hasil rapat (MoM)** lengkap dengan siapa yang hadir dan apa tindak lanjutnya — bisa langsung diubah jadi tugas.
- **Memantau KPI**, **kontrak kerja karyawan**, **payroll bulanan**, **budget project**, dan **royalti** dari hasil karya — semua di satu tempat, bukan di file Excel yang terpisah-pisah.
- **Mengurus kontrak & perjanjian legal** (kontrak rilis album, perjanjian royalti) beserta tanggal jatuh temponya.
- **Merekrut karyawan baru** — mulai dari pasang lowongan di halaman publik, sampai memproses lamaran yang masuk.
- Untuk pengunjung dari luar (calon klien, calon karyawan, fans), ada juga **halaman depan publik** — profil perusahaan, layanan, halaman karir, dan form kontak.

Siapa boleh melihat/mengubah apa **diatur per-orang**, bukan cuma berdasarkan jabatan — jadi Owner (pemilik/CEO) bisa memberi atau mencabut akses satu modul tertentu ke satu karyawan tertentu, tanpa harus mengubah jabatannya. Ini yang membedakan sistem ini dari sekadar "kasih role Manajer/HRD lalu otomatis buka semua".

---

## 2. Status Project Saat Ini

**Ringkasan: seluruh 16 fase pengembangan yang direncanakan sudah selesai**, ditambah beberapa penyempurnaan susulan (rework sidebar, perbaikan izin akses, dan lain-lain). Aplikasi ini **secara fungsional sudah lengkap** dan siap untuk tahap uji coba menyeluruh — tapi **belum 100% siap untuk benar-benar dipakai publik ("production")** karena ada beberapa hal teknis yang wajib dibereskan dulu sebelum di-upload ke hosting.

### Yang sudah selesai & bisa dites

Semua modul di bawah ini sudah punya halaman, alur kerja, dan data contoh (lihat bagian 3 & 5):
Halaman publik, Absensi (termasuk mode WFH/Lapangan/Gigs), Izin/Cuti, Lembur, Koreksi Presensi, Persetujuan atasan, Work Control (papan tugas + kalender + memo + rapat terstruktur), KPI, Kontrak Karyawan, Payroll, Project Budgeting, Royalty, Legal, Audit Log & Changelog sistem, Rekrutmen, Manajemen Karyawan & Struktur Organisasi, Pengaturan Kantor, dan Pesan Kontak.

### Sedang dikerjakan: Export & Import

Fitur baru, dikerjakan bertahap (lihat bagian 7 untuk detail lengkap). **Semua kode sudah selesai**: Export Excel (Batch 1), Export PDF (Batch 2), Import Work Tracker (Batch 3), dan Import Manajemen Karyawan/KPI/Project Budgeting (Batch 4) — halaman menu "Export & Import" sudah bisa dibuka, kartu sudah nyaring sesuai akses tiap orang, dan **semua tombol Export & Import sekarang aktif**, gak ada lagi yang "Segera Hadir". Import Work Tracker sudah dites manual sama Owner dan aman; sisanya (Export Excel/PDF semua modul, Import Manajemen Karyawan/KPI/Project Budgeting) **belum ada satu pun yang dites langsung lewat browser (login beneran)** — sejauh ini semua lewat audit kode manual, cocokin ke rules/model satu-satu — lihat catatan pengujian di bagian 7.

### Sebelum go-live

Daftar tes manual lengkap ada di **bagian 8**, temuan kekurangan di **bagian 9**, dan reminder persiapan production (`.env`, HTTPS, `storage:link`, `.htaccess`, dll) sengaja ditaruh di **bagian 10 (paling akhir)**.

---

## 3. Daftar Halaman, Fitur, dan Hak Akses

Kolom **"Siapa yang bisa akses"** mengikuti aturan berikut: **Owner selalu bisa akses semua halaman** tanpa perlu diatur satu-satu. Untuk staf lain (Manajer, HRD, Karyawan biasa), akses ke modul-modul manajerial **diberikan satu per satu oleh Owner** lewat halaman "Atur Akses Dashboard" — jadi tabel di bawah menyebut _modul_ yang perlu diberi akses, bukan jabatan tertentu. Untuk persetujuan izin/cuti/lembur, siapa yang berwenang menyetujui **selalu** mengikuti struktur atasan-langsung (`manager_id`) masing-masing karyawan — akses modul cuma membuka pintu masuk ke halamannya, bukan mengubah siapa yang berhak menyetujui.

### A. Halaman Publik (tanpa perlu login)

| Halaman      | Fitur                                                    | Siapa yang Bisa Akses |
| ------------ | -------------------------------------------------------- | --------------------- |
| Beranda      | Profil singkat WSM                                       | Siapa saja            |
| Tentang Kami | Profil & cerita perusahaan                               | Siapa saja            |
| Layanan      | Daftar layanan yang ditawarkan                           | Siapa saja            |
| Karir        | Lihat daftar lowongan yang sedang dibuka & kirim lamaran | Siapa saja            |
| Kontak       | Kirim pesan ke Owner lewat form                          | Siapa saja            |

### B. Halaman untuk Semua Staf (login, berlaku untuk Owner, Manajer, HRD, Karyawan)

| Halaman                        | Fitur                                                                                                     | Siapa yang Bisa Akses                                    |
| ------------------------------ | --------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| Home / Beranda Pegawai         | Ringkasan pribadi: sisa cuti, KPI, tugas hari ini, ulang tahun & anniversary tim, memo terbaru dari Owner | Semua staf internal                                      |
| Absen Masuk/Pulang             | Tap absen dengan validasi lokasi (mode Kantor/WFH/Lapangan/Gigs)                                          | Semua staf internal                                      |
| Riwayat Absensi                | Lihat riwayat kehadiran diri sendiri                                                                      | Semua staf internal                                      |
| Pengajuan Izin/Cuti            | Ajukan & batalkan izin/cuti                                                                               | Semua staf internal                                      |
| Pengajuan Lembur               | Ajukan & batalkan lembur                                                                                  | Semua staf internal                                      |
| Pengajuan Koreksi Presensi     | Ajukan koreksi kalau lupa tap absen                                                                       | Semua staf internal                                      |
| Kalender Tim (Shared Calendar) | Lihat beban kerja & jadwal tim secara bersama                                                             | Semua staf internal                                      |
| Profil Saya                    | Ganti password                                                                                            | Semua staf internal                                      |
| Inbox / Memo dari Owner        | Baca, balas, sembunyikan pengumuman dari Owner/manajemen                                                  | Semua staf internal                                      |
| Kunci Dashboard                | Mengunci sesi dashboard sementara (mis. saat HP ditinggal)                                                | Semua staf yang punya akses ke minimal 1 modul dashboard |

### C. Khusus Owner

| Halaman              | Fitur                                               | Siapa yang Bisa Akses |
| -------------------- | --------------------------------------------------- | --------------------- |
| Dashboard Owner      | Ringkasan menyeluruh perusahaan                     | Owner                 |
| Manajemen Karyawan   | Tambah/ubah/nonaktifkan akun karyawan               | Owner                 |
| Struktur Organisasi  | Lihat bagan organisasi (atasan-bawahan)             | Owner                 |
| Atur Akses Dashboard | Memberi/mencabut akses modul per-karyawan           | Owner                 |
| Pengaturan Kantor    | Titik lokasi kantor, radius absen, jam kerja normal | Owner                 |
| Pesan Kontak         | Baca pesan yang masuk dari form Kontak publik       | Owner                 |

### D. Modul Manajerial (akses diatur per-orang lewat "Atur Akses Dashboard")

| Halaman                                      | Fitur                                                                   | Modul Akses yang Dibutuhkan                                                       |
| -------------------------------------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| Rekap Absensi & Koreksi Manual               | Lihat rekap kehadiran seluruh/sebagian tim, koreksi jam absen langsung  | `people` (lihat = _view_, koreksi = _manage_)                                     |
| Persetujuan Izin/Cuti                        | Setujui/tolak pengajuan cuti bawahan langsung                           | `people` (pintu masuk halaman; siapa berwenang setuju tetap ikut struktur atasan) |
| Persetujuan Lembur                           | Setujui/tolak pengajuan lembur bawahan langsung                         | `people`                                                                          |
| Persetujuan Koreksi Presensi                 | Setujui/tolak pengajuan koreksi absen bawahan langsung                  | `people`                                                                          |
| Rekrutmen — Lowongan                         | Buat/edit/tutup lowongan kerja                                          | `recruitment` (lihat = _view_, kelola = _manage_)                                 |
| Rekrutmen — Pelamar                          | Lihat pipeline pelamar, ubah status, convert jadi akun karyawan         | `recruitment`                                                                     |
| Work Control — Memo & MoM                    | Tulis pengumuman/catatan rapat cepat, kirim ke tim                      | `work` (lihat = _view_, kelola = _manage_)                                        |
| Work Control — Timeline Calendar             | Kalender kerja versi manajemen                                          | `work`                                                                            |
| Work Control — Work Tracker Board            | Papan tugas ala kanban per-project                                      | `work`                                                                            |
| Work Control — Meetings (MoM terstruktur)    | Rapat dengan daftar hadir & action item yang bisa jadi tugas otomatis   | `work`                                                                            |
| KPI & Performance                            | Kelola target & capaian KPI seluruh tim                                 | `kpi`                                                                             |
| Contract Monitoring                          | Kelola dokumen kontrak kerja karyawan, notifikasi mendekati jatuh tempo | `contracts`                                                                       |
| Payroll                                      | Generate & kelola slip gaji bulanan                                     | `payroll`                                                                         |
| Project Budgeting                            | Kelola anggaran vs realisasi per-project                                | `budget`                                                                          |
| Royalty Dashboard                            | Kelola pendapatan royalti, status pembayaran                            | `royalty`                                                                         |
| Legal — Album Contracts & Royalty Agreements | Kelola dokumen kontrak album & perjanjian royalti                       | `legal`                                                                           |
| IT — Audit Log                               | Lihat jejak aktivitas sistem (siapa melakukan apa)                      | `it` (hanya lihat, tidak ada mode kelola)                                         |
| IT — System Changelog                        | Catat riwayat rilis versi & perubahan sistem                            | `it`                                                                              |

---

## 4. Arsitektur & Struktur File

Struktur mengikuti pola standar Laravel. Yang membedakan aplikasi bisnis dari skeleton kosong ada di `app/`, `database/`, `resources/views/`, dan `routes/`.

```
WSM-Office/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # "Otak" tiap halaman — apa yang terjadi saat halaman dibuka/form dikirim
│   │   ├── Requests/         # Aturan validasi form, dikelompokkan sesuai Controller-nya
│   │   └── Middleware/       # Penjaga pintu sebelum request sampai ke Controller
│   ├── Models/                # Definisi tiap "jenis data" (Karyawan, Cuti, Project, dst) & aturan bisnisnya
│   ├── Providers/             # Konfigurasi bootstrap aplikasi Laravel (bawaan framework)
│   └── Support/               # Helper/utilitas kecil yang dipakai lintas fitur
├── database/
│   ├── migrations/            # Riwayat perubahan struktur tabel database, urut berdasarkan tanggal dibuat
│   └── seeders/                # Skrip pengisi data contoh/testing (lihat bagian 5)
├── resources/
│   ├── views/                  # Tampilan HTML (Blade template), dikelompokkan sesuai Controller-nya
│   ├── css/app.css             # Styling utama (Tailwind CSS)
│   └── js/                     # Alpine.js untuk interaktivitas + skrip absensi & notifikasi
├── routes/
│   ├── web.php                  # Peta lengkap: alamat URL ⇄ Controller ⇄ siapa yang boleh akses
│   └── auth.php                 # Alamat URL untuk login/logout
└── public/                     # Folder yang "dihadapkan" ke internet (titik masuk aplikasi)
```

### `app/Models/` — 25 file, satu per jenis data utama

| File                                                  | Isinya                                                                                   |
| ----------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `User.php`                                            | Semua jenis akun staf (Owner/Manajer/HRD/Karyawan) dalam 1 tabel, dibedakan kolom `role` |
| `Attendance.php`                                      | Satu baris presensi (per sesi, per hari)                                                 |
| `AttendanceCorrectionRequest.php`                     | Pengajuan koreksi jam absen dari karyawan                                                |
| `LeaveRequest.php`                                    | Pengajuan izin/cuti                                                                      |
| `OvertimeRequest.php`                                 | Pengajuan lembur                                                                         |
| `DashboardAccess.php`                                 | Baris "siapa punya akses modul apa, level apa" — jantung sistem izin akses               |
| `Memo.php` / `MemoRead.php` / `MemoThreadMessage.php` | Pengumuman/MoM cepat, status baca per-orang, dan balasan thread-nya                      |
| `Project.php` / `WorkItem.php`                        | Project dan daftar tugas di dalamnya (papan kanban)                                      |
| `Meeting.php` / `MeetingActionItem.php`               | Rapat terstruktur (MoM lengkap) dan tindak lanjutnya                                     |
| `Kpi.php`                                             | Target & capaian KPI karyawan                                                            |
| `EmployeeContract.php`                                | Dokumen kontrak kerja karyawan                                                           |
| `PayrollRecord.php`                                   | Riwayat slip gaji bulanan                                                                |
| `ProjectBudget.php`                                   | Anggaran vs realisasi per-project                                                        |
| `RoyaltyEntry.php`                                    | Pendapatan royalti per periode                                                           |
| `LegalDocument.php`                                   | Dokumen kontrak album & perjanjian royalti                                               |
| `AuditLog.php`                                        | Jejak aktivitas sistem                                                                   |
| `SystemChangelog.php`                                 | Riwayat rilis versi sistem                                                               |
| `JobOpening.php` / `JobApplication.php`               | Lowongan kerja & lamaran yang masuk                                                      |
| `ContactMessage.php`                                  | Pesan dari form Kontak publik                                                            |
| `OfficeSetting.php`                                   | Pengaturan tunggal: lokasi kantor, radius absen, jam kerja normal                        |

### `app/Http/Controllers/` — dikelompokkan per area

| Folder         | Isinya                                                                                                  |
| -------------- | ------------------------------------------------------------------------------------------------------- |
| `Public/`      | Halaman-halaman publik (Beranda, Tentang, Karir, Kontak)                                                |
| `Auth/`        | Login                                                                                                   |
| `Employee/`    | Fitur self-service staf (absen, cuti, lembur, home, profil)                                             |
| `Approval/`    | Halaman persetujuan atasan (cuti, lembur, koreksi presensi)                                             |
| `Attendance/`  | Rekap absensi tim (untuk atasan)                                                                        |
| `Owner/`       | Fitur khusus Owner (karyawan, organisasi, akses, pengaturan kantor, pesan kontak)                       |
| `Dashboard/`   | Modul manajerial: Work, KPI, Contracts, Payroll, Budget, Royalty, Legal, IT — satu sub-folder per modul |
| `Recruitment/` | Kelola lowongan & pelamar                                                                               |

### `database/migrations/` — 40 file

Tiap file mencatat **satu perubahan** ke struktur tabel database, urut sesuai tanggal dibuat (jadi bisa dibaca seperti "riwayat pembangunan" aplikasi ini dari nol). Sebagian besar adalah migration "bikin tabel baru" untuk tiap fitur (mis. `..._attendances.php`, `..._kpis.php`, `..._payroll_records.php`), sebagian lagi adalah migration kecil untuk "menambah kolom" ke tabel yang sudah ada seiring fitur berkembang (mis. menambah field gaji ke tabel karyawan, menambah mode kerja "Lapangan/Gigs" ke tabel absensi).

### `database/seeders/` — 3 file

| File                      | Isinya                                                         |
| ------------------------- | -------------------------------------------------------------- |
| `DatabaseSeeder.php`      | "Daftar isi" — menentukan urutan seeder lain dijalankan        |
| `OfficeSettingSeeder.php` | Mengisi 1 baris pengaturan kantor (lokasi, radius, jam kerja)  |
| `DemoSeeder.php`          | Mengisi **seluruh** data contoh untuk testing — lihat bagian 5 |

### `resources/views/` — 104 file Blade, dikelompokkan sama seperti Controllers

Folder `layouts/` menyimpan kerangka halaman yang dipakai berulang (mis. `app.blade.php` untuk tampilan dashboard dengan sidebar, `employee.blade.php` untuk tampilan mobile-friendly staf, `public.blade.php` untuk halaman depan).

---

## 5. Data Dummy / Testing

Seluruh data contoh diisi lewat **satu perintah**:

```
php artisan migrate:fresh --seed
```

(atau `php artisan db:seed` kalau tabel sudah ada & cuma mau isi ulang data). Semua data dibuat oleh `database/seeders/DemoSeeder.php` — dirancang supaya **setiap halaman di bagian 3 di atas punya isi**, tidak ada yang kosong melompong saat pertama kali dites.

### Akun untuk login

Semua akun pakai password yang sama: **`password`** (wajib diganti sebelum dipakai beneran).

| Nama           | Email              | Role                 | Catatan Akses                                                                                         |
| -------------- | ------------------ | -------------------- | ----------------------------------------------------------------------------------------------------- |
| Whisnu Santika | `owner@wsm.local`  | Owner                | Otomatis akses penuh ke semua modul                                                                   |
| Kanaya         | `kanaya@wsm.local` | Manajer              | Akses `people` (lihat), `budget`/`royalty`/`kpi`/`contracts`/`payroll` (kelola), `legal`/`it` (lihat) |
| Rania          | `rania@wsm.local`  | HRD                  | Akses `people` & `recruitment` (kelola), `kpi` (lihat)                                                |
| Aldora         | `aldora@wsm.local` | Karyawan (Marketing) | Akses `work` (kelola) — contoh karyawan biasa yang diberi akses manajerial                            |
| Gepeng         | `gepeng@wsm.local` | Karyawan (Creative)  | Akses `work` (lihat saja) — contoh perbandingan dengan Aldora                                         |

### Ringkasan data per modul

| Data                    | Jumlah & Isi Singkat                                                                                                                                                                     | Tabel                                                   |
| ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| Karyawan                | 5 akun di atas, lengkap dengan data gaji & jam kerja untuk keperluan Payroll                                                                                                             | `users`                                                 |
| Hak akses dashboard     | 13 baris kombinasi user–modul, sengaja bervariasi (ada yang cuma "lihat", ada yang "kelola") biar perbedaan levelnya kelihatan                                                           | `dashboard_access`                                      |
| Pengumuman & MoM        | 3 memo (1 di-pin, 1 sudah dinonaktifkan sebagai contoh), lengkap 1 balasan thread & 1 status "sudah dibaca"                                                                              | `memos`, `memo_reads`, `memo_thread_messages`           |
| Izin/Cuti               | 2 pengajuan (1 disetujui otomatis kalau tanggal seed masih dalam bulan berjalan, 1 masih pending)                                                                                        | `leave_requests`                                        |
| Project & Tugas         | 2 project ("Album Q3 Release", "Merch Drop Oktober"), 9 tugas tersebar di berbagai status & tenggat (lewat tenggat, hari ini, besok, minggu ini/depan)                                   | `projects`, `work_items`                                |
| Rapat (MoM terstruktur) | 1 rapat kickoff lengkap 4 peserta & 2 tindak lanjut (1 di antaranya otomatis jadi tugas di Work Tracker)                                                                                 | `meetings`, `meeting_attendees`, `meeting_action_items` |
| Absensi                 | 5 hari kerja terakhir untuk Aldora & Gepeng (termasuk 1 contoh terlambat), 1 contoh "lupa absen pulang" (auto-closed sistem), 1 contoh mode Lapangan dengan 2 sesi dalam sehari (Kanaya) | `attendances`                                           |
| Lembur                  | 2 pengajuan (1 pending, 1 sudah disetujui)                                                                                                                                               | `overtime_requests`                                     |
| Koreksi Presensi        | 2 pengajuan (1 pending, 1 sudah disetujui & sudah diterapkan ke data absensi aslinya)                                                                                                    | `attendance_correction_requests`                        |
| KPI                     | 3 contoh dengan capaian berbeda-beda, supaya 3 warna badge (hijau/kuning/merah) sama-sama kelihatan                                                                                      | `kpis`                                                  |
| Kontrak Karyawan        | 2 file kontrak (path dummy, bukan file fisik asli — 1 sengaja dibuat "segera berakhir" untuk menguji badge peringatan)                                                                   | `employee_contracts`                                    |
| Payroll                 | 2 slip gaji (1 bulan lalu berstatus final, 1 bulan ini masih draft)                                                                                                                      | `payroll_records`                                       |
| Anggaran Project        | 3 baris anggaran (1 di antaranya sengaja melebihi budget, buat contoh selisih minus)                                                                                                     | `project_budgets`                                       |
| Royalti                 | 3 entri dengan 3 status berbeda (Estimated, Ready to Pay, Paid)                                                                                                                          | `royalty_entries`                                       |
| Dokumen Legal           | 2 dokumen (1 kontrak album yang segera berakhir, 1 perjanjian royalti jangka panjang)                                                                                                    | `legal_documents`                                       |
| Audit Log               | 5 catatan aktivitas contoh (login, ubah akses, buat memo, generate payroll, backup sistem)                                                                                               | `audit_logs`                                            |
| System Changelog        | 2 entri (1 versi yang sudah rilis, 1 versi yang masih direncanakan)                                                                                                                      | `system_changelogs`                                     |
| Lowongan Kerja          | 2 lowongan (1 tayang publik, 1 masih draft)                                                                                                                                              | `job_openings`                                          |
| Pelamar                 | 3 pelamar untuk lowongan yang tayang, tersebar di 3 tahap pipeline berbeda                                                                                                               | `job_applications`                                      |
| Pesan Kontak            | 2 pesan dari form Kontak publik (1 belum dibaca, 1 sudah)                                                                                                                                | `contact_messages`                                      |
| Pengaturan Kantor       | 1 baris (lokasi contoh: Depok, radius 200 meter, jam kerja 09:30–20:00)                                                                                                                  | `office_settings`                                       |

> Catatan: semua tanggal di atas dihitung relatif terhadap **kapan seeder dijalankan** (pakai "5 hari kerja terakhir dari hari ini", bukan tanggal tetap) — supaya datanya selalu relevan meskipun seeder dijalankan ulang di hari yang berbeda-beda.

---

## 6. Teknologi yang Digunakan

| Teknologi                     | Kegunaan                                                                                        |
| ----------------------------- | ----------------------------------------------------------------------------------------------- |
| **PHP 8.3+**                  | Bahasa pemrograman utama                                                                        |
| **Laravel 13**                | Framework backend — routing, database, autentikasi, dll                                         |
| **MySQL**                     | Database (dipakai di hosting produksi; bisa juga diuji pakai SQLite/MariaDB)                    |
| **Tailwind CSS v4**           | Styling tampilan, di-build lewat Vite                                                           |
| **Alpine.js**                 | Interaktivitas ringan di sisi browser (dropdown, modal, dll) tanpa perlu framework JS berat     |
| **Vite**                      | Build tool untuk menggabungkan & mengoptimalkan CSS/JS                                          |
| **Leaflet**                   | Peta interaktif untuk validasi lokasi absensi & pengaturan titik kantor                         |
| **SweetAlert2**               | Notifikasi & dialog konfirmasi yang lebih rapi dari `alert()` bawaan browser                    |
| **doctrine/dbal**             | Dibutuhkan Laravel untuk mengubah struktur kolom yang sudah ada (dipakai di beberapa migration) |
| **maatwebsite/excel**         | Export & import Excel (bagian 7) — sudah terpasang di `vendor/`                                 |
| **barryvdh/laravel-dompdf**   | Export PDF (bagian 7) — sudah terpasang di `vendor/`                                            |
| **cPanel Hosting (Rumahweb)** | Target hosting produksi — tanpa akses terminal/SSH, jadi deploy manual lewat upload file        |

Ikon di seluruh aplikasi memakai simbol Unicode sederhana (bukan pustaka ikon seperti Bootstrap Icons), jadi tidak perlu memuat file ikon tambahan.

---

## 7. Export & Import (Fitur Baru — Fondasi Sudah Jalan)

Fitur ini sedang dibangun **bertahap per-batch** biar bisa langsung dites tiap selesai satu bagian, bukan ditunggu sampai semuanya kelar sekaligus. Bagian ini isinya rencana lengkapnya + status tiap batch.

### Apa yang mau dibangun

Satu halaman pusat baru, **"Export & Import"** (muncul di sidebar, section paling bawah) — isinya kartu per-modul. Tiap kartu tunduk ke akses yang **sudah ada** (modul `dashboard_access` yang sama seperti dipakai halaman aslinya) — **tidak** bikin izin akses baru yang terpisah, biar Owner tidak perlu atur akses dua kali untuk hal yang sama.

Alurnya:

- **Export**: pilih filter (periode/karyawan/project) → lihat **preview** dulu di layar → baru tombol Download (Excel dan/atau PDF, tergantung kebutuhan modulnya — lihat tabel di bagian 3).
- **Import**: download **template** Excel kosong → isi → upload → lihat **preview** hasil baca file (baris yang valid vs baris yang error ditandai jelas) → baru tombol Konfirmasi Import.

### Status tiap batch

| Batch                      | Isinya                                                                                                                                                                        | Status                                                                                                  |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Batch 0 — Fondasi**      | Halaman menu "Export & Import" + kerangka dasar (base class Excel, base class Import, layout PDF, service preview import) yang dipakai semua batch berikutnya                 | ✅ **Selesai & sudah dites**                                                                            |
| **Batch 1 — Export Excel** | 11 modul: Rekap Absensi, KPI, Project Budgeting, Royalty, Employee Contracts, Legal Documents, Audit Log, Manajemen Karyawan, Rekrutmen–Pelamar, Work Tracker, Rekap Cuti Tim | ✅ **Kode selesai** — lihat catatan pengujian di bawah, ada 1 bagian yang **belum bisa dites langsung** |
| **Batch 2 — Export PDF**   | Payroll (slip gaji + rekap Excel-nya), Rekap Absensi (per-karyawan), Meetings/MoM (notulen)                                                                                   | ✅ **Kode selesai** — sama catatan pengujian seperti Batch 1, **belum bisa dites langsung**             |
| **Batch 3 — Import**       | Work Tracker (prioritas — nutup gap prototype lama)                                                                                                                           | ✅ **Selesai & sudah dites**                                                                            |
| **Batch 4 — Import**       | Manajemen Karyawan → KPI → Project Budgeting (urutan segini: Karyawan duluan karena paling sering kepake buat onboarding massal)                                              | ✅ **Kode selesai (ketiganya)**, belum dites                                                            |

Kartu **Import** untuk Work Tracker, **Manajemen Karyawan**, **KPI**, dan **Project Budgeting** sekarang SEMUA aktif (tombolnya sudah bisa diklik, gak ada lagi "Segera Hadir") — Work Tracker **sudah dites manual, aman**; Manajemen Karyawan/KPI/Project Budgeting **belum dites**, lihat catatan pengujian di bawah. Semua tombol **Export** (Excel & PDF) yang ada di catalog sudah aktif, kecuali kartu yang memang cuma punya 1 format dari awal (lihat bagian 3).

Payroll & Meetings punya alur beda dari 9 modul lain: PDF-nya **1 dokumen per record** (slip gaji 1 karyawan, notulen 1 rapat), bukan tabel banyak baris — jadi tombol "Export PDF" di kartunya membuka halaman **"pilih dulu"** (daftar record dengan tombol download per-baris), bukan langsung ke preview tabel seperti modul lain. Lihat detail di tabel file Batch 2 di bawah.

### Batch 4 — Import Manajemen Karyawan, KPI, Project Budgeting (kode selesai, belum dites)

**1. Manajemen Karyawan** — beda dari Work Tracker (Batch 3), import ini bikin **akun user baru** (bisa login), bukan cuma insert data biasa, jadi butuh keputusan tambahan dari Owner sebelum ditulis. Ini hasil finalnya, dan sudah diikuti persis di kode (`app/Imports/EmployeeImport.php`):

1. **Password** — kolom `password` di template **boleh dikosongkan**. Kalau diisi manual, dipakai apa adanya (sama seperti password akun biasa). Kalau dikosongkan, otomatis ke-isi `password` (sama kayak akun-akun demo/testing yang sudah ada) — bukan random per-orang. Halaman upload nanti kasih catatan soal ini biar Owner/HRD gak bingung pas nyoba login akun barunya.
2. **Jabatan (`role`)** — diisi langsung per baris di file Excel (Owner/Manajer/Karyawan/HRD), bukan default semua "Karyawan".
3. **Atasan langsung (`manager_id`)** — **TIDAK** ikut kolom import. Sama seperti Work Tracker yang tetap butuh CRUD manual buat sebagian hal, atasan diisi belakangan satu-satu lewat halaman edit karyawan yang sudah ada.
4. **Email yang sudah kepake** — baris itu **ditolak** (masuk baris error di preview, alasan "email sudah terdaftar"), TIDAK menimpa/update data karyawan lama. Kalau mau update data karyawan lama, tetap lewat halaman edit manual. Pengaman tambahan: 2 baris di file yang sama sengaja isi email baru yang SAMA, baris ke-2-nya ditolak juga, gak nunggu sampai bentrok pas nyimpan ke database.
5. **Field lengkap boleh diisi** — template ikut sediakan kolom divisi, jabatan pekerjaan, tanggal masuk, jatah cuti tahunan, tanggal lahir, gaji pokok, dll (bukan cuma nama/email/role) — tapi halaman upload & template kasih catatan jelas kolom mana yang **wajib** vs **boleh dikosongkan**.
6. **Tidak ada notifikasi otomatis** ke karyawan baru — Owner/HRD yang kabarin akun & passwordnya secara manual (WhatsApp/japri), di luar scope fitur ini.

**2. KPI** (`app/Imports/KpiImport.php`) — insert data biasa (bukan bikin akun), pola paling mirip Work Tracker. Kolom `karyawan` di template diisi nama/email karyawan yang **SUDAH terdaftar** (dicocokkan ke tabel `users`, sama cara `pic` di WorkItemImport) — kalau gak ketemu, baris ditolak (beda dari `pic` yang boleh kosong, di sini karyawan WAJIB diisi karena KPI tanpa pemilik gak ada gunanya). Kolom `capaian` (progress terkini) dan `status` boleh dikosongkan — default `capaian` ke 0 dan `status` ke "Active", meskipun form manual (`KpiRequest`) mewajibkan keduanya diisi; keputusan ini disengaja biar bulk-import KPI baru (yang progress-nya memang belum ada) tetap lolos per baris.

**3. Project Budgeting** (`app/Imports/ProjectBudgetImport.php`) — paling sederhana, cuma 1 lookup FK (`project`, dicocokkan ke tabel `projects` lewat nama, dan di sini **WAJIB diisi** — beda dari `project` di WorkItemImport yang boleh kosong). Kolom `realisasi` boleh dikosongkan, default 0 (sama seperti `actual` nullable di `BudgetRequest`).

Ketiga importer ini **belum** kena `AuditLog::record()` — sama seperti CRUD manual KPI/Budget/Manajemen Karyawan lewat form yang sudah ada (khusus Manajemen Karyawan, import-nya sendiri TETAP kena `AuditLog::record()`, cuma CRUD KPI/Budget manual yang belum diinstrumentasi — lihat daftar modul yang sudah/belum di bagian instrumentasi Fase 15 di arsip Fase 1-16).

### Apa yang sudah dites, dan apa yang belum bisa dites

**Sudah dites langsung lewat browser (login beneran, bukan cuma baca kode):**

- Halaman menu "Export & Import" — login sebagai **Owner** (13 kartu muncul semua) dan sebagai **Gepeng**, karyawan yang cuma punya akses modul `work` (cuma **2 kartu** yang muncul: Work Tracker & Meetings/MoM) — membuktikan penyaringan akses per-kartu jalan.
- Badge per-format (Excel aktif vs PDF "Segera Hadir" vs Import "Segera Hadir") dicek satu-satu, jumlahnya cocok persis dengan yang seharusnya untuk ke-13 kartu.

**BELUM bisa dites langsung** (halaman preview tabel & tombol Download di semua modul Batch 1 + Batch 2, Excel maupun PDF; halaman upload/preview/konfirmasi Import di Batch 3 Work Tracker & Batch 4 Manajemen Karyawan/KPI/Project Budgeting): package `maatwebsite/excel` **dan** `barryvdh/laravel-dompdf` **sudah terpasang** di `vendor/` (dicek langsung foldernya ada, beda dari catatan sebelumnya yang bilang belum ter-install), tapi saya tetap **belum sempat login & coba beneran lewat browser** untuk Batch 1/2/4 (Batch 3 Work Tracker sudah dites manual sama Owner, lihat status di tabel batch). Jadi Batch 1/2/4 sudah diperiksa teliti secara **audit kode manual** (nama kolom & nama relasi tiap model dicocokkan satu-satu ke kode aslinya, bukan ditebak — termasuk breakdown slip gaji yang saya cocokkan ke `dashboard/payroll/show.blade.php`, notulen ke `dashboard/work/meetings/show.blade.php`; untuk Import Manajemen Karyawan: rules validasi dicocokkan ke migration `users` (enum `role`, `unique email`, `annual_leave_entitlement` min/max) & `StoreEmployeeRequest` yang sudah ada, plus pengaman ekstra buat 2 baris rebutan 1 email baru yang sama dalam 1 file; untuk Import KPI: rules dicocokkan ke migration `kpis` & `KpiRequest` (`current`/`status` sengaja dilonggarkan jadi nullable buat kebutuhan bulk-import, lihat bagian Batch 4 di atas); untuk Import Project Budgeting: rules dicocokkan ke migration `project_budgets` & `BudgetRequest`) — tapi **belum dites "beneran jalan end-to-end"** oleh siapa pun untuk Batch 1/2/4. Kesimpulan audit: kodenya konsisten sama pola & kerangka Batch 0 (`BaseImport`/`ImportPreviewService`/`TemplateExport`) dan gak ada hal yang meleset dari model/rules aslinya sejauh yang saya periksa — tapi ini **bukan pengganti tes beneran**, ada kemungkinan gap yang cuma kelihatan pas benar-benar diklik (mis. kombinasi format tanggal dari Excel yang beda-beda, cocokan nama Karyawan/Project yang meleset dikit, atau — khusus Manajemen Karyawan — akun baru yang passwordnya gak sesuai ekspektasi). Tolong dicoba manual, kalau ada error tinggal kabari saya. Yang paling penting buat dicoba (urutan prioritas):

1. **Rekap Absensi → Export PDF**, pilih 1 karyawan → pastikan PDF-nya kebuka & datanya cocok sama Excel-nya.
2. **Payroll → Export PDF**, pilih periode yang sudah ada payroll-nya (generate dulu kalau belum ada) → klik salah satu baris "Unduh Slip PDF" → cek angka breakdown-nya cocok sama halaman Detail Payroll yang sudah ada.
3. **Meetings/MoM → Export PDF** (kartu ini muncul di sidebar Work Control), pilih 1 rapat → cek notulen PDF-nya lengkap (peserta, catatan, keputusan, action items).
4. **Payroll → Export Excel** — rekap 1 baris per karyawan untuk 1 periode.
5. **Work Tracker → Import** (Batch 3): download template dulu, isi 2-3 baris pakai nama Project/PIC yang beneran ada + 1 baris sengaja salah (mis. progress diisi teks bebas di luar pilihan) buat mastiin baris error kedeteksi → upload → cek preview (baris valid vs error, kolom Project/PIC nampilin nama bukan angka) → klik Konfirmasi → cek task barunya beneran muncul di papan Work Tracker dengan section/item_no yang bener.
6. **Manajemen Karyawan → Import** (Batch 4 — **paling penting dicoba hati-hati** karena bikin akun login beneran): download template, isi 2-3 baris (1 baris password dikosongkan, 1 baris password diisi manual, 1 baris role capital seperti "Karyawan" buat mastiin gak case-sensitive) + 1 baris email yang sengaja sama persis kayak baris lain di file yang sama (buat mastiin ke-detect dobel) + 1 baris pakai email yang sudah ada di sistem (buat mastiin ditolak, bukan nimpa) → upload → cek preview (kolom Password nunjukin "Default (password)" vs "Diisi manual" sesuai baris) → Konfirmasi → coba **login pakai akun yang baru diimport** (password kosong tadi pakai `password`) → cek juga masuk di halaman Audit Log ("... ditambahkan lewat import").
7. **KPI → Import** (Batch 4, baru): download template, isi 2-3 baris pakai nama/email karyawan yang beneran ada di kolom `karyawan` + 1 baris sengaja pakai nama karyawan yang gak ada (buat mastiin baris error kedeteksi, pesan "Karyawan tidak ditemukan") + 1 baris kosongkan `capaian` dan `status` (buat mastiin fallback 0/"Active" jalan) → upload → cek preview (kolom Karyawan nampilin nama, bukan angka id) → klik Konfirmasi → cek KPI barunya beneran muncul di halaman KPI & Performance dengan employee yang benar, dan (kalau karyawan itu login) kartu "My KPI" di App Mode Home ikut kebaca.
8. **Project Budgeting → Import** (Batch 4, terakhir): download template, isi 2-3 baris pakai nama Project yang beneran ada di kolom `project` + 1 baris sengaja pakai nama project yang gak ada (buat mastiin baris error kedeteksi) + 1 baris kosongkan `realisasi` (buat mastiin fallback 0 jalan) → upload → cek preview (kolom Project nampilin nama, bukan angka id) → klik Konfirmasi → cek baris budget barunya beneran muncul di halaman Project Budgeting, dikelompokkan di project yang benar, dan angka Budget vs Actual/selisihnya kebaca benar.

### File yang ditambahkan/diubah — Batch 0 + Batch 1

**Fondasi (Batch 0):**

| File                                                                     | Isinya                                                                                                                                    |
| ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Support/ExportImport/ExportCatalog.php`                             | Daftar tunggal semua modul yang punya export/import — label, format, modul akses yang jadi gerbangnya, dan status implementasi per-format |
| `app/Support/ExportImport/ImportPreviewService.php`                      | Alur simpan-sementara hasil parsing file import sebelum benar-benar dikonfirmasi masuk database (dipakai Batch 3)                         |
| `app/Exports/BaseExport.php`                                             | Kerangka dasar untuk semua export Excel                                                                                                   |
| `app/Exports/TemplateExport.php`                                         | Generator file template Excel kosong untuk tombol "Download Template" (dipakai Batch 3)                                                   |
| `app/Imports/BaseImport.php`                                             | Kerangka dasar untuk semua import — baca file, validasi per-baris, pisahkan baris valid/error (dipakai Batch 3)                           |
| `app/Http/Controllers/Dashboard/ExportImport/ExportImportController.php` | Controller halaman menu (index)                                                                                                           |
| `resources/views/dashboard/export-import/index.blade.php`                | Tampilan halaman menu (kartu per-modul)                                                                                                   |
| `resources/views/pdf/layout.blade.php`                                   | Kop surat/letterhead dasar untuk semua export PDF (dipakai Batch 2)                                                                       |

**Export Excel (Batch 1) — 11 class Export, 1 controller, 1 view preview, dipakai bareng:**

| File                                                               | Isinya                                                                                         |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Dashboard/ExportImport/ExportController.php` | Controller tunggal untuk SEMUA export (preview + download) — routing berdasar `{key}/{format}` |
| `resources/views/dashboard/export-import/preview.blade.php`        | View tunggal untuk preview tabel SEMUA modul (dipakai bareng, bukan 1 file per modul)          |
| `app/Exports/AttendanceRecapExport.php`                            | Rekap Absensi — filter: periode (bulan) + karyawan                                             |
| `app/Exports/KpiExport.php`                                        | KPI & Performance — filter: periode                                                            |
| `app/Exports/ProjectBudgetExport.php`                              | Project Budgeting — filter: project                                                            |
| `app/Exports/RoyaltyEntryExport.php`                               | Royalty Dashboard — filter: periode                                                            |
| `app/Exports/EmployeeContractExport.php`                           | Employee Contracts — tanpa filter                                                              |
| `app/Exports/LegalDocumentExport.php`                              | Legal Documents — tanpa filter                                                                 |
| `app/Exports/AuditLogExport.php`                                   | Audit Log — filter: rentang tanggal                                                            |
| `app/Exports/EmployeeExport.php`                                   | Manajemen Karyawan — tanpa filter (owner-only)                                                 |
| `app/Exports/JobApplicationExport.php`                             | Rekrutmen – Pelamar — filter: lowongan                                                         |
| `app/Exports/WorkItemExport.php`                                   | Work Tracker — filter: project                                                                 |
| `app/Exports/LeaveRequestExport.php`                               | Rekap Izin/Cuti Tim — filter: periode                                                          |

**File yang DIUBAH (bukan file baru):**

| File                                                      | Perubahan                                                                                                                                                 |
| --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `routes/web.php`                                          | + route `dashboard.export-import.index` (Batch 0), + route `dashboard.export-import.preview` & `.download` (Batch 1)                                      |
| `resources/views/layouts/app.blade.php`                   | + section sidebar ke-9 "EXPORT & IMPORT" (Batch 0)                                                                                                        |
| `resources/views/dashboard/export-import/index.blade.php` | Badge & tombol disesuaikan supaya per-FORMAT (Excel/PDF/Import dicek independen, bukan 1 status untuk seluruh kartu) — lihat contoh Rekap Absensi di atas |
| `composer.json`                                           | + `maatwebsite/excel`, + `barryvdh/laravel-dompdf` (Batch 0)                                                                                              |
| `README.md`                                               | Bagian ini (bagian 7)                                                                                                                                     |

### File yang ditambahkan/diubah — Batch 2 (Export PDF)

**File baru:**

| File                                                              | Isinya                                                                                                                                                  |
| ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Exports/PayrollExport.php`                                   | Payroll — Excel rekap sebulan semua karyawan (pola sama seperti 11 export Batch 1)                                                                      |
| `resources/views/pdf/attendance-recap.blade.php`                  | Isi dokumen PDF Rekap Absensi 1 karyawan (headings/rows dipakai bareng dari `AttendanceRecapExport` yang sama dengan versi Excel-nya)                   |
| `resources/views/pdf/payroll-slip.blade.php`                      | Isi dokumen PDF slip gaji 1 karyawan 1 periode — breakdown sama persis dengan `dashboard/payroll/show.blade.php`, + watermark "DRAFT" kalau belum final |
| `resources/views/pdf/meeting-minutes.blade.php`                   | Isi dokumen PDF notulen 1 rapat — peserta, catatan, keputusan, action items, sama data dengan `dashboard/work/meetings/show.blade.php`                  |
| `resources/views/dashboard/export-import/picker.blade.php`        | View "pilih record" untuk Payroll & Meetings PDF (1 dokumen per record, bukan tabel) — tiap baris punya tombol download sendiri                         |
| `resources/views/dashboard/export-import/_filters-form.blade.php` | Partial form filter, diekstrak dari `preview.blade.php` biar bisa dipakai bareng `picker.blade.php` tanpa duplikat markup                               |

**File yang DIUBAH:**

| File                                                               | Perubahan                                                                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Dashboard/ExportImport/ExportController.php` | + cabang `format === 'pdf'` (sesuai rencana lama) — Rekap Absensi PDF numpang `resolve()`+`preview.blade.php` yang sudah ada (wajib pilih karyawan); Payroll & Meetings PDF lewat jalur "picker" baru (`pickerPreview()`, `payrollPicker()`, `downloadPayrollSlip()`, `meetingsPicker()`, `downloadMeetingMinutes()`) |
| `app/Support/ExportImport/ExportCatalog.php`                       | `implemented_exports` untuk `attendance-recap` (+`pdf`), `payroll` (+`pdf`,`excel`), `meetings` (+`pdf`) di-update dari kosong/parsial ke lengkap                                                                                                                                                                     |
| `resources/views/dashboard/export-import/preview.blade.php`        | Dukung `$requiresSelection` (Rekap Absensi PDF: sembunyikan tombol download & kasih pesan kalau karyawan belum dipilih) & `$downloadLabel` ("Download Excel"/"Download PDF"); form filter dipindah ke partial                                                                                                         |
| `routes/web.php`                                                   | Komentar di grup `export-import` diperbarui (route-nya sendiri **tidak berubah** — tetap generik `{key}/{format}`, Payroll/Meetings numpang query string `?payroll_id=`/`?meeting_id=`)                                                                                                                               |
| `README.md`                                                        | Bagian ini (bagian 7) — status Batch 2, tabel file, catatan pengujian                                                                                                                                                                                                                                                 |

### File yang ditambahkan/diubah — Batch 3 (Import Work Tracker) & Batch 4 (Import Manajemen Karyawan/KPI/Project Budgeting)

**File baru:**

| File                                                               | Isinya                                                                                                                                                   |
| ------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Imports/WorkItemImport.php`                                   | Import Work Tracker (Batch 3) — lookup Project/PIC lewat nama, `progress`/`prioritas` nullable dengan fallback                                           |
| `app/Imports/EmployeeImport.php`                                   | Import Manajemen Karyawan (Batch 4) — bikin akun login baru, password fallback, cek email dobel dalam 1 file                                             |
| `app/Imports/KpiImport.php`                                        | Import KPI (Batch 4) — lookup Karyawan lewat nama/email (wajib sudah terdaftar), `capaian`/`status` nullable dengan fallback (0/"Active")                |
| `app/Imports/ProjectBudgetImport.php`                              | Import Project Budgeting (Batch 4, terakhir) — lookup Project lewat nama (wajib), `realisasi` nullable dengan fallback (0)                               |
| `resources/views/dashboard/export-import/import-show.blade.php`    | View generik halaman upload (form + link download template + catatan per-kolom dari `fieldNotes()`) — dipakai bareng ke-4 modul, gak ada logic per-modul |
| `resources/views/dashboard/export-import/import-preview.blade.php` | View generik halaman preview (baris valid vs error dari `previewColumns()`) — dipakai bareng ke-4 modul, gak ada logic per-modul                         |

**File yang DIUBAH:**

| File                                                               | Perubahan                                                                                                                                                                                                                                                                                                                                                                                      |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Dashboard/ExportImport/ImportController.php` | Controller tunggal untuk SEMUA import (show/template/preview/commit) — `IMPLEMENTED` sekarang `['work-tracker', 'employees', 'kpi', 'budget']` (lengkap), + cabang `resolveImporter()`/`exampleRows()`/`persist()` buat `kpi` & `budget` (`persistKpi()` set `created_by`, `persistBudget()` set `updated_by`, keduanya belum kena `AuditLog::record()` — sama seperti CRUD manual KPI/Budget) |
| `app/Support/ExportImport/ExportCatalog.php`                       | `import_implemented` untuk `kpi` & `budget` di-update dari `false` (Batch 3) jadi `true` (Batch 4) — kartu Import KPI & Project Budgeting di halaman menu sekarang aktif, gak lagi "Segera Hadir"                                                                                                                                                                                              |
| `README.md`                                                        | Bagian ini (bagian 7) — status Batch 3 & 4 lengkap, tabel file, catatan pengujian                                                                                                                                                                                                                                                                                                              |

### Catatan instalasi package

`maatwebsite/excel` dan `barryvdh/laravel-dompdf` **sudah terpasang** di `vendor/` (tercatat di `composer.json` & `composer.lock`), jadi tidak perlu `composer require` lagi. Yang tersisa hanya menjalankan tes manual — lihat bagian 8 (kelompok J).

---

## 8. Checklist Tes Manual di Browser (dari awal sampai akhir)

Disusun dari membaca kode, route, dan seeder (aplikasinya **belum dijalankan** saat checklist ini dibuat), jadi urutannya mengikuti alur pemakaian nyata: publik → login → karyawan → atasan → Owner → modul manajerial → rekrutmen → export/import → lintas role. Centang setelah dicoba sendiri.

Legenda: ☐ belum dites · ✅ sudah dites & aman · ❌ ada masalah (catat di kolom Catatan / issue).

### Persiapan Tes

Lakukan sekali sebelum mulai.

| Status | ID  | Halaman / Fitur                  | Yang dites → hasil yang benar                                                                                                                                                                                                             |
| ------ | --- | -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ☐      | P1  | Terminal lokal                   | `php artisan migrate:fresh --seed`, `php artisan storage:link`, `npm run build` (atau `npm run dev`) → data demo terisi, tidak ada error.                                                                                                 |
| ☐      | P2  | Browser                          | Siapkan Chrome desktop, Chrome Android, dan Safari iOS; izinkan **Lokasi** dan **Kamera** untuk situs.                                                                                                                                    |
| ☐      | P3  | Akun (password semua `password`) | OWN `owner@wsm.local` · KAN Kanaya (manajer) `kanaya@wsm.local` · RAN Rania (HRD) `rania@wsm.local` · ALD Aldora `aldora@wsm.local` · GEP Gepeng `gepeng@wsm.local`. Pakai jendela Incognito berbeda per akun supaya sesi tidak tertukar. |

### A. Halaman Publik (tanpa login)

| Status | ID  | Halaman / Fitur                                                | Yang dites → hasil yang benar                                                                                     |
| ------ | --- | -------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| ☐      | A1  | `/`, `/tentang-kami`, `/layanan`                               | Ketiganya terbuka tanpa error, menu navigasi jalan, tampil rapi di HP.                                            |
| ☐      | A2  | `/karir`                                                       | Hanya lowongan berstatus tayang yang muncul (seed: 1 tayang, 1 draft tidak muncul).                               |
| ☐      | A3  | `/karir/{slug}`                                                | Detail lowongan tampil; slug lowongan draft/ditutup → 404.                                                        |
| ☐      | A4  | Form Lamar                                                     | Kosong → pesan validasi (nama & email wajib); isi valid → pesan sukses; kirim >5x dalam semenit → 429 (throttle). |
| ☐      | A5  | `/kontak`                                                      | Kosong → validasi; valid → pesan sukses; >5x/menit → 429.                                                         |
| ☐      | A6  | URL ngawur (`/abc`)                                            | Halaman 404 custom tampil.                                                                                        |
| ☐      | A7  | Buka `/dashboard`, `/app/home`, `/owner/dashboard` tanpa login | Semuanya redirect ke `/login`.                                                                                    |

### B. Login, Logout, Sesi

| Status | ID  | Halaman / Fitur                                   | Yang dites → hasil yang benar                                           |
| ------ | --- | ------------------------------------------------- | ----------------------------------------------------------------------- |
| ☐      | B1  | `/login` password salah                           | Muncul "Email atau password salah."                                     |
| ☐      | B2  | Salah password 6x berturut-turut                  | Diblokir throttle (429), tidak bisa coba terus.                         |
| ☐      | B3  | Login OWN                                         | Masuk ke `/owner/dashboard`.                                            |
| ☐      | B4  | Login KAN / RAN / ALD / GEP                       | Masuk ke `/app/home` (App Mode versi HP).                               |
| ☐      | B5  | Buka URL terproteksi saat belum login, lalu login | Setelah login kembali ke URL tujuan awal.                               |
| ☐      | B6  | Sudah login lalu buka `/login`                    | Form login tidak tampil lagi.                                           |
| ☐      | B7  | Centang "ingat saya"                              | Tutup & buka browser tetap login.                                       |
| ☐      | B8  | Logout                                            | Kembali ke `/login`; tombol Back tidak menampilkan halaman terproteksi. |
| ☐      | B9  | Login dengan akun yang sudah dinonaktifkan Owner  | Gagal login.                                                            |

### C. App Mode Karyawan (login ALD, kecuali disebut lain)

| Status | ID  | Halaman / Fitur                                       | Yang dites → hasil yang benar                                                                                                                            |
| ------ | --- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ☐      | C1  | Home `/app/home`                                      | Kartu sisa cuti, KPI Saya, Work Tracker Saya, milestone, ulang tahun/anniversary tim, banner cuti berbayar tampil tanpa error.                           |
| ☐      | C2  | Home → Info dari Owner (memo)                         | Memo tampil, yang di-pin di atas, yang nonaktif tidak tampil; memo untuk penerima tertentu hanya muncul di akun penerima; tandai baca/sembunyikan jalan. |
| ☐      | C3  | Inbox (modal)                                         | Dibuka → badge belum-dibaca hilang otomatis.                                                                                                             |
| ☐      | C4  | Balas thread memo                                     | Balasan terkirim & terbaca di sisi Owner (Work Control → Memo); >15x/menit diblokir.                                                                     |
| ☐      | C5  | Absen Masuk mode **Kantor**, di dalam radius          | Tercatat, jarak tampil, peta Leaflet muncul, pesan sukses.                                                                                               |
| ☐      | C6  | Absen Kantor di luar radius                           | Radius ketat aktif → tercatat + catatan "X m dari kantor, di luar radius"; radius ketat mati → tanpa catatan (sesuaikan dengan Pengaturan Kantor).       |
| ☐      | C7  | Tolak izin lokasi browser                             | Muncul "Lokasi belum kebaca…"; tombol Test Lokasi berfungsi.                                                                                             |
| ☐      | C8  | Absen mode **WFH**                                    | Tercatat tanpa hitung jarak.                                                                                                                             |
| ☐      | C9  | Absen masuk 2x di hari yang sama (Kantor/WFH)         | Ditolak: "cuma 1 sesi per hari".                                                                                                                         |
| ☐      | C10 | Mode **Lapangan / Gigs**                              | Masuk → pulang → masuk lagi dibolehkan (sesi ke-2); masuk lagi sebelum pulang ditolak.                                                                   |
| ☐      | C11 | Absen pulang tanpa absen masuk                        | Error "Kamu belum absen masuk hari ini."                                                                                                                 |
| ☐      | C12 | Foto absen                                            | Selfie tersimpan & tampil di Rekap (butuh `storage:link`); tanpa foto / kamera ditolak tetap bisa absen.                                                 |
| ☐      | C13 | Absen saat cuti disetujui hari ini                    | Ditolak: "sedang izin/cuti hari ini".                                                                                                                    |
| ☐      | C14 | Lupa absen pulang                                     | Hari berikutnya sesi tertutup otomatis & berlabel "lupa absen pulang".                                                                                   |
| ☐      | C15 | Riwayat Absensi `/app/riwayat`                        | Data per bulan, tombol bulan sebelum/sesudah, badge terlambat/kurang jam, blok kekurangan jam.                                                           |
| ☐      | C16 | Pengajuan Izin/Cuti                                   | Tanggal lampau ditolak; selesai < mulai ditolak; cuti tahunan melebihi sisa ditolak (pesan menyebut sisa hari); valid → "tunggu persetujuan".            |
| ☐      | C17 | Cuti jenis sakit/pribadi/lainnya                      | Tidak mengurangi jatah cuti tahunan.                                                                                                                     |
| ☐      | C18 | Batalkan pengajuan pending                            | Alasan wajib; status jadi dibatalkan; sisa cuti kembali.                                                                                                 |
| ☐      | C19 | Ajukan cuti tanggal yang sama/tumpang tindih dua kali | **Cek & catat hasilnya** — dari pembacaan kode belum ada validasi tumpang tindih (lihat bagian 9).                                                       |
| ☐      | C20 | Pengajuan Lembur                                      | Tanggal lampau ditolak; tanggal sama 2x ditolak; batalkan berjalan.                                                                                      |
| ☐      | C21 | Koreksi Presensi                                      | Tanggal masa depan ditolak; jam masuk & pulang sama-sama kosong ditolak; valid → pending; batalkan berjalan.                                             |
| ☐      | C22 | Kalender Tim                                          | Grid bulan tampil, navigasi bulan & filter jalan, klik tugas membuka detail.                                                                             |
| ☐      | C23 | Profil → ganti password                               | Password lama salah ditolak; <8 karakter / konfirmasi beda ditolak; sukses → logout & login ulang dengan password baru.                                  |
| ☐      | C24 | Batas akses ALD                                       | Buka `/owner/dashboard`, `/dashboard/payroll`, `/persetujuan` → halaman 403.                                                                             |

### D. Persetujuan Atasan (KAN, RAN, OWN)

Aturan: yang berhak memutuskan = atasan langsung (`manager_id`) atau Owner. Atasan ALD & GEP = KAN; atasan KAN & RAN = OWN.

| Status | ID  | Halaman / Fitur                                  | Yang dites → hasil yang benar                                                                                  |
| ------ | --- | ------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- |
| ☐      | D1  | Persetujuan Izin/Cuti (KAN)                      | Daftar berisi pengajuan ALD & GEP; setujui → status disetujui, tercatat di Audit Log, sisa cuti ALD berkurang. |
| ☐      | D2  | Tolak (KAN)                                      | Alasan wajib; ALD melihat alasan di halaman Pengajuan.                                                         |
| ☐      | D3  | Putuskan dua kali (dua tab)                      | Tab kedua: "Pengajuan ini sudah diputuskan sebelumnya."                                                        |
| ☐      | D4  | RAN (HRD) buka Persetujuan                       | Halaman terbuka tapi daftar kosong (RAN bukan atasan siapa pun); memutuskan pengajuan ALD via URL/POST → 403.  |
| ☐      | D5  | OWN                                              | Bisa melihat & memutuskan pengajuan semua orang.                                                               |
| ☐      | D6  | Batalkan pengajuan yang sudah disetujui (atasan) | Alasan wajib; cuti kembali; ALD bisa absen lagi di hari itu.                                                   |
| ☐      | D7  | Persetujuan Lembur                               | Setujui/tolak/batalkan berjalan; lembur disetujui ikut terhitung di Payroll (H3).                              |
| ☐      | D8  | Persetujuan Koreksi Presensi                     | Setujui → jam di Riwayat & Rekap ALD berubah sesuai koreksi; tolak/batalkan berjalan.                          |
| ☐      | D9  | ALD / GEP buka `/persetujuan`                    | 403.                                                                                                           |

### E. Rekap Absensi (modul `people`)

| Status | ID  | Halaman / Fitur                                     | Yang dites → hasil yang benar                                                                           |
| ------ | --- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| ☐      | E1  | `/absensi` per akun                                 | OWN & RAN melihat semua karyawan; KAN hanya dirinya + bawahan; ALD → 403 dan menu tidak ada di sidebar. |
| ☐      | E2  | Detail `/absensi/{id}`                              | Tabel harian, jam, jarak, foto masuk/pulang tampil.                                                     |
| ☐      | E3  | Koreksi manual (RAN / OWN = manage)                 | Jam berubah & tersimpan; validasi jalan.                                                                |
| ☐      | E4  | KAN (people = view) coba koreksi                    | Tombol tidak tampil; POST langsung → 403.                                                               |
| ☐      | E5  | KAN buka detail karyawan di luar cakupan (mis. RAN) | 403.                                                                                                    |

### F. Owner

| Status | ID  | Halaman / Fitur                              | Yang dites → hasil yang benar                                                                                                                             |
| ------ | --- | -------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ☐      | F1  | Dashboard Owner `/owner/dashboard`           | Ringkasan tampil tanpa error.                                                                                                                             |
| ☐      | F2  | Manajemen Karyawan — daftar                  | Aktif & nonaktif tampil, pencarian/filter jalan.                                                                                                          |
| ☐      | F3  | Tambah karyawan                              | Email harus unik, password ≥8, atasan langsung & role tersimpan, akun baru bisa login.                                                                    |
| ☐      | F4  | Edit karyawan                                | Password dikosongkan = tidak berubah; ubah role/atasan tersimpan.                                                                                         |
| ☐      | F5  | Nonaktifkan                                  | Akun sendiri tidak bisa dinonaktifkan; akun lain berhasil, tidak bisa login, tercatat Audit Log.                                                          |
| ☐      | F6  | Aktifkan kembali                             | Bisa login lagi.                                                                                                                                          |
| ☐      | F7  | Struktur Organisasi                          | Bagan atasan-bawahan sesuai `manager_id`.                                                                                                                 |
| ☐      | F8  | Atur Akses Dashboard — untuk Owner           | Muncul "Owner otomatis punya akses penuh…".                                                                                                               |
| ☐      | F9  | Atur Akses — beri GEP `kpi` view, lalu cabut | Login GEP: menu KPI muncul tanpa tombol kelola; setelah dicabut → 403.                                                                                    |
| ☐      | F10 | Pengaturan Kantor                            | Ubah koordinat, radius (10–5000), jam kerja, toleransi, tarif potongan, warna → tersimpan & langsung dipakai absen berikutnya; input tidak valid ditolak. |
| ☐      | F11 | Toggle geo off / radius ketat                | Perilaku absen mode Kantor berubah sesuai.                                                                                                                |
| ☐      | F12 | Pesan Kontak                                 | Pesan dari form publik (A5) muncul; tandai sudah dibaca.                                                                                                  |
| ☐      | F13 | Kunci Dashboard (OWN & ALD)                  | Kunci → buka `/dashboard` → layar kunci; password salah ditolak; benar → kembali ke URL semula.                                                           |

### G. Work Control (ALD = manage, GEP = view)

| Status | ID  | Halaman / Fitur                 | Yang dites → hasil yang benar                                                                                |
| ------ | --- | ------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| ☐      | G1  | Memo Forum `/dashboard/work`    | Daftar tampil; GEP tidak melihat tombol tambah/edit.                                                         |
| ☐      | G2  | Buat Memo / MoM cepat           | Tipe, judul, isi wajib; pin; audiens semua/tertentu (penerima wajib bila tertentu); tampil di Home karyawan. |
| ☐      | G3  | Edit / nonaktifkan / hapus memo | Sesuai; memo nonaktif hilang dari Home.                                                                      |
| ☐      | G4  | Balas thread (manage)           | Terkirim & tampil di Home penerima.                                                                          |
| ☐      | G5  | Timeline Calendar               | Tampil, navigasi bulan jalan.                                                                                |
| ☐      | G6  | Work Tracker — Project          | Tambah (warna hex valid, tanggal akhir ≥ mulai), edit, hapus → task pindah ke "Tanpa Project".               |
| ☐      | G7  | Work Tracker — Task             | Tambah (section, PIC, due, progress, prioritas, link valid), edit, hapus.                                    |
| ☐      | G8  | Ubah progress cepat             | 6 status (Pending…Postpone) tersimpan; task muncul di "Work Tracker Saya" karyawan PIC.                      |
| ☐      | G9  | Filter / drag-drop board        | Filter jalan; hasil drag-drop bertahan setelah refresh.                                                      |
| ☐      | G10 | GEP coba tambah task (URL/POST) | 403.                                                                                                         |
| ☐      | G11 | Meetings — buat                 | Agenda wajib; peserta; action item (PIC / semua, due) + "sync ke tracker" → task muncul di Work Tracker.     |
| ☐      | G12 | Meetings — edit, lihat, hapus   | Menghapus action item saat edit bekerja; hapus rapat bekerja.                                                |
| ☐      | G13 | Meetings — Blast                | Menjadi Memo ke semua karyawan & muncul di Home mereka; >10x/menit diblokir.                                 |

### H. Modul Manajerial (KAN = manage, RAN = KPI view)

| Status | ID  | Halaman / Fitur                | Yang dites → hasil yang benar                                                                                                                               |
| ------ | --- | ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ☐      | H1  | KPI                            | Tambah/edit/hapus; badge hijau/kuning/merah sesuai persen; RAN tidak melihat tombol kelola; KPI muncul di kartu "KPI Saya" karyawan.                        |
| ☐      | H2  | Kontrak Karyawan               | Upload PDF/DOC/JPG ≤10MB; >10MB atau .exe ditolak; badge "segera berakhir"; file terbuka; edit ganti file (file lama terhapus); hapus (file ikut terhapus). |
| ☐      | H3  | Payroll — generate             | Pilih periode → draft untuk semua karyawan bergaji; total = gaji pokok + (hari lembur disetujui × tarif) − (blok kekurangan jam × tarif potongan).          |
| ☐      | H4  | Payroll — generate ulang       | Draft ditimpa; yang sudah final dilewati (ada pesan "dilewati").                                                                                            |
| ☐      | H5  | Payroll — detail & penyesuaian | Isi penyesuaian & catatan → total ikut berubah.                                                                                                             |
| ☐      | H6  | Payroll — finalisasi           | Status Final; tidak bisa diedit/dihapus/digenerate ulang.                                                                                                   |
| ☐      | H7  | Payroll — tandai dibayar       | Hanya dari status Final.                                                                                                                                    |
| ☐      | H8  | Payroll — hapus                | Hanya draft.                                                                                                                                                |
| ☐      | H9  | Project Budgeting              | CRUD; dikelompokkan per project; realisasi > budget → selisih minus.                                                                                        |
| ☐      | H10 | Royalty                        | CRUD; filter status (Estimated / Reported / Ready to Pay / Paid).                                                                                           |
| ☐      | H11 | Legal                          | Tab Album Contracts & Royalty Agreements; upload; badge jatuh tempo; edit; hapus.                                                                           |
| ☐      | H12 | IT — Audit Log                 | Daftar aktivitas; hanya lihat, tanpa tombol tambah/hapus.                                                                                                   |
| ☐      | H13 | IT — System Changelog          | CRUD entri versi.                                                                                                                                           |
| ☐      | H14 | Akses view vs manage           | KAN (legal/it = view) tidak melihat tombol kelola; RAN buka `/dashboard/payroll` → 403.                                                                     |

### I. Rekrutmen (RAN)

| Status | ID  | Halaman / Fitur                | Yang dites → hasil yang benar                                                                |
| ------ | --- | ------------------------------ | -------------------------------------------------------------------------------------------- |
| ☐      | I1  | Lowongan — buat                | Judul, status draft/tayang; judul kembar tetap menghasilkan slug unik.                       |
| ☐      | I2  | Tayangkan / tutup              | Tayang → muncul di `/karir`; ditutup → hilang.                                               |
| ☐      | I3  | Edit lowongan                  | Perubahan tersimpan & tampil di halaman publik.                                              |
| ☐      | I4  | Pelamar — daftar & detail      | Lamaran dari form publik (A4) masuk berstatus baru.                                          |
| ☐      | I5  | Ubah status pipeline           | baru → ditinjau → interview → ditawari → diterima/ditolak tersimpan.                         |
| ☐      | I6  | Convert jadi karyawan          | Form akun (email unik, password ≥8) → akun terbuat & bisa login; convert kedua kali ditolak. |
| ☐      | I7  | KAN buka `/rekrutmen/lowongan` | 403.                                                                                         |

### J. Export & Import (OWN; GEP untuk uji akses)

Detail langkah tiap tes ada di bagian 7 ("Apa yang sudah dites…").

| Status | ID  | Halaman / Fitur                                | Yang dites → hasil yang benar                                                                                                                |
| ------ | --- | ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| ✅     | J1  | Halaman menu `/dashboard/export-import`        | OWN 13 kartu, GEP 2 kartu (Work Tracker, Meetings). ✅ sudah dites.                                                                          |
| ☐      | J2  | Export Excel semua modul (preview → download)  | File terbuka di Excel; kolom & angka cocok dengan layar.                                                                                     |
| ☐      | J3  | Export PDF Rekap Absensi                       | Pilih 1 karyawan; PDF terbuka & cocok dengan Excel-nya.                                                                                      |
| ☐      | J4  | Export PDF Payroll                             | Pilih periode → unduh slip; angka cocok dengan Detail Payroll.                                                                               |
| ☐      | J5  | Export PDF Meetings                            | Notulen lengkap (peserta, catatan, keputusan, action item).                                                                                  |
| ✅     | J6  | Import Work Tracker                            | ✅ sudah dites Owner.                                                                                                                        |
| ☐      | J7  | Import Manajemen Karyawan                      | Password kosong → `password`; email dobel (di DB / dalam file) ditolak; role tidak case-sensitive; akun baru bisa login; tercatat Audit Log. |
| ☐      | J8  | Import KPI                                     | Karyawan tidak ditemukan → baris error; capaian/status kosong → 0 / Active; hasil muncul di halaman KPI.                                     |
| ☐      | J9  | Import Project Budgeting                       | Project tidak ditemukan → baris error; realisasi kosong → 0; hasil muncul di halaman Budgeting.                                              |
| ☐      | J10 | GEP buka `/dashboard/export-import/kpi/import` | 403.                                                                                                                                         |

### K. Lintas Role, Responsif, Browser

| Status | ID  | Halaman / Fitur     | Yang dites → hasil yang benar                                                                                         |
| ------ | --- | ------------------- | --------------------------------------------------------------------------------------------------------------------- |
| ☐      | K1  | Sidebar per akun    | OWN semua section; KAN & RAN sesuai akses; ALD/GEP hanya Work Control; nomor section berurutan; tidak ada menu ganda. |
| ☐      | K2  | Halaman placeholder | `/dashboard/people` atau `/manajer/*` dibuka langsung tidak membingungkan pengguna (lihat bagian 9 #12).              |
| ☐      | K3  | Responsif           | HP 375px, tablet, desktop: sidebar bisa di-scroll, tabel tidak melebar keluar layar.                                  |
| ☐      | K4  | Halaman error       | 403 & 404 custom tampil; 500 hanya tampil pesan umum (bukan detail teknis).                                           |
| ☐      | K5  | Browser lain        | Safari iOS (lokasi & kamera), Firefox: absen dan form utama berjalan.                                                 |

### Kesimpulan tes manual

- Total **116** item tes (di luar persiapan). Sudah ✅: **2** (menu Export & Import, Import Work Tracker). Sisanya ☐ menunggu dijalankan.
- Item yang paling perlu perhatian karena hasilnya belum pasti dari kode saja: **C19** (tumpang tindih cuti), **C12 / E2** (foto absen butuh `storage:link`), **H2 / H11** (upload & buka file), **J2–J5, J7–J9** (Export/Import yang belum pernah diklik lewat browser).
- Hasil akhir (jumlah ✅ / ❌ dan daftar masalah) diisi setelah putaran tes selesai.

---

## 9. Kekurangan yang Ditemukan (Prototype vs Implementasi & Kesiapan Publik)

Ditandai ⚠ = sebaiknya diputuskan/dikerjakan **sebelum** dibuka ke publik. Sisanya bisa menyusul.

| #   | Temuan                                                                                     | Kenapa penting / detail                                                                                                                                                                                                  | Saran                                                                                                                                                                                                   |
| --- | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | ⚠ File kontrak karyawan, dokumen legal, dan foto absen dibuka lewat `asset('storage/...')` | Siapa pun yang tahu URL-nya bisa membuka file **tanpa login**. Nama file acak, tapi itu bukan kontrol akses.                                                                                                             | Layani lewat route ber-`auth` + cek modul (`contracts`/`legal`/`people`), simpan di disk `local` bukan `public`. Sekaligus menghilangkan ketergantungan `storage:link`.                                 |
| 2   | ⚠ Password default `password`                                                              | Dipakai semua akun demo dan Import Karyawan saat kolom password dikosongkan. Di internet publik, email + `password` mudah ditebak.                                                                                       | Ganti semua akun demo sebelum go-live; untuk import, pertimbangkan password acak yang ditampilkan sekali atau wajib ganti saat login pertama. Keputusan lama ("pakai `password`") perlu ditinjau ulang. |
| 3   | ⚠ Audit Log belum lengkap                                                                  | Baru dipakai di sebagian modul. Belum tercatat: KPI, Budget, Royalty, Kontrak, Legal, Work Tracker, Memo, Meeting, Rekrutmen (termasuk convert pelamar jadi akun), Changelog, dan **semua Export** (termasuk data gaji). | Tambahkan `AuditLog::record()` minimal untuk Export, Convert pelamar, dan modul sensitif (Kontrak, Legal, Royalty, Budget).                                                                             |
| 4   | Cuti tidak mengecek tumpang tindih tanggal                                                 | `StoreLeaveRequestRequest` hanya mengecek sisa kuota; Lembur sudah punya cek duplikat.                                                                                                                                   | Uji lewat C19; kalau terbukti, tambah validasi overlap dengan pengajuan pending/disetujui.                                                                                                              |
| 5   | Payroll hanya menghitung lembur & kekurangan jam                                           | Hari tanpa absen sama sekali tidak dipotong dan tidak tercatat sebagai "Absen (A)". Prototype punya potongan per hari absen, per menit terlambat, dan pengali lembur per jam.                                            | Konfirmasi ke Owner apakah aturan sekarang memang disengaja.                                                                                                                                            |
| 6   | Budget & Royalty berbentuk CRUD datar                                                      | Prototype punya recoupment per lagu, ledger pendapatan per kuartal, grafik, cetak, dan sinkron Google Sheet.                                                                                                             | Sudah tercatat sebagai keputusan scope; masukkan ke roadmap kalau dibutuhkan.                                                                                                                           |
| 7   | Belum dipindah dari prototype                                                              | Tema/warna per pengguna (baru ada 2 warna aksen global), editor konten landing page, Team Groups, upload foto profil karyawan.                                                                                           | Roadmap; tidak menghalangi go-live.                                                                                                                                                                     |
| 8   | Tidak ada notifikasi email                                                                 | Pesan kontak, lamaran, dan pengajuan baru hanya terlihat kalau Owner/atasan membuka dashboard. `MAIL_MAILER=log`.                                                                                                        | Isi `MAIL_*` produksi jika notifikasi dibutuhkan; kalau tidak, catat di SOP bahwa dashboard harus dicek rutin.                                                                                          |
| 9   | Form Lamar & Kontak hanya diamankan throttle                                               | Tidak ada upload CV, cek pelamar dobel, atau captcha/honeypot; risiko spam di halaman publik.                                                                                                                            | Tambah honeypot sederhana; pertimbangkan captcha bila spam masuk.                                                                                                                                       |
| 10  | Tidak ada "Lupa Password" mandiri                                                          | Reset hanya lewat Owner (Edit Karyawan → isi password baru).                                                                                                                                                             | Cukup untuk tim kecil; tuliskan di SOP.                                                                                                                                                                 |
| 11  | Nama bulan/hari tampil bahasa Inggris                                                      | `APP_LOCALE=en` sementara 42 file memakai `translatedFormat()`; teks UI lain bahasa Indonesia. Nama aplikasi juga masih `APP_NAME=Laravel`.                                                                              | `APP_LOCALE=id` (atau `Carbon::setLocale('id')`) dan `APP_NAME="WSM Office"`.                                                                                                                           |
| 12  | Halaman placeholder                                                                        | Grup `/manajer/*` (Team Overview) kosong; `/dashboard/{module}` generik menampilkan halaman "belum ada isi" bila dibuka lewat URL langsung.                                                                              | Hapus route kosong atau arahkan ke halaman yang sudah ada.                                                                                                                                              |
| 13  | Konten halaman publik hardcode di Blade                                                    | Ubah teks = ubah file & upload ulang.                                                                                                                                                                                    | Roadmap (CMS ringan).                                                                                                                                                                                   |
| 14  | Belum ada automated test                                                                   | Hanya stub bawaan Laravel.                                                                                                                                                                                               | Mulai dari alur paling kritis: login, absen, approval, payroll.                                                                                                                                         |
| 15  | Nama file model di Git tidak cocok                                                         | Git melacak `app/Models/Contactmessage.php`, sedangkan folder kerja berisi `ContactMessage.php`. Di Linux (hosting, `git clone`) class `ContactMessage` tidak akan ditemukan → form Kontak & Pesan Kontak error.         | `git mv -f app/Models/Contactmessage.php app/Models/ContactMessage.php`, lalu commit. Kalau deploy lewat upload manual dari Windows, pastikan nama di server persis `ContactMessage.php`.               |

### Keputusan tim atas temuan di atas (2026-09-19)

Prioritas saat ini: **fitur absensi dan semua pekerjaan sehari-hari** (Work Tracker, Memo, Meetings, Kalender, cuti/lembur/koreksi + persetujuan). Modul lain menyusul.

| #   | Keputusan                                                                                                                       | Catatan                                                                                         |
| --- | ------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| 1   | Setuju: file kontrak/legal/foto absen dilayani lewat route ber-login                                                            | Dikerjakan sebelum go-live                                                                      |
| 2   | Seeder demo hanya untuk lokal (data asli dibuat berbeda saat deploy); karyawan hasil import langsung diminta mengganti password | Sisa risiko: sebelum diganti, akun import masih memakai `password`                              |
| 3   | Setuju: Audit Log dilengkapi                                                                                                    | Tidak otomatis; tiap aksi harus dipasangi pencatatan satu per satu                              |
| 4   | Setuju: cek tumpang tindih tanggal cuti                                                                                         |                                                                                                 |
| 5   | Hari tanpa absen **dipotong**; payroll masih bisa dikoreksi selama belum jatuh tempo gajian                                     | Perlu: daftar hari libur, dan tombol "buka kembali" untuk payroll Final sampai ditandai Dibayar |
| 6   | Budget & Royalty ditunda; utamakan absensi dan pekerjaan harian                                                                 |                                                                                                 |
| 7   | Tema per pengguna, editor landing page, Team Groups, foto profil: **dicatat saja dulu**                                         | Roadmap                                                                                         |
| 8   | Setuju: notifikasi email menyusul                                                                                               |                                                                                                 |
| 9   | Form Lamar: bisa **upload CV** dan bisa **kirim link portofolio**                                                               | File CV harus privat (ikut temuan #1)                                                           |
| 10  | Lupa Password mandiri: dibahas lagi setelah email asli terpasang                                                                |                                                                                                 |
| 11  | Teks tampilan berbahasa Inggris; teks krusial / rawan salah paham tetap Indonesia                                               | Cakupan terjemahan masih perlu dipastikan                                                       |
| 12  | Kartu "People & Leave" dan "Recruitment" di halaman `/dashboard` mengarah ke halaman "belum dibangun"                           | Perbaikan kecil: arahkan ke `/absensi` dan `/rekrutmen/lowongan`                                |
| 13  | Teks halaman publik tetap tertulis di file (tidak diubah lewat dashboard)                                                       |                                                                                                 |
| 14  | Tes otomatis: mulai dari absensi dan pekerjaan harian                                                                           | Perlu database MySQL khusus tes (migrasi memakai perintah khusus MySQL)                         |
| 15  | Folder `.git` dihapus saat deploy                                                                                               | Cukup pastikan file di server bernama persis `ContactMessage.php`                               |

---

## 10. Reminder Sebelum Production

Urutan kerja disarankan dari atas ke bawah. Poin 1–7 **wajib**; sisanya penyempurnaan.

| #   | Yang harus dikerjakan                                                                                                                                                                                                                                                          | Alasan                                                                                                                             |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Buat `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_NAME="WSM Office"`, `APP_LOCALE=id`, `LOG_LEVEL=error`, `SESSION_SECURE_COOKIE=true`, kredensial DB hosting, `MAIL_*` bila perlu, dan `APP_KEY` baru. **Jangan upload `.env` lokal.** | `.env` yang ada berisi password DB polos, `APP_DEBUG=true`, `APP_ENV=local`; siapa pun bisa melihat detail error teknis.           |
| 2   | Aktifkan **HTTPS** di cPanel dan paksa redirect ke HTTPS.                                                                                                                                                                                                                      | Geolokasi dan kamera browser (absen) hanya berjalan di HTTPS; cookie sesi juga perlu aman.                                         |
| 3   | Build aset: `npm run build`, upload `public/build/`, dan **hapus `public/hot`** serta `public/fonts-manifest.dev.json`.                                                                                                                                                        | `public/hot` (berisi alamat dev server Vite) membuat halaman mencari CSS/JS ke `localhost:5173` sehingga tampilan rusak di server. |
| 4   | Selesaikan akses file (temuan #1): idealnya layani lewat controller; kalau tetap pakai `storage:link`, buat symlink lewat route sekali-pakai yang langsung dihapus (hosting tanpa terminal).                                                                                   | Tanpa ini upload kontrak/legal/foto tidak bisa dibuka — atau terbuka untuk publik.                                                 |
| 5   | Routing hosting: tambahkan `.htaccess` di root, atau arahkan document root ke folder `public/`.                                                                                                                                                                                | Tanpa ini domain tidak mengarah ke aplikasi dengan benar.                                                                          |
| 6   | Database: import struktur dari lokal (atau jalankan migrasi lewat route sekali-pakai), **jangan jalankan `DemoSeeder`**; buat akun Owner asli dan isi Pengaturan Kantor dengan koordinat kantor sebenarnya. Hapus semua akun `*@wsm.local`.                                    | Akun demo berpassword `password` = pintu terbuka.                                                                                  |
| 7   | Upload `vendor/` hasil `composer install --no-dev`; hapus cache lokal di `bootstrap/cache/`; pastikan `storage/` dan `bootstrap/cache/` writable (775).                                                                                                                        | Cache lokal berisi path komputer sendiri; folder tak bisa ditulis = error 500.                                                     |
| 8   | Cek PHP di hosting: versi ≥ 8.3; ekstensi `gd`, `mbstring`, `zip`, `xml`, `fileinfo`; `upload_max_filesize` & `post_max_size` ≥ 10MB.                                                                                                                                          | Laravel 13, Excel/PDF, dan upload kontrak 10MB membutuhkannya.                                                                     |
| 9   | Bersihkan paket: jangan upload `node_modules/`, `.git/`, `database/database.sqlite`, file `.env.*`.                                                                                                                                                                            | Ukuran & kebocoran riwayat kode.                                                                                                   |
| 10  | Ganti `public/robots.txt` agar `/app`, `/dashboard`, `/owner`, `/login` tidak diindeks mesin pencari.                                                                                                                                                                          | Saat ini semua boleh diindeks.                                                                                                     |
| 11  | Jadwalkan backup database manual (tidak ada cron di hosting) dan uji restore satu kali.                                                                                                                                                                                        | Data gaji, kontrak, dan absensi tidak boleh hilang.                                                                                |
| 12  | Setelah upload, lakukan smoke test singkat: login Owner → absen dari HP di kantor → upload & buka kontrak → export satu PDF → logout.                                                                                                                                          | Memastikan konfigurasi server (bukan kode) sudah benar.                                                                            |
