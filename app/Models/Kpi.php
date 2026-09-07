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
}