<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Karyawan (PIC) mengubah status & catatan task miliknya dari kartu
 * "My Work Tracker" di Home (2026-10-11). Aldora = PIC utama, Gepeng = PIC
 * tambahan, Kanaya (manajer) = bukan PIC.
 */
class MyWorkItemUpdateTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private WorkItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();

        $project = Project::create([
            'name' => 'Album Q3',
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ]);

        $this->item = WorkItem::create([
            'project_id' => $project->id,
            'section' => 'CONTRACT',
            'item_no' => 1,
            'title' => 'Contract Rossa',
            'due_date' => '2026-09-25',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'pic_employee_id' => $this->p['aldora']->id,
            'created_by' => $this->p['owner']->id,
        ]);
        $this->item->additionalPics()->attach($this->p['gepeng']->id);
    }

    private function setStatus(string $who, string $progress, ?WorkItem $item = null)
    {
        return $this->actingAs($this->p[$who])
            ->patch(route('employee.workItems.progress', $item ?? $this->item), ['progress' => $progress]);
    }

    private function setNote(string $who, ?string $notes, ?WorkItem $item = null)
    {
        return $this->actingAs($this->p[$who])
            ->patch(route('employee.workItems.note', $item ?? $this->item), ['notes' => $notes]);
    }

    // ---- Status ----------------------------------------------------

    public function test_main_pic_can_change_status_and_returns_to_the_tracker(): void
    {
        $this->setStatus('aldora', 'On Development')
            ->assertRedirect(route('employee.home') . '#my-work-tracker')
            ->assertSessionHas('status');

        $this->assertSame('On Development', $this->item->fresh()->progress);
    }

    public function test_additional_pic_can_change_status_too(): void
    {
        $this->setStatus('gepeng', 'Follow Up')->assertSessionHasNoErrors();

        $this->assertSame('Follow Up', $this->item->fresh()->progress);
    }

    public function test_status_change_is_written_to_the_audit_log(): void
    {
        $this->setStatus('aldora', 'Done');

        $log = AuditLog::query()->where('action', 'Progress task diubah (oleh PIC)')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Contract Rossa', $log->detail);
        $this->assertStringContainsString('Pending → Done', $log->detail);
    }

    public function test_choosing_the_same_status_changes_nothing_and_writes_no_log(): void
    {
        $this->setStatus('aldora', 'Pending')->assertRedirect();

        $this->assertFalse(AuditLog::query()->where('action', 'Progress task diubah (oleh PIC)')->exists());
    }

    public function test_unknown_status_is_rejected(): void
    {
        $this->setStatus('aldora', 'Selesai Banget')->assertSessionHasErrors('progress');

        $this->assertSame('Pending', $this->item->fresh()->progress);
    }

    // ---- Catatan ---------------------------------------------------

    public function test_pic_can_save_and_clear_the_note(): void
    {
        $this->setNote('aldora', "  Menunggu tanda tangan Rossa.  ")->assertSessionHas('status');
        $this->assertSame('Menunggu tanda tangan Rossa.', $this->item->fresh()->notes);

        $this->setNote('gepeng', '')->assertSessionHasNoErrors();
        $this->assertNull($this->item->fresh()->notes);

        $this->assertTrue(AuditLog::query()->where('action', 'Catatan task diubah (oleh PIC)')->exists());
    }

    public function test_note_longer_than_2000_characters_is_rejected(): void
    {
        $this->setNote('aldora', str_repeat('a', 2001))->assertSessionHasErrors('notes');

        $this->assertNull($this->item->fresh()->notes);
    }

    // ---- Hak akses -------------------------------------------------

    public function test_non_pic_cannot_change_status_or_note_even_with_work_manage_access(): void
    {
        // Kanaya bukan PIC task ini. Pengelola task tetap lewat dashboard Work, bukan endpoint ini.
        $this->grant($this->p['manajer'], 'work', 'manage');

        $this->setStatus('manajer', 'Done')->assertForbidden();
        $this->setNote('manajer', 'Coba ubah')->assertForbidden();
        $this->setStatus('owner', 'Done')->assertForbidden();

        $fresh = $this->item->fresh();
        $this->assertSame('Pending', $fresh->progress);
        $this->assertNull($fresh->notes);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->patch(route('employee.workItems.progress', $this->item), ['progress' => 'Done'])
            ->assertRedirect(route('login'));
    }

    public function test_only_status_and_note_can_be_changed_from_this_endpoint(): void
    {
        $this->actingAs($this->p['aldora'])->patch(route('employee.workItems.progress', $this->item), [
            'progress' => 'Done',
            'title' => 'Judul Diganti',
            'pic_employee_id' => $this->p['hrd']->id,
            'due_date' => '2030-01-01',
        ]);

        $fresh = $this->item->fresh();
        $this->assertSame('Contract Rossa', $fresh->title);
        $this->assertSame($this->p['aldora']->id, $fresh->pic_employee_id);
        $this->assertSame('2026-09-25', $fresh->due_date->toDateString());
    }

    // ---- Tampilan Home ---------------------------------------------

    public function test_home_shows_status_and_note_controls_for_the_pic_only(): void
    {
        foreach (['aldora', 'gepeng'] as $who) {
            $this->actingAs($this->p[$who])->get(route('employee.home'))
                ->assertOk()
                ->assertSee(route('employee.workItems.progress', $this->item), false)
                ->assertSee(route('employee.workItems.note', $this->item), false)
                ->assertSee('Update Note');
        }

        $this->actingAs($this->p['manajer'])->get(route('employee.home'))
            ->assertOk()
            ->assertDontSee(route('employee.workItems.progress', $this->item), false);
    }

    public function test_done_task_moves_to_the_finished_list_and_can_be_reopened(): void
    {
        $this->setStatus('aldora', 'Done');

        $home = $this->actingAs($this->p['aldora'])->get(route('employee.home'))->assertOk();
        $home->assertSee('0 open');
        $home->assertSee('Lihat item selesai');
        $home->assertSee('Salah tandai selesai?');

        // Dibuka lagi dari daftar selesai lewat kontrol yang sama.
        $this->setStatus('aldora', 'On Development');

        $reopened = $this->actingAs($this->p['aldora'])->get(route('employee.home'))->assertOk();
        $reopened->assertSee('1 open');
        $reopened->assertDontSee('Lihat item selesai');
    }
}