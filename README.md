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

Fitur baru, dikerjakan bertahap (lihat bagian 7 untuk detail lengkap). **Fondasinya (Batch 0) sudah selesai** — halaman menu "Export & Import" sudah bisa dibuka & sudah nyaring kartu sesuai akses tiap orang (sudah dites langsung lewat browser, login beneran, bukan cuma dibaca kodenya) — tapi tombol export/import di tiap kartu **masih "Segera Hadir"**, belum ada yang benar-benar jalan. Menyusul satu-per-satu di batch berikutnya.

### Yang masih harus dibereskan sebelum benar-benar go-live

Ini bukan kesalahan kode, tapi konfigurasi/kelengkapan yang memang belum disentuh:

| #   | Isu                                                                                                                                  | Kenapa penting                                                                                                                                                             |
| --- | ------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | File `.env` yang ada masih berisi setting pengembangan (password database polos, `APP_DEBUG=true`, `APP_ENV=local`)                  | Kalau diupload apa adanya, siapa pun bisa melihat detail error teknis (termasuk info sensitif) di halaman publik. **Wajib diganti** dengan `.env` produksi sebelum upload. |
| 2   | Symlink `storage:link` belum pernah dijalankan di server                                                                             | Upload file kontrak karyawan & dokumen legal (yang disimpan lewat `Storage::disk('public')`) tidak akan bisa diakses/ditampilkan tanpa ini.                                |
| 3   | Belum ada file `.htaccess` di folder paling atas (root) project                                                                      | Di hosting cPanel yang document root-nya bukan folder `public/`, tanpa file ini alamat website tidak akan mengarah ke aplikasi dengan benar.                               |
| 4   | Import massal data (CSV/XLSX) untuk Work Tracker dari prototype lama — **fondasinya sudah ada** (lihat bagian 7), tapi belum selesai | Sampai selesai, isi tugas masih harus satu-satu manual atau lewat MoM.                                                                                                     |
| 5   | Belum ada automated test sungguhan (baru file contoh bawaan Laravel)                                                                 | Tidak ada jaring pengaman otomatis kalau ada perubahan kode yang tidak sengaja merusak fitur lain.                                                                         |
| 6   | Ada file `database/database.sqlite` yang tertinggal di folder project                                                                | Tidak dipakai (database sungguhan pakai MySQL), aman dihapus, cuma bikin bingung kalau dikira itu database aktif.                                                          |

**Kesimpulan:** dari sisi fitur, aplikasi ini sudah siap dites ujung-ke-ujung oleh tim WSM. Sebelum benar-benar diakses publik di internet, 3 poin pertama pada tabel di atas **wajib** dikerjakan dulu (ganti `.env`, jalankan `storage:link`, tambah `.htaccess`); 3 poin sisanya adalah penyempurnaan yang bisa menyusul.

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

### `app/Models/` — 23 file, satu per jenis data utama

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

### `resources/views/` — 94 file Blade, dikelompokkan sama seperti Controllers

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

| Teknologi                     | Kegunaan                                                                                                  |
| ----------------------------- | --------------------------------------------------------------------------------------------------------- |
| **PHP 8.3+**                  | Bahasa pemrograman utama                                                                                  |
| **Laravel 13**                | Framework backend — routing, database, autentikasi, dll                                                   |
| **MySQL**                     | Database (dipakai di hosting produksi; bisa juga diuji pakai SQLite/MariaDB)                              |
| **Tailwind CSS v4**           | Styling tampilan, di-build lewat Vite                                                                     |
| **Alpine.js**                 | Interaktivitas ringan di sisi browser (dropdown, modal, dll) tanpa perlu framework JS berat               |
| **Vite**                      | Build tool untuk menggabungkan & mengoptimalkan CSS/JS                                                    |
| **Leaflet**                   | Peta interaktif untuk validasi lokasi absensi & pengaturan titik kantor                                   |
| **SweetAlert2**               | Notifikasi & dialog konfirmasi yang lebih rapi dari `alert()` bawaan browser                              |
| **doctrine/dbal**             | Dibutuhkan Laravel untuk mengubah struktur kolom yang sudah ada (dipakai di beberapa migration)           |
| **maatwebsite/excel**         | Export & import Excel (fitur baru, lihat bagian 7) — **wajib `composer require` manual**, belum terpasang |
| **barryvdh/laravel-dompdf**   | Export PDF (fitur baru, lihat bagian 7) — **wajib `composer require` manual**, belum terpasang            |
| **cPanel Hosting (Rumahweb)** | Target hosting produksi — tanpa akses terminal/SSH, jadi deploy manual lewat upload file                  |

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
| **Batch 2 — Export PDF**   | Payroll (slip gaji), Rekap Absensi (per-karyawan), Meetings/MoM (notulen)                                                                                                     | ⏳ Belum dikerjakan                                                                                     |
| **Batch 3 — Import**       | Work Tracker (prioritas — nutup gap prototype lama), menyusul Manajemen Karyawan/KPI/Project Budgeting                                                                        | ⏳ Belum dikerjakan                                                                                     |

Sampai Batch 2/3 selesai, kartu-kartu terkait di halaman "Export & Import" masih menampilkan badge **"Segera Hadir"** untuk bagian yang belum jalan — ini sengaja, biar orang tetap tahu fitur apa saja yang direncanakan. Kartu **Rekap Absensi** contohnya: tombol **Export Excel** sudah aktif (Batch 1), tapi **Export PDF**-nya masih "Segera Hadir" (nyusul Batch 2) — 1 kartu bisa punya 2 status berbeda tergantung format.

### Apa yang sudah dites, dan apa yang belum bisa dites

**Sudah dites langsung lewat browser (login beneran, bukan cuma baca kode):**

- Halaman menu "Export & Import" — login sebagai **Owner** (13 kartu muncul semua) dan sebagai **Gepeng**, karyawan yang cuma punya akses modul `work` (cuma **2 kartu** yang muncul: Work Tracker & Meetings/MoM) — membuktikan penyaringan akses per-kartu jalan.
- Badge per-format (Excel aktif vs PDF "Segera Hadir" vs Import "Segera Hadir") dicek satu-satu, jumlahnya cocok persis dengan yang seharusnya untuk ke-13 kartu.

**BELUM bisa dites langsung** (halaman preview tabel & tombol Download Excel di 11 modul Batch 1): kode-nya butuh package `maatwebsite/excel` yang **belum ter-install** (lihat bagian selanjutnya) — instalasinya butuh akses ke `packagist.org`, yang tidak bisa diakses dari lingkungan kerja saya (sudah dicoba langsung `composer require`, hasilnya diblok/HTTP 403). Jadi bagian ini sudah saya periksa teliti secara manual (nama kolom & nama relasi tiap model dicocokkan satu-satu ke kode aslinya, bukan ditebak) dan lolos pengecekan sintaks PHP (`php -l`) di semua file — tapi **belum dites "beneran jalan end-to-end"** sampai package-nya di-install dan dicoba sendiri. Tolong dicoba manual sekali setelah `composer require` di bawah, kalau ada error tinggal kabari saya.

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

### Yang perlu dijalankan manual sebelum Batch 1 bisa benar-benar dipakai

`composer.json` sudah ditambahkan 2 package, **tapi package-nya sendiri belum ter-install** — jalankan ini dulu di komputer lokal (sesuai kebiasaan deploy project ini: composer di lokal, lalu folder `vendor/` yang diupload ke hosting), **lalu coba buka salah satu halaman preview** (mis. Dashboard → Export & Import → KPI → Export Excel) buat mastiin semuanya jalan mulus:

```
composer require maatwebsite/excel barryvdh/laravel-dompdf
```
