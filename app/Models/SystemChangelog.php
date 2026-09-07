<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SystemChangelog
 * ---------------------------------------------------------------------
 * Fase 15 (BARU) — padanan `state.systemChangelogCustom`
 * (`saveCustomChangeLog`) di prototype v18. `modules`/`changes` di-cast
 * `array` — controller yang urus split teks form (koma / baris baru)
 * jadi array sebelum `create()`/`update()`.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'version',
    'release_date',
    'status',
    'modules',
    'title',
    'changes',
    'created_by',
])]
class SystemChangelog extends Model
{
    public const STATUSES = ['Planned', 'Released'];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'modules' => 'array',
            'changes' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}