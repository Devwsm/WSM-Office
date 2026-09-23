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
use App\Exports\PayrollExport;
use App\Exports\ProjectBudgetExport;
use App\Exports\RoyaltyEntryExport;
use App\Exports\WorkItemExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\JobOpening;
use App\Models\Meeting;
use App\Models\OfficeSetting;
use App\Models\OvertimeRequest;
use App\Models\PayrollRecord;
use App\Models\Project;
use App\Models\User;
use App\Support\ExportImport\ExportCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * ExportController (Dashboard > Export & Import)
 * ---------------------------------------------------------------------
 * Batch 1 — SATU controller buat semua export Excel (bukan 1 controller
 * per modul), route-nya generik: `/{key}/{format}/preview` &
 * `/{key}/{format}/download`, `$key` cocok ke `ExportCatalog::CATALOG`.
 * Batch 2 (export PDF) numpang di controller yang SAMA, persis rencana
 * di komentar lama — TIDAK bikin controller baru.
 *
 * Batch 2 — dua BENTUK PDF yang beda, ditangani beda:
 *   1. 'attendance-recap' (Rekap Absensi per-karyawan) — tetap TABEL
 *      (banyak baris), cuma WAJIB `employee_id` (beda dari versi Excel
 *      yang boleh semua karyawan). Numpang penuh di resolve() yang
 *      sudah ada (AttendanceRecapExport dipakai bareng buat Excel &
 *      PDF) dan view preview.blade.php yang sama — cuma tombol
 *      downloadnya manggil downloadAttendanceRecapPdf() (dompdf),
 *      bukan Excel::download().
 *   2. 'payroll' (slip gaji) & 'meetings' (notulen) — 1 DOKUMEN PER
 *      RECORD, bukan tabel. Gak masuk akal dipaksa ke bentuk
 *      resolve()+preview.blade.php yang "1 filter -> 1 file". Jalur
 *      terpisah: pickerPreview() nampilin daftar record yang bisa
 *      dipilih (dashboard/export-import/picker.blade.php), tiap baris
 *      link LANGSUNG ke download-nya sendiri (gak ada preview
 *      perantara lagi — dokumennya sendiri sudah "preview-able").
 *      Kedua jalur ini DICEK PALING AWAL di preview()/download()
 *      sebelum masuk ke resolve(), lihat konstanta SINGLE_DOCUMENT_PDF.
 *
 * validated diambil dari accessLevelFor(), BUKAN dari middleware
 * `module:` di route — soalnya `$key` datang dari URL (dinamis),
 * sedangkan middleware `module:xxx` di route butuh nama modul yang
 * FIXED di definisi route. Levelnya sama persis (canView() ==
 * accessLevel() !== 'none'), cuma titik pengecekannya di controller.
 * Jalur picker (Batch 2) tetap pakai ExportCatalog::canView() yang
 * sama, dicek ulang di tiap method (preview & download beda request).
 * ---------------------------------------------------------------------
 */
class ExportController extends Controller
{
    /** Catalog key yang PDF-nya 1 dokumen per record (bukan tabel) — lihat docblock class. */
    private const SINGLE_DOCUMENT_PDF = ['payroll', 'meetings'];

    public function preview(Request $request, string $key, string $format): View
    {
        if ($format === 'pdf' && in_array($key, self::SINGLE_DOCUMENT_PDF, true)) {
            return $this->pickerPreview($request, $key);
        }

        ['export' => $export, 'title' => $title, 'filters' => $filters, 'requiresSelection' => $requiresSelection] =
            $this->resolve($request, $key, $format);

        return view('dashboard.export-import.preview', [
            'title' => $title,
            'headings' => $export->headings(),
            'rows' => $requiresSelection ? [] : $export->previewRows(),
            'filters' => $filters,
            'requiresSelection' => $requiresSelection,
            'downloadLabel' => $format === 'pdf' ? 'Download PDF' : 'Download Excel',
            'downloadUrl' => route('dashboard.export-import.download', array_merge(
                ['key' => $key, 'format' => $format],
                $request->query(),
            )),
            'backUrl' => route('dashboard.export-import.index'),
        ]);
    }

    public function download(Request $request, string $key, string $format): Response
    {
        if ($format === 'pdf' && $key === 'payroll') {
            return $this->downloadPayrollSlip($request);
        }

        if ($format === 'pdf' && $key === 'meetings') {
            return $this->downloadMeetingMinutes($request);
        }

        ['export' => $export, 'title' => $title] = $this->resolve($request, $key, $format);

        if ($format === 'pdf') {
            // Satu-satunya yang sampai sini: 'attendance-recap' (lihat
            // docblock class). abort_unless jadi jaring pengaman kalau
            // orang akses URL download langsung tanpa lewat preview
            // (yang nyembunyiin tombolnya selagi belum pilih karyawan).
            abort_unless($request->integer('employee_id'), 422, 'Pilih karyawan dulu untuk export PDF ini.');

            return $this->downloadAttendanceRecapPdf($request, $export);
        }

        $filename = Str::slug($title) . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Cari entri catalog + pastikan user boleh, format-nya valid &
     * SUDAH diimplementasikan, lalu bangun instance Export-nya sesuai
     * $key (query & filter tiap modul beda, lihat App\Exports\*).
     *
     * $requiresSelection: cuma dipakai 'attendance-recap' format
     * 'pdf' — true kalau employee_id belum dipilih, ngasih tau
     * preview() buat kosongin $rows & sembunyiin tombol download
     * (lihat preview.blade.php).
     *
     * @return array{export: BaseExport, title: string, filters: array<int, array<string, mixed>>, requiresSelection: bool}
     */
    private function resolve(Request $request, string $key, string $format): array
    {
        /** @var User $user */
        $user = $request->user();

        $entry = ExportCatalog::CATALOG[$key] ?? null;
        abort_if($entry === null, 404, 'Modul export tidak ditemukan.');
        abort_unless(ExportCatalog::canView($user, $key), 403);
        abort_unless(in_array($format, $entry['implemented_exports'], true), 404, 'Format export ini belum tersedia.');
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        $period = $request->query('period'); // 'Y-m', dipakai beberapa modul
        $projectId = $request->integer('project_id') ?: null;
        $employeeId = $request->integer('employee_id') ?: null;
        $requiresSelection = $key === 'attendance-recap' && $format === 'pdf' && ! $employeeId;

        // Fix 2026-09-23: scope Export Rekap Absensi ke tim requester,
        // sama seperti halaman Rekap Absensi (User::visibleAttendanceUserIds()).
        // Sebelumnya export ini bisa baca absensi SEMUA karyawan walau
        // requester cuma manajer tim tertentu.
        $allowedUserIds = null;
        if ($key === 'attendance-recap') {
            $allowedUserIds = $user->visibleAttendanceUserIds();

            abort_if(
                $employeeId && ! $allowedUserIds->contains($employeeId),
                403,
                'Kamu tidak punya akses ke absensi karyawan ini.',
            );
        }

        [$export, $filters] = match ($key) {
            'attendance-recap' => [
                new AttendanceRecapExport($period ?: now()->format('Y-m'), $employeeId, $allowedUserIds),
                [
                    $this->monthFilter($period),
                    $this->employeeFilter($employeeId, required: $format === 'pdf', allowedUserIds: $allowedUserIds),
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
            // 'payroll' cuma sampai sini buat format 'excel' — format
            // 'pdf'-nya dicegat lebih awal di preview()/download()
            // (lihat SINGLE_DOCUMENT_PDF).
            'payroll' => [
                new PayrollExport($period ?: now()->format('Y-m')),
                [$this->monthFilter($period)],
            ],
            default => abort(404, 'Export untuk modul ini belum tersedia.'),
        };

        return ['export' => $export, 'title' => $entry['label'], 'filters' => $filters, 'requiresSelection' => $requiresSelection];
    }

    /**
     * Batch 2 — PDF Rekap Absensi 1 karyawan. Headings/rows dipakai
     * BARENG dari AttendanceRecapExport yang sama dengan versi Excel
     * (lihat resolve()) — cuma dibungkus letterhead pdf.layout lewat
     * dompdf, bukan Maatwebsite Excel.
     */
    private function downloadAttendanceRecapPdf(Request $request, AttendanceRecapExport $export): Response
    {
        $employee = User::findOrFail($request->integer('employee_id'));
        $period = $request->query('period') ?: now()->format('Y-m');
        $periodLabel = Carbon::createFromFormat('!Y-m', $period)->translatedFormat('F Y');

        $filename = Str::slug("rekap-absensi-{$employee->name}-{$period}") . '.pdf';

        return Pdf::loadView('pdf.attendance-recap', [
            'employee' => $employee,
            'periodLabel' => $periodLabel,
            'headings' => $export->headings(),
            'rows' => $export->previewRows(),
        ])->download($filename);
    }

    /**
     * Batch 2 — halaman "pilih record" buat 2 catalog key yang PDF-nya
     * 1 dokumen per record (lihat docblock class & SINGLE_DOCUMENT_PDF).
     */
    private function pickerPreview(Request $request, string $key): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(ExportCatalog::canView($user, $key), 403);

        return match ($key) {
            'payroll' => $this->payrollPicker($request),
            'meetings' => $this->meetingsPicker($request),
            default => abort(404),
        };
    }

    private function payrollPicker(Request $request): View
    {
        $period = $request->query('period') ?: now()->format('Y-m');

        $records = PayrollRecord::query()
            ->with('user')
            ->where('period', $period)
            ->get()
            ->sortBy(fn(PayrollRecord $record) => $record->user->name)
            ->values();

        return view('dashboard.export-import.picker', [
            'title' => 'Payroll — Slip Gaji',
            'backUrl' => route('dashboard.export-import.index'),
            'filters' => [$this->monthFilter($period)],
            'columns' => ['Karyawan', 'Total', 'Status'],
            'rows' => $records->map(fn(PayrollRecord $record) => [
                'cells' => [
                    $record->user->name,
                    PayrollRecord::formatRupiah($record->total),
                    $record->statusLabel(),
                ],
                'downloadUrl' => route('dashboard.export-import.download', [
                    'key' => 'payroll',
                    'format' => 'pdf',
                    'payroll_id' => $record->id,
                ]),
                'downloadLabel' => 'Unduh Slip PDF',
            ])->all(),
            'emptyMessage' => 'Belum ada payroll digenerate untuk periode ini — generate dulu lewat halaman Payroll Overview.',
        ]);
    }

    private function downloadPayrollSlip(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(ExportCatalog::canView($user, 'payroll'), 403);

        $payroll = PayrollRecord::with(['user', 'generator'])->findOrFail($request->integer('payroll_id'));

        $setting = OfficeSetting::current();
        $start = Carbon::createFromFormat('!Y-m', $payroll->period)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('!Y-m', $payroll->period)->endOfMonth()->toDateString();

        $shortage = Attendance::monthlyShortageBlocks($payroll->user_id, $payroll->period, $setting);
        $overtimeCount = OvertimeRequest::query()
            ->where('user_id', $payroll->user_id)
            ->where('status', 'disetujui')
            ->whereBetween('date', [$start, $end])
            ->count();

        $filename = Str::slug("slip-gaji-{$payroll->user->name}-{$payroll->period}") . '.pdf';

        return Pdf::loadView('pdf.payroll-slip', [
            'payroll' => $payroll,
            'shortage' => $shortage,
            'overtimeCount' => $overtimeCount,
            'shortageRate' => (float) $setting->shortage_deduction_rate,
        ])->download($filename);
    }

    private function meetingsPicker(Request $request): View
    {
        $period = $request->query('period') ?: null;

        $meetings = Meeting::query()
            ->with('project')
            ->when($period, fn($q) => $q->whereBetween('date', [
                "{$period}-01",
                date('Y-m-t', strtotime("{$period}-01")),
            ]))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(100)
            ->get();

        return view('dashboard.export-import.picker', [
            'title' => 'Meetings / MoM — Notulen',
            'backUrl' => route('dashboard.export-import.index'),
            'filters' => [$this->monthFilter($period, required: false)],
            'columns' => ['Tanggal', 'Agenda', 'Project'],
            'rows' => $meetings->map(fn(Meeting $meeting) => [
                'cells' => [
                    $meeting->date->translatedFormat('d M Y'),
                    $meeting->agenda,
                    $meeting->project->name ?? '-',
                ],
                'downloadUrl' => route('dashboard.export-import.download', [
                    'key' => 'meetings',
                    'format' => 'pdf',
                    'meeting_id' => $meeting->id,
                ]),
                'downloadLabel' => 'Unduh Notulen PDF',
            ])->all(),
            'emptyMessage' => 'Belum ada rapat/MoM tercatat.',
        ]);
    }

    private function downloadMeetingMinutes(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(ExportCatalog::canView($user, 'meetings'), 403);

        $meeting = Meeting::with(['project', 'creator', 'attendees', 'actionItems.pic'])
            ->findOrFail($request->integer('meeting_id'));

        $filename = Str::slug('notulen-' . $meeting->agenda . '-' . $meeting->date->format('Y-m-d')) . '.pdf';

        return Pdf::loadView('pdf.meeting-minutes', ['meeting' => $meeting])->download($filename);
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

    /**
     * @param  \Illuminate\Support\Collection<int, int>|null  $allowedUserIds
     *     Kalau diisi, dropdown-nya cuma nampilin karyawan dalam scope
     *     itu (fix 2026-09-23 — dipakai attendance-recap).
     */
    private function employeeFilter(?int $value, bool $required = false, ?\Illuminate\Support\Collection $allowedUserIds = null): array
    {
        return [
            'name' => 'employee_id',
            'label' => 'Karyawan' . ($required ? ' (wajib untuk PDF)' : ' (kosongkan = semua)'),
            'type' => 'select',
            'value' => $value,
            'options' => User::query()
                ->when($allowedUserIds !== null, fn($q) => $q->whereIn('id', $allowedUserIds))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
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