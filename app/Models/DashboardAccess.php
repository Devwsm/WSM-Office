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
 * MODULES ini sumber kebenaran tunggal buat 7 modul dari prototype
 * v13 — dipakai di form assign (Owner), di sidebar dashboard (User::
 * canView()), dan validasi. Kalau nambah modul baru, cukup ubah di
 * sini + enum kolom `module` di migration baru (jangan cuma di 1
 * tempat).
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
            'icon' => 'Rp',
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
            'icon' => '🧾',
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