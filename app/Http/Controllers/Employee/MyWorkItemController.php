<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\WorkItem;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * MyWorkItemController (App Mode)
 * ---------------------------------------------------------------------
 * 2026-10-11 — karyawan mengubah STATUS dan CATATAN task miliknya sendiri
 * dari kartu "My Work Tracker" di Home, tanpa perlu akses dashboard Work.
 * Padanan dropdown status + tombol "Update Note" di `taskCardMarkupV9`
 * (employeeModeView) prototype.
 *
 * Aturan:
 * - Hanya PIC task itu (PIC utama atau PIC 2/3) yang boleh. Definisi
 *   "task milik seseorang" tetap satu tempat: `WorkItem::scopeForPic()`.
 * - Hanya dua kolom yang bisa diubah: `progress` dan `notes`. Judul,
 *   tanggal, PIC, project, dan section tetap lewat dashboard Work.
 * - Setiap perubahan masuk Audit Log, sama seperti perubahan dari dashboard.
 * - Setelah menyimpan, halaman kembali ke bagian My Work Tracker.
 *   Task berstatus Done pindah ke daftar "selesai" dan bisa dibuka lagi
 *   dengan mengubah statusnya dari daftar itu.
 * ---------------------------------------------------------------------
 */
class MyWorkItemController extends Controller
{
    public function updateProgress(Request $request, WorkItem $item): RedirectResponse
    {
        $this->authorizePic($item);

        $data = $request->validate([
            'progress' => ['required', Rule::in(WorkItem::PROGRESS_OPTIONS)],
        ]);

        if ($item->progress === $data['progress']) {
            return $this->backToTracker();
        }

        $changes = Audit::changes($item, $data, Audit::labels('work_item'));
        $item->update($data);

        AuditLog::record('Progress task diubah (oleh PIC)', "Task \"{$item->title}\": {$changes}.", $request->user());

        $message = $data['progress'] === 'Done'
            ? 'Task ditandai selesai. Kalau salah, buka daftar "selesai" lalu ubah statusnya.'
            : 'Status task diperbarui.';

        return $this->backToTracker()->with('status', $message);
    }

    public function updateNote(Request $request, WorkItem $item): RedirectResponse
    {
        $this->authorizePic($item);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $notes = trim((string) ($validated['notes'] ?? ''));
        $data = ['notes' => $notes === '' ? null : $notes];

        if ($item->notes === $data['notes']) {
            return $this->backToTracker();
        }

        $changes = Audit::changes($item, $data, Audit::labels('work_item'));
        $item->update($data);

        AuditLog::record('Catatan task diubah (oleh PIC)', "Task \"{$item->title}\": {$changes}.", $request->user());

        return $this->backToTracker()->with('status', 'Catatan task disimpan.');
    }

    /** Hanya PIC task itu (utama atau tambahan) yang boleh mengubahnya. */
    private function authorizePic(WorkItem $item): void
    {
        $isPic = WorkItem::query()
            ->forPic((int) Auth::id())
            ->whereKey($item->getKey())
            ->exists();

        abort_unless($isPic, 403, 'Task ini bukan tugas kamu.');
    }

    private function backToTracker(): RedirectResponse
    {
        return redirect()->to(route('employee.home') . '#my-work-tracker');
    }
}