<?php

namespace App\Http\Controllers\Dashboard\It;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * AuditLogController (Dashboard > IT > Audit Log)
 * ---------------------------------------------------------------------
 * Listing AuditLog, READ-ONLY (gate `module:it,view`). Log hanya dicatat
 * dari kode lewat `AuditLog::record()`, tidak ada input manual.
 *
 * Cakupan pencatatan (2026-10-09): semua aksi yang mengubah data dicatat,
 * dengan nilai lama → baru bila memungkinkan. Yang tidak dicatat sengaja
 * didaftarkan beserta alasannya di `config/audit.php`; aksi baru yang
 * belum punya catatan khusus tetap masuk lewat middleware
 * `AuditUncoveredChanges` ("Perubahan data (tidak terinci)"), dan
 * `AuditCoverageTest` mengingatkan kalau ada route baru yang terlewat.
 *
 * Filter: kata kunci, pelaku, area (nama halaman), dan rentang tanggal.
 * ---------------------------------------------------------------------
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('q')->toString() ?: null;
        $actor = $request->string('actor')->toString() ?: null;
        $area = $request->string('area')->toString() ?: null;
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $logs = AuditLog::query()
            ->with('actor')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('detail', 'like', "%{$search}%")
                        ->orWhere('actor_label', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($actor === 'system', fn($q) => $q->whereNull('actor_id'))
            ->when($actor && $actor !== 'system', fn($q) => $q->where('actor_id', (int) $actor))
            ->when($area, fn($q) => $q->where('area', $area))
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.it.index', [
            'logs' => $logs,
            'search' => $search,
            'actor' => $actor,
            'area' => $area,
            'from' => $from,
            'to' => $to,
            'actors' => User::query()->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id'))->orderBy('name')->get(['id', 'name']),
            'areas' => AuditLog::query()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'filtered' => $search || $actor || $area || $from || $to,
        ]);
    }

    /** Tanggal valid (Y-m-d) atau null. */
    private function date(mixed $value): ?string
    {
        $value = is_string($value) ? $value : '';

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? $value : null;
    }
}