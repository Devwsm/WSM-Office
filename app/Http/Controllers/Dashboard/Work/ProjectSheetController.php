<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Exports\ProjectWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Support\ExportImport\ImportPreviewService;
use App\Support\ProjectSheet\SheetReader;
use App\Support\ProjectSheet\SheetSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * ProjectSheetController — item 6 daftar selisih prototype v32:
 * "Download project ke Excel & sinkron Google Sheet".
 *
 *   download() — Excel per project (sheet Project + sheet Tracker). Padanan
 *                tombol "XLSX" di header project prototype. Gate: view modul work.
 *   show()/preview()/commit() — sinkron dari file tracker (Excel/CSV yang
 *                diunduh dari Google Sheet, atau hasil download() yang sudah
 *                diedit). Gate: manage modul work, sama seperti edit task manual.
 *
 * Yang berbeda dari prototype: prototype "sinkron" lewat CSV + Apps Script dan
 * hanya untuk project Map of Feelings dengan sumber yang ditanam di kode. Di
 * sini berlaku untuk SEMUA project, dan tidak menyambung langsung ke Google
 * (tidak ada kredensial/akun layanan Google di server) — arah Sheet -> WSM lewat
 * upload di halaman ini, arah WSM -> Sheet lewat download() lalu tempel.
 */
class ProjectSheetController extends Controller
{
    public function __construct(private readonly ImportPreviewService $previewService) {}

    public function download(Request $request, Project $project): Response
    {
        $filename = 'WSM-' . Str::slug($project->name ?: 'project') . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProjectWorkbookExport($project, $request->user()->name), $filename);
    }

    public function show(Project $project): View
    {
        return view('dashboard.work.projects.sync', [
            'project' => $project,
            'itemCount' => $project->workItems()->count(),
        ]);
    }

    public function preview(Request $request, Project $project): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Pilih file tracker (.xlsx atau .csv) dulu.',
            'file.mimes' => 'File harus format .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $upload = $request->file('file');

        try {
            $parsed = (new SheetReader)->parse($upload->getRealPath(), $upload->getClientOriginalExtension());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $plan = (new SheetSync($project))->plan($parsed['rows']);

        $token = $this->previewService->stage($request->user(), $this->cacheKey($project), [
            'plan' => $plan,
            'file' => $upload->getClientOriginalName(),
            'sheet' => $parsed['sheet'],
        ]);

        return view('dashboard.work.projects.sync-preview', [
            'project' => $project,
            'plan' => $plan,
            'token' => $token,
            'fileName' => $upload->getClientOriginalName(),
            'sheetName' => $parsed['sheet'],
        ]);
    }

    public function commit(Request $request, Project $project): RedirectResponse
    {
        $token = (string) $request->input('token');
        abort_if($token === '', 422, 'Token sinkron tidak valid.');

        $payload = $this->previewService->retrieve($request->user(), $this->cacheKey($project), $token);
        abort_if($payload === null, 410, 'Sesi preview sudah kadaluarsa (30 menit) — silakan upload ulang filenya.');

        $result = (new SheetSync($project))->apply($payload['plan'], $request->user());

        $this->previewService->forget($request->user(), $this->cacheKey($project), $token);

        AuditLog::record(
            'Sinkron Work Tracker dari file',
            "{$request->user()->name} menyinkronkan project \"{$project->name}\" dari file {$payload['file']}: "
                . "{$result['created']} task baru, {$result['updated']} diperbarui.",
            $request->user(),
        );

        $status = "Sinkron selesai: {$result['created']} task baru, {$result['updated']} task diperbarui.";
        if ($result['skipped'] > 0) {
            $status .= " {$result['skipped']} task dilewati karena sudah terhapus sejak preview.";
        }

        return redirect()
            ->route('dashboard.work.tracker.index', ['project_id' => $project->id])
            ->with('status', $status);
    }

    /** Staging preview dipisah per project supaya token project A tidak bisa dipakai commit di project B. */
    private function cacheKey(Project $project): string
    {
        return "work-sync-{$project->id}";
    }
}