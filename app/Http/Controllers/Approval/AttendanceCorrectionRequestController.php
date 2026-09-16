<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\RejectLeaveRequestRequest;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AttendanceCorrectionRequestController (Approval)
 * ---------------------------------------------------------------------
 * 2026-09-16 — Manajer & Owner approve/reject Koreksi Presensi. Scope
 * siapa yang bisa diputuskan SAMA PERSIS LeaveRequestController
 * (canDecide()): Manajer cuma bawahan LANGSUNG, Owner semua orang.
 * `RejectLeaveRequestRequest`/`CancelLeaveRequestRequest` dipakai ulang
 * (bukan bikin FormRequest baru) — aturannya sama persis (wajib alasan).
 *
 * `approve()` MEMICU AttendanceCorrectionRequest::approveBy(), yang
 * nerapin jam yang diminta ke baris `attendances` asli (bikin baru kalau
 * belum ada) — beda dari LeaveRequest/OvertimeRequest yang approve-nya
 * cuma ubah status, di sini approve BENERAN ngubah data presensi.
 * ---------------------------------------------------------------------
 */
class AttendanceCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'disetujui', 'ditolak', 'dibatalkan', 'semua'], true) ? $status : 'pending';

        /** @var User $me */
        $me = Auth::user();

        $query = AttendanceCorrectionRequest::query()->with('user')->orderByDesc('created_at');

        if (! $me->isOwner()) {
            $query->whereHas('user', fn($q) => $q->where('manager_id', $me->id));
        }

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        return view('approval.attendance-correction.index', [
            'rows' => $query->get(),
            'status' => $status,
        ]);
    }

    public function approve(AttendanceCorrectionRequest $correction)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($correction, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $correction->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $correction->approveBy($me);

        AuditLog::record('Koreksi presensi disetujui', "Pengajuan koreksi presensi {$correction->user->name} ({$correction->date->translatedFormat('d M Y')}) disetujui oleh {$me->name}.", $me);

        return back()->with('status', "Koreksi presensi {$correction->user->name} disetujui & sudah diterapkan.");
    }

    public function reject(RejectLeaveRequestRequest $request, AttendanceCorrectionRequest $correction)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($correction, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $correction->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $correction->rejectBy($me, $request->validated('decision_note'));

        AuditLog::record('Koreksi presensi ditolak', "Pengajuan koreksi presensi {$correction->user->name} ({$correction->date->translatedFormat('d M Y')}) ditolak oleh {$me->name}.", $me);

        return back()->with('status', "Koreksi presensi {$correction->user->name} ditolak.");
    }

    public function cancel(CancelLeaveRequestRequest $request, AttendanceCorrectionRequest $correction)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($correction, $me), 403, 'Kamu tidak berwenang membatalkan pengajuan ini.');

        if (! $correction->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $correction->cancelBy($me, $request->validated('cancellation_reason'));

        AuditLog::record('Koreksi presensi dibatalkan', "Pengajuan koreksi presensi {$correction->user->name} dibatalkan oleh {$me->name}.", $me);

        return back()->with('status', "Pengajuan koreksi presensi {$correction->user->name} dibatalkan.");
    }

    /** Owner bisa memutuskan siapa aja. Manajer cuma bawahan langsungnya — sama pola LeaveRequestController::canDecide(). */
    private function canDecide(AttendanceCorrectionRequest $correction, User $me): bool
    {
        return $me->isOwner() || $correction->user->manager_id === $me->id;
    }
}