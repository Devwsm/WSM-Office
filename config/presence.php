<?php

/**
 * config/presence.php
 * ---------------------------------------------------------------------
 * Pengaturan Monitor Login (Dashboard > IT > Monitor Login).
 *
 * Status dihitung dari umur `users.last_seen_at`:
 *   - Online  : aktivitas terakhir <= online_seconds
 *   - Idle    : lebih lama dari itu tapi <= idle_seconds (tab masih
 *               terbuka / baru saja berhenti berinteraksi)
 *   - Offline : lebih lama dari idle_seconds, belum pernah, atau sudah logout
 *
 * Heartbeat (resources/views/partials/presence-heartbeat.blade.php) hanya
 * dikirim saat tab terlihat DAN ada interaksi (klik/ketik/scroll/sentuh)
 * dalam activity_window_seconds terakhir — jadi heartbeat TIDAK
 * memperpanjang sesi login kalau karyawan sedang tidak memakai aplikasi.
 *
 * `pages` memetakan NAMA ROUTE -> label halaman (Str::is, yang pertama
 * cocok menang). Yang tidak cocok tampil "Halaman lain". Hanya label ini
 * yang disimpan, bukan URL atau isi query.
 * ---------------------------------------------------------------------
 */

return [
    'online_seconds' => 180,
    'idle_seconds' => 900,
    'heartbeat_seconds' => 60,
    'activity_window_seconds' => 120,

    // Route yang TIDAK dianggap "membuka halaman" (aset/unduhan/sistem).
    'ignore' => [
        'avatar.show',
        'presence.ping',
        'logout',
        'system.*',
        '*.photo',
        '*.file',
        '*.pdf',
        '*.download',
        '*.template',
        '*.excel*',
    ],

    'pages' => [
        // --- App Mode ---
        'employee.home' => 'App Mode · Home',
        'employee.attendance.*' => 'App Mode · Riwayat Absensi',
        'employee.attendanceCorrection.*' => 'App Mode · Koreksi Presensi',
        'employee.leave.*' => 'App Mode · Izin / Cuti',
        'employee.overtime.*' => 'App Mode · Lembur',
        'employee.workTracker.*' => 'App Mode · Timeline Calendar',
        'employee.memo.*' => 'App Mode · Memo',
        'employee.profile.*' => 'App Mode · Profil',
        'manajer.team.attendance' => 'Absensi Tim',
        'manajer.team.work' => 'Progress Kerja Tim',

        // --- Approval & rekap ---
        'approval.leave.*' => 'Approval Izin / Cuti',
        'approval.overtime.*' => 'Approval Lembur',
        'approval.attendanceCorrection.*' => 'Approval Koreksi Presensi',
        'attendance.recap.*' => 'Rekap Absensi',

        // --- Owner ---
        'owner.dashboard' => 'Executive People Overview',
        'owner.organization' => 'Organization',
        'owner.employees.*' => 'Karyawan & Access',
        'owner.office-settings.*' => 'Pengaturan Kantor',
        'owner.contact-messages.*' => 'Pesan Kontak',
        'owner.team-groups.*' => 'Kelompok Tim',
        'owner.landing.*' => 'Editor Halaman Depan',

        // --- Dashboard ---
        'dashboard.index' => 'Dashboard',
        'dashboard.show' => 'Dashboard',
        'dashboard.lock.*' => 'Dashboard (terkunci)',
        'dashboard.work.projects.*' => 'Projects',
        'dashboard.work.tracker.*' => 'Work Tracker',
        'dashboard.work.calendar*' => 'Timeline Calendar',
        'dashboard.work.meetings.*' => 'MoM / Meeting',
        'dashboard.work.*' => 'Work Control',
        'dashboard.budget.*' => 'Project Budgeting',
        'dashboard.contracts.*' => 'Contract Monitoring',
        'dashboard.kpi.*' => 'KPI & Performance',
        'dashboard.legal.*' => 'Legal',
        'dashboard.payroll.*' => 'Payroll Overview',
        'dashboard.royalty.*' => 'Royalty Dashboard',
        'dashboard.it.index' => 'Audit Log',
        'dashboard.it.changelog.*' => 'System Change Log',
        'dashboard.it.password-resets.*' => 'Reset Password',
        'dashboard.it.presence.*' => 'Monitor Login',
        'dashboard.export-import.*' => 'Export & Import',

        // --- Recruitment ---
        'recruitment.applications.*' => 'Pelamar',
        'recruitment.openings.*' => 'Lowongan',

        // --- Website publik (karyawan yang sedang login membukanya) ---
        'public.*' => 'Website publik',
    ],

    'fallback_label' => 'Halaman lain',
];