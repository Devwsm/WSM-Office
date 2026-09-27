<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * README Bab 2.1 #40 — Team Overview manajer, dipecah 2 halaman:
 * TeamAttendanceController (manajer.team.attendance) dan
 * TeamWorkController (manajer.team.work). Keduanya `role:manajer,owner`
 * doang (bukan module-access seperti kebanyakan halaman lain), dan
 * scoping tim pakai User::visibleAttendanceUserIds() yang sudah dites
 * AttendanceRecapTest — di sini fokus ke bagian barunya saja.
 */
class TeamOverviewTest extends TestCase
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

    // ---- Gate: cuma manajer & owner --------------------------------

    public function test_only_manajer_and_owner_can_open_team_overview_pages(): void
    {
        $this->actingAs($this->p['manajer'])->get(route('manajer.team.attendance'))->assertOk();
        $this->actingAs($this->p['manajer'])->get(route('manajer.team.work'))->assertOk();
        $this->actingAs($this->p['owner'])->get(route('manajer.team.attendance'))->assertOk();
        $this->actingAs($this->p['owner'])->get(route('manajer.team.work'))->assertOk();

        $this->actingAs($this->p['hrd'])->get(route('manajer.team.attendance'))->assertForbidden();
        $this->actingAs($this->p['hrd'])->get(route('manajer.team.work'))->assertForbidden();
        $this->actingAs($this->p['aldora'])->get(route('manajer.team.attendance'))->assertForbidden();
        $this->actingAs($this->p['gepeng'])->get(route('manajer.team.work'))->assertForbidden();
    }

    // ---- Scope tim: sama pola dengan Rekap Absensi -------------------

    public function test_manajer_sees_own_team_only_owner_sees_everyone(): void
    {
        $ids = fn($response) => $response->viewData('rows')->pluck('user.id')->sort()->values()->all();

        $all = collect($this->p)->pluck('id')->sort()->values()->all();
        $team = collect([$this->p['manajer'], $this->p['aldora'], $this->p['gepeng']])->pluck('id')->sort()->values()->all();

        $this->assertSame($team, $ids($this->actingAs($this->p['manajer'])->get(route('manajer.team.attendance'))));
        $this->assertSame($all, $ids($this->actingAs($this->p['owner'])->get(route('manajer.team.attendance'))));

        $this->assertSame($team, $ids($this->actingAs($this->p['manajer'])->get(route('manajer.team.work'))));
        $this->assertSame($all, $ids($this->actingAs($this->p['owner'])->get(route('manajer.team.work'))));
    }

    // ---- Absensi Tim --------------------------------------------------

    public function test_attendance_overview_summary_and_badges(): void
    {
        Attendance::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-21',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-21 09:00:00',
        ]);

        LeaveRequest::create([
            'user_id' => $this->p['gepeng']->id,
            'type' => 'izin_sakit',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-21',
            'work_days' => 1,
            'reason' => 'Demam',
            'status' => 'disetujui',
        ]);

        OvertimeRequest::create([
            'user_id' => $this->p['manajer']->id,
            'date' => '2026-09-21',
            'reason' => 'Deadline klien',
            'status' => 'disetujui',
        ]);

        // Kanaya (manajer) sendiri belum absen -> "belum absen" walau lembur disetujui.
        $response = $this->actingAs($this->p['owner'])->get(route('manajer.team.attendance'))->assertOk();

        $response->assertViewHas('summary', [
            'total' => 5,
            'sudahAbsen' => 1,
            'belumAbsen' => 3,
            'cuti' => 1,
            'lembur' => 1,
        ]);
    }

    // ---- Progress Kerja Tim --------------------------------------------

    public function test_work_overview_flags_overdue_and_sorts_by_it(): void
    {
        WorkItem::create([
            'title' => 'Task lewat due date',
            'section' => 'Testing',
            'pic_employee_id' => $this->p['gepeng']->id,
            'due_date' => '2026-09-20',
            'progress' => 'Pending',
            'priority' => 'High',
            'created_by' => $this->p['manajer']->id,
        ]);

        WorkItem::create([
            'title' => 'Task masih aman',
            'section' => 'Testing',
            'pic_employee_id' => $this->p['aldora']->id,
            'due_date' => '2026-09-25',
            'progress' => 'On Development',
            'priority' => 'Medium',
            'created_by' => $this->p['manajer']->id,
        ]);

        WorkItem::create([
            'title' => 'Task sudah selesai, tidak boleh ikut ke-count',
            'section' => 'Testing',
            'pic_employee_id' => $this->p['aldora']->id,
            'due_date' => '2026-09-18',
            'progress' => 'Done',
            'priority' => 'Low',
            'created_by' => $this->p['manajer']->id,
        ]);

        $response = $this->actingAs($this->p['manajer'])->get(route('manajer.team.work'))->assertOk();

        $rows = $response->viewData('rows')->keyBy(fn(array $row) => $row['user']->id);

        $this->assertSame(1, $rows[$this->p['gepeng']->id]['overdueCount']);
        $this->assertSame(1, $rows[$this->p['gepeng']->id]['openCount']);
        $this->assertSame(0, $rows[$this->p['aldora']->id]['overdueCount']);
        $this->assertSame(1, $rows[$this->p['aldora']->id]['openCount']); // task Done tidak dihitung

        // Gepeng (overdue) harus di atas Aldora (tidak overdue).
        $order = $response->viewData('rows')->pluck('user.id')->values()->all();
        $this->assertSame(
            array_search($this->p['gepeng']->id, $order, true) < array_search($this->p['aldora']->id, $order, true),
            true,
        );
    }
}