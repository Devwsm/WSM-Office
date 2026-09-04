<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User
 * ---------------------------------------------------------------------
 * Satu tabel untuk SEMUA role (Owner, Manajer, Karyawan) — dibedakan
 * lewat kolom `role`. `manager_id` self-reference dipakai untuk org-chart
 * (Fase 2) dan alur approval cuti (Karyawan -> Manajer -> fallback Owner)
 * di Fase 5.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'manager_id',
    'division',
    'job_title',
    'join_date',
    'annual_leave_entitlement',
    'birth_date',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'join_date' => 'date',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    /** Atasan langsung user ini (null kalau langsung di bawah Owner). */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** Bawahan langsung user ini. */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManajer(): bool
    {
        return $this->role === 'manajer';
    }

    public function isHrd(): bool
    {
        return $this->role === 'hrd';
    }

    /** Label role yang enak dibaca (dipakai di badge tabel karyawan). */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'owner' => 'Owner',
            'manajer' => 'Manajer',
            'hrd' => 'HRD',
            default => 'Karyawan',
        };
    }
}