<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Revisi Work Tracker & Timeline Calendar (2026-10-20): filter checklist (banyak nilai),
 * ganti nama section, edit item dari modal kalender, dan mode kalender tahunan.
 * Aldora = work `manage`, Gepeng = work `view`.
 */
class WorkTrackerRevision20261020Test extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private Project $a;

    private Project $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
        $this->a = $this->project('Alpha');
        $this->b = $this->project('Beta');
    }

    private function project(string $name, array $o = []): Project
    {
        return Project::create(array_merge([
            'name' => $name,
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function item(Project $project, string $section, string $title, array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $project->id,
            'section' => $section,
            'item_no' => 1,
            'title' => $title,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function section(Project $project, string $name): ProjectSection
    {
        return ProjectSection::where('project_id', $project->id)->where('name', $name)->firstOrFail();
    }

    // ---- filter checklist -------------------------------------------------

    public function test_filters_accept_several_values_and_still_accept_a_single_one(): void
    {
        $this->item($this->a, 'S', 'Tugas Alpha', ['progress' => 'Done']);
        $this->item($this->b, 'S', 'Tugas Beta');
        $c = $this->project('Gamma');
        $this->item($c, 'S', 'Tugas Gamma');
        $as = $this->actingAs($this->p['aldora']);

        $as->get(route('dashboard.work.tracker.index', ['project_id' => [$this->a->id, $this->b->id]]))
            ->assertOk()->assertSee('Tugas Alpha')->assertSee('Tugas Beta')->assertDontSee('Tugas Gamma');

        // Link lama dengan nilai tunggal tetap jalan.
        $as->get(route('dashboard.work.tracker.index', ['project_id' => $this->a->id]))
            ->assertOk()->assertSee('Tugas Alpha')->assertDontSee('Tugas Beta');

        $as->get(route('dashboard.work.tracker.index', ['progress' => ['Done', 'Pending']]))
            ->assertOk()->assertSee('Tugas Alpha')->assertSee('Tugas Beta');
        $as->get(route('dashboard.work.tracker.index', ['progress' => ['Done']]))
            ->assertOk()->assertSee('Tugas Alpha')->assertDontSee('Tugas Beta');
    }

    public function test_pic_filter_with_several_people_and_garbage_values_are_ignored(): void
    {
        $this->item($this->a, 'S', 'Milik Aldora', ['pic_employee_id' => $this->p['aldora']->id]);
        $this->item($this->a, 'S', 'Milik Gepeng', ['item_no' => 2, 'pic_employee_id' => $this->p['gepeng']->id]);
        $this->item($this->a, 'S', 'Milik HRD', ['item_no' => 3, 'pic_employee_id' => $this->p['hrd']->id]);
        $as = $this->actingAs($this->p['aldora']);

        $as->get(route('dashboard.work.tracker.index', ['pic' => [$this->p['aldora']->id, $this->p['gepeng']->id]]))
            ->assertOk()->assertSee('Milik Aldora')->assertSee('Milik Gepeng')->assertDontSee('Milik HRD');

        // Nilai ngawur dibuang -> sama dengan tanpa filter (tidak error).
        $as->get(route('dashboard.work.tracker.index', ['pic' => ['abc', '-1', ''], 'progress' => ['Ngawur'], 'focus' => 'xx']))
            ->assertOk()->assertSee('Milik HRD');
    }

    // ---- ganti nama section ----------------------------------------------

    public function test_section_can_be_renamed_and_items_follow(): void
    {
        $this->item($this->a, 'CONTRACT', 'Isi kontrak');
        $this->item($this->b, 'CONTRACT', 'Kontrak Beta');
        $section = $this->section($this->a, 'CONTRACT');

        $this->actingAs($this->p['aldora'])
            ->patchJson(route('dashboard.work.tracker.sections.update', $section), ['name' => '  Legal   Docs '])
            ->assertOk()->assertJson(['ok' => true, 'name' => 'Legal Docs']);

        $this->assertSame('Legal Docs', $section->fresh()->name);
        $this->assertSame('Legal Docs', WorkItem::where('title', 'Isi kontrak')->value('section'));
        // Project lain dengan nama section yang sama tidak ikut berubah.
        $this->assertSame('CONTRACT', WorkItem::where('title', 'Kontrak Beta')->value('section'));
        $this->assertSame(1, ProjectSection::where('project_id', $this->a->id)->count());
        $this->assertTrue(AuditLog::where('action', 'Section diganti nama')->exists());
    }

    public function test_rename_rejects_duplicates_blank_and_too_long_but_allows_case_change(): void
    {
        $this->item($this->a, 'SONG', 'Lagu');
        $this->item($this->a, 'AUDIO', 'Mixing', ['item_no' => 2]);
        $song = $this->section($this->a, 'SONG');
        $as = $this->actingAs($this->p['aldora']);

        $as->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => 'audio'])->assertStatus(422);
        $as->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => '   '])->assertStatus(422);
        $as->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => str_repeat('x', 81)])->assertStatus(422);
        $as->patchJson(route('dashboard.work.tracker.sections.update', $song), [])->assertStatus(422);
        $this->assertSame('SONG', $song->fresh()->name);

        $as->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => 'Song'])->assertOk();
        $this->assertSame('Song', $song->fresh()->name);
        $this->assertSame('Song', WorkItem::where('title', 'Lagu')->value('section'));
    }

    public function test_rename_is_denied_for_view_only_and_for_locked_sections(): void
    {
        $this->item($this->a, 'SONG', 'Lagu');
        $song = $this->section($this->a, 'SONG');

        $this->actingAs($this->p['gepeng'])
            ->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => 'X'])->assertForbidden();

        $song->viewers()->sync([$this->p['hrd']->id]);
        $this->actingAs($this->p['aldora'])
            ->patchJson(route('dashboard.work.tracker.sections.update', $song), ['name' => 'X'])->assertForbidden();
        $this->assertSame('SONG', $song->fresh()->name);
    }

    public function test_rename_button_only_for_managers_and_title_is_a_textarea(): void
    {
        $item = $this->item($this->a, 'SONG', 'Judul panjang yang bisa melebar');

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee('wtStartRename(event, ', false)->assertSee('wt-title-input', false)
            ->assertSee("wtSaveField({$item->id}, 'title', this)", false);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertDontSee('wtStartRename(event, ', false)->assertSee('Judul panjang yang bisa melebar');
    }

    // ---- kalender: edit item dari modal -----------------------------------

    public function test_calendar_exposes_item_details_and_modal_for_managers_only(): void
    {
        $this->item($this->a, 'SONG', 'Final mixdown', ['due_date' => '2026-09-24', 'notes' => 'cek stem']);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.calendar'))
            ->assertOk()->assertSee('Final mixdown')->assertSee('Edit Item')->assertSee('update_url', false);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar'))
            ->assertOk()->assertSee('Detail Item')->assertDontSee('Save Item');
    }

    public function test_item_can_be_updated_from_calendar_modal(): void
    {
        $item = $this->item($this->a, 'SONG', 'Lama', ['due_date' => '2026-09-24', 'additional_pic' => 'ALL TEAM']);

        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.calendar.items.update', $item), [
            'title' => "Baru\nbaris dua",
            'due_date' => '2026-09-30',
            'pic_employee_id' => $this->p['aldora']->id,
            'additional_pic_ids' => [$this->p['gepeng']->id],
            'progress' => 'On Development',
            'priority' => 'High',
            'link' => 'https://example.com/x',
            'notes' => 'update terakhir',
        ])->assertOk()->assertJson(['ok' => true]);

        $item->refresh();
        $this->assertSame('Baru baris dua', $item->title);
        $this->assertSame('2026-09-30', $item->due_date->toDateString());
        $this->assertSame('On Development', $item->progress);
        $this->assertSame('High', $item->priority);
        $this->assertSame($this->p['aldora']->id, $item->pic_employee_id);
        $this->assertSame([$this->p['gepeng']->id], $item->additionalPics()->pluck('users.id')->all());
        $this->assertNull($item->additional_pic); // catatan teks lama "ALL TEAM" digantikan PIC nyata
        $this->assertTrue(AuditLog::where('action', 'Task diperbarui (kalender)')->exists());
    }

    public function test_calendar_update_validates_and_is_denied_for_view_only(): void
    {
        $item = $this->item($this->a, 'SONG', 'Lama');
        $url = route('dashboard.work.calendar.items.update', $item);
        $ok = ['title' => 'T', 'progress' => 'Pending', 'priority' => 'Low'];
        $as = $this->actingAs($this->p['aldora']);

        $as->patchJson($url, ['title' => '  ', 'progress' => 'Pending', 'priority' => 'Low'])->assertStatus(422);
        $as->patchJson($url, ['progress' => 'Ngawur'] + $ok)->assertStatus(422);
        $as->patchJson($url, ['link' => 'bukan-url'] + $ok)->assertStatus(422);
        // PIC 2 tanpa PIC 1, dan PIC 2 sama dengan PIC 1.
        $as->patchJson($url, ['additional_pic_ids' => [$this->p['gepeng']->id]] + $ok)->assertStatus(422);
        $as->patchJson($url, ['pic_employee_id' => $this->p['gepeng']->id, 'additional_pic_ids' => [$this->p['gepeng']->id]] + $ok)->assertStatus(422);
        $this->assertSame('Lama', $item->fresh()->title);

        $this->actingAs($this->p['gepeng'])->patchJson($url, $ok)->assertForbidden();
    }

    public function test_calendar_update_respects_restricted_sections(): void
    {
        $item = $this->item($this->a, 'RAHASIA', 'Rahasia');
        $this->section($this->a, 'RAHASIA')->viewers()->sync([$this->p['hrd']->id]);

        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.calendar.items.update', $item), [
            'title' => 'Dibobol',
            'progress' => 'Pending',
            'priority' => 'Low',
        ])->assertForbidden();
        $this->assertSame('Rahasia', $item->fresh()->title);
    }

    // ---- kalender: mode tahunan -------------------------------------------

    public function test_year_view_marks_project_duration_as_connected_days_without_gantt(): void
    {
        $this->a->update(['start_date' => '2026-06-01', 'end_date' => '2026-06-10']);
        $this->project('Kosong'); // tanpa tanggal: tidak ada jalur
        $this->project('Lama', ['start_date' => '2024-01-01', 'end_date' => '2024-02-01']);

        $res = $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk();

        $res->assertSee('Project Timeline · Tahunan', false)
            ->assertSee('data-span="' . $this->a->id . '"', false);
        $this->assertSame(10, substr_count($res->getContent(), 'data-span="' . $this->a->id . '"')); // 1–10 Jun
        $this->assertStringNotContainsString('data-span="' . Project::where('name', 'Lama')->value('id') . '"', $res->getContent());
        $res->assertDontSee('data-year-row', false)->assertDontSee('Timeline Project 2026', false);
    }

    public function test_year_view_filters_and_bad_params_do_not_break(): void
    {
        $this->a->update(['start_date' => '2026-06-01', 'end_date' => '2026-06-05']);
        $this->b->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-03']);
        $g = $this->actingAs($this->p['gepeng']);

        $g->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026, 'project' => $this->a->id]))
            ->assertOk()->assertSee('data-span="' . $this->a->id . '"', false)
            ->assertDontSee('data-span="' . $this->b->id . '"', false);

        $g->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 'abc']))->assertOk();
        $g->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 1800]))->assertOk();
    }

    public function test_year_view_hides_items_of_restricted_sections_from_progress_and_ticks(): void
    {
        $this->item($this->a, 'RAHASIA', 'Judul rahasia', ['due_date' => '2026-07-01']);
        $this->section($this->a, 'RAHASIA')->viewers()->sync([$this->p['hrd']->id]);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk()->assertDontSee('Judul rahasia');
    }
}