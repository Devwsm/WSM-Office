<?php

namespace Tests\Feature;

use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\TeamGroup;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Selisih prototype #10 (2026-10-02): foto profil, warna tampilan pribadi,
 * kelompok tim, editor beranda publik, dan modal ganti password.
 */
class PersonalizationTest extends TestCase
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
        Storage::fake('public');
        $this->p = $this->company();
    }

    // --- Foto profil -------------------------------------------------

    public function test_karyawan_bisa_unggah_ganti_dan_hapus_foto_profil(): void
    {
        $user = $this->p['aldora'];

        $this->actingAs($user)
            ->post(route('employee.profile.avatar.update'), ['photo' => UploadedFile::fake()->image('a.jpg', 200, 200)])
            ->assertSessionHasNoErrors();

        $first = $user->fresh()->avatar_path;
        $this->assertNotNull($first);
        Storage::disk('local')->assertExists($first);

        $this->post(route('employee.profile.avatar.update'), ['photo' => UploadedFile::fake()->image('b.png', 200, 200)]);
        $second = $user->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);

        $this->get(route('avatar.show', $user))->assertOk();

        $this->delete(route('employee.profile.avatar.destroy'));
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($second);
        $this->get(route('avatar.show', $user))->assertNotFound();
    }

    public function test_foto_profil_menolak_file_bukan_gambar_dan_terlalu_besar(): void
    {
        $user = $this->p['aldora'];

        $this->actingAs($user)
            ->post(route('employee.profile.avatar.update'), ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        $this->post(route('employee.profile.avatar.update'), ['photo' => UploadedFile::fake()->image('big.jpg')->size(3000)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_foto_profil_tidak_bisa_dibuka_tanpa_login(): void
    {
        $user = $this->p['aldora'];
        $this->actingAs($user)->post(route('employee.profile.avatar.update'), ['photo' => UploadedFile::fake()->image('a.jpg')]);
        auth()->logout();

        $this->get(route('avatar.show', $user))->assertRedirect(route('login'));
    }

    // --- Warna pribadi -----------------------------------------------

    public function test_warna_pribadi_tersimpan_dan_dipakai_di_layout_hanya_untuk_pemiliknya(): void
    {
        $a = $this->p['aldora'];
        $b = $this->p['gepeng'];

        $this->actingAs($a)->patch(route('employee.profile.theme.update'), [
            'background' => '#112233',
            'text' => '#ffffff',
            'primary' => '#aa0000',
            'success' => '#00aa00',
            'attention' => '#aaaa00',
            'danger' => '#aa00aa',
            'leave' => '#00aaaa',
        ])->assertSessionHasNoErrors();

        $this->assertSame('#112233', $a->fresh()->themeColors()['background']);

        $this->get(route('employee.profile.index'))->assertSee('--color-cream:#112233', false);
        $this->actingAs($b)->get(route('employee.profile.index'))->assertDontSee('--color-cream:#112233', false);
    }

    public function test_warna_pribadi_menolak_nilai_bukan_hex_dan_reset_mengembalikan_bawaan(): void
    {
        $user = $this->p['aldora'];

        $this->actingAs($user)->patch(route('employee.profile.theme.update'), [
            'background' => 'red;background:url(x)',
            'text' => '#111111',
            'primary' => '#111111',
            'success' => '#27c84d',
            'attention' => '#deb92e',
            'danger' => '#f16c61',
            'leave' => '#b4ef4b',
        ])->assertSessionHasErrors('background');
        $this->assertNull($user->fresh()->theme_colors);

        $user->update(['theme_colors' => ['background' => '#112233']]);
        $this->assertTrue($user->fresh()->hasCustomTheme());

        $this->delete(route('employee.profile.theme.reset'));
        $this->assertFalse($user->fresh()->hasCustomTheme());
        $this->assertSame(User::THEME_DEFAULTS, $user->fresh()->themeColors());
    }

    public function test_tanpa_tema_tersimpan_layout_tidak_menambah_style_apa_pun(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('employee.profile.index'))->assertDontSee('--color-cream:', false);
    }

    // --- Modal ganti password ----------------------------------------

    public function test_ganti_password_lewat_modal_tetap_berfungsi_dan_modal_terbuka_lagi_saat_gagal(): void
    {
        $user = $this->p['aldora'];

        $page = $this->actingAs($user)->get(route('employee.profile.index'));
        $page->assertSee('password-modal-title', false)->assertSee('open: false', false);

        $this->from(route('employee.profile.index'))
            ->followingRedirects()
            ->patch(route('employee.profile.password'), ['current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSee('open: true', false)
            ->assertSee('Password saat ini salah.');

        $this->patch(route('employee.profile.password'), ['current_password' => 'password', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('passwordbaru1', $user->fresh()->password));
    }

    public function test_password_sementara_langsung_membuka_modal(): void
    {
        $user = $this->p['aldora'];
        $user->update(['must_change_password' => true]);

        $this->actingAs($user)->get(route('employee.profile.index'))->assertOk()->assertSee('open: true', false);
    }

    // --- Kelompok tim ------------------------------------------------

    public function test_owner_membuat_mengubah_dan_menghapus_kelompok_tim(): void
    {
        $owner = $this->p['owner'];

        $this->actingAs($owner)->post(route('owner.team-groups.store'), [
            'name' => 'WS TEAM',
            'color' => '#dce8ff',
            'member_ids' => [$this->p['aldora']->id, $this->p['gepeng']->id],
        ])->assertSessionHasNoErrors();

        $group = TeamGroup::firstOrFail();
        $this->assertCount(2, $group->members);

        $this->patch(route('owner.team-groups.update', $group), ['name' => 'WS TEAM', 'color' => '#ddf0e4', 'member_ids' => [$this->p['aldora']->id]]);
        $this->assertCount(1, $group->fresh()->members);

        $this->post(route('owner.team-groups.store'), ['name' => 'WS TEAM', 'color' => '#dce8ff'])->assertSessionHasErrors('name');

        $this->delete(route('owner.team-groups.destroy', $group));
        $this->assertSame(0, TeamGroup::count());
    }

    public function test_karyawan_biasa_tidak_boleh_membuka_kelompok_tim(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('owner.team-groups.index'))->assertForbidden();
        $this->post(route('owner.team-groups.store'), ['name' => 'X', 'color' => '#dce8ff'])->assertForbidden();
    }

    public function test_kelompok_yang_masih_dipakai_project_tidak_bisa_dihapus(): void
    {
        $group = TeamGroup::create(['name' => 'OPERATING', 'color' => '#ddf0e4']);
        Project::create(['slug' => 'p1', 'name' => 'P1', 'color' => '#3558f4', 'priority' => 'Medium', 'status' => 'On Development', 'visibility' => $group->visibilityKey(), 'created_by' => $this->p['owner']->id]);

        $this->actingAs($this->p['owner'])->delete(route('owner.team-groups.destroy', $group))->assertSessionHas('error');
        $this->assertSame(1, TeamGroup::count());
    }

    public function test_visibility_kelompok_hanya_terlihat_oleh_anggotanya_di_kalender(): void
    {
        $group = TeamGroup::create(['name' => 'WS TEAM', 'color' => '#dce8ff']);
        $group->members()->sync([$this->p['aldora']->id]);

        $project = Project::create(['slug' => 'rahasia', 'name' => 'Proyek Kelompok', 'color' => '#3558f4', 'priority' => 'Medium', 'status' => 'On Development', 'visibility' => $group->visibilityKey(), 'created_by' => $this->p['owner']->id]);
        WorkItem::create(['project_id' => $project->id, 'section' => 'General', 'item_no' => 1, 'title' => 'Tugas Kelompok', 'due_date' => '2026-09-22', 'progress' => 'Pending', 'priority' => 'Medium', 'created_by' => $this->p['owner']->id]);

        $url = route('employee.workTracker.calendar') . '?month=2026-09';

        $this->actingAs($this->p['aldora'])->get($url)->assertSee('Tugas Kelompok');
        $this->actingAs($this->p['gepeng'])->get($url)->assertDontSee('Tugas Kelompok');
    }

    public function test_form_project_menerima_kelompok_sebagai_visibility(): void
    {
        $group = TeamGroup::create(['name' => 'WS TEAM', 'color' => '#dce8ff']);
        $options = Project::visibilityOptions();

        $this->assertSame('Kelompok · WS TEAM', $options['group:' . $group->id]);
        $this->assertArrayNotHasKey('group:9999', $options);
    }

    // --- Beranda publik ----------------------------------------------

    public function test_beranda_publik_memakai_teks_bawaan_sampai_owner_mengubahnya(): void
    {
        $this->get(route('public.home'))
            ->assertSee('Musik, karya, dan tim di baliknya.')
            ->assertSee('Terbuka');

        $this->actingAs($this->p['owner'])->patch(route('owner.landing.update'), [
            'headline' => 'Judul Baru Beranda',
            'tagline' => 'Tagline baru dari Owner.',
            'cards' => [
                ['label' => 'A', 'title' => 'Satu', 'color' => '#111111'],
                ['label' => 'B', 'title' => 'Dua', 'color' => '#ffffff'],
                ['label' => 'C', 'title' => 'Tiga', 'color' => '#3558f4'],
                ['label' => 'D', 'title' => 'Empat', 'color' => '#b4ef4b'],
            ],
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->get(route('public.home'))
            ->assertSee('Judul Baru Beranda')
            ->assertSee('Tagline baru dari Owner.')
            ->assertSee('Empat')
            ->assertDontSee('Musik, karya, dan tim di baliknya.');

        $landing = OfficeSetting::current()->landing();
        $this->assertSame('#ffffff', $landing['cards'][0]['text']);
        $this->assertSame('#13220d', $landing['cards'][1]['text']);
    }

    public function test_editor_beranda_menolak_isian_tidak_valid_dan_reset_mengembalikan_bawaan(): void
    {
        $owner = $this->p['owner'];

        $this->actingAs($owner)->patch(route('owner.landing.update'), [
            'headline' => '',
            'tagline' => 'x',
            'cards' => [['label' => 'A', 'title' => 'B', 'color' => 'bukanwarna']],
        ])->assertSessionHasErrors(['headline', 'cards', 'cards.0.color']);

        OfficeSetting::query()->first()->update(['landing_content' => ['headline' => 'Custom']]);
        $this->assertTrue(OfficeSetting::current()->landing()['customized']);

        $this->delete(route('owner.landing.reset'));
        $this->assertFalse(OfficeSetting::current()->landing()['customized']);
        $this->assertSame('Musik, karya, dan tim di baliknya.', OfficeSetting::current()->landing()['headline']);
    }

    public function test_hanya_owner_atau_developer_yang_boleh_mengubah_beranda(): void
    {
        $this->actingAs($this->p['aldora'])->patch(route('owner.landing.update'), [])->assertForbidden();
        $this->delete(route('owner.landing.reset'))->assertForbidden();
    }
}