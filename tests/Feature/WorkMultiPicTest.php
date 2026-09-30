<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * PIC lebih dari satu orang per task (2026-09-30): PIC 1 = pic_employee_id,
 * PIC 2 & PIC 3 = tabel work_item_additional_pics. Semua PIC melihat task itu
 * di daftar kerjanya sendiri. Aldora = work `manage`, Gepeng = work `view`.
 */
class WorkMultiPicTest extends TestCase
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
        $this->project = Project::create([
            'name' => 'Album Q3',
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ]);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'project_id' => $this->project->id,
            'section' => 'CONTRACT',
            'title' => 'Contract Rossa',
            'due_date' => '2026-09-25',
            'pic_employee_id' => $this->p['aldora']->id,
            'progress' => 'Pending',
            'priority' => 'Medium',
        ], $o);
    }

    private function item(array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $this->project->id,
            'section' => 'CONTRACT',
            'item_no' => 1,
            'title' => 'Contract Rossa',
            'due_date' => '2026-09-25',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function store(array $o = [])
    {
        return $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.items.store'), $this->payload($o));
    }

    // ---- Simpan & validasi -----------------------------------------

    public function test_task_can_have_three_pics_and_each_is_saved(): void
    {
        $this->store(['additional_pic_ids' => [$this->p['gepeng']->id, $this->p['manajer']->id]])
            ->assertSessionHasNoErrors();

        $item = WorkItem::firstOrFail();

        $this->assertSame($this->p['aldora']->id, $item->pic_employee_id);
        $this->assertSame(
            [$this->p['gepeng']->id, $this->p['manajer']->id],
            $item->additionalPics()->pluck('users.id')->all()
        );
        $this->assertSame('Aldora · Gepeng · Kanaya', $item->fresh(['pic', 'additionalPics'])->picLabel());
    }

    public function test_empty_pic_selects_are_ignored(): void
    {
        $this->store(['additional_pic_ids' => ['', '']])->assertSessionHasNoErrors();

        $this->assertSame(0, WorkItem::firstOrFail()->additionalPics()->count());
    }

    public function test_more_than_three_pics_is_rejected(): void
    {
        $this->store(['additional_pic_ids' => [$this->p['gepeng']->id, $this->p['manajer']->id, $this->p['hrd']->id]])
            ->assertSessionHasErrors('additional_pic_ids');

        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_additional_pic_cannot_repeat_or_equal_the_main_pic(): void
    {
        $this->store(['additional_pic_ids' => [$this->p['aldora']->id]])->assertSessionHasErrors('additional_pic_ids');
        $this->store(['additional_pic_ids' => [$this->p['gepeng']->id, $this->p['gepeng']->id]])->assertSessionHasErrors('additional_pic_ids.1');
        $this->store(['additional_pic_ids' => [9999]])->assertSessionHasErrors('additional_pic_ids.0');

        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_additional_pics_need_a_main_pic(): void
    {
        $this->store(['pic_employee_id' => '', 'additional_pic_ids' => [$this->p['gepeng']->id]])
            ->assertSessionHasErrors('additional_pic_ids');

        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_update_replaces_pics_and_clearing_removes_them(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);
        $aldora = $this->actingAs($this->p['aldora']);
        $url = route('dashboard.work.tracker.items.update', $item);

        $aldora->patch($url, $this->payload(['additional_pic_ids' => [$this->p['manajer']->id]]))->assertSessionHasNoErrors();
        $this->assertSame([$this->p['manajer']->id], $item->additionalPics()->pluck('users.id')->all());

        $aldora->patch($url, $this->payload(['additional_pic_ids' => ['', '']]))->assertSessionHasNoErrors();
        $this->assertSame(0, $item->additionalPics()->count());
    }

    public function test_update_without_the_field_keeps_existing_additional_pics(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['aldora'])
            ->patch(route('dashboard.work.tracker.items.update', $item), $this->payload(['title' => 'Judul baru']))
            ->assertSessionHasNoErrors();

        $this->assertSame([$this->p['gepeng']->id], $item->additionalPics()->pluck('users.id')->all());
    }

    public function test_real_additional_pics_replace_the_old_free_text_note(): void
    {
        $this->store(['additional_pic' => 'ALL TEAM', 'additional_pic_ids' => [$this->p['gepeng']->id]])->assertSessionHasNoErrors();

        $this->assertNull(WorkItem::firstOrFail()->additional_pic);
    }

    public function test_view_only_user_cannot_change_pics(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->post(route('dashboard.work.tracker.items.store'), $this->payload(['additional_pic_ids' => [$this->p['gepeng']->id]]))
            ->assertForbidden();
    }

    public function test_deleting_a_task_removes_its_pic_rows(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['aldora'])->delete(route('dashboard.work.tracker.items.destroy', $item));

        $this->assertDatabaseCount('work_item_additional_pics', 0);
    }

    // ---- Task muncul di daftar kerja SEMUA PIC ---------------------

    public function test_every_pic_sees_the_task_on_their_home_work_tracker(): void
    {
        $item = $this->item(['title' => 'Kerja Bareng Trio', 'pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id, $this->p['manajer']->id]);

        foreach (['aldora', 'gepeng', 'manajer'] as $who) {
            $this->actingAs($this->p[$who])->get(route('employee.home'))->assertOk()->assertSee('Kerja Bareng Trio');
        }

        $this->actingAs($this->p['hrd'])->get(route('employee.home'))->assertOk()->assertDontSee('Kerja Bareng Trio');
    }

    public function test_home_card_names_the_other_pics_but_not_the_viewer(): void
    {
        $item = $this->item(['title' => 'Kerja Bareng Duo', 'pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertSee('Bersama: Aldora', false);
    }

    public function test_scope_for_pic_matches_main_and_additional_only(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);
        $this->item(['title' => 'Milik Rania', 'item_no' => 2, 'pic_employee_id' => $this->p['hrd']->id]);

        $this->assertSame(1, WorkItem::forPic($this->p['aldora']->id)->count());
        $this->assertSame(1, WorkItem::forPic($this->p['gepeng']->id)->count());
        $this->assertSame(2, WorkItem::forPic([$this->p['gepeng']->id, $this->p['hrd']->id])->count());
        $this->assertSame(0, WorkItem::forPic($this->p['manajer']->id)->count());
    }

    // ---- Board: filter & tampilan -----------------------------------

    public function test_board_pic_filter_includes_tasks_where_person_is_additional_pic(): void
    {
        $shared = $this->item(['title' => 'Task Bersama', 'pic_employee_id' => $this->p['aldora']->id]);
        $shared->additionalPics()->sync([$this->p['gepeng']->id]);
        $this->item(['title' => 'Task Aldora Saja', 'item_no' => 2, 'pic_employee_id' => $this->p['aldora']->id]);

        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.tracker.index', ['pic' => $this->p['gepeng']->id]))
            ->assertOk()
            ->assertSee('Task Bersama')
            ->assertDontSee('Task Aldora Saja');
    }

    public function test_board_lists_every_pic_name_in_the_row(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id, $this->p['manajer']->id]);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()->assertSeeInOrder(['Aldora', 'Gepeng', 'Kanaya']);
    }

    // ---- Kalender & Team Overview ------------------------------------

    public function test_dashboard_calendar_pic_filter_and_chip_include_additional_pics(): void
    {
        $item = $this->item(['title' => 'Kalender Bersama', 'pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.calendar', ['month' => '2026-09', 'pic' => $this->p['gepeng']->id]))
            ->assertOk()->assertSee('Kalender Bersama')->assertSee('Aldora · Gepeng');
    }

    public function test_employee_calendar_pic_filter_and_dropdown_include_additional_pics(): void
    {
        $item = $this->item(['title' => 'Kalender Karyawan', 'pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['hrd']->id]);

        $response = $this->actingAs($this->p['gepeng'])
            ->get(route('employee.workTracker.calendar', ['month' => '2026-09', 'pic' => $this->p['hrd']->id]))
            ->assertOk();

        $response->assertSee('Kalender Karyawan');
        $this->assertContains($this->p['hrd']->id, $response->viewData('picOptions')->pluck('id')->all());
    }

    public function test_team_overview_counts_shared_task_for_each_team_member(): void
    {
        $item = $this->item(['pic_employee_id' => $this->p['aldora']->id]);
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $rows = $this->actingAs($this->p['manajer'])->get(route('manajer.team.work'))
            ->assertOk()->viewData('rows')->keyBy(fn($r) => $r['user']->id);

        $this->assertSame(1, $rows[$this->p['aldora']->id]['openCount']);
        $this->assertSame(1, $rows[$this->p['gepeng']->id]['openCount']);
    }

    // ---- Migrasi data lama ---------------------------------------------

    public function test_migration_moves_exact_name_matches_from_free_text_into_real_pics(): void
    {
        $migration = require database_path('migrations/2026_09_30_091136_work_item_additional_pics.php');

        $exact = $this->item(['title' => 'A', 'pic_employee_id' => $this->p['aldora']->id, 'additional_pic' => 'Gepeng & Kanaya']);
        $partial = $this->item(['title' => 'B', 'item_no' => 2, 'pic_employee_id' => $this->p['aldora']->id, 'additional_pic' => 'Gepeng WS Team']);
        $self = $this->item(['title' => 'C', 'item_no' => 3, 'pic_employee_id' => $this->p['aldora']->id, 'additional_pic' => 'Aldora, Rania']);
        $allTeam = $this->item(['title' => 'D', 'item_no' => 4, 'additional_pic' => 'ALL TEAM']);

        DB::table('work_item_additional_pics')->delete();
        $method = new \ReflectionMethod($migration, 'backfillFromFreeText');
        $method->invoke($migration);

        $this->assertEqualsCanonicalizing([$this->p['gepeng']->id, $this->p['manajer']->id], $exact->additionalPics()->pluck('users.id')->all());
        $this->assertSame(0, $partial->additionalPics()->count(), 'Nama tidak persis = dibiarkan sebagai teks.');
        $this->assertSame([$this->p['hrd']->id], $self->additionalPics()->pluck('users.id')->all(), 'PIC utama tidak boleh jadi PIC tambahan.');
        $this->assertSame(0, $allTeam->additionalPics()->count());
        $this->assertSame('Gepeng WS Team', $partial->fresh()->additional_pic, 'Teks lama tidak dihapus.');
    }
}