<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual C16–C21 — pengajuan izin/cuti, lembur, dan koreksi presensi
 * dari sisi karyawan (App Mode). Hari "ini" dibekukan ke Senin 2026-09-21.
 */
class EmployeeRequestsTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $people;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->people = $this->company();
        $this->employee = $this->people['aldora'];
    }

    private function leave(array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id' => $this->employee->id,
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-24',
            'work_days' => 3,
            'reason' => 'Liburan',
            'status' => 'pending',
        ], $overrides));
    }

    private function submitLeave(array $overrides = [])
    {
        return $this->actingAs($this->employee)->post(route('employee.leave.store'), array_merge([
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-24',
            'reason' => 'Liburan keluarga',
        ], $overrides));
    }

    // ---- C16: izin/cuti ------------------------------------------------

    public function test_leave_page_shows_annual_balance(): void
    {
        $this->actingAs($this->employee)->get(route('employee.leave.index'))
            ->assertOk()
            ->assertViewHas('remainingLeave', 12)
            ->assertViewHas('entitlement', 12);
    }

    public function test_leave_form_validation_rules(): void
    {
        $this->submitLeave(['start_date' => '2026-09-20', 'end_date' => '2026-09-22'])
            ->assertSessionHasErrors(['start_date' => 'Tanggal mulai nggak boleh tanggal yang udah lewat.']);

        $this->submitLeave(['start_date' => '2026-09-24', 'end_date' => '2026-09-22'])
            ->assertSessionHasErrors(['end_date' => 'Tanggal selesai nggak boleh sebelum tanggal mulai.']);

        $this->submitLeave(['reason' => ''])->assertSessionHasErrors('reason');
        $this->submitLeave(['type' => 'bolos'])->assertSessionHasErrors('type');
        $this->submitLeave(['reason' => str_repeat('x', 1001)])->assertSessionHasErrors('reason');

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_annual_leave_over_the_remaining_balance_is_rejected_with_the_numbers(): void
    {
        // 2026-09-22 s/d 2026-10-14 = 17 hari kerja > jatah 12.
        $this->submitLeave(['start_date' => '2026-09-22', 'end_date' => '2026-10-14'])
            ->assertSessionHasErrors(['start_date' => 'Sisa jatah cuti tahunan kamu tinggal 12 hari kerja, pengajuan ini butuh 17 hari kerja.']);

        $this->assertSame(0, LeaveRequest::count());
    }

    public function test_valid_leave_is_saved_as_pending_with_weekends_excluded(): void
    {
        // Jumat 25 Sep – Selasa 29 Sep = Jum, Sen, Sel = 3 hari kerja.
        $this->submitLeave(['start_date' => '2026-09-25', 'end_date' => '2026-09-29'])
            ->assertSessionHas('status', 'Pengajuan berhasil dikirim, tunggu persetujuan atasan.')
            ->assertRedirect()->assertSessionHasNoErrors();

        $row = LeaveRequest::sole();
        $this->assertSame('pending', $row->status);
        $this->assertSame(3, $row->work_days);
        $this->assertSame($this->employee->id, $row->user_id);
    }

    public function test_pending_leave_does_not_reduce_the_balance_but_approved_does(): void
    {
        $leave = $this->leave();

        $this->assertSame(12, $this->employee->fresh()->remainingAnnualLeaveDays());

        $leave->approveBy($this->people['manajer']);
        $this->assertSame(9, $this->employee->fresh()->remainingAnnualLeaveDays());
    }

    // ---- C17: jenis lain tidak memotong jatah -------------------------

    public function test_sick_personal_and_other_leave_do_not_use_the_annual_quota(): void
    {
        foreach (['izin_sakit', 'izin_pribadi', 'lainnya'] as $type) {
            // 30 hari kerja jauh di atas jatah, tapi bukan cuti tahunan → boleh.
            $this->submitLeave(['type' => $type, 'start_date' => '2026-09-22', 'end_date' => '2026-11-02'])
                ->assertRedirect()->assertSessionHasNoErrors();
        }

        $this->assertSame(3, LeaveRequest::count());

        LeaveRequest::query()->update(['status' => 'disetujui']);
        $this->assertSame(12, $this->employee->fresh()->remainingAnnualLeaveDays());
    }

    // ---- C18: batalkan -------------------------------------------------

    public function test_cancel_requires_a_reason_and_restores_the_balance(): void
    {
        $leave = $this->leave(['status' => 'disetujui']);
        $this->assertSame(9, $this->employee->fresh()->remainingAnnualLeaveDays());

        $this->actingAs($this->employee)->post(route('employee.leave.cancel', $leave), [])
            ->assertSessionHasErrors(['cancellation_reason' => 'Alasan pembatalan wajib diisi.']);
        $this->assertSame('disetujui', $leave->fresh()->status);

        $this->actingAs($this->employee)->post(route('employee.leave.cancel', $leave), ['cancellation_reason' => 'Rencana batal'])
            ->assertSessionHas('status', 'Pengajuan berhasil dibatalkan.');

        $leave->refresh();
        $this->assertSame('dibatalkan', $leave->status);
        $this->assertSame('Rencana batal', $leave->cancellation_reason);
        $this->assertSame($this->employee->id, $leave->cancelled_by);
        $this->assertSame(12, $this->employee->fresh()->remainingAnnualLeaveDays());
    }

    public function test_pending_leave_can_be_cancelled_too(): void
    {
        $leave = $this->leave();

        $this->actingAs($this->employee)->post(route('employee.leave.cancel', $leave), ['cancellation_reason' => 'Salah tanggal'])
            ->assertSessionHas('status');

        $this->assertSame('dibatalkan', $leave->fresh()->status);
    }

    public function test_rejected_or_already_finished_leave_cannot_be_cancelled(): void
    {
        $rejected = $this->leave(['status' => 'ditolak']);
        $past = $this->leave(['start_date' => '2026-09-14', 'end_date' => '2026-09-15', 'status' => 'disetujui']);

        foreach ([$rejected, $past] as $leave) {
            $this->actingAs($this->employee)->post(route('employee.leave.cancel', $leave), ['cancellation_reason' => 'x'])
                ->assertSessionHas('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
        }

        $this->assertSame('ditolak', $rejected->fresh()->status);
        $this->assertSame('disetujui', $past->fresh()->status);
    }

    public function test_cannot_cancel_someone_elses_leave(): void
    {
        $leave = $this->leave();
        $other = $this->people['gepeng'];

        $this->actingAs($other)->post(route('employee.leave.cancel', $leave), ['cancellation_reason' => 'iseng'])
            ->assertForbidden();

        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_leave_history_only_lists_own_requests(): void
    {
        $mine = $this->leave(['reason' => 'punya-aldora']);
        $other = $this->makeUser();
        LeaveRequest::create([
            'user_id' => $other->id,
            'type' => 'izin_sakit',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-22',
            'work_days' => 1,
            'reason' => 'punya-orang-lain',
            'status' => 'pending',
        ]);

        $this->actingAs($this->employee)->get(route('employee.leave.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1 && $rows->first()->is($mine));
    }

    // ---- C19: tumpang tindih -------------------------------------------

    /**
     * Mendokumentasikan kondisi SAAT INI (checklist C19): aplikasi belum
     * memvalidasi pengajuan tumpang tindih, sehingga dua pengajuan dengan
     * tanggal sama sama-sama tersimpan. Kalau validasi ditambahkan nanti,
     * tes ini akan gagal — ubah menjadi assertSessionHasErrors.
     */
    public function test_overlapping_leave_requests_are_currently_not_blocked(): void
    {
        $this->submitLeave(['type' => 'izin_pribadi'])->assertRedirect()->assertSessionHasNoErrors();
        $this->submitLeave(['type' => 'izin_pribadi'])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, LeaveRequest::count());
    }

    // ---- C20: lembur ---------------------------------------------------

    private function submitOvertime(array $overrides = [])
    {
        return $this->actingAs($this->employee)->post(route('employee.overtime.store'), array_merge([
            'date' => '2026-09-22',
            'reason' => 'Mengejar deadline rilis',
        ], $overrides));
    }

    public function test_overtime_rejects_past_date_and_missing_reason(): void
    {
        $this->submitOvertime(['date' => '2026-09-20'])
            ->assertSessionHasErrors(['date' => 'Tanggal lembur nggak boleh tanggal yang udah lewat.']);
        $this->submitOvertime(['reason' => ''])->assertSessionHasErrors('reason');
        $this->submitOvertime(['date' => ''])->assertSessionHasErrors('date');

        $this->assertSame(0, OvertimeRequest::count());
    }

    public function test_valid_overtime_is_saved_as_pending_and_today_is_allowed(): void
    {
        $this->submitOvertime(['date' => '2026-09-21'])
            ->assertSessionHas('status')
            ->assertRedirect()->assertSessionHasNoErrors();

        $row = OvertimeRequest::sole();
        $this->assertSame('pending', $row->status);
        $this->assertSame('2026-09-21', $row->date->toDateString());
    }

    public function test_same_active_date_twice_is_rejected(): void
    {
        $this->submitOvertime()->assertRedirect()->assertSessionHasNoErrors();

        $this->submitOvertime()->assertSessionHasErrors(['date' => 'Kamu sudah punya pengajuan lembur aktif di tanggal ini.']);
        $this->assertSame(1, OvertimeRequest::count());
    }

    /**
     * Dulu: UNIQUE(user_id, date) di database membuat pengajuan ulang setelah
     * ditolak/dibatalkan meledak jadi error 500. Kini boleh; yang tetap
     * ditolak hanyalah pengajuan kedua selagi yang pertama masih aktif.
     */
    public function test_same_date_can_be_resubmitted_after_reject_or_cancel(): void
    {
        foreach (['ditolak', 'dibatalkan'] as $status) {
            OvertimeRequest::query()->delete();

            $this->submitOvertime()->assertRedirect()->assertSessionHasNoErrors();
            OvertimeRequest::query()->update(['status' => $status]);

            $this->submitOvertime()->assertRedirect()->assertSessionHasNoErrors();

            $this->assertSame(2, OvertimeRequest::count(), "Ajukan ulang setelah {$status}");
            $this->assertSame(1, OvertimeRequest::where('status', 'pending')->count());
        }

        // Selagi yang baru masih pending, pengajuan ketiga tetap ditolak.
        $this->submitOvertime()->assertSessionHasErrors(['date' => 'Kamu sudah punya pengajuan lembur aktif di tanggal ini.']);
    }

    public function test_overtime_can_be_cancelled_with_reason_by_its_owner_only(): void
    {
        $this->submitOvertime();
        $overtime = OvertimeRequest::sole();

        $this->actingAs($this->employee)->post(route('employee.overtime.cancel', $overtime), [])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($this->makeUser())->post(route('employee.overtime.cancel', $overtime), ['cancellation_reason' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->employee)->post(route('employee.overtime.cancel', $overtime), ['cancellation_reason' => 'Batal lembur'])
            ->assertSessionHas('status', 'Pengajuan lembur berhasil dibatalkan.');

        $this->assertSame('dibatalkan', $overtime->fresh()->status);
    }

    public function test_overtime_page_lists_only_own_requests(): void
    {
        $this->submitOvertime();
        OvertimeRequest::create(['user_id' => $this->makeUser()->id, 'date' => '2026-09-23', 'reason' => 'lain', 'status' => 'pending']);

        $this->actingAs($this->employee)->get(route('employee.overtime.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1);
    }

    // ---- C21: koreksi presensi -----------------------------------------

    private function submitCorrection(array $overrides = [])
    {
        return $this->actingAs($this->employee)->post(route('employee.attendanceCorrection.store'), array_merge([
            'date' => '2026-09-18',
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:00',
            'requested_mode' => 'kantor',
            'reason' => 'Lupa absen karena listrik padam',
        ], $overrides));
    }

    public function test_correction_rejects_future_date_empty_times_and_bad_format(): void
    {
        $this->submitCorrection(['date' => '2026-09-22'])
            ->assertSessionHasErrors(['date' => 'Koreksi presensi cuma buat tanggal hari ini atau sebelumnya.']);

        $this->submitCorrection(['requested_clock_in' => null, 'requested_clock_out' => null])
            ->assertSessionHasErrors(['requested_clock_in' => 'Isi minimal salah satu: jam masuk atau jam pulang yang seharusnya.']);

        $this->submitCorrection(['requested_clock_in' => '9.30 pagi'])->assertSessionHasErrors('requested_clock_in');
        $this->submitCorrection(['requested_mode' => 'terbang'])->assertSessionHasErrors('requested_mode');
        $this->submitCorrection(['reason' => ''])->assertSessionHasErrors('reason');

        $this->assertSame(0, AttendanceCorrectionRequest::count());
    }

    public function test_valid_correction_is_saved_pending_and_today_or_only_one_time_is_allowed(): void
    {
        $this->submitCorrection()->assertSessionHas('status')->assertRedirect()->assertSessionHasNoErrors();
        $this->submitCorrection(['date' => '2026-09-21', 'requested_clock_out' => null])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, AttendanceCorrectionRequest::where('status', 'pending')->count());
    }

    public function test_correction_can_only_be_cancelled_while_pending_and_by_its_owner(): void
    {
        $this->submitCorrection();
        $correction = AttendanceCorrectionRequest::sole();
        $route = route('employee.attendanceCorrection.cancel', $correction);

        $this->actingAs($this->employee)->post($route, [])->assertSessionHasErrors('cancellation_reason');
        $this->actingAs($this->makeUser())->post($route, ['cancellation_reason' => 'x'])->assertForbidden();

        $this->actingAs($this->employee)->post($route, ['cancellation_reason' => 'Salah tanggal'])
            ->assertSessionHas('status', 'Pengajuan berhasil dibatalkan.');
        $this->assertSame('dibatalkan', $correction->fresh()->status);

        // Sudah bukan pending → tidak bisa dibatalkan lagi.
        $this->actingAs($this->employee)->post($route, ['cancellation_reason' => 'lagi'])
            ->assertSessionHas('error');
    }

    public function test_correction_page_lists_only_own_requests(): void
    {
        $this->submitCorrection();
        AttendanceCorrectionRequest::create([
            'user_id' => $this->makeUser()->id,
            'date' => '2026-09-17',
            'requested_clock_in' => '09:00',
            'requested_mode' => 'wfh',
            'reason' => 'lain',
            'status' => 'pending',
        ]);

        $this->actingAs($this->employee)->get(route('employee.attendanceCorrection.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1);
    }

    // ---- akses ----------------------------------------------------------

    public function test_request_pages_and_endpoints_require_login(): void
    {
        Auth::logout();

        foreach ([route('employee.leave.index'), route('employee.overtime.index'), route('employee.attendanceCorrection.index')] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $this->post(route('employee.leave.store'), [])->assertRedirect('/login');
        $this->post(route('employee.overtime.store'), [])->assertRedirect('/login');
        $this->post(route('employee.attendanceCorrection.store'), [])->assertRedirect('/login');
    }

    public function test_leave_submission_is_throttled_at_ten_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->submitLeave(['type' => 'izin_pribadi'])->assertRedirect();
        }

        $this->submitLeave(['type' => 'izin_pribadi'])->assertStatus(429);
    }
}