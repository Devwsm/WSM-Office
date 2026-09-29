<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AttendanceAbsence
 * ---------------------------------------------------------------------
 * Penanda "Absen (A)" manual: satu baris = satu karyawan tidak masuk
 * TANPA keterangan pada satu tanggal. Diisi Manajer/HRD/Owner lewat
 * halaman rekap absensi (modul `people`, level manage). Padanan
 * `manualStatus === 'Absen (A)'` di prototype v32.
 *
 * Payroll HANYA memotong hari yang ditandai di sini (keputusan opsi B):
 * hari tanpa clock-in yang tidak ditandai tidak dipotong. Hari libur,
 * izin, cuti, dan sakit tidak boleh ditandai absen.
 * ---------------------------------------------------------------------
 */
#[Fillable(['user_id', 'date', 'note', 'marked_by'])]
class AttendanceAbsence extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}