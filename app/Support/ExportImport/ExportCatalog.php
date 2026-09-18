<?php

namespace App\Support\ExportImport;

use App\Models\User;

/**
 * ExportCatalog
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi) — sumber kebenaran tunggal buat "halaman mana yang
 * punya export/import, format apa aja, dan modul dashboard_access mana
 * yang jadi gerbangnya", persis pola DashboardAccess::MODULES.
 *
 * SENGAJA TIDAK bikin modul dashboard_access baru ('export_import' dkk)
 * — export/import 1 halaman numpang izin akses YANG SUDAH ADA buat
 * halaman itu (mis. export Payroll butuh akses modul 'payroll', bukan
 * modul baru).
 *
 * 'implemented_exports' => subset dari 'exports' yang BENERAN sudah
 * jalan (route + controller + Export class-nya ada). Dicek TERPISAH
 * dari 'import_implemented' karena beberapa entri (mis.
 * 'attendance-recap') punya 2 format export yang dikerjakan di batch
 * berbeda (Excel di Batch 1, PDF di Batch 2) — jangan tandai
 * 'implemented_exports' penuh sebelum semua format-nya beneran ada.
 * ---------------------------------------------------------------------
 */
class ExportCatalog
{
    public const CATALOG = [
        'attendance-recap' => [
            'label' => 'Rekap Absensi',
            'desc' => 'Rekap kehadiran tim per periode — Excel buat rekap tim, PDF buat rekap per-karyawan.',
            'icon' => '◉',
            'module' => 'people',
            'owner_only' => false,
            'exports' => ['excel', 'pdf'],
            'implemented_exports' => ['excel', 'pdf'], // Batch 1 (excel) + Batch 2 (pdf, per-karyawan).
            'import' => false,
            'import_implemented' => false,
        ],
        'payroll' => [
            'label' => 'Payroll',
            'desc' => 'PDF slip gaji per-karyawan, Excel rekap payroll sebulan semua karyawan.',
            'icon' => '$',
            'module' => 'payroll',
            'owner_only' => false,
            'exports' => ['pdf', 'excel'],
            'implemented_exports' => ['pdf', 'excel'], // Batch 2.
            'import' => false,
            'import_implemented' => false,
        ],
        'kpi' => [
            'label' => 'KPI & Performance',
            'desc' => 'Tabel target vs capaian KPI seluruh tim per periode.',
            'icon' => '◎',
            'module' => 'kpi',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => true,
            'import_implemented' => false, // Batch 3.
        ],
        'budget' => [
            'label' => 'Project Budgeting',
            'desc' => 'Tabel anggaran vs realisasi per project.',
            'icon' => '▦',
            'module' => 'budget',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => true,
            'import_implemented' => false, // Batch 3.
        ],
        'royalty' => [
            'label' => 'Royalty Dashboard',
            'desc' => 'Tabel entri royalti per periode & statusnya.',
            'icon' => '♪',
            'module' => 'royalty',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
        'contracts' => [
            'label' => 'Employee Contracts',
            'desc' => 'Daftar kontrak kerja karyawan & status jatuh tempo.',
            'icon' => '▤',
            'module' => 'contracts',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
        'legal' => [
            'label' => 'Legal Documents',
            'desc' => 'Daftar dokumen kontrak album & perjanjian royalti beserta jatuh temponya.',
            'icon' => '▤',
            'module' => 'legal',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
        'audit-log' => [
            'label' => 'Audit Log',
            'desc' => 'Jejak aktivitas sistem, buat kebutuhan review/kepatuhan.',
            'icon' => '≡',
            'module' => 'it',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
        'employees' => [
            'label' => 'Manajemen Karyawan',
            'desc' => 'Data karyawan lengkap — juga bisa dipakai buat onboarding banyak orang sekaligus lewat import.',
            'icon' => '⌘',
            'module' => null,
            'owner_only' => true,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => true,
            'import_implemented' => true, // Batch 4.
        ],
        'recruitment-applicants' => [
            'label' => 'Rekrutmen — Pelamar',
            'desc' => 'Pipeline pelamar per lowongan.',
            'icon' => '✎',
            'module' => 'recruitment',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
        'work-tracker' => [
            'label' => 'Work Tracker',
            'desc' => 'Daftar tugas per-project — prioritas utama import (nutup gap dari prototype lama).',
            'icon' => '☷',
            'module' => 'work',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => true,
            'import_implemented' => true, // Batch 3.
        ],
        'meetings' => [
            'label' => 'Meetings / MoM',
            'desc' => 'Notulen rapat resmi dalam bentuk PDF, siap diprint/dikirim.',
            'icon' => '≡',
            'module' => 'work',
            'owner_only' => false,
            'exports' => ['pdf'],
            'implemented_exports' => ['pdf'], // Batch 2.
            'import' => false,
            'import_implemented' => false,
        ],
        'leave-recap' => [
            'label' => 'Rekap Izin/Cuti Tim',
            'desc' => 'Rekap pengajuan izin/cuti tim per periode.',
            'icon' => '◉',
            'module' => 'people',
            'owner_only' => false,
            'exports' => ['excel'],
            'implemented_exports' => ['excel'], // Batch 1.
            'import' => false,
            'import_implemented' => false,
        ],
    ];

    /**
     * Daftar entri catalog yang BOLEH DILIHAT user ini — Owner lihat
     * semua, staf lain cuma lihat entri yang module-nya dia punya akses
     * (minimal 'view') atau entri owner-only yang emang gak akan pernah
     * kelihatan buat mereka.
     *
     * @return array<string, array>
     */
    public static function visibleFor(User $user): array
    {
        return collect(self::CATALOG)
            ->filter(function (array $entry) use ($user) {
                if ($entry['owner_only']) {
                    return $user->isOwner();
                }

                return $user->canViewModule($entry['module']);
            })
            ->all();
    }

    /** Level akses user ke SATU entri catalog ('view'/'manage'/'none'). */
    public static function accessLevelFor(User $user, string $key): string
    {
        $entry = self::CATALOG[$key] ?? null;

        if (! $entry) {
            return 'none';
        }

        if ($entry['owner_only']) {
            return $user->isOwner() ? 'manage' : 'none';
        }

        return $user->accessLevel($entry['module']);
    }

    /** Shortcut yang dipakai controller export/import: true kalau boleh minimal LIHAT entri ini. */
    public static function canView(User $user, string $key): bool
    {
        return self::accessLevelFor($user, $key) !== 'none';
    }

    /** Import butuh level 'manage', bukan cuma 'view' — nulis data beda urgensinya sama baca data. */
    public static function canImport(User $user, string $key): bool
    {
        return self::accessLevelFor($user, $key) === 'manage';
    }
}