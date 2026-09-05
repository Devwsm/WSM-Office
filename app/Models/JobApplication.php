<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model JobApplication
 * ---------------------------------------------------------------------
 * Lamaran dari form publik (Fase 3). `status` adalah pipeline yang
 * dikelola HRD/Owner lewat panel Rekrutmen:
 *   baru -> ditinjau -> interview -> ditawari -> diterima / ditolak
 * `converted_user_id` kesisi kalau pelamar sudah di-convert jadi akun
 * karyawan (lihat Recruitment\JobApplicationController@convert).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'job_opening_id',
    'name',
    'email',
    'phone',
    'message',
    'status',
    'notes',
    'converted_user_id',
])]
class JobApplication extends Model
{
    use HasFactory;

    /** Urutan tahap pipeline, dipakai buat tab filter & progres di panel HRD/Owner. */
    public const STATUSES = ['baru', 'ditinjau', 'interview', 'ditawari', 'diterima', 'ditolak'];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    public function isConverted(): bool
    {
        return ! is_null($this->converted_user_id);
    }

    /** Label status yang enak dibaca (badge di panel HRD/Owner). */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'ditinjau' => 'Ditinjau',
            'interview' => 'Interview',
            'ditawari' => 'Ditawari',
            'diterima' => 'Diterima',
            'ditolak' => 'Ditolak',
            default => 'Baru',
        };
    }

    /** Warna badge (dipetakan ke class .badge-wsm-* yang sudah ada). */
    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'ditinjau', 'interview' => 'blue',
            'ditawari' => 'yellow',
            'diterima' => 'green',
            'ditolak' => 'red',
            default => 'gray',
        };
    }
}
