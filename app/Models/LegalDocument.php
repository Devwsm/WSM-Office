<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model LegalDocument
 * ---------------------------------------------------------------------
 * Fase 14 (BARU) — padanan `state.legalDocs` (`saveLegalDocV18`) di
 * prototype v18. `category` bedain Album Contracts vs Royalty
 * Agreements, 1 tabel dipakai bareng (sama pola `Memo.type`).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'category',
    'title',
    'party',
    'start_date',
    'end_date',
    'file_path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'notes',
    'created_by',
])]
class LegalDocument extends Model
{
    public const CATEGORIES = ['album', 'royalty'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'album' => 'Album Contracts',
            'royalty' => 'Royalty Agreements',
            default => ucfirst($this->category),
        };
    }
}