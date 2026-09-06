<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model LeaveRequest
 * ---------------------------------------------------------------------
 * Fase 5 — pengajuan izin/cuti. Alur status:
 *   pending -> disetujui / ditolak
 *   pending / disetujui -> dibatalkan (oleh karyawan sendiri ATAU
 *   Manajer/Owner yang berwenang, keduanya WAJIB isi alasan)
 *
 * Siapa yang boleh approve/reject/cancel dihitung di controller
 * (Approval\LeaveRequestController::canDecide()), bukan di sini — model
 * cuma nyimpen state transition-nya lewat approveBy()/rejectBy()/
 * cancelBy() biar logic-nya nggak keulang di 2 controller (Employee &
 * Approval).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'type',
    'start_date',
    'end_date',
    'work_days',
    'reason',
    'status',
])]
class LeaveRequest extends Model
{
    use HasFactory;

    public const TYPES = ['cuti_tahunan', 'izin_sakit', 'izin_pribadi', 'lainnya'];

    /** Cuma tipe ini yang motong saldo cuti tahunan (kesepakatan Fase 5). */
    public const QUOTA_TYPE = 'cuti_tahunan';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'decided_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'cuti_tahunan' => 'Cuti Tahunan',
            'izin_sakit' => 'Izin Sakit',
            'izin_pribadi' => 'Izin Pribadi',
            default => 'Lainnya',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            default => 'Pending',
        };
    }

    /** Nama class badge yang udah ada di app.css (.badge-wsm-*). */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'disetujui' => 'badge-wsm-green',
            'ditolak' => 'badge-wsm-red',
            'dibatalkan' => 'badge-wsm-gray',
            default => 'badge-wsm-yellow',
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Boleh dibatalkan kalau statusnya masih pending/disetujui DAN belum kelar (end_date belum lewat). */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'disetujui'], true)
            && $this->end_date->toDateString() >= now()->toDateString();
    }

    public function approveBy(User $approver): void
    {
        $this->update([
            'status' => 'disetujui',
            'approver_id' => $approver->id,
            'decided_at' => now(),
        ]);
    }

    public function rejectBy(User $approver, string $reason): void
    {
        $this->update([
            'status' => 'ditolak',
            'approver_id' => $approver->id,
            'decided_at' => now(),
            'decision_note' => $reason,
        ]);
    }

    public function cancelBy(User $canceller, string $reason): void
    {
        $this->update([
            'status' => 'dibatalkan',
            'cancelled_by' => $canceller->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /** Hitung jumlah hari kerja (Senin-Jumat) di rentang tanggal, inklusif. Hardcode dulu — nyusul jadi setting pas ada CMS. */
    public static function countWorkDays(string|Carbon $start, string|Carbon $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $count = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! $date->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }

    /** Approved leave (kalau ada) yang nyakup tanggal tertentu buat 1 user. */
    public static function approvedFor(int $userId, string $date): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }
}