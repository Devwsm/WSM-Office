<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model Memo
 * ---------------------------------------------------------------------
 * Fase 6b — pengumuman ('memo') & catatan rapat ('mom'), nempel di
 * modul 'work' (Dashboard Access). Tampil di 2 tempat:
 *   1. Kartu "Info dari Owner" di Home (semua role internal, read-only,
 *      cuma beberapa terbaru) — lihat HomeController.
 *   2. Halaman penuh Work Control (/dashboard/work) buat yang punya
 *      akses modul 'work' — CRUD kalau levelnya 'manage'.
 * ---------------------------------------------------------------------
 */
#[Fillable(['type', 'title', 'content', 'meeting_date', 'attendees', 'pinned', 'created_by'])]
class Memo extends Model
{
    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'pinned' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Pinned duluan, lalu terbaru duluan — dipakai di kartu Home & listing. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('pinned')->orderByDesc('created_at');
    }

    public function typeLabel(): string
    {
        return $this->type === 'mom' ? 'Minutes of Meeting' : 'Memo';
    }
}