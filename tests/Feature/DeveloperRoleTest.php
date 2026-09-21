<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\ExportImport\ImportPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Role `developer` (akses Tingkat 2): boleh masuk area /owner kecuali
 * Dashboard Access, boleh mengelola semua akun KECUALI akun Owner, tidak
 * boleh membuat Owner/Developer baru, melihat absensi semua orang di Rekap,
 * dan mengimport/mengekspor karyawan. Hak modul dashboard-nya tetap lewat
 * tabel dashboard_access; wewenang approval tetap hanya untuk bawahan langsung.
 */
class DeveloperRoleTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        Storage::fake('local');
        $this->p = $this->company();
        $this->dev = $this->makeDeveloper($this->p['owner']);
    }

    private function employeePayload(array $overrides = []): array
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
            'birth_date' => '1999-05-20',
            'salary_base' => 5500000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 35000,
        ], $overrides);
    }

    // ---- akses area /owner ----------------------------------------------

    public function test_developer_can_open_the_owner_area_pages(): void
    {
        $this->actingAs($this->dev);

        foreach (['owner.dashboard', 'owner.employees.index', 'owner.employees.create', 'owner.organization', 'owner.office-settings.edit', 'owner.contact-messages.index'] as $name) {
            $this->get(route($name))->assertOk();
        }

        $this->get(route('owner.employees.edit', $this->p['aldora']))->assertOk();
    }

    public function test_developer_cannot_open_dashboard_access(): void
    {
        $this->actingAs($this->dev);

        $this->get(route('owner.employees.access.edit', $this->p['aldora']))->assertForbidden();
        $this->patch(route('owner.employees.access.update', $this->p['aldora']), [])->assertForbidden();
        $this->assertSame('none', $this->p['aldora']->fresh()->accessLevel('payroll'));
    }

    public function test_developer_can_still_open_the_owner_area_when_only_given_a_role_and_no_modules(): void
    {
        $bare = $this->makeUser('developer', [], $this->p['owner']->id, ['email' => 'bare.dev@wsm.local']);

        $this->actingAs($bare)->get(route('owner.employees.index'))->assertOk();
        $this->get(route('dashboard.index'))->assertOk();
    }

    public function test_the_owner_area_stays_closed_to_every_other_role(): void
    {
        $everything = $this->makeUser('hrd', array_fill_keys(array_keys(\App\Models\DashboardAccess::MODULES), 'manage'), null, ['email' => 'hrd.full@wsm.local']);

        foreach ([$this->p['manajer'], $this->p['hrd'], $this->p['aldora'], $everything] as $user) {
            $this->actingAs($user)->get(route('owner.employees.index'))->assertForbidden();
            $this->get(route('owner.office-settings.edit'))->assertForbidden();
        }
    }

    public function test_developer_lands_on_the_app_home_after_login(): void
    {
        $this->post('/login', ['email' => $this->dev->email, 'password' => 'password'])
            ->assertRedirect(route('employee.home'));
    }

    public function test_developer_sees_the_owner_menus_in_the_dashboard_sidebar(): void
    {
        $this->actingAs($this->dev)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('owner.employees.index'), false)
            ->assertSee(route('owner.organization'), false)
            ->assertSee(route('owner.office-settings.edit'), false)
            ->assertSee(route('owner.contact-messages.index'), false);
    }

    public function test_employee_list_hides_the_access_button_from_developers_but_not_from_owners(): void
    {
        $access = route('owner.employees.access.edit', $this->p['aldora']);

        $this->actingAs($this->dev)->get(route('owner.employees.index'))->assertOk()->assertDontSee($access, false);
        $this->actingAs($this->p['owner'])->get(route('owner.employees.index'))->assertOk()->assertSee($access, false);
    }

    public function test_employee_list_shows_the_developer_badge_and_filter(): void
    {
        $this->actingAs($this->p['owner'])
            ->get(route('owner.employees.index', ['role' => 'developer']))
            ->assertOk()
            ->assertViewHas('employees', fn($e) => $e->total() === 1 && $e->first()->is($this->dev))
            ->assertSee('Developer');
    }

    // ---- kelola akun ----------------------------------------------------

    public function test_developer_can_create_karyawan_manajer_and_hrd_accounts(): void
    {
        $this->actingAs($this->dev);

        foreach (['karyawan', 'manajer', 'hrd'] as $i => $role) {
            $this->post(route('owner.employees.store'), $this->employeePayload(['role' => $role, 'email' => "baru{$i}@wsm.local"]))
                ->assertRedirect(route('owner.employees.index'))
                ->assertSessionHasNoErrors();

            $this->assertSame($role, User::where('email', "baru{$i}@wsm.local")->firstOrFail()->role);
        }

        $this->assertNotNull(AuditLog::where('action', 'Karyawan ditambahkan')->first());
    }

    public function test_developer_cannot_create_owner_or_developer_accounts(): void
    {
        $this->actingAs($this->dev);

        foreach (['owner', 'developer'] as $role) {
            $this->post(route('owner.employees.store'), $this->employeePayload(['role' => $role]))
                ->assertSessionHasErrors('role');
        }

        $this->assertNull(User::where('email', 'budi.baru@wsm.local')->first());
    }

    public function test_owner_can_create_developer_and_owner_accounts(): void
    {
        $this->actingAs($this->p['owner']);

        foreach (['developer', 'owner'] as $i => $role) {
            $this->post(route('owner.employees.store'), $this->employeePayload(['role' => $role, 'email' => "istimewa{$i}@wsm.local"]))
                ->assertSessionHasNoErrors();

            $this->assertSame($role, User::where('email', "istimewa{$i}@wsm.local")->firstOrFail()->role);
        }
    }

    public function test_role_dropdown_only_offers_what_the_user_may_assign(): void
    {
        $this->actingAs($this->dev)->get(route('owner.employees.create'))
            ->assertOk()
            ->assertSee('value="karyawan"', false)
            ->assertSee('value="manajer"', false)
            ->assertSee('value="hrd"', false)
            ->assertDontSee('value="owner"', false)
            ->assertDontSee('value="developer"', false);

        $this->actingAs($this->p['owner'])->get(route('owner.employees.create'))
            ->assertOk()
            ->assertSee('value="owner"', false)
            ->assertSee('value="developer"', false);
    }

    public function test_developer_cannot_view_edit_deactivate_or_restore_an_owner_account(): void
    {
        $owner = $this->p['owner'];
        $this->actingAs($this->dev);

        $this->get(route('owner.employees.edit', $owner))->assertForbidden();
        $this->patch(route('owner.employees.update', $owner), $this->employeePayload(['email' => $owner->email, 'name' => 'Diretas', 'role' => 'karyawan']))->assertForbidden();
        $this->delete(route('owner.employees.destroy', $owner))->assertForbidden();

        $this->assertSame('Whisnu Santika', $owner->fresh()->name);
        $this->assertSame('owner', $owner->fresh()->role);
        $this->assertNull($owner->fresh()->deleted_at);

        // Owner kedua yang sudah nonaktif juga tidak bisa dihidupkan oleh Developer.
        $second = $this->makeUser('owner', [], null, ['email' => 'owner2@wsm.local']);
        $second->delete();
        $this->post(route('owner.employees.restore', $second->id))->assertForbidden();
        $this->assertTrue(User::withTrashed()->find($second->id)->trashed());
    }

    public function test_the_owner_list_shows_a_note_instead_of_buttons_to_developers(): void
    {
        $this->actingAs($this->dev)
            ->get(route('owner.employees.index'))
            ->assertOk()
            ->assertSee('Hanya Owner yang bisa mengelola akun ini')
            ->assertDontSee(route('owner.employees.edit', $this->p['owner']), false);
    }

    public function test_developer_can_edit_deactivate_and_restore_regular_accounts(): void
    {
        $this->actingAs($this->dev);
        $aldora = $this->p['aldora'];

        $this->patch(route('owner.employees.update', $aldora), $this->employeePayload(['email' => $aldora->email, 'name' => 'Aldora Baru', 'role' => 'manajer']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Aldora Baru', $aldora->fresh()->name);
        $this->assertSame('manajer', $aldora->fresh()->role);

        $this->delete(route('owner.employees.destroy', $aldora))->assertRedirect();
        $this->assertTrue(User::withTrashed()->find($aldora->id)->trashed());

        $this->post(route('owner.employees.restore', $aldora->id))->assertRedirect();
        $this->assertNull(User::find($aldora->id)->deleted_at);
    }

    public function test_developer_cannot_promote_anyone_to_owner_or_developer_by_editing(): void
    {
        $this->actingAs($this->dev);
        $aldora = $this->p['aldora'];

        foreach (['owner', 'developer'] as $role) {
            $this->patch(route('owner.employees.update', $aldora), $this->employeePayload(['email' => $aldora->email, 'role' => $role]))
                ->assertSessionHasErrors('role');
        }

        $this->assertSame('karyawan', $aldora->fresh()->role);
    }

    public function test_developer_can_edit_another_developer_but_keeps_the_developer_role_option(): void
    {
        $other = $this->makeDeveloper($this->p['owner'], ['email' => 'dev2@wsm.local', 'name' => 'Dev Dua']);
        $this->actingAs($this->dev);

        $this->get(route('owner.employees.edit', $other))->assertOk()->assertSee('value="developer"', false);

        $this->patch(route('owner.employees.update', $other), $this->employeePayload(['email' => $other->email, 'name' => 'Dev Dua Baru', 'role' => 'developer']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Dev Dua Baru', $other->fresh()->name);
        $this->assertSame('developer', $other->fresh()->role);
    }

    public function test_developer_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->dev)->delete(route('owner.employees.destroy', $this->dev))->assertSessionHas('error');

        $this->assertNull($this->dev->fresh()->deleted_at);
    }

    // ---- matriks aturan di model ----------------------------------------

    public function test_account_rules_matrix(): void
    {
        $owner = $this->p['owner'];
        $dev = $this->dev;
        $dev2 = $this->makeDeveloper($owner, ['email' => 'dev2@wsm.local']);
        $aldora = $this->p['aldora'];
        $itManager = $this->makeUser('hrd', ['it' => 'manage'], null, ['email' => 'it.hrd@wsm.local']);

        // kelola akun (menu Karyawan)
        $this->assertTrue($owner->canManageAccount($owner));
        $this->assertTrue($owner->canManageAccount($dev));
        $this->assertTrue($dev->canManageAccount($aldora));
        $this->assertTrue($dev->canManageAccount($dev2));
        $this->assertFalse($dev->canManageAccount($owner));
        $this->assertFalse($this->p['hrd']->canManageAccount($aldora));

        // reset password (dashboard IT)
        $this->assertFalse($owner->canResetPasswordOf($owner));
        $this->assertTrue($owner->canResetPasswordOf($dev));
        $this->assertTrue($owner->canResetPasswordOf($aldora));
        $this->assertFalse($dev->canResetPasswordOf($dev));
        $this->assertFalse($dev->canResetPasswordOf($owner));
        $this->assertTrue($dev->canResetPasswordOf($dev2));
        $this->assertTrue($dev->canResetPasswordOf($aldora));
        $this->assertFalse($itManager->canResetPasswordOf($owner));
        $this->assertFalse($itManager->canResetPasswordOf($dev));
        $this->assertTrue($itManager->canResetPasswordOf($aldora));

        // role yang boleh dipilih
        $this->assertSame(['karyawan', 'manajer', 'hrd', 'developer', 'owner'], $owner->assignableRoles());
        $this->assertSame(['karyawan', 'manajer', 'hrd'], $dev->assignableRoles());
        $this->assertSame(['karyawan', 'manajer', 'hrd'], $dev->assignableRoles($aldora));
        $this->assertSame(['karyawan', 'manajer', 'hrd', 'developer'], $dev->assignableRoles($dev2));
        $this->assertSame([], $aldora->assignableRoles());

        $this->assertSame('Developer', $dev->roleLabel());
        $this->assertTrue($dev->isOwnerOrDeveloper());
        $this->assertFalse($this->p['manajer']->isOwnerOrDeveloper());
    }

    // ---- rekap absensi & approval ---------------------------------------

    public function test_developer_sees_every_employee_in_the_attendance_recap(): void
    {
        $all = collect($this->p)->pluck('id')->push($this->dev->id)->sort()->values()->all();

        $ids = $this->actingAs($this->dev)
            ->get(route('attendance.recap.index'))
            ->assertOk()
            ->viewData('rows')
            ->pluck('user.id')->sort()->values()->all();

        $this->assertSame($all, $ids);
    }

    public function test_developer_only_decides_requests_of_direct_subordinates(): void
    {
        $mine = $this->makeUser('karyawan', [], $this->dev->id, ['email' => 'anak.buah@wsm.local']);
        $request = fn(User $u) => LeaveRequest::create([
            'user_id' => $u->id,
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-22',
            'work_days' => 1,
            'reason' => 'Liburan',
            'status' => 'pending',
        ]);
        $notMine = $request($this->p['aldora']);
        $ownLeave = $request($mine);

        $this->actingAs($this->dev)->post(route('approval.leave.approve', $notMine))->assertForbidden();
        $this->assertSame('pending', $notMine->fresh()->status);

        $this->post(route('approval.leave.approve', $ownLeave))->assertRedirect();
        $this->assertSame('disetujui', $ownLeave->fresh()->status);
    }

    // ---- export & import karyawan ---------------------------------------

    private function csv(array $rows): UploadedFile
    {
        $headings = ['nama', 'email', 'password', 'role', 'divisi', 'jabatan', 'tanggal_masuk', 'jatah_cuti', 'tanggal_lahir', 'gaji_pokok', 'target_jam_per_hari', 'tarif_lembur_flat'];
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headings);
        foreach ($rows as $row) {
            fputcsv($stream, array_pad($row, count($headings), ''));
        }
        rewind($stream);

        return UploadedFile::fake()->createWithContent('karyawan.csv', stream_get_contents($stream));
    }

    private function previewEmployees(User $user, array $rows)
    {
        return $this->actingAs($user)->post(route('dashboard.export-import.import.preview', ['key' => 'employees']), ['file' => $this->csv($rows)]);
    }

    public function test_the_employee_card_is_visible_to_developers_and_owners_only(): void
    {
        $keys = fn(User $u) => collect($this->actingAs($u)->get(route('dashboard.export-import.index'))->assertOk()->viewData('catalog'))->keys();

        $this->assertTrue($keys($this->dev)->contains('employees'));
        $this->assertTrue($keys($this->p['owner'])->contains('employees'));

        $everything = $this->makeUser('hrd', array_fill_keys(array_keys(\App\Models\DashboardAccess::MODULES), 'manage'), null, ['email' => 'hrd.full@wsm.local']);
        $this->assertFalse($keys($everything)->contains('employees'));
        $this->actingAs($everything)->get(route('dashboard.export-import.import.show', ['key' => 'employees']))->assertForbidden();
        $this->actingAs($everything)->get(route('dashboard.export-import.preview', ['key' => 'employees', 'format' => 'excel']))->assertForbidden();
    }

    public function test_developer_can_export_employees(): void
    {
        $this->actingAs($this->dev)
            ->get(route('dashboard.export-import.download', ['key' => 'employees', 'format' => 'excel']))
            ->assertOk();
    }

    public function test_developer_import_accepts_regular_roles_and_rejects_owner_and_developer_rows(): void
    {
        $preview = $this->previewEmployees($this->dev, [
            ['Karyawan Import', 'k1@wsm.local', 'rahasia123', 'karyawan'],
            ['Manajer Import', 'k2@wsm.local', 'rahasia123', 'manajer'],
            ['Owner Palsu', 'k3@wsm.local', 'rahasia123', 'owner'],
            ['Developer Palsu', 'k4@wsm.local', 'rahasia123', 'developer'],
        ])->assertOk();

        $preview->assertViewHas('valid', fn($v) => count($v) === 2)->assertViewHas('invalid', fn($i) => count($i) === 2);

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'employees']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertNotNull(User::where('email', 'k1@wsm.local')->first());
        $this->assertNotNull(User::where('email', 'k2@wsm.local')->first());
        $this->assertNull(User::where('email', 'k3@wsm.local')->first());
        $this->assertNull(User::where('email', 'k4@wsm.local')->first());
    }

    public function test_owner_import_may_create_developer_and_owner_accounts(): void
    {
        $preview = $this->previewEmployees($this->p['owner'], [
            ['Dev Import', 'd1@wsm.local', 'rahasia123', 'developer'],
            ['Owner Import', 'o1@wsm.local', 'rahasia123', 'owner'],
        ])->assertOk();

        $preview->assertViewHas('valid', fn($v) => count($v) === 2);
        $this->post(route('dashboard.export-import.import.commit', ['key' => 'employees']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertSame('developer', User::where('email', 'd1@wsm.local')->firstOrFail()->role);
        $this->assertSame('owner', User::where('email', 'o1@wsm.local')->firstOrFail()->role);
    }

    public function test_commit_refuses_privileged_rows_even_if_they_were_staged_by_a_developer(): void
    {
        $token = app(ImportPreviewService::class)->stage($this->dev, 'employees', [
            'valid' => [['row' => 2, 'data' => ['name' => 'Menyusup', 'email' => 'menyusup@wsm.local', 'password' => 'rahasia123', 'role' => 'owner']]],
            'invalid' => [],
            'headings' => [],
        ]);

        $this->actingAs($this->dev)
            ->post(route('dashboard.export-import.import.commit', ['key' => 'employees']), ['token' => $token])
            ->assertForbidden();

        $this->assertNull(User::where('email', 'menyusup@wsm.local')->first());
    }

    public function test_import_with_default_password_forces_a_password_change_but_a_manual_password_does_not(): void
    {
        $preview = $this->previewEmployees($this->p['owner'], [
            ['Default Pass', 'default@wsm.local', '', 'karyawan'],
            ['Manual Pass', 'manual@wsm.local', 'rahasia123', 'karyawan'],
        ])->assertOk();

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'employees']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertTrue(User::where('email', 'default@wsm.local')->firstOrFail()->must_change_password);
        $this->assertFalse(User::where('email', 'manual@wsm.local')->firstOrFail()->must_change_password);
    }
}