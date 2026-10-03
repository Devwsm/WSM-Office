<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Timeline Calendar (dashboard) versi 2026-09-28: tampilan mengikuti prototype
 * dan tanpa data hardcode — Weekly Rhythm dari office_settings, penanda
 * start/end project, filter PIC (termasuk ALL TEAM / PIC tambahan).
 * Aldora = work `manage`, Gepeng = work `view`.
 */
class TimelineCalendarTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:\App\Models\User,manajer:\App\Models\User,hrd:\App\Models\User,aldora:\App\Models\User,gepeng:\App\Models\User} */
    private array $p;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
    }

    private function item(array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'section' => 'General',
            'item_no' => 1,
            'title' => 'Approval lirik',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function rhythmPayload(array $overrides = []): array
    {
        $rows = [];
        foreach (OfficeSetting::DEFAULT_WEEKLY_RHYTHM as $dow => $d) {
            $rows[$dow] = ['focus' => $d['focus'], 'mode' => $d['mode'], 'hours' => ''];
        }

        return ['rhythm' => array_replace_recursive($rows, $overrides)];
    }

    public function test_rhythm_defaults_come_from_office_hours_when_nothing_is_stored(): void
    {
        $rhythm = OfficeSetting::current()->weeklyRhythm();

        $this->assertSame('Alignment & Planning', $rhythm[1]['focus']);
        $this->assertSame('WFO', $rhythm[1]['mode']);
        $this->assertSame('Flexible / remote', $rhythm[4]['hours']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}–\d{2}:\d{2}$/', $rhythm[1]['hours']);
        $this->assertSame([1, 2, 3, 4, 5], array_keys($rhythm));
    }

    public function test_calendar_shows_stored_rhythm_not_hardcoded_text(): void
    {
        OfficeSetting::query()->first()->update(['weekly_rhythm' => [
            1 => ['focus' => 'Fokus Kustom Senin', 'mode' => 'WFH', 'hours' => 'Bebas'],
        ]]);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))
            ->assertOk()
            ->assertSee('Fokus Kustom Senin')
            ->assertSee('WFH · Bebas', false)
            ->assertSee('Production & Decision'); // hari lain tetap default
    }

    public function test_only_work_manage_can_see_and_use_rhythm_settings(): void
    {
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))
            ->assertOk()->assertDontSee('Weekly Rhythm Settings');
        $this->actingAs($this->p['gepeng'])
            ->patch(route('dashboard.work.calendar.rhythm.update'), $this->rhythmPayload())->assertForbidden();
        $this->actingAs($this->p['gepeng'])
            ->post(route('dashboard.work.calendar.rhythm.reset'))->assertForbidden();

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.calendar'))
            ->assertOk()->assertSee('Weekly Rhythm Settings');
    }

    public function test_manager_can_save_and_reset_rhythm_and_it_is_audited(): void
    {
        $a = $this->actingAs($this->p['aldora']);

        $a->patch(route('dashboard.work.calendar.rhythm.update'), $this->rhythmPayload([
            2 => ['focus' => 'Editing Massal', 'mode' => 'Flexible', 'hours' => '10:00–18:00'],
        ]))->assertRedirect();

        $rhythm = OfficeSetting::current()->weeklyRhythm();
        $this->assertSame('Editing Massal', $rhythm[2]['focus']);
        $this->assertSame('Flexible', $rhythm[2]['mode']);
        $this->assertSame('10:00–18:00', $rhythm[2]['hours']);
        $this->assertSame('Alignment & Planning', $rhythm[1]['focus']);
        $this->assertTrue(AuditLog::query()->where('action', 'Weekly rhythm diubah')->exists());

        // Sinkron ke dashboard Owner juga (satu sumber data).
        $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertSee('Editing Massal');

        $a->post(route('dashboard.work.calendar.rhythm.reset'))->assertRedirect();
        $this->assertSame('Production & Decision', OfficeSetting::current()->weeklyRhythm()[2]['focus']);
        $this->assertNull(OfficeSetting::query()->first()->weekly_rhythm);
    }

    public function test_rhythm_update_rejects_invalid_mode_and_blank_focus_falls_back_to_default(): void
    {
        $a = $this->actingAs($this->p['aldora']);

        $a->patch(route('dashboard.work.calendar.rhythm.update'), $this->rhythmPayload([1 => ['mode' => 'Nginep']]))
            ->assertSessionHasErrors('rhythm.1.mode');

        $a->patch(route('dashboard.work.calendar.rhythm.update'), $this->rhythmPayload([3 => ['focus' => '   ']]))
            ->assertRedirect();
        $this->assertSame('Delivery & Execution', OfficeSetting::current()->weeklyRhythm()[3]['focus']);
    }

    public function test_project_start_and_end_dates_appear_as_markers(): void
    {
        $project = Project::create([
            'name' => 'Album Penanda',
            'priority' => 'High',
            'status' => 'On Development',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-25',
            'created_by' => $this->p['owner']->id,
        ]);
        $other = Project::create([
            'name' => 'Project Lain',
            'priority' => 'Low',
            'status' => 'Pending',
            'start_date' => '2026-09-12',
            'created_by' => $this->p['owner']->id,
        ]);

        $g = $this->actingAs($this->p['gepeng']);
        $g->get(route('dashboard.work.calendar'))
            ->assertOk()->assertSee('▶ Album Penanda')->assertSee('■ Album Penanda end')->assertSee('▶ Project Lain');

        $g->get(route('dashboard.work.calendar', ['project' => $project->id]))
            ->assertOk()->assertSee('▶ Album Penanda')->assertDontSee('▶ Project Lain');
    }

    public function test_pic_filter_includes_main_pic_all_team_and_additional_pic_only(): void
    {
        $gepeng = $this->p['gepeng'];
        $this->item(['title' => 'Milik Gepeng', 'due_date' => '2026-09-24', 'pic_employee_id' => $gepeng->id]);
        $this->item(['title' => 'Milik Aldora', 'due_date' => '2026-09-24', 'item_no' => 2, 'pic_employee_id' => $this->p['aldora']->id]);
        $this->item(['title' => 'Untuk Semua Tim', 'due_date' => '2026-09-24', 'item_no' => 3, 'additional_pic' => 'ALL TEAM']);
        $this->item(['title' => 'Dibantu Gepeng', 'due_date' => '2026-09-24', 'item_no' => 4, 'pic_employee_id' => $this->p['aldora']->id, 'additional_pic' => $gepeng->name]);

        $this->actingAs($gepeng)->get(route('dashboard.work.calendar', ['pic' => $gepeng->id]))
            ->assertOk()
            ->assertSee('Milik Gepeng')
            ->assertSee('Untuk Semua Tim')
            ->assertSee('Dibantu Gepeng')
            ->assertDontSee('Milik Aldora');
    }

    public function test_cells_fold_extra_items_into_plus_n_and_link_to_the_tracker(): void
    {
        $project = Project::create(['name' => 'Padat', 'priority' => 'Low', 'status' => 'Pending', 'created_by' => $this->p['owner']->id]);
        foreach (range(1, 8) as $n) {
            $this->item(['project_id' => $project->id, 'title' => "Tugas padat $n", 'due_date' => '2026-09-24', 'item_no' => $n]);
        }

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))
            ->assertOk()
            ->assertSee('+5 item') // maks 3 chip per tanggal, sisanya +N
            ->assertSee('Tugas padat 8') // tetap ada di data popup harian
            ->assertSee(route('dashboard.work.tracker.index', ['project_id' => $project->id]), false);
    }

    public function test_no_project_legend_only_shows_when_items_without_project_exist(): void
    {
        $g = $this->actingAs($this->p['gepeng']);
        $g->get(route('dashboard.work.calendar'))->assertOk()->assertDontSee('Tanpa Project');

        $this->item(['title' => 'Yatim', 'due_date' => '2026-09-24']);
        $g->get(route('dashboard.work.calendar'))->assertOk()->assertSee('Tanpa Project');
    }
}