<?php

/**
 * config/audit.php
 * ---------------------------------------------------------------------
 * Pengecualian Audit Log. Semua route yang MENGUBAH data (POST/PUT/PATCH/
 * DELETE) harus tercatat di Audit Log, kecuali yang didaftarkan di sini
 * beserta alasannya.
 *
 *   - Middleware AuditUncoveredChanges mencatat otomatis aksi berhasil yang
 *     belum punya catatan khusus, kecuali route di daftar ini.
 *   - AuditCoverageTest gagal kalau ada route yang mengubah data tapi tidak
 *     mencatat dan tidak terdaftar di sini (jadi route baru tidak terlewat).
 *
 * Pola memakai Str::is() terhadap NAMA ROUTE (`ignore`) atau URI untuk route
 * tanpa nama (`ignore_uris`).
 * ---------------------------------------------------------------------
 */

return [
    'ignore' => [
        // Catatan absensinya sendiri sudah menjadi jejak (tabel attendances); volume tinggi.
        'employee.attendance.clockIn',
        'employee.attendance.clockOut',
        // Membaca/menyembunyikan memo dan balasan thread: interaksi harian, bukan perubahan data kerja.
        'employee.memo.*',
        'dashboard.work.reply',
        // Preferensi pribadi (foto profil, warna tema).
        'employee.profile.avatar.*',
        'employee.profile.theme.*',
        // Kunci tampilan dashboard (bukan data).
        'dashboard.lock.*',
        // Pratinjau: tidak menyimpan apa pun (simpan = commit, yang dicatat).
        'dashboard.export-import.import.preview',
        'dashboard.work.tracker.projects.sync.preview',
        // Tanpa login (tidak ada pelaku): form publik.
        'public.*',
        // Menandai pesan kontak sudah dibaca.
        'owner.contact-messages.markRead',
        // Sistem: heartbeat, logout, deploy hook (token).
        'presence.ping',
        'logout',
        'system.deploy-hook',
    ],

    // Route tanpa nama. Login dicatat lewat event Login/Failed (AppServiceProvider).
    'ignore_uris' => [
        'login',
    ],
];