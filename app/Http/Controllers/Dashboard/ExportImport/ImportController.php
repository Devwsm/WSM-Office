<?php

namespace App\Http\Controllers\Dashboard\ExportImport;

use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BaseImport;
use App\Imports\WorkItemImport;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ExportImport\ExportCatalog;
use App\Support\ExportImport\ImportPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * ImportController (Dashboard > Export & Import)
 * ---------------------------------------------------------------------
 * Batch 3 — jalur import (Work Tracker duluan, lihat IMPLEMENTED di
 * bawah — menyusul Manajemen Karyawan/KPI/Project Budgeting). SATU
 * controller buat semua import (pola sama seperti ExportController buat
 * export), route generik `/{key}/import/...`, `$key` cocok ke
 * `ExportCatalog::CATALOG`.
 *
 * Alur 2 tahap (lihat docblock ImportPreviewService buat alasannya):
 *   1. show()    -> form upload + link download template.
 *   2. preview() -> parse file upload pakai App\Imports\BaseImport
 *      turunan (Excel::import(), BUKAN Excel::download()) -> staged ke
 *      ImportPreviewService -> render halaman preview (baris valid vs
 *      error, TIDAK ada yang masuk database di tahap ini).
 *   3. commit()  -> ambil lagi hasil staging pakai token yang sama dari
 *      preview() (BUKAN baca ulang file aslinya) -> insert baris valid
 *      ke database satu-satu lewat persist() -> forget() cache-nya.
 *
 * canImport() (butuh level 'manage', bukan cuma 'view') dicek di
 * SETIAP method publik di sini, bukan cuma show() — sama alasan kenapa
 * ExportController cek canView() ulang di preview() & download(): URL
 * bisa diakses langsung tanpa lewat halaman sebelumnya.
 * ---------------------------------------------------------------------
 */
class ImportController extends Controller
{
    /**
     * Subset key dari ExportCatalog::CATALOG yang import-nya BENERAN
     * jalan (bukan cuma 'import' => true di catalog). 'kpi', 'budget',
     * 'employees' juga sudah ditandai butuh import di catalog, tapi
     * class Import/persist()-nya belum ditulis — nyusul, lihat README.
     */
    private const IMPLEMENTED = ['work-tracker'];

    public function __construct(private readonly ImportPreviewService $previewService) {}

    public function show(Request $request, string $key): View
    {
        $entry = $this->resolveEntry($request, $key);
        $importer = $this->resolveImporter($key);

        return view('dashboard.export-import.import-show', [
            'title' => $entry['label'],
            'key' => $key,
            'headings' => $importer->templateHeadings(),
            'backUrl' => route('dashboard.export-import.index'),
            'templateUrl' => route('dashboard.export-import.import.template', ['key' => $key]),
            'previewUrl' => route('dashboard.export-import.import.preview', ['key' => $key]),
        ]);
    }

    public function template(Request $request, string $key): Response
    {
        $entry = $this->resolveEntry($request, $key);
        $importer = $this->resolveImporter($key);

        $filename = Str::slug('template-' . $entry['label']) . '.xlsx';

        return Excel::download(
            new TemplateExport($importer->templateHeadings(), $this->exampleRows($key)),
            $filename,
        );
    }

    public function preview(Request $request, string $key): View
    {
        $entry = $this->resolveEntry($request, $key);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel/CSV hasil isian template dulu.',
            'file.mimes' => 'File harus format .xlsx, .xls, atau .csv.',
        ]);

        $importer = $this->resolveImporter($key);
        Excel::import($importer, $request->file('file'));

        $token = $this->previewService->stage($request->user(), $key, [
            'valid' => $importer->validRows(),
            'invalid' => $importer->invalidRows(),
            'headings' => $importer->templateHeadings(),
        ]);

        return view('dashboard.export-import.import-preview', [
            'title' => $entry['label'],
            'key' => $key,
            'token' => $token,
            'headings' => $importer->templateHeadings(),
            'valid' => $importer->validRows(),
            'invalid' => $importer->invalidRows(),
            'backUrl' => route('dashboard.export-import.import.show', ['key' => $key]),
            'commitUrl' => route('dashboard.export-import.import.commit', ['key' => $key]),
        ]);
    }

    public function commit(Request $request, string $key): RedirectResponse
    {
        $entry = $this->resolveEntry($request, $key);

        $token = (string) $request->input('token');
        abort_if($token === '', 422, 'Token import tidak valid.');

        $payload = $this->previewService->retrieve($request->user(), $key, $token);
        abort_if($payload === null, 410, 'Sesi preview import sudah kadaluarsa (30 menit) — silakan upload ulang filenya.');

        $imported = 0;
        foreach ($payload['valid'] as $row) {
            $this->persist($key, $row['data'], $request->user());
            $imported++;
        }

        $this->previewService->forget($request->user(), $key, $token);

        $skipped = count($payload['invalid']);
        $status = "{$imported} baris berhasil diimport ke {$entry['label']}.";
        if ($skipped > 0) {
            $status .= " {$skipped} baris error tadi TIDAK ikut masuk (lihat lagi di halaman preview kalau mau perbaiki & upload ulang).";
        }

        return redirect()
            ->route('dashboard.export-import.index')
            ->with('status', $status);
    }

    /**
     * Cari entri catalog + pastikan user boleh IMPORT (level 'manage',
     * bukan cuma 'view') + modul ini beneran sudah diimplementasikan.
     *
     * @return array<string, mixed>
     */
    private function resolveEntry(Request $request, string $key): array
    {
        $entry = ExportCatalog::CATALOG[$key] ?? null;
        abort_if($entry === null, 404, 'Modul tidak ditemukan.');
        abort_unless($entry['import'], 404, 'Modul ini tidak punya fitur import.');
        abort_unless(ExportCatalog::canImport($request->user(), $key), 403);
        abort_unless(in_array($key, self::IMPLEMENTED, true), 404, 'Import untuk modul ini belum tersedia — segera hadir.');

        return $entry;
    }

    private function resolveImporter(string $key): BaseImport
    {
        return match ($key) {
            'work-tracker' => new WorkItemImport,
            default => abort(404),
        };
    }

    /** @return array<int, array<int, mixed>> */
    private function exampleRows(string $key): array
    {
        return match ($key) {
            'work-tracker' => [
                ['Album Q3 Release', 'RELEASE PLAN', 'Contoh: Finalisasi artwork cover', '01/10/2026', 'Aldora', 'Pending', 'High', 'Contoh catatan — opsional, boleh dikosongkan'],
            ],
            default => [],
        };
    }

    /** Simpan 1 baris valid ke database — logic per-modul beda (lihat komentar tiap cabang). */
    private function persist(string $key, array $data, User $user): void
    {
        match ($key) {
            'work-tracker' => $this->persistWorkItem($data, $user),
            default => throw new \RuntimeException("Belum ada cara nyimpan hasil import buat key '{$key}'."),
        };
    }

    /**
     * Sama persis logic WorkTrackerBoardController::storeItem() — item_no
     * dihitung ulang PER BARIS (bukan sekali di awal loop), biar tiap
     * baris baru di section/project yang sama dapat nomor urut yang
     * benar meski masih dalam 1 batch import yang sama.
     */
    private function persistWorkItem(array $data, User $user): void
    {
        $data['created_by'] = $user->id;
        $data['item_no'] = WorkItem::query()
            ->where('project_id', $data['project_id'] ?? null)
            ->where('section', $data['section'] ?? null)
            ->max('item_no') + 1;

        WorkItem::create($data);
    }
}