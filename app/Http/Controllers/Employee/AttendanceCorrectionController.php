<?php

namespace App\Http\Controllers\Employee;

use App\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelLeaveRequestRequest;
use App\Http\Requests\Employee\StoreAttendanceCorrectionRequestRequest;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;

/**
 * AttendanceCorrectionController (Employee)
 * ---------------------------------------------------------------------
 * 2026-09-16 — "Koreksi Presensi" self-service (temuan audit ronde 8/9,
 * dieksekusi sekarang). Ajukan koreksi jam masuk/pulang buat hari yang
 * udah lewat/hari ini, lihat riwayat pengajuan sendiri, batalkan selagi
 * masih pending — struktur SENGAJA disamain persis
 * Employee\LeaveRequestController biar konsisten sama Izin/Cuti & Lembur
 * yang udah ada. `CancelLeaveRequestRequest` dipakai ulang (bukan bikin
 * FormRequest baru) — aturannya sama persis (wajib alasan), lihat
 * catatan di class itu & di migration attendance_correction_requests.
 * ---------------------------------------------------------------------
 */
class AttendanceCorrectionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        $rows = AttendanceCorrectionRequest::query()
            ->where('user_id', $me->id)
            ->orderByDesc('date')
            ->get();

        return view('employee.attendance-correction.index', [
            'rows' => $rows,
        ]);
    }

    public function store(StoreAttendanceCorrectionRequestRequest $request)
    {
        $data = $request->validated();

        $created = AttendanceCorrectionRequest::query()->create([
            'user_id' => Auth::id(),
            'date' => $data['date'],
            'requested_clock_in' => $data['requested_clock_in'] ?? null,
            'requested_clock_out' => $data['requested_clock_out'] ?? null,
            'requested_mode' => $data['requested_mode'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        AuditLog::record('Pengajuan koreksi presensi dikirim', "Koreksi tanggal {$created->date->translatedFormat('d M Y')} (mode {$created->requested_mode}). Alasan: {$created->reason}", Auth::user());

        return back()->with('status', 'Pengajuan koreksi presensi berhasil dikirim, tunggu persetujuan atasan.');
    }

    public function cancel(CancelLeaveRequestRequest $request, AttendanceCorrectionRequest $correction)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();

        abort_unless($correction->user_id === $me->id, 403, 'Ini bukan pengajuan kamu.');

        if (! $correction->isCancellable()) {
            return back()->with('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $correction->cancelBy($me, $request->validated('cancellation_reason'));

        AuditLog::record('Pengajuan koreksi presensi dibatalkan pemohon', "Koreksi tanggal {$correction->date->translatedFormat('d M Y')} dibatalkan oleh pemohon. Alasan: " . ($request->validated('cancellation_reason') ?: '—'), $me);

        return back()->with('status', 'Pengajuan berhasil dibatalkan.');
    }
}