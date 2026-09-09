<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DashboardAccess
 * ---------------------------------------------------------------------
 * Fase 6a — 1 baris = 1 user dapat akses 'view' atau 'manage' ke 1
 * modul. Kalau user gak punya baris buat modul tertentu, artinya
 * levelnya 'none' (gak disimpan literal di DB, lihat migration).
 *
 * MODULES ini sumber kebenaran tunggal buat modul-modul dashboard di
 * luar attendance-self-service — 7 modul pertama (`work`, `budget`,
 * `royalty`, `kpi`, `people`, `contracts`, `payroll`) PERSIS sama
 * dengan `DASHBOARD_MODULES` di prototype v18 (`index.html`). `legal`
 * (Fase 14) & `it` (Fase 15) BUKAN dari prototype — di prototype v18,
 * LEGAL & IT cuma section tetap di sidebar CEO Dashboard (role-gated
 * ke CEO), gak pernah jadi modul yang bisa di-assign per-user lewat
 * `dashboard_access`/`DASHBOARD_MODULES`. Nambahin keduanya ke sini
 * adalah desain baru WSM Office System sendiri (biar Owner bisa
 * delegasikan akses Legal/IT ke staf lain, bukan cuma CEO/Owner) —
 * bukan hasil porting dari prototype.
 *
 * `recruitment` (2026-09-09, refactor "permission bukan role") JUGA
 * BUKAN dari prototype (prototype v32 gak punya fitur rekrutmen sama
 * sekali — dicek langsung, nol hasil grep "rekrutmen"/"recruitment"/
 * "pelamar" di seluruh file). Ditambahin ke sini dengan alasan SAMA
 * kayak legal/it di atas: biar Owner bisa cabut/kasih akses Rekrutmen
 * ke staf tertentu satu-satu, bukan blanket ke SEMUA orang berrole
 * 'hrd'. Sebelum ini, Rekrutmen role-gated (`role:hrd,owner`) — itu
 * PERSIS jenis masalah yang lagi dibenerin (role != akses beneran).
 *
 * Total jadi 10 modul. Dipakai di form assign (Owner), di sidebar
 * dashboard (User::canView()), dan validasi. Kalau nambah modul baru,
 * cukup ubah di sini + enum kolom `module` di migration baru (jangan
 * cuma di 1 tempat) — lihat pola migration
 * `add_legal_and_it_modules_to_dashboard_access`.
 *
 * PENTING (2026-09-09) — 2 modul ini SEKARANG JUGA dipakai buat
 * fitur-fitur yang DULUNYA role-based (`role:manajer,owner,hrd` dkk):
 * - `people` -> gerbang masuk Rekap Absensi ("Kelola Tim") & layar
 *   Persetujuan Izin/Cuti/Lembur. TIDAK mengubah SIAPA yang boleh
 *   approve request tertentu — itu tetap murni relasi
 *   atasan-langsung (`manager_id`), persis prototype (lihat komentar
 *   `rolePeopleDashboard()`: "Dashboard access tidak mengubah
 *   authority approval"). `people` cuma ngatur siapa yang BISA MASUK
 *   layar itu sama sekali.
 * - `recruitment` -> gerbang masuk seluruh modul Rekrutmen (lowongan
 *   & pelamar).
 * Lihat README (2026-09-09, "Dashboard permission-based, bukan role")
 * buat daftar lengkap file yang kena refactor ini + migration backfill
 * buat user existing yang sebelumnya dapet akses ini dari role.
 * ---------------------------------------------------------------------
 */
#[Fillable(['user_id', 'module', 'level', 'granted_by'])]
class DashboardAccess extends Model
{
    protected $table = 'dashboard_access';

    public const MODULES = [
        'work' => [
            'label' => 'Work Control',
            'desc' => 'Project, tracker, timeline, MoM, memo',
            'icon' => '☷',
        ],
        'budget' => [
            'label' => 'Project Budgeting',
            'desc' => 'Budget vs actual per project',
            'icon' => '▦',
        ],
        'royalty' => [
            'label' => 'Royalty Dashboard',
            'desc' => 'Royalty, share, recoupment, payment status',
            'icon' => '♪',
        ],
        'kpi' => [
            'label' => 'KPI & Performance',
            'desc' => 'KPI seluruh tim',
            'icon' => '◎',
        ],
        'people' => [
            'label' => 'People & Leave',
            'desc' => 'People directory & leave monitoring',
            'icon' => '◉',
        ],
        'contracts' => [
            'label' => 'Contract Monitoring',
            'desc' => 'Status & file kontrak',
            'icon' => '▤',
        ],
        'payroll' => [
            'label' => 'Payroll Overview',
            'desc' => 'Payroll & take home pay',
            'icon' => '$',
        ],
        // Fase 14 — dipakai bareng buat 2 kategori (Album Contracts &
        // Royalty Agreements), gak dipecah 'legal_album'/'legal_royalty'
        // — lihat catatan di migration create_legal_documents_table.
        'legal' => [
            'label' => 'Legal',
            'desc' => 'Album Contracts & Royalty Agreements',
            'icon' => '▤',
        ],
        // Fase 15 — Audit Logs + System Change Log.
        'it' => [
            'label' => 'IT',
            'desc' => 'Audit log & system change log',
            'icon' => '≡',
        ],
        // 2026-09-09 — refactor "permission bukan role". Gerbang masuk
        // modul Rekrutmen (lowongan & pelamar), gantiin
        // `role:hrd,owner` yang lama. Lihat catatan panjang di
        // doc-comment class ini.
        'recruitment' => [
            'label' => 'Rekrutmen',
            'desc' => 'Lowongan kerja & pipeline pelamar',
            'icon' => '✎',
        ],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}