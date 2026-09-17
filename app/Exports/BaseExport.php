<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * BaseExport
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi Export & Import) — kerangka dasar yang dipakai
 * SEMUA export Excel per-modul (Batch 1: Rekap Absensi, KPI, Budget,
 * Royalty, Contracts, Legal, Audit Log, Manajemen Karyawan, Pelamar,
 * Work Tracker, Rekap Cuti Tim). Butuh package `maatwebsite/excel`
 * (belum terpasang — lihat README bagian Export & Import, "Yang perlu
 * dijalankan manual").
 *
 * CARA PAKAI (Batch 1 dst.): bikin class baru extends BaseExport, isi
 * 3 method abstract di bawah, lalu panggil lewat
 * `Excel::download(new XxxExport($data), 'nama-file.xlsx')` di
 * controller. Method rows()/headings()/map() DIPISAH dari controller
 * (bukan langsung query di sini) — controller yang nyiapin data
 * (query + filter periode/karyawan/dll), class Export ini cuma
 * ngurusin "cara nulisnya ke Excel", biar data yang sama juga gampang
 * dipakai buat PREVIEW di layar (lihat `previewRows()` — dipanggil
 * controller SEBELUM `Excel::download()`, jadi preview & file yang
 * beneran didownload dijamin isinya identik, gak ada 2 sumber logic
 * yang bisa beda).
 * ---------------------------------------------------------------------
 */
abstract class BaseExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Data mentah yang mau diekspor (Eloquent Collection/support
     * Collection hasil query dari controller). WAJIB collection yang
     * SAMA dipakai buat preview di layar & buat file yang didownload.
     */
    abstract public function rows(): Collection;

    /**
     * Nama kolom, urut sesuai urutan yang dikembalikan map().
     *
     * @return array<int, string>
     */
    abstract public function headings(): array;

    /**
     * Ubah 1 baris data mentah (dari rows()) jadi array nilai per
     * kolom, urutannya HARUS sama persis sama headings().
     *
     * @return array<int, mixed>
     */
    abstract public function map($row): array;

    /** Judul sheet Excel — dipendekin otomatis (Excel batasin 31 karakter). */
    public function title(): string
    {
        return mb_substr(static::class, 0, 31);
    }

    public function collection(): Collection
    {
        return $this->rows();
    }

    /**
     * Dipanggil controller buat nampilin PREVIEW di layar SEBELUM
     * user klik "Download" — array biasa (bukan file), tinggal
     * di-loop di Blade pakai headings()+ini.
     *
     * @return array<int, array<int, mixed>>
     */
    public function previewRows(): array
    {
        return $this->rows()->map(fn($row) => $this->map($row))->all();
    }

    /** Baris judul kolom ditebalkan — dipakai semua export turunan, jangan di-override kecuali beneran perlu beda. */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}