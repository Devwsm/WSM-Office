<?php

namespace App\Support\ExportImport;

use App\Models\User;

/**
 * ExportCatalog
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi fitur Export & Import) — sumber kebenaran tunggal
 * buat "halaman mana yang punya export/import, format apa aja, dan
 * modul dashboard_access mana yang jadi gerbangnya", persis pola
 * DashboardAccess::MODULES.
 *
 * SENGAJA TIDAK bikin modul dashboard_access baru ('export_import' dkk)
 * — export/import 1 halaman numpang izin akses YANG SUDAH ADA buat
 * halaman itu (mis. export Payroll butuh akses modul 'payroll', bukan
 * modul baru). Alasannya: kalau seseorang sudah boleh LIHAT data
 * Payroll di layar, dia juga wajar boleh MENGUNDUHNYA — bikin modul
 * akses terpisah cuma bikin Owner harus assign akses 2x buat hal yang
 * sama.
 *
 * 'implemented' => false berarti card-nya kelihatan di "Export & Import
 * Center" (biar orang tahu fitur ini lagi disiapkan) tapi tombolnya
 * nonaktif — flag ini diubah jadi true satu-satu pas modulnya beneran
 * dikerjakan (lihat rencana Batch 1/2/3), BARENGAN sama diisinya field
 * 'export_route'/'import_route'. Jangan isi field route sebelum
 * controller-nya beneran ada, nanti `route()` di Blade error.
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
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'payroll' => [
            'label' => 'Payroll',
            'desc' => 'PDF slip gaji per-karyawan, Excel rekap payroll sebulan semua karyawan.',
            'icon' => '$',
            'module' => 'payroll',
            'owner_only' => false,
            'exports' => ['pdf', 'excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'kpi' => [
            'label' => 'KPI & Performance',
            'desc' => 'Tabel target vs capaian KPI seluruh tim per periode.',
            'icon' => '◎',
            'module' => 'kpi',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => true,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'budget' => [
            'label' => 'Project Budgeting',
            'desc' => 'Tabel anggaran vs realisasi per project.',
            'icon' => '▦',
            'module' => 'budget',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => true,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'royalty' => [
            'label' => 'Royalty Dashboard',
            'desc' => 'Tabel entri royalti per periode & statusnya.',
            'icon' => '♪',
            'module' => 'royalty',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'contracts' => [
            'label' => 'Employee Contracts',
            'desc' => 'Daftar kontrak kerja karyawan & status jatuh tempo.',
            'icon' => '▤',
            'module' => 'contracts',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'legal' => [
            'label' => 'Legal Documents',
            'desc' => 'Daftar dokumen kontrak album & perjanjian royalti beserta jatuh temponya.',
            'icon' => '▤',
            'module' => 'legal',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'audit-log' => [
            'label' => 'Audit Log',
            'desc' => 'Jejak aktivitas sistem, buat kebutuhan review/kepatuhan.',
            'icon' => '≡',
            'module' => 'it',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'employees' => [
            'label' => 'Manajemen Karyawan',
            'desc' => 'Data karyawan lengkap — juga bisa dipakai buat onboarding banyak orang sekaligus lewat import.',
            'icon' => '⌘',
            'module' => null,
            'owner_only' => true,
            'exports' => ['excel'],
            'import' => true,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'recruitment-applicants' => [
            'label' => 'Rekrutmen — Pelamar',
            'desc' => 'Pipeline pelamar per lowongan.',
            'icon' => '✎',
            'module' => 'recruitment',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'work-tracker' => [
            'label' => 'Work Tracker',
            'desc' => 'Daftar tugas per-project — prioritas utama import (nutup gap dari prototype lama).',
            'icon' => '☷',
            'module' => 'work',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => true,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'meetings' => [
            'label' => 'Meetings / MoM',
            'desc' => 'Notulen rapat resmi dalam bentuk PDF, siap diprint/dikirim.',
            'icon' => '≡',
            'module' => 'work',
            'owner_only' => false,
            'exports' => ['pdf'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
        ],
        'leave-recap' => [
            'label' => 'Rekap Izin/Cuti Tim',
            'desc' => 'Rekap pengajuan izin/cuti tim per periode.',
            'icon' => '◉',
            'module' => 'people',
            'owner_only' => false,
            'exports' => ['excel'],
            'import' => false,
            'implemented' => false,
            'export_route' => null,
            'import_route' => null,
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

    /**
     * Level akses user ke SATU entri catalog ('view'/'manage'/'none') —
     * import selalu butuh minimal 'manage' (lihat catatan di
     * index.blade.php), export cukup 'view'.
     */
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
}