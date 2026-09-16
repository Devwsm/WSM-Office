<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
 *
 * 2026-09-16 nambah: `audience`/`memo_recipients` (target ke sebagian
 * karyawan aja, bukan otomatis semua) & `active` (bisa "dimatiin" tanpa
 * dihapus — lihat migration `memo_audience_and_active` buat penjelasan
 * lengkap kenapa). Halaman manajemen (dashboard/work) TETAP nampilin
 * memo nonaktif (biar bisa diaktifin lagi) — yang difilter cuma kartu
 * "Info dari Owner" di App Mode lewat scopeActive()+scopeVisibleTo().
 * ---------------------------------------------------------------------
 */
#[Fillable(['type', 'title', 'content', 'meeting_date', 'attendees', 'pinned', 'audience', 'active', 'created_by'])]
class Memo extends Model
{
    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'pinned' => 'boolean',
            'active' => 'boolean',
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

    /** Karyawan target kalau audience='tertentu'. Kosong (nggak dipakai sama sekali) kalau audience='semua'. */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memo_recipients')->withTimestamps();
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
     *
     * 2026-09-16 — dibatesin ke memo yang active() & visibleTo($user)
     * doang (sebelumnya static::query()->get() nyapu SEMUA memo tanpa
     * peduli audience/status aktif, jadi nandain baca punya memo yang
     * si user bahkan gak pernah lihat).
     */
    public static function markAllReadFor(User $user): void
    {
        static::query()->active()->visibleTo($user)->get()->each(function (Memo $memo) use ($user) {
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

    /** Cuma memo yang belum "dimatiin" (tombol Deactivate) — dipakai di kartu Home App Mode, BUKAN di listing manajemen (yang nonaktif pun tetap harus kelihatan manajemen biar bisa diaktifin lagi). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Cuma memo yang audience-nya nyakup $user: 'semua', ATAU $user ada
     * di daftar recipients (audience='tertentu'), ATAU $user adalah
     * pembuat memo-nya sendiri (pembuat selalu bisa lihat memo-nya
     * sendiri walau nggak eksplisit nargetin diri sendiri).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('audience', 'semua')
                ->orWhere('created_by', $user->id)
                ->orWhereHas('recipients', fn(Builder $r) => $r->where('users.id', $user->id));
        });
    }

    /** Dipakai instance-by-instance (mis. dari collection udah di-load) — logikanya sama scopeVisibleTo(), bukan query. */
    public function isVisibleTo(User $user): bool
    {
        if ($this->audience !== 'tertentu') {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        return $this->recipients->contains('id', $user->id);
    }

    public function typeLabel(): string
    {
        return $this->type === 'mom' ? 'Minutes of Meeting' : 'Memo';
    }

    public function audienceLabel(): string
    {
        if ($this->audience !== 'tertentu') {
            return 'Semua Karyawan';
        }

        $names = $this->recipients->pluck('name');

        return $names->isEmpty() ? 'Karyawan tertentu (belum dipilih)' : $names->join(', ');
    }

    /** Universe user yang jadi denominator Read/Hidden count — semua user aktif kalau audience='semua', recipients doang kalau 'tertentu'. */
    public function audienceUserIds(): Collection
    {
        return $this->audience === 'tertentu'
            ? $this->recipients()->pluck('users.id')
            : User::query()->pluck('id');
    }

    public function audienceCount(): int
    {
        return $this->audienceUserIds()->count();
    }

    public function readCount(): int
    {
        return $this->reads()
            ->whereNotNull('read_at')
            ->whereIn('user_id', $this->audienceUserIds())
            ->count();
    }

    public function hiddenCount(): int
    {
        return $this->reads()
            ->whereNotNull('hidden_at')
            ->whereIn('user_id', $this->audienceUserIds())
            ->count();
    }
}