<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AttendanceCorrectionRequest (Koreksi Presensi)
 * ---------------------------------------------------------------------
 * 2026-09-15/16 — pengajuan koreksi presensi self-service karyawan. Alur
 * status IDENTIK LeaveRequest/OvertimeRequest: pending -> disetujui/
 * ditolak, pending -> dibatalkan (lihat isCancellable() — SENGAJA cuma
 * dari pending, beda dari LeaveRequest yang izinin batalkan walau udah
 * disetujui, karena "disetujui" di sini berarti korekainnya udah kepatri
 * ke baris `attendances` asli lewat applyToAttendance()).
 *
 * 2026-09-16 — approveBy()/applyToAttendance() dilengkapi (kemarin baru
 * kerangka model + migration, controller/route/view/logic approve belum
 * ada sama sekali).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'date',
    'requested_clock_in',
    'requested_clock_out',
    'requested_mode',
    'reason',
    'status',
])]
class AttendanceCorrectionRequest extends Model
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

    public function appliedAttendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'applied_attendance_id');
    }

    public function modeLabel(): string
    {
        return match ($this->requested_mode) {
            'wfh' => 'WFH',
            'lapangan' => 'Lapangan',
            'gigs' => 'Gigs',
            default => 'Kantor (WFO)',
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

    /** Nama class badge yang udah ada di app.css (.badge-wsm-*), sama pola LeaveRequest/OvertimeRequest. */
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

    /**
     * Boleh dibatalkan CUMA kalau masih pending — begitu disetujui,
     * koreksinya udah nempel ke `attendances` asli (`applied_attendance_id`
     * keisi), jadi "batalkan" butuh alur revert Attendance terpisah yang
     * sengaja belum dicakup di scope ini.
     */
    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }

    public function requestedTimeLabel(): string
    {
        return match (true) {
            $this->requested_clock_in && $this->requested_clock_out => "Masuk {$this->requested_clock_in} · Pulang {$this->requested_clock_out}",
            (bool) $this->requested_clock_in => "Masuk {$this->requested_clock_in}",
            (bool) $this->requested_clock_out => "Pulang {$this->requested_clock_out}",
            default => '-',
        };
    }

    /**
     * Terapin koreksi yang diminta ke baris Attendance asli hari itu —
     * bikin baru (session_number 1, mode = requested_mode) kalau
     * karyawan bener-bener belum absen sama sekali hari itu. Nyambung ke
     * kolom `corrected_by`/`corrected_at`/`correction_note`/
     * `original_clock_in_at`/`original_clock_out_at` yang UDAH ADA dari
     * Fase 7 (Attendance\RecapController::correct()) — biar riwayat jam
     * asli tetap kebaca dari 1 tempat yang sama, siapapun yang ngoreksi
     * (langsung oleh Manajer/Owner ATAU lewat pengajuan ini).
     *
     * Kalau baris attendance-nya UDAH ADA, mode yang ada TIDAK diubah
     * (cuma jam) — sama kesepakatan CorrectAttendanceRequest: "cuma edit
     * jam, bukan override status/mode".
     */
    public function applyToAttendance(User $approver): Attendance
    {
        $attendance = Attendance::query()
            ->where('user_id', $this->user_id)
            ->where('date', $this->date->toDateString())
            ->orderBy('session_number')
            ->first();

        if (! $attendance) {
            $attendance = Attendance::query()->create([
                'user_id' => $this->user_id,
                'date' => $this->date->toDateString(),
                'session_number' => 1,
                'mode' => $this->requested_mode,
            ]);
        }

        if ($this->requested_clock_in) {
            if ($attendance->original_clock_in_at === null && $attendance->clock_in_at) {
                $attendance->original_clock_in_at = $attendance->clock_in_at;
            }
            $attendance->clock_in_at = $this->date->copy()->setTimeFromTimeString($this->requested_clock_in);
        }

        if ($this->requested_clock_out) {
            if ($attendance->original_clock_out_at === null && $attendance->clock_out_at) {
                $attendance->original_clock_out_at = $attendance->clock_out_at;
            }
            $attendance->clock_out_at = $this->date->copy()->setTimeFromTimeString($this->requested_clock_out);
        }

        $attendance->corrected_by = $approver->id;
        $attendance->corrected_at = now();
        $attendance->correction_note = "Disetujui dari pengajuan Koreksi Presensi #{$this->id}: {$this->reason}";
        $attendance->save();

        return $attendance;
    }

    public function approveBy(User $approver): void
    {
        $attendance = $this->applyToAttendance($approver);

        $this->update([
            'status' => 'disetujui',
            'applied_attendance_id' => $attendance->id,
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
}