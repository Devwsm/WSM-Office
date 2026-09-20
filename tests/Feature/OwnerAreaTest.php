<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\DashboardAccess;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian F — area Owner (F1–F12): dashboard, manajemen
 * karyawan (tambah/edit/nonaktifkan/aktifkan), struktur organisasi, akses
 * dashboard per modul, pengaturan kantor, dan pesan kontak.
 * (F13, kunci dashboard, ada di AuthenticationTest.)
 */
class OwnerAreaTest extends TestCase
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

    // ---- akses: hanya Owner --------------------------------------------

    public function test_every_owner_page_is_forbidden_for_non_owners(): void
    {
        $urls = [
            ['get', route('owner.dashboard')],
            ['get', route('owner.employees.index')],
            ['get', route('owner.employees.create')],
            ['get', route('owner.employees.edit', $this->p['aldora'])],
            ['get', route('owner.organization')],
            ['get', route('owner.employees.access.edit', $this->p['aldora'])],
            ['patch', route('owner.employees.access.update', $this->p['aldora'])],
            ['get', route('owner.office-settings.edit')],
            ['patch', route('owner.office-settings.update')],
            ['get', route('owner.contact-messages.index')],
            ['post', route('owner.employees.store')],
            ['delete', route('owner.employees.destroy', $this->p['aldora'])],
        ];

        foreach (['manajer', 'hrd', 'aldora', 'gepeng'] as $who) {
            $this->actingAs($this->p[$who]);

            foreach ($urls as [$method, $url]) {
                $this->{$method}($url)->assertForbidden();
            }
        }

        $this->assertSame(5, User::count());
    }

    // ---- F1 -------------------------------------------------------------

    public function test_owner_dashboard_and_organization_pages_render(): void
    {
        $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertOk();
        $this->get(route('owner.organization'))->assertOk()->assertSee('Kanaya')->assertSee('Aldora');
    }

    // ---- F2: daftar & filter -------------------------------------------

    public function test_employee_list_filters_by_search_role_and_inactive_status(): void
    {
        $this->p['gepeng']->delete();
        $owner = $this->actingAs($this->p['owner']);

        $owner->get(route('owner.employees.index'))
            ->assertOk()
            ->assertViewHas('employees', fn($e) => $e->total() === 4 && ! $e->pluck('id')->contains($this->p['gepeng']->id));

        $owner->get(route('owner.employees.index', ['nonaktif' => 1]))
            ->assertViewHas('employees', fn($e) => $e->total() === 1 && $e->first()->is($this->p['gepeng']));

        $owner->get(route('owner.employees.index', ['role' => 'hrd']))
            ->assertViewHas('employees', fn($e) => $e->total() === 1 && $e->first()->is($this->p['hrd']));

        $owner->get(route('owner.employees.index', ['q' => 'kanaya']))
            ->assertViewHas('employees', fn($e) => $e->total() === 1 && $e->first()->is($this->p['manajer']));

        $owner->get(route('owner.employees.index', ['q' => 'wsm.local']))
            ->assertViewHas('employees', fn($e) => $e->total() === 4);
    }

    // ---- F3: tambah ----------------------------------------------------

    public function test_owner_can_create_an_employee_who_can_then_log_in(): void
    {
        $this->actingAs($this->p['owner'])->get(route('owner.employees.create'))->assertOk();

        $this->post(route('owner.employees.store'), $this->employeePayload())
            ->assertRedirect(route('owner.employees.index'))
            ->assertSessionHas('status', 'Karyawan baru berhasil ditambahkan.');

        $new = User::where('email', 'budi.baru@wsm.local')->firstOrFail();
        $this->assertSame('karyawan', $new->role);
        $this->assertSame($this->p['manajer']->id, $new->manager_id);
        $this->assertSame('Creative', $new->division);
        $this->assertEquals(5500000, $new->salary_base);
        $this->assertNotSame('rahasia123', $new->password, 'Password harus di-hash.');
        $this->assertTrue(Hash::check('rahasia123', $new->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Karyawan ditambahkan', 'actor_id' => $this->p['owner']->id]);

        $this->post('/logout');
        $this->post('/login', ['email' => 'budi.baru@wsm.local', 'password' => 'rahasia123'])
            ->assertRedirect(route('employee.home'));
        $this->assertAuthenticatedAs($new);
    }

    public function test_create_employee_validation(): void
    {
        $owner = $this->actingAs($this->p['owner']);
        $post = fn(array $o) => $owner->post(route('owner.employees.store'), $this->employeePayload($o));

        $post(['email' => 'kanaya@wsm.local'])->assertSessionHasErrors(['email' => 'Email ini sudah dipakai user lain.']);
        $post(['email' => 'bukan-email'])->assertSessionHasErrors('email');
        $post(['password' => 'pendek'])->assertSessionHasErrors('password');
        $post(['name' => ''])->assertSessionHasErrors('name');
        $post(['role' => 'presiden'])->assertSessionHasErrors('role');
        $post(['manager_id' => 9999])->assertSessionHasErrors(['manager_id' => 'Atasan yang dipilih tidak valid.']);
        $post(['annual_leave_entitlement' => 61])->assertSessionHasErrors('annual_leave_entitlement');
        $post(['target_hours_per_day' => 0])->assertSessionHasErrors('target_hours_per_day');
        $post(['salary_base' => -1])->assertSessionHasErrors('salary_base');
        $post(['join_date' => 'kemarin'])->assertSessionHasErrors('join_date');
        // Tanggal lahir di masa depan (salah ketik tahun) ditolak — bukan bug, lihat catatan proyek.
        $post(['birth_date' => '2099-01-01'])->assertSessionHasErrors('birth_date');

        $this->assertSame(5, User::count());
    }

    public function test_optional_fields_can_be_left_empty(): void
    {
        $this->actingAs($this->p['owner'])->post(route('owner.employees.store'), [
            'name' => 'Minimal',
            'email' => 'minimal@wsm.local',
            'password' => 'rahasia123',
            'role' => 'karyawan',
        ])->assertRedirect(route('owner.employees.index'));

        $this->assertNull(User::where('email', 'minimal@wsm.local')->firstOrFail()->manager_id);
    }

    public function test_email_of_a_deactivated_account_cannot_be_reused(): void
    {
        $this->p['gepeng']->delete();

        $this->actingAs($this->p['owner'])
            ->post(route('owner.employees.store'), $this->employeePayload(['email' => 'gepeng@wsm.local']))
            ->assertSessionHasErrors('email');
    }

    // ---- F4: edit ------------------------------------------------------

    public function test_edit_keeps_password_when_blank_and_changes_role_and_manager(): void
    {
        $oldHash = $this->p['gepeng']->password;

        $this->actingAs($this->p['owner'])->get(route('owner.employees.edit', $this->p['gepeng']))->assertOk();

        $this->patch(route('owner.employees.update', $this->p['gepeng']), $this->employeePayload([
            'name' => 'Gepeng Senior',
            'email' => 'gepeng@wsm.local',
            'password' => '',
            'role' => 'manajer',
            'manager_id' => $this->p['owner']->id,
        ]))->assertRedirect(route('owner.employees.index'))->assertSessionHas('status');

        $gepeng = $this->p['gepeng']->fresh();
        $this->assertSame('Gepeng Senior', $gepeng->name);
        $this->assertSame('manajer', $gepeng->role);
        $this->assertSame($this->p['owner']->id, $gepeng->manager_id);
        $this->assertSame($oldHash, $gepeng->password, 'Password kosong berarti tidak berubah.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Karyawan diperbarui']);
    }

    public function test_edit_with_a_new_password_changes_it(): void
    {
        $this->actingAs($this->p['owner'])->patch(route('owner.employees.update', $this->p['gepeng']), $this->employeePayload([
            'email' => 'gepeng@wsm.local',
            'password' => 'passwordbaru99',
        ]))->assertRedirect(route('owner.employees.index'));

        $this->assertTrue(Hash::check('passwordbaru99', $this->p['gepeng']->fresh()->password));
    }

    public function test_edit_validation_email_unique_ignores_self_and_manager_cannot_be_self(): void
    {
        $owner = $this->actingAs($this->p['owner']);
        $url = route('owner.employees.update', $this->p['gepeng']);

        // Email sendiri boleh tetap sama.
        $owner->patch($url, $this->employeePayload(['email' => 'gepeng@wsm.local', 'password' => '']))
            ->assertSessionHasNoErrors();

        // Email orang lain ditolak.
        $owner->patch($url, $this->employeePayload(['email' => 'aldora@wsm.local', 'password' => '']))
            ->assertSessionHasErrors('email');

        $owner->patch($url, $this->employeePayload(['email' => 'gepeng@wsm.local', 'password' => '', 'manager_id' => $this->p['gepeng']->id]))
            ->assertSessionHasErrors(['manager_id' => 'Karyawan tidak bisa jadi atasannya sendiri.']);

        $owner->patch($url, $this->employeePayload(['email' => 'gepeng@wsm.local', 'password' => 'pendek']))
            ->assertSessionHasErrors('password');
    }

    // ---- F5 / F6: nonaktif & aktif kembali -----------------------------

    public function test_owner_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->p['owner'])->delete(route('owner.employees.destroy', $this->p['owner']))
            ->assertSessionHas('error', 'Tidak bisa menonaktifkan akun sendiri.');

        $this->assertNotSoftDeleted($this->p['owner']);
    }

    public function test_deactivating_blocks_login_reparents_subordinates_and_logs_it(): void
    {
        $manajer = $this->p['manajer'];

        $this->actingAs($this->p['owner'])->delete(route('owner.employees.destroy', $manajer))
            ->assertSessionHas('status', 'Kanaya dinonaktifkan.');

        $this->assertSoftDeleted($manajer);
        // Bawahan Kanaya naik ke atasan Kanaya (Owner), tidak menggantung ke akun nonaktif.
        $this->assertSame($this->p['owner']->id, $this->p['aldora']->fresh()->manager_id);
        $this->assertSame($this->p['owner']->id, $this->p['gepeng']->fresh()->manager_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Karyawan dinonaktifkan']);

        $this->post('/logout');
        $this->post('/login', ['email' => 'kanaya@wsm.local', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_restoring_an_account_lets_the_user_log_in_again(): void
    {
        $this->p['gepeng']->delete();

        $this->actingAs($this->p['owner'])->post(route('owner.employees.restore', $this->p['gepeng']->id))
            ->assertSessionHas('status', 'Gepeng diaktifkan kembali.');

        $this->assertNotSoftDeleted($this->p['gepeng']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Karyawan diaktifkan kembali']);

        $this->post('/logout');
        $this->post('/login', ['email' => 'gepeng@wsm.local', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($this->p['gepeng']);
    }

    public function test_restoring_an_active_or_unknown_account_returns_404(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->post(route('owner.employees.restore', $this->p['aldora']->id))->assertNotFound();
        $owner->post(route('owner.employees.restore', 9999))->assertNotFound();
    }

    // ---- F8 / F9: akses dashboard --------------------------------------

    public function test_owner_access_page_explains_owner_already_has_full_access(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->get(route('owner.employees.access.edit', $this->p['owner']))
            ->assertRedirect(route('owner.employees.index'))
            ->assertSessionHas('error', fn($m) => str_contains($m, 'Owner otomatis punya akses penuh'));

        $owner->patch(route('owner.employees.access.update', $this->p['owner']), ['access' => ['kpi' => 'view']])
            ->assertRedirect(route('owner.employees.index'));

        $this->assertSame(0, DashboardAccess::where('user_id', $this->p['owner']->id)->count());
    }

    public function test_granting_and_revoking_module_access_takes_effect_immediately(): void
    {
        $gepeng = $this->p['gepeng'];

        // Belum punya akses KPI.
        $this->actingAs($gepeng)->get(route('dashboard.kpi.index'))->assertForbidden();

        $this->actingAs($this->p['owner'])->get(route('owner.employees.access.edit', $gepeng))->assertOk();
        $this->patch(route('owner.employees.access.update', $gepeng), ['access' => ['work' => 'view', 'kpi' => 'view']])
            ->assertRedirect(route('owner.employees.index'))
            ->assertSessionHas('status', 'Dashboard access Gepeng berhasil diperbarui.');

        $this->assertDatabaseHas('dashboard_access', ['user_id' => $gepeng->id, 'module' => 'kpi', 'level' => 'view', 'granted_by' => $this->p['owner']->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Dashboard access diubah']);

        // View: bisa lihat, tidak bisa kelola.
        $this->actingAs($gepeng->fresh())->get(route('dashboard.kpi.index'))->assertOk();
        $this->get(route('dashboard.kpi.create'))->assertForbidden();

        // Naik ke manage.
        $this->actingAs($this->p['owner'])->patch(route('owner.employees.access.update', $gepeng), ['access' => ['work' => 'view', 'kpi' => 'manage']]);
        $this->actingAs($gepeng->fresh())->get(route('dashboard.kpi.create'))->assertOk();

        // Cabut semua.
        $this->actingAs($this->p['owner'])->patch(route('owner.employees.access.update', $gepeng), ['access' => []]);
        $this->assertSame(0, DashboardAccess::where('user_id', $gepeng->id)->count());
        $this->actingAs($gepeng->fresh())->get(route('dashboard.kpi.index'))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['detail' => 'Akses Gepeng diubah oleh Whisnu Santika — semua modul dicabut.']);
    }

    public function test_access_update_rejects_unknown_level_and_ignores_unknown_module(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->patch(route('owner.employees.access.update', $this->p['gepeng']), ['access' => ['kpi' => 'admin']])
            ->assertSessionHasErrors('access.kpi');

        $owner->patch(route('owner.employees.access.update', $this->p['gepeng']), ['access' => ['modul_gaib' => 'view']])
            ->assertRedirect(route('owner.employees.index'));

        $this->assertDatabaseMissing('dashboard_access', ['module' => 'modul_gaib']);
    }

    public function test_all_ten_modules_can_be_assigned(): void
    {
        $access = collect(array_keys(DashboardAccess::MODULES))->mapWithKeys(fn($m) => [$m => 'view'])->all();
        $this->assertCount(10, $access);

        $this->actingAs($this->p['owner'])->patch(route('owner.employees.access.update', $this->p['gepeng']), ['access' => $access]);

        $this->assertSame(10, DashboardAccess::where('user_id', $this->p['gepeng']->id)->count());
    }

    // ---- F10 / F11: pengaturan kantor ----------------------------------

    private function settingPayload(array $overrides = []): array
    {
        return array_merge([
            'office_name' => 'WSM Office Baru',
            'address' => 'Jl. Contoh No. 1, Depok',
            'latitude' => -6.4,
            'longitude' => 106.9,
            'radius_meters' => 300,
            'geo_attendance_enabled' => '1',
            'enforce_radius' => '1',
            'work_start_time' => '09:00',
            'normal_end_time' => '18:00',
            'late_tolerance_minutes' => 10,
            'required_work_minutes' => 420,
            'shortage_deduction_rate' => 25000,
            'ceo_accent_color' => '#111111',
            'work_accent_color' => '#3558f4',
        ], $overrides);
    }

    public function test_office_settings_page_renders_and_saves_valid_input(): void
    {
        $this->actingAs($this->p['owner'])->get(route('owner.office-settings.edit'))->assertOk();

        $this->patch(route('owner.office-settings.update'), $this->settingPayload())
            ->assertSessionHas('status', 'Pengaturan kantor berhasil disimpan.');

        $setting = OfficeSetting::current();
        $this->assertSame('WSM Office Baru', $setting->office_name);
        $this->assertSame(300, (int) $setting->radius_meters);
        $this->assertSame('09:00:00', $setting->work_start_time);
        $this->assertSame('18:00:00', $setting->normal_end_time);
        $this->assertEquals(25000, $setting->shortage_deduction_rate);
        $this->assertTrue($setting->geo_attendance_enabled);
        $this->assertSame(1, OfficeSetting::count(), 'Harus tetap satu baris (singleton).');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Pengaturan kantor diubah']);
    }

    public function test_unchecked_toggles_are_saved_as_off(): void
    {
        $payload = $this->settingPayload();
        unset($payload['geo_attendance_enabled'], $payload['enforce_radius']);

        $this->actingAs($this->p['owner'])->patch(route('owner.office-settings.update'), $payload)->assertRedirect();

        $setting = OfficeSetting::current();
        $this->assertFalse($setting->geo_attendance_enabled);
        $this->assertFalse($setting->enforce_radius);
    }

    public function test_office_settings_validation(): void
    {
        $owner = $this->actingAs($this->p['owner']);
        $patch = fn(array $o) => $owner->patch(route('owner.office-settings.update'), $this->settingPayload($o));

        $patch(['radius_meters' => 9])->assertSessionHasErrors('radius_meters');
        $patch(['radius_meters' => 5001])->assertSessionHasErrors('radius_meters');
        $patch(['radius_meters' => 10])->assertSessionHasNoErrors();
        $patch(['radius_meters' => 5000])->assertSessionHasNoErrors();
        $patch(['latitude' => 91])->assertSessionHasErrors('latitude');
        $patch(['longitude' => -181])->assertSessionHasErrors('longitude');
        $patch(['office_name' => ''])->assertSessionHasErrors('office_name');
        $patch(['address' => ''])->assertSessionHasErrors('address');
        $patch(['work_start_time' => '9 pagi'])->assertSessionHasErrors('work_start_time');
        $patch(['work_start_time' => '18:00', 'normal_end_time' => '09:00'])
            ->assertSessionHasErrors(['normal_end_time' => 'Jam selesai window kerja normal harus lebih besar dari jam mulai.']);
        $patch(['work_start_time' => '09:00', 'normal_end_time' => '09:00'])->assertSessionHasErrors('normal_end_time');
        $patch(['late_tolerance_minutes' => 121])->assertSessionHasErrors('late_tolerance_minutes');
        $patch(['required_work_minutes' => 59])->assertSessionHasErrors('required_work_minutes');
        $patch(['required_work_minutes' => 961])->assertSessionHasErrors('required_work_minutes');
        $patch(['shortage_deduction_rate' => -1])->assertSessionHasErrors('shortage_deduction_rate');
        $patch(['ceo_accent_color' => 'merah'])->assertSessionHasErrors('ceo_accent_color');
        $patch(['work_accent_color' => '#12345'])->assertSessionHasErrors('work_accent_color');
    }

    public function test_changed_office_settings_are_used_by_the_very_next_clock_in(): void
    {
        $employee = $this->p['aldora'];
        $far = $this->farCoordinates();
        $clockIn = fn() => $this->actingAs($employee)->post(route('employee.attendance.clockIn'), ['mode' => 'kantor'] + $far);

        $clockIn()->assertSessionHas('status', fn($m) => str_contains($m, 'di luar radius'));

        // Owner memperbesar radius sampai 5 km lalu lokasi 11 km tetap di luar → naikkan titik kantor ke dekat lokasi.
        $this->actingAs($this->p['owner'])->patch(route('owner.office-settings.update'), $this->settingPayload([
            'latitude' => $far['lat'],
            'longitude' => $far['lng'],
            'radius_meters' => 100,
        ]))->assertRedirect();

        \App\Models\Attendance::query()->delete();

        $clockIn()->assertSessionHas('status', fn($m) => ! str_contains($m, 'di luar radius'));
        $this->assertTrue(\App\Models\Attendance::sole()->clock_in_within_radius);
    }

    // ---- F12: pesan kontak ---------------------------------------------

    public function test_owner_sees_contact_messages_and_marks_them_read(): void
    {
        $unread = ContactMessage::create(['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Halo WSM', 'status' => 'baru']);
        ContactMessage::create(['name' => 'Tono', 'email' => 'tono@example.com', 'message' => 'Sudah dibaca', 'status' => 'dibaca', 'read_at' => now()]);

        $this->assertSame(1, ContactMessage::unreadCount());

        $this->actingAs($this->p['owner'])->get(route('owner.contact-messages.index'))
            ->assertOk()
            ->assertSee('Halo WSM')
            ->assertSee('Sudah dibaca');

        $this->post(route('owner.contact-messages.markRead', $unread))
            ->assertSessionHas('status', 'Pesan dari Sari ditandai sudah dibaca.');

        $unread->refresh();
        $this->assertSame('dibaca', $unread->status);
        $this->assertNotNull($unread->read_at);
        $this->assertSame(0, ContactMessage::unreadCount());
    }

    public function test_public_contact_form_message_reaches_the_owner_inbox(): void
    {
        Auth::logout();
        $this->post('/kontak', ['name' => 'Rina', 'email' => 'rina@example.com', 'message' => 'Tanya kerja sama'])->assertRedirect();

        $this->actingAs($this->p['owner'])->get(route('owner.contact-messages.index'))
            ->assertOk()
            ->assertSee('Tanya kerja sama');
    }
}