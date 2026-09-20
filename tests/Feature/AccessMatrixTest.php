<?php

namespace Tests\Feature;

use App\Models\DashboardAccess;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\OfficeSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian K — matriks hak akses (K1–K3) dan "smoke test" seluruh
 * halaman GET tanpa parameter untuk tiap akun seeder, memakai DatabaseSeeder
 * asli (data demo lengkap) supaya halaman dirender dengan data sungguhan.
 * Sekaligus menjaga agar `CreatesWsmFixtures::company()` tetap sama dengan seeder.
 */
class AccessMatrixTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array<string,User> */
    private array $u;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        Storage::fake('local');
        Storage::fake('public');
        // Sama dengan DatabaseSeeder, tetapi dipanggil langsung: DatabaseSeeder memakai
        // WithoutModelEvents yang mematikan emulasi kolom DATE MySQL di TestCase.
        $this->seed(OfficeSettingSeeder::class);
        $this->seed(DemoSeeder::class);

        foreach (['owner', 'kanaya', 'rania', 'aldora', 'gepeng'] as $name) {
            $this->u[$name] = User::where('email', "{$name}@wsm.local")->firstOrFail();
        }
    }

    public function test_seeded_accounts_have_the_documented_roles_and_module_access(): void
    {
        $access = fn(User $u) => $u->dashboardAccess()->pluck('level', 'module')->sortKeys()->all();

        $this->assertSame('owner', $this->u['owner']->role);
        $this->assertSame([], $access($this->u['owner']), 'Owner akses penuh otomatis, tanpa baris dashboard_access.');
        $this->assertSame(
            ['budget' => 'manage', 'contracts' => 'manage', 'it' => 'view', 'kpi' => 'manage', 'legal' => 'view', 'payroll' => 'manage', 'people' => 'view', 'royalty' => 'manage'],
            $access($this->u['kanaya']),
        );
        $this->assertSame(['kpi' => 'view', 'people' => 'manage', 'recruitment' => 'manage'], $access($this->u['rania']));
        $this->assertSame(['work' => 'manage'], $access($this->u['aldora']));
        $this->assertSame(['work' => 'view'], $access($this->u['gepeng']));

        $this->assertSame($this->u['owner']->id, $this->u['kanaya']->manager_id);
        $this->assertSame($this->u['kanaya']->id, $this->u['aldora']->manager_id);
        $this->assertSame($this->u['kanaya']->id, $this->u['gepeng']->manager_id);
    }

    public function test_test_fixtures_mirror_the_seeder_access_matrix(): void
    {
        $fromSeeder = collect($this->u)->map(fn(User $u) => $u->dashboardAccess()->pluck('level', 'module')->sortKeys()->all())->all();

        // company() dipakai semua tes lain; kalau seeder berubah, tes ini mengingatkan untuk menyamakannya.
        \Illuminate\Support\Facades\DB::table('users')->delete();
        \Illuminate\Support\Facades\DB::table('dashboard_access')->delete();
        $company = $this->company();

        $fromFixtures = [
            'owner' => [],
            'kanaya' => $company['manajer']->dashboardAccess()->pluck('level', 'module')->sortKeys()->all(),
            'rania' => $company['hrd']->dashboardAccess()->pluck('level', 'module')->sortKeys()->all(),
            'aldora' => $company['aldora']->dashboardAccess()->pluck('level', 'module')->sortKeys()->all(),
            'gepeng' => $company['gepeng']->dashboardAccess()->pluck('level', 'module')->sortKeys()->all(),
        ];

        $this->assertSame($fromSeeder, $fromFixtures);
    }

    /**
     * URL => status untuk [owner, kanaya, rania, aldora, gepeng].
     */
    public static function matrix(): array
    {
        return [
            '/app/home' => ['/app/home', [200, 200, 200, 200, 200]],
            '/app/riwayat' => ['/app/riwayat', [200, 200, 200, 200, 200]],
            '/app/profile' => ['/app/profile', [200, 200, 200, 200, 200]],
            '/dashboard' => ['/dashboard', [200, 200, 200, 200, 200]],
            '/owner/dashboard' => ['/owner/dashboard', [200, 403, 403, 403, 403]],
            '/owner/employees' => ['/owner/employees', [200, 403, 403, 403, 403]],
            '/owner/pengaturan-kantor' => ['/owner/pengaturan-kantor', [200, 403, 403, 403, 403]],
            '/absensi' => ['/absensi', [200, 200, 200, 403, 403]],
            '/persetujuan' => ['/persetujuan', [200, 200, 200, 403, 403]],
            '/dashboard/kpi' => ['/dashboard/kpi', [200, 200, 200, 403, 403]],
            '/dashboard/contracts' => ['/dashboard/contracts', [200, 200, 403, 403, 403]],
            '/dashboard/payroll' => ['/dashboard/payroll', [200, 200, 403, 403, 403]],
            '/dashboard/budget' => ['/dashboard/budget', [200, 200, 403, 403, 403]],
            '/dashboard/royalty' => ['/dashboard/royalty', [200, 200, 403, 403, 403]],
            '/dashboard/legal' => ['/dashboard/legal', [200, 200, 403, 403, 403]],
            '/dashboard/it' => ['/dashboard/it', [200, 200, 403, 403, 403]],
            '/dashboard/work' => ['/dashboard/work', [200, 403, 403, 200, 200]],
            '/dashboard/work/meetings' => ['/dashboard/work/meetings', [200, 403, 403, 200, 200]],
            '/rekrutmen/lowongan' => ['/rekrutmen/lowongan', [200, 403, 200, 403, 403]],
            '/rekrutmen/pelamar' => ['/rekrutmen/pelamar', [200, 403, 200, 403, 403]],
            '/dashboard/export-import' => ['/dashboard/export-import', [200, 200, 200, 200, 200]],
        ];
    }

    /** @dataProvider matrix */
    #[\PHPUnit\Framework\Attributes\DataProvider('matrix')]
    public function test_access_matrix(string $url, array $expected): void
    {
        foreach (array_keys($this->u) as $i => $name) {
            $this->actingAs($this->u[$name])->get($url)->assertStatus($expected[$i], "{$name} → {$url}");
        }

        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_module_pages_that_exist_only_as_placeholders_follow_the_same_rules(): void
    {
        $this->actingAs($this->u['kanaya'])->get('/dashboard/people')->assertOk();
        $this->actingAs($this->u['rania'])->get('/dashboard/recruitment')->assertOk();
        $this->actingAs($this->u['aldora'])->get('/dashboard/people')->assertForbidden();
    }

    /**
     * Smoke test: setiap halaman GET tanpa parameter URL harus dirender tanpa
     * error 500 untuk semua akun (200 / redirect / 403 saja).
     */
    public function test_no_parameterless_get_page_crashes_for_any_seeded_account(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())
            ->filter(fn($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{') && ! str_starts_with($r->uri(), 'up') && ! str_starts_with($r->uri(), 'storage'))
            ->map(fn($r) => '/' . ltrim($r->uri(), '/'))
            ->unique()->sort()->values();

        $this->assertGreaterThan(40, $uris->count());
        $checked = 0;

        foreach ($this->u as $name => $user) {
            $this->actingAs($user);

            foreach ($uris as $uri) {
                if ($uri === '/logout') {
                    continue;
                }

                $status = $this->get($uri)->getStatusCode();
                $this->assertContains($status, [200, 302, 403], "{$name} GET {$uri} → {$status}");
                $checked++;
            }
        }

        $this->assertGreaterThan(200, $checked);
    }

    public function test_public_pages_render_with_seeded_data(): void
    {
        auth()->logout();

        foreach (['/', '/tentang-kami', '/layanan', '/karir', '/kontak', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_seeded_demo_data_shows_up_in_key_pages(): void
    {
        $this->actingAs($this->u['aldora'])->get('/app/home')->assertOk();
        $this->actingAs($this->u['owner'])->get('/owner/dashboard')->assertOk();
        $this->actingAs($this->u['kanaya'])->get('/dashboard/kpi')->assertOk()->assertViewHas('kpis', fn($k) => $k->count() > 0);
        $this->actingAs($this->u['rania'])->get('/absensi')->assertOk()->assertViewHas('rows', fn($r) => $r->count() === 5);
        $this->assertGreaterThan(0, \App\Models\Memo::count());
        $this->assertGreaterThan(0, DashboardAccess::count());
    }
}