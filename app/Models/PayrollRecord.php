<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'other_adjustment',
    'total',
    'status',
    'notes',
    'generated_by',
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

    /** Hitung ulang `total` dari komponen-komponennya — dipanggil controller sebelum save, BUKAN otomatis lewat event (biar generate payroll tetap 1 langkah eksplisit, bukan efek samping). */
    public function recalculateTotal(): float
    {
        $this->total = round(
            (float) $this->base_salary
                + (float) $this->overtime_amount
                - (float) $this->shortage_deduction
                + (float) ($this->other_adjustment ?? 0),
            2
        );

        return $this->total;
    }
}