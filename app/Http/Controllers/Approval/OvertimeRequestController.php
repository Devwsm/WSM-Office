<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Approval\RejectLeaveRequestRequest;
use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * OvertimeRequestController (Approval)
 * ---------------------------------------------------------------------
 * Fase 7 — Manajer & Owner approve/reject/cancel Lembur. Scope siapa
 * yang bisa diputuskan SAMA PERSIS `Approval\LeaveRequestController`
 * (Fase 5): Manajer cuma bawahan LANGSUNG, Owner semua orang kapan aja.
 * HRD SENGAJA gak ikut, sama kesepakatan Fase 5.
 *
 * `RejectLeaveRequestRequest` & `CancelLeaveRequestRequest` dipakai
 * ulang (BUKAN bikin versi Overtime) — aturan validasinya identik.
 *
 * Fase 15 (instrumentasi, 2026-09-13) — approve/reject/cancel dicatat
 * ke AuditLog::record(), sama pola persis LeaveRequestController.
 * ---------------------------------------------------------------------
 */
class OvertimeRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'disetujui', 'ditolak', 'dibatalkan', 'semua'], true) ? $status : 'pending';

        /** @var User $me */
        $me = Auth::user();

        $query = OvertimeRequest::query()->with('user')->orderByDesc('date');

        if (! $me->isOwner()) {
            $query->whereHas('user', fn($q) => $q->where('manager_id', $me->id));
        }

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        return view('approval.overtime.index', [
            'rows' => $query->get(),
            'status' => $status,
        ]);
    }

    public function approve(OvertimeRequest $overtime)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($overtime, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $overtime->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $overtime->approveBy($me);

        AuditLog::record('Lembur disetujui', "Pengajuan lembur {$overtime->user->name} disetujui oleh {$me->name}.", $me);

        return back()->with('status', "Pengajuan lembur {$overtime->user->name} disetujui.");
    }

    public function reject(RejectLeaveRequestRequest $request, OvertimeRequest $overtime)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($overtime, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $overtime->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $overtime->rejectBy($me, $request->validated('decision_note'));

        AuditLog::record('Lembur ditolak', "Pengajuan lembur {$overtime->user->name} ditolak oleh {$me->name}.", $me);

        return back()->with('status', "Pengajuan lembur {$overtime->user->name} ditolak.");
    }

    public function cancel(CancelLeaveRequestRequest $request, OvertimeRequest $overtime)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($overtime, $me), 403, 'Kamu tidak berwenang membatalkan pengajuan ini.');

        if (! $overtime->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $overtime->cancelBy($me, $request->validated('cancellation_reason'));

        AuditLog::record('Lembur dibatalkan', "Lembur {$overtime->user->name} dibatalkan oleh {$me->name}.", $me);

        return back()->with('status', "Lembur {$overtime->user->name} dibatalkan.");
    }

    private function canDecide(OvertimeRequest $overtime, User $me): bool
    {
        return $me->isOwner() || $overtime->user->manager_id === $me->id;
    }
}