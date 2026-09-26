<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * README Bab 2.1 #28 / Bab 4.2 no. 9 — "Assign / Reminder dari
 * dashboard". Aldora = work `manage`, Gepeng = work `view` (sama
 * fixture yang dipakai WorkControlTest).
 */
class WorkReminderTest extends TestCase
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

    private function payload(array $o = []): array
    {
        return array_merge([
            'pic_employee_id' => $this->p['gepeng']->id,
            'title' => 'Update tracker sebelum meeting',
            'due_date' => '2026-09-30',
            'priority' => 'High',
            'notes' => 'Sertakan progres terbaru.',
        ], $o);
    }

    public function test_manager_of_work_module_sends_reminder_creating_work_item_and_targeted_memo(): void
    {
        $this->actingAs($this->p['aldora'])
            ->post(route('dashboard.work.reminder.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('status');

        $item = WorkItem::sole();
        $this->assertTrue($item->is_reminder);
        $this->assertSame('REMINDER / ADMIN', $item->section);
        $this->assertSame('Update tracker sebelum meeting', $item->title);
        $this->assertSame($this->p['gepeng']->id, $item->pic_employee_id);
        $this->assertSame('Pending', $item->progress);
        $this->assertSame('High', $item->priority);
        $this->assertSame($this->p['aldora']->id, $item->created_by);

        $memo = Memo::sole();
        $this->assertSame('tertentu', $memo->audience);
        $this->assertSame([$this->p['gepeng']->id], $memo->recipients()->pluck('users.id')->all());
        $this->assertStringContainsString('Update tracker sebelum meeting', $memo->content);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertSee('Work Reminder');
        $this->actingAs($this->p['hrd'])->get(route('employee.home'))->assertDontSee('Work Reminder');
    }

    public function test_view_only_access_to_work_module_cannot_send_reminder(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->post(route('dashboard.work.reminder.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('memos', 0);
    }

    public function test_reminder_validation(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $post = fn(array $o) => $aldora->post(route('dashboard.work.reminder.store'), $this->payload($o));

        $post(['pic_employee_id' => ''])->assertSessionHasErrors('pic_employee_id');
        $post(['pic_employee_id' => 9999])->assertSessionHasErrors('pic_employee_id');
        $post(['title' => ''])->assertSessionHasErrors('title');
        $post(['priority' => 'Urgent'])->assertSessionHasErrors('priority');
        $post(['due_date' => 'bukan-tanggal'])->assertSessionHasErrors('due_date');

        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('memos', 0);
    }
}