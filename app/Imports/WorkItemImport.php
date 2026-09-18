<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * WorkItemImport — Batch 3.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ImportController
 * Catalog key: 'work-tracker' (lihat App\Support\ExportImport\ExportCatalog)
 *
 * Kolom template SENGAJA dalam Bahasa Indonesia & cocok sama urutan
 * WorkItemExport (versi export-nya), TAPI keynya (hasil WithHeadingRow)
 * beda dari nama kolom `work_items` sendiri — 'project'/'pic' di sini
 * nama ORANG/PROJECT (teks bebas diisi user di Excel), bukan foreign
 * key id, jadi rules() validasi via `exists:table,name` (kolom `name`,
 * bukan `id`) lalu mapRow() lookup id-nya buat WorkItem::create().
 *
 * 'progress'/'prioritas' dibiarkan NULLABLE di rules() (beda dari
 * WorkItemRequest yang mewajibkan keduanya buat form manual satu-satu)
 * — biar bulk-import tetep lolos kalau user belum sempat isi kolom itu
 * per baris, mapRow() kasih fallback ('progress' default 'Pending' -
 * sama default kolom DB-nya, 'priority' default null - sama-sama
 * nullable di migration).
 */
class WorkItemImport extends BaseImport
{
    public function templateHeadings(): array
    {
        return ['project', 'section', 'judul', 'tenggat', 'pic', 'progress', 'prioritas', 'catatan'];
    }

    public function previewColumns(): array
    {
        return [
            ['label' => 'Judul', 'key' => 'title'],
            ['label' => 'Project', 'key' => 'project_name', 'fallback' => 'Tanpa Project'],
            ['label' => 'Section', 'key' => 'section'],
            ['label' => 'Tenggat', 'key' => 'due_date', 'type' => 'date', 'fallback' => '-'],
            ['label' => 'PIC', 'key' => 'pic_name', 'fallback' => '-'],
            ['label' => 'Progress', 'key' => 'progress'],
            ['label' => 'Prioritas', 'key' => 'priority', 'fallback' => '-'],
            ['label' => 'Catatan', 'key' => 'notes', 'fallback' => '-'],
        ];
    }

    public function rules(): array
    {
        return [
            'project' => ['nullable', 'string', 'exists:projects,name'],
            'section' => ['nullable', 'string', 'max:80'],
            'judul' => ['required', 'string', 'max:255'],
            'tenggat' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && $this->parseDate($value) === null) {
                        $fail('Tenggat harus tanggal yang valid (format DD/MM/YYYY).');
                    }
                },
            ],
            'pic' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && ! $this->findUser($value)) {
                        $fail('PIC tidak ditemukan — isi nama atau email karyawan yang sudah terdaftar.');
                    }
                },
            ],
            'progress' => ['nullable', Rule::in(WorkItem::PROGRESS_OPTIONS)],
            'prioritas' => ['nullable', Rule::in(WorkItem::PRIORITIES)],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function mapRow(array $validated): array
    {
        $project = ! empty($validated['project']) ? Project::query()->where('name', $validated['project'])->first() : null;
        $pic = ! empty($validated['pic']) ? $this->findUser($validated['pic']) : null;

        return [
            'project_id' => $project?->id,
            'section' => $validated['section'] !== '' && ! empty($validated['section']) ? $validated['section'] : 'OTHER',
            'title' => $validated['judul'],
            'due_date' => $this->parseDate($validated['tenggat'] ?? null),
            'pic_employee_id' => $pic?->id,
            'additional_pic' => null,
            'progress' => ! empty($validated['progress']) ? $validated['progress'] : 'Pending',
            'priority' => ! empty($validated['prioritas']) ? $validated['prioritas'] : null,
            'notes' => $validated['catatan'] ?? null,
            'link' => null,
            'is_reminder' => false,
            // Dua key di bawah BUKAN kolom `work_items` (diabaikan
            // otomatis sama Eloquent pas mass-assign, model ini pakai
            // #[Fillable] jadi key di luar daftar itu gak nyangkut) —
            // sengaja disisipkan cuma buat halaman preview, biar
            // nampilin "Aldora"/"Album Q3 Release" (nama), bukan angka
            // id yang gak berarti apa-apa buat user yang lagi ngecek.
            'project_name' => $project?->name,
            'pic_name' => $pic?->name,
        ];
    }

    private function findUser(string $value): ?User
    {
        return User::query()
            ->where('name', $value)
            ->orWhere('email', $value)
            ->first();
    }

    /**
     * Terima 2 bentuk: teks "DD/MM/YYYY" (format yang diminta di
     * template) ATAU angka serial Excel (kalau cell-nya diformat
     * sebagai Date beneran di Excel, bukan Text — package baca
     * mentahnya sebagai float, bukan string tanggal).
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim((string) $value));
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}