<?php

/**
 * config/page_guides.php
 * ---------------------------------------------------------------------
 * Panduan halaman dashboard (tombol "? Panduan" di kanan bawah layout
 * `layouts.app`, lihat resources/views/partials/page-guide.blade.php).
 *
 * Dua bagian:
 *
 * 1. `routes` — peta NAMA ROUTE -> kunci panduan. Pola boleh pakai `*`
 *    (dicocokkan dengan Str::is), urutan dicek dari atas, yang pertama
 *    cocok menang. Halaman yang route-nya tidak ada di sini simply tidak
 *    punya tombol panduan (bukan error). Halaman baru? Tambah 1 baris di
 *    sini + 1 entri di `guides`, TIDAK perlu menyentuh view-nya.
 *    (Alternatif: view boleh memaksa panduan tertentu lewat
 *    `@extends('layouts.app', ['pageGuide' => 'kunci'])`.)
 *
 * 2. `guides` — isi panduan per kunci:
 *    - title    : judul modal
 *    - summary  : 1-2 kalimat "halaman ini untuk apa"
 *    - access   : (opsional) 'owner' -> label "Khusus Owner"; atau nama
 *                 modul dashboard_access (mis. 'work') -> label dinamis
 *                 "Akses kamu: Manage/View" sesuai user yang login
 *    - sections : heading => daftar butir. Butir = string biasa, atau
 *                 [label, penjelasan] (label ditebalkan). Semua teks
 *                 di-escape saat dirender, jadi JANGAN pakai HTML di sini.
 *
 * Teks sengaja berbahasa Indonesia (penjelasan fitur = teks yang rawan
 * salah paham); nama fitur ditulis persis seperti label di UI.
 * ---------------------------------------------------------------------
 */

return [

    'routes' => [
        // --- Area Owner ---
        'owner.dashboard' => 'owner-dashboard',
        'owner.contact-messages.index' => 'contact-messages',
        'owner.organization' => 'organization',
        'owner.employees.index' => 'employees',
        'owner.employees.create' => 'employee-form',
        'owner.employees.edit' => 'employee-form',
        'owner.employees.access.edit' => 'employee-access',
        'owner.office-settings.edit' => 'office-settings',

        // --- Hub modul ---
        'dashboard.index' => 'module-hub',

        // --- Work Control ---
        'dashboard.work.index' => 'memo-forum',
        'dashboard.work.create' => 'memo-form',
        'dashboard.work.edit' => 'memo-form',
        'dashboard.work.tracker.*' => 'work-tracker',
        'dashboard.work.calendar' => 'timeline-calendar',
        'dashboard.work.meetings.index' => 'meetings',
        'dashboard.work.meetings.create' => 'meeting-form',
        'dashboard.work.meetings.edit' => 'meeting-form',
        'dashboard.work.meetings.show' => 'meeting-detail',

        // --- HR Admin ---
        'attendance.recap.index' => 'attendance-recap',
        'attendance.recap.show' => 'attendance-detail',
        'approval.leave.index' => 'approval-leave',
        'approval.overtime.index' => 'approval-overtime',
        'approval.attendanceCorrection.index' => 'approval-correction',
        'dashboard.kpi.index' => 'kpi',
        'dashboard.kpi.create' => 'kpi-form',
        'dashboard.kpi.edit' => 'kpi-form',
        'dashboard.contracts.index' => 'contracts',
        'dashboard.contracts.create' => 'contract-form',
        'dashboard.contracts.edit' => 'contract-form',
        'dashboard.payroll.index' => 'payroll',
        'dashboard.payroll.show' => 'payroll-detail',

        // --- Finance, Royalty, Legal ---
        'dashboard.budget.index' => 'budget',
        'dashboard.budget.create' => 'budget-form',
        'dashboard.budget.edit' => 'budget-form',
        'dashboard.royalty.index' => 'royalty',
        'dashboard.royalty.create' => 'royalty-form',
        'dashboard.royalty.edit' => 'royalty-form',
        'dashboard.legal.index' => 'legal',
        'dashboard.legal.create' => 'legal-form',
        'dashboard.legal.edit' => 'legal-form',

        // --- IT ---
        'dashboard.it.index' => 'audit-log',
        'dashboard.it.changelog.index' => 'changelog',
        'dashboard.it.changelog.create' => 'changelog-form',
        'dashboard.it.changelog.edit' => 'changelog-form',

        // --- Recruitment ---
        'recruitment.openings.index' => 'openings',
        'recruitment.openings.create' => 'opening-form',
        'recruitment.openings.edit' => 'opening-form',
        'recruitment.applications.index' => 'applications',
        'recruitment.applications.show' => 'application-detail',
        'recruitment.applications.convert*' => 'application-convert',

        // --- Export & Import ---
        'dashboard.export-import.index' => 'export-import',
        'dashboard.export-import.preview' => 'export-preview',
        'dashboard.export-import.import.show' => 'import-upload',
        'dashboard.export-import.import.preview' => 'import-preview',
    ],

    'guides' => [

        // =============================================================
        // AREA OWNER
        // =============================================================

        'owner-dashboard' => [
            'title' => 'Dashboard Owner',
            'summary' => 'Ringkasan cepat kondisi tim hari ini. Halaman ini untuk dibaca saja: tiap kartu punya link ke halaman lengkapnya.',
            'access' => 'owner',
            'sections' => [
                'Yang bisa kamu lihat' => [
                    ['Ritme mingguan', 'fokus kerja dan mode (WFO/WFH) tiap hari Senin sampai Jumat.'],
                    ['Kehadiran Hari Ini', 'jumlah karyawan yang sudah absen dibanding total karyawan. Link "Lihat rekap" membuka Rekap Absensi.'],
                    ['Pengajuan Pending', 'total izin/cuti yang menunggu keputusan plus absen yang perlu dicek. Link "Lihat" membuka Persetujuan.'],
                    ['Tugas Berjalan', 'task Work Tracker yang belum berstatus Done atau Postpone. Link "Lihat board" membuka Work Tracker.'],
                    ['Kontrak Akan Habis', 'kontrak karyawan yang berakhir dalam 30 hari ke depan. Link "Lihat" membuka Contract Monitoring.'],
                    ['Birthday & Work Anniversary', 'ulang tahun dan hari jadi kerja yang datang dalam 60 hari ke depan.'],
                    ['Service Length', 'daftar karyawan berdasarkan lama bekerja. Link "People" membuka halaman Karyawan.'],
                ],
                'Perlu diketahui' => [
                    'Kalau di Service Length muncul tulisan "Set join date", isi Tanggal Bergabung di data karyawan yang bersangkutan.',
                    'Tombol "Kunci Dashboard" di bawah sidebar mengunci sesi kamu. Untuk membukanya lagi perlu memasukkan password akun.',
                    'Tombol "App Saya" di sidebar membawa kamu kembali ke tampilan karyawan (absen, pengajuan, profil).',
                ],
            ],
        ],

        'contact-messages' => [
            'title' => 'Pesan Kontak',
            'summary' => 'Kumpulan pesan yang dikirim pengunjung lewat form Kontak di halaman publik.',
            'access' => 'owner',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Baca pesan', 'pesan terbaru ada di atas, lengkap dengan nama, email, waktu kirim, dan isi pesan.'],
                    ['Tandai Dibaca', 'menghapus badge "Baru" dari pesan itu.'],
                ],
                'Perlu diketahui' => [
                    'Angka merah di menu "Pesan Kontak" pada sidebar adalah jumlah pesan yang masih berstatus Baru. Angkanya turun setiap kali kamu menandai pesan sebagai dibaca.',
                    'Halaman ini belum punya fitur balas. Untuk membalas, kirim email langsung ke alamat pengirim.',
                    'Kalau pesan banyak, daftar dibagi per halaman (20 pesan per halaman).',
                ],
            ],
        ],

        'organization' => [
            'title' => 'Struktur Organisasi',
            'summary' => 'Bagan hierarki tim yang disusun otomatis dari data Atasan Langsung tiap karyawan aktif.',
            'access' => 'owner',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Lihat struktur', 'siapa melapor ke siapa, dari Owner sampai level paling bawah.'],
                    ['Kelola Karyawan', 'tombol di pojok atas untuk pindah ke daftar karyawan.'],
                ],
                'Perlu diketahui' => [
                    'Halaman ini hanya untuk dilihat. Untuk mengubah bagan, ubah field "Atasan Langsung" lewat Karyawan, lalu Edit.',
                    'Karyawan yang dinonaktifkan tidak ikut tampil di bagan.',
                ],
            ],
        ],

        'employees' => [
            'title' => 'Karyawan',
            'summary' => 'Daftar semua akun internal. Di sini kamu mengelola akun, role, atasan, dan hak akses modul tiap orang.',
            'access' => 'owner',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['+ Tambah Karyawan', 'membuat akun baru.'],
                    ['Cari dan filter', 'cari berdasarkan nama, email, atau divisi. Filter Role untuk menyaring jenis akun.'],
                    ['Tampilkan yang nonaktif', 'centang lalu Terapkan untuk melihat akun yang sudah dinonaktifkan.'],
                    ['Edit', 'ubah data karyawan.'],
                    ['Akses', 'atur modul dashboard apa saja yang boleh dibuka orang itu (tidak tersedia untuk akun Owner).'],
                    ['Nonaktifkan / Aktifkan', 'akun nonaktif tidak bisa login, tetapi riwayat datanya tetap tersimpan. Bisa diaktifkan lagi kapan saja.'],
                ],
                'Perlu diketahui' => [
                    'Role hanyalah label jabatan. Yang menentukan menu apa yang terlihat adalah "Akses" per modul.',
                    'Untuk memasukkan banyak karyawan sekaligus, pakai Export & Import, kartu Manajemen Karyawan.',
                ],
            ],
        ],

        'employee-form' => [
            'title' => 'Form Karyawan',
            'summary' => 'Isi atau ubah data akun dan data kerja seorang karyawan.',
            'access' => 'owner',
            'sections' => [
                'Arti tiap isian' => [
                    ['Email dan Password', 'dipakai untuk login. Saat mengedit, kosongkan Password Baru kalau tidak ingin mengganti password.'],
                    ['Role', 'label jabatan (Owner, Manajer, HRD, Karyawan). Hak akses modul diatur terpisah lewat tombol Akses.'],
                    ['Atasan Langsung', 'menentukan siapa yang menyetujui izin, cuti, lembur, dan koreksi presensi orang ini, sekaligus posisinya di Struktur Organisasi.'],
                    ['Tanggal Bergabung', 'dipakai untuk Work Anniversary dan Service Length.'],
                    ['Tanggal Lahir', 'dipakai untuk pengingat Birthday.'],
                    ['Jatah Cuti Tahunan', 'jumlah hari cuti tahunan per tahun (bawaan 12 hari).'],
                ],
                'Data Payroll (opsional)' => [
                    ['Gaji Pokok', 'kalau dikosongkan, karyawan ini tidak muncul di daftar generate Payroll.'],
                    ['Target Jam Kerja/Hari', 'acuan jam kerja harian karyawan.'],
                    ['Flat Rate Lembur', 'nominal per pengajuan lembur yang disetujui, dikalikan jumlah lembur disetujui bulan itu (bukan dihitung per jam).'],
                ],
                'Perlu diketahui' => [
                    'Minta karyawan baru mengganti password lewat menu Profil setelah login pertama.',
                ],
            ],
        ],

        'employee-access' => [
            'title' => 'Dashboard Access',
            'summary' => 'Atur modul dashboard apa saja yang boleh dibuka seorang karyawan, dan seberapa jauh.',
            'access' => 'owner',
            'sections' => [
                'Tiga level akses' => [
                    ['None', 'modul tidak terlihat dan tidak bisa dibuka.'],
                    ['View', 'hanya bisa melihat data modul.'],
                    ['Manage', 'bisa melihat sekaligus menambah, mengubah, dan menghapus data modul.'],
                ],
                'Perlu diketahui' => [
                    'Klik "Simpan Akses" agar perubahan berlaku. Menu sidebar orang itu langsung menyesuaikan.',
                    'Modul People & Leave adalah pintu masuk ke Rekap Absensi dan Persetujuan Izin/Cuti/Lembur. Meski begitu, siapa yang boleh menyetujui pengajuan tertentu tetap mengikuti atasan langsung. Akses di sini tidak mengubah hal itu.',
                    'Akun Owner selalu punya akses penuh, jadi tidak perlu diatur.',
                    'Setiap perubahan akses tercatat di Audit Log.',
                ],
            ],
        ],

        'office-settings' => [
            'title' => 'Pengaturan Kantor',
            'summary' => 'Lokasi kantor, aturan jam kerja, dan warna aksen dashboard. Perubahan langsung berlaku untuk absen berikutnya.',
            'access' => 'owner',
            'sections' => [
                'Lokasi & Radius' => [
                    ['Nama, Alamat, Latitude, Longitude', 'titik kantor untuk pengecekan jarak saat absen. Cara cepat cari koordinat: buka lokasi kantor di Google Maps, klik kanan pada titiknya, koordinat otomatis tersalin.'],
                    ['Radius Toleransi', 'jarak maksimal (meter) dari titik kantor yang dianggap "di dalam radius".'],
                    ['Pengecekan geo', 'kalau dimatikan, absen mode Kantor tidak menghitung jarak sama sekali (diperlakukan seperti WFH).'],
                    ['Tandai di luar radius', 'kalau dimatikan, jarak tetap dicatat sebagai informasi tetapi tidak pernah dianggap masalah.'],
                ],
                'Jam Kerja Normal (WFO)' => [
                    ['Jam Mulai dan Jam Selesai', 'jam selesai juga dipakai untuk menutup otomatis sesi karyawan yang lupa absen pulang (jika tidak punya lembur disetujui).'],
                    ['Toleransi Telat', 'batas menit keterlambatan sebelum status jadi Terlambat.'],
                    ['Minimal Menit Kerja/Hari', 'kurang dari ini masuk status Kurang Jam Kerja dan dihitung per blok 60 menit di rekap bulanan.'],
                    ['Rate Potongan Kurang Jam', 'rupiah per blok 60 menit, dipakai Payroll untuk menghitung potongan otomatis.'],
                ],
                'Warna Aksen' => [
                    'Mengubah warna menu Dashboard/Owner dan menu Work Control di sidebar.',
                ],
                'Perlu diketahui' => [
                    'Absen tidak pernah diblokir karena radius, baik pengecekan geo dinyalakan maupun dimatikan.',
                    'Penutupan otomatis sesi tidak real-time. Ia terpicu saat ada aktivitas absen atau buka riwayat berikutnya, karena hosting tidak punya cron.',
                    'Kalau Rate Potongan Kurang Jam masih 0, potongan kurang jam di Payroll akan bernilai nol.',
                ],
            ],
        ],

        'module-hub' => [
            'title' => 'Dashboard',
            'summary' => 'Pintu masuk ke semua modul yang boleh kamu buka.',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Klik kartu modul', 'membuka modul itu. Badge di kartu menunjukkan level aksesmu (View atau Manage).'],
                    ['Menu sidebar', 'jalan pintas yang sama, dikelompokkan per bagian (People, Work Control, Finance, dan seterusnya).'],
                ],
                'Perlu diketahui' => [
                    'Modul yang tidak muncul berarti kamu belum diberi akses. Minta Owner mengaturnya lewat menu Karyawan, tombol Akses.',
                ],
            ],
        ],

        // =============================================================
        // WORK CONTROL
        // =============================================================

        'memo-forum' => [
            'title' => 'Memo Forum (MoM & Memo)',
            'summary' => 'Pengumuman internal dan catatan rapat singkat untuk tim, lengkap dengan balasan dari karyawan.',
            'access' => 'work',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['+ Tambah', 'buat Memo (pengumuman) atau Minutes of Meeting.'],
                    ['Edit', 'ubah isi memo.'],
                    ['Deactivate / Aktifkan', 'memo nonaktif tidak lagi ditampilkan ke karyawan, tetapi tidak dihapus.'],
                    ['Hapus', 'menghapus memo secara permanen.'],
                    ['Balas', 'membalas thread di bawah kartu memo sebagai manajemen.'],
                    ['Tab di atas', 'pindah ke Work Tracker, Timeline Calendar, atau Rapat & Action Item.'],
                ],
                'Arti tampilan kartu' => [
                    ['PINNED', 'memo tampil di kartu "Info dari Owner" pada Home semua karyawan.'],
                    ['Penerima', 'semua karyawan, atau hanya orang yang dipilih.'],
                    ['Read x/y', 'berapa penerima yang sudah membaca dari total penerima.'],
                    ['Hidden n', 'jumlah penerima yang menyembunyikan memo dari Home mereka.'],
                ],
                'Perlu diketahui' => [
                    'Angka merah di menu Memo Forum adalah balasan karyawan yang belum dilihat manajemen. Angkanya hilang otomatis begitu halaman ini dibuka.',
                    'Tombol Tambah, Edit, Hapus, dan Balas hanya muncul untuk akses Manage.',
                ],
            ],
        ],

        'memo-form' => [
            'title' => 'Form Memo / MoM',
            'summary' => 'Buat atau ubah pengumuman dan catatan rapat singkat.',
            'access' => 'work',
            'sections' => [
                'Arti tiap isian' => [
                    ['Jenis', 'Memo (pengumuman) atau Minutes of Meeting.'],
                    ['Tanggal Rapat dan Peserta', 'hanya untuk jenis MoM.'],
                    ['Penerima', 'Semua Karyawan, atau Karyawan Tertentu lalu centang siapa saja penerimanya.'],
                    ['Pin', 'centang agar memo tampil di kartu "Info dari Owner" pada Home semua karyawan.'],
                ],
                'Perlu diketahui' => [
                    'MoM di sini adalah catatan cepat. Untuk MoM lengkap dengan peserta dari tim dan action item per PIC, pakai tab Rapat & Action Item.',
                ],
            ],
        ],

        'work-tracker' => [
            'title' => 'Work Tracker',
            'summary' => 'Papan kanban semua task lintas project dan PIC, dikelompokkan menurut progress kerja.',
            'access' => 'work',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter Project', 'tampilkan task satu project saja, atau Semua Project.'],
                    ['Geser kartu', 'pindahkan kartu ke kolom lain untuk mengubah progress task (akses Manage).'],
                    ['+ Tambah Task', 'isi judul, project, section, PIC, due date, progress, priority, link, dan notes.'],
                    ['Menu titik tiga di kartu', 'Edit atau Hapus task.'],
                    ['Kelola Projects', 'tambah, ubah, atau hapus project (nama, warna, tanggal mulai/selesai, priority, status, lead, tracker URL, progress recap).'],
                ],
                'Arti tampilan' => [
                    ['Kolom', 'Pending, On Development, Follow Up, Confirmed, Done, dan Postpone.'],
                    ['Badge fokus di kartu', 'HARI INI, BESOK, MINGGU INI, KELEWAT, SELESAI, dan sebagainya. Dihitung otomatis dari due date, tidak bisa digeser manual.'],
                    ['Warna project', 'tiap project punya warna yang sama di board dan di Timeline Calendar.'],
                ],
                'Perlu diketahui' => [
                    'Menghapus project tidak menghapus task-nya. Task dipindah menjadi "Tanpa Project".',
                    'Akses View hanya bisa melihat board. Menggeser, menambah, mengubah, dan menghapus butuh Manage.',
                    'Untuk memasukkan banyak task dari Excel, pakai Export & Import, kartu Work Tracker.',
                ],
            ],
        ],

        'timeline-calendar' => [
            'title' => 'Timeline Calendar',
            'summary' => 'Kalender bersama berisi deadline Work Tracker seluruh tim. Bukan kalender pribadi.',
            'access' => 'work',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Panah dan Today', 'pindah bulan sebelumnya, bulan berikutnya, atau kembali ke bulan ini.'],
                    ['Filter Project dan PIC', 'tampilkan deadline project atau orang tertentu saja.'],
                ],
                'Arti tampilan' => [
                    ['Kotak berwarna di tanggal', 'satu task dengan deadline hari itu, warnanya mengikuti project. Arahkan kursor untuk melihat judul dan PIC lengkap.'],
                    ['+N', 'ada task lain di tanggal itu. Tiap tanggal hanya menampilkan 3 task.'],
                    ['Deretan hari di atas kalender', 'ritme kerja mingguan (fokus, WFO/WFH, jam) dari Senin sampai Jumat.'],
                ],
                'Perlu diketahui' => [
                    'Kalender hanya untuk dilihat. Untuk mengubah task atau deadline, buka Work Tracker.',
                ],
            ],
        ],

        'meetings' => [
            'title' => 'Rapat & Action Item',
            'summary' => 'Catatan rapat terstruktur: peserta dari tim, keputusan, dan action item per PIC yang bisa masuk ke Work Tracker.',
            'access' => 'work',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['+ Tambah MoM', 'buat catatan rapat baru (akses Manage).'],
                    ['Detail', 'lihat catatan lengkap, action item, dan kirim ringkasannya ke tim.'],
                    ['Edit dan Hapus', 'ubah atau hapus catatan rapat. Hapus bersifat permanen.'],
                ],
                'Arti tampilan kartu' => [
                    ['Sudah di-blast', 'ringkasan rapat ini sudah pernah dikirim ke karyawan sebagai memo.'],
                    ['Jumlah action item dan peserta tim', 'ringkasan isi catatan tanpa harus membuka detail.'],
                ],
            ],
        ],

        'meeting-form' => [
            'title' => 'Form MoM',
            'summary' => 'Isi catatan rapat lengkap dengan peserta dan action item.',
            'access' => 'work',
            'sections' => [
                'Arti tiap isian' => [
                    ['Tanggal, Jam, Project, Agenda', 'Jam dan Project boleh dikosongkan.'],
                    ['Peserta (dari tim)', 'centang karyawan yang hadir.'],
                    ['Peserta Tambahan', 'tulis nama tamu atau klien yang bukan karyawan.'],
                    ['Catatan dan Keputusan', 'isi pembahasan dan hasil rapat.'],
                    ['Action Items', 'satu baris per tugas: isi tugas, pilih PIC (atau ALL TEAM), dan due date. Tombol "Tambah Baris" menambah baris baru.'],
                    ['Masukkan action items ke Work Tracker otomatis', 'kalau dicentang, action item ikut menjadi task di Work Tracker.'],
                ],
            ],
        ],

        'meeting-detail' => [
            'title' => 'Detail MoM',
            'summary' => 'Catatan rapat lengkap: peserta, catatan, keputusan, dan action item.',
            'access' => 'work',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Edit', 'ubah catatan rapat (akses Manage).'],
                    ['Blast Summary', 'mengirim ringkasan rapat sebagai Memo ke semua karyawan.'],
                    ['Blast Ulang', 'muncul setelah pernah di-blast. Tiap blast membuat memo baru, bukan memperbarui memo lama.'],
                ],
                'Perlu diketahui' => [
                    'Di daftar Action Items terlihat PIC, due date, dan status task-nya di Work Tracker (jika action item dimasukkan ke sana).',
                    'PDF notulen rapat bisa diunduh lewat Export & Import, kartu Meetings / MoM.',
                ],
            ],
        ],

        // =============================================================
        // HR ADMIN
        // =============================================================

        'attendance-recap' => [
            'title' => 'Rekap Absensi',
            'summary' => 'Ringkasan kehadiran karyawan pada satu tanggal, dengan jalan ke riwayat bulanan tiap orang.',
            'access' => 'people',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Pilih tanggal', 'lihat kehadiran hari lain. Tombol "Hari Ini" mengembalikan ke hari ini.'],
                    ['Riwayat', 'buka riwayat absensi bulanan karyawan itu.'],
                ],
                'Arti tampilan' => [
                    ['Kartu ringkasan', 'Total, Hadir, Terlambat, Izin/Cuti, dan Belum Absen pada tanggal yang dipilih.'],
                    ['Kolom Radius', 'hanya untuk absen mode Kantor: Dalam radius atau Di luar radius. Tidak berarti absen ditolak, hanya penanda.'],
                    ['Status', 'Hadir, Terlambat, Sedang Bekerja, Kurang Jam Kerja, Lupa Absen Pulang, atau Belum Absen.'],
                ],
                'Perlu diketahui' => [
                    'Owner dan HRD melihat semua karyawan. Akun lain melihat dirinya sendiri dan seluruh bawahannya.',
                    'Karyawan yang sedang izin/cuti disetujui tidak dihitung sebagai Belum Absen.',
                    'Rekap bisa diunduh sebagai Excel atau PDF lewat Export & Import.',
                ],
            ],
        ],

        'attendance-detail' => [
            'title' => 'Riwayat Absensi Karyawan',
            'summary' => 'Absensi satu karyawan sepanjang satu bulan, hari demi hari.',
            'access' => 'people',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Bulan Lalu dan Bulan Depan', 'pindah bulan.'],
                    ['Lihat lokasi', 'membuka titik lokasi absen masuk atau pulang di Google Maps, lengkap dengan jaraknya dari kantor.'],
                    ['Lihat selfie', 'thumbnail foto absen masuk dan pulang (kalau ada).'],
                    ['Koreksi jam absen', 'ubah jam masuk dan/atau pulang. Alasan koreksi wajib diisi dan bisa dilihat karyawan.'],
                ],
                'Perlu diketahui' => [
                    'Setelah dikoreksi, kartu menampilkan siapa yang mengoreksi, kapan, dan alasannya.',
                    'Koreksi langsung membutuhkan akses Manage pada modul People. Karyawan sendiri bisa mengajukan koreksi lewat aplikasinya, lalu diputuskan di halaman Persetujuan Koreksi Presensi.',
                    'Panel "Izin/Cuti Bulan Ini" muncul di atas kalau karyawan punya pengajuan di bulan itu.',
                ],
            ],
        ],

        'approval-leave' => [
            'title' => 'Persetujuan Izin/Cuti',
            'summary' => 'Pengajuan izin dan cuti yang menunggu keputusan, beserta riwayat keputusannya.',
            'access' => 'people',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Tab status', 'Pending, Disetujui, Ditolak, Dibatalkan, atau Semua.'],
                    ['Setujui', 'menyetujui pengajuan.'],
                    ['Tolak', 'menolak pengajuan. Alasan penolakan wajib diisi.'],
                    ['Batalkan izin/cuti ini', 'membatalkan pengajuan yang masih pending atau sudah disetujui, selama tanggal selesainya belum lewat. Alasan wajib diisi.'],
                    ['Link di atas', 'pindah ke Persetujuan Lembur atau Persetujuan Koreksi Presensi.'],
                ],
                'Perlu diketahui' => [
                    'Owner melihat dan bisa memutuskan semua pengajuan. Akun lain hanya melihat dan memutuskan pengajuan bawahan langsungnya.',
                    'Akses modul People hanya membuka halaman ini. Yang berwenang memutuskan tetap atasan langsung.',
                    'Hanya cuti tahunan yang disetujui yang mengurangi jatah cuti karyawan.',
                    'Setiap keputusan tercatat di Audit Log.',
                ],
            ],
        ],

        'approval-overtime' => [
            'title' => 'Persetujuan Lembur',
            'summary' => 'Pengajuan lembur yang menunggu keputusan, beserta riwayat keputusannya.',
            'access' => 'people',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Tab status', 'Pending, Disetujui, Ditolak, Dibatalkan, atau Semua.'],
                    ['Setujui', 'menyetujui pengajuan lembur.'],
                    ['Tolak', 'menolak pengajuan. Alasan penolakan wajib diisi.'],
                    ['Batalkan lembur ini', 'membatalkan lembur yang pending atau sudah disetujui, selama tanggalnya belum lewat. Alasan wajib diisi.'],
                ],
                'Perlu diketahui' => [
                    'Owner memutuskan semua pengajuan. Akun lain hanya bawahan langsungnya.',
                    'Lembur yang disetujui dihitung Payroll dengan Flat Rate Lembur karyawan, per pengajuan disetujui.',
                    'Lembur yang disetujui juga mencegah sesi kerja ditutup otomatis di jam selesai normal.',
                ],
            ],
        ],

        'approval-correction' => [
            'title' => 'Persetujuan Koreksi Presensi',
            'summary' => 'Pengajuan karyawan untuk memperbaiki jam absen masuk atau pulangnya.',
            'access' => 'people',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Tab status', 'Pending, Disetujui, Ditolak, Dibatalkan, atau Semua.'],
                    ['Setujui', 'jam yang diminta karyawan langsung diterapkan ke data presensi.'],
                    ['Tolak', 'menolak pengajuan, alasan wajib diisi.'],
                ],
                'Perlu diketahui' => [
                    'Pengajuan yang disetujui ditandai "sudah diterapkan ke presensi".',
                    'Owner memutuskan semua pengajuan. Akun lain hanya bawahan langsungnya.',
                ],
            ],
        ],

        'kpi' => [
            'title' => 'KPI & Performance',
            'summary' => 'Target kinerja seluruh tim. Data yang sama tampil di kartu "My KPI" masing-masing karyawan.',
            'access' => 'kpi',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter Karyawan', 'tampilkan KPI satu orang saja.'],
                    ['+ Tambah KPI', 'buat target baru untuk seorang karyawan (akses Manage).'],
                    ['Edit dan Hapus', 'ubah progress atau hapus KPI. Hapus bersifat permanen.'],
                ],
                'Arti tampilan kartu' => [
                    ['Persen pencapaian', 'Progress Saat Ini dibagi Target. Hijau kalau 90% ke atas, kuning 60% sampai kurang dari 90%, merah di bawah 60%.'],
                    ['Weight dan Due', 'bobot KPI dan tenggat waktunya (jika diisi).'],
                ],
                'Perlu diketahui' => [
                    'Rekap KPI bisa diunduh sebagai Excel, dan KPI bisa diimpor massal, lewat Export & Import.',
                ],
            ],
        ],

        'kpi-form' => [
            'title' => 'Form KPI',
            'summary' => 'Tetapkan target kinerja untuk seorang karyawan dan perbarui progress-nya.',
            'access' => 'kpi',
            'sections' => [
                'Arti tiap isian' => [
                    ['Periode', 'label bebas, mis. Q3 2026.'],
                    ['Target dan Progress Saat Ini', 'angka target dan capaian sejauh ini. Persen pencapaian dihitung dari keduanya.'],
                    ['Unit', 'satuan angka, mis. %, pcs, Rp (opsional).'],
                    ['Weight %', 'bobot KPI ini dibanding KPI lain (opsional).'],
                    ['Status', 'Active, Completed, atau Archived.'],
                    ['Catatan Owner', 'catatan tambahan (opsional).'],
                ],
            ],
        ],

        'contracts' => [
            'title' => 'Contract Monitoring',
            'summary' => 'Arsip kontrak kerja karyawan: file, masa berlaku, dan catatan.',
            'access' => 'contracts',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter Karyawan', 'tampilkan kontrak satu orang saja.'],
                    ['Buka file', 'klik nama file di kartu kontrak untuk membukanya di tab baru. File bersifat privat dan hanya bisa dibuka setelah login dengan akses yang sesuai.'],
                    ['+ Upload Kontrak', 'unggah kontrak baru (akses Manage).'],
                    ['Edit dan Hapus', 'ubah data atau hapus kontrak. Hapus bersifat permanen.'],
                ],
                'Perlu diketahui' => [
                    'Badge "Segera Berakhir" muncul untuk kontrak yang berakhir dalam 30 hari. Jumlahnya juga tampil di Dashboard Owner.',
                    'Badge itu hanya bisa muncul kalau Tanggal Selesai diisi.',
                ],
            ],
        ],

        'contract-form' => [
            'title' => 'Form Kontrak',
            'summary' => 'Unggah atau perbarui kontrak kerja seorang karyawan.',
            'access' => 'contracts',
            'sections' => [
                'Arti tiap isian' => [
                    ['File Kontrak', 'PDF, Word, atau gambar, maksimal 10 MB. Saat mengedit, biarkan kosong kalau file tidak diganti.'],
                    ['Tanggal Mulai dan Selesai', 'opsional, tetapi Tanggal Selesai dibutuhkan agar peringatan "Segera Berakhir" bekerja.'],
                    ['Catatan', 'keterangan tambahan (opsional).'],
                ],
            ],
        ],

        'payroll' => [
            'title' => 'Payroll Overview',
            'summary' => 'Take home pay karyawan per bulan. Hasilnya disimpan sebagai riwayat, tidak dihitung ulang otomatis.',
            'access' => 'payroll',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Pilih Periode', 'lihat payroll bulan lain.'],
                    ['Generate Payroll', 'hitung payroll untuk periode terpilih. Kosongkan pilihan karyawan untuk memproses semua karyawan yang punya gaji pokok (akses Manage).'],
                    ['Detail', 'buka rincian payroll satu karyawan.'],
                ],
                'Arti tampilan' => [
                    ['Total Payroll', 'jumlah seluruh payroll yang sudah digenerate periode ini.'],
                    ['Belum Digenerate', 'karyawan bergaji yang belum punya payroll periode ini.'],
                    ['Belum Ada Gaji Pokok', 'karyawan tanpa gaji pokok. Lengkapi di data karyawan agar bisa digenerate.'],
                    ['Status', 'draft, finalized, atau paid.'],
                ],
                'Perlu diketahui' => [
                    'Generate ulang hanya menyentuh payroll berstatus draft. Yang sudah final atau dibayar dilewati dan tidak ditimpa.',
                    'Kalau muncul peringatan Rate Potongan Kurang Jam masih Rp 0, isi dulu di Pengaturan Kantor, atau potongan kurang jam akan bernilai nol.',
                    'Slip gaji PDF bisa diunduh lewat Export & Import, kartu Payroll.',
                ],
            ],
        ],

        'payroll-detail' => [
            'title' => 'Detail Payroll',
            'summary' => 'Rincian take home pay satu karyawan untuk satu periode, beserta tahapan statusnya.',
            'access' => 'payroll',
            'sections' => [
                'Rincian perhitungan' => [
                    ['Gaji Pokok', 'dari data karyawan.'],
                    ['Lembur', 'jumlah lembur disetujui dikali flat rate lembur karyawan.'],
                    ['Potongan Kurang Jam', 'jumlah blok kurang jam kerja dikali rate di Pengaturan Kantor. Sisa menit yang belum genap satu blok dibawa ke bulan berikutnya.'],
                    ['Penyesuaian Lain', 'angka manual, boleh minus.'],
                ],
                'Yang bisa dilakukan (akses Manage)' => [
                    ['Simpan Penyesuaian', 'isi Penyesuaian Lain dan catatan. Hanya bisa saat masih draft.'],
                    ['Finalisasi', 'mengunci payroll. Setelah itu tidak bisa digenerate ulang atau diedit.'],
                    ['Tandai Sudah Dibayar', 'muncul setelah difinalisasi. Setelah ditandai, riwayatnya permanen tanpa aksi lanjutan.'],
                    ['Hapus Draft', 'menghapus payroll yang masih draft.'],
                ],
                'Perlu diketahui' => [
                    'Alur status satu arah: draft, lalu finalized, lalu paid.',
                    'Generate, penyesuaian, finalisasi, pembayaran, dan hapus draft semuanya tercatat di Audit Log.',
                ],
            ],
        ],

        // =============================================================
        // FINANCE, ROYALTY, LEGAL
        // =============================================================

        'budget' => [
            'title' => 'Project Budgeting',
            'summary' => 'Perbandingan budget dan realisasi (actual) biaya per project.',
            'access' => 'budget',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter Project', 'tampilkan baris budget satu project saja.'],
                    ['+ Tambah Budget', 'tambah baris budget baru (akses Manage).'],
                    ['Edit dan Hapus', 'ubah angka atau hapus baris. Hapus bersifat permanen.'],
                ],
                'Arti angka' => [
                    ['Budget', 'anggaran yang direncanakan.'],
                    ['Actual', 'biaya yang sudah benar-benar keluar.'],
                    ['Variance', 'Budget dikurangi Actual. Positif berarti masih ada sisa anggaran, negatif berarti melebihi anggaran.'],
                ],
                'Perlu diketahui' => [
                    'Data budget bisa diunduh sebagai Excel dan diimpor massal lewat Export & Import.',
                ],
            ],
        ],

        'budget-form' => [
            'title' => 'Form Budget',
            'summary' => 'Tambah atau ubah satu baris budget project.',
            'access' => 'budget',
            'sections' => [
                'Arti tiap isian' => [
                    ['Project', 'project yang memiliki biaya ini.'],
                    ['Kategori dan Item', 'pengelompokan dan nama biaya, mis. kategori Marketing, item Iklan.'],
                    ['Budget (Rp)', 'anggaran yang direncanakan.'],
                    ['Actual (Rp)', 'realisasi biaya. Boleh 0 kalau belum ada pengeluaran.'],
                    ['Catatan', 'keterangan tambahan (opsional).'],
                ],
            ],
        ],

        'royalty' => [
            'title' => 'Royalty Dashboard',
            'summary' => 'Daftar entri royalti beserta bagian (share), recoup, dan status pembayarannya.',
            'access' => 'royalty',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter status', 'Estimated, Reported, Ready to Pay, atau Paid.'],
                    ['+ Tambah Entry', 'catat entri royalti baru (akses Manage).'],
                    ['Edit dan Hapus', 'ubah angka atau status, atau hapus entri. Hapus bersifat permanen.'],
                ],
                'Arti angka' => [
                    ['Gross', 'pendapatan kotor.'],
                    ['Share', 'persentase bagian kita dari Gross.'],
                    ['Recoup', 'jumlah yang dipotong untuk menutup biaya di muka.'],
                    ['Net Payable', 'Gross dikali Share, dikurangi Recoup. Itu yang benar-benar dibayarkan.'],
                ],
            ],
        ],

        'royalty-form' => [
            'title' => 'Form Royalty Entry',
            'summary' => 'Catat atau ubah satu entri royalti.',
            'access' => 'royalty',
            'sections' => [
                'Arti tiap isian' => [
                    ['Judul', 'nama entri, mis. judul lagu atau album.'],
                    ['Periode dan Sumber', 'opsional, mis. periode laporan dan sumbernya (Spotify, YouTube, label).'],
                    ['Status', 'Estimated, Reported, Ready to Pay, atau Paid.'],
                    ['Gross, Share, Recoup', 'dasar hitung Net Payable: Gross x Share% dikurangi Recoup.'],
                    ['Catatan', 'keterangan tambahan (opsional).'],
                ],
            ],
        ],

        'legal' => [
            'title' => 'Legal',
            'summary' => 'Arsip dokumen legal: kontrak album/lagu dan perjanjian royalti, lengkap dengan masa berlaku.',
            'access' => 'legal',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter kategori', 'Album Contracts atau Royalty Agreements. Dua menu di bagian LEGAL pada sidebar membuka halaman ini dengan kategori yang sudah terpilih.'],
                    ['Buka file', 'klik nama file di kartu dokumen untuk membukanya di tab baru. File bersifat privat dan hanya bisa dibuka setelah login dengan akses yang sesuai.'],
                    ['+ Upload Dokumen', 'unggah dokumen baru (akses Manage).'],
                    ['Edit dan Hapus', 'ubah data atau hapus dokumen. Hapus bersifat permanen.'],
                ],
                'Perlu diketahui' => [
                    'Badge "Segera Berakhir" muncul untuk dokumen yang berakhir dalam 30 hari, asalkan Tanggal Selesai diisi.',
                ],
            ],
        ],

        'legal-form' => [
            'title' => 'Form Dokumen Legal',
            'summary' => 'Unggah atau perbarui dokumen legal.',
            'access' => 'legal',
            'sections' => [
                'Arti tiap isian' => [
                    ['Kategori', 'Album Contracts atau Royalty Agreements.'],
                    ['Judul dan Pihak Terkait', 'nama dokumen dan pihak lawan (label, artist, publisher). Pihak terkait opsional.'],
                    ['File Dokumen', 'PDF, Word, atau gambar, maksimal 10 MB. Saat mengedit, biarkan kosong kalau file tidak diganti.'],
                    ['Tanggal Mulai dan Selesai', 'opsional, tetapi Tanggal Selesai dibutuhkan untuk badge "Segera Berakhir".'],
                    ['Catatan', 'keterangan tambahan (opsional).'],
                ],
            ],
        ],

        // =============================================================
        // IT
        // =============================================================

        'audit-log' => [
            'title' => 'Audit Log',
            'summary' => 'Jejak aktivitas sistem: siapa melakukan apa dan kapan. Hanya untuk dibaca.',
            'access' => 'it',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Cari', 'cari berdasarkan nama aksi, detail, atau nama pelaku, lalu klik Cari. Tombol Reset menghapus pencarian.'],
                    ['Tab System Changelog', 'pindah ke catatan rilis fitur.'],
                ],
                'Yang saat ini tercatat' => [
                    'Tambah, ubah, nonaktifkan, dan aktifkan kembali karyawan.',
                    'Perubahan Dashboard Access dan Pengaturan Kantor.',
                    'Keputusan izin/cuti, lembur, dan koreksi presensi.',
                    'Payroll: generate, penyesuaian, finalisasi, tandai dibayar, dan hapus draft.',
                ],
                'Perlu diketahui' => [
                    'Belum semua aksi tercatat. Contohnya pengelolaan Work Tracker, KPI, Budget, Royalty, dan Legal belum masuk log.',
                    'Log ditampilkan 20 per halaman, yang terbaru di atas. Rekapnya bisa diunduh sebagai Excel lewat Export & Import.',
                ],
            ],
        ],

        'changelog' => [
            'title' => 'System Changelog',
            'summary' => 'Catatan rilis fitur sistem untuk Owner dan tim.',
            'access' => 'it',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter status', 'Planned (direncanakan) atau Released (sudah dirilis).'],
                    ['+ Tambah Changelog', 'catat rilis baru (akses Manage).'],
                    ['Edit dan Hapus', 'ubah atau hapus catatan. Hapus bersifat permanen.'],
                ],
                'Perlu diketahui' => [
                    'Tab Audit Log di atas membawa kamu kembali ke jejak aktivitas sistem.',
                ],
            ],
        ],

        'changelog-form' => [
            'title' => 'Form Changelog',
            'summary' => 'Catat atau ubah satu rilis fitur.',
            'access' => 'it',
            'sections' => [
                'Arti tiap isian' => [
                    ['Versi', 'nomor versi, mis. v1.4.0.'],
                    ['Tanggal Rilis dan Status', 'Planned kalau masih rencana, Released kalau sudah rilis.'],
                    ['Judul', 'nama rilis, mis. Modul Legal & Payroll.'],
                    ['Modul Terkait', 'nama modul dipisah koma (opsional).'],
                    ['Daftar Perubahan', 'satu baris sama dengan satu poin perubahan.'],
                ],
            ],
        ],

        // =============================================================
        // RECRUITMENT
        // =============================================================

        'openings' => [
            'title' => 'Lowongan',
            'summary' => 'Kelola posisi yang dibuka. Lowongan yang berstatus Tayang muncul di halaman Karir publik.',
            'access' => 'recruitment',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter Status', 'Draft, Tayang, atau Ditutup.'],
                    ['+ Tambah Lowongan', 'buat lowongan baru (akses Manage).'],
                    ['Lihat Publik', 'membuka halaman publik lowongan itu, seperti yang dilihat pelamar.'],
                    ['Edit', 'ubah isi atau status lowongan.'],
                ],
                'Perlu diketahui' => [
                    'Kolom Pelamar menunjukkan jumlah lamaran yang sudah masuk ke posisi itu.',
                    'Hanya lowongan berstatus Tayang yang terlihat di halaman Karir. Draft dan Ditutup tidak tampil.',
                ],
            ],
        ],

        'opening-form' => [
            'title' => 'Form Lowongan',
            'summary' => 'Isi atau ubah posisi yang dibuka.',
            'access' => 'recruitment',
            'sections' => [
                'Arti tiap isian' => [
                    ['Judul Posisi dan Divisi', 'nama posisi dan divisinya.'],
                    ['Tipe Kerja', 'Penuh Waktu, Paruh Waktu, Kontrak, atau Magang.'],
                    ['Deskripsi Pekerjaan dan Kualifikasi', 'teks yang dibaca pelamar di halaman publik. Kualifikasi opsional.'],
                    ['Status', 'Draft (belum tampil), Tayang (tampil di halaman Karir), atau Ditutup (tidak menerima lamaran).'],
                ],
                'Perlu diketahui' => [
                    'Link publik dibuat otomatis dari judul setelah lowongan disimpan.',
                ],
            ],
        ],

        'applications' => [
            'title' => 'Pelamar',
            'summary' => 'Semua lamaran yang masuk dari halaman Karir publik.',
            'access' => 'recruitment',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter', 'saring berdasarkan Status, Lowongan, atau cari nama/email pelamar, lalu klik Cari.'],
                    ['Detail', 'buka data lengkap pelamar untuk meninjau dan mengubah statusnya.'],
                ],
                'Arti status' => [
                    'Baru, Ditinjau, Interview, Ditawari, Diterima, dan Ditolak, sesuai tahap proses seleksi.',
                ],
            ],
        ],

        'application-detail' => [
            'title' => 'Detail Pelamar',
            'summary' => 'Data seorang pelamar, pesan motivasinya, dan tempat mencatat perkembangan seleksinya.',
            'access' => 'recruitment',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Ubah Status & Catatan', 'pilih status seleksi dan tulis catatan internal (tidak terlihat pelamar), lalu Simpan.'],
                    ['Terima & Buatkan Akun', 'muncul setelah status Diterima. Membuka form untuk membuat akun karyawan dari data pelamar.'],
                ],
                'Perlu diketahui' => [
                    'Kalau tampil "Sudah Jadi Karyawan", akun untuk pelamar ini sudah dibuat sehingga tidak perlu dibuat lagi.',
                ],
            ],
        ],

        'application-convert' => [
            'title' => 'Buatkan Akun Karyawan',
            'summary' => 'Membuat akun login karyawan dari data pelamar yang sudah diterima.',
            'access' => 'recruitment',
            'sections' => [
                'Arti tiap isian' => [
                    ['Nama dan Email', 'terisi dari lamaran, boleh diubah. Email dipakai untuk login.'],
                    ['Password', 'password awal akun.'],
                    ['Role dan Atasan Langsung', 'label jabatan dan atasan yang menyetujui izin, cuti, dan lembur orang ini.'],
                    ['Divisi dan Jabatan', 'terisi dari lowongan yang dilamar.'],
                    ['Tanggal Bergabung, Jatah Cuti, Tanggal Lahir', 'data kerja karyawan. Tanggal Lahir opsional.'],
                ],
                'Perlu diketahui' => [
                    'Pilih Role sesuai jabatan. Hak akses modul diatur terpisah oleh Owner lewat menu Karyawan, tombol Akses.',
                    'Minta karyawan baru mengganti password lewat menu Profil setelah login pertama.',
                ],
            ],
        ],

        // =============================================================
        // EXPORT & IMPORT
        // =============================================================

        'export-import' => [
            'title' => 'Export & Import',
            'summary' => 'Unduh data sebagai Excel atau PDF, dan masukkan data massal lewat template Excel. Kartu yang tampil mengikuti akses modul yang kamu punya.',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Export Excel atau PDF', 'membuka preview datanya dulu, baru diunduh. Beberapa PDF (slip Payroll, notulen rapat, rekap absensi per karyawan) menampilkan daftar pilihan, lalu PDF langsung terunduh.'],
                    ['Import', 'memasukkan banyak data lewat template Excel. Tersedia untuk Work Tracker, Manajemen Karyawan, KPI, dan Project Budgeting.'],
                ],
                'Perlu diketahui' => [
                    'Tombol bertanda "Segera Hadir" belum bisa dipakai.',
                    'Import membutuhkan akses Manage pada modul terkait. Manajemen Karyawan khusus Owner.',
                    'Data tidak langsung masuk saat file diunggah. Kamu selalu melihat preview dan mengonfirmasi dulu.',
                ],
            ],
        ],

        'export-preview' => [
            'title' => 'Preview Export',
            'summary' => 'Cek data yang akan diunduh sebelum download.',
            'sections' => [
                'Yang bisa dilakukan' => [
                    ['Filter dan Terapkan Filter', 'sempitkan data (periode, status, dan sebagainya), lalu klik Terapkan Filter.'],
                    ['Tabel preview', 'menampilkan baris data sesuai filter beserta jumlah barisnya.'],
                    ['Tombol download', 'mengunduh file sesuai filter yang sedang dipakai.'],
                ],
                'Perlu diketahui' => [
                    'Untuk PDF yang dibuat per dokumen, halaman ini berupa daftar pilihan. Pilih salah satu dan PDF langsung terunduh.',
                    'PDF rekap absensi meminta kamu memilih karyawan lebih dulu di filter.',
                    'Kalau tabel kosong, longgarkan filter atau periksa periodenya.',
                ],
            ],
        ],

        'import-upload' => [
            'title' => 'Import Data',
            'summary' => 'Dua langkah: unduh template, isi, lalu unggah kembali. Data belum masuk sampai kamu mengonfirmasi di halaman preview.',
            'sections' => [
                'Langkah' => [
                    ['1. Download Template', 'unduh template Excel kosong. Jangan ubah nama kolom di baris pertama. Baris contoh (huruf miring) boleh dihapus.'],
                    ['2. Upload File Terisi', 'format .xlsx, .xls, atau .csv, maksimal 5 MB, lalu klik "Lihat Preview".'],
                ],
                'Perlu diketahui' => [
                    'Kolom bertanda Wajib harus terisi. Kolom yang boleh kosong tertera di daftar kolom pada halaman ini.',
                    'Import hanya menambah data baru. Baris yang tidak valid akan ditandai di preview, bukan langsung ditolak seluruhnya.',
                ],
            ],
        ],

        'import-preview' => [
            'title' => 'Preview Import',
            'summary' => 'Cek dulu hasil pembacaan file sebelum data benar-benar masuk.',
            'sections' => [
                'Yang tampil' => [
                    ['Baris Valid', 'siap diimpor.'],
                    ['Baris Error', 'tidak ikut masuk ke database. Tiap baris menampilkan alasan errornya.'],
                ],
                'Yang bisa dilakukan' => [
                    ['Konfirmasi Import', 'memasukkan hanya baris valid ke database.'],
                    ['Upload Ulang', 'kembali ke halaman upload untuk mengunggah file yang sudah diperbaiki.'],
                ],
                'Perlu diketahui' => [
                    'Baris error dilewati begitu saja. Perbaiki di file lalu upload ulang kalau baris itu ingin ikut masuk.',
                    'Kalau tidak ada baris valid, tombol konfirmasi tidak tersedia. Perbaiki file lalu upload ulang.',
                ],
            ],
        ],

    ],
];