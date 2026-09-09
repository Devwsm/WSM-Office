# WSM Office System

Sistem internal untuk **Whisnu Santika Music (WSM)** — absensi, cuti/izin,
data karyawan, rekrutmen, dan modul kerja tim, semuanya di 1 tempat.
Dokumen ini isinya status progres (buat dipantau) + panduan buat yang
lanjut ngerjain.

## 🎯 Acuan Utama Project — WAJIB DIIKUTI

**`WOS_2_0_App_v32.zip` adalah source of truth untuk sistem internal WSM.**
WSM-Office adalah implementasi Laravel dari prototype tersebut. Target akhir
adalah **seluruh fitur, data, halaman, layout, placement, UI, UX, permission,
dan flow prototype berhasil dipindahkan ke web resmi**.

Aturan anti-drift:

- Prototype **v32**, bukan v13/v18 atau roadmap lama, menjadi acuan utama.
- Sebelum membuat atau mengubah fitur, cari padanannya di prototype v32.
- Cocokkan **data, halaman, komponen, urutan informasi, placement, UI, UX,
  flow, role, dan hak akses** sebelum coding.
- Jangan menyederhanakan atau menghilangkan fitur prototype hanya karena lebih
  mudah dibuat di Laravel.
- Perbedaan hanya boleh dilakukan untuk **keamanan, arsitektur
  Laravel/database, responsive behavior, atau kebutuhan resmi WSM di luar
  prototype**.
- Setiap perbedaan yang disengaja wajib dicatat di README beserta alasannya.
- Migration/model saja **bukan** berarti fitur selesai.

### Definisi "Selesai"

Sebuah fitur/fase hanya boleh diberi `[x]` jika **database/data, logic, UI,
UX, placement, permission, validation, dan flow end-to-end** sudah tersedia,
berjalan tanpa error, dan hasilnya sudah dibandingkan kembali dengan
prototype v32.

> **Prinsip utama:** jangan membuat WSM-Office semakin berbeda dari prototype.
> Jika ragu, **ikuti prototype v32 terlebih dahulu**, lalu dokumentasikan
> alasan jika implementasi Laravel memang harus berbeda.

## ✅ Progres Sekarang

**Status saat ini (2026-09-08): sistem inti sudah berjalan dan Fase 6–8
sudah divalidasi end-to-end. Fokus berikutnya adalah memindahkan seluruh
fitur prototype v32 yang belum ada ke WSM-Office tanpa mengubah scope secara
sembarangan.**

### Sudah berjalan

- [x] Website publik (Beranda, Tentang Kami, Layanan, Kontak)
- [x] Karir + form lamaran kerja publik
- [x] Login & data karyawan
- [x] Struktur atasan-bawahan & organisasi
- [x] Rekrutmen & pipeline pelamar
- [x] Absensi dasar: Kantor/WFH, lokasi, selfie opsional, riwayat & rekap
- [x] Izin/Cuti & approval
- [x] Dashboard Access per-orang
- [x] Memo/MoM: CRUD, pin, baca/sembunyikan, reply thread
- [x] Home personalization: jabatan/divisi + Cuti Tim Bulan Ini
- [x] Profile & ganti password
- [x] Lock Dashboard
- [x] Halaman error custom
- [x] **Fase 7 Absensi Lanjutan:** WFO 09:30–20:00, auto-close, Lembur
      request/approval, mode Lapangan/Gigs multi-sesi, shortage per blok
      60 menit, dan pengaturan kantor dari UI Owner/HR.
- [x] Data Layer Fase 9–16 sudah disiapkan sebagai fondasi database/model.

### Sudah divalidasi end-to-end

- [x] **Fase 6 — Dashboard Access & MoM/Memo:** role Owner, Manage, View,
      dan tanpa akses sudah dicek termasuk pembatasan URL langsung.
- [x] **Fase 8 — Memo Forum & Home Personalization:** read/unread,
      hide/show, shared reply, badge management, job title/divisi, dan
      banner cuti tim sudah dicek.
- [x] **Fase 7 — Absensi Lanjutan:** Lapangan/Gigs, auto-close, Lembur,
      shortage, dan pengaturan kantor sudah dicek.
- [ ] Migration + seeder seluruh fondasi Fase 9–16 perlu tetap divalidasi
      di environment development setelah perubahan terakhir.

### Belum dibangun sebagai fitur user-facing

- [ ] **Fase 9:** Projects, Work Tracker board, Timeline Calendar, dan MoM
      sebagai fitur Work Control lanjutan.
- [ ] **Fase 10:** KPI & Performance.
- [ ] **Fase 11:** Kontrak Karyawan.
- [ ] **Fase 12:** Payroll, termasuk Gaji Pokok, Target Jam/Hari, Flat
      Overtime Rate, perhitungan lembur, dan dampak shortage.
- [ ] **Fase 13:** Project Budgeting & Royalty.
- [ ] **Fase 14:** Legal.
- [ ] **Fase 15:** IT — Audit Log & System Change Log.
- [ ] **Fase 16:** CEO Dashboard IA & Settings.
- [ ] **Fase 17:** CMS Landing Page.
- [ ] **Fase 18:** Security, testing final, staging, deployment, backup &
      monitoring.

> `~` berarti **fondasi database/model sudah dibuat**, bukan berarti modul
> sudah bisa dipakai dari UI.

> **Catatan penting Fase 7 → Fase 12:** Fase 7 tidak menghitung nominal
> rupiah lembur atau potongan shortage. Fase 7 hanya menghasilkan data
> kehadiran, lembur, dan shortage yang nantinya dipakai Payroll Fase 12.
> Field **Gaji Pokok, Target Jam/Hari, dan Flat Overtime Rate** juga sengaja
> dikerjakan di Fase 12, bukan di Fase 7.

## 👉 Langkah Selanjutnya

Urutan kerja terbaru:

1. **Step 0 — Foundation: SELESAI.** Composer/PHP, migration dasar,
   struktur project, dan pemeriksaan fondasi yang sebelumnya menjadi
   blocker sudah dicek.
2. **Fase 6–8: SELESAI & sudah divalidasi end-to-end.** Tidak perlu diulang
   sebagai blocker development; lakukan regression test bila ada perubahan
   yang menyentuh modul tersebut.
3. **Prioritas berikutnya: Fase 9 — Work Control Lanjutan**, dengan fokus
   utama pada **App Mode yang dipakai semua karyawan** sebelum memperdalam
   dashboard Owner.
4. Setelah Fase 9 stabil, lanjut **Fase 10 → 11 → 12 → 13 → 14 → 15 → 16 →
   17** sesuai urutan roadmap aktif.
5. Setiap fase wajib diselesaikan sebagai fitur utuh: database + logic + UI
    - UX + placement + hak akses + validasi + responsive + flow end-to-end +
      perbandingan kembali dengan prototype v32.
6. Terakhir kerjakan **Fase 18 — Security, Testing, Staging, Deployment,
   Backup & Monitoring**. Sistem baru dianggap benar-benar selesai setelah
   tahap ini lolos.

**Definisi "selesai" untuk sebuah fase:** bukan sekadar migration/model
sudah ada. Fitur dianggap selesai kalau pengguna bisa menjalankan flow dari
awal sampai akhir, hak akses benar, validasi benar, tidak ada error, dan
sudah diuji di environment project yang sebenarnya.

### 🎯 Prioritas Implementasi Sampai Deployment

Karena **App Mode dapat diakses seluruh karyawan**, penyelesaian sistem
internal diprioritaskan dari pengalaman pengguna di App Mode terlebih dahulu.

1. **Fase 9 — Work Control Lanjutan**
    - Samakan Home App dengan prototype v32: identitas karyawan, status
      absensi, banner cuti, milestone, My KPI, My Work Tracker, Shared
      Calendar, Latest Attendance, dan Work Dashboard sesuai role.
    - Bangun Projects, Work Tracker board, Timeline Calendar, dan MoM.
    - Pastikan alur dari App Mode → fitur kerja → kembali ke App Mode berjalan
      utuh di desktop, tablet, dan mobile.
2. **Fase 10 — KPI & Performance**
    - Bangun tampilan KPI, target/current, bobot, periode, status, dan
      persentase pencapaian sesuai prototype v32.
3. **Fase 11 — Kontrak Karyawan**
    - Bangun monitoring kontrak, periode, file, catatan, dan status jatuh
      tempo sesuai hak akses.
4. **Fase 12 — Payroll**
    - Integrasikan data Fase 7: absensi, lembur, shortage, lalu hitung payroll
      termasuk Gaji Pokok, Target Jam/Hari, Flat Overtime Rate, potongan, dan
      adjustment.
5. **Fase 13 — Project Budgeting & Royalty**
    - Selesaikan budget vs actual, variance, royalty, share, recoupment, dan
      status pembayaran.
6. **Fase 14 — Legal**
    - Bangun Kontrak Album dan Perjanjian Royalti.
7. **Fase 15 — IT**
    - Bangun Audit Log dan System Change Log.
8. **Fase 16 — CEO Dashboard & Settings**
    - Samakan Information Architecture Owner dengan prototype v32: 7 grup,
      badge LIMITED, accent color, dan sidebar yang dapat scroll independen.
9. **Fase 17 — CMS Landing Page**
    - Owner dapat mengubah konten website publik tanpa mengubah kode.
10. **Fase 18 — Production Readiness & Deployment**
    - Feature freeze → audit parity v32 → E2E semua role → responsive test
      → security audit → automated/regression test → staging → UAT → fix &
      regression → backup & monitoring → production deployment → smoke test
      setelah rilis.

**Aturan kerja tiap fase:** `Prototype v32 → pahami flow → cek data →
implement logic → UI/UX & placement → permission → responsive → E2E →
bandingkan kembali dengan prototype → PASS.`

## 🚀 Cara Menjalankan (Setup)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=OfficeSettingSeeder   # WAJIB — isi office_settings (lokasi kantor, radius, jam kerja)
php artisan db:seed --class=DemoSeeder   # opsional: isi 1 Owner, 1 Manajer, 1 HRD, 2 Karyawan buat testing
php artisan storage:link   # wajib buat Fase 4 — foto selfie absen disimpan di storage/app/public
npm install
npm run dev   # atau: npm run build
```

> ⚠️ **Sebelum absen bisa dipakai beneran**, cek dulu
> `database/seeders/OfficeSettingSeeder.php` — isinya SEKARANG sudah
> `office_name: 'WSM Office'`, alamat Jl. Raya Tapos No.43, Depok, dan
> koordinat `-6.4069, 106.8880` (BUKAN placeholder titik Monas lagi
> seperti catatan versi sebelumnya — komentar itu sudah basi, sudah
> dikoreksi di sini). Yang perlu dipastikan sebelum dipakai beneran:
> konfirmasi apakah alamat & koordinat itu memang lokasi kantor WSM
> yang sesungguhnya (bukan cuma placeholder baru yang belum
> diverifikasi) — kalau belum, ganti `latitude`, `longitude`, `address`,
> `office_name` sesuai lokasi asli, lalu jalankan ulang
> `php artisan db:seed --class=OfficeSettingSeeder`.
> `radius_meters`/`work_start_time`/`late_tolerance_minutes` juga bisa
> disesuaikan di file yang sama.

Akun demo dari `DemoSeeder` (password semua `password`, **ganti sebelum
pakai beneran**):

| Role     | Email              | Nama           |
| -------- | ------------------ | -------------- |
| Owner    | `owner@wsm.local`  | Whisnu Santika |
| Manajer  | `kanaya@wsm.local` | Kanaya         |
| HRD      | `rania@wsm.local`  | Rania          |
| Karyawan | `aldora@wsm.local` | Aldora         |
| Karyawan | `gepeng@wsm.local` | Gepeng         |

Local dev pakai Laragon (Windows) + SQLite. Sebelum deploy ke cPanel,
jalankan `npm run build` lalu upload file yang berubah + folder
`public/build/` — jangan pernah sentuh database live secara langsung.

## 📖 Detail Lengkap (Arsip Teknis)

> Bagian di bawah ini buat yang lanjut ngerjain/develop —
> penjelasan teknis kenapa tiap keputusan diambil, detail per fase,
> dan checklist testing lengkap. **Gak perlu dibaca kalau cuma mau
> pantau progres** — cukup lihat bagian ✅ Progres Sekarang di paling
> atas.

### Status Pengerjaan Terbaru (2026-09-08)

- **Step 0 — Foundation:** selesai dan sudah dicek.
- **Fase 6–8:** fitur sudah ada dan sudah divalidasi end-to-end.
- **Fase 9:** menjadi pekerjaan aktif berikutnya. Data layer sudah tersedia,
  tetapi UI, controller, permission detail, responsive behavior, dan flow
  user-facing Work Control belum selesai.
- **Fase 9–16:** fondasi database/model sudah disiapkan, tetapi UI dan
  flow user-facing belum dibangun.
- **Fase 17–18:** belum dikerjakan.

> Catatan audit lama di bawah tetap dipertahankan sebagai riwayat. Jika
> ada pernyataan lama yang bertentangan dengan status di bagian atas,
> gunakan **Status Pengerjaan Terbaru** dan **Progres Sekarang** sebagai
> acuan.

### Riwayat Perubahan Detail — Arsip

> Bagian ini adalah **catatan sejarah development**, bukan source of truth
> untuk scope atau status sekarang. Beberapa catatan lama masih menyebut
> prototype v13/v18 atau status sebelum fase berikutnya dikerjakan. Jika ada
> konflik dengan bagian atas README atau prototype v32, **abaikan catatan
> historis tersebut dan ikuti v32 + status aktif di atas**.

> Status saat ini: **Fase 0 (Fondasi) selesai**, **Fase 1 (Landing Page &
> Company Profile) selesai** — Beranda, Tentang Kami, Layanan, Karir,
> Kontak sudah ada route + view (konten masih hardcode, form kontak belum
> simpan ke DB). **Fase 2 (Manajemen Karyawan & Struktur Organisasi)
> selesai** — CRUD karyawan/manajer/HRD lengkap dengan soft delete
> ("nonaktifkan"/aktifkan lagi), assign atasan (`manager_id`), filter +
> search, dan halaman org-chart. Role `hrd` sudah resmi masuk enum
> `users.role` (dimajukan dari rencana awal Fase 3). **Fase 3 (Rekrutmen)
> selesai** — HRD & Owner kelola lowongan (`job_openings`, draft/
> published/closed, slug otomatis buat link publik), halaman Karir publik
> sekarang nampilin lowongan asli + halaman detail & form lamaran
> (`job_applications`, masih teks: nama/email/telepon/pesan — upload CV
> menyusul), panel Pelamar dengan pipeline status (Baru → Ditinjau →
> Interview → Ditawari → Diterima/Ditolak) + catatan internal, dan tombol
> "Terima & Buatkan Akun" buat convert pelamar diterima jadi akun
> karyawan asli. HRD sekarang landing ke `/rekrutmen/pelamar` setelah
> login (bukan `/app/home` lagi). **Fase 4 (Absensi) selesai** — absen
> masuk/pulang buat semua role internal (Karyawan/Manajer/HRD/Owner
> sama-sama absen), mode Kantor (radius dicek) atau WFH (radius
> di-skip), "Test Lokasi" + modal konfirmasi nampilin mini map (Leaflet +
> OpenStreetMap, tanpa API key) berisi jarak & radius sebelum absen
> beneran tersimpan, foto selfie opsional langsung dari kamera browser
> (dikompres di `<canvas>`, bukan upload galeri), riwayat absensi
> bulanan per karyawan, dan halaman rekap buat Manajer (bawahan turunan)
> /HRD/Owner (semua karyawan) lengkap dengan link Google Maps + thumbnail
> foto per absen. Titik lokasi kantor & aturan jam kerja ada di tabel
> singleton `office_settings` (diisi placeholder lewat
> `OfficeSettingSeeder` — **wajib diganti ke koordinat kantor asli**
> sebelum dipakai beneran). Koreksi/approval absen manual SENGAJA belum
> ada — nyambung ke Fase 5. **Fase 5 (Izin/Cuti & Approval) selesai** —
> karyawan ajukan Cuti Tahunan/Izin Sakit/Izin Pribadi/Lainnya (cuma
> tanggal hari ini & ke depan, gak bisa backdate), diputuskan atasan
> langsung (`manager_id`) lewat Manajer, dengan Owner bisa lihat &
> memutuskan SEMUA pengajuan kapan aja (bukan cuma fallback — beda
> sengaja dari scope rekap absensi Fase 4 yang pakai bawahan turunan,
> approval Fase 5 cuma bawahan LANGSUNG). HRD sengaja TIDAK ikut
> approval. Saldo cuti tahunan (`users.annual_leave_entitlement`)
> otomatis kepotong pas disetujui, dihitung hari kerja Senin-Jumat aja,
> tombol ajukan otomatis disable kalau sisa saldo gak cukup. Ditolak
> wajib alasan (karyawan boleh ajukan ulang), dibatalkan (oleh karyawan
> sendiri ATAU Manajer/Owner) juga wajib alasan — semua aksi berdampak
> (setuju/tolak/batalkan/koreksi) dipasangi konfirmasi `data-confirm`.
> Hari yang izin/cutinya disetujui bikin tombol absen ilang dari Home
> (dicek ganda di server, bukan cuma sembunyi UI). Koreksi absen manual
> yang ditunda dari Fase 4 juga masuk sini — Manajer/Owner cuma bisa
> edit JAM (bukan override status jadi izin/cuti manual), tersimpan jam
> asli sebelum dikoreksi + siapa/kapan yang koreksi, dan karyawan bisa
> lihat catatan itu di riwayatnya sendiri (transparan). Dashboard Owner
> kartu "Pengajuan Pending" gabungan izin/cuti pending + absen yang
> butuh perhatian (lupa checkout). Fase 6 ke atas (MoM & Memos, dst.)
> belum mulai.
>
> **Perbaikan pasca-Fase 4:** `config/app.php` timezone dibetulin dari
> default Laravel (`UTC`) ke `Asia/Jakarta` — sebelum ini semua jam
> (absen, `now()`, dsb.) kegeser 7 jam ke belakang dari WIB. **Kalau
> kamu sempat testing sebelum fix ini, hapus data absen yang kejadian pas
> masih `UTC`** (`php artisan migrate:fresh --seed` paling gampang) biar
> nggak campur data jam yang salah. Juga dibetulin warning Intelephense
> "Undefined method 'isHrd()'" di `RecapController` (cuma soal tipe data
> buat editor, bukan bug jalan/nggaknya kode).
>
> **Update tambahan (di luar nomor fase, cross-cutting):** semua alert,
> validasi, dan konfirmasi aksi destruktif (keluar akun, nonaktifkan/
> aktifkan karyawan, buatkan akun dari pelamar) sudah pindah dari
> `alert()`/`confirm()` bawaan browser ke SweetAlert2 lewat
> `resources/js/alerts.js` — lihat bagian
> [Alert & Konfirmasi (SweetAlert)](#alert--konfirmasi-sweetalert).
> Halaman error custom (`404`, `403`, `500`, `503`) juga sudah ada,
> termasuk `503` yang otomatis beda tampilan saat `php artisan down`
> (maintenance) vs gangguan layanan biasa — lihat
> [Halaman Error Custom](#halaman-error-custom).
>
> **Perbaikan gap navigasi (Manajer/HRD/Owner):** sebelum ini, Manajer
> landing di app-mobile (`employee.home`, bottom-nav) tapi fitur
> rekap+koreksi absensi bawahan (`attendance.recap.*`) dan approval
> izin/cuti (`approval.leave.*`) cuma ada link-nya di sidebar dashboard
> (`layouts.app`) — gak ada penghubung dari app-mobile ke situ. Sekarang
> header app-mobile dapat 1 tombol "Kelola Tim" (bukan tab navbar
> terpisah per fitur — sengaja disamakan sama pola prototype yang taruh
> 1 tombol "Role Dashboard" di halaman/header), muncul buat
> Manajer/Owner/HRD, landing di `attendance.recap.index` (satu-satunya
> route yang dibolehkan buat ketiga role itu — dari situ sidebar
> `layouts.app` nampilin link lain sesuai role, mis. Persetujuan buat
> Manajer/Owner). Sebaliknya, sidebar dashboard sekarang juga dapat link
> "← App Saya" balik ke `employee.home`, buat Manajer, HRD, **dan
> Owner** (Owner tetap butuh ini karena Absensi/Cuti Fase 4–5 berlaku
> buat semua role internal termasuk Owner, bukan cuma karena dia landing
> di dashboard). Ini perbaikan cepat dengan role-based yang sudah ada —
> sistem akses per-modul yang lebih fleksibel (lihat
> [Peta Fase 6–12](#peta-fase-6–12-belum-mulai-baru-garis-besar))
> menyusul di Fase 6, belum dikerjakan di sini.
>
> **Audit posisi UI/UX vs prototype v18 (2026-09-06):** dibandingin
> struktur kode `WSM-Office` sama `WOS_2_0_App_v18` (standalone HTML)
> secara menyeluruh — lihat tabel gap arsitektur di bagian
> [Rombak Rencana](#-rombak-rencana-acuan-naik-ke-prototype-v18-2026-09-06)
> di bawah untuk temuan besar (Memo Forum, sidebar 7 grup, dst, semua
> sudah masuk roadmap Fase 8/16). Dari audit itu ada 2 kategori
> tambahan:
>
> 1. **Sudah dibetulin sekarang** — tab "Profile" di bottom-nav
>    app-mobile sebelumnya `href="#"` (`TODO Fase 1`), sekarang halaman
>    beneran (`Employee\ProfileController` + `employee/profile.blade.php`):
>    kartu identitas (nama/role/jabatan/divisi/atasan/sisa cuti) + form
>    ganti password (`UpdatePasswordRequest`, cek `current_password`
>    lewat `Hash::check` manual). Header app-mobile juga dapat avatar
>    inisial nama (link ke Profile) di ujung kanan, biar posisinya
>    sejajar sama avatar di `.mobile-top` prototype. `ProfileController`
>    juga langsung dikasih `/** @var User $me */` di `Auth::user()`
>    (pola yang sama kayak fix Intelephense `isHrd()` di
>    `RecapController` pas Fase 4) — bukan nunggu ketemu warning dulu.
> 2. **SENGAJA belum dibetulin, dicatat aja dulu:**
>     - Prototype punya icon "⋮" (account switcher, buat gonta-ganti akun
>       karyawan di 1 device) & "◌" (notifikasi) di header app-mobile —
>       dua-duanya **cuma dekorasi di prototype, gak ada `onclick`/fungsi
>       beneran**, jadi TIDAK direplikasi di sini. "⋮" juga gak relevan:
>       sistem ini pakai email+password per akun (bukan pilih-employee
>       dari 1 device kayak prototype), jadi konsepnya beda.
>     - **"Lock Dashboard"** (tombol merah di footer sidebar Owner
>       prototype, quick-lock terpisah dari logout penuh) — **temuan
>       baru, belum ada di roadmap Fase 7–18 manapun.** Perlu keputusan
>       dulu sebelum dikerjain: pakai PIN terpisah atau password akun
>       yang sama, timeout otomatis atau manual doang, berlaku cuma
>       Owner atau semua yang masuk `layouts.app` (Manajer/HRD juga).
>       Taruh sebagai kandidat Fase 16 (bareng CEO Dashboard IA
>       Restructure) atau fase tersendiri — belum diputusin.
>     - Split tombol "Kelola Tim" (role-based) + "Dashboard"
>       (dashboard_access-based) di header, dan posisi "← App Saya" di
>       dalam nav sidebar (bukan tombol header/footer terpisah kayak
>       "Preview Employee"/"← Employee Dashboard" di prototype) — ini
>       **deviasi disengaja**, bukan gap yang perlu dibetulin (sudah
>       dijelasin di entri "Perbaikan gap navigasi" di atas).
>
> **Update (2026-09-06, lanjutan):** halaman Profile & avatar di atas
> sudah dites manual — **aman, jalan sesuai spesifikasi**. Arahan buat
> semua pekerjaan lanjutan (Fase 7 ke atas & backlog gap UI/UX):
> **dibuat semirip mungkin sama prototype v18**, pembeda yang disengaja
> **cuma landing page publik + rekrutmen** (Fase 0–3, sudah ditulis di
> [Rombak Rencana](#-rombak-rencana-acuan-naik-ke-prototype-v18-2026-09-06)
> di bawah kalau perlu dicek ulang) — di luar itu, kalau prototype
> punya suatu perilaku/posisi UI dan belum ada alasan teknis kuat buat
> beda, ikuti prototype, bukan diadaptasi seenaknya.
>
> Prinsip itu langsung dipakai buat jawab pertanyaan "Lock Dashboard"
> yang kemarin masih terbuka — dibongkar isi `lockOwner()` &
> `openOwnerLogin()` di kode prototype v18:
>
> ```js
> function lockOwner() {
>     sessionStorage.removeItem(OWNER_SESSION_KEY);
>     openOwnerLogin();
> }
> ```
>
> Ternyata mekanismenya simpel: **1 password manajemen yang SAMA buat
> semua yang masuk area CEO Dashboard** (bukan PIN per-orang), disimpan
> client-side (`sessionStorage`), gak ada timeout otomatis (murni
> tombol manual), dan ada tombol "Kembali" balik ke pemilihan akun
> (`showAccess()`). Prototype bahkan hardcode "Password awal: **123**,
> segera ganti di Settings → Security" sebagai placeholder demo.
>
> **Ini TIDAK bisa ditransplant mentah-mentah** ke web resmi: sistem
> sekarang per-user (email+password masing-masing akun, `Auth::attempt`
> server-side) — bikin 1 password manajemen yang dibagi rame-rame
> (apalagi disimpan di `sessionStorage`, bisa dibaca lewat devtools)
> justru **mundur dari sisi keamanan** dibanding yang udah ada, bukan
> "menyamai prototype". Adaptasi yang tetap pegang SEMANGAT prototype
> (quick-lock terpisah dari logout, manual doang gak ada timeout,
> ada tombol balik) tapi konsisten sama arsitektur auth yang udah ada:
>
> - Password buat unlock = **password akun user sendiri** (bukan 1
>   secret bersama) — dicek server-side sama pola `Hash::check` yang
>   udah dipakai `UpdatePasswordRequest`.
> - Berlaku buat **siapa aja yang masuk `layouts.app`** (Owner, Manajer,
>   HRD) — bukan cuma Owner kayak prototype, karena di web resmi
>   ketiganya sama-sama lewat sidebar itu (prototype cuma punya 1
>   role "Management" di dashboard-nya, web resmi pecah jadi 3 role).
> - Session-based (`session('dashboard_locked')`), dicek middleware
>   sebelum masuk grup route `layouts.app` — bukan `sessionStorage`
>   client-side yang bisa diakalin dari browser.
> - Tombol "Kunci Dashboard" nempatin posisi yang sama kayak prototype
>   (footer sidebar, di atas kartu profil + "Keluar").
> - Tombol "Kembali"/batal di layar unlock → balik ke halaman
>   `employee.home` (padanan "balik ke account picker" versi web resmi
>   yang per-user), bukan logout paksa.
>
> Spek di atas dianggap **final, siap dikerjain** — gak perlu rapat
> lagi soal ini, tinggal dieksekusi kalau ada waktu (lihat
> [Langkah Selanjutnya](#langkah-selanjutnya)).
>
> **Update (2026-09-06, eksekusi):** Lock Dashboard **sudah dikerjain**
> persis sesuai spek di atas:
>
> - `App\Http\Middleware\EnsureDashboardUnlocked` (alias
>   `dashboard.unlocked`) — dipasang ke SEMUA grup route yang nempatin
>   user di `layouts.app`: `manajer.*`, `owner.*`, `recruitment.*`,
>   `attendance.recap.*`, `approval.leave.*`, `approval.overtime.*`,
>   `dashboard.*`. SENGAJA tidak dipasang ke grup `employee.*`
>   (app-mobile) dan ke grup `dashboard.lock.*` sendiri (biar gak
>   infinite redirect pas lagi kekunci).
> - `App\Http\Controllers\Dashboard\DashboardLockController` — 4 aksi:
>   `lock` (POST, dari tombol sidebar), `show` (GET, layar unlock),
>   `unlock` (POST, cek password akun sendiri lewat
>   `UnlockDashboardRequest`), `cancel` (POST, tombol "Kembali" →
>   balik ke `employee.home`, BUKAN logout paksa).
> - `resources/views/dashboard/locked.blade.php` — layar unlock,
>   gaya disamain sama `auth/login.blade.php`.
> - Tombol "🔒 Kunci Dashboard" nempatin posisi sama kayak prototype:
>   footer sidebar `layouts.app`, di atas kartu profil + tombol
>   Keluar, ada konfirmasi SweetAlert kayak logout.
>
> **Belum dites** (sandbox nulis kode ini gak ada PHP/composer buat
> jalanin server) — checklist lengkap di
> [Langkah Selanjutnya](#langkah-selanjutnya) poin 11.
>
> **Update (2026-09-07, Fase 8 — Memo Forum & Home Personalization):**
> **sudah dikerjain semua**, mengikuti fungsi FINAL prototype v18
> (`memoThreadForV18`/`employeeMemoMarkup`/`v18OwnerNav`) — sempat ada
> fungsi lebih lama (`memoReplies`) yang keliatan mirip tapi ternyata
> beda konsep (thread privat per-karyawan, bukan dibagi bareng), sudah
> dites cek fungsi mana yang beneran dipakai di render terakhir sebelum
> mulai nulis kode.
>
> - **Migration baru**: `memo_reads` (baca/sembunyi per-user per-memo,
>   pola "gak ada baris = default", sama kayak `dashboard_access`) &
>   `memo_thread_messages` (1 thread FLAT dibagi bareng semua yang bisa
>   lihat memo itu — BUKAN channel privat per-karyawan).
> - **Model baru**: `MemoRead`, `MemoThreadMessage`. `Memo` nambah
>   relasi `reads()`/`threadMessages()` + helper `isReadBy()`/
>   `isHiddenBy()`.
> - **Controller baru**: `Employee\MemoInteractionController`
>   (toggleRead/toggleHidden/reply, dari kartu Home — SEMUA role
>   internal, sama kayak `$memos` di HomeController yang emang gak
>   dicek dashboard_access). `Dashboard\Work\MemoController` nambah
>   `reply()` (sisi manajemen, butuh `module:work,manage`) + auto-mark
>   `read_by_management_at` pas `index()` dibuka.
> - **View baru**: `memo/_thread.blade.php` — partial reusable, dipakai
>   di Home (karyawan) & `/dashboard/work` (manajemen) lewat variabel
>   `$replyRoute`, biar gak dobel kode buat 2 konteks yang beda action
>   URL-nya doang.
> - `employee/home.blade.php`: kartu "Info dari Owner" sekarang
>   interaktif penuh — chip READ/UNREAD, tombol tandai-baca +
>   sembunyikan, thread + form reply, toggle "N disembunyikan" (Alpine,
>   client-side, semua memo udah di-render duluan di DOM — wajar buat
>   volume kecil kayak sekarang, lihat catatan di kode kalau nanti
>   perlu diubah jadi request terpisah). Job title/divisi di bawah
>   tanggal, banner "Cuti Tim Bulan Ini" (`cuti_tahunan` &
>   `izin_pribadi` doang, `izin_sakit` sengaja gak diumumin — privat).
> - `DashboardAccess::MODULES` nambah field `icon` buat SEMUA 7 modul
>   (bukan cuma Memo/Work) — dipakai di `layouts/app.blade.php`.
> - **1 deviasi disengaja dari prototype**: badge unread di prototype
>   itung SEMUA pesan karyawan dari awal waktu dan **gak pernah
>   reset/berkurang** (`unreadThreads=(state.memoThreads||[]).
filter(x=>x.authorType==='employee').length` — dihitung ulang tiap
>   render, gak ada flag "udah dibaca" sama sekali di struktur data
>   `memoThreads`). Itu jelas bukan UX yang benar buat sesuatu yang
>   dilabelin "unread" — masuk kategori "berantakan", jadi diadaptasi:
>   badge di sini itung `read_by_management_at IS NULL`, ke-reset
>   begitu ada manage-level user buka `/dashboard/work` (semua thread
>   emang udah kelihatan penuh di situ).
> - `DemoSeeder` ditambah 1 contoh reply (Aldora) + 1 contoh status
>   baca (Gepeng) + 1 contoh cuti bulan berjalan (Gepeng, buat demo
>   banner) — biar Fase 8 langsung kelihatan hasilnya abis
>   `db:seed --class=DemoSeeder`, sama pola kayak Fase 6a/6b.
>
> **Migration WAJIB dijalanin dulu** sebelum dites (`php artisan
migrate`) — 2 tabel baru di atas belum ada di database manapun.
> **Belum dites end-to-end** (sandbox nulis kode ini gak ada PHP) —
> checklist lengkap di [Langkah Selanjutnya](#langkah-selanjutnya)
> poin 12.

**Update (2026-09-07, Data Layer Fase 9–16):** fondasi database dan Eloquent
Model untuk roadmap Fase 9 sampai Fase 16 sudah disiapkan lebih awal agar
UI/Controller berikutnya punya struktur data yang jelas. Ini **belum berarti
Fase 9–16 selesai secara fitur** — yang selesai pada update ini adalah layer
migration + model/relation dan penyesuaian data pendukung.

- **Fase 9 — Work Control:** `projects`, `work_items`, `meetings`,
  `meeting_attendees`, `meeting_action_items`, serta relasi
  `meeting_action_item_id` pada `work_items`. `Project` terhubung ke lead,
  creator, work items, meetings, dan budgets. `WorkItem` terhubung ke
  project, PIC, creator, dan meeting action item.
- **Fase 10 — KPI:** tabel `kpis` + model `Kpi`, termasuk target/current,
  weight, status, periode, relasi employee/creator, dan helper
  `achievementPct()`.
- **Fase 11 — Kontrak Karyawan:** tabel `employee_contracts` + model
  `EmployeeContract`, menyimpan metadata file, periode kontrak, catatan,
  employee, dan uploader, termasuk helper `isExpiringSoon()`.
- **Fase 12 — Payroll:** `users` mendapat `salary_base`,
  `target_hours_per_day`, dan `flat_overtime_rate`. Tabel
  `payroll_records` menyimpan payroll unik per karyawan/periode dengan
  base salary, overtime, shortage deduction, adjustment, total, dan status.
- **Fase 13 — Budget & Royalty:** `project_budgets` untuk budget vs actual
  per project + helper variance; `royalty_entries` untuk gross, share,
  recoupment, net, source, periode, dan status pembayaran.
- **Fase 14 — Legal:** tabel `legal_documents` + model `LegalDocument`.
  Satu modul `legal` dipakai untuk kategori `album` dan `royalty`, sehingga
  kontrak album/perjanjian royalti tetap dipisahkan dari Kontrak Karyawan.
- **Fase 15 — IT:** tabel `audit_logs` + `system_changelogs` beserta modelnya.
  Audit Log menyimpan actor/action/detail/timestamp, sedangkan Change Log
  menyimpan version, release date, status, modules, title, dan changes.
- **Fase 16 — Dashboard IA & Settings:** `office_settings` mendapat
  `ceo_accent_color` dan `work_accent_color`. `DashboardAccess` sekarang
  mendukung total 9 modul dengan penambahan `legal` dan `it`; daftar modul
  menjadi sumber kebenaran untuk label, deskripsi, dan icon.
- **User model diperluas:** relasi baru untuk Project Lead, Work Item, KPI,
  Contract, dan Payroll, serta field payroll sudah masuk ke `$fillable` dan
  casts.

**Catatan status:** file-file ini adalah **Data Layer Fase 9–16**. Belum ada
Controller/View/flow end-to-end untuk modul-modul tersebut pada update ini.
Migration harus dijalankan di development sebelum testing, dan deployment
production jangan dilakukan sebelum migration + relation + flow diuji.

### 🔄 Rombak Rencana: Acuan Final Prototype v32

Acuan desain & sistem sebelumnya adalah prototype **W.O.S 2.0 v13**
(`WOS_2_0_STANDALONE_v13.html`). Prototype itu sudah berkembang jauh
lebih lengkap sampai **v18** (`WOS_2_0_App_v18` /
`WOS_2_0_STANDALONE_v18.html`) — bukan cuma nambah halaman, tapi ada
beberapa keputusan produk baru yang mengubah bentuk beberapa modul yang
sebagian sudah kadung dibangun (Fase 4 & 6b). Roadmap di bawah ini
**dirombak total** menyesuaikan v18, dampaknya:

- **Landing page publik (Fase 0–3) TETAP DIPERTAHANKAN apa adanya** —
  ini improvement di luar scope prototype (prototype v13 maupun v18
  cuma didesain untuk sistem internal, gak ada landing page publik/
  rekrutmen sama sekali di sana). Jangan dirombak cuma karena
  "gak ada di prototype".
- **Fase 4 (Absensi) & Fase 6b (Memo) yang sudah "selesai" perlu
  di-revisit**, bukan dianggap gugur — pondasinya (geo, radius,
  riwayat, CRUD memo) tetap dipakai, cuma perlu tambahan sesuai
  kebijakan v18 yang lebih detail dari yang diasumsikan pas Fase 4/6b
  digarap.
- **2 modul sama sekali baru** yang gak ada di rencana lama:
  **Legal** (Kontrak Album & Perjanjian Royalti — beda dari Kontrak
  Karyawan) dan **IT** (Audit Log + System Change Log).
- **CEO Dashboard punya Information Architecture baru**: sidebar
  dikelompokkan 7 grup bernomor (People, Work Control, Finance,
  Royalty, HR Admin, Legal, IT) dengan badge "LIMITED" di grup yang
  aksesnya dibatasi, plus warna aksen CEO/Work Control yang bisa
  di-custom dan sidebar yang scroll independen.

Ringkasan gap yang ditemukan pas audit kode vs `README.md`
(`WOS_2_0_App_v18`) prototype:

| Area                      | Status kode sekarang                                      | Spec v18                                                                                          |
| ------------------------- | --------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Memo                      | 1 arah (Owner/Manajer → tim), CRUD biasa, cuma `pinned`   | Forum: read/unread, hide/unhide, reply berthread per karyawan                                     |
| Home — identitas karyawan | Cuma nama depan                                           | Nama + job title + divisi                                                                         |
| Home — banner cuti        | Cuma cek cuti diri sendiri hari ini                       | Ditambah info cuti tim yang disetujui bulan berjalan                                              |
| Jam kerja WFO             | `work_start_time` + `late_tolerance_minutes` generik      | Jam normal 09:30–20:00, auto-close jam 20:00 kalau lupa checkout & gak ada lembur disetujui       |
| Lembur                    | Belum ada                                                 | Wajib approval, dibayar flat rate per jabatan/karyawan (bukan per durasi jam)                     |
| Potongan kurang jam       | Belum ada logic block                                     | Diakumulasi & dipotong per blok 60 menit                                                          |
| Lapangan/Gigs             | 1 baris absen per orang per hari (`unique(user_id,date)`) | Boleh multi-sesi check-in/out dalam 1 hari                                                        |
| Geo settings              | Lewat seeder, radius fix 150m                             | UI Owner/HR: office name/lat/lng/radius, enable/disable geo, enforce/disable radius, default 200m |
| CEO sidebar               | Flat list satu level                                      | 7 grup bernomor + badge LIMITED + warna aksen custom                                              |
| Legal, IT                 | Tidak ada di rencana lama sama sekali                     | Modul baru (Kontrak Album/Royalti, Audit Log, Change Log)                                         |

Roadmap Fase 7 ke atas di bagian **Roadmap Modul & Role** di bawah ini
sudah ditulis ulang total mengikuti temuan di atas — jangan pakai lagi
peta fase lama (Fase 7–13 versi sebelumnya, kalau ketemu referensinya
di riwayat commit/dokumen breakdown lama, itu sudah tidak berlaku).

### Tech Stack

- Laravel (latest) + PHP
- Tailwind CSS v4 (CSS-first config via `@theme` di `resources/css/app.css`, tanpa `tailwind.config.js`)
- Alpine.js untuk interaksi ringan (sidebar toggle, dsb.) + widget yang lebih kompleks (mis. absen — geolocation, map, kompresi foto)
- Leaflet + OpenStreetMap (tanpa API key) untuk mini map absen (Fase 4) — lihat `resources/js/attendance.js`
- Vite + `laravel-vite-plugin` (font di-_bundle_ lewat Bunny Fonts, bukan Google Fonts CDN)
- MySQL
- Deploy target: shared hosting cPanel/Rumahweb tanpa akses terminal — sama seperti Mavnus & Map of Feelings, jadi hindari dependency yang butuh compile/CLI di server.

### Desain — Disamakan dengan Prototype W.O.S 2.0

Tampilan lama (default Tailwind gray/putih polos) sudah diganti supaya
konsisten dengan prototype desain **W.O.S 2.0**. Acuan sekarang naik ke
**v18** (`WOS_2_0_App_v18/index.html` a.k.a `WOS_2_0_STANDALONE_v18.html`,
dikirim terpisah) — sebelumnya v13. Prototype itu adalah acuan visual +
perilaku sistem (bukan kode yang dipakai langsung — dia HTML/CSS/JS
standalone berbasis `localStorage`, tanpa Laravel/database beneran),
jadi setiap kali menambah halaman/fitur baru, cocokkan ke prototype
v18 dulu sebelum ngoding (bukan v13 lagi). Token warna/radius/font di
bawah ini gak berubah dari v13→v18, cuma struktur IA (sidebar,
grouping modul) dan sebagian behavior sistem yang berubah — lihat
[Rombak Rencana](#-rombak-rencana-acuan-naik-ke-prototype-v18-2026-09-06)
di atas.

Token desain didefinisikan di `resources/css/app.css` lewat blok `@theme`
Tailwind v4 (otomatis jadi utility class, contoh: `--color-cream` → class
`bg-cream`/`text-cream`):

| Token                  | Hex       | Kegunaan                                          |
| ---------------------- | --------- | ------------------------------------------------- |
| `--color-cream`        | `#f2efe7` | Background utama seluruh halaman                  |
| `--color-paper`        | `#fbf9f4` | Background kartu/sidebar sekunder                 |
| `--color-ink`          | `#101010` | Teks utama, tombol hitam, sidebar item aktif      |
| `--color-muted`        | `#8b867e` | Teks sekunder                                     |
| `--color-line`         | `#e5dfd5` | Border kartu/input                                |
| `--color-brand-blue`   | `#3558f4` | Aksen biru (kartu status, badge, link)            |
| `--color-brand-yellow` | `#deb92e` | Aksen kuning (stat card)                          |
| `--color-brand-green`  | `#27c84d` | Aksen hijau (stat card, tombol center bottom-nav) |
| `--color-brand-lime`   | `#b4ef4b` | Aksen lime (stat card)                            |
| `--color-brand-red`    | `#f16c61` | Aksen merah (error/badge)                         |

Font: **Inter** (sebelumnya Instrument Sans), di-load lewat Bunny Fonts di
`vite.config.js`. Radius kartu besar (22–28px), tombol pill penuh
(`rounded-full`), heading tebal & rapat (`font-black`, `tracking-tight`).

Class siap pakai (di `@layer components`, `resources/css/app.css`):

- Tombol: `.btn-wsm-black`, `.btn-wsm-white`, `.btn-wsm-blue`, `.btn-wsm-red`
- Kartu: `.card-wsm` (paper bg), `.card-wsm-white` (white bg)
- Stat card warna solid: `.stat-wsm-blue`, `.stat-wsm-yellow`, `.stat-wsm-green`, `.stat-wsm-lime`
- Badge: `.badge-wsm-green`, `.badge-wsm-yellow`, `.badge-wsm-red`, `.badge-wsm-blue`, `.badge-wsm-gray`
- Form: `.field-label-wsm`, `.input-wsm`
- Bottom nav (layout Karyawan): `.bottom-nav-wsm`, `.bottom-nav-wsm-item` (+ modifier `.active`, `.center`)
- Widget absen (Fase 4): `.mode-toggle-wsm` + `.mode-toggle-wsm-btn` (toggle Kantor/WFH), `.geo-status-wsm` (kartu status geo tag), `.wsm-map` (container mini map Leaflet)

Dua layout yang sudah disesuaikan:

- `resources/views/layouts/app.blade.php` — shell **Owner/Manajer**, sidebar
  paper dengan brand mark hitam, nav item aktif jadi pill hitam (meniru
  `.owner-sidebar` di prototype).
- `resources/views/layouts/employee.blade.php` — shell **Karyawan** gaya
  mobile app, hero besar + bottom nav pill melayang (meniru
  `.employee-shell` + `.bottom-nav` di prototype).

Halaman `auth/login.blade.php`, `employee/home.blade.php`,
`owner/dashboard.blade.php`, halaman-halaman Fase 2
(`owner/employees/*.blade.php`, `owner/organization/index.blade.php`,
komponen `components/org-node.blade.php` untuk render node org-chart
rekursif), halaman-halaman Fase 3 (`public/careers/*.blade.php`,
`recruitment/*.blade.php`), serta halaman-halaman Fase 4
(`employee/attendance/history.blade.php`, `attendance/recap/*.blade.php`),
serta halaman-halaman Fase 5 (`employee/leave/index.blade.php`,
`approval/leave/index.blade.php`), serta `employee/profile.blade.php`
(baru, audit UI/UX 2026-09-06)
sudah ikut disesuaikan (kartu, tombol, stat card warna).
Halaman lain yang belum dibuat (Fase 6 ke atas) tinggal pakai class-class
di atas supaya konsisten — jangan balik pakai `bg-white border
rounded-xl` polos lagi.

### Roadmap Modul & Role

Sistem punya bagian **publik** (landing page + karir, tanpa login) dan
**internal** (4 role dengan akun: Karyawan, Manajer, HRD, Owner). Awalnya
prototype `absensi_wsm`/W.O.S 2.0 cuma didesain untuk sistem internal;
scope sekarang diperluas jadi web publik + rekrutmen, jadi HRD wajib jadi
role beneran (bukan cuma wacana di dokumen breakdown).

Role di kolom `users.role` sudah 4 sejak Fase 2: `owner`, `manajer`,
`karyawan`, `hrd` (dimajukan dari rencana awal "nyusul di Fase 3" karena
ternyata langsung dibutuhkan begitu CRUD karyawan digarap). Manajer
sendiri adalah role eksplisit yang di-assign Owner (bukan status otomatis
dari org-chart) — org-chart tetap dipakai untuk menentukan siapa manajer
dari siapa (approval cuti/izin nanti).

Urutan fase development:

0. **Fondasi** — Laravel, Tailwind, Vite, login multi-role, middleware akses. ✅
1. **Landing Page & Company Profile** — Beranda, Tentang Kami, Layanan, Karir, Kontak (publik, tanpa login). ✅ _(route + view sudah ada; konten masih hardcode di Blade, bukan CMS — itu baru Fase 12. Form kontak baru flash message, belum simpan ke tabel/kirim email. Halaman Karir sengaja tampil "belum ada lowongan" karena data pipeline lowongan asli baru Fase 3.)_
2. **Manajemen Karyawan & Struktur Organisasi** — CRUD karyawan, assign role, org-chart. ✅ _(`Owner\EmployeeController` — index dengan filter role/search/nonaktif + pagination, create/edit/update, soft-delete lewat `destroy` yang dilabeli "nonaktifkan" di UI + `restore` untuk aktifkan lagi; bawahan otomatis dioper ke atasan-di-atasnya kalau manager-nya dinonaktifkan. `Owner\OrganizationController` bangun tree org-chart dari `manager_id` di memori (belum perlu CTE, jumlah karyawan masih kecil), di-render rekursif lewat komponen `components/org-node.blade.php`. Validasi lewat `StoreEmployeeRequest`/`UpdateEmployeeRequest`. `DemoSeeder` isi 1 Owner + 1 Manajer + 1 HRD + 2 Karyawan buat coba langsung. Belum ada: foto profil karyawan, riwayat perubahan role/atasan, halaman detail per karyawan.)_
3. **Rekrutmen (HRD)** — kelola lowongan (nyambung ke halaman Karir), form lamaran publik, pipeline pelamar, convert ke karyawan. ✅ _(`Recruitment\JobOpeningController` (resource, slug otomatis dari judul) + `Recruitment\JobApplicationController` (index/show/updateStatus/convert). Halaman Karir publik (`PageController::careers`/`careerShow`/`careerApply`) sekarang nampilin lowongan status "Tayang" beneran, bukan hardcode lagi. Pipeline status di `JobApplication::STATUSES`. HRD landing ke `/rekrutmen/pelamar` setelah login. Belum ada: upload CV, notifikasi email ke pelamar.)_
4. **Absensi** — clock in/out, riwayat, rekap. ✅ _(`Employee\AttendanceController` — `clockIn()`/`clockOut()` hitung ulang jarak dari kantor di server (`App\Support\Geo::distanceMeters`, Haversine) biar nggak percaya koordinat mentah dari browser, `history()` buat riwayat bulanan sendiri. `Attendance\RecapController` — rekap harian + detail bulanan per karyawan, scope Manajer dibatasi ke bawahan turunan (`scopedUsers()`, sama polanya dengan tree org-chart Fase 2), Owner/HRD lihat semua. Status (`Hadir`/`Terlambat`/`Kurang Jam Kerja`/`Sedang Bekerja`/`Lupa Absen Pulang`) dihitung on-the-fly di model `Attendance`, bukan kolom DB, biar nggak basi kalau `office_settings` diubah. Widget di `employee/home.blade.php` (Alpine component `attendanceWidget`, `resources/js/attendance.js`) urus geolocation, mini map Leaflet (marker kantor + user + lingkaran radius), kompresi foto selfie client-side, dan modal konfirmasi. Koreksi absen manual pindah & selesai di Fase 5. Belum ada: mode Lapangan/Event, halaman Settings buat Owner ubah lokasi/radius dari UI — nyusul Fase 12.)_
5. **Izin/Cuti & Approval** — ke Manajer, fallback Owner. ✅ _(`LeaveRequest` model — 4 jenis (`cuti_tahunan`/`izin_sakit`/`izin_pribadi`/`lainnya`), `countWorkDays()` hitung hari kerja Senin-Jumat, `approveBy()`/`rejectBy()`/`cancelBy()` sebagai state transition biar logic-nya gak keulang di 2 controller. `Employee\LeaveRequestController` — ajukan (cuma hari ini/ke depan, saldo cuti tahunan divalidasi server di `StoreLeaveRequestRequest`) + riwayat + batalkan sendiri. `Approval\LeaveRequestController` — **scope beda dari rekap absensi**: Manajer cuma bawahan LANGSUNG (`manager_id` persis dia, bukan turunan), Owner bisa lihat & putuskan siapa aja kapan aja (approver tercatat siapa yang beneran mutusin). HRD sengaja TIDAK dikasih akses approval. Ditolak wajib alasan (`decision_note`), dibatalkan wajib alasan (`cancellation_reason`, oleh karyawan sendiri ATAU Manajer/Owner) — dua-duanya lewat `CancelLeaveRequestRequest`/`RejectLeaveRequestRequest`. Hari yang izin/cutinya disetujui: tombol absen ilang dari Home (`LeaveRequest::approvedFor()` dicek di `HomeController` buat UI DAN di `AttendanceController::clockIn()` buat validasi server — bukan cuma sembunyi tombol doang), dan muncul badge "Cuti"/"Izin" (bukan "Belum Absen") di rekap `RecapController`. Koreksi absen manual (`RecapController::correct()`, `CorrectAttendanceRequest`) — cuma edit jam, nyimpen `original_clock_in_at`/`original_clock_out_at` (kesisi sekali di koreksi pertama) + siapa/kapan/alasan, dan karyawan bisa lihat catatan itu transparan di riwayatnya sendiri. Semua aksi berdampak (setuju/tolak/batalkan/koreksi) dipasangi `data-confirm`. Belum ada: notifikasi real-time/email (baru badge count, itupun masih ditunda), approval berjenjang (mis. HRD ikut approve cuti tahunan).)_
6. **Dashboard Access & MoM/Memo** ⚠️ _Fitur sudah dibangun; menunggu validasi end-to-end._ _(Fase 6a — fondasi permission per-user per-modul, ngikutin persis konsep prototype v13: tabel `dashboard_access` (`user_id`×`module`×`level` view/manage, baris dihapus kalau levelnya 'none' — bukan disimpan literal), 7 modul (`App\Models\DashboardAccess::MODULES`): Work Control, Project Budgeting, Royalty, KPI & Performance, People & Leave, Contract Monitoring, Payroll Overview. Owner SENGAJA gak punya baris di tabel ini — `User::accessLevel()` hardcode 'manage' semua modul buat Owner, jadi Owner baru otomatis full-access tanpa seed ulang. Middleware baru `module:{modul},{level}` (`EnsureModuleAccess`, alias di `bootstrap/app.php`) — polanya disamain sama `role:...` yang udah ada. Assign akses cuma bisa Owner, lewat `Owner\DashboardAccessController` (tombol "Akses" di tabel karyawan, gak muncul buat baris Owner). Sisi user: tombol "Dashboard" di header app-mobile (`hasAnyDashboardAccess()`) + section "Modul" dinamis di sidebar (`app.blade.php`) — beda tombol dari "Kelola Tim" yang tetap role-based (rekap absensi/approval cuti Fase 4/5 SENGAJA TIDAK dipindah ke sistem ini, kesepakatan waktu breakdown Fase 6). — Fase 6b — modul pertama yang jalan di atas fondasi itu: `Memo` (tabel `memos`, kolom `type` bedain 'memo'/pengumuman vs 'mom'/Minutes of Meeting, `pinned` buat nahan di atas). `Dashboard\Work\MemoController` — CRUD, dijaga `module:work,view` (index) / `module:work,manage` (create/edit/delete) di routing, bukan dicek manual di controller. Kartu "Info dari Owner" di Home (`employee/home.blade.php`) sekarang nampilin 3 memo terbaru beneran — SENGAJA kelihatan buat SEMUA role internal terlepas dari `dashboard_access`, karena ini pengumuman ke tim, bukan modul kerja. `DemoSeeder` nambah contoh: Aldora (karyawan biasa) dikasih akses 'manage' ke Work Control walau dia bukan Manajer/HRD/Owner — buat nunjukin sistemnya beneran per-user bukan per-role. Enam modul lain masih placeholder generik (`dashboard/module.blade.php`)
   sampai dibangun satu-satu.)_

    > ⚠️ **Peta Fase 7 ke bawah ini sudah dirombak total (2026-09-06)**
    > menyesuaikan prototype v18 — lihat
    > [Rombak Rencana](#-rombak-rencana-acuan-naik-ke-prototype-v18-2026-09-06)
    > di atas untuk alasannya. Kalau ada dokumen breakdown lama yang masih
    > nyebut "Fase 7: Task & Project Tracker" langsung habis "Fase 6:
    > Dashboard Access & MoM/Memo", itu urutan LAMA — pakai urutan di bawah
    > ini.

7. **Absensi Lanjutan (Kebijakan WFO v18)** — **fitur user-facing sudah
   dibangun, menunggu validasi end-to-end.** Jam kerja normal WFO 09:30–20:00
    - auto-close, Lembur request → approval, mode Lapangan/Gigs multi-sesi,
      shortage yang dihitung per blok 60 menit, serta pengaturan kantor dari
      UI Owner. **Nominal rupiah lembur/shortage tidak dihitung di Fase 7**;
      data hasil Fase 7 menjadi input Fase 12 Payroll.

8. **Memo Forum & Home Personalization** ✅ _(revisi Fase 6b, bukan
   modul baru. `Memo` yang tadinya broadcast satu arah sekarang forum:
   status baca/sembunyi per-karyawan (tabel `memo_reads`), balasan
   berthread dibagi bareng (tabel `memo_thread_messages`, BUKAN privat
   per-karyawan), badge jumlah balasan belum dibaca manajemen di
   sidebar "Work Control" (beda dari prototype: badge di sini beneran
   reset pas dibaca, prototype-nya gak pernah reset — lihat Riwayat
   Perubahan Detail buat alasannya). Home: sapaan tampil job title +
   divisi, banner "Cuti Tim Bulan Ini". Belum dites end-to-end,
   checklist ada di Checklist Testing Teknis.)_
9. **Work Control Lanjutan** — Projects, Work Tracker (papan
   status/board), Timeline Calendar, dan MoM (Minutes of Meeting)
   sebagai fitur-fitur terpisah di modul `work` yang sama (bukan cuma
   1 tabel `type=memo|mom` kayak sekarang) — nempel di
   `dashboard_access` modul `work` yang udah ada dari Fase 6a, cuma
   nambah tab/section baru, gak perlu modul access baru.
10. **KPI & Performance** — modul `kpi` (udah ada di
    `DashboardAccess::MODULES`, tinggal diisi).
11. **Kontrak Karyawan** — modul `contracts`, KHUSUS kontrak kerja
    karyawan (bukan kontrak album/royalti — itu masuk Legal di Fase
    14). Nama modul di kode boleh tetap `contracts`, tapi UI-nya perlu
    jelas dilabeli "Kontrak Karyawan" biar gak ketuker sama Legal.
12. **Payroll** — modul `payroll`. Baru bisa akurat kalau Fase 7
    (Lembur + potongan blok 60 menit) udah selesai duluan — payroll
    butuh angka itu sebagai input. **Juga nampung field yang ditunda
    dari Fase 7**: `Gaji Pokok`, `Target Jam/Hari`, `Flat Overtime
Rate` per karyawan (di form Karyawan, `users` table) — baru
    ditambah di sini, bukan Fase 2/7, sesuai keputusan 2026-09-06.
13. **Project Budgeting & Royalty** — modul `budget` (budget vs actual
    per project) & `royalty` (royalty, share, recoupment, status
    pembayaran) — dua modul terpisah tapi biasanya dikerjain
    berurutan karena sama-sama "Finance & Rights" di sidebar CEO.
14. **Legal — BARU, gak ada di rencana lama** — Kontrak Album &
    Perjanjian Royalti, SENGAJA dipisah dari Kontrak Karyawan (Fase
    11). Modul access baru (`legal` atau dipecah `legal_album`/
    `legal_royalty` — perlu diputusin pas breakdown, prototype pakai 2
    halaman terpisah tapi belum tentu perlu 2 level akses beda).
15. **IT — BARU, gak ada di rencana lama** — Audit Logs (siapa ubah
    apa, kapan — lintas modul) + System Change Log (riwayat rilis
    fitur, versi, tanggal — mirip semangat bagian "Status saat ini" di
    README ini, tapi buat end-user Owner lewat UI, bukan cuma
    dokumentasi repo). Modul access baru (`it`).
16. **CEO Dashboard IA Restructure & Settings** — sidebar Owner
    dirombak dari flat list jadi 7 grup bernomor (People/Work
    Control/Finance/Royalty/HR Admin/Legal/IT), grup yang aksesnya
    dibatasi dikasih badge "LIMITED". Halaman Settings baru: warna
    aksen CEO Dashboard & Work Control Dashboard bisa di-custom Owner
    (disimpan di `office_settings` atau tabel `settings` baru), sidebar
    dibikin scroll independen dari main content biar nav panjang tetap
    kejangkau.
17. **CMS Landing Page** — Owner edit konten landing page publik
    (Fase 1) tanpa sentuh kode. Tetap di urutan paling akhir kayak
    rencana lama — landing page publik statis kontennya jarang
    berubah, gak sepenting modul internal di atas.
18. **Keamanan, Testing, Deployment** — staging/production terpisah,
    backup otomatis, monitoring.

Detail lengkap tiap fase dan peta halaman per role ada di dokumen breakdown
project (dibagikan terpisah oleh tim, bukan bagian repo ini).

### Flow yang Sudah Berjalan (per Role)

Ringkasan yang bisa langsung dicoba klik-klik per role, bukan cuma daftar
fitur — biar gampang dites urut dari login sampai selesai.

**Publik** (tanpa login)

- ✅ Buka Beranda/Tentang Kami/Layanan, kirim pesan lewat Kontak (flash
  message aja, belum tersimpan ke tabel)
- ✅ Buka Karir → daftar lowongan status "Tayang" (data asli dari DB)
- ✅ Buka detail lowongan → kirim lamaran (nama, email, telepon, pesan)

**Karyawan** (Manajer/HRD/Owner juga jalanin flow ini buat absen diri
sendiri — satu tabel `users`, semua role internal absen dengan cara yang
sama)

- ✅ Login → Home nampilin status kehadiran hari ini
- ✅ Pilih mode Kantor/WFH + catatan opsional
- ✅ "Test Lokasi" → cek mini map & radius sebelum absen beneran
- ✅ Absen Masuk — modal konfirmasi (map + jarak + radius), foto selfie
  opsional dari kamera langsung
- ✅ Absen Pulang — pola konfirmasi sama
- ✅ Riwayat absensi bulanan sendiri (navigasi bulan), termasuk catatan
  transparan kalau ada absen yang dikoreksi Manajer/Owner (siapa, kapan,
  kenapa, jam aslinya berapa)
- ✅ Banner reminder kalau lupa absen pulang hari sebelumnya
- ✅ Menu Request → ajukan Cuti Tahunan/Izin Sakit/Izin Pribadi/Lainnya,
  lihat sisa saldo cuti tahunan, tombol ajukan otomatis disable kalau
  saldo kurang
- ✅ Batalkan pengajuan sendiri (pending atau yang udah disetujui) —
  wajib isi alasan
- ✅ Kalau hari ini lagi izin/cuti disetujui: tombol absen ilang, kartu
  Home ganti jadi info izin/cuti
- ❌ Koreksi absen sendiri kalau salah (harus lewat Manajer/Owner) — di
  luar scope, karyawan cuma bisa "lapor", bukan edit sendiri

**Manajer** (semua flow Karyawan di atas, ditambah)

- ✅ Menu Absensi di sidebar → rekap harian **bawahan turunan sendiri
  aja** (bukan seluruh perusahaan)
- ✅ Klik nama karyawan → riwayat bulanan (jam, radius, link Google
  Maps, foto selfie)
- ✅ Menu Persetujuan di sidebar → izin/cuti bawahan **LANGSUNG aja**
  (beda dari scope rekap absensi di atas yang bawahan turunan — ini
  kesepakatan khusus Fase 5)
- ✅ Setujui (satu klik + konfirmasi) atau Tolak (wajib alasan) pengajuan
  pending
- ✅ Batalkan izin/cuti bawahan yang udah disetujui — wajib alasan
- ✅ Koreksi jam absen bawahan dari halaman riwayat (edit jam masuk/
  pulang + catatan alasan wajib, karyawan bisa lihat catatannya)
- ❌ Approval berjenjang / ikut campur approval Owner

**HRD** (semua flow Karyawan di atas, ditambah)

- ✅ Landing langsung ke halaman Pelamar setelah login
- ✅ Kelola lowongan (buat/edit/hapus, draft/tayang/tutup)
- ✅ Kelola pipeline pelamar (ubah status, catatan internal, convert
  jadi akun karyawan)
- ✅ Menu Absensi → rekap **SEMUA karyawan** (beda dari Manajer yang
  cuma lihat timnya)
- ❌ Approval izin/cuti (sengaja gak dikasih — kesepakatan Fase 5: cuma
  Manajer & Owner), upload CV pelamar, notifikasi email

**Owner** (semua flow HRD & Manajer di atas, ditambah)

- ✅ Dashboard: kehadiran hari ini (X/Y karyawan), "Pengajuan Pending"
  gabungan izin/cuti pending + absen yang butuh perhatian (lupa
  checkout)
- ✅ CRUD karyawan penuh (tambah/edit/nonaktifkan/aktifkan lagi)
- ✅ Struktur organisasi (org-chart dari `manager_id`)
- ✅ Menu Absensi → rekap semua karyawan (sama seperti HRD) + koreksi
  jam absen siapa aja
- ✅ Menu Persetujuan → lihat & putuskan pengajuan **SIAPA AJA**, kapan
  aja — termasuk yang harusnya diurus Manajer (tercatat "disetujui oleh
  Owner" biar jelas siapa yang beneran mutusin)
- ✅ Assign/ubah Dashboard Access karyawan per modul (tombol "Akses" di
  tabel Karyawan) — cuma Owner yang bisa
- ✅ Otomatis akses 'manage' ke semua 7 modul tanpa perlu di-assign
  (dihitung di kode, bukan data)
- ❌ Atur lokasi kantor/radius/jam kerja dari UI (masih lewat seeder,
  UI-nya baru Fase 12)

**Dashboard Access & MoM/Memo (Fase 6)** — siapa aja yang punya akses,
bukan cuma role tertentu

- ✅ Tombol "Dashboard" muncul di header app-mobile buat siapa aja yang
  punya minimal 1 akses modul (`hasAnyDashboardAccess()`) — termasuk
  karyawan biasa kalau di-assign Owner, bukan cuma Manajer/HRD/Owner
- ✅ Landing `/dashboard` nampilin modul yang diakses aja, dengan badge
  level (View/Manage)
- ✅ Modul **Work Control** (satu-satunya yang udah ada isinya): lihat
  daftar Memo & MoM (kalau level View), tambah/edit/hapus (kalau
  level Manage) — pin memo penting biar nongol duluan
- ✅ Memo yang di-pin/terbaru (3 teratas) otomatis muncul di kartu "Info
  dari Owner" di Home **semua** role internal, terlepas dari siapa
  yang punya akses modul Work Control
- ❌ 6 modul lain (Project Budgeting, Royalty, KPI, People & Leave,
  Contract Monitoring, Payroll) — masih placeholder "belum dibangun",
  levelnya udah bisa di-assign tapi isinya kosong

### Alert & Konfirmasi (SweetAlert)

Semua notifikasi (flash message sukses/gagal, ringkasan error validasi)
dan semua konfirmasi sebelum aksi destruktif sekarang pakai
**SweetAlert2**, dipusatkan di `resources/js/alerts.js` (di-_import_ dari
`resources/js/app.js`, jalan otomatis di semua halaman yang me-load
`@vite(['resources/js/app.js'])`).

**Flash & validasi otomatis** — cukup taruh
`@include('partials.flash-data')` sekali di layout (sudah ada di
`layouts.app`, `layouts.employee`, `layouts.public`, dan
`auth/login.blade.php`). Partial itu nulis `session('status')`,
`session('error')`, `session('warning')`, dan `$errors->all()` jadi JSON;
`alerts.js` yang baca lalu tampilkan:

- `session('status')` → toast hijau
- `session('error')` / `session('warning')` → toast merah/kuning
- 1 pesan validasi → toast merah
- 2+ pesan validasi → popup checklist "Ada isian yang belum sesuai"

Kalau bikin controller/halaman baru yang pakai layout di atas, flash
message otomatis kepakai — tidak perlu nulis blade banner manual lagi.

**Konfirmasi sebelum submit** — tinggal tambah atribut `data-confirm` di
`<form>`, tidak perlu JS tambahan:

```blade
<form method="POST" action="{{ route('owner.employees.destroy', $employee) }}"
    data-confirm="{{ $employee->name }} tidak akan bisa login lagi, tapi riwayat datanya tetap tersimpan."
    data-confirm-title="Nonaktifkan {{ $employee->name }}?"
    data-confirm-button="Ya, nonaktifkan" data-confirm-danger="1">
    @csrf
    @method('DELETE')
    <button type="submit">Nonaktifkan</button>
</form>
```

Atribut yang tersedia: `data-confirm` (teks isi), `data-confirm-title`,
`data-confirm-button`, `data-cancel-button`, `data-confirm-danger="1"`
(tombol konfirmasi jadi merah, dipakai untuk aksi yang sifatnya
menghapus/menonaktifkan). Sudah dipakai di: form keluar akun
(`layouts.app`, `layouts.employee`) dan form nonaktifkan/aktifkan
karyawan (`owner/employees/index.blade.php`).

Untuk manggil dari JS langsung (mis. dalam Alpine `@click`), ada
`window.WsmAlert` dengan method `success()`, `error()`, `warning()`,
`validationSummary(messages)`, dan `confirm({ title, text, ... })`
(return Promise, lihat isi `alerts.js` untuk detail).

### Halaman Error Custom

Ada di `resources/views/errors/` (`404`, `403`, `500`, `503`) + layout
mandiri `resources/views/layouts/error.blade.php` (tidak `extends
layouts.app`, karena error bisa kejadian sebelum ada user login). Gaya
visualnya sudah disamakan ke brand WSM (lihat token desain di atas).

Catatan penting soal kapan halaman ini benar-benar muncul:

- `404`, `403`, `503` → langsung kepakai kapan saja, termasuk saat
  `APP_DEBUG=true` (dev lokal). Untuk coba `403`, akses halaman yang
  butuh role lain dari akun yang lagi login; untuk `404`, akses URL
  ngasal; untuk `503`, jalankan `php artisan down`.
- `500` (generic `Throwable`, bukan `abort(500)`) → **hanya** muncul
  kalau `APP_DEBUG=false` di `.env`. Selama dev lokal (`APP_DEBUG=true`)
  Laravel selalu nunjukin halaman Ignition/Whoops yang detail, itu
  perilaku bawaan framework, bukan berarti custom view-nya salah/tidak
  kepasang. Kalau mau coba tampilannya di lokal: set sementara
  `APP_DEBUG=false` lalu picu error apa saja, atau panggil
  `abort(500)` di satu route buat tes.
- `503` otomatis beda konten: `resources/views/errors/503.blade.php`
  cek `app()->isDownForMaintenance()` — kalau `true` (lagi
  `php artisan down`) tampil "Sedang Maintenance" + auto-reload tiap 30
  detik; kalau bukan (503 dari sumber lain) tampil "Layanan Tidak
  Tersedia" generik.

- `Auth::id()` Facade, bukan `auth()->id()` helper (kompatibilitas Intelephense)
- `asset('storage/...')`, bukan `Storage::disk('public')->url()`
- Role dicek lewat middleware `role:...` (`App\Http\Middleware\EnsureRole`), bukan Gate/Policy terpisah, untuk sekarang
- Satu tabel `users` untuk semua role internal (dibedakan kolom `role`), `manager_id` self-reference untuk alur approval
- Route dikelompokkan per role di `routes/web.php` — halaman baru masuk ke grup yang sesuai, jangan lepas di luar grup
- Notifikasi & konfirmasi pakai SweetAlert (`resources/js/alerts.js`), **jangan** balik pakai `alert()`/`confirm()` bawaan browser — lihat [Alert & Konfirmasi (SweetAlert)](#alert--konfirmasi-sweetalert)
- Input tanggal yang secara logis tidak boleh di masa depan (mis. `birth_date`) dikasih atribut `max` di sisi HTML selain validasi server (`before:today` dsb.) — biar salah ketik ketauan sebelum submit, bukan cuma setelah
- Model yang punya halaman publik dengan URL berbasis slug (mis. `JobOpening`) override `getRouteKeyName()` jadi `'slug'` — jangan cuma taruh `{param:slug}` di routes/web.php doang, soalnya `route()` helper generate URL pakai `getRouteKeyName()` model, bukan suffix binding di route
- Status yang bisa dihitung ulang dari data lain (mis. status kehadiran di `Attendance`) sengaja TIDAK disimpan sebagai kolom DB — dihitung lewat accessor di model, biar nggak ada data basi kalau aturan/pengaturan berubah belakangan
- Data sensitif yang dikirim dari browser (koordinat GPS, dsb.) selalu dihitung ulang/divalidasi di server (`App\Support\Geo`), jangan percaya begitu saja angka yang dikirim JS — bisa dimanipulasi user
- Kompresi gambar (mis. foto selfie absen) dilakukan di browser lewat `<canvas>`, bukan library PHP (Intervention/GD) di server — sesuai batasan hosting cPanel tanpa terminal, hindari nambah dependency yang butuh extension khusus kalau bisa dihindari
- **Scope "bawahan Manajer" BEDA-BEDA per modul, ini sengaja bukan bug**: rekap absensi (`Attendance\RecapController::scopedUsers()`) pakai bawahan TURUNAN (rekursif, ikut cucu-cicit di org-chart), sedangkan approval izin/cuti (`Approval\LeaveRequestController::canDecide()`) pakai bawahan LANGSUNG doang (`manager_id` persis Manajer tsb). Dua keputusan beda yang diambil terpisah pas breakdown fase — kalau nambah modul baru yang ada konsep "scope Manajer", jangan asumsikan otomatis sama, konfirmasi dulu mana yang dimaksud
- State transition (approve/reject/cancel di `LeaveRequest`, dst.) ditaruh sebagai method di model (`approveBy()`, `rejectBy()`, `cancelBy()`), bukan logic mentah di controller — biar gak keulang nulis hal yang sama pas dipanggil dari 2 controller berbeda (Employee & Approval)

### Checklist Testing Teknis

> **Tujuan bagian ini sekarang:** memvalidasi fitur yang sudah dibangun,
> bukan mengaudit ulang kode dari nol. Step 0 sudah selesai; fokus terdekat
> adalah Fase 6 & 8, lalu validasi Fase 7.

#### Regression Check — Fase 6–8

Fase 6–8 sudah divalidasi end-to-end. Checklist di bawah dipakai sebagai
regression test jika ada perubahan pada modul terkait.

**A. Owner — Dashboard Access**

1. Login sebagai `owner@wsm.local`.
2. Buka **Karyawan → Akses**.
3. Pastikan Owner tidak memiliki tombol "Akses" untuk dirinya sendiri.
4. Buka akses **Gepeng**:
    - ubah satu modul menjadi `View` → simpan → pastikan tersimpan;
    - ubah menjadi `Manage` → simpan → pastikan tersimpan;
    - hapus/nonaktifkan akses → pastikan modul tidak lagi bisa dipakai.
5. Login sebagai user tersebut dan cek perubahan akses benar-benar berlaku.
6. Coba buka URL modul secara langsung untuk memastikan pembatasan bukan
   hanya menyembunyikan menu.

**B. Karyawan dengan Manage — contoh Aldora**

1. Login sebagai `aldora@wsm.local`.
2. Tombol **Dashboard** harus muncul.
3. Dashboard hanya menampilkan modul yang memang diberikan.
4. Buka **Work Control**.
5. Buat Memo dan MoM.
6. Edit dan hapus data.
7. Pastikan data tampil kembali di listing.

**C. Karyawan dengan View — contoh Gepeng**

1. Login sebagai `gepeng@wsm.local`.
2. Buka Work Control.
3. Data boleh dilihat.
4. Tombol Tambah/Edit/Hapus tidak boleh tersedia.
5. Coba buka `/dashboard/work/create` langsung → harus **403**.

**D. Memo Forum — semua role internal**

1. Login sebagai user biasa.
2. Di Home, memo muncul di kartu **Info dari Owner**.
3. Memo baru harus berstatus **UNREAD**.
4. Tandai sudah dibaca → status berubah menjadi **READ**.
5. Sembunyikan memo → memo hilang dari daftar utama.
6. Buka "N disembunyikan" → memo muncul kembali.
7. Balas memo → balasan muncul di thread.
8. Login sebagai user lain → balasan user sebelumnya harus tetap terlihat,
   karena thread bersifat bersama, bukan privat.

**E. Badge Management**

1. Pastikan ada reply karyawan yang belum dibaca management.
2. Login sebagai user yang punya `Manage` Work Control.
3. Badge unread pada **Work Control** harus muncul.
4. Buka `/dashboard/work`.
5. Badge harus menjadi 0 setelah halaman dimuat, tanpa perlu refresh manual.

**F. Home Personalization**

1. Pastikan user memiliki `job_title` dan/atau `division`.
2. Login → Home harus menampilkan informasi tersebut.
3. Jika ada cuti tim bulan berjalan, banner **Cuti Tim Bulan Ini** harus
   muncul.
4. Izin sakit tidak boleh diumumkan di banner tim.

> **Hasil Step 1:** tandai tiap poin sebagai `PASS`, `PARTIAL`, atau `FAIL`.
> Jangan mengubah status fase hanya berdasarkan perkiraan dari kode.

#### Regression Check — Fase 7 Absensi Lanjutan

Gunakan setelah ada perubahan pada modul Absensi Lanjutan:

- [ ] Kantor/WFH tetap maksimal 1 sesi per hari.
- [ ] Lapangan/Gigs bisa membuat sesi berikutnya setelah sesi sebelumnya
      checkout.
- [ ] Sesi yang lupa checkout untuk Kantor/WFH dapat auto-close sesuai
      `normal_end_time`.
- [ ] Lembur bisa diajukan.
- [ ] Manajer hanya melihat bawahan langsung untuk approval.
- [ ] Owner bisa melihat dan memutuskan semua pengajuan.
- [ ] Reject wajib alasan.
- [ ] Pembatalan wajib alasan.
- [ ] Pengajuan aktif ganda pada tanggal yang sama ditolak.
- [ ] Shortage dapat dihitung dalam blok 60 menit.
- [ ] Owner dapat mengubah pengaturan kantor dari UI.
- [ ] Perubahan `normal_end_time` yang tidak valid ditolak.
- [ ] Status Hadir/Terlambat/Kurang Jam Kerja/Auto-close tampil sesuai data.

**Catatan:** Fase 7 tidak menghitung nominal rupiah. Gaji pokok, target
jam/hari, flat overtime rate, pembayaran lembur, dan dampak finansial
shortage dikerjakan bersama **Fase 12 Payroll**.

#### Setelah Step 1–2

Jika Fase 6, 7, dan 8 sudah `PASS`, lanjut ke:

**Fase 9 → Fase 10 → Fase 11 → Fase 12 Payroll → Fase 13 → Fase 14 →
Fase 15 → Fase 16 → Fase 17 → Fase 18.**

### Ceklis Parity UI/UX vs Prototype v32

**Aturan baku buat semua fase di bawah:** ikutin UX & posisi elemen
prototype v32 SEPERSIS mungkin. Boleh dirubah dari aslinya HANYA kalau
niru persis bikin sesuatu **gak responsif** (mis. sidebar 7-grup
prototype didesain buat layar lebar, harus dites & disesuaikan buat
mobile) atau **berantakan** (mis. prototype numpuk banyak badge/label
di 1 baris yang muat di layar besar tapi kepotong di HP) — kalau
kejadian salah satu itu, susunan/posisi boleh disesuaikan, tapi
elemen & fungsinya harus tetap ada, jangan dihilangin.

**Sudah selesai:**

- [x] Tab Profile bottom-nav (kartu identitas + ganti password) — dulu placeholder `href="#"`
- [x] Avatar inisial nama di header app-mobile (posisi kanan, sejajar prototype)
- [x] Lock Dashboard — tombol posisi footer sidebar (di atas kartu profil), layar unlock terpisah
- [x] "← App Saya" — posisi di paling atas nav sidebar
- [x] Sidebar Modul (7 modul, urutan & label persis prototype: Work Control, Project Budgeting, Royalty Dashboard, KPI & Performance, People & Leave, Contract Monitoring, Payroll Overview)
- [x] Split "Kelola Tim" + "Dashboard" di header app-mobile (deviasi disengaja, alasannya di atas)
- [x] Halaman error custom (404/403/500/503) — gaya brand mark disamain
- [x] SweetAlert buat semua konfirmasi/alert (termasuk logout & kunci dashboard)
- [x] **Fase 8 (Memo Forum & Home Personalization)** — badge unread di sidebar "Work Control" (dihitung ulang biar beneran reset pas dibaca, bukan numpuk terus kayak prototype — lihat catatan di bawah), icon per-item buat semua 7 modul, reply jadi thread (dibagi bareng, bukan flat/privat), tombol tandai-baca + sembunyikan per-memo, job title/divisi di Home, banner cuti tim bulan ini.

**Belum, per fase (urutan sama seperti roadmap aktif; Fase 6–8 sudah selesai):**

- **Fase 7 (Absensi Lanjutan)** — parity UI/UX sudah diimplementasikan dan
  divalidasi sebagai bagian Fase 7. Regression test dilakukan kembali jika
  ada perubahan pada absensi, lembur, shortage, atau pengaturan kantor.
- **Fase 9 (Work Control Lanjutan)** — Tracker pakai board (kolom Todo/In Progress/Done, drag-drop), Timeline Calendar pakai grid kalender bulanan — dua-duanya UI BARU (belum ada padanan lama sama sekali di web resmi), jadi bebas ambil struktur HTML/CSS-nya dari prototype (bukan kode JS-nya, itu di-porting jadi Blade+Livewire/Alpine sesuai stack yang udah dipakai).
- **Fase 10–15 (KPI, Kontrak, Payroll, Budgeting, Royalty, Legal, IT)** —
  UI masih placeholder generik (`dashboard/module.blade.php`), jadi belum ada
  flow user-facing. Namun **Data Layer Fase 10–15 sudah disiapkan**:
  migration + model untuk KPI, kontrak, payroll, budget, royalty, legal, dan
  IT. Saat mulai UI, tetap cek halaman `data-opage` yang sepadan di prototype
  v18 sebelum desain dari nol.
- **Fase 16 (CEO Dashboard IA Restructure)** — perubahan UI paling besar:
    - Sidebar dipecah jadi section berlabel angka: `1·PEOPLE`, `2·WORK CONTROL`, `3·FINANCE`, `4·ROYALTY`, `5·HR ADMIN`, `6·LEGAL`, `7·IT` (label persis, posisi di atas grupnya masing-masing, style `.sidebar-group-label`).
    - Section yang dibatasin (mis. Finance/Royalty kalau user gak punya akses) dikasih badge "LIMITED" (`.sidebar-lock`) di ujung kanan label grup, BUKAN grup itu disembunyikan total — beda dari cara `canViewModule()` sekarang yang nyembunyiin modul kalau gak ada akses.
    - Warna aksen custom per-user (Owner bisa atur) — ini satu-satunya bagian Fase 16 yang butuh kolom baru di database (`users.accent_color` atau semacamnya), bukan cuma soal tata letak.
    - **Sidebar-nya sendiri kudu scroll independen** dari konten utama kalau daftar modul udah panjang (7 grup + isinya) — cek dulu di layar pendek/laptop kecil, jangan sampe keseluruhan sidebar kepotong tanpa bisa di-scroll.

1. **Fase 7 — Absensi Lanjutan (Kebijakan WFO v18)** — jam WFO
   09:30–20:00 + auto-close, Lembur (flat rate per jabatan), potongan
   kurang jam per blok 60 menit, mode Lapangan/Gigs multi-sesi, geo
   settings pindah ke UI Owner/HR.
2. **Fase 8 — Memo Forum & Home Personalization** — Memo jadi forum
   (read/unread/hide/reply thread), Home nampilin job title+divisi +
   banner cuti tim bulan ini.
3. **Fase 9 — Work Control Lanjutan** — Projects, Work Tracker
   (board), Timeline Calendar, MoM dipisah dari Memo, tetap nempel
   modul `work` yang sama.
4. **Fase 10 — KPI & Performance** — modul `kpi`.
5. **Fase 11 — Kontrak Karyawan** — modul `contracts` (khusus kontrak
   kerja karyawan, bukan album/royalti).
6. **Fase 12 — Payroll** — modul `payroll` (butuh Fase 7 kelar duluan
   buat data Lembur & potongan jam).
7. **Fase 13 — Project Budgeting & Royalty** — modul `budget` &
   `royalty`.
8. **Fase 14 — Legal (BARU)** — Kontrak Album & Perjanjian Royalti,
   modul access baru, terpisah dari Kontrak Karyawan.
9. **Fase 15 — IT (BARU)** — Audit Logs + System Change Log, modul
   access baru.
10. **Fase 16 — CEO Dashboard IA Restructure & Settings** — sidebar
    7 grup bernomor + badge LIMITED, warna aksen custom, sidebar
    scroll independen.
11. **Fase 17 — CMS Landing Page** — Owner edit konten landing page
    publik tanpa sentuh kode.
12. **Fase 18 — Keamanan, Testing, Deployment** — staging/production
    terpisah, backup otomatis, monitoring.

**Hubungan Fase 7 → Fase 12:** Fase 7 menghasilkan data absensi,
Lembur, dan shortage. Fase 12 memakai data tersebut untuk perhitungan
Payroll, termasuk nominal lembur dan dampak shortage. Karena itu Fase 7
perlu selesai dan tervalidasi sebelum Payroll dibuat, tetapi **tidak
perlu menghitung uang lembur di Fase 7**.

Fase 8 tidak bergantung pada Fase 7. Karena itu Fase 6/8 bisa divalidasi
terlebih dahulu, lalu Fase 7 divalidasi sebelum masuk Fase 9–12.

Belum ada keputusan final urutan Fase 10–13 (KPI/Kontrak/Payroll/
Budgeting & Royalty) — sama seperti rencana lama, itu masih perlu
didiskusikan tim sebelum mulai ngoding, cuma Fase 14 (Legal) & Fase 15
(IT) yang baru ketauan urutannya wajar diletakkan setelah modul
finance karena sama-sama grup "terbatas" di sidebar CEO v18.

### 🩹 Perbaikan Bug Fase 7 (2026-09-08)

Audit ulang parity Fase 7 vs prototype v18 nemuin 2 masalah, dua-duanya
udah diperbaiki:

1. **File request ketuker, dua fitur Owner sama-sama bakal fatal error.**
   `app/Http/Requests/Owner/UpdateEmployeeRequest.php` ternyata isinya
   ketuker jadi isi class `UpdateOfficeSettingRequest` (kemungkinan
   ke-overwrite gak sengaja pas development Fase 7). Efeknya: class
   `UpdateEmployeeRequest` yang asli gak pernah ada file-nya (autoload
   Composer butuh nama file = nama class), jadi:
    - **Owner → Karyawan → Edit** (`EmployeeController::update()`) bakal
      `Class not found`.
    - **Owner → Pengaturan Kantor → Simpan** (`OfficeSettingController::update()`)
      juga bakal `Class not found`, karena class `UpdateOfficeSettingRequest`
      yang bener gak pernah ada di file dengan nama yang cocok.

    Diperbaiki dengan misahin ke 2 file yang benar:
    - `app/Http/Requests/Owner/UpdateEmployeeRequest.php` — dibikin ulang
      dari nol, rules-nya disamain sama `StoreEmployeeRequest` (Fase 2),
      bedanya `password` jadi `nullable` (opsional pas edit) dan
      `email` unique-nya ngecualiin baris user itu sendiri
      (`Rule::unique(...)->ignore($this->route('employee'))`).
    - `app/Http/Requests/Owner/UpdateOfficeSettingRequest.php` — file
      baru, isinya yang sebelumnya ketuker taruh di file
      `UpdateEmployeeRequest.php` (rules lokasi/radius/jam kerja +
      validasi `normal_end_time > work_start_time` lewat `withValidator()`).

2. **Auto-close cuma nutup sesi hari-hari sebelumnya, belum sesi hari ini.**
   `AttendanceReconciler::reconcile()` sebelumnya query
   `whereDate('date', '<', Carbon::today())` — sesi Kantor/WFH yang
   lupa checkout HARI INI baru ketutup besok pas reconcile jalan lagi,
   beda dari `ensureAutoCloseAttendance()` prototype yang nutup sesi
   hari yang sama begitu app dibuka lewat `normal_end_time`. Diperbaiki:
   query sekarang `whereDate('date', '<=', Carbon::today())`, lalu buat
   baris hari ini di-skip (dibiarin terbuka) kalau mode-nya
   Lapangan/Gigs, ATAU ada Lembur disetujui hari itu, ATAU belum lewat
   `normal_end_time` — baru ditutup di `normal_end_time` hari itu juga
   kalau ketiga syarat itu gak kepenuhi. Perilaku buat tanggal yang
   sudah lewat (kemarin dst.) TIDAK berubah.

**Belum sempat dites langsung** (sandbox nulis kode ini gak punya PHP),
jadi sebelum dianggap kelar, jalanin manual:

- **Edit Karyawan**: login Owner → Karyawan → Edit salah satu baris →
  ubah nama/role/dll → Simpan → harus berhasil update tanpa error, dan
  field `password` boleh dikosongin (gak ganti password lama).
- **Pengaturan Kantor**: login Owner → buka `/pengaturan-kantor` → ubah
  salah satu field → Simpan → harus muncul toast/status sukses, bukan
  error. Coba juga isi `normal_end_time` lebih kecil/sama dari
  `work_start_time` → harus muncul pesan validasi, bukan tersimpan.
- **Auto-close hari ini**: set `normal_end_time` ke waktu yang udah
  lewat dari jam sekarang (mis. kalau sekarang jam 14:00, set ke
  13:00) → login sebagai karyawan yang lagi check-in mode Kantor tanpa
  checkout → buka halaman Home/Riwayat lagi → sesi itu harus otomatis
  ke-checkout dengan `auto_closed=true` di jam `normal_end_time` yang
  baru di-set, TANPA nunggu ganti hari. Balikin lagi `normal_end_time`
  ke jam normal setelah selesai tes.

### 🔍 Audit Ulang Menyeluruh (2026-09-08, ronde 2)

Ronde audit sebelumnya cuma baca kode manual (sandbox penulisan gak
ada PHP). Kali ini project beneran dijalanin (PHP 8.3 + SQLite di
sandbox terpisah) buat nyari masalah yang gak kelihatan cuma dari baca
kode. Ketemu 1 masalah **kritis buat deployment** dan 1 lagi **bug
sama persis kayak kemarin** di modul lain.

1.  **🚨 KRITIS — dependency project butuh PHP 8.4+, padahal
    `composer.json` nulis `"php": "^8.3"`.** `vendor/` yang ke-upload
    ternyata di-install pas lokal udah pakai PHP 8.4+ (kemungkinan
    Laragon udah keupdate), dan beberapa komponen Symfony yang dipakai
    Laravel 13 (`symfony/http-foundation`, `symfony/console`, dst.)
    sekarang PAKAI SYNTAX PHP 8.4 (**property hooks**, contoh:
    `public ParameterBag $attributes { set { ... } }` di
    `vendor/symfony/http-foundation/Request.php`). Ini BUKAN cuma soal
    compatibility yang "kemungkinan error" — kodenya secara harfiah gak
    bisa di-parse PHP 8.3 ke bawah, langsung fatal `syntax error` di
    baris paling awal request masuk. **Sebelum deploy ke Rumahweb,
    WAJIB dicek dulu di cPanel → MultiPHP Manager, versi PHP tertinggi
    yang tersedia di hosting itu berapa.** Kalau cuma sampai 8.3, situs
    bakal langsung down total (bukan cuma 1 fitur) begitu file di-upload
    — apapun benar-salahnya kode PHP kita sendiri, gak akan pernah
    sempat kejalanin.

             **Update 2026-09-08 (ronde 4) — akar masalahnya BUKAN Laravel 13
             sendiri, jadi gak perlu downgrade major version.** Laravel 13
             ("illuminate/\*") sebenarnya cuma butuh PHP 8.3 minimum (naik dari
             8.2 di Laravel 12, bukan ke 8.4) — dicek langsung ke rilis resminya.
             Yang butuh 8.4 itu spesifik `symfony/http-foundation` versi 8.x,
             padahal constraint Laravel 13 sendiri ke paket itu masih
             `^5.4|^6.4|^7.3|^8` — artinya Symfony **7.3/7.4 juga tetap
             memenuhi** syarat Laravel 13, dan Symfony 7.x itu cuma butuh PHP
             8.2+ (bukan 8.4). Composer kemarin kebetulan resolve ke Symfony 8.x
             (versi terbaru yang tersedia) karena constraint di `composer.json`
             gak mengunci versi Symfony-nya secara eksplisit.

             **Fix yang sudah diterapkan di `composer.json`:** nambahin pin
             eksplisit `"symfony/console": "^7.3"`, `"symfony/http-foundation":

        "^7.3"`, dan komponen Symfony lain yang dipakai Laravel 13 (mailer,

    mime, routing, http-kernel, dst.) semua ke `^7.3`— biar Composer
    gak lagi milih Symfony 8.x pas resolve dependency. **BELUM bisa
    diverifikasi jalan di sandbox ini** (Composer & akses ke
    `packagist.org`gak tersedia di sini), jadi **WAJIB dijalankan &
    dicek manual**: hapus folder`vendor/`lama, jalankan`composer
    update`di lokal (PHP 8.3), pastikan`composer.lock`yang baru
    resolve semua paket Symfony ke garis`7.3.x`/`7.4.x`(bukan`8.x`
    lagi), lalu ulang smoke-test dasar (`php artisan --version`, buka
    halaman Home). Kalau masih ada 1-2 paket dependency lain yang
    maksa Symfony 8 (`composer why-not symfony/http-foundation 7.4`
    bakal nunjukin kalau ada conflict), baru pertimbangkan opsi kedua:
    minta Rumahweb upgrade PHP ke 8.4 (kalau hostingnya nanti nyediain).

2.  **Bug sama kayak kemarin, kejadian lagi di modul Izin/Cuti &
    Lembur.** `app/Http/Requests/Employee/StoreLeaveRequestRequest.php`
    ternyata isinya ketuker jadi isi class `StoreOvertimeRequestRequest`
    — sama persis pola kejadiannya kayak `UpdateEmployeeRequest` kemarin
    (file lama ke-overwrite pas nulis fitur baru Fase 7, harusnya bikin
    file baru). Efeknya:
    - **Karyawan → Ajukan Izin/Cuti** (`LeaveRequestController::store()`)
      bakal `Class not found` — class `StoreLeaveRequestRequest` yang
      asli gak ada file-nya.
    - **Karyawan → Ajukan Lembur** (`OvertimeRequestController::store()`)
      JUGA bakal `Class not found` — walau isinya textually ada, dia
      nyangkut di file dengan nama yang salah, jadi autoload Composer
      (yang cocokin nama file = nama class) gak nemuin.

    Diperbaiki dengan cara yang sama: pisah ke 2 file yang benar.
    Untung repo ini udah ada riwayat git, jadi `StoreLeaveRequestRequest`
    dipulihkan PERSIS dari commit `937da89` (bukan ditulis ulang dari
    nol) — termasuk validasi saldo cuti tahunan yang sempat ketinggalan
    kalau direkonstruksi manual. `StoreOvertimeRequestRequest` dipindah
    ke file `app/Http/Requests/Employee/StoreOvertimeRequestRequest.php`
    sendiri.

    **Bonus temuan dari cek ulang git history**: perbaikan
    `UpdateEmployeeRequest` di ronde sebelumnya (2026-09-08 pagi) ternyata
    kehilangan 1 aturan validasi asli — larangan `manager_id` nunjuk ke
    diri sendiri (karyawan gak boleh jadi atasannya sendiri), yang ada
    di versi asli commit `da33da5` tapi kelewat pas direkonstruksi manual
    dari baca kode doang (bukan dari git). Udah ditambahin balik di file
    yang sama.

**Pengecekan otomatis tambahan yang udah lolos** (biar makin yakin gak
ada bug sejenis yang kelewat):

- Semua class `FormRequest`/Controller di `app/` dicek satu-satu, nama
  class-nya harus sama persis nama file-nya (persis pola bug di atas)
  — sekarang semua cocok, gak ada lagi yang ketuker.
- Semua pemanggilan `view('...')` di seluruh Controller (35 pemanggilan
  unik) dicek, file blade-nya harus ada — semua ketemu.
- Semua route di `routes/web.php` yang nunjuk ke `[Controller::class,
'method']` (62 referensi) dicek, class & method-nya harus ada —
  semua ketemu, termasuk yang pakai `use ... as Alias`.
- Semua file PHP di `app/`, `database/`, `routes/` lolos `php -l`
  (syntax check) — gak ada typo penulisan PHP.

**Belum bisa dites/divalidasi lebih lanjut di sandbox ini** (PHP 8.4
gak tersedia buat diinstall): jalannya migration end-to-end
(`php artisan migrate:fresh --seed`), dan smoke-test tiap alur lewat
browser. Checklist testing manual yang udah ditulis di bagian
"🩹 Perbaikan Bug Fase 7" di atas TETAP berlaku, ditambah 2 ini:

- **Ajukan Izin/Cuti**: login karyawan mana aja → Izin/Cuti → isi form
  → submit → harus berhasil (bukan error), muncul di riwayat status
  Pending. Coba juga ajuin cuti tahunan yang jumlah harinya lebih dari
  sisa saldo → harus ditolak validasi dengan pesan sisa saldo, bukan
  malah kesimpen.
- **Ajukan Lembur**: login karyawan mana aja → Lembur → isi tanggal +
  alasan → submit → harus berhasil, muncul di riwayat status Pending.
  Coba ajuin 2x tanggal yang sama sebelum yang pertama diputus →
  yang kedua harus ditolak validasi ("sudah punya pengajuan aktif").

### 🔍 Audit Ulang Menyeluruh (2026-09-08, ronde 3 — cek parity prototype v18 & konsistensi dokumentasi)

Ronde ini beda sandbox dari ronde 1 & 2 — kali ini **PHP 8.3 berhasil
diinstall** (paket `noble/main`, bukan `noble-updates` yang 404 di
mirror ini), jadi bisa dicek otomatis, bukan cuma baca kode manual.
Tapi **PHP 8.4 tetap gak tersedia** di repo Ubuntu 24.04 manapun yang
bisa diakses sandbox ini (cuma ada lewat PPA pihak ketiga yang gak di-
whitelist), jadi klaim kritis ronde 2 (vendor butuh PHP 8.4+) dicoba
dikonfirmasi ulang dengan cara lain, bukan dijalankan penuh:

1. **Konfirmasi ulang blocker PHP 8.4 — masih valid, dan sekarang
   kebukti langsung (bukan cuma baca `composer.json`).** Coba jalanin
   `php artisan --version` pakai PHP 8.3.6 asli → persis kejadian yang
   diprediksi: fatal `RuntimeException` dari
   `vendor/composer/platform_check.php` ("require PHP >= 8.4.1, you
   are running 8.3.6"). Dicek lebih dalam: `php -l` langsung ke
   `vendor/symfony/http-foundation/Request.php` → **parse error
   sungguhan** di baris 117 (`public ParameterBag $attributes { set {
... } }`, syntax _property hooks_ PHP 8.4). Jadi ini bukan cuma
   soal `platform_check.php` yang bisa di-bypass — filenya sendiri
   secara harfiah gak valid buat parser PHP 8.3 ke bawah. Kesimpulan
   ronde 2 soal ini **akurat, gak perlu revisi**.
2. **Re-cek otomatis semua yang diklaim "udah dites" di ronde 1 & 2 —
   semua konsisten, gak ada regresi.** Pakai PHP 8.3 buat: `php -l` ke
   seluruh isi `app/`, `database/`, `routes/` (nol syntax error);
   tokenize tiap file `app/**/*.php` buat mastiin nama class = nama
   file (nol yang ketuker lagi — perbaikan `UpdateEmployeeRequest`,
   `UpdateOfficeSettingRequest`, `StoreLeaveRequestRequest`,
   `StoreOvertimeRequestRequest` dari ronde 1 & 2 terkonfirmasi masih
   bener); cocokin 35 pemanggilan `view()` di seluruh `app/` ke file
   Blade yang ada (semua ketemu); cocokin semua `[Controller::class,
'method']` di `routes/web.php` + `routes/auth.php` ke class/method
   yang beneran ada (semua ketemu, termasuk yang lewat alias `use ...
as`).
3. **README (bagian "Cara Menjalankan") ketinggalan info — sudah
   dibetulin di atas.** Peringatan sebelum absen dipakai bilang
   koordinat kantor "masih placeholder titik Monas", padahal isi
   `OfficeSettingSeeder.php` yang sebenarnya sekarang alamat Jl. Raya
   Tapos No.43, Depok (`-6.4069, 106.8880`) — bukan Monas. Kemungkinan
   sudah diganti ke alamat asli/alamat baru di ronde sebelumnya tapi
   catatan peringatannya lupa disesuaikan. **Belum bisa dipastikan dari
   sini apakah alamat Depok itu memang lokasi kantor WSM yang
   sebenarnya atau masih placeholder lain** — itu perlu dikonfirmasi
   manusia, bukan sesuatu yang bisa diverifikasi dari kode. README
   sudah diubah supaya gak menyesatkan (gak bilang "masih Monas" lagi),
   plus nambahin catatan buat konfirmasi manual itu.
4. **Ditemukan 1 klaim provenance yang salah di komentar kode (bukan
   README) — sudah dibetulin.** Komentar `MODULES` di
   `app/Models/DashboardAccess.php` bilang modul `legal` & `it`
   "ditambah ... dari prototype v18". Dicek langsung ke `DASHBOARD_MODULES`
   di `index.html` prototype (sumber tunggal buat daftar modul yang
   bisa di-assign per-user) → cuma isi 7 key
   (`work,budget,royalty,kpi,people,contracts,payroll`), gak pernah ada
   `legal`/`it` di situ maupun di pemanggilan `canViewModule()`/
   `canManageModule()`/`accessLevel()` manapun di seluruh file
   prototype. Yang ada di prototype cuma section **LEGAL** & **IT** di
   sidebar CEO Dashboard — itu tetap/role-gated ke CEO, BUKAN modul
   `dashboard_access` yang bisa didelegasikan. README sendiri sebenarnya
   udah benar (nyebut Legal & IT sebagai "(BARU)" di roadmap Fase 14/15,
   bukan porting dari prototype) — cuma komentar di file model yang
   kelewat nulis "dari prototype v18". Komentar sudah dikoreksi supaya
   jelas: 7 modul dasar = persis prototype, `legal`/`it` = desain baru
   WSM Office sendiri (delegasi akses Legal/IT ke staf, bukan cuma
   CEO), bukan hasil porting.
5. **Konsistensi lain yang ikut dicek dan AMAN (gak ada temuan baru):**
   kebijakan WFO di `OfficeSettingSeeder` (`radius_meters=200`,
   `work_start_time=09:30`, `normal_end_time=20:00`) persis kebijakan
   v18 di README prototype; akun `DemoSeeder` (nama, email, role) persis
   tabel di bagian "Cara Menjalankan"; daftar 9 modul di migration
   `add_legal_and_it_modules_to_dashboard_access` konsisten dengan enum
   di model (gak ada mismatch DB vs kode).

**Belum bisa dites di sandbox ini** (masih sama seperti ronde 2, PHP
8.4 tetap gak ada): migration end-to-end
(`php artisan migrate:fresh --seed`) dan smoke-test lewat browser.
Checklist manual di bagian "🩹 Perbaikan Bug Fase 7" & poin
Izin/Cuti-Lembur di ronde 2 di atas **tetap jadi langkah wajib**
sebelum fitur-fitur itu dianggap kelar — belum ada satupun yang
tervalidasi jalan beneran end-to-end, baru lolos cek statis (syntax,
nama class/file, referensi view & route).

### 🔍 Audit Ulang Menyeluruh (2026-09-09, ronde 4 — fokus App Mode/Home, karena diakses semua karyawan)

Ronde ini beda fokus dari ronde 1-3 (yang lebih ke bug/syntax): baca
ulang `WOS_2_0_App_v32/index.html` (1MB, ~3000 baris) sisi Home App
karyawan dari fungsi `renderEmployeeHome` dkk, dibandingkan baris per
baris ke `resources/views/employee/home.blade.php` +
`Employee\HomeController` + `layouts/employee.blade.php` yang beneran
ada di kode. Tidak menemukan gap baru di luar yang sudah tercatat di
"Langkah Selanjutnya" — laporan ini konfirmasi ulang + detail supaya
Fase 9 gampang dieksekusi tanpa re-audit dari nol.

**Sudah PERSIS sesuai prototype (dicek isi, bukan cuma judul):**

- Hero salam ("Halo, {nama} 👋" + tanggal), job title + divisi di
  bawah tanggal — cocok `employeeCelebrationMarkup`/header v18.
- Kartu absen: toggle Kantor/WFH (Lapangan/Gigs juga sudah ada, malah
  Laravel-nya sesi multi-check-in/out yang prototype baru punya di
  v18 ke atas), test lokasi + mini map + radius, foto selfie opsional,
  status "sedang bekerja"/"absensi selesai".
- Banner "Cuti Tim Bulan Ini" (cuti_tahunan & izin_pribadi doang,
  izin_sakit privat) — persis kebijakan v18.
- Kartu "Info dari Owner" (memo): read/unread badge, tandai baca,
  sembunyikan/tampilkan, reply thread — persis `employeeMemoMarkup`.
- **Dashboard Access — 7 modul inti** (`work`, `budget`, `royalty`,
  `kpi`, `people`, `contracts`, `payroll`): key, label, DAN deskripsi
  di `DashboardAccess::MODULES` (Laravel) dicocokkan literal ke
  `DASHBOARD_MODULES` (prototype, baris 1202-1210) — **sama persis,
  nol drift**. `legal`/`it` sebagai modul ke-8/9 juga sudah dilabeli
  benar di komentar sebagai desain baru WSM, bukan porting.
- Routing generik modul placeholder (`dashboard/{module}`) vs
  modul `work` yang sudah punya controller sendiri — urutan
  registrasi route di `web.php` sudah benar (grup `work.` didaftar
  SEBELUM `/{module}` generik), jadi tidak ke-intercept placeholder.

**Konfirmasi ulang: bagian Home yang BELUM ada, semuanya sudah
tercakup di cakupan Fase 9 (baris "Langkah Selanjutnya" poin 3 di
atas) — bukan temuan baru, tapi berikut detail per item biar
langsung actionable:**

1. **Milestones (Birthday & Work Anniversary)** — prototype selalu
   nampilin ini di Home (ada sejak versi paling awal `v7`, bukan fitur
   baru v18), tapi belum ada sama sekali di
   `employee/home.blade.php`. **Catatan penting:** kolom
   `users.birth_date` dan `users.join_date` **SUDAH ADA** di database
   (dipakai buat Fase 2 Master Karyawan & perhitungan cuti) — jadi ini
   **cuma butuh 1 partial view + sedikit logic tanggal di
   `HomeController`, TIDAK butuh migration baru**. Kandidat quick win
   pertama dari Fase 9 karena effort kecil, dampak kelihatan ke semua
   karyawan.
2. **My Work Tracker** (daftar task/item pribadi milik karyawan) —
   butuh `work_items` (sudah ada modelnya dari Data Layer Fase 9-16,
   tinggal query + card UI di Home).
3. **Shared Calendar** (tombol dari dalam My Work Tracker, modal 14
   hari workload tim dari deadline task, BUKAN kalender pribadi) —
   nunggu #2 selesai duluan (sumber datanya sama, `work_items.due`).
4. **My KPI** (mini card skor KPI pribadi di Home, ringkas — beda
   dari halaman KPI & Performance penuh Fase 10) — butuh tabel `kpis`
   (sudah ada modelnya).
5. **Latest Attendance embedded di Home** — prototype nampilin 3-5
   riwayat absen terakhir LANGSUNG di Home (bukan cuma link). Laravel
   sekarang cuma kasih link "Riwayat" di bottom-nav ke halaman
   terpisah (`employee/attendance/history.blade.php`). Beda posisi
   ini **efeknya kecil** (data & halamannya sudah ada, cuma soal
   ditaruh di 2 tempat atau 1) — bisa disamakan cepat kapan aja,
   tidak perlu nunggu Fase 9 penuh kalau mau dikerjakan duluan.
6. **Work Dashboard entry point di Home** (`secretaryConsoleMarkup` —
   kartu shortcut ke Work Control buat role tertentu) — sudah ADA
   padanannya (tombol "Kelola Tim" & "Dashboard" di header
   `layouts/employee.blade.php`, beda posisi tapi fungsinya
   ketemu), jadi item ini **statusnya sudah cukup**, bukan gap.

**Prioritas eksekusi App Mode yang disarankan (dari yang paling
murah ke paling mahal, karena semua ini kelihatan seluruh karyawan):**

1. Milestones (quick win, no migration).
2. Latest Attendance embedded di Home (quick win, no migration, no
   model baru — tinggal UI).
3. My Work Tracker (butuh controller + view baru buat CRUD/list
   `work_items` versi karyawan, ini beban utama Fase 9).
4. Shared Calendar (menyusul #3).
5. My KPI mini card (butuh controller/view kecil buat `kpis` versi
   employee, bisa paralel sama #3-4).
6. Baru sesudah itu masuk ke sisi ADMIN Fase 9 (Projects, Work
   Tracker board penuh, Timeline Calendar, MoM) yang dipakai
   Owner/Manajer/HRD/role dengan `dashboard_access=work,manage` —
   README "Langkah Selanjutnya" sudah benar naruh App Mode duluan
   sebelum ini, tinggal dieksekusi urutannya.

**Belum bisa dites di sandbox ini** (masih sama kendala ronde
sebelumnya — PHP 8.4 tidak tersedia): perbandingan di atas murni dari
baca kode statis (isi Blade view, Controller, dan prototype
`index.html`), bukan hasil klik langsung di browser. Checklist
manual E2E dari ronde 1-3 tetap wajib dijalankan sebelum rilis.

### ✅ Dikerjakan (2026-09-09): Milestones + Latest Attendance + My KPI

3 quick win dari ronde 4 di atas (poin 1, 2, 5 — yang independen dari
Projects/Work Tracker board) **sudah diimplementasikan**, TIDAK ada
migration baru (semua kolom/tabel yang dipakai sudah ada dari Fase
2/10):

- **`app/Models/User.php`** — 3 method baru: `nextBirthdayOccurrence()`,
  `nextWorkAnniversaryOccurrence()` (padanan `occurrenceInfo()` di
  prototype, termasuk geser 29 Februari ke 28 Februari di tahun
  non-kabisat), `serviceDurationLabel()` (padanan `serviceDuration()`).
  Dipanggil langsung dari view (`auth()->user()->...`), sama pola
  kayak job_title/divisi di Fase 8 — sengaja TIDAK lewat Controller.
- **`app/Models/Kpi.php`** — 3 method baru: `achievementBadgeClass()`,
  `progressBarClass()` (ambang batas >=90 hijau/>=60 kuning/sisanya
  merah, sama persis prototype), `formatNumber()` (angka ringkas,
  buang trailing zero).
- **`app/Http/Controllers/Employee/HomeController.php`** — nambah
  query `$latestAttendance` (5 sesi absen terakhir milik sendiri,
  lintas bulan) dan `$kpis` (KPI Active/Completed milik sendiri, urut
  `due_date`), dioper ke view.
- **`resources/views/employee/_milestones.blade.php`** (baru) — kartu
  Milestones (Lama Bekerja/Birthday/Anniversary), padanan
  `employeeCelebrationMarkup`.
- **`resources/views/employee/_kpi.blade.php`** (baru) — kartu My KPI,
  padanan `employeeKpiMarkup`. Nampilin pesan "KPI belum diset..."
  kalau kosong (Fase 10 UI Owner buat isi KPI belum ada — kartu ini
  baru keliatan isinya setelah ada baris `kpis` lewat
  `tinker`/seeder/Fase 10).
- **`resources/views/employee/attendance/_history-card.blade.php`**
  (baru) — partial 1 kartu riwayat absen, di-extract dari
  `history.blade.php` biar bisa dipakai ulang di kartu "Latest
  Attendance" pada Home TANPA duplikasi markup.
- **`resources/views/employee/attendance/history.blade.php`** — diubah
  supaya `@include` partial di atas (perilaku halaman ini TIDAK
  berubah, cuma refactor markup ke partial).
- **`resources/views/employee/home.blade.php`** — tambah
  `@include('employee._milestones')` tepat setelah hero,
  `@include('employee._kpi')` sebelum kartu memo "Info dari Owner",
  dan section "Latest Attendance" (5 kartu + tombol "Lihat semua" ke
  `employee.attendance.history`) di paling bawah, sebelum
  `@endsection`. Urutan section LAIN yang sudah ada (banner cuti,
  kartu absen, memo) **sengaja tidak diubah/dipindah** — cuma nambah,
  biar risiko regresi kecil (prototype v18 taruh Milestones & memo di
  urutan berbeda dari Laravel sekarang; menyamakan urutan penuh
  ditunda, bukan scope quick win ini).

**Flow hasil akhir buat dicek manual** (Home karyawan, urutan
top-to-bottom setelah perubahan):

1. Hero ("Halo, {nama}") + job title/divisi.
2. **[BARU] Milestones** — 3 kartu: Lama Bekerja, Birthday, Work
   Anniversary (atau pesan "belum diset" kalau `birth_date`/
   `join_date` kosong).
3. Banner "Cuti Tim Bulan Ini" (kalau ada).
4. Warning absen lupa checkout (kalau ada).
5. Status cuti hari ini ATAU kartu absen (mode Kantor/WFH/Lapangan/
   Gigs, test lokasi, dst — tidak berubah).
6. **[BARU] My KPI** — grid kartu KPI aktif, atau pesan kosong.
7. Kartu "Info dari Owner" (memo forum — tidak berubah).
8. **[BARU] Latest Attendance** — 5 kartu riwayat absen terakhir +
   tombol "Lihat semua" ke halaman Riwayat penuh.

**Checklist manual yang perlu dijalankan** (belum tervalidasi di
sandbox ini, PHP 8.4 masih belum ada):

- `php artisan migrate:fresh --seed`, lalu buka Home login sebagai
  Aldora/Gepeng (2 karyawan `DemoSeeder`) — cek kartu Milestones
  nongol dan angkanya masuk akal berdasarkan `birth_date`/`join_date`
  di seeder.
- Kalau `DemoSeeder` belum isi `birth_date` buat semua user, cek juga
  tampilan "belum diset" (jangan sampai error/blank kalau null).
- `php artisan tinker` → buat 1-2 baris `Kpi::create([...])` manual
  buat 1 user demo → refresh Home → cek kartu My KPI muncul dengan
  badge warna & progress bar yang sesuai (coba 1 KPI overachieve
    > 100%, 1 KPI di bawah 60%, 1 tanpa `owner_note`).
- Absen masuk-pulang 1-2 kali (beda mode) → cek "Latest Attendance" di
  Home ke-update dan kartunya identik visual dengan yang di halaman
  Riwayat (`/app/riwayat`) — soalnya sekarang 1 partial yang sama.
- Buka halaman Riwayat (`/app/riwayat`) itu sendiri → pastikan TIDAK
  ada regresi dari refactor partial (tampilan harus identik dengan
  sebelum perubahan).
- Cek responsive mobile (kartu Milestones & My KPI pakai
  `grid-cols-1 sm:grid-cols-3` / `sm:grid-cols-2` — pastikan gak
  numpuk aneh di layar sempit).

**Belum dikerjakan (sengaja, di luar scope quick win ini):** My Work
Tracker & Shared Calendar tetap ditunda sampai ada fondasi admin
Projects + Work Tracker board (lihat diskusi jalur eksekusi di atas)
— dikerjain duluan cuma bakal jadi widget kosong tanpa cara isi data
dari UI.

### 🔍 Audit Ulang Menyeluruh (2026-09-09, ronde 5 — cek `WOS_2_0_App_v32` (prototype terbaru) vs kode WSM-Office aktual, fokus App Mode)

Ronde ini beda dari ronde 4: bukan baca ulang dari ingatan, tapi
ekstrak & `grep` langsung isi `WOS_2_0_STANDALONE_v32.html` (fungsi
`renderEmployeeHome`, `employeeNav`, `employeeTasksMarkup`,
`teamCelebrationMarkup`, `paidLeaveBannerV18`, dst — versi PALING
AKHIR di file, karena prototype nulis fungsi yang sama berkali-kali
sebagai overlay per-versi v10→v32) lalu dicocokkan baris-per-baris ke
`resources/views/employee/*.blade.php`,
`app/Http/Controllers/Employee/HomeController.php`, `routes/web.php`,
dan migration/model Fase 9-17. Kesimpulan utama: **klaim ronde 4 masih
akurat 100%**, ditambah 2 temuan baru yang ronde 4 lewatkan, plus
konfirmasi ulang status Fase 10-17 di level kode (bukan cuma migration
ada, tapi benar-benar belum ada Controller/route/view sama sekali).

**Terkonfirmasi ulang (sesuai, tidak ada drift):**

- Bottom-nav App Mode prototype (`employeeNav`) cuma 5 tombol: Home,
  Riwayat, Absen (center), Request, Profile — **persis** struktur
  route `employee.*` yang ada sekarang. Tidak ada tombol nav terpisah
  buat Work Tracker/Projects di prototype manapun — makanya "My Work
  Tracker" & "Shared Calendar" memang harus nempel di dalam Home
  (bukan halaman/route baru), sesuai rencana ronde 4.
- Modul placeholder `dashboard/module.blade.php` (budget, royalty,
  kpi, people, legal, it) dicek isinya: literal cuma nampilin pesan
  "Modul ini belum dibangun" — dicek juga tidak ada Controller selain
  `Dashboard\Work\MemoController`. Jadi klaim README "Fase 10-17 belum
  ada sebagai fitur user-facing" **akurat di level kode**, bukan cuma
  checklist yang lupa dicoret.
- `My Work Tracker` di prototype (`employeeTasksMarkup`, versi
  terakhir baris ~2461) ternyata sudah berkembang jauh dari deskripsi
  ronde 4: ada filter Project/Category/Progress, tombol Expand
  All/Collapse All, dan tiap task jadi `<details>` collapsible — bukan
  cuma list kartu sederhana. **Actionable buat nanti:** waktu Fase 9
  My Work Tracker beneran dikerjain, sertakan filter ini dari awal,
  jangan versi minimal dulu baru nambah filter belakangan (biar gak
  bikin migration UI 2x).

**Temuan baru (belum tercatat di ronde 1-4):**

1. **"Team Moments" (`teamCelebrationMarkup`) — beda dari Milestones
   pribadi yang sudah dikerjakan.** Prototype nampilin section
   terpisah di Home: daftar ulang tahun/anniversary **rekan kerja
   lain** yang jatuh dalam 45 hari ke depan (bukan milik user yang
   login). Dirender persis setelah `employeeTasksMarkup` (My Work
   Tracker), sebelum `secretaryConsoleMarkup`. **Belum ada sama
   sekali** di `employee/home.blade.php` atau di manapun — dicek
   dengan `grep -rn "Team Moments|celebrationRows|teamCelebration"`
   ke `resources/` & `app/`, nol hasil. Datanya bisa dihitung dari
   kolom yang sudah ada (`birth_date`, `join_date` di tabel `users`,
   sama seperti Milestones pribadi), jadi ini **quick win murah**
   (tidak butuh migration baru) — cocok masuk sebelum atau bareng My
   Work Tracker, tidak perlu nunggu Fase 9 penuh.
2. **Banner "Paid Leave" (`paidLeaveBannerV18`) belum tampil di Home**,
   padahal datanya sudah ada dan sudah dipakai di 2 tempat lain
   (`User::remainingAnnualLeaveDays()`, dipakai di
   `employee/profile.blade.php` dan `employee/leave/index.blade.php`).
   Prototype nampilin banner besar (sisa hari, dari berapa hari
   accrued, sudah terpakai berapa, tanggal reset di anniversary join
   date) tepat setelah Milestones di Home — di Laravel sekarang info
   ini cuma bisa dilihat kalau buka halaman Profile atau Request
   Cuti secara terpisah, tidak muncul proaktif di Home. **Quick win
   murah lain** (nol migration baru, tinggal 1 partial + include di
   Home) karena logic `remainingAnnualLeaveDays()` sudah ada, cuma
   perlu tanggal reset (anniversary join date) dihitung juga (mirip
   `nextWorkAnniversaryOccurrence()` yang sudah ada di `User.php`).

**Prioritas App Mode yang disarankan (update dari ronde 4, urutan
termurah → termahal, karena semua dilihat SELURUH karyawan):**

1. ~~Milestones~~ — **selesai** (ronde 4).
2. ~~Latest Attendance embedded di Home~~ — **selesai** (ronde 4).
3. ~~My KPI mini card~~ — **selesai** (ronde 4, kosong sampai Fase 10
   ada cara isi data, itu memang disengaja).
4. **[BARU] Team Moments** — quick win, nol migration, bisa
   dikerjakan sekarang juga, tidak perlu nunggu Fase 9.
5. **[BARU] Paid Leave banner di Home** — quick win, nol migration,
   bisa dikerjakan sekarang juga, tidak perlu nunggu Fase 9.
6. **My Work Tracker** (+ filter Project/Category/Progress dari awal,
   bukan versi minimal) — beban utama Fase 9, butuh Controller + view
   baru buat `work_items` versi karyawan.
7. **Shared Calendar** — menyusul #6, sumber data sama
   (`work_items.due`, plus filter Project & PIC).
8. Baru sesudah itu sisi ADMIN Fase 9 (Projects CRUD, Work Tracker
   board penuh ala Owner/Manajer, Timeline Calendar) dan Fase 10-17
   lain sesuai urutan roadmap di atas — belum ada satupun
   Controller/route untuk ini di kode saat ini.

**Belum bisa dites di sandbox ini** (sama seperti ronde 1-4 — PHP 8.4
tidak tersedia di sandbox ini): audit murni baca kode statis (isi
Blade view, Controller, migration, dan `grep` langsung ke prototype
`WOS_2_0_STANDALONE_v32.html`), bukan hasil klik langsung di browser.
Checklist manual E2E dari ronde 1-4 tetap wajib dijalankan sebelum
rilis, ditambah 2 item baru di atas begitu diimplementasikan.

### ✅ Dikerjakan (2026-09-09): Team Moments + Paid Leave banner

2 quick win poin 4-5 dari ronde 5 di atas **sudah diimplementasikan**,
TIDAK ada migration baru (semua kolom yang dipakai — `birth_date`,
`join_date`, `annual_leave_entitlement` — sudah ada dari Fase 2/5):

- **`app/Http/Controllers/Employee/HomeController.php`** — nambah
  query `$teamMoments`: loop semua `User`, ambil
  `nextBirthdayOccurrence()`/`nextWorkAnniversaryOccurrence()` yang
  jatuh ≤45 hari ke depan, urut tanggal, ambil 6 teratas — padanan
  `celebrationRows(45).slice(0,6)` di prototype. Paid Leave banner
  SENGAJA tidak lewat sini (sama pola Milestones — lihat poin di
  bawah).
- **`resources/views/employee/_team-moments.blade.php`** (baru) —
  section "Team Moments", padanan `teamCelebrationMarkup()`. Baris:
  ikon (🎂/✦) + nama + jenis (Birthday/Work Anniversary + tahun ke
  berapa kalau anniversary) di kiri, tanggal di kanan. Section
  disembunyikan total kalau `$teamMoments` kosong (sama seperti
  prototype).
- **`resources/views/employee/_paid-leave.blade.php`** (baru) —
  banner lime (`bg-brand-lime`, token warna yang SUDAH ADA di
  `resources/css/app.css` dan **persis** `--lime:#b4ef4b` di
  prototype — nol drift warna) menampilkan sisa cuti tahunan + bubble
  bulat `remaining/entitlement` di kanan, padanan `paidLeaveBannerV18`.
  Sengaja manggil `auth()->user()` langsung (pola sama Milestones),
  bukan lewat Controller. **Deviasi yang disengaja & didokumentasikan
  di kode:** pakai `annual_leave_entitlement` FLAT (sama seperti
  Profile & Request Cuti yang sudah ada), BUKAN hasil proration
  bulanan tahun pertama seperti `leaveCycle()` di prototype — logic
  accrual bulanan itu belum ada di modul Cuti manapun di WSM-Office,
  jadi tidak direkayasa dadakan cuma buat banner ini. Kalau proration
  bulanan dibutuhkan beneran, itu harus jadi perubahan terpisah yang
  konsisten di Profile + Request Cuti + banner ini sekaligus, bukan
  quick win.
- **`resources/views/employee/home.blade.php`** — nambah
  `@include('employee._paid-leave')` tepat setelah `_milestones`,
  sebelum banner "Cuti Tim Bulan Ini" (posisi **persis** urutan
  prototype: Milestones → Paid Leave → banner cuti tim). Nambah
  `@include('employee._team-moments')` sebelum section "Latest
  Attendance" — **beda posisi** dari prototype (prototype naruhnya
  setelah "My Work Tracker", yang belum dibangun di Fase 9), posisi
  saat ini adalah yang paling dekat dari urutan section yang SUDAH ADA
  sekarang. Catatan sudah ditulis di komentar
  `_team-moments.blade.php` — pindahkan ke bawah My Work Tracker
  begitu Fase 9 selesai biar urutannya balik persis prototype.

**Flow hasil akhir buat dicek manual** (Home karyawan, urutan
top-to-bottom setelah perubahan, tambahan ronde 5 ditandai **[BARU]**,
tambahan ronde 4 sebelumnya tetap ditandai _[ronde 4]_ biar jelas):

1. Hero ("Halo, {nama}") + job title/divisi.
2. Milestones _[ronde 4]_ — Lama Bekerja, Birthday, Work Anniversary.
3. **[BARU] Paid Leave** — banner lime, sisa cuti tahunan + bubble
   `remaining/entitlement`, atau pesan "Join date belum diset / leave
   tidak berlaku" kalau `join_date` kosong.
4. Banner "Cuti Tim Bulan Ini" (kalau ada — tidak berubah).
5. Warning absen lupa checkout (kalau ada — tidak berubah).
6. Status cuti hari ini ATAU kartu absen (tidak berubah).
7. My KPI _[ronde 4]_ — grid kartu KPI aktif.
8. Kartu "Info dari Owner" (memo forum — tidak berubah).
9. **[BARU] Team Moments** — list ulang tahun/anniversary rekan kerja
   dalam 45 hari, disembunyikan total kalau tidak ada yang jatuh
   dalam rentang itu.
10. Latest Attendance _[ronde 4]_ — 5 kartu riwayat absen terakhir.

**Checklist manual yang perlu dijalankan** (belum tervalidasi di
sandbox ini, PHP 8.4 masih belum ada):

- `php artisan migrate:fresh --seed`, login sebagai 2+ karyawan demo
  yang `birth_date`/`join_date`-nya beda-beda → cek Team Moments
  cuma muncul kalau ada yang jatuh ≤45 hari dari hari ini, dan
  section-nya hilang total (bukan kotak kosong) kalau tidak ada.
- Cek Team Moments nongolin SEMUA karyawan termasuk yang lagi login
  sendiri (sengaja disamakan ke prototype, lihat catatan deviasi di
  atas) — kalau ini kerasa aneh/duplikat sama Milestones pas dicek
  visual, exclude-diri-sendiri adalah opsi valid buat dibahas
  terpisah, BUKAN silent fix.
- Cek banner Paid Leave: angka `remaining` di banner harus SAMA
  dengan angka "Sisa Cuti Tahunan" di halaman Profile & Request Cuti
  (3 tempat harus konsisten karena sama-sama pakai
  `remainingAnnualLeaveDays()`).
- Coba user dengan `join_date` NULL → banner harus tampil versi
  "belum diset", bukan error/blank.
- Cek responsive mobile: banner Paid Leave (grid 1 kolom di mobile,
  `1fr auto` di ≥sm) dan Team Moments (list, bukan grid, harusnya
  aman di semua lebar layar).
- Regression check halaman Profile & Request Cuti — pastikan TIDAK
  ada perubahan di sana (quick win ini cuma nambah 2 partial baru +
  1 query baru, tidak menyentuh Controller/Model Cuti yang sudah ada).

**Update (2026-09-09): My Work Tracker & Shared Calendar — SELESAI,
plus koreksi audit ronde 5.** Baris di atas ("Belum dikerjakan...
sertakan filter Project/Category/Progress dari awal") **SALAH** dan
digantikan seksi di bawah — dibiarkan di sini (dicoret secara tekstual
lewat baris ini, bukan dihapus) sesuai prinsip README ini: catat
riwayatnya, jangan diam-diam diedit seolah gak pernah salah.

### ✅ Dikerjakan (2026-09-09): My Work Tracker + Shared Calendar

**Koreksi audit sebelumnya dulu, sebelum daftar file:** ronde 5 bilang
versi final prototype punya filter Project/Category/Progress di widget
Home. **Itu salah** — pas beneran ditelusuri ulang baris-per-baris di
`WOS_2_0_STANDALONE_v32.html`, filter itu (`V12_TASK_FILTERS`, baris 1183) ternyata dari versi v12 yang SUDAH DIGANTIKAN. Versi
`employeeTasksMarkup()` PALING AKHIR (baris 1857) sama sekali gak
punya filter — cuma daftar item open (max 8) + tombol "▦ Shared
Calendar" yang buka modal 14 hari (`openSharedWorkloadCalendar()`,
baris 1858, JUGA tanpa filter Project/PIC — klaim ronde 5 soal itu
juga salah). Diimplementasikan di bawah PERSIS versi final ini, bukan
versi ber-filter yang disebut sebelumnya.

**File baru:**

- **`app/Http/Controllers/Employee/WorkTrackerController.php`** —
  cuma 1 action, `calendar()`, buat Shared Calendar. My Work Tracker
  sendiri TIDAK punya Controller/route sendiri — datanya dihitung di
  `HomeController` (pola sama Milestones/My KPI/Team Moments) karena
  dia embedded langsung di Home, bukan halaman terpisah.
- **`resources/views/employee/_work-tracker.blade.php`** — widget "My
  Work Tracker" di Home. Header + subtitle "{open} open · {done}
  done" + tombol "▦ Shared Calendar", lalu daftar sampai 8 item open
  (lewat `_work-item-card.blade.php`). BEDA dari Team Moments: section
  ini TETAP tampil walau kosong (nampilin "Tidak ada item aktif"),
  bukan disembunyikan total — ini bagian tetap Home App Mode, bukan
  notifikasi kondisional.
- **`resources/views/employee/_work-item-card.blade.php`** — 1 kartu
  task, padanan `taskCardMarkupV9()`. Badge progress pakai token warna
  yang SUDAH ADA (`badge-wsm-green/yellow/red/blue/gray`, nol token
  baru). Focus pill (HARI INI/BESOK/MINGGU INI/dst) pakai hex warna
  **persis** sama prototype (`#ffe876`, `#e6f4e9`, dst — nol drift
  warna), inline style karena bukan token Tailwind yang ada di
  `app.css`.
- **`resources/views/employee/work-tracker/calendar.blade.php`** —
  halaman Shared Calendar. **Deviasi UI yang disengaja & dicatat di
  kode:** prototype nampilin ini sebagai grid horizontal 14 kolom
  dalam MODAL (wajar buat SPA layar lebar) — WSM-Office bukan SPA dan
  App Mode-nya mobile-first (`max-w-140`, sama kayak halaman Riwayat),
  jadi diadaptasi jadi LIST VERTIKAL (1 hari = 1 section, ditumpuk ke
  bawah) sebagai halaman tersendiri (bukan modal overlay). Isi & sumber
  data 100% sama — cuma tata letaknya yang beda, bukan datanya.

**File yang diubah:**

- **`app/Http/Controllers/Employee/HomeController.php`** — tambah
  query `$myWorkItems` (WorkItem dengan `pic_employee_id` = user yang
  login, eager-load `project`), split jadi `$openWorkItems` &
  `$doneWorkItemsCount`.
- **`resources/views/employee/home.blade.php`** — `@include('employee.
_work-tracker')` ditaruh tepat setelah My KPI. **Team Moments
  DIPINDAH** dari posisi sementara (sebelum Latest Attendance, ronde 5)
  ke tepat SETELAH My Work Tracker — sekarang urutan Home PERSIS
  prototype: Milestones → Paid Leave → Cuti Tim → status
  absen/cuti hari ini → My KPI → **My Work Tracker (baru)** → **Team
  Moments (posisi final)** → Memo → Latest Attendance.
- **`database/seeders/DemoSeeder.php`** — tambah 1 `Project` ("Album
  Q3 Release") + 7 `WorkItem` contoh, sengaja nyebar tanggalnya
  (kelewat, hari ini, besok, minggu ini/depan, tanpa tanggal, sudah
  Done) biar SEMUA warna focus pill kelihatan pas dites manual, tanpa
  My Work Tracker & Shared Calendar bakal keliatan kosong terus abis
  seed (belum ada UI Owner/PIC buat bikin WorkItem — itu sisi ADMIN
  Fase 9 yang masih di roadmap, lihat penutup di bawah).

**Yang SENGAJA belum dikerjakan (v1, view-only):** prototype punya
dropdown ubah status + tombol "Update Note" langsung di tiap task-card
(`employeeModeView=true` di `taskCardMarkupV9`) — itu butuh
route+validasi+authorization nulis (PATCH WorkItem) yang belum digarap
di putaran ini, biar gak nyampur sama scope "tampilkan data yang udah
ada" murni. Karyawan sekarang cuma bisa LIHAT My Work Tracker, belum
bisa update progress/notes dari situ.

**Flow hasil akhir Home App Mode** (top → bottom, LENGKAP, semua
ronde ditandai):

1. Hero.
2. Milestones _[ronde 4]_.
3. Paid Leave banner _[2026-09-09, quick win]_.
4. Banner "Cuti Tim Bulan Ini".
5. Warning absen lupa checkout (kalau ada).
6. Status cuti hari ini ATAU kartu absen.
7. My KPI _[ronde 4]_ — kosong sampai Fase 10.
8. **My Work Tracker [BARU]** — max 8 item open + tombol Shared
   Calendar, klik → halaman `/app/kalender-tim` (14 hari, seluruh tim,
   item punya sendiri di-highlight biru).
9. **Team Moments [posisi final]** — disembunyikan total kalau kosong.
10. Kartu "Info dari Owner" (memo forum).
11. Latest Attendance _[ronde 4]_.

**Checklist manual yang perlu dijalankan** (belum tervalidasi di
sandbox ini):

> **Update (2026-09-09, setelah dicoba beneran oleh Arga):**
> `migrate:fresh --seed` sempat GAGAL di `DemoSeeder` — MySQL nolak
> insert ke `projects` karena kolom `slug` (NOT NULL, unique) kosong:
> `Field 'slug' doesn't have a default value`. **Sudah diperbaiki** di
> `database/seeders/DemoSeeder.php` — `Project::create([...])` sekarang
> set `'slug' => Project::uniqueSlugFrom('Album Q3 Release')` eksplisit,
> gak lagi ngandelin `static::creating()` hook di `Project::booted()`
> yang seharusnya auto-isi slug kalau kosong.
>
> **Belum terjawab tuntas (perlu diinvestigasi terpisah, BUKAN
> diklaim sudah beres):** kenapa hook `booted()` itu gak jalan pas
> mass-assignment lewat `create()`? `Project` pakai atribut PHP
> `#[Fillable([...])]` (dicek: ini class ASLI Laravel 13,
> `Illuminate\Database\Eloquent\Attributes\Fillable`, bukan sesuatu
> yang custom/salah pakai) yang literally mendaftarkan `slug` sebagai
> fillable — jadi bukan itu masalahnya. Kemungkinan ada interaksi
> aneh antara atribut ini dan event `creating` yang didaftarkan di
> `static::booted()`, tapi belum dibuktikan langsung (gak ada PHP di
> sandbox audit ini buat `dd()`/tinker beneran). **Dampak nyata buat
> ke depan:** kalau nanti Fase 9 sisi Admin bikin form "Create
> Project" yang manggil `Project::create([...])` TANPA eksplisit isi
> `slug` (ngandelin hook), kemungkinan bakal kena error yang SAMA.
> Sampai akar masalahnya jelas, **jangan andelin `booted()` hook itu**
> — selalu isi `slug` eksplisit tiap kali `Project::create()`/`save()`
> dipanggil (sama pola yang udah dipakai `JobOpeningController::store()`
> buat `JobOpening`, itu juga gak pernah ngandelin hook otomatis).

- `php artisan migrate:fresh --seed` → login Aldora → My Work Tracker
  harus nampilin 3 item (kelewat/merah, hari ini/kuning, sudah Done
  gak ikut ke-list) plus 1 item "Update inventaris" tanpa tanggal
  (fokus "NOT URGENT"/hijau). Login Gepeng → 2 item (besok/kuning,
  minggu ini/biru tergantung tanggal seed).
- Klik "Shared Calendar" dari Aldora → harus lihat SEMUA item tim
  (termasuk punya Gepeng & Kanaya), item Aldora sendiri ditandai
  "Punyaku" + background biru muda.
- Cek warna focus pill di browser cocok persis deskripsi di atas (kalau
  ada yang meleset, kemungkinan besar tanggal seed relatif sudah
  lewat dari asumsi "hari ini" pas seeding — wajar, bukan bug).
- Regression check: My KPI, Team Moments, Memo, Latest Attendance
  masih di posisi & isi yang benar setelah widget baru disisipkan di
  tengah.

---

## 🔐 Dashboard permission-based, bukan role (2026-09-09)

**Laporan awal:** Aldora (role `karyawan` biasa, tapi punya
`dashboard_access` modul `work` level `manage`) kelihatan link
**"Absensi"** di sidebar `layouts.app` — padahal dia gak pernah dikasih
akses ke modul itu sama sekali.

**Akar masalah, ditemukan lewat baca kode langsung (bukan asumsi):**
link `<a href="{{ route('attendance.recap.index') }}">Absensi</a>` di
`resources/views/layouts/app.blade.php` **SAMA SEKALI TANPA `@if`** —
nempel gitu aja di luar kondisi apa pun. Siapa pun yang nyampe
`layouts.app` (lewat modul APA PUN yang dia punya akses beneran, dalam
kasus Aldora lewat modul `work`) otomatis lihat link ke Rekap Absensi
juga. Ini murni bug dari struktur lama: 4 fitur (Rekap Absensi,
Persetujuan Izin/Cuti, Persetujuan Lembur, Rekrutmen) digerbang lewat
Laravel role middleware (`role:manajer,owner,hrd` dkk) yang blanket
per-JABATAN, bukan per-ORANG — begitu seseorang (lewat modul lain)
punya jalan masuk ke `layouts.app`, sidebar-nya nyampur nampilin semua
link tanpa cek ulang apakah dia beneran ditugasin ke situ.

**Kenapa ini juga soal ke-akuratan terhadap prototype, bukan cuma
selera:** ditelusuri langsung ke `WOS_2_0_App_v32` — prototype
TERNYATA 100% permission-based buat SEMUA fitur admin/manajemen
(`canViewModule()`/`canManageModule()` dari `dashboard_access`), TIDAK
PERNAH pakai konsep "role karyawan" buat nge-gate fitur apa pun. Fungsi
`suggestAccessFromRole()` di prototype cuma auto-fill FORM waktu Owner
BIKIN karyawan baru (convenience, berdasarkan teks jabatan yang
diketik) — bukan enforcement. "People & Leave" (`people`, salah satu
dari 7 modul awal `DASHBOARD_MODULES`) di prototype literally
mendeskripsikan dirinya "People directory & leave monitoring" —
persis cakupan Rekap Absensi + Persetujuan Izin/Cuti/Lembur. Jadi versi
WSM-Office yang role-gated untuk fitur-fitur ini SEBENARNYA drift dari
prototype sejak awal (keputusan breakdown Fase 6 dulu: "Fase 4/5 tetap
role-based" — itu keputusan yang sekarang dibalik).

**1 nuansa penting yang TIDAK diubah** — dicek langsung di fungsi
`rolePeopleDashboard()` prototype, ada disclaimer literal di situ:
_"Dashboard access tidak mengubah authority approval. Approval tetap
mengikuti direct supervisor."_ Modul `people` di prototype cuma
ngatur siapa BISA MASUK layar People/Leave — siapa yang BOLEH
approve/reject request TERTENTU tetap murni relasi atasan-langsung
(`manager_id`). WSM-Office `LeaveRequestController::canDecide()` /
`OvertimeRequestController::canDecide()` **SUDAH** persis begitu sejak
awal (dicek, tidak disentuh sama sekali di refactor ini) — jadi bagian
ini TIDAK berubah, cuma gerbang MASUK layarnya yang diperbaiki.

### Apa yang diubah

| Sebelumnya (role-based)                               | Sekarang (permission-based)                                             |
| ----------------------------------------------------- | ----------------------------------------------------------------------- |
| `role:manajer,owner,hrd` → Rekap Absensi              | `module:people,view` (koreksi jam: `module:people,manage`)              |
| `role:manajer,owner` → Persetujuan Izin/Cuti & Lembur | `module:people,view` (approve/reject tetap `manager_id`, tidak berubah) |
| `role:hrd,owner` → Rekrutmen                          | `module:recruitment,view`/`manage` (modul BARU, lihat di bawah)         |
| Link "Absensi" di sidebar: **TANPA gate sama sekali** | `canViewModule('people')`                                               |
| Tombol "Kelola Tim" (App Mode): role check            | `canViewModule('people')`                                               |

**Modul `recruitment` (BARU, ke-10) BUKAN dari prototype** — dicek
langsung, prototype v32 gak punya fitur rekrutmen sama sekali (nol
hasil grep "rekrutmen"/"recruitment"/"pelamar" di seluruh file).
Ditambahin dengan alasan sama kayak `legal`/`it` sebelumnya: biar Owner
bisa cabut/kasih akses ke staf tertentu satu-satu, bukan blanket ke
semua orang berrole `hrd` — justru itulah tujuan refactor ini.

**Yang SENGAJA TIDAK diubah:** grup `owner.*` (Karyawan, Struktur
Organisasi, Pengaturan Kantor, assign Dashboard Access) tetap
`role:owner` murni. Owner di prototype memang konsep akun super-admin
terpisah (`accessLevel()` hardcode `'manage'` semua modul), bukan
sesuatu yang didelegasikan lewat `dashboard_access` — jadi ini BUKAN
kasus yang perlu "dibenerin".

### File yang berubah (path asli project)

**Baru:**

- `database/migrations/2026_09_09_010000_add_recruitment_module_to_dashboard_access.php`
  — nambah `recruitment` ke enum `dashboard_access.module` (pola
  persis migration `legal`/`it` sebelumnya).
- `database/migrations/2026_09_09_010100_backfill_dashboard_access_for_manajer_hrd.php`
  — **PENTING buat deploy ke instalasi yang UDAH JALAN** (bukan
  instalasi baru): tanpa ini, semua user existing berrole
  `manajer`/`hrd` di database production/staging bakal LANGSUNG
  kehilangan akses pas migration ini di-deploy. Migration ini nyamain
  akses SETELAH refactor supaya PERSIS SAMA dengan SEBELUM refactor
  (`manajer` → `people:view`, `hrd` → `people:manage` +
  `recruitment:manage`) — nol perubahan akses buat siapa pun di hari
  deploy. Owner baru bisa cabut satu-satu lewat halaman "Dashboard
  Access" kalau memang ada yang gak seharusnya punya akses itu.
  Idempotent (`insertOrIgnore`), aman di-run ulang. `down()` sengaja
  no-op (mencabut akses banyak orang sekaligus lewat rollback otomatis
  itu keputusan Owner, bukan yang boleh kejadian diam-diam).

**Diubah:**

- `app/Models/DashboardAccess.php` — tambah entri `recruitment` ke
  `MODULES` + doc-comment lengkap alasan refactor.
- `routes/web.php` — 4 grup route (`attendance.recap.*`,
  `approval.leave.*`, `approval.overtime.*`, `recruitment.*`) pindah
  dari `role:...` ke `module:...`. Grup `dashboard-lock.*` dilebarin ke
  `role:karyawan,manajer,owner,hrd` (dulu cuma manajer/owner/hrd) biar
  karyawan biasa yang punya dashboard_access modul apa pun tetap bisa
  pakai "Kunci Dashboard".
- `app/Http/Controllers/Auth/LoginController.php` — redirect khusus
  role `hrd` ke halaman Pelamar DIHAPUS (biar gak 403 kalau
  `recruitment` udah dicabut Owner dari orang itu). Semua role selain
  Owner sekarang seragam ke `/app/home`.
- `resources/views/layouts/employee.blade.php` — tombol "Kelola Tim"
  → `canViewModule('people')`.
- `resources/views/layouts/app.blade.php` — **fix bug utama**: link
  "Absensi" (dulu nol gate) → `canViewModule('people')`. Link
  "Persetujuan" → `canViewModule('people')`. Link "Pelamar"/"Lowongan"
  → `canViewModule('recruitment')`. Link "← App Saya" disederhanakan
  jadi tanpa kondisi (siapa pun yang nyampe layout ini otomatis anggota
  grup route `employee.*`).
- `resources/views/owner/dashboard-access/edit.blade.php` — teks
  banner yang dulu bilang "Persetujuan izin/cuti & rekap absensi tetap
  ngikutin role" (sekarang SALAH) dibetulin, jelasin nuansa approval
  authority di atas.
- `database/seeders/DemoSeeder.php` — Kanaya (manajer) dikasih
  `people:view`, Rania (hrd) dikasih `people:manage` +
  `recruitment:manage` — biar fresh seed tetap punya akses yang sama
  kayak sebelum refactor (padanan migration backfill di atas, tapi
  buat instalasi baru).
- Beberapa doc-comment controller (`RecapController`,
  `DashboardAccessController`, `JobOpeningController`) diupdate biar
  gak nyebut role lama.

### Flow hasil akhir untuk dicek

**Sebagai Aldora** (`karyawan`, akses `work:manage` doang):

1. Login → masuk `/app/home` (bukan lagi ada redirect aneh).
2. Buka modul Work Control lewat tombol "Dashboard" di header →
   masuk `layouts.app`.
3. **Cek sidebar: link "Absensi" & "Persetujuan" TIDAK BOLEH muncul
   sama sekali** (ini bug yang dilaporkan — harus hilang sekarang).
4. Coba akses langsung `/absensi` via URL → harus kena 403.

**Sebagai Kanaya** (`manajer`, backfill `people:view`):

1. Login → tombol "Kelola Tim" di header App Mode harus tetap muncul.
2. Buka Rekap Absensi & Persetujuan → harus tetap bisa (akses lama
   dipertahankan lewat seed baru).
3. Approve/reject leave request Aldora/Gepeng (bawahan langsung) →
   harus tetap bisa (logic `canDecide()` tidak berubah).

**Sebagai Rania** (`hrd`, backfill `people:manage` + `recruitment:manage`):

1. Login → landing di `/app/home` (bukan lagi auto-redirect ke
   Pelamar).
2. Sidebar harus nampilin Pelamar, Lowongan, DAN Absensi (karena
   `people:manage` juga buka Rekap Absensi) + tombol koreksi jam
   absensi harus muncul (manage-level).
3. Coba approve leave request yang BUKAN bawahannya → harus tetap
   kena 403 (approval authority tidak berubah oleh `people` access).

**Sebagai Owner:** semua di atas harus tetap bisa diakses tanpa
perubahan apa pun (akses Owner hardcode, tidak lewat tabel
`dashboard_access`).

**Nuansa minor yang perlu diketahui, BUKAN bug:** karena `module:
people,view` sekarang jadi gerbang bareng buat Rekap Absensi & layar
Persetujuan, seseorang dengan HANYA `people` access (tanpa jadi atasan
siapa pun) BISA membuka halaman Persetujuan (bakal keliatan kosong,
`canDecide()` tetap menolak approve). Ini konsekuensi wajar dari
"1 modul gerbangin beberapa layar terkait", bukan celah keamanan
(data tetap terlindungi oleh `canDecide()`), tapi dicatat di sini biar
Arga sadar kalau ada yang nanya kenapa Rania bisa lihat halaman
Persetujuan walau dia gak punya siapa pun bawahan.

**Keputusan yang SENGAJA belum diambil (perlu konfirmasi Arga kalau
mau diubah):** apakah `people` access-level (`view` vs `manage`) juga
harus mempersempit `scopedUsers()` di `RecapController` (misalnya
`view` → subordinate doang, `manage` → recursive/semua)? Sekarang
levelnya CUMA ngatur boleh-koreksi-jam atau enggak, scope datanya
masih 100% role-based lama (Owner/HRD → semua, sisanya → bawahan
turunan) — dicek di prototype, `rolePeopleDashboard()` juga TIDAK
mempersempit tabel berdasarkan view/manage (keduanya lihat SEMUA
karyawan), jadi keputusan WSM-Office saat ini (scope masih role-based)
sebenarnya **lebih ketat** dari prototype, bukan longgar — aman
dibiarkan, tapi dicatat di sini sebagai keputusan yang bisa
didiskusikan lagi kalau perlu.

### Langkah selanjutnya

1. **Jalankan checklist manual di atas** (3 skenario user: Aldora,
   Kanaya, Rania) — terutama poin #3 Aldora, itu inti dari laporan bug.
2. `php artisan migrate` di staging/production yang UDAH ADA datanya
   — pastikan migration backfill jalan duluan sebelum siapa pun
   komplain kehilangan akses.
3. Cek satu-satu apakah ada Manajer/HRD LAIN di database production
   yang aksesnya perlu di-fine-tune lewat halaman "Dashboard Access"
   (misalnya: ada Manajer yang seharusnya CUMA lihat timnya sendiri,
   bukan approve — sekarang Owner BISA atur itu per-orang, dulu
   enggak bisa sama sekali).
4. **My Work Tracker sisi ADMIN** (Fase 9 lanjutan) — Projects CRUD,
   Work Tracker board penuh (assign task ke siapa pun, bukan cuma
   lihat), update progress/notes dari App Mode (yang di-skip di v1
   ini) — ini beban terbesar yang masih tersisa dari Fase 9, dan
   sekarang jadi lebih jelas juga harus digerbang `module:work`
   (bukan role), konsisten sama refactor ini.
5. Timeline Calendar (Owner-side, beda dari Shared Calendar
   karyawan) & Fase 10-17 lain — belum tersentuh, masih di roadmap
   seperti sebelumnya.
