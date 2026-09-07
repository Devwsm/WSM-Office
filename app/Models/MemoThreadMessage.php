<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model MemoThreadMessage
 * ---------------------------------------------------------------------
 * Fase 8 — 1 pesan di thread reply sebuah memo. Thread-nya SATU,
 * dibagi bareng semua yang bisa lihat memo itu (bukan channel privat
 * per-karyawan) — lihat migration buat penjelasan lengkap.
 * ---------------------------------------------------------------------
 */
#[Fillable(['memo_id', 'user_id', 'message', 'read_by_management_at'])]
class MemoThreadMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_by_management_at' => 'datetime',
        ];
    }

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Pesan ini dari manajemen (posisi tampil beda di UI — lihat memo-thread.blade.php) atau dari karyawan biasa. */
    public function isFromManagement(): bool
    {
        return $this->author->canManageModule('work');
    }

    /**
     * Badge unread di sidebar item "Work Control" — cuma itung reply
     * yang BELUM ditandai read_by_management_at (lihat
     * Dashboard\Work\MemoController::index() buat kapan kolom ini
     * ke-set).
     */
    public static function unreadForManagementCount(): int
    {
        return static::query()->whereNull('read_by_management_at')->count();
    }
}