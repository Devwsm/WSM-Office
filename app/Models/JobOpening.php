<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model JobOpening
 * ---------------------------------------------------------------------
 * Lowongan yang dikelola HRD/Owner (Fase 3). `slug` dipakai untuk URL
 * publik /karir/{slug}. Hanya lowongan berstatus `published` yang
 * tampil di halaman Karir & bisa menerima lamaran baru — draft/closed
 * tetap bisa dibuka HRD/Owner lewat panel internal, cuma disembunyikan
 * dari publik.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'title',
    'slug',
    'division',
    'employment_type',
    'description',
    'requirements',
    'status',
    'created_by',
    'published_at',
    'closed_at',
])]
class JobOpening extends Model
{
    use HasFactory;

    /**
     * Route model binding (baik admin /rekrutmen/lowongan/{opening} maupun
     * publik /karir/{lowongan}) pakai slug, bukan id — supaya route()
     * helper otomatis generate URL yang benar di mana pun model ini
     * di-pass, konsisten dengan binding eksplisit `{lowongan:slug}` di
     * routes/web.php.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Label status yang enak dibaca (badge di panel HRD/Owner). */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'published' => 'Tayang',
            'closed' => 'Ditutup',
            default => 'Draft',
        };
    }

    public function employmentTypeLabel(): string
    {
        return match ($this->employment_type) {
            'part_time' => 'Paruh Waktu',
            'contract' => 'Kontrak',
            'internship' => 'Magang',
            default => 'Penuh Waktu',
        };
    }
}