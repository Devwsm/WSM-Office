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

        // IP dan area (nama halaman) otomatis dari request yang sedang berjalan.
        $request = app()->bound('request') ? request() : null;

        return static::create([
            'ip_address' => $request?->ip(),
            'area' => $area ?? Presence::labelFor($request?->route()?->getName()),
            'actor_id' => $actor instanceof User ? $actor->id : null,
            'actor_label' => is_string($actor) ? $actor : ($actor === null ? 'System' : null),
            'action' => $action,
            'detail' => $detail,
        ]);
    }

    public function actorName(): string
    {
        return $this->actor?->name ?? $this->actor_label ?? 'System';
    }
}