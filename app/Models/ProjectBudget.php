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
 * `song_title` & `proof_link` = padanan "Linked Song P&L" dan "Payment
 * Proof Link" prototype v22 (lihat migration add_song_and_proof_*).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'project_id',
    'category',
    'item',
    'song_title',
    'budget',
    'actual',
    'note',
    'proof_link',
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

    /**
     * 2026-10-04 — setiap item selalu memakai ejaan kategori yang sudah
     * terdaftar di project-nya (atau mendaftarkannya kalau baru), apapun
     * jalurnya: form, edit satuan, import Excel, seeder. Lihat BudgetCategory.
     */
    protected static function booted(): void
    {
        static::saving(function (self $line) {
            if ($line->project_id && trim((string) $line->category) !== '') {
                $line->category = BudgetCategory::canonicalName((int) $line->project_id, (string) $line->category);
            }
        });
    }

    /** Boleh dilihat $user? (kategori terbatas hanya untuk viewer-nya + Owner/Developer). */
    public function isVisibleTo(User $user): bool
    {
        if ($user->isOwnerOrDeveloper()) {
            return true;
        }

        $category = BudgetCategory::findByName((int) $this->project_id, $this->category);

        return $category === null || $category->isVisibleTo($user);
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