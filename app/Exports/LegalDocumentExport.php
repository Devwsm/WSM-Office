<?php

namespace App\Exports;

use App\Models\LegalDocument;
use Illuminate\Support\Collection;

/**
 * LegalDocumentExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'legal' (lihat App\Support\ExportImport\ExportCatalog)
 */
class LegalDocumentExport extends BaseExport
{
    public function rows(): Collection
    {
        return LegalDocument::query()->orderBy('end_date')->get();
    }

    public function headings(): array
    {
        return ['Kategori', 'Judul', 'Pihak', 'Mulai', 'Berakhir', 'Segera Berakhir?', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->categoryLabel(),
            $row->title,
            $row->party,
            optional($row->start_date)->format('d/m/Y'),
            optional($row->end_date)->format('d/m/Y'),
            $row->isExpiringSoon() ? 'Ya' : 'Tidak',
            $row->notes,
        ];
    }
}