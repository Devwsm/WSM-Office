<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model PayrollRecord
 * ---------------------------------------------------------------------
 * Fase 12 — histori payroll per bulan per karyawan. DEVIASI DISENGAJA
 * dari prototype (yang cuma hitung estimasi on-the-fly, gak pernah
 * disimpan) — lihat catatan lengkap di migration
 * `create_payroll_records_table`. `base_salary` SALINAN nilai
 * `users.salary_base` pas digenerate, bukan referensi live.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'period',
    'base_salary',
    'overtime_amount',
    'shortage_deduction',
    'absent_days',
    'absence_deduction',
    'work_days_divisor',
    'other_adjustment',
    'total',
    'status',
    'notes',
    'generated_by',
    'reopened_by',
    'reopened_at',
    'reopen_reason',
])]
class PayrollRecord extends Model
{
    public const STATUSES = ['draft', 'finalized', 'paid'];

    protected function casts(): array
    {
        return [
            'base_salary' => 'float',
            'overtime_amount' => 'float',
            'shortage_deduction' => 'float',
            'absent_days' => 'integer',
            'absence_deduction' => 'float',
            'reopened_at' => 'datetime',
            'other_adjustment' => 'float',
            'total' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /** Hitung ulang `total` dari komponen-komponennya — dipanggil controller sebelum save, BUKAN otomatis lewat event (biar generate payroll tetap 1 langkah eksplisit, bukan efek samping). */
    public function recalculateTotal(): float
    {
        // Gaji tidak pernah minus (aturan payroll 2026-09-29, sama `Math.max(0, …)` prototype):
        // dipatok 0 SETELAH semua komponen dihitung, termasuk penyesuaian manual.
        $this->total = max(0.0, round(
            (float) $this->base_salary
                + (float) $this->overtime_amount
                - (float) $this->shortage_deduction
                - (float) ($this->absence_deduction ?? 0)
                + (float) ($this->other_adjustment ?? 0),
            2
        ));

        return $this->total;
    }

    /** Label status buat badge (Bahasa Indonesia) — sama pola LeaveRequest/OvertimeRequest::statusLabel(). */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'finalized' => 'Final',
            'paid' => 'Dibayar',
            default => 'Draft',
        };
    }

    /** Nama class badge yang udah ada di app.css (.badge-wsm-*). */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'finalized' => 'badge-wsm-blue',
            'paid' => 'badge-wsm-green',
            default => 'badge-wsm-gray',
        };
    }

    /** Format rupiah ringkas ("Rp 5.000.000") — dipakai di semua view Payroll. */
    public static function formatRupiah(?float $value): string
    {
        return 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    }

    /** Label periode manusiawi ("September 2026") dari kolom `period` (format YYYY-MM). */
    public function periodLabel(): string
    {
        return Carbon::createFromFormat('!Y-m', $this->period)->translatedFormat('F Y');
    }
}