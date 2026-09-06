<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model OvertimeRequest (Lembur)
 * ---------------------------------------------------------------------
 * Fase 7 — pengajuan Lembur WFO. Alur status identik `LeaveRequest`
 * (Fase 5): pending -> disetujui/ditolak, pending/disetujui ->
 * dibatalkan (wajib alasan). Siapa yang boleh approve/reject/cancel
 * dihitung di controller (`Approval\OvertimeRequestController`), bukan
 * di sini — sama pola dengan LeaveRequest.
 *
 * TIDAK ada kolom nominal rupiah di sini SENGAJA — lihat catatan di
 * migration `create_overtime_requests_table` (ditunda ke Fase 12).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'date',
    'reason',
    'status',
])]
class OvertimeRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            default => 'Pending',
        };
    }

    /** Nama class badge yang udah ada di app.css (.badge-wsm-*), sama pola LeaveRequest. */
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

    /** Boleh dibatalkan kalau masih pending/disetujui DAN tanggalnya belum lewat. */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'disetujui'], true)
            && $this->date->toDateString() >= now()->toDateString();
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

    /**
     * Dipakai `Attendance` (auto-close & shortage) buat cek: hari ini
     * user tsb punya Lembur yang UDAH disetujui gak. Kalau ada, jam
     * kerja hari itu gak di-auto-close jam 20:00 & gak kena hitungan
     * "Kurang Jam Kerja".
     */
    public static function approvedFor(int $userId, string $date): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', 'disetujui')
            ->whereDate('date', $date)
            ->first();
    }
}