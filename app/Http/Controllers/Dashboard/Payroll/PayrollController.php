<?php

namespace App\Http\Controllers\Dashboard\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Payroll\GeneratePayrollRequest;
use App\Http\Requests\Dashboard\Payroll\ReopenPayrollRequest;
use App\Http\Requests\Dashboard\Payroll\UpdatePayrollRecordRequest;
use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Support\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * PayrollController (Dashboard > Payroll Overview)
 * ---------------------------------------------------------------------
 * Fase 12 — sisi ADMIN generate & kelola histori payroll bulanan per
 * karyawan. DEVIASI DISENGAJA dari prototype (lihat catatan lengkap di
 * migration `create_payroll_records_table`): prototype cuma hitung
 * "estimate" on-the-fly tiap buka halaman CEO Dashboard, gak pernah
 * disimpan. Di sini payroll digenerate jadi baris `payroll_records`
 * per (`user_id`, `period`) yang PERMANEN — angkanya gak berubah lagi
 * walau `users.salary_base` atau setting lain direvisi belakangan,
 * KECUALI di-generate ulang manual selagi masih berstatus `draft`.
 *
 * Alur status: draft -> finalized -> paid (satu arah, gak bisa mundur
 * dari sini — kalau salah, hapus record draft-nya atau biarkan
 * finalized/paid sebagai histori apa adanya, itu memang tujuannya).
 * Cuma record `draft` yang boleh: digenerate ulang (menimpa
 * base_salary/overtime_amount/shortage_deduction dengan angka
 * terbaru), diedit (other_adjustment/notes), atau dihapus. Sekali
 * `finalized`, angka-angka itu terkunci permanen sebagai histori.
 *
 * Komponen perhitungan — SEMUA aturan ada di `App\Support\PayrollCalculator`
 * (aturan payroll 2026-09-29, mengikuti prototype v32 versi v18):
 * - `base_salary` = salinan `users.salary_base` saat digenerate
 * - `overtime_amount` = jumlah TANGGAL lembur `disetujui` × `flat_overtime_rate`
 * - `shortage_deduction` = blok kurang jam × jam per blok × tarif per jam
 *   (gaji ÷ pembagi hari ÷ jam kerja/hari) — tarif rupiah tidak diinput lagi
 * - `absence_deduction` = hari yang ditandai Absen × tarif harian
 * - `other_adjustment` + `notes` = manual, gak disentuh `generate()`
 *   kalau record-nya udah ada (biar penyesuaian gak ketimpa tiap
 *   regenerate)
 * - `total` dipatok minimal 0 (lihat `PayrollRecord::recalculateTotal()`)
 *
 * Buka kembali payroll Final (`reopen()`): hanya Owner, hanya status
 * Final (bukan Dibayar), wajib alasan, tercatat di Audit Log. Hasilnya
 * balik ke draft supaya bisa digenerate ulang/disesuaikan lalu difinalisasi lagi.
 *
 * Karyawan tanpa `salary_base` terisi SENGAJA dilewati dari daftar
 * generate — bukan dianggap gaji Rp 0. Gate 'view'/'manage' modul
 * 'payroll' — sama pola persis ContractController/KpiController.
 *
 * Fase 15 (instrumentasi, 2026-09-13) — generate/update/finalize/
 * markPaid/destroy dicatat ke AuditLog::record() (payroll = data
 * finansial, aksi paling sensitif buat modul Audit Log).
 *
 * Catatan 2026-09-29: warning "rate potongan masih Rp 0" dihapus karena
 * rate itu tidak ada lagi (potongan diturunkan dari gaji).
 * ---------------------------------------------------------------------
 */
class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->string('period')->toString() ?: now()->format('Y-m');

        $records = PayrollRecord::query()
            ->with('user')
            ->where('period', $period)
            ->get()
            ->sortBy(fn(PayrollRecord $record) => $record->user->name)
            ->values();

        $totalPayroll = $records->sum('total');
        $generatedUserIds = $records->pluck('user_id');

        $eligibleEmployees = User::query()->whereNotNull('salary_base')->orderBy('name')->get(['id', 'name']);
        $notYetGeneratedCount = $eligibleEmployees->pluck('id')->diff($generatedUserIds)->count();
        $missingSalaryCount = User::query()->whereNull('salary_base')->count();

        return view('dashboard.payroll.index', [
            'period' => $period,
            'records' => $records,
            'totalPayroll' => $totalPayroll,
            'notYetGeneratedCount' => $notYetGeneratedCount,
            'missingSalaryCount' => $missingSalaryCount,
            'eligibleEmployees' => $eligibleEmployees,
        ]);
    }

    /**
     * Generate/regenerate draft payroll sebulan buat semua karyawan
     * yang punya `salary_base` (atau subset lewat `employee_ids`).
     * Record yang udah `finalized`/`paid` DILEWATI, bukan ditimpa —
     * itulah yang bikin histori tetap "apa adanya".
     */
    public function generate(GeneratePayrollRequest $request)
    {
        $data = $request->validated();
        $period = $data['period'];

        $employees = User::query()
            ->whereNotNull('salary_base')
            ->when(! empty($data['employee_ids']), fn($q) => $q->whereIn('id', $data['employee_ids']))
            ->get();

        $setting = OfficeSetting::current();

        $generated = 0;
        $skippedLocked = 0;

        foreach ($employees as $employee) {
            /** @var PayrollRecord $record */
            $record = PayrollRecord::query()->firstOrNew([
                'user_id' => $employee->id,
                'period' => $period,
            ]);

            if ($record->exists && $record->status !== 'draft') {
                $skippedLocked++;
                continue;
            }

            $calc = PayrollCalculator::calculate($employee, $period, $setting);

            $record->base_salary = $employee->salary_base;
            $record->overtime_amount = $calc['overtime_amount'];
            $record->shortage_deduction = $calc['shortage_deduction'];
            $record->absent_days = $calc['absent_days'];
            $record->absence_deduction = $calc['absence_deduction'];
            $record->work_days_divisor = $calc['divisor'];
            $record->status = 'draft';
            $record->generated_by = Auth::id();
            $record->recalculateTotal();
            $record->save();

            $generated++;
        }

        $message = "{$generated} payroll berhasil digenerate untuk periode " . Carbon::createFromFormat('!Y-m', $period)->translatedFormat('F Y') . '.';
        if ($skippedLocked > 0) {
            $message .= " {$skippedLocked} dilewati karena sudah difinalisasi/dibayar (regenerate gak nimpa histori final).";
        }

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Payroll digenerate', "{$generated} payroll periode {$period} digenerate oleh {$actor->name}" . ($skippedLocked > 0 ? " ({$skippedLocked} dilewati, sudah terkunci)." : '.'), $actor);

        return redirect()->route('dashboard.payroll.index', ['period' => $period])->with('status', $message);
    }

    public function show(PayrollRecord $payroll)
    {
        $payroll->load(['user', 'generator']);

        return view('dashboard.payroll.show', $this->breakdown($payroll));
    }

    /**
     * Data rincian buat halaman detail & slip PDF. Angka rupiah SELALU dari record
     * (yang terkunci saat final); yang dihitung ulang cuma penjelasnya (jumlah blok,
     * daftar tanggal absen, jumlah lembur).
     *
     * @return array<string,mixed>
     */
    public static function breakdownFor(PayrollRecord $payroll): array
    {
        $calc = PayrollCalculator::calculate($payroll->user, $payroll->period);
        $blocks = $calc['shortage']['blocks'];

        return [
            'payroll' => $payroll,
            'shortage' => $calc['shortage'],
            'overtimeCount' => $calc['overtime_count'],
            'absentDates' => $calc['absent_dates'],
            'divisor' => $payroll->work_days_divisor ?? $calc['divisor'],
            // Tarif per blok diturunkan dari angka yang TERSIMPAN, jadi selalu konsisten dengan total.
            'ratePerBlock' => $blocks > 0 ? round((float) $payroll->shortage_deduction / $blocks, 2) : 0.0,
        ];
    }

    private function breakdown(PayrollRecord $payroll): array
    {
        return self::breakdownFor($payroll);
    }

    /** Sesuaikan other_adjustment/notes manual — cuma boleh selagi masih draft. */
    public function update(UpdatePayrollRecordRequest $request, PayrollRecord $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Payroll yang sudah difinalisasi/dibayar tidak bisa diubah lagi.');
        }

        $payroll->fill($request->validated());
        $payroll->recalculateTotal();
        $payroll->save();

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Payroll disesuaikan', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) disesuaikan oleh {$actor->name}.", $actor);

        return redirect()->route('dashboard.payroll.show', $payroll)->with('status', 'Penyesuaian payroll disimpan.');
    }

    public function finalize(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Cuma payroll berstatus draft yang bisa difinalisasi.');
        }

        $payroll->update(['status' => 'finalized']);

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Payroll difinalisasi', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) difinalisasi oleh {$actor->name}.", $actor);

        return back()->with('status', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) difinalisasi. Angka terkunci, gak bisa digenerate ulang/diedit lagi.");
    }

    public function markPaid(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'finalized') {
            return back()->with('error', 'Cuma payroll berstatus final yang bisa ditandai dibayar.');
        }

        $payroll->update(['status' => 'paid']);

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Payroll ditandai dibayar', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) ditandai dibayar oleh {$actor->name}.", $actor);

        return back()->with('status', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) ditandai sudah dibayar.");
    }

    /**
     * Buka kembali payroll Final jadi draft. Hanya Owner (dijaga ReopenPayrollRequest),
     * hanya status Final — yang sudah Dibayar tidak dibuka (koreksinya lewat
     * penyesuaian di bulan berikutnya). Wajib alasan; tercatat di Audit Log.
     */
    public function reopen(ReopenPayrollRequest $request, PayrollRecord $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Payroll yang sudah dibayar tidak bisa dibuka kembali. Koreksi lewat penyesuaian di bulan berikutnya.');
        }

        if ($payroll->status !== 'finalized') {
            return back()->with('error', 'Cuma payroll berstatus final yang bisa dibuka kembali.');
        }

        /** @var User $actor */
        $actor = Auth::user();
        $reason = $request->validated()['reopen_reason'];

        $payroll->update([
            'status' => 'draft',
            'reopened_by' => $actor->id,
            'reopened_at' => now(),
            'reopen_reason' => $reason,
        ]);

        AuditLog::record('Payroll final dibuka kembali', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) dibuka kembali jadi draft oleh {$actor->name}. Alasan: {$reason}", $actor);

        return redirect()->route('dashboard.payroll.show', $payroll)->with('status', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) dibuka kembali jadi draft. Generate ulang atau sesuaikan, lalu finalisasi lagi.");
    }

    /** Cuma record draft yang boleh dihapus — final/paid adalah histori permanen. */
    public function destroy(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Cuma payroll draft yang bisa dihapus. Payroll final/dibayar adalah histori permanen.');
        }

        $period = $payroll->period;
        $employeeName = $payroll->user->name;
        $payroll->delete();

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Payroll draft dihapus', "Payroll draft {$employeeName} (periode {$period}) dihapus oleh {$actor->name}.", $actor);

        return redirect()->route('dashboard.payroll.index', ['period' => $period])->with('status', 'Payroll draft dihapus.');
    }
}