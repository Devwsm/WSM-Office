<?php

namespace App\Http\Controllers\Dashboard\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Payroll\GeneratePayrollRequest;
use App\Http\Requests\Dashboard\Payroll\UpdatePayrollRecordRequest;
use App\Models\Attendance;
use App\Models\OfficeSetting;
use App\Models\OvertimeRequest;
use App\Models\PayrollRecord;
use App\Models\User;
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
 * Komponen perhitungan (semua di `generate()`):
 * - `base_salary` = salinan `users.salary_base` saat digenerate
 * - `overtime_amount` = jumlah `OvertimeRequest` status `disetujui`
 *   di periode itu × `users.flat_overtime_rate` (bukan per jam durasi,
 *   kebijakan v18 — lihat catatan `OvertimeRequest`)
 * - `shortage_deduction` = `Attendance::monthlyShortageBlocks()`
 *   (Fase 7) blocks × `OfficeSetting::shortage_deduction_rate` (field
 *   baru Fase 12, lihat migration `add_shortage_deduction_rate_to_office_settings`
 *   — rate ini gak ada acuan dari prototype, jadi Owner yang tentukan
 *   sendiri lewat Pengaturan Kantor)
 * - `other_adjustment` + `notes` = manual, gak disentuh `generate()`
 *   kalau record-nya udah ada (biar penyesuaian gak ketimpa tiap
 *   regenerate)
 *
 * Karyawan tanpa `salary_base` terisi SENGAJA dilewati dari daftar
 * generate — bukan dianggap gaji Rp 0. Gate 'view'/'manage' modul
 * 'payroll' — sama pola persis ContractController/KpiController.
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
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();

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

            $overtimeCount = OvertimeRequest::query()
                ->where('user_id', $employee->id)
                ->where('status', 'disetujui')
                ->whereBetween('date', [$start, $end])
                ->count();

            $shortage = Attendance::monthlyShortageBlocks($employee->id, $period, $setting);

            $record->base_salary = $employee->salary_base;
            $record->overtime_amount = $overtimeCount * (float) ($employee->flat_overtime_rate ?? 0);
            $record->shortage_deduction = $shortage['blocks'] * (float) ($setting->shortage_deduction_rate ?? 0);
            $record->status = 'draft';
            $record->generated_by = Auth::id();
            $record->recalculateTotal();
            $record->save();

            $generated++;
        }

        $message = "{$generated} payroll berhasil digenerate untuk periode " . Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') . '.';
        if ($skippedLocked > 0) {
            $message .= " {$skippedLocked} dilewati karena sudah difinalisasi/dibayar (regenerate gak nimpa histori final).";
        }

        return redirect()->route('dashboard.payroll.index', ['period' => $period])->with('status', $message);
    }

    public function show(PayrollRecord $payroll)
    {
        $payroll->load(['user', 'generator']);

        $setting = OfficeSetting::current();
        $start = Carbon::createFromFormat('Y-m', $payroll->period)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $payroll->period)->endOfMonth()->toDateString();

        $shortage = Attendance::monthlyShortageBlocks($payroll->user_id, $payroll->period, $setting);
        $overtimeCount = OvertimeRequest::query()
            ->where('user_id', $payroll->user_id)
            ->where('status', 'disetujui')
            ->whereBetween('date', [$start, $end])
            ->count();

        return view('dashboard.payroll.show', [
            'payroll' => $payroll,
            'shortage' => $shortage,
            'overtimeCount' => $overtimeCount,
            'shortageRate' => (float) $setting->shortage_deduction_rate,
        ]);
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

        return redirect()->route('dashboard.payroll.show', $payroll)->with('status', 'Penyesuaian payroll disimpan.');
    }

    public function finalize(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Cuma payroll berstatus draft yang bisa difinalisasi.');
        }

        $payroll->update(['status' => 'finalized']);

        return back()->with('status', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) difinalisasi. Angka terkunci, gak bisa digenerate ulang/diedit lagi.");
    }

    public function markPaid(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'finalized') {
            return back()->with('error', 'Cuma payroll berstatus final yang bisa ditandai dibayar.');
        }

        $payroll->update(['status' => 'paid']);

        return back()->with('status', "Payroll {$payroll->user->name} ({$payroll->periodLabel()}) ditandai sudah dibayar.");
    }

    /** Cuma record draft yang boleh dihapus — final/paid adalah histori permanen. */
    public function destroy(PayrollRecord $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'Cuma payroll draft yang bisa dihapus. Payroll final/dibayar adalah histori permanen.');
        }

        $period = $payroll->period;
        $payroll->delete();

        return redirect()->route('dashboard.payroll.index', ['period' => $period])->with('status', 'Payroll draft dihapus.');
    }
}
