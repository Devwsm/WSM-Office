<?php

namespace App\Imports;

use App\Models\Project;

/**
 * ProjectBudgetImport — Batch 4 (terakhir, Project Budgeting).
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ImportController
 * Catalog key: 'budget' (lihat App\Support\ExportImport\ExportCatalog)
 *
 * Paling sederhana dari 4 importer yang ada — gak ada tanggal, gak ada
 * lookup user, cuma 1 lookup FK (project, teks bebas nama project di
 * Excel dicocokkan ke tabel `projects` lewat kolom `name`, sama pola
 * kolom 'project' di WorkItemImport, tapi di sini WAJIB — beda dari
 * WorkItemImport yang boleh kosong, soalnya baris budget tanpa project
 * gak ada artinya).
 *
 * BudgetRequest (form manual) mewajibkan 'budget' tapi 'actual'
 * nullable — diikuti persis di sini (fallback 'realisasi' kosong -> 0,
 * sama default kolom `actual` di migration).
 */
class ProjectBudgetImport extends BaseImport
{
    public function templateHeadings(): array
    {
        return ['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan'];
    }

    public function previewColumns(): array
    {
        return [
            ['label' => 'Project', 'key' => 'project_name'],
            ['label' => 'Kategori', 'key' => 'category'],
            ['label' => 'Item', 'key' => 'item'],
            ['label' => 'Anggaran', 'key' => 'budget'],
            ['label' => 'Realisasi', 'key' => 'actual'],
            ['label' => 'Catatan', 'key' => 'note', 'fallback' => '-'],
        ];
    }

    public function fieldNotes(): array
    {
        return [
            'project' => ['required' => true, 'note' => 'Isi nama project yang SUDAH ada di sistem.'],
            'kategori' => ['required' => true, 'note' => 'Teks bebas, mis. "Creative"/"Marketing"/"Production".'],
            'item' => ['required' => true],
            'anggaran' => ['required' => true],
            'realisasi' => ['required' => false, 'note' => 'Kalau kosong, otomatis 0.'],
            'catatan' => ['required' => false],
        ];
    }

    public function rules(): array
    {
        return [
            'project' => ['required', 'string', 'exists:projects,name'],
            'kategori' => ['required', 'string', 'max:100'],
            'item' => ['required', 'string', 'max:150'],
            'anggaran' => ['required', 'numeric', 'min:0'],
            'realisasi' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function mapRow(array $validated): array
    {
        $project = Project::query()->where('name', $validated['project'])->first();

        return [
            'project_id' => $project?->id,
            'category' => $validated['kategori'],
            'item' => $validated['item'],
            'budget' => (float) $validated['anggaran'],
            // 0 = default kolom `actual` di migration, ditulis eksplisit
            // biar preview nampilin angka yang beneran bakal kesimpen.
            'actual' => $validated['realisasi'] !== '' && $validated['realisasi'] !== null ? (float) $validated['realisasi'] : 0.0,
            'note' => $validated['catatan'] ?? null,
            // Display-only buat preview (diabaikan Eloquent pas
            // mass-assign) — sama trik kayak project_name/pic_name di
            // WorkItemImport.
            'project_name' => $project?->name,
        ];
    }
}