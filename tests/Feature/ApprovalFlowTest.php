<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian D — persetujuan atasan (D1–D9).
 * Aturan: yang berhak memutuskan = atasan langsung (`manager_id`) atau Owner,
 * bukan siapa pun yang punya modul `people`. Atasan Aldora & Gepeng = Kanaya;
 * atasan Kanaya & Rania = Owner.
 */
class ApprovalFlowTest extends TestCase
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
        Storage::fake('local');
        $this->p = $this->company();
    }

    private function leave(User $user, array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'user_id' => $user->id,
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-24',
            'work_days' => 3,
            'reason' => 'Liburan',
            'status' => 'pending',
        ], $overrides));
    }

    private function overtime(User $user, array $overrides = []): OvertimeRequest
    {
        return OvertimeRequest::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-09-22',
            'reason' => 'Deadline',
            'status' => 'pending',
        ], $overrides));
    }

    private function correction(User $user, array $overrides = []): AttendanceCorrectionRequest
    {
        return AttendanceCorrectionRequest::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-09-18',
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:00',
            'requested_mode' => 'kantor',
            'reason' => 'Lupa absen',
            'status' => 'pending',
        ], $overrides));
    }

    // ---- daftar persetujuan (D1, D4, D5) -------------------------------

    public function test_manager_only_sees_direct_subordinates_and_owner_sees_everyone(): void
    {
        $this->leave($this->p['aldora']);
        $this->leave($this->p['gepeng']);
        $this->leave($this->p['hrd']); // atasan Rania = Owner, bukan Kanaya

        $this->actingAs($this->p['manajer'])->get(route('approval.leave.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 2
                && $rows->pluck('user_id')->sort()->values()->all() === collect([$this->p['aldora']->id, $this->p['gepeng']->id])->sort()->values()->all());

        $this->actingAs($this->p['owner'])->get(route('approval.leave.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 3);
    }

    public function test_hrd_opens_the_page_but_sees_an_empty_list_because_she_is_nobodys_manager(): void
    {
        $this->leave($this->p['aldora']);
        $this->overtime($this->p['aldora']);
        $this->correction($this->p['aldora']);

        foreach (['approval.leave.index', 'approval.overtime.index', 'approval.attendanceCorrection.index'] as $route) {
            $this->actingAs($this->p['hrd'])->get(route($route, ['status' => 'semua']))
                ->assertOk()
                ->assertViewHas('rows', fn($rows) => $rows->isEmpty());
        }
    }

    public function test_status_filter_defaults_to_pending_and_ignores_garbage(): void
    {
        $this->leave($this->p['aldora'], ['status' => 'pending']);
        $this->leave($this->p['aldora'], ['status' => 'disetujui']);
        $this->leave($this->p['gepeng'], ['status' => 'ditolak']);

        $manager = $this->actingAs($this->p['manajer']);

        $manager->get(route('approval.leave.index'))->assertViewHas('rows', fn($r) => $r->count() === 1)->assertViewHas('status', 'pending');
        $manager->get(route('approval.leave.index', ['status' => 'semua']))->assertViewHas('rows', fn($r) => $r->count() === 3);
        $manager->get(route('approval.leave.index', ['status' => 'ditolak']))->assertViewHas('rows', fn($r) => $r->count() === 1);
        $manager->get(route('approval.leave.index', ['status' => 'ngawur']))->assertViewHas('status', 'pending');
    }

    // ---- setujui / tolak (D1, D2) --------------------------------------

    public function test_manager_approves_leave_records_decision_audit_log_and_reduces_balance(): void
    {
        $leave = $this->leave($this->p['aldora']);

        $this->actingAs($this->p['manajer'])->post(route('approval.leave.approve', $leave))
            ->assertSessionHas('status', 'Pengajuan Aldora disetujui.');

        $leave->refresh();
        $this->assertSame('disetujui', $leave->status);
        $this->assertSame($this->p['manajer']->id, $leave->approver_id);
        $this->assertNotNull($leave->decided_at);

        $this->assertSame(9, $this->p['aldora']->fresh()->remainingAnnualLeaveDays());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Izin/cuti disetujui', 'actor_id' => $this->p['manajer']->id]);
    }

    public function test_rejection_requires_a_reason_and_the_employee_can_read_it(): void
    {
        $leave = $this->leave($this->p['aldora']);
        $reject = route('approval.leave.reject', $leave);

        $this->actingAs($this->p['manajer'])->post($reject, [])
            ->assertSessionHasErrors(['decision_note' => 'Alasan penolakan wajib diisi.']);
        $this->assertSame('pending', $leave->fresh()->status);

        $this->post($reject, ['decision_note' => 'Sedang ada deadline rilis'])
            ->assertSessionHas('status', 'Pengajuan Aldora ditolak.');

        $leave->refresh();
        $this->assertSame('ditolak', $leave->status);
        $this->assertSame('Sedang ada deadline rilis', $leave->decision_note);
        $this->assertSame(12, $this->p['aldora']->fresh()->remainingAnnualLeaveDays());

        $this->actingAs($this->p['aldora'])->get(route('employee.leave.index'))
            ->assertOk()
            ->assertSee('Sedang ada deadline rilis');
    }

    // ---- D3: putus dua kali --------------------------------------------

    public function test_deciding_an_already_decided_request_is_refused(): void
    {
        $leave = $this->leave($this->p['aldora']);
        $manager = $this->actingAs($this->p['manajer']);

        $manager->post(route('approval.leave.approve', $leave));

        $manager->post(route('approval.leave.approve', $leave))
            ->assertSessionHas('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');
        $manager->post(route('approval.leave.reject', $leave), ['decision_note' => 'terlambat'])
            ->assertSessionHas('warning', 'Pengajuan ini sudah diputuskan sebelumnya.');

        $this->assertSame('disetujui', $leave->fresh()->status);
    }

    // ---- D4 / D5: siapa yang berwenang ---------------------------------

    public function test_hrd_cannot_decide_requests_even_by_posting_directly(): void
    {
        $leave = $this->leave($this->p['aldora']);
        $overtime = $this->overtime($this->p['aldora']);
        $correction = $this->correction($this->p['aldora']);
        $hrd = $this->actingAs($this->p['hrd']);

        $hrd->post(route('approval.leave.approve', $leave))->assertForbidden();
        $hrd->post(route('approval.leave.reject', $leave), ['decision_note' => 'x'])->assertForbidden();
        $hrd->post(route('approval.overtime.approve', $overtime))->assertForbidden();
        $hrd->post(route('approval.attendanceCorrection.approve', $correction))->assertForbidden();

        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('pending', $overtime->fresh()->status);
        $this->assertSame('pending', $correction->fresh()->status);
    }

    public function test_manager_cannot_decide_someone_outside_her_team_but_owner_can_decide_anyone(): void
    {
        $hrdLeave = $this->leave($this->p['hrd']);

        $this->actingAs($this->p['manajer'])->post(route('approval.leave.approve', $hrdLeave))->assertForbidden();
        $this->assertSame('pending', $hrdLeave->fresh()->status);

        $this->actingAs($this->p['owner'])->post(route('approval.leave.approve', $hrdLeave))->assertSessionHas('status');
        $this->assertSame('disetujui', $hrdLeave->fresh()->status);

        $aldoraLeave = $this->leave($this->p['aldora']);
        $this->actingAs($this->p['owner'])->post(route('approval.leave.approve', $aldoraLeave))->assertSessionHas('status');
        $this->assertSame('disetujui', $aldoraLeave->fresh()->status);
    }

    public function test_employees_without_the_people_module_get_403_on_every_approval_page(): void
    {
        foreach (['aldora', 'gepeng'] as $who) {
            $this->actingAs($this->p[$who]);

            foreach (['approval.leave.index', 'approval.overtime.index', 'approval.attendanceCorrection.index'] as $route) {
                $this->get(route($route))->assertForbidden();
            }
        }

        auth()->logout();
        $this->get(route('approval.leave.index'))->assertRedirect('/login');
    }

    // ---- D6: batalkan yang sudah disetujui -----------------------------

    public function test_manager_can_cancel_an_approved_leave_with_a_reason_and_balance_returns(): void
    {
        $leave = $this->leave($this->p['aldora'], ['status' => 'disetujui']);
        $cancel = route('approval.leave.cancel', $leave);

        $this->actingAs($this->p['manajer'])->post($cancel, [])->assertSessionHasErrors('cancellation_reason');

        $this->post($cancel, ['cancellation_reason' => 'Ada proyek mendadak'])
            ->assertSessionHas('status', 'Izin/cuti Aldora dibatalkan.');

        $leave->refresh();
        $this->assertSame('dibatalkan', $leave->status);
        $this->assertSame($this->p['manajer']->id, $leave->cancelled_by);
        $this->assertSame('Ada proyek mendadak', $leave->cancellation_reason);
        $this->assertSame(12, $this->p['aldora']->fresh()->remainingAnnualLeaveDays());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Izin/cuti dibatalkan']);
    }

    public function test_employee_can_clock_in_again_after_the_leave_covering_today_is_cancelled(): void
    {
        $leave = $this->leave($this->p['aldora'], ['start_date' => '2026-09-21', 'end_date' => '2026-09-21', 'work_days' => 1, 'status' => 'disetujui']);
        $clock = fn() => $this->actingAs($this->p['aldora'])->post(route('employee.attendance.clockIn'), array_merge(['mode' => 'wfh'], $this->officeCoordinates()));

        $clock()->assertSessionHas('error');
        $this->assertSame(0, Attendance::count());

        $this->actingAs($this->p['manajer'])->post(route('approval.leave.cancel', $leave), ['cancellation_reason' => 'Masuk saja'])
            ->assertSessionHas('status');

        $clock()->assertSessionHas('status');
        $this->assertSame(1, Attendance::count());
    }

    public function test_finished_leave_cannot_be_cancelled_by_the_manager(): void
    {
        $leave = $this->leave($this->p['aldora'], ['start_date' => '2026-09-14', 'end_date' => '2026-09-15', 'status' => 'disetujui']);

        $this->actingAs($this->p['manajer'])->post(route('approval.leave.cancel', $leave), ['cancellation_reason' => 'x'])
            ->assertSessionHas('error', 'Pengajuan ini sudah tidak bisa dibatalkan.');
    }

    // ---- D7: lembur ----------------------------------------------------

    public function test_overtime_approve_reject_cancel_cycle_with_audit_logs(): void
    {
        $approved = $this->overtime($this->p['aldora'], ['date' => '2026-09-22']);
        $rejected = $this->overtime($this->p['aldora'], ['date' => '2026-09-23']);
        $manager = $this->actingAs($this->p['manajer']);

        $manager->post(route('approval.overtime.approve', $approved))
            ->assertSessionHas('status', 'Pengajuan lembur Aldora disetujui.');
        $manager->post(route('approval.overtime.reject', $rejected), [])->assertSessionHasErrors('decision_note');
        $manager->post(route('approval.overtime.reject', $rejected), ['decision_note' => 'Tidak perlu'])
            ->assertSessionHas('status');

        $approved->refresh();
        $rejected->refresh();
        $this->assertSame('disetujui', $approved->status);
        $this->assertSame($this->p['manajer']->id, $approved->approver_id);
        $this->assertSame('ditolak', $rejected->status);
        $this->assertSame('Tidak perlu', $rejected->decision_note);

        $manager->post(route('approval.overtime.approve', $approved))->assertSessionHas('warning');

        $manager->post(route('approval.overtime.cancel', $approved), ['cancellation_reason' => 'Rencana berubah'])
            ->assertSessionHas('status', 'Lembur Aldora dibatalkan.');
        $this->assertSame('dibatalkan', $approved->fresh()->status);

        foreach (['Lembur disetujui', 'Lembur ditolak', 'Lembur dibatalkan'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
    }

    public function test_approved_overtime_removes_the_shortage_for_that_day(): void
    {
        Attendance::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-15',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-15 09:30:00',
            'clock_out_at' => '2026-09-15 13:30:00',
        ]);
        $overtime = $this->overtime($this->p['aldora'], ['date' => '2026-09-15']);

        $blocks = fn() => Attendance::monthlyShortageBlocks($this->p['aldora']->id, '2026-09')['blocks'];

        $this->assertSame(4, $blocks());

        $this->actingAs($this->p['manajer'])->post(route('approval.overtime.approve', $overtime));
        $this->assertSame(0, $blocks());
    }

    public function test_overtime_is_limited_to_the_direct_manager_or_owner(): void
    {
        $overtime = $this->overtime($this->p['hrd']);

        $this->actingAs($this->p['manajer'])->post(route('approval.overtime.approve', $overtime))->assertForbidden();
        $this->actingAs($this->p['owner'])->post(route('approval.overtime.approve', $overtime))->assertSessionHas('status');
    }

    // ---- D8: koreksi presensi ------------------------------------------

    public function test_approving_a_correction_updates_the_existing_attendance_and_keeps_the_original_times(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-18 11:00:00',
            'clock_out_at' => '2026-09-18 15:00:00',
        ]);
        $correction = $this->correction($this->p['aldora']);

        $this->actingAs($this->p['manajer'])->post(route('approval.attendanceCorrection.approve', $correction))
            ->assertSessionHas('status', 'Koreksi presensi Aldora disetujui & sudah diterapkan.');

        $attendance->refresh();
        $this->assertSame('09:30', $attendance->clock_in_at->format('H:i'));
        $this->assertSame('18:00', $attendance->clock_out_at->format('H:i'));
        $this->assertSame('11:00', $attendance->original_clock_in_at->format('H:i'));
        $this->assertSame('15:00', $attendance->original_clock_out_at->format('H:i'));
        $this->assertSame($this->p['manajer']->id, $attendance->corrected_by);
        $this->assertStringContainsString("#{$correction->id}", $attendance->correction_note);

        $correction->refresh();
        $this->assertSame('disetujui', $correction->status);
        $this->assertSame($attendance->id, $correction->applied_attendance_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Koreksi presensi disetujui']);
    }

    public function test_approving_a_correction_for_a_day_without_attendance_creates_the_record(): void
    {
        $correction = $this->correction($this->p['gepeng'], ['requested_mode' => 'wfh', 'requested_clock_out' => null]);

        $this->actingAs($this->p['manajer'])->post(route('approval.attendanceCorrection.approve', $correction))
            ->assertSessionHas('status');

        $row = Attendance::where('user_id', $this->p['gepeng']->id)->sole();
        $this->assertSame('2026-09-18', $row->date->toDateString());
        $this->assertSame('wfh', $row->mode);
        $this->assertSame('09:30', $row->clock_in_at->format('H:i'));
        $this->assertNull($row->clock_out_at);
    }

    public function test_rejecting_or_cancelling_a_correction_leaves_attendance_untouched(): void
    {
        $rejected = $this->correction($this->p['aldora']);
        $cancelled = $this->correction($this->p['aldora'], ['date' => '2026-09-17']);
        $manager = $this->actingAs($this->p['manajer']);

        $manager->post(route('approval.attendanceCorrection.reject', $rejected), [])->assertSessionHasErrors('decision_note');
        $manager->post(route('approval.attendanceCorrection.reject', $rejected), ['decision_note' => 'Data tidak cocok'])
            ->assertSessionHas('status');
        $manager->post(route('approval.attendanceCorrection.cancel', $cancelled), ['cancellation_reason' => 'Dobel'])
            ->assertSessionHas('status');

        $this->assertSame('ditolak', $rejected->fresh()->status);
        $this->assertSame('Data tidak cocok', $rejected->fresh()->decision_note);
        $this->assertSame('dibatalkan', $cancelled->fresh()->status);
        $this->assertSame(0, Attendance::count());

        // Yang sudah diputuskan tidak bisa dibatalkan lagi.
        $manager->post(route('approval.attendanceCorrection.cancel', $rejected), ['cancellation_reason' => 'x'])
            ->assertSessionHas('error');
    }

    public function test_correction_decisions_are_limited_to_direct_manager_or_owner(): void
    {
        $correction = $this->correction($this->p['hrd']);

        $this->actingAs($this->p['manajer'])->post(route('approval.attendanceCorrection.approve', $correction))->assertForbidden();
        $this->actingAs($this->p['owner'])->post(route('approval.attendanceCorrection.approve', $correction))->assertSessionHas('status');
    }

    // ---- audit log tercatat lengkap ------------------------------------

    public function test_every_approval_action_writes_an_audit_log_entry_with_the_actor(): void
    {
        $leave = $this->leave($this->p['aldora']);

        $this->actingAs($this->p['manajer'])->post(route('approval.leave.approve', $leave));

        $log = AuditLog::latest('id')->first();
        $this->assertSame('Izin/cuti disetujui', $log->action);
        $this->assertSame('Kanaya', $log->actorName());
        $this->assertStringContainsString('Aldora', $log->detail);
    }
}