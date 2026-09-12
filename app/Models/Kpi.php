<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Kpi
 * ---------------------------------------------------------------------
 * Fase 10 — padanan `state.kpis` (`saveKpi`) di prototype v18. Nempel
 * modul `kpi` (`DashboardAccess::MODULES['kpi']`) yang slotnya udah ada
 * dari Fase 6a.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'employee_id',
    'title',
    'period',
    'target',
    'current',
    'unit',
    'weight',
    'due_date',
    'status',
    'owner_note',
    'created_by',
])]
class Kpi extends Model
{
    public const STATUSES = ['Active', 'Completed', 'Archived'];

    protected function casts(): array
    {
        return [
            'target' => 'float',
            'current' => 'float',
            'weight' => 'float',
            'due_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Persentase pencapaian (current/target), diklem 0-100+ (boleh lebih dari 100 kalau overachieve). */
    public function achievementPct(): float
    {
        if (! $this->target) {
            return 0.0;
        }

        return round(($this->current / $this->target) * 100, 1);
    }

    /**
     * App Mode quick win (2026-09-09) — warna badge kartu "My KPI" di
     * Home. Ambang batas sama persis prototype (`employeeKpiMarkup`:
     * >=90 hijau, >=60 kuning, sisanya merah).
     */
    public function achievementBadgeClass(): string
    {
        $pct = $this->achievementPct();

        return match (true) {
            $pct >= 90 => 'badge-wsm-green',
            $pct >= 60 => 'badge-wsm-yellow',
            default => 'badge-wsm-red',
        };
    }

    /** Warna progress bar kartu "My KPI" — pasangan achievementBadgeClass() di atas. */
    public function progressBarClass(): string
    {
        $pct = $this->achievementPct();

        return match (true) {
            $pct >= 90 => 'bg-brand-green',
            $pct >= 60 => 'bg-brand-yellow',
            default => 'bg-[#f16c61]',
        };
    }

    /** Tampilan angka ringkas ("100" bukan "100.00", "12.5" bukan "12.50"). */
    public static function formatNumber(?float $value): string
    {
        return rtrim(rtrim(number_format($value ?? 0, 2, '.', ''), '0'), '.') ?: '0';
    }
}