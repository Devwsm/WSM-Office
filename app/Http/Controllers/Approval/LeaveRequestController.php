<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Approval\RejectLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LeaveRequestController (Approval)
 * ---------------------------------------------------------------------
 * Fase 5 — Manajer & Owner approve/reject/cancel izin-cuti. HRD SENGAJA
 * tidak ikut di sini (kesepakatan Fase 5: cuma Manajer & Owner).
 *
 * Scope siapa yang bisa diputuskan:
 * - Manajer -> cuma bawahan LANGSUNG (`manager_id` persis dia, BUKAN
 *   turunan seperti scope rekap absensi Fase 4 — beda kesepakatan).
 * - Owner -> semua orang, kapan aja, termasuk yang punya Manajer
 *   (tercatat `approver_id` = Owner biar jelas siapa yang beneran
 *   mutusin, bukan manajernya).
 * ---------------------------------------------------------------------
 */
class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'disetujui', 'ditolak', 'dibatalkan', 'semua'], true) ? $status : 'pending';

        /** @var User $me */
        $me = Auth::user();

        $query = LeaveRequest::query()->with('user')->orderByDesc('created_at');

        if (! $me->isOwner()) {
            // Manajer: cuma bawahan langsung.
            $query->whereHas('user', fn($q) => $q->where('manager_id', $me->id));
        }

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        return view('approval.leave.index', [
            'rows' => $query->get(),
            'status' => $status,
        ]);
    }

    public function approve(LeaveRequest $leave)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($leave, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $leave->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $leave->approveBy($me);

        return back()->with('status', "Pengajuan {$leave->user->name} disetujui.");
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leave)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($leave, $me), 403, 'Kamu tidak berwenang memutuskan pengajuan ini.');

        if (! $leave->isPending()) {
            return back()->with('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        $leave->rejectBy($me, $request->validated('decision_note'));

        return back()->with('status', "Pengajuan {$leave->user->name} ditolak.");
    }

    public function cancel(CancelLeaveRequestRequest $request, LeaveRequest $leave)
    {
        /** @var User $me */
        $me = Auth::user();
        abort_unless($this->canDecide($leave, $me), 403, 'Kamu tidak berwenang membatalkan pengajuan ini.');

        if (! $leave->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $leave->cancelBy($me, $request->validated('cancellation_reason'));

        return back()->with('status', "Izin/cuti {$leave->user->name} dibatalkan.");
    }

    /** Owner bisa memutuskan siapa aja. Manajer cuma bawahan langsungnya. */
    private function canDecide(LeaveRequest $leave, User $me): bool
    {
        return $me->isOwner() || $leave->user->manager_id === $me->id;
    }
}