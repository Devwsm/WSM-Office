<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model BudgetFund
 * ---------------------------------------------------------------------
 * "Project Budget" (2026-10-04) = dana keseluruhan untuk semua project.
 * Singleton: hanya ada 1 baris, dibuat saat pertama kali disimpan. Tidak
 * ada tambah/hapus — hanya diubah lewat form edit (halaman sendiri, bukan
 * modal, karena data sensitif). Bisa diisi walau belum ada project.
 *
 * Bukan "Budget Allocation": itu jumlah `budget` semua item. Selisihnya
 * (Project Budget - Budget Allocation) = dana yang belum dialokasikan.
 * ---------------------------------------------------------------------
 */
#[Fillable(['project_budget', 'updated_by'])]
class BudgetFund extends Model
{
    protected function casts(): array
    {
        return ['project_budget' => 'float'];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Dana saat ini; null = belum pernah diisi (beda dari Rp 0). */
    public static function amount(): ?float
    {
        $value = static::query()->orderBy('id')->value('project_budget');

        return $value === null ? null : (float) $value;
    }

    /** Simpan dana (membuat baris pertama kalau belum ada). */
    public static function set(float $amount, ?int $userId): self
    {
        $fund = static::query()->orderBy('id')->first() ?? new static;
        $fund->fill(['project_budget' => $amount, 'updated_by' => $userId])->save();

        return $fund;
    }
}