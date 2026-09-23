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
use Illuminate\Support\Carbon;

/**
 * Model User
 * ---------------------------------------------------------------------
 * Satu tabel untuk SEMUA role (Owner, Developer, Manajer, HRD, Karyawan) — dibedakan
 * lewat kolom `role`. `manager_id` self-reference dipakai untuk org-chart
 * (Fase 2) dan alur approval cuti (Karyawan -> Manajer -> fallback Owner)
 * di Fase 5.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'name',
    'email',
    'password',
    'must_change_password',
    'role',
    'manager_id',
    'division',
    'job_title',
    'join_date',
    'annual_leave_entitlement',
    'birth_date',
    'salary_base',
    'target_hours_per_day',
    'flat_overtime_rate',
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
            'must_change_password' => 'boolean',
            // Fase 12 — field payroll (Gaji Pokok/Target Jam/Flat Overtime
            // Rate), ditunda dari Fase 7 sesuai keputusan README.
            'salary_base' => 'float',
            'target_hours_per_day' => 'integer',
            'flat_overtime_rate' => 'float',
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

    /** Fase 9 — project yang user ini jadi Project Lead-nya. */
    public function leadProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'lead_employee_id');
    }

    /** Fase 9 — item tracker yang PIC-nya user ini. */
    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'pic_employee_id');
    }

    /** Fase 10 — KPI milik user ini. */
    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class, 'employee_id');
    }

    /** Fase 11 — dokumen kontrak kerja user ini. */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class, 'employee_id');
    }

    /** Fase 12 — histori payroll user ini per bulan. */
    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
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
        return $this->isOwner() || $this->isDeveloper() || $this->dashboardAccess->isNotEmpty();
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

    /**
     * Developer = akses Tingkat 2: area /owner (kecuali Dashboard Access),
     * lihat absensi semua orang di Rekap, kelola karyawan non-Owner, dan
     * reset password non-Owner. Hak modul dashboard-nya tetap datang dari
     * tabel `dashboard_access` (bukan hard-code seperti Owner).
     */
    public function isDeveloper(): bool
    {
        return $this->role === 'developer';
    }

    /** Boleh masuk area `/owner/*` (kecuali Dashboard Access yang khusus Owner). */
    public function isOwnerOrDeveloper(): bool
    {
        return $this->isOwner() || $this->isDeveloper();
    }

    /**
     * ID user yang absensinya boleh dilihat oleh user ini.
     * Dipakai bareng oleh Rekap Absensi (RecapController::scopedUsers(),
     * TIDAK berubah perilakunya) dan Export Rekap Absensi
     * (AttendanceRecapExport, fix 2026-09-23 — sebelumnya export
     * membaca SEMUA karyawan tanpa scope, beda dari halaman Rekapnya
     * sendiri yang sudah dibatasi).
     *
     * - Owner, HRD, Developer -> semua karyawan.
     * - Selain itu (termasuk Manajer, atau karyawan biasa yang
     *   di-assign modul `people`) -> diri sendiri + seluruh bawahan
     *   turunan (bukan cuma bawahan langsung).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function visibleAttendanceUserIds(): \Illuminate\Support\Collection
    {
        if ($this->isOwner() || $this->isHrd() || $this->isDeveloper()) {
            return static::query()->pluck('id');
        }

        $all = static::query()->get(['id', 'manager_id']);
        $byManager = $all->groupBy('manager_id');

        $ids = collect([$this->id]);
        $queue = [$this->id];

        while ($queue) {
            $currentId = array_shift($queue);
            foreach ($byManager->get($currentId, collect()) as $child) {
                if (! $ids->contains($child->id)) {
                    $ids->push($child->id);
                    $queue[] = $child->id;
                }
            }
        }

        return $ids;
    }

    /** Daftar role beserta labelnya (urutan = urutan di dropdown). */
    public const ROLE_LABELS = [
        'karyawan' => 'Karyawan',
        'manajer' => 'Manajer',
        'hrd' => 'HRD',
        'developer' => 'Developer',
        'owner' => 'Owner',
    ];

    /**
     * Boleh membuat/mengubah/menonaktifkan/mengaktifkan kembali akun
     * `$target` lewat menu Karyawan? Owner boleh semuanya; Developer boleh
     * semua akun KECUALI akun Owner; role lain tidak boleh sama sekali.
     */
    public function canManageAccount(User $target): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->isDeveloper() && ! $target->isOwner();
    }

    /**
     * Boleh mereset password akun `$target` lewat dashboard IT (gerbang
     * modul `it` dicek terpisah di route)? Tidak boleh mereset akun sendiri
     * (pakai Profil), akun Owner hanya oleh Owner, dan akun Developer
     * hanya oleh Owner atau Developer lain (supaya pemegang akses IT biasa
     * tidak bisa menaikkan dirinya lewat akun Developer).
     */
    public function canResetPasswordOf(User $target): bool
    {
        if ($this->is($target)) {
            return false;
        }

        if ($this->isOwner()) {
            return true;
        }

        if ($target->isOwner()) {
            return false;
        }

        if ($target->isDeveloper()) {
            return $this->isDeveloper();
        }

        return true;
    }

    /**
     * Role yang boleh DIPILIH user ini saat membuat/mengubah akun.
     * Owner: semua. Developer: Karyawan/Manajer/HRD saja, tidak boleh
     * membuat Owner atau Developer baru (kecuali mempertahankan role
     * Developer milik akun yang sedang diedit).
     *
     * @return list<string>
     */
    public function assignableRoles(?User $target = null): array
    {
        if ($this->isOwner()) {
            return array_keys(self::ROLE_LABELS);
        }

        if (! $this->isDeveloper()) {
            return [];
        }

        $roles = ['karyawan', 'manajer', 'hrd'];

        if ($target?->isDeveloper()) {
            $roles[] = 'developer';
        }

        return $roles;
    }

    /** Label role yang enak dibaca (dipakai di badge tabel karyawan). */
    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? 'Karyawan';
    }

    /**
     * App Mode quick win (2026-09-09) — kartu Milestones di Home.
     * Padanan `birthdayInfo(emp)` di prototype (`occurrenceInfo`,
     * kind='birthday'). Null kalau `birth_date` belum diisi Owner di
     * Master Karyawan (kolomnya sudah ada sejak Fase 2, cuma belum
     * dipakai buat ini).
     *
     * @return array{date: Carbon, days: int, years: null}|null
     */
    public function nextBirthdayOccurrence(): ?array
    {
        return $this->nextAnnualOccurrence($this->birth_date, false);
    }

    /**
     * Padanan `anniversaryInfo(emp)` di prototype — tanggal kerja
     * berikutnya + sudah tahun ke berapa. Null kalau `join_date` belum
     * diisi.
     *
     * @return array{date: Carbon, days: int, years: int}|null
     */
    public function nextWorkAnniversaryOccurrence(): ?array
    {
        return $this->nextAnnualOccurrence($this->join_date, true);
    }

    /**
     * Cari kejadian tahunan terdekat (tahun ini atau tahun depan) dari
     * bulan+tanggal `$source` — tahun aslinya diabaikan kecuali buat
     * hitung `years` pas anniversary. 29 Februari di tahun non-kabisat
     * digeser ke 28 Februari, sama seperti `occurrenceInfo()` di
     * prototype (`candidate.setDate(0)`).
     */
    private function nextAnnualOccurrence(?Carbon $source, bool $isAnniversary): ?array
    {
        if (! $source) {
            return null;
        }

        $today = Carbon::today();

        $buildFor = function (int $year) use ($source) {
            try {
                return Carbon::create($year, $source->month, $source->day);
            } catch (\Exception) {
                return Carbon::create($year, $source->month, 1)->endOfMonth();
            }
        };

        $next = $buildFor($today->year);

        if ($isAnniversary) {
            // Belum genap 1 tahun sejak join_date -> anniversary pertama
            // dulu, bukan tanggal bulan-ini/tahun-ini yang mungkin masih
            // di masa lalu.
            $firstAnniversary = $source->copy()->addYear();
            if ($today->lt($firstAnniversary)) {
                $next = $firstAnniversary;
            } elseif ($next->lt($today)) {
                $next = $buildFor($today->year + 1);
            }
        } elseif ($next->lt($today)) {
            $next = $buildFor($today->year + 1);
        }

        return [
            'date' => $next,
            'days' => $today->diffInDays($next),
            'years' => $isAnniversary ? max(1, $next->year - $source->year) : null,
        ];
    }

    /**
     * Label "lama bekerja" (mis. "2 tahun 3 bulan 10 hari"). Padanan
     * `serviceDuration()` di prototype. Null kalau `join_date` belum
     * diisi atau masih tanggal di masa depan.
     */
    public function serviceDurationLabel(): ?string
    {
        if (! $this->join_date || Carbon::today()->lt($this->join_date)) {
            return null;
        }

        $diff = $this->join_date->diff(Carbon::today());

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = "{$diff->y} tahun";
        }
        if ($diff->m > 0 || $diff->y > 0) {
            $parts[] = "{$diff->m} bulan";
        }
        $parts[] = "{$diff->d} hari";

        return implode(' ', $parts);
    }
}