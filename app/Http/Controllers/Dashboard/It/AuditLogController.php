<?php

namespace App\Http\Controllers\Dashboard\It;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogController (Dashboard > IT > Audit Log)
 * ---------------------------------------------------------------------
 * Fase 15 — listing AuditLog, padanan panel `auditV18()` di prototype
 * v18. READ-ONLY sepenuhnya (gak ada create/edit/delete dari UI — log
 * cuma dicatat lewat `AuditLog::record()` yang dipanggil dari kode,
 * bukan input manual user), makanya gate-nya cuma `module:it,view` buat
 * SEMUA route di controller ini, gak ada `module:it,manage` sama sekali
 * (beda dari SystemChangelogController).
 *
 * PENTING — SCOPE FASE 15: controller ini cuma bangun UI buat
 * NAMPILIN log yang udah ada di tabel `audit_logs`. `AuditLog::record()`
 * sekarang SUDAH dipanggil dari beberapa controller lain (bukan lagi
 * kosong seperti catatan Fase 15 awal): `Owner\EmployeeController`
 * (tambah/edit/nonaktifkan/aktifkan karyawan), `Owner\DashboardAccessController`
 * (ubah akses modul), `Owner\OfficeSettingController` (ubah pengaturan
 * kantor), `Approval\LeaveRequestController` & `Approval\OvertimeRequestController`
 * (approve/reject/cancel), dan `Dashboard\Payroll\PayrollController`
 * (generate/adjust/finalize/mark-paid/delete). Aksi lain (mis. CRUD
 * Work Tracker/KPI/Budget/Royalty/Legal) BELUM disambungkan ke
 * `record()` — kalau mau audit trail-nya lengkap, itu masih perlu
 * ditambahin satu-satu ke controller-controller tersebut.
 * ---------------------------------------------------------------------
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('q')->toString() ?: null;

        $logs = AuditLog::query()
            ->with('actor')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('detail', 'like', "%{$search}%")
                        ->orWhere('actor_label', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.it.index', [
            'logs' => $logs,
            'search' => $search,
        ]);
    }
}