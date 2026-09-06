<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Employee\StoreOvertimeRequestRequest;
use App\Models\OvertimeRequest;
use Illuminate\Support\Facades\Auth;

/**
 * OvertimeRequestController (Employee)
 * ---------------------------------------------------------------------
 * Fase 7 — ajukan Lembur & lihat riwayat pengajuan sendiri. Alur PERSIS
 * sama `Employee\LeaveRequestController` (Fase 5): submit -> pending ->
 * atasan putusin, bisa dibatalkan sendiri kalau masih pending/disetujui
 * & tanggalnya belum lewat.
 *
 * `CancelLeaveRequestRequest` dipakai ulang (BUKAN bikin
 * `CancelOvertimeRequestRequest` baru) — aturannya sama persis
 * (cancellation_reason wajib), gak perlu duplikat class cuma beda nama
 * model. Sama pola kayak yang udah dipakai bareng Approval\LeaveRequestController.
 * ---------------------------------------------------------------------
 */
class OvertimeRequestController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        $rows = OvertimeRequest::query()
            ->where('user_id', $me->id)
            ->orderByDesc('date')
            ->get();

        return view('employee.overtime.index', [
            'rows' => $rows,
        ]);
    }

    public function store(StoreOvertimeRequestRequest $request)
    {
        OvertimeRequest::query()->create([
            'user_id' => Auth::id(),
            'date' => $request->validated('date'),
            'reason' => $request->validated('reason'),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Pengajuan lembur berhasil dikirim, tunggu persetujuan atasan.');
    }

    public function cancel(CancelLeaveRequestRequest $request, OvertimeRequest $overtime)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        abort_unless($overtime->user_id === $me->id, 403, 'Ini bukan pengajuan kamu.');

        if (! $overtime->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $overtime->cancelBy($me, $request->validated('cancellation_reason'));

        return back()->with('status', 'Pengajuan lembur berhasil dibatalkan.');
    }
}