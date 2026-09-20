<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Memo;
use App\Models\MemoThreadMessage;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian G — Work Control (G1–G13): Memo Forum, Timeline
 * Calendar, Work Tracker (project + task + progress), Meetings/MoM + Blast.
 * Aldora = work `manage`, Gepeng = work `view`.
 */
class WorkControlTest extends TestCase
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

    private function memoPayload(array $o = []): array
    {
        return array_merge([
            'type' => 'memo',
            'title' => 'Libur bersama',
            'content' => 'Kantor libur Jumat depan.',
            'audience' => 'semua',
        ], $o);
    }

    private function project(array $o = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Album Q3',
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ], $o));
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

    // =====================================================================
    // Memo Forum (G1–G4)
    // =====================================================================

    public function test_memo_forum_is_visible_to_view_and_manage_but_write_actions_need_manage(): void
    {
        $memo = Memo::create($this->memoPayload() + ['created_by' => $this->p['owner']->id]);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.index'))->assertOk()->assertSee('Libur bersama');

        $gepeng = $this->actingAs($this->p['gepeng']);
        $gepeng->get(route('dashboard.work.create'))->assertForbidden();
        $gepeng->post(route('dashboard.work.store'), $this->memoPayload())->assertForbidden();
        $gepeng->get(route('dashboard.work.edit', $memo))->assertForbidden();
        $gepeng->patch(route('dashboard.work.update', $memo), $this->memoPayload())->assertForbidden();
        $gepeng->delete(route('dashboard.work.destroy', $memo))->assertForbidden();
        $gepeng->post(route('dashboard.work.toggleActive', $memo))->assertForbidden();
        $gepeng->post(route('dashboard.work.reply', $memo), ['message' => 'x'])->assertForbidden();

        $this->assertDatabaseCount('memos', 1);
    }

    public function test_memo_validation(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $post = fn(array $o) => $aldora->post(route('dashboard.work.store'), $this->memoPayload($o));

        $post(['title' => ''])->assertSessionHasErrors('title');
        $post(['content' => ''])->assertSessionHasErrors('content');
        $post(['type' => 'iklan'])->assertSessionHasErrors('type');
        $post(['audience' => 'siapa-saja'])->assertSessionHasErrors('audience');
        $post(['title' => str_repeat('x', 151)])->assertSessionHasErrors('title');
        $post(['audience' => 'tertentu'])->assertSessionHasErrors('recipients');
        $post(['audience' => 'tertentu', 'recipients' => [9999]])->assertSessionHasErrors('recipients.0');

        $this->assertDatabaseCount('memos', 0);
    }

    public function test_manager_of_work_module_creates_pinned_memo_for_everyone(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.create'))->assertOk();

        $this->post(route('dashboard.work.store'), $this->memoPayload(['pinned' => '1']))
            ->assertRedirect(route('dashboard.work.index'))
            ->assertSessionHas('status', 'Memo/MoM berhasil ditambahkan.');

        $memo = Memo::sole();
        $this->assertTrue($memo->pinned);
        $this->assertTrue($memo->active);
        $this->assertSame($this->p['aldora']->id, $memo->created_by);
        $this->assertSame('semua', $memo->audience);

        foreach (['gepeng', 'hrd', 'manajer'] as $who) {
            $this->actingAs($this->p[$who])->get(route('employee.home'))->assertOk()->assertSee('Libur bersama');
        }
    }

    public function test_targeted_memo_only_appears_for_selected_recipients_and_its_creator(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.store'), $this->memoPayload([
            'title' => 'Rahasia Tim Kreatif',
            'audience' => 'tertentu',
            'recipients' => [$this->p['gepeng']->id],
        ]))->assertRedirect();

        $memo = Memo::sole();
        $this->assertSame([$this->p['gepeng']->id], $memo->recipients()->pluck('users.id')->all());

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertSee('Rahasia Tim Kreatif');
        $this->actingAs($this->p['aldora'])->get(route('employee.home'))->assertSee('Rahasia Tim Kreatif'); // pembuat
        $this->actingAs($this->p['hrd'])->get(route('employee.home'))->assertDontSee('Rahasia Tim Kreatif');
        $this->actingAs($this->p['manajer'])->get(route('employee.home'))->assertDontSee('Rahasia Tim Kreatif');
    }

    public function test_memo_edit_deactivate_reactivate_and_delete(): void
    {
        $memo = Memo::create($this->memoPayload() + ['created_by' => $this->p['owner']->id, 'active' => true]);
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->get(route('dashboard.work.edit', $memo))->assertOk();
        $aldora->patch(route('dashboard.work.update', $memo), $this->memoPayload(['title' => 'Judul Baru', 'pinned' => '1']))
            ->assertRedirect(route('dashboard.work.index'))
            ->assertSessionHas('status', 'Memo/MoM berhasil diperbarui.');
        $this->assertSame('Judul Baru', $memo->fresh()->title);
        $this->assertTrue($memo->fresh()->pinned);

        // Nonaktif → hilang dari Home karyawan, tetap ada di forum manajemen.
        $aldora->post(route('dashboard.work.toggleActive', $memo));
        $this->assertFalse($memo->fresh()->active);
        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertDontSee('Judul Baru');
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.index'))->assertSee('Judul Baru');

        $aldora->post(route('dashboard.work.toggleActive', $memo));
        $this->assertTrue($memo->fresh()->active);
        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertSee('Judul Baru');

        $this->actingAs($this->p['aldora'])->delete(route('dashboard.work.destroy', $memo))
            ->assertSessionHas('status', 'Memo/MoM berhasil dihapus.');
        $this->assertDatabaseCount('memos', 0);
    }

    public function test_pinned_memos_come_first_on_the_employee_home(): void
    {
        Memo::create($this->memoPayload(['title' => 'Memo biasa terbaru']) + ['created_by' => $this->p['owner']->id, 'created_at' => now()]);
        Memo::create($this->memoPayload(['title' => 'Memo pinned lama']) + ['created_by' => $this->p['owner']->id, 'pinned' => true, 'created_at' => now()->subDays(5)]);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertOk()
            ->assertViewHas('memos', fn($m) => $m->pluck('title')->all() === ['Memo pinned lama', 'Memo biasa terbaru']);
    }

    public function test_management_reply_is_shown_to_employees_and_marked_read_by_management(): void
    {
        $memo = Memo::create($this->memoPayload() + ['created_by' => $this->p['owner']->id]);

        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.reply', $memo), ['message' => 'Balasan dari manajemen'])
            ->assertSessionHas('status', 'Reply terkirim.');

        $reply = MemoThreadMessage::sole();
        $this->assertNotNull($reply->read_by_management_at);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertSee('Balasan dari manajemen');

        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.reply', $memo), ['message' => ''])->assertSessionHasErrors('message');
    }

    public function test_employee_reply_is_unread_for_management_until_the_forum_is_opened(): void
    {
        $memo = Memo::create($this->memoPayload() + ['created_by' => $this->p['owner']->id]);

        $this->actingAs($this->p['gepeng'])->post(route('employee.memo.reply', $memo), ['message' => 'Siap, noted!'])
            ->assertSessionHas('status', 'Reply terkirim.');

        $this->assertNull(MemoThreadMessage::sole()->read_by_management_at);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.index'))->assertOk()->assertSee('Siap, noted!');

        $this->assertNotNull(MemoThreadMessage::sole()->read_by_management_at);
    }

    // =====================================================================
    // Work Tracker (G6–G10)
    // =====================================================================

    public function test_tracker_board_groups_tasks_by_progress_and_filters_by_project(): void
    {
        $a = $this->project(['name' => 'Album Q3']);
        $b = $this->project(['name' => 'Merch Drop']);
        $this->item(['project_id' => $a->id, 'title' => 'Task A', 'progress' => 'Pending']);
        $this->item(['project_id' => $a->id, 'title' => 'Task A2', 'progress' => 'Done', 'item_no' => 2]);
        $this->item(['project_id' => $b->id, 'title' => 'Task B', 'progress' => 'Pending']);

        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.tracker.index'))
            ->assertOk()
            ->assertViewHas('columns', fn($c) => $c['Pending']->count() === 2 && $c['Done']->count() === 1 && $c->keys()->all() === WorkItem::PROGRESS_OPTIONS);

        $this->get(route('dashboard.work.tracker.index', ['project_id' => $b->id]))
            ->assertViewHas('columns', fn($c) => $c->flatten()->pluck('title')->all() === ['Task B'])
            ->assertViewHas('selectedProjectId', $b->id);
    }

    public function test_view_only_user_cannot_change_projects_or_tasks(): void
    {
        $project = $this->project();
        $item = $this->item();
        $g = $this->actingAs($this->p['gepeng']);

        $g->post(route('dashboard.work.tracker.projects.store'), ['name' => 'X', 'priority' => 'Low', 'status' => 'Pending'])->assertForbidden();
        $g->patch(route('dashboard.work.tracker.projects.update', $project), ['name' => 'X', 'priority' => 'Low', 'status' => 'Pending'])->assertForbidden();
        $g->delete(route('dashboard.work.tracker.projects.destroy', $project))->assertForbidden();
        $g->post(route('dashboard.work.tracker.items.store'), ['title' => 'X', 'progress' => 'Pending', 'priority' => 'Low'])->assertForbidden();
        $g->patch(route('dashboard.work.tracker.items.update', $item), ['title' => 'X', 'progress' => 'Pending', 'priority' => 'Low'])->assertForbidden();
        $g->patch(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Done'])->assertForbidden();
        $g->delete(route('dashboard.work.tracker.items.destroy', $item))->assertForbidden();

        $this->assertSame('Approval lirik', $item->fresh()->title);
        $this->assertSame('Pending', $item->fresh()->progress);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_project_create_update_delete_and_delete_detaches_tasks(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->post(route('dashboard.work.tracker.projects.store'), [
            'name' => 'Merch Drop Oktober',
            'color' => '#27c84d',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'priority' => 'High',
            'status' => 'Confirmed',
            'lead_employee_id' => $this->p['manajer']->id,
            'tracker_url' => 'https://example.com/board',
            'progress_recap' => 'Baru mulai',
        ])->assertSessionHas('status', 'Project ditambahkan.');

        $project = Project::where('name', 'Merch Drop Oktober')->firstOrFail();
        $this->assertSame('merch-drop-oktober', $project->slug);
        $this->assertSame('#27c84d', $project->color);
        $this->assertSame($this->p['aldora']->id, $project->created_by);

        $aldora->patch(route('dashboard.work.tracker.projects.update', $project), [
            'name' => 'Merch Drop Nov',
            'priority' => 'Low',
            'status' => 'Done',
        ])->assertSessionHas('status', 'Project diperbarui.');
        $this->assertSame('Merch Drop Nov', $project->fresh()->name);
        $this->assertSame('Done', $project->fresh()->status);

        $task = $this->item(['project_id' => $project->id]);
        $aldora->delete(route('dashboard.work.tracker.projects.destroy', $project))
            ->assertSessionHas('status', 'Project dihapus. Task yang nempel dipindah jadi "Tanpa Project".');

        $this->assertNull(Project::find($project->id));
        $this->assertNotNull($task->fresh(), 'Task tidak boleh ikut terhapus.');
        $this->assertNull($task->fresh()->project_id);
    }

    public function test_project_validation_and_automatic_color_and_unique_slug(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $store = fn(array $o) => $aldora->post(route('dashboard.work.tracker.projects.store'), array_merge(
            ['name' => 'Proyek', 'priority' => 'Low', 'status' => 'Pending'],
            $o
        ));

        $store(['color' => 'merah'])->assertSessionHasErrors('color');
        $store(['color' => '#12345'])->assertSessionHasErrors('color');
        $store(['start_date' => '2026-10-10', 'end_date' => '2026-10-01'])->assertSessionHasErrors('end_date');
        $store(['name' => ''])->assertSessionHasErrors('name');
        $store(['priority' => 'Urgent'])->assertSessionHasErrors('priority');
        $store(['status' => 'Selesai banget'])->assertSessionHasErrors('status');
        $store(['tracker_url' => 'bukan-url'])->assertSessionHasErrors('tracker_url');
        $store(['lead_employee_id' => 9999])->assertSessionHasErrors('lead_employee_id');
        $this->assertDatabaseCount('projects', 0);

        $store(['color' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $store(['color' => ''])->assertRedirect()->assertSessionHasNoErrors();

        $slugs = Project::orderBy('id')->pluck('slug')->all();
        $this->assertSame(['proyek', 'proyek-2'], $slugs);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', Project::first()->color, 'Warna kosong harus diisi otomatis dari palet.');
    }

    public function test_task_create_update_delete_and_item_numbers_increase(): void
    {
        $project = $this->project();
        $aldora = $this->actingAs($this->p['aldora']);
        $payload = [
            'project_id' => $project->id,
            'section' => 'Pre-production',
            'title' => 'Approval lirik single',
            'due_date' => '2026-09-25',
            'pic_employee_id' => $this->p['gepeng']->id,
            'additional_pic' => 'Vendor',
            'progress' => 'On Development',
            'priority' => 'High',
            'notes' => 'Kirim H+1',
            'link' => 'https://example.com/lirik',
        ];

        $aldora->post(route('dashboard.work.tracker.items.store'), $payload)->assertSessionHas('status', 'Task ditambahkan.');
        $aldora->post(route('dashboard.work.tracker.items.store'), ['title' => 'Task kedua'] + $payload);

        $items = WorkItem::orderBy('id')->get();
        $this->assertEquals([1, 2], $items->pluck('item_no')->all());
        $this->assertSame($this->p['aldora']->id, $items[0]->created_by);
        $this->assertSame('2026-09-25', $items[0]->due_date->toDateString());
        $this->assertSame($this->p['gepeng']->id, $items[0]->pic_employee_id);

        $aldora->patch(route('dashboard.work.tracker.items.update', $items[0]), ['title' => 'Judul revisi', 'progress' => 'Follow Up', 'priority' => 'Low'])
            ->assertSessionHas('status', 'Task diperbarui.');
        $this->assertSame('Judul revisi', $items[0]->fresh()->title);
        $this->assertSame('Follow Up', $items[0]->fresh()->progress);

        $aldora->delete(route('dashboard.work.tracker.items.destroy', $items[0]))->assertSessionHas('status', 'Task dihapus.');
        $this->assertDatabaseCount('work_items', 1);
    }

    public function test_task_without_a_section_can_be_created(): void
    {
        // Form Task tidak mewajibkan section (kosong dikirim browser sebagai null) — dulu error 500
        // karena kolom `section` NOT NULL.
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.items.store'), [
            'title' => 'Tanpa section',
            'section' => '',
            'progress' => 'Pending',
            'priority' => 'Medium',
        ])->assertRedirect()->assertSessionHas('status', 'Task ditambahkan.');

        $this->assertSame('Tanpa section', WorkItem::sole()->title);
    }

    public function test_task_validation(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $store = fn(array $o) => $aldora->post(route('dashboard.work.tracker.items.store'), array_merge(
            ['title' => 'T', 'progress' => 'Pending', 'priority' => 'Low'],
            $o
        ));

        $store(['title' => ''])->assertSessionHasErrors('title');
        $store(['progress' => 'Selesai'])->assertSessionHasErrors('progress');
        $store(['priority' => 'Kritis'])->assertSessionHasErrors('priority');
        $store(['link' => 'javascript:alert(1)'])->assertSessionHasErrors('link');
        $store(['link' => 'bukan-url'])->assertSessionHasErrors('link');
        $store(['project_id' => 9999])->assertSessionHasErrors('project_id');
        $store(['pic_employee_id' => 9999])->assertSessionHasErrors('pic_employee_id');
        $store(['due_date' => 'besok'])->assertSessionHasErrors('due_date');

        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_quick_progress_change_supports_all_six_statuses_and_json(): void
    {
        $item = $this->item();
        $aldora = $this->actingAs($this->p['aldora']);

        foreach (WorkItem::PROGRESS_OPTIONS as $status) {
            $aldora->patch(route('dashboard.work.tracker.items.progress', $item), ['progress' => $status])->assertRedirect();
            $this->assertSame($status, $item->fresh()->progress);
        }

        $aldora->patch(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Postpone'], ['Accept' => 'application/json'])
            ->assertOk()->assertExactJson(['ok' => true]);

        $aldora->patch(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Ngawur'])->assertSessionHasErrors('progress');
        $this->assertSame('Postpone', $item->fresh()->progress);
    }

    public function test_tasks_show_up_in_my_work_tracker_of_the_assigned_employee_only(): void
    {
        $this->item(['title' => 'Milik Gepeng', 'pic_employee_id' => $this->p['gepeng']->id, 'progress' => 'On Development']);
        $this->item(['title' => 'Sudah selesai', 'pic_employee_id' => $this->p['gepeng']->id, 'progress' => 'Done', 'item_no' => 2]);
        $this->item(['title' => 'Milik Aldora', 'pic_employee_id' => $this->p['aldora']->id, 'item_no' => 3]);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertOk()
            ->assertViewHas('openWorkItems', fn($i) => $i->pluck('title')->all() === ['Milik Gepeng'])
            ->assertViewHas('doneWorkItemsCount', 1);

        // Ubah progress lewat dashboard → langsung tercermin di Home.
        $item = WorkItem::where('title', 'Milik Gepeng')->first();
        $this->actingAs($this->p['aldora'])->patch(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'Done']);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertViewHas('openWorkItems', fn($i) => $i->isEmpty())
            ->assertViewHas('doneWorkItemsCount', 2);
    }

    // =====================================================================
    // Timeline Calendar (G5) + Kalender Tim (C22)
    // =====================================================================

    public function test_timeline_calendar_renders_navigates_months_and_filters(): void
    {
        $project = $this->project();
        $this->item(['project_id' => $project->id, 'title' => 'Posting teaser', 'due_date' => '2026-09-24', 'pic_employee_id' => $this->p['gepeng']->id]);
        $this->item(['title' => 'Task bulan depan', 'due_date' => '2026-10-05', 'item_no' => 2]);

        $g = $this->actingAs($this->p['gepeng']);

        $g->get(route('dashboard.work.calendar'))->assertOk()->assertSee('Posting teaser');
        $g->get(route('dashboard.work.calendar', ['month' => '2026-10']))->assertOk()->assertSee('Task bulan depan');
        $g->get(route('dashboard.work.calendar', ['month' => 'ngawur']))->assertOk();
        $g->get(route('dashboard.work.calendar', ['project' => $project->id]))->assertOk()->assertSee('Posting teaser');
        $g->get(route('dashboard.work.calendar', ['pic' => $this->p['gepeng']->id]))->assertOk();
    }

    public function test_employee_shared_calendar_renders_for_every_role(): void
    {
        $this->item(['title' => 'Posting teaser', 'due_date' => '2026-09-24']);

        foreach ($this->p as $who => $user) {
            $this->actingAs($user)->get(route('employee.workTracker.calendar'))->assertOk();
        }

        $this->actingAs($this->p['gepeng'])->get(route('employee.workTracker.calendar', ['month' => '2026-09']))
            ->assertOk()->assertSee('Posting teaser');
        $this->get(route('employee.workTracker.calendar', ['month' => 'ngawur']))->assertOk();
    }

    // =====================================================================
    // Meetings / MoM (G11–G13)
    // =====================================================================

    private function meetingPayload(array $o = []): array
    {
        return array_merge([
            'date' => '2026-09-21',
            'time' => '10:00',
            'agenda' => 'Kickoff Project Q3',
            'notes' => 'Bahas timeline.',
            'decisions' => 'Rilis 30 Oktober.',
            'attendees' => [$this->p['manajer']->id, $this->p['gepeng']->id],
            'action_items' => [
                ['task' => 'Kirim brief', 'pic_employee_id' => $this->p['gepeng']->id, 'due_date' => '2026-09-23'],
                ['task' => 'Siapkan venue', 'pic_all' => '1', 'due_date' => '2026-09-25'],
            ],
        ], $o);
    }

    public function test_meeting_creation_saves_attendees_and_action_items_and_can_sync_to_tracker(): void
    {
        $project = $this->project();
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->get(route('dashboard.work.meetings.create'))->assertOk();
        $aldora->post(route('dashboard.work.meetings.store'), $this->meetingPayload(['project_id' => $project->id, 'sync_to_tracker' => '1']))
            ->assertRedirect(route('dashboard.work.meetings.index'))
            ->assertSessionHas('status', 'MoM berhasil ditambahkan.');

        $meeting = Meeting::sole();
        $this->assertSame('Kickoff Project Q3', $meeting->agenda);
        $this->assertSame($this->p['aldora']->id, $meeting->created_by);
        $this->assertCount(2, $meeting->attendees);
        $this->assertCount(2, $meeting->actionItems);

        $tasks = WorkItem::orderBy('id')->get();
        $this->assertCount(2, $tasks, 'Tiap action item harus jadi task di Work Tracker.');
        $this->assertSame($project->id, $tasks[0]->project_id);
        $this->assertSame($this->p['gepeng']->id, $tasks[0]->pic_employee_id);
        $this->assertSame('Pending', $tasks[0]->progress);
        $this->assertNotNull($tasks[0]->meeting_action_item_id);
        $this->assertSame('ALL TEAM', $tasks[1]->additional_pic);
        $this->assertNull($tasks[1]->pic_employee_id);
        $this->assertSame('From MoM: Kickoff Project Q3', $tasks[0]->notes);
    }

    public function test_meeting_without_sync_does_not_create_tracker_tasks(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload())->assertRedirect();

        $this->assertDatabaseCount('meeting_action_items', 2);
        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_meeting_validation(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $post = fn(array $o) => $aldora->post(route('dashboard.work.meetings.store'), $this->meetingPayload($o));

        $post(['agenda' => ''])->assertSessionHasErrors('agenda');
        $post(['date' => ''])->assertSessionHasErrors('date');
        $post(['time' => '10 pagi'])->assertSessionHasErrors('time');
        $post(['attendees' => [9999]])->assertSessionHasErrors('attendees.0');
        $post(['action_items' => [['task' => '']]])->assertSessionHasErrors('action_items.0.task');
        $post(['project_id' => 9999])->assertSessionHasErrors('project_id');

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_meeting_can_be_viewed_edited_and_action_items_removed_on_edit(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload());
        $meeting = Meeting::sole();
        [$keep, $drop] = $meeting->actionItems()->orderBy('id')->get()->all();

        $this->get(route('dashboard.work.meetings.show', $meeting))->assertOk()->assertSee('Kickoff Project Q3')->assertSee('Kirim brief');
        $this->get(route('dashboard.work.meetings.edit', $meeting))->assertOk();

        $this->patch(route('dashboard.work.meetings.update', $meeting), $this->meetingPayload([
            'agenda' => 'Kickoff (revisi)',
            'attendees' => [$this->p['gepeng']->id],
            'action_items' => [
                ['id' => $keep->id, 'task' => 'Kirim brief FINAL', 'pic_employee_id' => $this->p['gepeng']->id],
                ['task' => 'Item baru', 'pic_employee_id' => $this->p['manajer']->id],
            ],
        ]))->assertRedirect(route('dashboard.work.meetings.index'))->assertSessionHas('status', 'MoM berhasil diperbarui.');

        $meeting->refresh();
        $this->assertSame('Kickoff (revisi)', $meeting->agenda);
        $this->assertCount(1, $meeting->attendees);
        $this->assertNull(\App\Models\MeetingActionItem::find($drop->id), 'Action item yang dihapus di form harus hilang.');
        $this->assertSame('Kirim brief FINAL', $keep->fresh()->task);
        $this->assertSame(['Kirim brief FINAL', 'Item baru'], $meeting->actionItems()->orderBy('id')->pluck('task')->all());
    }

    public function test_meeting_can_be_deleted(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload());

        $this->delete(route('dashboard.work.meetings.destroy', Meeting::sole()))->assertSessionHas('status', 'MoM berhasil dihapus.');

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_view_only_user_can_read_meetings_but_not_change_them(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload());
        $meeting = Meeting::sole();

        $g = $this->actingAs($this->p['gepeng']);
        $g->get(route('dashboard.work.meetings.index'))->assertOk()->assertSee('Kickoff Project Q3');
        $g->get(route('dashboard.work.meetings.show', $meeting))->assertOk();
        $g->get(route('dashboard.work.meetings.create'))->assertForbidden();
        $g->post(route('dashboard.work.meetings.store'), $this->meetingPayload())->assertForbidden();
        $g->get(route('dashboard.work.meetings.edit', $meeting))->assertForbidden();
        $g->patch(route('dashboard.work.meetings.update', $meeting), $this->meetingPayload())->assertForbidden();
        $g->delete(route('dashboard.work.meetings.destroy', $meeting))->assertForbidden();
        $g->post(route('dashboard.work.meetings.blast', $meeting))->assertForbidden();

        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_blast_turns_the_minutes_into_a_memo_visible_on_every_employee_home(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload());
        $meeting = Meeting::sole();

        $this->post(route('dashboard.work.meetings.blast', $meeting))
            ->assertRedirect(route('dashboard.work.meetings.index'))
            ->assertSessionHas('status', 'Ringkasan MoM berhasil di-blast jadi Memo ke semua karyawan.');

        $this->assertNotNull($meeting->fresh()->blasted_at);

        $memo = Memo::sole();
        $this->assertSame('mom', $memo->type);
        $this->assertSame('Kickoff Project Q3', $memo->title);
        $this->assertStringContainsString('Rilis 30 Oktober.', $memo->content);
        $this->assertStringContainsString('Kirim brief (PIC: Gepeng, Due: 23 Sep 2026)', $memo->content);
        $this->assertStringContainsString('ALL TEAM', $memo->content);

        foreach (['gepeng', 'hrd', 'manajer', 'owner'] as $who) {
            $this->actingAs($this->p[$who])->get(route('employee.home'))->assertOk()->assertSee('Kickoff Project Q3');
        }
    }

    public function test_blast_is_throttled_at_ten_per_minute(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.meetings.store'), $this->meetingPayload());
        $meeting = Meeting::sole();

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('dashboard.work.meetings.blast', $meeting))->assertRedirect();
        }

        $this->post(route('dashboard.work.meetings.blast', $meeting))->assertStatus(429);
    }

    // =====================================================================
    // Akses modul work
    // =====================================================================

    public function test_users_without_work_access_get_403_everywhere_in_work_control(): void
    {
        foreach (['manajer', 'hrd'] as $who) {
            $u = $this->actingAs($this->p[$who]);

            $u->get(route('dashboard.work.index'))->assertForbidden();
            $u->get(route('dashboard.work.tracker.index'))->assertForbidden();
            $u->get(route('dashboard.work.meetings.index'))->assertForbidden();
            $u->get(route('dashboard.work.calendar'))->assertForbidden();
        }
    }
}