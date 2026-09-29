<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Pembatasan project per tim (2026-09-29): `users.work_team` +
 * `projects.visibility`, diterapkan di kalender bersama karyawan
 * (/app/kalender-tim). Kalender dashboard modul work tetap menampilkan semua.
 */
class WorkVisibilityTest extends TestCase
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

    private function project(string $name, string $visibility = 'all', ?int $leadId = null): Project
    {
        return Project::create([
            'slug' => Project::uniqueSlugFrom($name),
            'name' => $name,
            'color' => '#3558f4',
            'priority' => 'Medium',
            'status' => 'On Development',
            'visibility' => $visibility,
            'lead_employee_id' => $leadId,
            'created_by' => $this->p['owner']->id,
        ]);
    }

    private function item(string $title, ?Project $project, array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $project?->id,
            'section' => 'General',
            'item_no' => 1,
            'title' => $title,
            'due_date' => '2026-09-22',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function calendar(User $user, string $query = '')
    {
        return $this->actingAs($user)->get(route('employee.workTracker.calendar') . '?month=2026-09' . $query);
    }

    private function marketer(): User
    {
        return $this->makeUser('karyawan', [], $this->p['manajer']->id, ['name' => 'Mira Marketing', 'division' => 'Marketing']);
    }

    private function creative(): User
    {
        return $this->makeUser('karyawan', [], $this->p['manajer']->id, ['name' => 'Cakra Kreatif', 'division' => 'Creative']);
    }

    // ---- tim kerja -------------------------------------------------------

    public function test_work_team_is_guessed_from_division_and_job_title(): void
    {
        $guess = fn(array $a) => (new User(array_merge(['role' => 'karyawan'], $a)))->workTeam();

        $this->assertSame('marketing', $guess(['division' => 'Marketing']));
        $this->assertSame('marketing', $guess(['job_title' => 'PR Specialist']));
        $this->assertSame('creative', $guess(['division' => 'Creative', 'job_title' => 'Video Editor']));
        $this->assertSame('finance', $guess(['division' => 'Finance']));
        $this->assertSame('legal', $guess(['job_title' => 'Contract Officer']));
        $this->assertSame('hr', $guess(['division' => 'HR']));
        $this->assertSame('ga', $guess(['job_title' => 'Driver']));
        $this->assertSame('management', $guess(['job_title' => 'General Manager']));
        $this->assertSame('other', $guess([]));
        // Kata pendek tidak boleh salah tangkap ("product" bukan "pr", "shrimp" bukan "hr").
        $this->assertSame('other', $guess(['job_title' => 'Product Owner']));
        $this->assertSame('other', $guess(['division' => 'Shrimp Farm']));
        // Owner selalu Management walau divisinya kosong.
        $this->assertSame('management', (new User(['role' => 'owner']))->workTeam());
    }

    public function test_explicit_work_team_overrides_the_guess(): void
    {
        $user = new User(['role' => 'karyawan', 'division' => 'Marketing', 'work_team' => 'legal']);
        $this->assertSame('legal', $user->workTeam());

        $bogus = new User(['role' => 'karyawan', 'division' => 'Marketing', 'work_team' => 'tidak-ada']);
        $this->assertSame('marketing', $bogus->workTeam());
    }

    // ---- kalender bersama karyawan ---------------------------------------

    public function test_open_projects_are_visible_to_everyone_by_default(): void
    {
        $open = $this->project('Album Terbuka');
        $this->item('Tugas Terbuka', $open);

        foreach ([$this->marketer(), $this->creative(), $this->p['gepeng']] as $user) {
            $this->calendar($user)->assertOk()->assertSee('Tugas Terbuka');
        }
    }

    public function test_team_project_is_hidden_from_other_teams_but_shown_to_its_team(): void
    {
        $secret = $this->project('Kontrak Rahasia', 'marketing');
        $this->item('Tugas Marketing Only', $secret);

        $this->calendar($this->marketer())->assertOk()->assertSee('Tugas Marketing Only');
        $this->calendar($this->creative())->assertOk()->assertDontSee('Tugas Marketing Only');
        $this->calendar($this->p['gepeng'])->assertOk()->assertDontSee('Tugas Marketing Only');
    }

    public function test_owner_and_developer_always_see_everything(): void
    {
        $secret = $this->project('Kontrak Rahasia', 'assigned');
        $this->item('Tugas Terkunci', $secret);

        $this->calendar($this->p['owner'])->assertOk()->assertSee('Tugas Terkunci');
        $this->calendar($this->makeDeveloper($this->p['owner']))->assertOk()->assertSee('Tugas Terkunci');
    }

    public function test_project_lead_and_task_pic_still_see_their_own_work(): void
    {
        $lead = $this->creative();
        $pic = $this->makeUser('karyawan', [], $this->p['manajer']->id, ['name' => 'Pandu Penanggung', 'division' => 'Finance']);
        $other = $this->makeUser('karyawan', [], $this->p['manajer']->id, ['name' => 'Rina Rekan', 'division' => 'Finance']);

        $secret = $this->project('Kontrak Rahasia', 'marketing', $lead->id);
        $this->item('Tugas Rahasia Umum', $secret);
        $this->item('Tugas Milik Pandu', $secret, ['pic_employee_id' => $pic->id, 'due_date' => '2026-09-23']);
        $this->item('Tugas Rekan Tambahan', $secret, ['additional_pic' => 'Rina Rekan', 'due_date' => '2026-09-24']);

        // Lead melihat seluruh project walau beda tim.
        $this->calendar($lead)->assertSee('Tugas Rahasia Umum');

        // PIC utama hanya melihat task miliknya, bukan seluruh project.
        $this->calendar($pic)->assertSee('Tugas Milik Pandu')->assertDontSee('Tugas Rahasia Umum');

        // Nama di PIC tambahan juga dihitung.
        $this->calendar($other)->assertSee('Tugas Rekan Tambahan')->assertDontSee('Tugas Rahasia Umum');
    }

    public function test_assigned_only_hides_the_project_from_its_own_team(): void
    {
        $lead = $this->creative();
        $project = $this->project('Rencana Khusus', 'assigned', $lead->id);
        $this->item('Tugas Khusus', $project);

        $this->calendar($this->marketer())->assertDontSee('Tugas Khusus');
        $this->calendar($this->creative())->assertDontSee('Tugas Khusus');
        $this->calendar($lead)->assertSee('Tugas Khusus');
    }

    public function test_tasks_without_a_project_stay_visible(): void
    {
        $this->item('Tugas Tanpa Project', null);

        $this->calendar($this->creative())->assertOk()->assertSee('Tugas Tanpa Project');
    }

    public function test_project_filter_only_lists_and_returns_visible_projects(): void
    {
        $open = $this->project('Album Terbuka');
        $secret = $this->project('Kontrak Rahasia', 'marketing');
        $this->item('Tugas Terbuka', $open);
        $this->item('Tugas Marketing Only', $secret);

        $creative = $this->creative();

        $this->calendar($creative)->assertSee('Album Terbuka')->assertDontSee('Kontrak Rahasia');

        // Memaksa ?project= ke project tersembunyi tidak membocorkan apa pun.
        $this->calendar($creative, '&project=' . $secret->id)->assertOk()->assertDontSee('Tugas Marketing Only');
    }

    public function test_pic_dropdown_does_not_leak_names_from_hidden_projects(): void
    {
        $ghost = $this->makeUser('karyawan', [], $this->p['manajer']->id, ['name' => 'Hantu Tersembunyi', 'division' => 'Marketing']);
        $secret = $this->project('Kontrak Rahasia', 'marketing');
        $this->item('Tugas Marketing Only', $secret, ['pic_employee_id' => $ghost->id]);

        $this->calendar($this->creative())->assertOk()->assertDontSee('Hantu Tersembunyi');
        $this->calendar($this->marketer())->assertOk()->assertSee('Hantu Tersembunyi');
    }

    public function test_dashboard_calendar_for_work_module_still_shows_every_project(): void
    {
        $secret = $this->project('Kontrak Rahasia', 'assigned');
        $this->item('Tugas Terkunci', $secret);

        $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.calendar') . '?month=2026-09')
            ->assertOk()
            ->assertSee('Tugas Terkunci');
    }

    // ---- form project ----------------------------------------------------

    private function projectPayload(array $o = []): array
    {
        return array_merge(['name' => 'Project Baru', 'color' => '#3558f4', 'priority' => 'Low', 'status' => 'Pending'], $o);
    }

    public function test_project_form_saves_visibility_and_defaults_to_all(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->post(route('dashboard.work.tracker.projects.store'), $this->projectPayload(['name' => 'Tanpa Field']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('all', Project::where('name', 'Tanpa Field')->value('visibility'));

        $aldora->post(route('dashboard.work.tracker.projects.store'), $this->projectPayload(['name' => 'Khusus Legal', 'visibility' => 'legal']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $project = Project::where('name', 'Khusus Legal')->firstOrFail();
        $this->assertSame('legal', $project->visibility);

        $aldora->patch(route('dashboard.work.tracker.projects.update', $project), $this->projectPayload(['name' => 'Khusus Legal', 'visibility' => 'assigned']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('assigned', $project->refresh()->visibility);
    }

    public function test_project_form_rejects_unknown_visibility(): void
    {
        $this->actingAs($this->p['aldora'])
            ->post(route('dashboard.work.tracker.projects.store'), $this->projectPayload(['visibility' => 'semua-orang']))
            ->assertSessionHasErrors('visibility');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_projects_page_marks_restricted_projects(): void
    {
        $this->project('Proyek Terbuka');
        $this->project('Proyek Terbatas', 'finance');

        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.projects.index'))
            ->assertOk()
            ->assertSee('Terlihat: Finance Team')
            ->assertSee('name="visibility"', false);
    }

    // ---- form karyawan ---------------------------------------------------

    private function employeePayload(array $o = []): array
    {
        return array_merge([
            'name' => 'Budi Baru',
            'email' => 'budi.baru@wsm.local',
            'password' => 'rahasia123',
            'role' => 'karyawan',
            'manager_id' => $this->p['manajer']->id,
            'division' => 'Creative',
            'job_title' => 'Editor',
            'join_date' => '2026-09-01',
            'annual_leave_entitlement' => 12,
            'salary_base' => 5500000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 30000,
        ], $o);
    }

    public function test_employee_form_saves_work_team_and_blank_means_automatic(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->post(route('owner.employees.store'), $this->employeePayload(['work_team' => 'finance']))
            ->assertSessionHasNoErrors();
        $budi = User::where('email', 'budi.baru@wsm.local')->firstOrFail();
        $this->assertSame('finance', $budi->work_team);
        $this->assertSame('finance', $budi->workTeam());

        $owner->patch(route('owner.employees.update', $budi), $this->employeePayload(['work_team' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull($budi->refresh()->work_team);
        $this->assertSame('creative', $budi->workTeam()); // ditebak dari divisi "Creative"
    }

    public function test_employee_form_rejects_unknown_work_team_and_shows_the_field(): void
    {
        $this->actingAs($this->p['owner'])
            ->post(route('owner.employees.store'), $this->employeePayload(['work_team' => 'astronot']))
            ->assertSessionHasErrors('work_team');

        $this->actingAs($this->p['owner'])
            ->get(route('owner.employees.edit', $this->p['gepeng']))
            ->assertOk()
            ->assertSee('name="work_team"', false);
    }
}