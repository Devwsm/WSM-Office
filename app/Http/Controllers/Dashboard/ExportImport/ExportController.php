<?php

namespace App\Http\Controllers\Dashboard\ExportImport;

use App\Exports\AttendanceRecapExport;
use App\Exports\AuditLogExport;
use App\Exports\BaseExport;
use App\Exports\EmployeeContractExport;
use App\Exports\EmployeeExport;
use App\Exports\JobApplicationExport;
use App\Exports\KpiExport;
use App\Exports\LeaveRequestExport;
use App\Exports\LegalDocumentExport;
use App\Exports\ProjectBudgetExport;
use App\Exports\RoyaltyEntryExport;
use App\Exports\WorkItemExport;
use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use App\Models\Project;
use App\Models\User;
use App\Support\ExportImport\ExportCatalog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ExportController (Dashboard > Export & Import)
 * ---------------------------------------------------------------------
 * Batch 1 — SATU controller buat semua export Excel (bukan 1 controller
 * per modul), route-nya generik: `/{key}/{format}/preview` &
 * `/{key}/{format}/download`, `$key` cocok ke `ExportCatalog::CATALOG`.
 * Batch 2 (export PDF) numpang di controller yang SAMA — tinggal
 * tambah cabang `format === 'pdf'` di resolve(), TIDAK bikin
 * controller baru, biar route & alur akses tetap 1 pintu.
 *
 * Kenapa 1 controller generik, bukan 11 controller kayak modul lain
 * (Dashboard/Kpi, Dashboard/Budget, dst.)? Karena Export & Import itu
 * sendiri sudah 1 "modul" tersendiri di sidebar (section 9) — 11 modul
 * ASLI-nya numpang IZIN AKSES ke sini (lewat ExportCatalog), bukan
 * numpang KODE-nya. Kalau nanti export Payroll/dll butuh logic yang
 * jauh lebih rumit dari sekadar query+map, silakan pecah jadi
 * controller sendiri saat itu — untuk 11 modul Batch 1 ini polanya
 * masih seragam banget (query -> Excel), jadi numpuk di sini lebih
 * gampang dirawat daripada disebar ke 11 file yang isinya cuma
 * beda query.
 *
 * validated diambil dari accessLevelFor(), BUKAN dari middleware
 * `module:` di route — soalnya `$key` datang dari URL (dinamis),
 * sedangkan middleware `module:xxx` di route butuh nama modul yang
 * FIXED di definisi route. Levelnya sama persis (canView() ==
 * accessLevel() !== 'none'), cuma titik pengecekannya di controller.
 * ---------------------------------------------------------------------
 */
class ExportController extends Controller
{
    public function preview(Request $request, string $key, string $format): \Illuminate\View\View
    {
        ['export' => $export, 'title' => $title, 'filters' => $filters] = $this->resolve($request, $key, $format);

        return view('dashboard.export-import.preview', [
            'title' => $title,
            'headings' => $export->headings(),
            'rows' => $export->previewRows(),
            'filters' => $filters,
            'downloadUrl' => route('dashboard.export-import.download', array_merge(
                ['key' => $key, 'format' => $format],
                $request->query(),
            )),
            'backUrl' => route('dashboard.export-import.index'),
        ]);
    }

    public function download(Request $request, string $key, string $format): BinaryFileResponse
    {
        ['export' => $export, 'title' => $title] = $this->resolve($request, $key, $format);

        $filename = \Illuminate\Support\Str::slug($title) . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Cari entri catalog + pastikan user boleh, format-nya valid &
     * SUDAH diimplementasikan, lalu bangun instance Export-nya sesuai
     * $key (query & filter tiap modul beda, lihat App\Exports\*).
     *
     * @return array{export: BaseExport, title: string, filters: array<int, array<string, mixed>>}
     */
    private function resolve(Request $request, string $key, string $format): array
    {
        $entry = ExportCatalog::CATALOG[$key] ?? null;
        abort_if($entry === null, 404, 'Modul export tidak ditemukan.');
        abort_unless(ExportCatalog::canView($request->user(), $key), 403);
        abort_unless(in_array($format, $entry['implemented_exports'], true), 404, 'Format export ini belum tersedia.');

        // Batch 1 baru ngerjain Excel. Cabang 'pdf' nyusul di Batch 2
        // (numpang di controller yang sama, lihat catatan class ini).
        abort_unless($format === 'excel', 404);

        $period = $request->query('period'); // 'Y-m', dipakai beberapa modul
        $projectId = $request->integer('project_id') ?: null;

        [$export, $filters] = match ($key) {
            'attendance-recap' => [
                new AttendanceRecapExport($period ?: now()->format('Y-m'), $request->integer('employee_id') ?: null),
                [
                    $this->monthFilter($period),
                    $this->employeeFilter($request->integer('employee_id')),
                ],
            ],
            'kpi' => [
                new KpiExport($period ?: null),
                [$this->monthFilter($period, required: false)],
            ],
            'budget' => [
                new ProjectBudgetExport($projectId),
                [$this->projectFilter($projectId)],
            ],
            'royalty' => [
                new RoyaltyEntryExport($period ?: null),
                [$this->monthFilter($period, required: false)],
            ],
            'contracts' => [new EmployeeContractExport, []],
            'legal' => [new LegalDocumentExport, []],
            'audit-log' => [
                new AuditLogExport($request->query('from') ?: null, $request->query('to') ?: null),
                [
                    ['name' => 'from', 'label' => 'Dari Tanggal', 'type' => 'date', 'value' => $request->query('from')],
                    ['name' => 'to', 'label' => 'Sampai Tanggal', 'type' => 'date', 'value' => $request->query('to')],
                ],
            ],
            'employees' => [new EmployeeExport, []],
            'recruitment-applicants' => [
                new JobApplicationExport($request->integer('job_opening_id') ?: null),
                [$this->jobOpeningFilter($request->integer('job_opening_id'))],
            ],
            'work-tracker' => [
                new WorkItemExport($projectId),
                [$this->projectFilter($projectId)],
            ],
            'leave-recap' => [
                new LeaveRequestExport($period ?: null),
                [$this->monthFilter($period, required: false)],
            ],
            default => abort(404, 'Export untuk modul ini belum tersedia.'),
        };

        return ['export' => $export, 'title' => $entry['label'], 'filters' => $filters];
    }

    private function monthFilter(?string $value, bool $required = true): array
    {
        return [
            'name' => 'period',
            'label' => 'Periode' . ($required ? '' : ' (kosongkan = semua)'),
            'type' => 'month',
            'value' => $value,
        ];
    }

    private function projectFilter(?int $value): array
    {
        return [
            'name' => 'project_id',
            'label' => 'Project (kosongkan = semua)',
            'type' => 'select',
            'value' => $value,
            'options' => Project::query()->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function employeeFilter(?int $value): array
    {
        return [
            'name' => 'employee_id',
            'label' => 'Karyawan (kosongkan = semua)',
            'type' => 'select',
            'value' => $value,
            'options' => User::query()->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function jobOpeningFilter(?int $value): array
    {
        return [
            'name' => 'job_opening_id',
            'label' => 'Lowongan (kosongkan = semua)',
            'type' => 'select',
            'value' => $value,
            'options' => JobOpening::query()->orderBy('title')->pluck('title', 'id')->all(),
        ];
    }
}