<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ContactMessage
 * ---------------------------------------------------------------------
 * Fase 1 (susulan, 2026-09-13) — pesan dari form Kontak publik
 * (`public.contact`). Status `baru`/`dibaca` dipakai buat badge unread
 * di sidebar Owner, sama pola `MemoRead` (baca/belum) tapi lebih
 * sederhana karena yang baca cuma Owner, bukan banyak user.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'name',
    'email',
    'message',
    'status',
    'read_at',
])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function markRead(): void
    {
        if ($this->status === 'dibaca') {
            return;
        }

        $this->update(['status' => 'dibaca', 'read_at' => now()]);
    }

    public static function unreadCount(): int
    {
        return static::query()->where('status', 'baru')->count();
    }
}