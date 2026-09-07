<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MemoRead
 * ---------------------------------------------------------------------
 * Fase 8 — 1 baris = status baca & sembunyi 1 user buat 1 memo. Gak
 * ada baris = belum dibaca & belum disembunyiin (lihat migration buat
 * penjelasan pola "absennya baris = default").
 * ---------------------------------------------------------------------
 */
#[Fillable(['memo_id', 'user_id', 'read_at', 'hidden_at'])]
class MemoRead extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}