<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceAbsence;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Aturan hitung gaji (2026-09-29): hari absen dipotong (hanya yang ditandai),
 * potongan kurang jam diturunkan dari gaji, toleransi sisa di bawah satu blok,
 * pembagi hari dari Pengaturan Kantor, total tidak pernah minus, dan buka
 * kembali payroll final (Owner saja).
 *
 * Waktu dibekukan 2026-09-21 (Senin). Aldora gaji 6.000.000, Gepeng 6.500.000.
 */
class PayrollRulesTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
    }

    private function generateFor(User $user, string $period = '2026-09'): PayrollRecord
    {
        $this->actingAs($this->p['manajer'])->post(route('dashboard.payroll.generate'), [
            'period' => $period,
            'employee_ids' => [$user->id],
        ]);

        return PayrollRecord::where('user_id', $user->id)->where('period', $period)->firstOrFail();
    }

    private function absent(User $user, string $date, string $note = 'Tanpa kabar'): AttendanceAbsence
    {
        return AttendanceAbsence::create([
            'user_id' => $user->id,
            'date' => $date,
            'note' => $note,
            'marked_by' => $this->p['hrd']->id,
        ]);
    }

    private function workDay(User $user, string $date, string $in, string $out): void
    {
        Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => "{$date} {$in}:00",
            'clock_out_at' => "{$date} {$out}:00",
        ]);
    }

    private function approvedLeave(User $user, string $date): void
    {
        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => 'izin_sakit',
            'start_date' => $date,
            'end_date' => $date,
            'work_days' => 1,
            'reason' => 'Demam',
            'status' => 'disetujui',
        ]);
    }

    // ---- potongan hari absen ---------------------------------------------

    public function test_marked_absent_days_are_deducted_at_the_daily_rate(): void
    {
        $this->absent($this->p['aldora'], '2026-09-10');
        $this->absent($this->p['aldora'], '2026-09-11');

        $record = $this->generateFor($this->p['aldora']);

        $this->assertSame(2, $record->absent_days);
        $this->assertEquals(545454.55, $record->absence_deduction); // 2 × (6.000.000 ÷ 22)
        $this->assertSame(22, $record->work_days_divisor);
        $this->assertEquals(5454545.45, $record->total);
    }

    public function test_days_without_attendance_that_are_not_marked_are_not_deducted(): void
    {
        $record = $this->generateFor($this->p['aldora']); // tidak ada absensi & tidak ada penanda sama sekali

        $this->assertSame(0, $record->absent_days);
        $this->assertEquals(0, $record->absence_deduction);
        $this->assertEquals(6000000, $record->total, 'Tanpa penanda Absen, gaji tidak dipotong.');
    }

    public function test_a_marked_day_with_clock_in_or_approved_leave_is_not_deducted(): void
    {
        $this->workDay($this->p['aldora'], '2026-09-14', '09:30', '18:00'); // ada clock-in
        $this->absent($this->p['aldora'], '2026-09-14');
        $this->approvedLeave($this->p['aldora'], '2026-09-15');             // izin disetujui
        $this->absent($this->p['aldora'], '2026-09-15');
        $this->absent($this->p['aldora'], '2026-09-16');                    // satu-satunya yang murni absen

        $record = $this->generateFor($this->p['aldora']);

        $this->assertSame(1, $record->absent_days);
        $this->assertEquals(272727.27, $record->absence_deduction);
    }

    // ---- potongan kurang jam ---------------------------------------------

    public function test_shortage_is_derived_from_salary_and_follows_the_divisor_setting(): void
    {
        $this->workDay($this->p['aldora'], '2026-09-15', '09:30', '13:30'); // kurang 240 menit = 4 blok

        $record = $this->generateFor($this->p['aldora']);
        $this->assertEquals(136363.64, $record->shortage_deduction); // 4 × (6.000.000 ÷ 22 ÷ 8)

        OfficeSetting::current()->update(['payroll_work_days_divisor' => 20]);
        $record = $this->generateFor($this->p['aldora']); // masih draft → ditimpa

        $this->assertSame(20, $record->work_days_divisor);
        $this->assertEquals(150000, $record->shortage_deduction); // 4 × (6.000.000 ÷ 20 ÷ 8)
    }

    public function test_shortage_below_one_block_is_not_deducted(): void
    {
        $this->workDay($this->p['aldora'], '2026-09-15', '09:30', '17:00'); // kurang 30 menit < 1 blok

        $record = $this->generateFor($this->p['aldora']);

        $this->assertEquals(0, $record->shortage_deduction);
        $this->assertEquals(6000000, $record->total);
    }

    // ---- gaji tidak minus --------------------------------------------------

    public function test_total_is_never_below_zero(): void
    {
        $record = $this->generateFor($this->p['gepeng']);

        $this->actingAs($this->p['manajer'])
            ->patch(route('dashboard.payroll.update', $record), ['other_adjustment' => -99000000, 'notes' => 'Uji minus'])
            ->assertSessionHas('status');

        $record->refresh();
        $this->assertEquals(-99000000, $record->other_adjustment, 'Penyesuaian tetap tercatat apa adanya.');
        $this->assertEquals(0, $record->total, 'Total dipatok 0, tidak pernah minus.');
    }

    // ---- buka kembali payroll final ---------------------------------------

    public function test_owner_can_reopen_a_final_payroll_with_a_reason(): void
    {
        $record = $this->generateFor($this->p['gepeng']);
        $this->post(route('dashboard.payroll.finalize', $record));
        $this->assertSame('finalized', $record->fresh()->status);

        $this->actingAs($this->p['owner'])
            ->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'Salah tandai absen tanggal 12'])
            ->assertRedirect(route('dashboard.payroll.show', $record))
            ->assertSessionHas('status');

        $record->refresh();
        $this->assertSame('draft', $record->status);
        $this->assertSame($this->p['owner']->id, $record->reopened_by);
        $this->assertSame('Salah tandai absen tanggal 12', $record->reopen_reason);
        $this->assertNotNull($record->reopened_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Payroll final dibuka kembali']);

        // Sudah draft lagi: bisa digenerate ulang & difinalisasi lagi.
        $this->post(route('dashboard.payroll.generate'), ['period' => '2026-09', 'employee_ids' => [$this->p['gepeng']->id]])
            ->assertSessionHas('status', fn($m) => str_contains($m, '1 payroll berhasil digenerate'));
        $this->post(route('dashboard.payroll.finalize', $record->fresh()))->assertSessionHas('status');
        $this->get(route('dashboard.payroll.show', $record))->assertOk()->assertSee('Pernah dibuka kembali');
    }

    public function test_only_the_owner_can_reopen_even_with_manage_access_to_payroll(): void
    {
        $record = $this->generateFor($this->p['gepeng']);
        $record->update(['status' => 'finalized']);

        $this->actingAs($this->p['manajer'])   // punya payroll = manage, tapi bukan Owner
            ->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'Coba buka'])
            ->assertForbidden();

        $this->assertSame('finalized', $record->fresh()->status);
    }

    public function test_reopen_needs_a_reason_and_refuses_paid_and_draft_records(): void
    {
        $record = $this->generateFor($this->p['gepeng']);
        $owner = $this->actingAs($this->p['owner']);

        // Draft: belum final, tidak ada yang dibuka.
        $owner->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'Alasan cukup panjang'])
            ->assertSessionHas('error', 'Cuma payroll berstatus final yang bisa dibuka kembali.');

        $record->update(['status' => 'finalized']);
        $owner->post(route('dashboard.payroll.reopen', $record), [])->assertSessionHasErrors('reopen_reason');
        $owner->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'x'])->assertSessionHasErrors('reopen_reason');
        $this->assertSame('finalized', $record->fresh()->status);

        $record->update(['status' => 'paid']);
        $owner->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'Alasan cukup panjang'])
            ->assertSessionHas('error', fn($m) => str_contains($m, 'sudah dibayar tidak bisa dibuka kembali'));
        $this->assertSame('paid', $record->fresh()->status);
    }

    public function test_payroll_detail_shows_the_absence_breakdown_and_the_reopen_form_only_to_the_owner(): void
    {
        $this->absent($this->p['aldora'], '2026-09-10');
        $record = $this->generateFor($this->p['aldora']);
        $record->update(['status' => 'finalized']);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.payroll.show', $record))
            ->assertOk()
            ->assertSee('Potongan Hari Absen')
            ->assertDontSee('name="reopen_reason"', false); // panduan halaman menyebut tombolnya, jadi cek field formnya

        $this->actingAs($this->p['owner'])->get(route('dashboard.payroll.show', $record))
            ->assertOk()
            ->assertSee('name="reopen_reason"', false);
    }

    // ---- menandai Absen -----------------------------------------------------

    public function test_hrd_can_mark_and_unmark_an_absent_day(): void
    {
        $hrd = $this->actingAs($this->p['hrd']);

        $hrd->post(route('attendance.recap.absence.store', $this->p['aldora']), ['date' => '2026-09-15', 'note' => 'Tidak masuk tanpa kabar'])
            ->assertSessionHas('status');

        $absence = AttendanceAbsence::sole();
        $this->assertSame($this->p['aldora']->id, $absence->user_id);
        $this->assertSame($this->p['hrd']->id, $absence->marked_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Absen ditandai']);

        $hrd->get(route('attendance.recap.show', [$this->p['aldora'], 'bulan' => '2026-09']))
            ->assertOk()
            ->assertSee('Hari Absen (Dipotong Payroll)')
            ->assertSee('Tidak masuk tanpa kabar');

        $hrd->delete(route('attendance.recap.absence.destroy', $absence))->assertSessionHas('status');
        $this->assertDatabaseCount('attendance_absences', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Penanda absen dibatalkan']);
    }

    public function test_marking_absent_is_validated_and_guards_conflicts(): void
    {
        $hrd = $this->actingAs($this->p['hrd']);
        $url = route('attendance.recap.absence.store', $this->p['aldora']);

        $hrd->post($url, ['date' => '2026-09-22', 'note' => 'Besok'])->assertSessionHasErrors('date');       // masa depan
        $hrd->post($url, ['date' => '2026-09-15', 'note' => ''])->assertSessionHasErrors('note');
        $hrd->post($url, ['date' => 'kemarin', 'note' => 'Salah format'])->assertSessionHasErrors('date');

        $this->workDay($this->p['aldora'], '2026-09-14', '09:30', '18:00');
        $hrd->post($url, ['date' => '2026-09-14', 'note' => 'Padahal masuk'])
            ->assertSessionHas('error', fn($m) => str_contains($m, 'punya catatan masuk'));

        $this->approvedLeave($this->p['aldora'], '2026-09-16');
        $hrd->post($url, ['date' => '2026-09-16', 'note' => 'Padahal izin'])
            ->assertSessionHas('error', fn($m) => str_contains($m, 'izin/cuti/sakit'));

        $hrd->post($url, ['date' => '2026-09-15', 'note' => 'Tanpa kabar'])->assertSessionHas('status');
        $hrd->post($url, ['date' => '2026-09-15', 'note' => 'Dobel'])
            ->assertSessionHas('error', fn($m) => str_contains($m, 'sudah ditandai absen'));

        $this->assertDatabaseCount('attendance_absences', 1);
    }

    public function test_marking_absent_needs_manage_access_and_respects_the_team_scope(): void
    {
        $url = route('attendance.recap.absence.store', $this->p['aldora']);
        $payload = ['date' => '2026-09-15', 'note' => 'Tanpa kabar'];

        // Kanaya (Manajer) hanya punya people = view → tidak boleh menandai.
        $this->actingAs($this->p['manajer'])->post($url, $payload)->assertForbidden();

        // Karyawan biasa tanpa modul people.
        $this->actingAs($this->p['gepeng'])->post($url, $payload)->assertForbidden();

        // people = manage tapi bukan atasan Aldora → di luar scope rekapnya.
        $outsider = $this->makeUser('karyawan', ['people' => 'manage'], $this->p['owner']->id);
        $this->actingAs($outsider)->post($url, $payload)->assertForbidden();

        $this->assertDatabaseCount('attendance_absences', 0);
    }

    public function test_absent_marks_are_locked_once_the_payroll_of_that_month_is_final(): void
    {
        $absence = $this->absent($this->p['aldora'], '2026-09-10');
        $record = $this->generateFor($this->p['aldora']);
        $record->update(['status' => 'finalized']);

        $hrd = $this->actingAs($this->p['hrd']);

        $hrd->post(route('attendance.recap.absence.store', $this->p['aldora']), ['date' => '2026-09-11', 'note' => 'Tanpa kabar'])
            ->assertSessionHas('error', fn($m) => str_contains($m, 'terkunci'));
        $hrd->delete(route('attendance.recap.absence.destroy', $absence))
            ->assertSessionHas('error', fn($m) => str_contains($m, 'terkunci'));
        $this->assertDatabaseCount('attendance_absences', 1);

        // Owner membuka kembali payroll → penanda boleh diubah lagi.
        $this->actingAs($this->p['owner'])->post(route('dashboard.payroll.reopen', $record), ['reopen_reason' => 'Salah tandai tanggal']);
        $this->actingAs($this->p['hrd'])->delete(route('attendance.recap.absence.destroy', $absence))->assertSessionHas('status');
        $this->assertDatabaseCount('attendance_absences', 0);
    }
}