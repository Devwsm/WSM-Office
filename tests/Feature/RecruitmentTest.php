<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian I — Rekrutmen (I1–I7): kelola lowongan, pipeline
 * pelamar, ubah status, convert pelamar → akun karyawan, dan alur ujung ke
 * ujung dari form lamar publik. Rania (hrd) = recruitment `manage`.
 */
class RecruitmentTest extends TestCase
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

    private function openingPayload(array $o = []): array
    {
        return array_merge([
            'title' => 'Video Editor',
            'division' => 'Creative',
            'employment_type' => 'full_time',
            'description' => 'Mengedit video klip.',
            'requirements' => 'Menguasai Premiere.',
            'status' => 'draft',
        ], $o);
    }

    private function opening(array $o = []): JobOpening
    {
        return JobOpening::create(array_merge([
            'title' => 'Video Editor',
            'slug' => 'video-editor',
            'division' => 'Creative',
            'employment_type' => 'full_time',
            'description' => 'Mengedit video klip.',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->p['hrd']->id,
        ], $o));
    }

    private function applicant(JobOpening $opening, array $o = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'job_opening_id' => $opening->id,
            'name' => 'Budi Pelamar',
            'email' => 'budi@example.com',
            'phone' => '0812',
            'status' => 'baru',
        ], $o));
    }

    // ---- I1: kelola lowongan -------------------------------------------

    public function test_hr_creates_a_draft_that_stays_hidden_from_the_public_careers_page(): void
    {
        $rania = $this->actingAs($this->p['hrd']);

        $rania->get(route('recruitment.openings.index'))->assertOk();
        $rania->get(route('recruitment.openings.create'))->assertOk();
        $rania->post(route('recruitment.openings.store'), $this->openingPayload())
            ->assertRedirect(route('recruitment.openings.index'))
            ->assertSessionHas('status', 'Lowongan baru berhasil dibuat.');

        $opening = JobOpening::sole();
        $this->assertSame('video-editor', $opening->slug);
        $this->assertSame('draft', $opening->status);
        $this->assertNull($opening->published_at);
        $this->assertSame($this->p['hrd']->id, $opening->created_by);

        Auth::logout();
        $this->get('/karir')->assertDontSee('Video Editor');
        $this->get('/karir/video-editor')->assertNotFound();
    }

    public function test_publishing_sets_published_at_and_makes_it_visible_publicly_then_closing_hides_it(): void
    {
        $rania = $this->actingAs($this->p['hrd']);
        $rania->post(route('recruitment.openings.store'), $this->openingPayload(['status' => 'published']));
        $opening = JobOpening::sole();
        $this->assertNotNull($opening->published_at);

        Auth::logout();
        $this->get('/karir')->assertSee('Video Editor');
        $this->get('/karir/video-editor')->assertOk();

        $this->actingAs($this->p['hrd'])->get(route('recruitment.openings.edit', $opening))->assertOk();
        $this->patch(route('recruitment.openings.update', $opening), $this->openingPayload(['status' => 'closed']))
            ->assertRedirect(route('recruitment.openings.index'))
            ->assertSessionHas('status', 'Lowongan berhasil diperbarui.');

        $this->assertNotNull($opening->fresh()->closed_at);

        Auth::logout();
        $this->get('/karir')->assertDontSee('Video Editor');
        $this->get('/karir/video-editor')->assertNotFound();
    }

    public function test_draft_promoted_to_published_gets_published_at_once(): void
    {
        $opening = $this->opening(['status' => 'draft', 'published_at' => null]);

        $this->actingAs($this->p['hrd'])->patch(route('recruitment.openings.update', $opening), $this->openingPayload(['status' => 'published']));
        $first = $opening->fresh()->published_at;
        $this->assertNotNull($first);

        $this->freezeWorkday('2026-09-25 10:00:00');
        $this->patch(route('recruitment.openings.update', $opening), $this->openingPayload(['status' => 'published', 'title' => 'Judul revisi']));
        $this->assertEquals($first, $opening->fresh()->published_at, 'Edit tanpa ganti status tidak boleh menggeser tanggal terbit.');
    }

    public function test_same_title_gets_a_unique_slug(): void
    {
        $rania = $this->actingAs($this->p['hrd']);
        $rania->post(route('recruitment.openings.store'), $this->openingPayload());
        $rania->post(route('recruitment.openings.store'), $this->openingPayload());

        $this->assertSame(['video-editor', 'video-editor-2'], JobOpening::orderBy('id')->pluck('slug')->all());
    }

    public function test_opening_validation(): void
    {
        $rania = $this->actingAs($this->p['hrd']);
        $post = fn(array $o) => $rania->post(route('recruitment.openings.store'), $this->openingPayload($o));

        $post(['title' => ''])->assertSessionHasErrors('title');
        $post(['description' => ''])->assertSessionHasErrors('description');
        $post(['employment_type' => 'freelance-abadi'])->assertSessionHasErrors('employment_type');
        $post(['status' => 'arsip'])->assertSessionHasErrors('status');
        $post(['description' => str_repeat('x', 5001)])->assertSessionHasErrors('description');

        $this->assertDatabaseCount('job_openings', 0);
    }

    public function test_opening_list_can_be_filtered_by_status(): void
    {
        $this->opening(['title' => 'Terbit', 'slug' => 'terbit']);
        $this->opening(['title' => 'Konsep', 'slug' => 'konsep', 'status' => 'draft']);

        $this->actingAs($this->p['hrd'])->get(route('recruitment.openings.index', ['status' => 'draft']))
            ->assertOk()->assertSee('Konsep')->assertDontSee('Terbit');
    }

    // ---- I2 / I3: pipeline pelamar -------------------------------------

    public function test_applicant_list_filters_and_search(): void
    {
        $editor = $this->opening();
        $social = $this->opening(['title' => 'Social Media', 'slug' => 'social-media']);
        $this->applicant($editor, ['name' => 'Ani Editor', 'email' => 'ani@example.com']);
        $this->applicant($social, ['name' => 'Rudi Sosmed', 'email' => 'rudi@example.com', 'status' => 'interview']);

        $rania = $this->actingAs($this->p['hrd']);
        $rania->get(route('recruitment.applications.index'))->assertOk()->assertSee('Ani Editor')->assertSee('Rudi Sosmed');
        $rania->get(route('recruitment.applications.index', ['status' => 'interview']))->assertSee('Rudi Sosmed')->assertDontSee('Ani Editor');
        $rania->get(route('recruitment.applications.index', ['lowongan' => $editor->id]))->assertSee('Ani Editor')->assertDontSee('Rudi Sosmed');
        $rania->get(route('recruitment.applications.index', ['q' => 'rudi@']))->assertSee('Rudi Sosmed')->assertDontSee('Ani Editor');
        $rania->get(route('recruitment.applications.index', ['q' => 'tidak-ada-orang-ini']))->assertDontSee('Ani Editor');
    }

    public function test_applicant_detail_and_status_updates(): void
    {
        $application = $this->applicant($this->opening());
        $rania = $this->actingAs($this->p['hrd']);

        $rania->get(route('recruitment.applications.show', $application))->assertOk()->assertSee('Budi Pelamar');

        foreach (JobApplication::STATUSES as $status) {
            $rania->patch(route('recruitment.applications.status', $application), ['status' => $status, 'notes' => "Catatan {$status}"])
                ->assertSessionHas('status', 'Status pelamar berhasil diperbarui.');
            $this->assertSame($status, $application->fresh()->status);
            $this->assertSame("Catatan {$status}", $application->fresh()->notes);
        }

        $rania->patch(route('recruitment.applications.status', $application), ['status' => 'lolos-banget'])->assertSessionHasErrors('status');
        $rania->patch(route('recruitment.applications.status', $application), ['status' => 'ditolak', 'notes' => str_repeat('x', 2001)])->assertSessionHasErrors('notes');
    }

    // ---- I4 / I5: convert -----------------------------------------------

    private function convertPayload(array $o = []): array
    {
        return array_merge([
            'name' => 'Budi Pelamar',
            'email' => 'budi@wsm.local',
            'password' => 'rahasia123',
            'role' => 'karyawan',
            'manager_id' => $this->p['manajer']->id,
            'division' => 'Creative',
            'job_title' => 'Video Editor',
            'join_date' => '2026-10-01',
            'annual_leave_entitlement' => 12,
        ], $o);
    }

    public function test_convert_creates_an_employee_account_links_it_and_marks_the_applicant_accepted(): void
    {
        $application = $this->applicant($this->opening());
        $rania = $this->actingAs($this->p['hrd']);

        $rania->get(route('recruitment.applications.convert', $application))->assertOk();
        $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload())
            ->assertRedirect(route('recruitment.applications.show', $application))
            ->assertSessionHas('status', 'Budi Pelamar berhasil dibuatkan akun karyawan.');

        $user = User::where('email', 'budi@wsm.local')->firstOrFail();
        $this->assertSame('karyawan', $user->role);
        $this->assertSame($this->p['manajer']->id, $user->manager_id);
        $this->assertNotSame('rahasia123', $user->password);

        $application->refresh();
        $this->assertSame($user->id, $application->converted_user_id);
        $this->assertSame('diterima', $application->status);

        $this->post('/logout');
        $this->post('/login', ['email' => 'budi@wsm.local', 'password' => 'rahasia123'])->assertRedirect(route('employee.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_applicant_can_only_be_converted_once(): void
    {
        $application = $this->applicant($this->opening());
        $rania = $this->actingAs($this->p['hrd']);
        $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload());

        $rania->get(route('recruitment.applications.convert', $application))
            ->assertRedirect(route('recruitment.applications.show', $application))
            ->assertSessionHas('error', 'Pelamar ini sudah pernah di-convert jadi karyawan.');

        $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload(['email' => 'lain@wsm.local']))
            ->assertSessionHas('error', 'Pelamar ini sudah pernah di-convert jadi karyawan.');

        $this->assertNull(User::where('email', 'lain@wsm.local')->first());
    }

    public function test_convert_validation(): void
    {
        $application = $this->applicant($this->opening());
        $rania = $this->actingAs($this->p['hrd']);
        $post = fn(array $o) => $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload($o));

        $post(['email' => 'kanaya@wsm.local'])->assertSessionHasErrors('email');
        $post(['password' => 'pendek'])->assertSessionHasErrors('password');
        $post(['role' => 'presiden'])->assertSessionHasErrors('role');
        $post(['manager_id' => 9999])->assertSessionHasErrors('manager_id');
        $post(['name' => ''])->assertSessionHasErrors('name');
        $post(['birth_date' => '2099-01-01'])->assertSessionHasErrors('birth_date');

        $this->assertNull($application->fresh()->converted_user_id);
    }

    /**
     * Keamanan: akun Owner punya akses penuh, jadi hanya Owner yang boleh
     * membuat Owner baru. HRD (recruitment `manage`) tidak boleh, baik lewat
     * form (opsi Owner disembunyikan) maupun lewat kiriman langsung ke server.
     */
    public function test_hr_cannot_create_an_owner_account_through_convert(): void
    {
        $application = $this->applicant($this->opening());
        $rania = $this->actingAs($this->p['hrd']);

        $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload(['role' => 'owner']))
            ->assertSessionHasErrors(['role' => 'Role itu tidak boleh dipilih. Akun Owner hanya bisa dibuat oleh Owner.']);

        $this->assertSame(1, User::where('role', 'owner')->count());
        $this->assertNull($application->fresh()->converted_user_id);

        // Role lain yang sah tetap bisa.
        foreach (['manajer', 'hrd', 'karyawan'] as $i => $role) {
            $other = $this->applicant($this->opening(['slug' => "lowongan-{$i}", 'title' => "Lowongan {$i}"]), ['email' => "pelamar{$i}@example.com"]);
            $rania->post(route('recruitment.applications.convert.store', $other), $this->convertPayload(['email' => "baru{$i}@wsm.local", 'role' => $role]))
                ->assertRedirect(route('recruitment.applications.show', $other));
            $this->assertSame($role, User::where('email', "baru{$i}@wsm.local")->value('role'));
        }
    }

    public function test_owner_can_still_create_an_owner_account_through_convert(): void
    {
        $application = $this->applicant($this->opening());

        $this->actingAs($this->p['owner'])->post(route('recruitment.applications.convert.store', $application), $this->convertPayload(['role' => 'owner']))
            ->assertRedirect(route('recruitment.applications.show', $application));

        $this->assertSame('owner', User::where('email', 'budi@wsm.local')->value('role'));
    }

    public function test_convert_form_only_offers_the_owner_role_to_owners(): void
    {
        $application = $this->applicant($this->opening());

        $this->actingAs($this->p['hrd'])->get(route('recruitment.applications.convert', $application))
            ->assertOk()
            ->assertSee('value="hrd"', false)
            ->assertDontSee('value="owner"', false);

        $this->actingAs($this->p['owner'])->get(route('recruitment.applications.convert', $application))
            ->assertOk()
            ->assertSee('value="owner"', false);
    }

    // ---- I6: akses ------------------------------------------------------

    public function test_view_only_recruiter_can_read_but_not_manage(): void
    {
        $this->grant($this->p['gepeng'], 'recruitment', 'view');
        $opening = $this->opening();
        $application = $this->applicant($opening);
        $g = $this->actingAs($this->p['gepeng']);

        $g->get(route('recruitment.openings.index'))->assertOk();
        $g->get(route('recruitment.applications.index'))->assertOk();
        $g->get(route('recruitment.applications.show', $application))->assertOk();

        $g->get(route('recruitment.openings.create'))->assertForbidden();
        $g->post(route('recruitment.openings.store'), $this->openingPayload())->assertForbidden();
        $g->get(route('recruitment.openings.edit', $opening))->assertForbidden();
        $g->patch(route('recruitment.openings.update', $opening), $this->openingPayload())->assertForbidden();
        $g->patch(route('recruitment.applications.status', $application), ['status' => 'ditolak'])->assertForbidden();
        $g->get(route('recruitment.applications.convert', $application))->assertForbidden();
        $g->post(route('recruitment.applications.convert.store', $application), $this->convertPayload())->assertForbidden();

        $this->assertSame('baru', $application->fresh()->status);
    }

    public function test_users_without_the_recruitment_module_are_blocked(): void
    {
        $application = $this->applicant($this->opening());

        foreach (['manajer', 'aldora'] as $who) {
            $u = $this->actingAs($this->p[$who]);
            $u->get(route('recruitment.openings.index'))->assertForbidden();
            $u->get(route('recruitment.applications.index'))->assertForbidden();
            $u->get(route('recruitment.applications.show', $application))->assertForbidden();
        }

        Auth::logout();
        $this->get(route('recruitment.applications.index'))->assertRedirect('/login');
    }

    // ---- I7: ujung ke ujung ---------------------------------------------

    public function test_full_flow_from_public_application_to_a_working_employee_account(): void
    {
        $this->opening(['title' => 'Social Media Specialist', 'slug' => 'social-media-specialist']);

        // 1. Pelamar mengisi form publik.
        $this->post('/karir/social-media-specialist/lamar', [
            'name' => 'Citra Pelamar',
            'email' => 'citra@example.com',
            'phone' => '0813',
            'message' => 'Saya siap bergabung.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        // 2. HR melihatnya di pipeline dan memajukan status.
        $rania = $this->actingAs($this->p['hrd']);
        $rania->get(route('recruitment.applications.index'))->assertOk()->assertSee('Citra Pelamar');
        $application = JobApplication::where('email', 'citra@example.com')->firstOrFail();
        $rania->patch(route('recruitment.applications.status', $application), ['status' => 'interview', 'notes' => 'Jadwal Senin']);
        $rania->patch(route('recruitment.applications.status', $application), ['status' => 'ditawari']);

        // 3. Diterima → akun dibuat → bisa login.
        $rania->post(route('recruitment.applications.convert.store', $application), $this->convertPayload(['name' => 'Citra Pelamar', 'email' => 'citra@wsm.local']))
            ->assertRedirect(route('recruitment.applications.show', $application));

        $this->assertSame('diterima', $application->fresh()->status);

        $this->post('/logout');
        $this->post('/login', ['email' => 'citra@wsm.local', 'password' => 'rahasia123'])->assertRedirect(route('employee.home'));
        $this->get(route('employee.home'))->assertOk()->assertSee('Citra');
    }
}