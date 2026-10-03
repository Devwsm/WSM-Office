<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Revisi Work Control & sidebar (2026-10-03): edit satuan di tracker, filter
 * Focus, visibility section per orang, ritme di header kalender (+ Sabtu/Minggu
 * event), popup harian, drag & drop pindah tanggal, dan grup sidebar CEO DASHBOARD.
 * Aldora = work `manage`, Gepeng = work `view`.
 */
class WorkControlRevisionTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
        $this->project = Project::create(['name' => 'Album Q4', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
    }

    private function item(array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $this->project->id,
            'section' => 'CONTRACT',
            'item_no' => 1,
            'title' => 'Item uji',
            'due_date' => '2026-09-24',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function section(string $name = 'CONTRACT'): ProjectSection
    {
        ProjectSection::ensure($this->project->id, $name);

        return ProjectSection::query()->where('project_id', $this->project->id)->where('name', $name)->firstOrFail();
    }

    // --- Edit satuan di tracker ---

    public function test_manage_can_edit_single_fields_inline(): void
    {
        $item = $this->item();
        $a = $this->actingAs($this->p['aldora']);

        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => 'Judul baru'])->assertOk();
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'due_date', 'value' => '2026-10-10'])->assertOk();
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'pic_employee_id', 'value' => (string) $this->p['gepeng']->id])->assertOk();
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'link', 'value' => 'https://example.com/x'])->assertOk();

        $item->refresh();
        $this->assertSame('Judul baru', $item->title);
        $this->assertSame('2026-10-10', $item->due_date->toDateString());
        $this->assertSame($this->p['gepeng']->id, $item->pic_employee_id);
        $this->assertSame('https://example.com/x', $item->link);
        $this->assertSame('Pending', $item->progress); // kolom lain tidak tersentuh
    }

    public function test_inline_edit_validates_and_is_manage_only(): void
    {
        $item = $this->item();

        $this->actingAs($this->p['gepeng'])
            ->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => 'Nakal'])
            ->assertForbidden();

        $a = $this->actingAs($this->p['aldora']);
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => '   '])->assertStatus(422);
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'link', 'value' => 'bukan-url'])->assertStatus(422);
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'progress', 'value' => 'Done'])->assertStatus(422);

        $this->assertSame('Item uji', $item->fresh()->title);
        $this->assertSame('Pending', $item->fresh()->progress);
    }

    public function test_new_main_pic_is_removed_from_additional_pics(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['aldora'])
            ->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'pic_employee_id', 'value' => (string) $this->p['gepeng']->id])
            ->assertOk();

        $this->assertSame([], $item->additionalPics()->pluck('users.id')->all());
    }

    // --- Filter focus ---

    public function test_tracker_filters_by_focus(): void
    {
        $this->item(['title' => 'Mepet hari ini', 'due_date' => '2026-09-21', 'item_no' => 1]); // WORKDAY = 2026-09-21
        $this->item(['title' => 'Sudah lewat', 'due_date' => '2026-09-01', 'item_no' => 2]);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index', ['focus' => 'KELEWAT']))
            ->assertOk()
            ->assertSee('Sudah lewat')
            ->assertDontSee('Mepet hari ini');

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index', ['focus' => 'NGAWUR']))
            ->assertOk()->assertSee('Sudah lewat')->assertSee('Mepet hari ini'); // nilai tak dikenal diabaikan
    }

    // --- Visibility section per orang ---

    public function test_manage_can_set_and_clear_section_viewers_and_it_is_audited(): void
    {
        $section = $this->section();
        $a = $this->actingAs($this->p['aldora']);

        $a->put(route('dashboard.work.tracker.sections.viewers', $section), ['user_ids' => [$this->p['hrd']->id]])->assertRedirect();
        $this->assertSame([$this->p['hrd']->id], $section->viewers()->pluck('users.id')->all());
        $this->assertTrue(AuditLog::query()->where('action', 'Visibility section diubah')->exists());

        $a->put(route('dashboard.work.tracker.sections.viewers', $section), [])->assertRedirect();
        $this->assertSame([], $section->viewers()->pluck('users.id')->all());
    }

    public function test_view_only_user_cannot_change_section_viewers(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->put(route('dashboard.work.tracker.sections.viewers', $this->section()), ['user_ids' => [$this->p['gepeng']->id]])
            ->assertForbidden();
    }

    public function test_restricted_section_is_hidden_from_non_viewers_but_not_from_owner_viewer_or_pic(): void
    {
        $section = $this->section();
        $section->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kontrak']);
        $this->item(['title' => 'Tugas pribadi Gepeng', 'item_no' => 2, 'pic_employee_id' => $this->p['gepeng']->id]);
        $this->item(['title' => 'Terbuka', 'section' => 'OPEN', 'item_no' => 1]);

        // Non-viewer (work view): section & itemnya hilang, section lain tetap, task miliknya tetap terlihat.
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertDontSee('Rahasia kontrak')->assertSee('Tugas pribadi Gepeng')->assertSee('Terbuka');

        // Manager work=manage yang bukan viewer pun tidak melihatnya.
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertDontSee('Rahasia kontrak');

        // Owner selalu lihat semuanya dan melihat label terbatas.
        $this->actingAs($this->p['owner'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee('Rahasia kontrak')->assertSee('1 orang');
    }

    public function test_restricted_section_items_are_hidden_from_employee_shared_calendar(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kalender', 'due_date' => '2026-09-24']);

        $this->actingAs($this->p['gepeng'])->get(route('employee.workTracker.calendar'))
            ->assertOk()->assertDontSee('Rahasia kalender');
        $this->actingAs($this->p['hrd'])->get(route('employee.workTracker.calendar'))
            ->assertOk()->assertSee('Rahasia kalender');
    }

    // --- Kalender: ritme header, event weekend, popup, drag & drop ---

    public function test_weekend_event_rhythm_defaults_and_is_in_the_header(): void
    {
        $rhythm = OfficeSetting::current()->calendarRhythm();
        $this->assertSame([0, 1, 2, 3, 4, 5, 6], array_keys($rhythm));
        $this->assertSame('Event', $rhythm[6]['mode']);
        $this->assertSame('Event', $rhythm[0]['mode']);
        $this->assertSame([1, 2, 3, 4, 5], array_keys(OfficeSetting::current()->weeklyRhythm())); // dashboard Owner tetap 5 hari

        $html = $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))->assertOk()->getContent();
        $this->assertStringContainsString('Event &amp; Show', $html);
        // Ritme tidak lagi diulang di setiap tanggal: 1 kali di header, bukan ~42 kali.
        $this->assertLessThan(5, substr_count($html, 'Alignment &amp; Planning'));
    }

    public function test_weekend_rhythm_can_be_saved_and_keeps_weekday_values(): void
    {
        $rows = [];
        foreach (OfficeSetting::DEFAULT_WEEKLY_RHYTHM as $dow => $d) {
            $rows[$dow] = ['focus' => $d['focus'], 'mode' => $d['mode'], 'hours' => ''];
        }
        $rows[6] = ['focus' => 'Showcase Sabtu', 'mode' => 'Event', 'hours' => '19:00–23:00'];
        $rows[0] = ['focus' => 'Bongkar Panggung', 'mode' => 'Flexible', 'hours' => ''];

        $this->actingAs($this->p['aldora'])->patch(route('dashboard.work.calendar.rhythm.update'), ['rhythm' => $rows])->assertRedirect();

        $rhythm = OfficeSetting::current()->calendarRhythm();
        $this->assertSame('Showcase Sabtu', $rhythm[6]['focus']);
        $this->assertSame('19:00–23:00', $rhythm[6]['hours']);
        $this->assertSame('Bongkar Panggung', $rhythm[0]['focus']);
        $this->assertSame('Alignment & Planning', $rhythm[1]['focus']);
    }

    public function test_cell_shows_three_chips_and_popup_data_has_every_item(): void
    {
        foreach (range(1, 5) as $n) {
            $this->item(['title' => "Padat $n", 'due_date' => '2026-09-24', 'item_no' => $n]);
        }

        $html = $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))->assertOk()->getContent();

        $this->assertStringContainsString('+2 item', $html);
        $this->assertStringContainsString('wsmCalendar(', $html);
        $this->assertStringContainsString('Padat 5', $html); // ada di data popup harian
        $this->assertSame(3, substr_count($html, 'data-item-id="'));
    }

    public function test_manage_can_move_item_date_with_drag_endpoint_and_it_is_audited(): void
    {
        $item = $this->item(['due_date' => '2026-09-24']);

        $this->actingAs($this->p['aldora'])
            ->patchJson(route('dashboard.work.calendar.items.move', $item), ['due_date' => '2026-09-30'])
            ->assertOk()->assertJson(['ok' => true, 'due_date' => '2026-09-30']);

        $this->assertSame('2026-09-30', $item->fresh()->due_date->toDateString());
        $this->assertTrue(AuditLog::query()->where('action', 'Deadline task dipindah')->exists());
    }

    public function test_drag_endpoint_rejects_view_only_and_bad_dates(): void
    {
        $item = $this->item(['due_date' => '2026-09-24']);

        $this->actingAs($this->p['gepeng'])
            ->patchJson(route('dashboard.work.calendar.items.move', $item), ['due_date' => '2026-09-30'])->assertForbidden();

        $this->actingAs($this->p['aldora'])
            ->patchJson(route('dashboard.work.calendar.items.move', $item), ['due_date' => 'besok'])->assertStatus(422);

        $this->assertSame('2026-09-24', $item->fresh()->due_date->toDateString());
    }

    public function test_view_only_user_gets_no_draggable_setup_but_still_gets_popup(): void
    {
        $this->item(['due_date' => '2026-09-24']);
        $html = $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))->assertOk()->getContent();

        $this->assertStringContainsString("wsmCalendar(", $html);
        $this->assertMatchesRegularExpression('/wsmCalendar\(.*?,\s*false,/s', $html); // canManage = false -> drag tidak diaktifkan
    }

    // --- Sidebar ---

    public function test_sidebar_groups_karyawan_and_access_under_ceo_dashboard(): void
    {
        $html = $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('· PEOPLE</p>', $html);
        $ceo = strpos($html, '1 · CEO DASHBOARD');
        $karyawan = strpos($html, 'Karyawan &amp; Access');
        $work = strpos($html, 'WORK CONTROL');

        $this->assertNotFalse($ceo);
        $this->assertNotFalse($karyawan);
        $this->assertTrue($ceo < $karyawan && $karyawan < $work, 'Karyawan & Access harus di dalam grup CEO DASHBOARD, sebelum WORK CONTROL.');
        $this->assertSame(1, substr_count($html, 'Karyawan &amp; Access'));
    }
}