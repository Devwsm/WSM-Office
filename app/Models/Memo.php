<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 *
 * Fase 8 nambah: baca/sembunyi per-user (`MemoRead`) & thread reply
 * (`MemoThreadMessage`) — kartu "Info dari Owner" di Home sekarang
 * interaktif (bukan cuma read-only), lihat employee/home.blade.php.
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

    public function reads(): HasMany
    {
        return $this->hasMany(MemoRead::class);
    }

    /** Urutan tampil di thread: lama ke baru (percakapan), BEDA dari urutan listing memo yang terbaru duluan. */
    public function threadMessages(): HasMany
    {
        return $this->hasMany(MemoThreadMessage::class)->with('author')->oldest();
    }

    /** Ambil (atau bikin instance baru belum-tersimpan, default belum dibaca & belum disembunyiin) baris MemoRead 1 user buat memo ini. */
    public function readStateFor(User $user): MemoRead
    {
        return $this->reads->firstWhere('user_id', $user->id)
            ?? new MemoRead(['memo_id' => $this->id, 'user_id' => $user->id]);
    }

    public function isReadBy(User $user): bool
    {
        return $this->readStateFor($user)->read_at !== null;
    }

    public function isHiddenBy(User $user): bool
    {
        return $this->readStateFor($user)->hidden_at !== null;
    }

    /**
     * Tandain SEMUA memo yang keliatan (belum disembunyikan) buat user
     * ini jadi sudah dibaca. 2026-09-10 (permintaan Arga) — gak ada lagi
     * tombol "Tandai Sudah Dibaca" manual: begitu memo-nya DITAMPILKAN/
     * DIBUKA (Home atau Inbox modal), otomatis kebaca, sama prinsipnya
     * kayak badge modul baru di sidebar dashboard (Owner) — muncul
     * sekali pas ada yang baru, ilang begitu udah "dibuka".
     *
     * Dipanggil SETELAH data buat ditampilin ke view udah diambil
     * (lihat HomeController/MemoInteractionController::markAllRead) —
     * biar kunjungan yang lagi jalan tetap kelihatan status
     * unread-nya (user perlu tau ada yang baru), yang berubah cuma
     * status buat kunjungan BERIKUTNYA.
     */
    public static function markAllReadFor(User $user): void
    {
        static::query()->get()->each(function (Memo $memo) use ($user) {
            if ($memo->isHiddenBy($user)) {
                return;
            }

            $state = $memo->readStateFor($user);
            if (! $state->read_at) {
                $state->read_at = now();
                $state->save();
            }
        });
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