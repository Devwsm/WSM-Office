<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Support\Collection;

/**
 * AttendanceRecapExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'attendance-recap' (lihat App\Support\ExportImport\ExportCatalog)
 */
class AttendanceRecapExport extends BaseExport
{
    /**
     * @param  \Illuminate\Support\Collection<int, int>|null  $allowedUserIds
     *     ID karyawan yang boleh dilihat requester (dari
     *     User::visibleAttendanceUserIds()). Wajib diisi controller —
     *     fix 2026-09-23: sebelumnya export ini baca SEMUA karyawan
     *     kalau $userId kosong, tidak dibatasi scope tim manajer
     *     seperti halaman Rekap Absensi aslinya.
     */
    public function __construct(
        private readonly string $period, // format 'Y-m'
        private readonly ?int $userId = null,
        private readonly ?Collection $allowedUserIds = null,
    ) {}

    public function rows(): Collection
    {
        $start = "{$this->period}-01";
        $end = date('Y-m-t', strtotime($start));

        return Attendance::query()
            ->with('user')
            ->whereBetween('date', [$start, $end])
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when(
                ! $this->userId && $this->allowedUserIds !== null,
                fn($q) => $q->whereIn('user_id', $this->allowedUserIds ?? []),
            )
            ->orderBy('date')
            ->orderBy('user_id')
            ->orderBy('session_number')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Karyawan', 'Sesi', 'Mode', 'Konteks Kerja', 'Jam Masuk', 'Jam Pulang', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->date->format('d/m/Y'),
            $row->user->name,
            $row->session_number,
            ucfirst($row->mode),
            $row->work_context,
            $row->clock_in_at?->format('H:i'),
            $row->clock_out_at?->format('H:i'),
            $row->statusLabel(),
        ];
    }
}