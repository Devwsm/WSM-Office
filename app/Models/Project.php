<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Project
 * ---------------------------------------------------------------------
 * Fase 9 — project master. `work_items`/`meetings`/`project_budgets`
 * (Fase 13) semua nunjuk balik ke sini lewat `project_id` nullable.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'slug',
    'name',
    'start_date',
    'end_date',
    'priority',
    'status',
    'lead_employee_id',
    'tracker_url',
    'progress_recap',
    'created_by',
])]
class Project extends Model
{
    public const PRIORITIES = ['Low', 'Medium', 'High'];

    public const STATUSES = ['On Development', 'Follow Up', 'Done', 'Postpone', 'Pending', 'Confirmed'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (! $project->slug) {
                $project->slug = static::uniqueSlugFrom($project->name);
            }
        });
    }

    public static function uniqueSlugFrom(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$i;
        }

        return $slug;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /** Total budget vs actual seluruh line item project ini (Fase 13). */
    public function budgetTotals(): array
    {
        $budget = (float) $this->budgets()->sum('budget');
        $actual = (float) $this->budgets()->sum('actual');

        return [
            'budget' => $budget,
            'actual' => $actual,
            'remaining' => $budget - $actual,
        ];
    }
}