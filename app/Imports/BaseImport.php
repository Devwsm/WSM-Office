<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * BaseImport
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi Export & Import) — kerangka dasar buat SEMUA import
 * per-modul (Batch 3: Work Tracker duluan, nyusul Manajemen
 * Karyawan/KPI/Budget). Butuh package `maatwebsite/excel` (belum
 * terpasang — lihat README bagian Export & Import).
 *
 * `WithHeadingRow` artinya baris pertama file Excel dianggap nama
 * kolom, dan tiap baris data masuk ke collection() sebagai array
 * asosiatif key=nama-kolom (huruf kecil, spasi jadi underscore —
 * bawaan package). Makanya kolom di TEMPLATE download (lihat
 * templateHeadings() di turunannya) harus PERSIS sama urutan/ejaannya
 * dengan yang dipakai di rules().
 *
 * Validasi jalan PER-BARIS (bukan seluruh file sekali gagal semua) —
 * baris yang lolos rules() masuk ke validRows(), yang gagal masuk ke
 * invalidRows() lengkap pesan errornya, biar user bisa lihat di
 * halaman preview mana yang perlu diperbaiki tanpa harus ulang upload
 * dari nol buat baris yang sudah benar.
 * ---------------------------------------------------------------------
 */
abstract class BaseImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, data: array}> */
    protected array $validRows = [];

    /** @var array<int, array{row: int, data: array, errors: array<int, string>}> */
    protected array $invalidRows = [];

    /**
     * Nama kolom buat file TEMPLATE yang didownload user (urutan bebas,
     * TAPI ejaannya harus nyambung ke key yang dipakai rules()/mapRow()
     * — package ubah "Nama Karyawan" jadi key `nama_karyawan`).
     *
     * @return array<int, string>
     */
    abstract public function templateHeadings(): array;

    /**
     * Kolom yang ditampilkan di tabel "Baris Valid" halaman preview
     * (ditambah pas Batch 4 — Work Tracker & Manajemen Karyawan beda-
     * beda field hasil mapRow()-nya, jadi blade preview digeneralisir
     * lewat sini biar gak nulis 1 file blade per modul). `key` = key
     * di array hasil mapRow(), `label` = judul kolom tabelnya, `type`
     * opsional ('date' -> diformat d/m/Y), `fallback` opsional = teks
     * kalau valuenya kosong/null (default '-').
     *
     * @return array<int, array{label: string, key: string, type?: string, fallback?: string}>
     */
    abstract public function previewColumns(): array;

    /**
     * Catatan per kolom template, ditampilin di halaman UPLOAD (bukan
     * preview) — biar user tau sebelum ngisi Excel-nya, mana kolom
     * yang WAJIB, mana yang BOLEH kosong, dan APA yang kejadian kalau
     * dikosongin (khususnya kolom yang ada fallback otomatis, mis.
     * `password` -> "password" kalau kosong). Key di array HARUS sama
     * persis kayak yang ada di templateHeadings() (urutan bebas, boleh
     * gak lengkap — kolom yang gak disebut dianggap opsional tanpa
     * catatan tambahan).
     *
     * @return array<string, array{required: bool, note?: string}>
     */
    abstract public function fieldNotes(): array;

    /**
     * Aturan validasi Laravel per-baris, key-nya = key hasil
     * WithHeadingRow (huruf kecil + underscore).
     *
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * Ubah 1 baris yang SUDAH LOLOS validasi jadi array field siap
     * dipakai Model::create()/Model::updateOrCreate() — controller
     * yang beneran nyimpannya (BaseImport ini cuma nyiapin datanya).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    abstract public function mapRow(array $validated): array;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = $row->toArray();

            // Baris kosong total (sering ada di ekor file Excel) dilewatin
            // aja, jangan dianggap error.
            if (collect($data)->filter(fn($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            // Nomor baris buat ditampilkan ke user: +1 karena heading row
            // sudah dipotong package, +1 lagi karena Excel mulai dari 1
            // bukan 0.
            $rowNumber = $index + 2;

            $validator = Validator::make($data, $this->rules());

            if ($validator->fails()) {
                $this->invalidRows[] = [
                    'row' => $rowNumber,
                    'data' => $data,
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $this->validRows[] = [
                'row' => $rowNumber,
                'data' => $this->mapRow($validator->validated()),
            ];
        }
    }

    /** @return array<int, array{row: int, data: array}> */
    public function validRows(): array
    {
        return $this->validRows;
    }

    /** @return array<int, array{row: int, data: array, errors: array<int, string>}> */
    public function invalidRows(): array
    {
        return $this->invalidRows;
    }
}