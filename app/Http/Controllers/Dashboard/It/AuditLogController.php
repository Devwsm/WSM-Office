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
 * (helper yang udah ada dari migration awal) BELUM dipanggil dari
 * controller manapun di sistem ini (Karyawan, Owner, Dashboard, dst) —
 * jadi listing ini bakal KOSONG sampai instrumentasi itu ditambahin.
 * Nyambungin `record()` ke tiap aksi penting (CRUD karyawan, approve
 * izin/cuti, ubah dashboard access, dll) SENGAJA di luar scope fase
 * ini — itu perubahan lintas-controller yang besar, beda kerjaan dari
 * "bangun UI di atas tabel yang udah siap" (pola Fase 14/15 selama
 * ini). Lihat README buat catatan rekomendasi urutan kerja berikutnya.
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