<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * ProjectWorkbookExport — item 6 daftar selisih prototype v32
 * (padanan `exportProjectXlsxV28()` di prototype: tombol "XLSX" per project).
 *
 * SATU file, DUA sheet:
 *   1. "Project"  — info project (nama, tanggal, prioritas, status, lead, dst).
 *   2. "Tracker"  — semua task project dengan susunan kolom yang sama dengan
 *      tracker di Google Sheet tim (SECTION / NO / ITEM / DATE / FOCUS / PIC /
 *      PROGRESS / NOTE / LINK) + kolom "ID WSM" di paling kanan.
 *
 * "ID WSM" sengaja ikut terekspor: itulah yang membuat file ini bisa DIBALIK
 * lagi ke WSM Office lewat halaman "Sinkron Sheet" (App\Support\ProjectSheet\
 * SheetSync) tanpa menggandakan task — baris ber-ID = update task itu, baris
 * tanpa ID = task baru. Prototype memakai nomor baris sheet untuk hal yang
 * sama, tapi nomor baris bergeser begitu ada yang menyisipkan baris; ID task
 * tidak.
 *
 * Kalau file dibuka di Google Sheets lalu kolom ID WSM dihapus, sinkron masih
 * jalan: task dicocokkan lewat section + judul (lihat SheetSync).
 */
class ProjectWorkbookExport implements FromArray, WithMultipleSheets
{
    public function __construct(private readonly Project $project, private readonly ?string $exportedBy = null) {}

    /**
     * Excel::download() mewajibkan tipe Export; WithMultipleSheets saja tidak
     * cukup. Isi array() ini tidak pernah dipakai — sheets() yang menentukan.
     */
    public function array(): array
    {
        return [];
    }

    public function sheets(): array
    {
        return [
            new ProjectInfoSheet($this->project, $this->exportedBy),
            new ProjectTrackerSheet($this->project),
        ];
    }
}