<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\Presence;

/**
 * Model AuditLog
 * ---------------------------------------------------------------------
 * Fase 15 (BARU) — padanan `state.auditLog` (`auditV18()`) di prototype
 * v18. Cuma `created_at` (lihat migration), gak ada `updated_at`.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'actor_id',
    'actor_label',
    'action',
    'detail',
    'ip_address',
    'area',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Hanya kejadian keamanan yang menyimpan alamat IP. Pekerjaan harian
     * (task, approval, dan sebagainya) tidak menyimpan IP supaya Audit Log
     * tidak menjadi jejak lokasi karyawan.
     *
     * @var list<string>
     */
    public const IP_ACTIONS = [
        'Login gagal',
        'Password direset',
        'Password diganti',
        'Dashboard access diubah',
    ];

    /** Jumlah catatan yang ditulis dalam request ini (dibaca middleware AuditUncoveredChanges). */
    public static int $recorded = 0;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Helper buat nyatet log dari mana aja, padanan `auditV18(action,
     * detail, actor)` di prototype. `$actor` boleh instance User (nyimpen
     * ke `actor_id`) atau string (nyimpen ke `actor_label`, mis. 'System').
     */
    public static function record(string $action, string $detail = '', User|string|null $actor = null, ?string $area = null): self
    {
        static::$recorded++;

        // Area (nama halaman) otomatis dari request yang sedang berjalan. IP hanya
        // disimpan untuk kejadian keamanan (lihat IP_ACTIONS), bukan pekerjaan harian.
        $request = app()->bound('request') ? request() : null;

        return static::create([
            'ip_address' => in_array($action, static::IP_ACTIONS, true) ? $request?->ip() : null,
            'area' => $area ?? Presence::labelFor($request?->route()?->getName()),
            'actor_id' => $actor instanceof User ? $actor->id : null,
            'actor_label' => is_string($actor) ? $actor : ($actor === null ? 'System' : null),
            'action' => $action,
            'detail' => $detail,
        ]);
    }

    /**
     * Pembersihan otomatis (2026-10-11): hapus log yang lebih tua dari
     * `config('audit.retention_months')` bulan. Mengembalikan jumlah baris yang dihapus.
     */
    public static function pruneExpired(): int
    {
        $months = max(1, (int) config('audit.retention_months', 12));

        return static::query()->where('created_at', '<', now()->subMonths($months))->delete();
    }

    public function actorName(): string
    {
        return $this->actor?->name ?? $this->actor_label ?? 'System';
    }
}