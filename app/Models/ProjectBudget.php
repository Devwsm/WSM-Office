<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ProjectBudget
 * ---------------------------------------------------------------------
 * Fase 13 — padanan `state.projectBudgets` (`saveBudgetEntry`) di
 * prototype v18. Nempel modul `budget` ("Project Budgeting").
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'project_id',
    'category',
    'item',
    'budget',
    'actual',
    'note',
    'updated_by',
])]
class ProjectBudget extends Model
{
    protected function casts(): array
    {
        return [
            'budget' => 'float',
            'actual' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function variance(): float
    {
        return (float) $this->budget - (float) $this->actual;
    }
}