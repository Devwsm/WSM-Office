<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ProjectBudgetPlan
 * ---------------------------------------------------------------------
 * "Project Budget" = anggaran awal 1 project (2026-10-04). Satu baris per
 * project, TIDAK ada tombol tambah/hapus — hanya diubah lewat form edit
 * (halaman sendiri, bukan modal, karena data sensitif). Baris dibuat saat
 * pertama kali disimpan; project tanpa baris dianggap belum diisi (null),
 * bukan Rp 0.
 *
 * Bukan "Budget Allocation": itu jumlah `budget` seluruh item project.
 * Selisihnya (Project Budget - Budget Allocation) = anggaran yang belum
 * dialokasikan.
 * ---------------------------------------------------------------------
 */
#[Fillable(['project_id', 'project_budget', 'updated_by'])]
class ProjectBudgetPlan extends Model
{
    protected function casts(): array
    {
        return ['project_budget' => 'float'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}