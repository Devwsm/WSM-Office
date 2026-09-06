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

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Baris dashboard_access milik user ini (kosong buat Owner — lihat accessLevel()). */
    public function dashboardAccess(): HasMany
    {
        return $this->hasMany(DashboardAccess::class);
    }

    /**
     * Level akses user ini ke satu modul: 'none' | 'view' | 'manage'.
     * Owner SENGAJA di-hardcode 'manage' di semua modul di sini (bukan
     * disimpan sebagai baris di DB) — biar Owner baru otomatis
     * full-access tanpa perlu seed ulang tabel dashboard_access.
     */
    public function accessLevel(string $module): string
    {
        if ($this->isOwner()) {
            return 'manage';
        }

        return $this->dashboardAccess->firstWhere('module', $module)?->level ?? 'none';
    }

    public function canViewModule(string $module): bool
    {
        return $this->accessLevel($module) !== 'none';
    }

    public function canManageModule(string $module): bool
    {
        return $this->accessLevel($module) === 'manage';
    }

    /** Dipakai buat nampilin/nyembunyiin tombol "Dashboard" di app-mobile. */
    public function hasAnyDashboardAccess(): bool
    {
        return $this->isOwner() || $this->dashboardAccess->isNotEmpty();
    }

    /** Total hari cuti tahunan yang sudah TERPAKAI (status disetujui aja — pending/ditolak/dibatalkan nggak motong). */
    public function usedAnnualLeaveDays(): int
    {
        return (int) $this->leaveRequests()
            ->where('type', LeaveRequest::QUOTA_TYPE)
            ->where('status', 'disetujui')
            ->sum('work_days');
    }

    public function remainingAnnualLeaveDays(): int
    {
        return max(0, (int) $this->annual_leave_entitlement - $this->usedAnnualLeaveDays());
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