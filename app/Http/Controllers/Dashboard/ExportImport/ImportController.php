<?php

namespace App\Http\Controllers\Dashboard\ExportImport;

use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BaseImport;
use App\Imports\EmployeeImport;
use App\Imports\KpiImport;
use App\Imports\ProjectBudgetImport;
use App\Imports\WorkItemImport;
use App\Models\AuditLog;
use App\Models\Kpi;
use App\Models\ProjectBudget;
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
 * Batch 3 & 4 — jalur import (lihat IMPLEMENTED di bawah untuk daftar
 * lengkap: Work Tracker, Manajemen Karyawan, KPI, Project Budgeting —
 * semua 4 modul rencana README sudah kelar). SATU controller buat
 * semua import (pola sama seperti ExportController buat export), route
 * generik `/{key}/import/...`, `$key` cocok ke `ExportCatalog::CATALOG`.
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
     * jalan (bukan cuma 'import' => true di catalog). Batch 4 kelar
     * penuh sekarang — 'kpi' & 'budget' nyusul 'employees' (urutan
     * README: Karyawan -> KPI -> Project Budgeting).
     */
    private const IMPLEMENTED = ['work-tracker', 'employees', 'kpi', 'budget'];

    public function __construct(private readonly ImportPreviewService $previewService) {}

    public function show(Request $request, string $key): View
    {
        $entry = $this->resolveEntry($request, $key);
        $importer = $this->resolveImporter($key);

        return view('dashboard.export-import.import-show', [
            'title' => $entry['label'],
            'key' => $key,
            'headings' => $importer->templateHeadings(),
            'fieldNotes' => $importer->fieldNotes(),
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

    public function preview(Request $request, string $key): View|RedirectResponse
    {
        $entry = $this->resolveEntry($request, $key);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel/CSV hasil isian template dulu.',
            'file.mimes' => 'File harus format .xlsx, .xls, atau .csv.',
        ]);

        $importer = $this->resolveImporter($key, $request->user());
        Excel::import($importer, $request->file('file'));

        // Kolom wajib hilang/berganti nama: berhenti di sini dengan pesan jelas
        // (bukan error 500 atau puluhan baris "wajib diisi" yang menyesatkan).
        if ($importer->missingColumns() !== []) {
            return back()->withErrors([
                'file' => 'Kolom wajib tidak ditemukan di file: ' . implode(', ', $importer->missingColumns())
                    . '. Pakai template terbaru dan jangan mengubah nama kolomnya.',
            ]);
        }

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
            'previewColumns' => $importer->previewColumns(),
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

        // Jaring pengaman kedua (aturan utamanya ada di EmployeeImport::rules()):
        // hanya Owner yang boleh membuat akun Owner/Developer lewat import.
        if ($key === 'employees' && ! $request->user()->isOwner()) {
            foreach ($payload['valid'] as $row) {
                abort_if(
                    in_array($row['data']['role'] ?? null, ['owner', 'developer'], true),
                    403,
                    'Akun Owner dan Developer hanya bisa dibuat oleh Owner.',
                );
            }
        }

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

    private function resolveImporter(string $key, ?User $actor = null): BaseImport
    {
        return match ($key) {
            'work-tracker' => new WorkItemImport,
            // Role Owner/Developer di file hanya diterima kalau yang mengimport Owner.
            'employees' => new EmployeeImport((bool) $actor?->isOwner()),
            'kpi' => new KpiImport,
            'budget' => new ProjectBudgetImport,
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
            'employees' => [
                ['Contoh Nama', 'contoh@wsm.test', '', 'karyawan', 'Marketing', 'Staff Marketing', '01/09/2026', '12', '', '', '', ''],
            ],
            'kpi' => [
                ['Aldora', 'Contoh: Jumlah Konten Dipublikasi', 'Q3 2026', '20', '14', 'konten', '30', '30/09/2026', 'Active', 'Contoh catatan owner — opsional, boleh dikosongkan'],
            ],
            'budget' => [
                ['Album Q3 Release', 'Contoh: Marketing', 'Contoh: Iklan Sosial Media', '15000000', '9500000', 'Contoh catatan — opsional, boleh dikosongkan'],
            ],
            default => [],
        };
    }

    /** Simpan 1 baris valid ke database — logic per-modul beda (lihat komentar tiap cabang). */
    private function persist(string $key, array $data, User $user): void
    {
        match ($key) {
            'work-tracker' => $this->persistWorkItem($data, $user),
            'employees' => $this->persistEmployee($data, $user),
            'kpi' => $this->persistKpi($data, $user),
            'budget' => $this->persistBudget($data, $user),
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

    /**
     * Bikin akun login baru (bukan cuma insert data biasa) — sama alur
     * `AuditLog::record()` seperti EmployeeController::store() (form
     * manual), cuma detail-nya dikasih tanda "lewat import" biar beda
     * kelihatan di log dari yang ditambah satu-satu manual.
     */
    private function persistEmployee(array $data, User $actor): void
    {
        $employee = User::create($data);

        AuditLog::record(
            'Karyawan ditambahkan',
            "{$employee->name} ({$employee->role}) ditambahkan lewat import oleh {$actor->name}.",
            $actor,
        );
    }

    /**
     * Sama persis KpiController::store() (form manual) — 'created_by'
     * gak lewat rules()/mapRow() (bukan input user), disisipkan di sini
     * baru pas mau disimpan, sama pola persistWorkItem() buat
     * 'created_by'. Belum kena AuditLog::record() — KPI/Budget CRUD
     * manual juga belum diinstrumentasi (lihat README bagian 7).
     */
    private function persistKpi(array $data, User $actor): void
    {
        $data['created_by'] = $actor->id;

        Kpi::create($data);
    }

    /** Sama persis BudgetController::store() (form manual) — 'updated_by' disisipkan di sini, sama pola persistKpi() di atas. */
    private function persistBudget(array $data, User $actor): void
    {
        $data['updated_by'] = $actor->id;

        ProjectBudget::create($data);
    }
}