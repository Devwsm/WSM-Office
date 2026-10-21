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

    public function test_owner_can_set_and_clear_section_viewers_and_it_is_audited(): void
    {
        $section = $this->section();
        $a = $this->actingAs($this->p['owner']);

        $a->put(route('dashboard.work.tracker.sections.viewers', $section), ['user_ids' => [$this->p['hrd']->id]])->assertRedirect();
        $this->assertSame([$this->p['hrd']->id], $section->viewers()->pluck('users.id')->all());
        $this->assertTrue(AuditLog::query()->where('action', 'Visibility section diubah')->exists());

        $a->put(route('dashboard.work.tracker.sections.viewers', $section), [])->assertRedirect();
        $this->assertSame([], $section->viewers()->pluck('users.id')->all());
    }

    public function test_developer_can_set_section_viewers(): void
    {
        $section = $this->section();
        $dev = $this->makeDeveloper($this->p['owner']);

        $this->actingAs($dev)
            ->put(route('dashboard.work.tracker.sections.viewers', $section), ['user_ids' => [$this->p['gepeng']->id]])
            ->assertRedirect();
        $this->assertSame([$this->p['gepeng']->id], $section->viewers()->pluck('users.id')->all());
    }

    public function test_work_manage_who_is_not_owner_or_developer_cannot_set_section_viewers(): void
    {
        $section = $this->section();
        $section->viewers()->sync([$this->p['hrd']->id]);

        // Aldora punya work=manage (boleh kelola task/section), tapi bukan Owner/Developer.
        $this->actingAs($this->p['aldora'])
            ->put(route('dashboard.work.tracker.sections.viewers', $section), ['user_ids' => [$this->p['aldora']->id]])
            ->assertForbidden();
        $this->actingAs($this->p['aldora'])
            ->put(route('dashboard.work.tracker.sections.viewers', $section), [])
            ->assertForbidden();

        // Tidak ada yang berubah dan tidak ada jejak audit perubahan.
        $this->assertSame([$this->p['hrd']->id], $section->viewers()->pluck('users.id')->all());
        $this->assertFalse(AuditLog::query()->where('action', 'Visibility section diubah')->exists());
    }

    public function test_visibility_button_is_only_shown_to_owner_and_developer(): void
    {
        $this->item(['title' => 'Item pemicu section']);
        $dev = $this->makeDeveloper($this->p['owner']);

        foreach ([$this->p['owner'], $dev] as $user) {
            $this->actingAs($user)->get(route('dashboard.work.tracker.index'))
                ->assertOk()->assertSee('Atur siapa yang bisa melihat section ini')->assertSee('Siapa yang bisa lihat?');
        }

        // work=manage biasa tetap bisa mengelola section (warna/urutan), tapi tidak melihat kontrol visibility.
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee('Ubah warna section')
            ->assertDontSee('Atur siapa yang bisa melihat section ini')->assertDontSee('Siapa yang bisa lihat?');
    }

    public function test_tracker_marks_project_and_pic_dropdowns_as_searchable(): void
    {
        $this->item(['title' => 'Item pemicu section']);

        $html = $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))->assertOk()->getContent();

        // Filter Project & PIC, form Tambah Task (Project, PIC 1-3), dan PIC per baris memakai dropdown pencarian.
        $this->assertStringContainsString('wtSsPanel', $html);
        $this->assertStringContainsString('data-search-placeholder="Cari project…"', $html);
        $this->assertStringContainsString('data-search-placeholder="Cari pic…"', $html);
        $this->assertStringContainsString('data-search-placeholder="Cari nama PIC…"', $html);
        $this->assertGreaterThanOrEqual(7, substr_count($html, 'data-searchable'));

        // 2026-10-20: SEMUA dropdown Work Tracker = dropdown pencarian; keempat filter berupa checklist (multiple).
        foreach (['project_id', 'pic', 'progress', 'focus'] as $filter) {
            $this->assertMatchesRegularExpression('/<select name="' . $filter . '\[\]" multiple[^>]*data-searchable/', $html);
        }
        foreach (['progress', 'focus', 'section', 'priority'] as $word) {
            $this->assertStringContainsString('data-search-placeholder="Cari ' . $word . '…"', $html);
        }
    }

    public function test_view_only_user_cannot_change_section_viewers(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->put(route('dashboard.work.tracker.sections.viewers', $this->section()), ['user_ids' => [$this->p['gepeng']->id]])
            ->assertForbidden();
    }

    public function test_restricted_section_is_locked_for_non_viewers_but_open_to_owner_developer_viewer_and_pic(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kontrak']);
        $this->item(['title' => 'Tugas pribadi Gepeng', 'item_no' => 2, 'pic_employee_id' => $this->p['gepeng']->id]);
        $this->item(['title' => 'Terbuka', 'section' => 'OPEN', 'item_no' => 1]);
        $dev = $this->makeDeveloper($this->p['owner']);
        $noAccess = 'Tidak ada akses ke item di dalam section ini';

        // Akses Manage saja TIDAK cukup: section tampil terkunci, isinya (judul item) tidak ikut terkirim.
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee($noAccess)->assertSee('wt-locked', false)->assertSee('Terkunci')->assertSee('Terbuka')
            ->assertDontSee('Rahasia kontrak')->assertDontSee('Tugas pribadi Gepeng');

        // PIC item di section itu otomatis viewer: membuka SELURUH section, bukan cuma task miliknya.
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee('Rahasia kontrak')->assertSee('Tugas pribadi Gepeng')->assertDontSee('wt-locked', false);

        // Owner dan Developer selalu bisa membuka (pengecualian), walau tidak dipilih sebagai viewer.
        foreach ([$this->p['owner'], $dev] as $user) {
            $this->actingAs($user)->get(route('dashboard.work.tracker.index'))
                ->assertOk()->assertSee('Rahasia kontrak')->assertDontSee('wt-locked', false);
        }
        $this->actingAs($this->p['owner'])->get(route('dashboard.work.tracker.index'))->assertSee('1 orang');
    }

    public function test_additional_pic_also_counts_as_automatic_viewer(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kontrak'])->additionalPics()->sync([$this->p['aldora']->id]);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSee('Rahasia kontrak')->assertDontSee('wt-locked', false);
    }

    public function test_locked_section_is_not_offered_in_add_task_form_and_hidden_when_filtering(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kontrak']);
        $this->item(['title' => 'Terbuka', 'section' => 'OPEN', 'item_no' => 1]);

        $html = $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))->assertOk()->getContent();
        // Daftar section untuk form Tambah Task (JSON per project) tidak memuat section terkunci.
        $this->assertStringContainsString('const wtSections = {"' . $this->project->id . '":["OPEN"]}', $html);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index', ['progress' => 'Pending']))
            ->assertOk()->assertDontSee('Terkunci');
    }

    public function test_write_endpoints_reject_work_manage_users_without_access_to_a_locked_section(): void
    {
        $section = $this->section();
        $section->viewers()->sync([$this->p['hrd']->id]);
        $item = $this->item(['title' => 'Rahasia kontrak']);
        $a = $this->actingAs($this->p['aldora']);
        $payload = ['project_id' => $this->project->id, 'section' => 'CONTRACT', 'title' => 'Diubah', 'progress' => 'Done', 'priority' => 'High'];

        $a->patchJson(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Done'])->assertForbidden();
        $a->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => 'Diubah'])->assertForbidden();
        $a->patchJson(route('dashboard.work.tracker.items.note', $item), ['notes' => 'diubah'])->assertForbidden();
        $a->patch(route('dashboard.work.tracker.items.update', $item), $payload)->assertForbidden();
        $a->post(route('dashboard.work.tracker.items.store'), $payload)->assertForbidden();
        $a->delete(route('dashboard.work.tracker.items.destroy', $item))->assertForbidden();
        $a->patch(route('dashboard.work.tracker.sections.update', $section), ['color' => '#112233'])->assertForbidden();
        $a->patch(route('dashboard.work.tracker.sections.move', $section), ['direction' => 'down'])->assertForbidden();
        $a->delete(route('dashboard.work.tracker.sections.destroy', $section), ['confirm_name' => 'CONTRACT'])->assertForbidden();
        $a->delete(route('dashboard.work.tracker.projects.destroy', $this->project))->assertForbidden();
        $a->patchJson(route('dashboard.work.calendar.items.move', $item), ['due_date' => '2026-10-30'])->assertForbidden();

        // Memindahkan item SENDIRI ke section terkunci juga ditolak.
        $own = $this->item(['title' => 'Item terbuka', 'section' => 'OPEN', 'item_no' => 5, 'pic_employee_id' => $this->p['aldora']->id]);
        $a->patch(route('dashboard.work.tracker.items.update', $own), array_merge($payload, ['title' => 'Item terbuka']))->assertForbidden();

        $item->refresh();
        $this->assertSame('Rahasia kontrak', $item->title);
        $this->assertSame('Pending', $item->progress);
        $this->assertSame('2026-09-24', $item->due_date->toDateString());
        $this->assertNull($item->notes);
        $this->assertSame('OPEN', $own->fresh()->section);
        $this->assertSame(2, WorkItem::query()->where('project_id', $this->project->id)->count());
        $this->assertNotNull($this->project->fresh());
        $this->assertTrue(ProjectSection::query()->whereKey($section->id)->exists());
    }

    public function test_owner_developer_and_viewers_can_still_change_a_locked_section(): void
    {
        $section = $this->section();
        $section->viewers()->sync([$this->p['aldora']->id]);
        $item = $this->item(['title' => 'Rahasia kontrak']);
        $dev = $this->makeDeveloper($this->p['owner']);

        $this->actingAs($this->p['owner'])->patchJson(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Done'])->assertOk();
        $this->actingAs($dev)->patchJson(route('dashboard.work.tracker.items.note', $item), ['notes' => 'dari developer'])->assertOk();
        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => 'Judul viewer'])->assertOk();

        $item->refresh();
        $this->assertSame('Done', $item->progress);
        $this->assertSame('dari developer', $item->notes);
        $this->assertSame('Judul viewer', $item->title);
    }

    public function test_pic_is_added_to_viewers_automatically_when_owner_restricts_a_section(): void
    {
        $section = $this->section();
        $this->item(['title' => 'Tugas Gepeng', 'pic_employee_id' => $this->p['gepeng']->id]);
        $this->item(['title' => 'Tugas bersama', 'item_no' => 2])->additionalPics()->sync([$this->p['aldora']->id]);

        // Checkbox PIC disabled di UI (tidak terkirim); server tetap memasukkan semua PIC.
        $this->actingAs($this->p['owner'])
            ->put(route('dashboard.work.tracker.sections.viewers', $section), ['access' => 'selected', 'user_ids' => [$this->p['hrd']->id]])
            ->assertRedirect();
        $ids = $section->viewers()->pluck('users.id')->all();
        sort($ids);
        $expected = [$this->p['hrd']->id, $this->p['gepeng']->id, $this->p['aldora']->id];
        sort($expected);
        $this->assertSame($expected, $ids);

        // Pilihan "Terbuka untuk semua orang" mengosongkan daftar walau ada PIC.
        $this->actingAs($this->p['owner'])
            ->put(route('dashboard.work.tracker.sections.viewers', $section), ['access' => 'all'])
            ->assertRedirect();
        $this->assertSame([], $section->viewers()->pluck('users.id')->all());
    }

    public function test_restricting_with_nobody_selected_and_no_pic_is_rejected_instead_of_silently_opening(): void
    {
        $section = $this->section();
        $section->viewers()->sync([$this->p['hrd']->id]);

        $this->actingAs($this->p['owner'])
            ->put(route('dashboard.work.tracker.sections.viewers', $section), ['access' => 'selected'])
            ->assertSessionHasErrors('user_ids');
        $this->assertSame([$this->p['hrd']->id], $section->viewers()->pluck('users.id')->all());
    }

    public function test_visibility_modal_marks_pic_users_for_the_owner(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Tugas Gepeng', 'pic_employee_id' => $this->p['gepeng']->id]);

        $html = $this->actingAs($this->p['owner'])->get(route('dashboard.work.tracker.index'))->assertOk()->getContent();
        $this->assertStringContainsString('viewersModal.pics.includes(' . $this->p['gepeng']->id . ')', $html);
        $this->assertStringContainsString('Terbuka untuk semua orang', $html);
        $this->assertStringContainsString('Hanya orang tertentu', $html);
        // Payload section: PIC Gepeng terdaftar sebagai PIC, mode awal "selected" karena sudah dibatasi.
        $q = '(?:\\\\u0022|&quot;|")';
        $this->assertMatchesRegularExpression('/pics' . $q . ':\[' . $this->p['gepeng']->id . '\]/', $html);
        $this->assertMatchesRegularExpression('/access' . $q . ':' . $q . 'selected/', $html);
    }

    public function test_dashboard_timeline_calendar_hides_locked_section_items(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kalender', 'due_date' => '2026-09-24']);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.calendar', ['month' => '2026-09']))
            ->assertOk()->assertDontSee('Rahasia kalender');
        $this->actingAs($this->p['owner'])->get(route('dashboard.work.calendar', ['month' => '2026-09']))
            ->assertOk()->assertSee('Rahasia kalender');
    }

    public function test_project_excel_and_export_center_skip_locked_section_items(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia ekspor']);
        $this->item(['title' => 'Terbuka ekspor', 'section' => 'OPEN', 'item_no' => 1]);

        $titles = fn($rows) => collect($rows)->flatten()->filter(fn($v) => is_string($v))->implode('|');

        $forManager = $titles((new \App\Exports\ProjectTrackerSheet($this->project, $this->p['aldora']))->array());
        $this->assertStringContainsString('Terbuka ekspor', $forManager);
        $this->assertStringNotContainsString('Rahasia ekspor', $forManager);

        $forOwner = $titles((new \App\Exports\ProjectTrackerSheet($this->project, $this->p['owner']))->array());
        $this->assertStringContainsString('Rahasia ekspor', $forOwner);

        $this->assertSame(1, (new \App\Exports\WorkItemExport($this->project->id, $this->p['aldora']))->rows()->count());
        $this->assertSame(2, (new \App\Exports\WorkItemExport($this->project->id, $this->p['owner']))->rows()->count());
    }

    public function test_sheet_sync_cannot_touch_or_create_items_in_a_locked_section(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $locked = $this->item(['title' => 'Rahasia sinkron']);
        $plan = ['rows' => [
            ['action' => 'create', 'apply' => ['section' => 'CONTRACT', 'title' => 'Sisipan', 'item_no' => '9'], 'pics' => null],
            ['action' => 'update', 'id' => $locked->id, 'apply' => ['title' => 'Ditimpa'], 'pics' => null],
        ]];

        $result = (new \App\Support\ProjectSheet\SheetSync($this->project, $this->p['aldora']))->apply($plan, $this->p['aldora']);
        $this->assertSame(['created' => 0, 'updated' => 0, 'skipped' => 2], $result);
        $this->assertSame('Rahasia sinkron', $locked->fresh()->title);

        // Owner tidak dibatasi.
        $result = (new \App\Support\ProjectSheet\SheetSync($this->project, $this->p['owner']))->apply($plan, $this->p['owner']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('Ditimpa', $locked->fresh()->title);
    }

    public function test_universal_search_box_and_row_search_text_are_rendered_without_locked_items(): void
    {
        $this->section()->viewers()->sync([$this->p['hrd']->id]);
        $this->item(['title' => 'Rahasia kontrak', 'notes' => 'catatan rahasia']);
        $this->item(['title' => 'Revisi lagu', 'section' => 'OPEN', 'item_no' => 1, 'notes' => 'cek mixing', 'pic_employee_id' => $this->p['gepeng']->id]);

        $html = $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="wtSearch"', $html);
        $this->assertStringContainsString('id="wtSearchEmpty"', $html);
        // Bahan cari memuat judul, catatan, section, project, dan nama PIC.
        $this->assertMatchesRegularExpression('/data-search="[^"]*revisi lagu[^"]*cek mixing[^"]*open[^"]*album q4[^"]*"/', $html);
        $this->assertStringContainsString(mb_strtolower($this->p['gepeng']->name), $html);
        // Item section terkunci tidak punya baris, jadi tidak mungkin ketemu lewat pencarian.
        $this->assertStringNotContainsString('catatan rahasia', $html);
        $this->assertStringNotContainsString('Rahasia kontrak', $html);
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
        $this->assertSame([1, 2, 3, 4, 5], array_keys(OfficeSetting::current()->weeklyRhythm())); // App Mode tetap 5 hari kerja

        // Dashboard Owner (Executive People Overview) 7 hari, sama dengan header kalender.
        $ownerHtml = $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Event &amp; Show', $ownerHtml);
        $this->assertStringContainsString('Minggu', $ownerHtml);
        $this->assertStringContainsString('Sabtu', $ownerHtml);

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

        $this->assertMatchesRegularExpression('/\+2\s+item/', $html); // toleran terhadap pemformat Blade yang memecah baris
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