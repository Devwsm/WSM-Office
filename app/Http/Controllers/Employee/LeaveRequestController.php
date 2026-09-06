<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Employee\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;

/**
 * LeaveRequestController (Employee)
 * ---------------------------------------------------------------------
 * Fase 5 — ajukan izin/cuti, lihat riwayat pengajuan sendiri, dan
 * batalkan (pending ATAU udah disetujui, sesuai kesepakatan Fase 5 —
 * dua-duanya boleh dibatalkan karyawan sendiri, wajib alasan).
 * ---------------------------------------------------------------------
 */
class LeaveRequestController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        $rows = LeaveRequest::query()
            ->where('user_id', $me->id)
            ->orderByDesc('start_date')
            ->get();

        return view('employee.leave.index', [
            'rows' => $rows,
            'remainingLeave' => $me->remainingAnnualLeaveDays(),
            'entitlement' => $me->annual_leave_entitlement,
        ]);
    }

    public function store(StoreLeaveRequestRequest $request)
    {
        $data = $request->validated();
        $workDays = LeaveRequest::countWorkDays($data['start_date'], $data['end_date']);

        LeaveRequest::query()->create([
            'user_id' => Auth::id(),
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'work_days' => $workDays,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Pengajuan berhasil dikirim, tunggu persetujuan atasan.');
    }

    public function cancel(CancelLeaveRequestRequest $request, LeaveRequest $leave)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        abort_unless($leave->user_id === $me->id, 403, 'Ini bukan pengajuan kamu.');

        if (! $leave->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $leave->cancelBy($me, $request->validated('cancellation_reason'));

        return back()->with('status', 'Pengajuan berhasil dibatalkan.');
    }
}