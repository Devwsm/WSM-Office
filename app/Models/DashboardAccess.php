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
 * luar `people`/attendance/leave yang tetap role-based — awalnya 7
 * modul dari prototype v13, ditambah `legal` (Fase 14) & `it` (Fase 15)
 * dari prototype v18, jadi 9. Dipakai di form assign (Owner), di
 * sidebar dashboard (User::canView()), dan validasi. Kalau nambah
 * modul baru, cukup ubah di sini + enum kolom `module` di migration
 * baru (jangan cuma di 1 tempat) — lihat pola migration
 * `add_legal_and_it_modules_to_dashboard_access_table`.
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