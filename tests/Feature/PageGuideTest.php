<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PageGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Tombol "? Panduan" + modal panduan di setiap halaman dashboard
 * (config/page_guides.php, App\Support\PageGuide, partials/page-guide).
 *
 * Tes ini juga jadi pagar pengaman: halaman dashboard baru yang lupa
 * didaftarkan panduannya akan membuat tes "semua halaman punya panduan"
 * gagal.
 */
class PageGuideTest extends TestCase
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

    public function test_setiap_route_di_peta_menunjuk_ke_panduan_yang_ada_dan_route_yang_valid(): void
    {
        $routeNames = collect(Route::getRoutes()->getRoutesByName())->keys();

        foreach (config('page_guides.routes') as $pattern => $key) {
            $this->assertNotNull(
                config("page_guides.guides.{$key}"),
                "Route '{$pattern}' menunjuk ke panduan '{$key}' yang tidak ada."
            );

            $this->assertTrue(
                $routeNames->contains(fn(string $name) => \Illuminate\Support\Str::is($pattern, $name)),
                "Pola route '{$pattern}' tidak cocok dengan route mana pun (salah ketik?)."
            );
        }
    }

    public function test_tidak_ada_panduan_yatim_dan_semua_isi_panduan_lengkap(): void
    {
        $usedKeys = array_unique(array_values(config('page_guides.routes')));

        foreach (config('page_guides.guides') as $key => $guide) {
            $this->assertContains($key, $usedKeys, "Panduan '{$key}' tidak dipakai route mana pun.");
            $this->assertNotSame('', trim($guide['title'] ?? ''), "Panduan '{$key}' tanpa judul.");
            $this->assertNotSame('', trim($guide['summary'] ?? ''), "Panduan '{$key}' tanpa ringkasan.");
            $this->assertNotEmpty($guide['sections'] ?? [], "Panduan '{$key}' tanpa isi.");

            foreach ($guide['sections'] as $heading => $items) {
                $this->assertIsString($heading, "Panduan '{$key}': heading harus string.");
                $this->assertNotEmpty($items, "Panduan '{$key}', bagian '{$heading}' kosong.");

                foreach ($items as $item) {
                    if (is_array($item)) {
                        $this->assertCount(2, $item, "Panduan '{$key}': butir [label, teks] harus 2 elemen.");
                        $this->assertIsString($item[0]);
                        $this->assertIsString($item[1]);
                    } else {
                        $this->assertIsString($item, "Panduan '{$key}': butir harus string atau [label, teks].");
                    }
                }
            }
        }
    }

    public function test_pola_route_dicocokkan_dengan_benar(): void
    {
        $this->assertSame('work-tracker', PageGuide::keyForRoute('dashboard.work.tracker.index'));
        $this->assertSame('work-tracker', PageGuide::keyForRoute('dashboard.work.tracker.projects.store'));
        $this->assertSame('memo-form', PageGuide::keyForRoute('dashboard.work.edit'));
        $this->assertSame('employee-form', PageGuide::keyForRoute('owner.employees.create'));
        $this->assertSame('application-convert', PageGuide::keyForRoute('recruitment.applications.convert'));
        $this->assertSame('application-convert', PageGuide::keyForRoute('recruitment.applications.convert.store'));
        $this->assertSame('import-preview', PageGuide::keyForRoute('dashboard.export-import.import.preview'));
        $this->assertNull(PageGuide::keyForRoute('employee.home'));
        $this->assertNull(PageGuide::keyForRoute('public.home'));
        $this->assertNull(PageGuide::keyForRoute(null));
    }

    public function test_semua_halaman_dashboard_tanpa_parameter_punya_panduan(): void
    {
        $this->actingAs($this->p['owner']);

        $checked = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }

            // Hanya area yang memakai layouts.app.
            if (! preg_match('/^(owner\.|dashboard\.(?!lock\.)|attendance\.recap\.|approval\.|recruitment\.)/', $name)) {
                continue;
            }

            $response = $this->get('/' . ltrim($route->uri(), '/'));

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            $html = $response->getContent();

            // Bukan halaman layouts.app (mis. respons file/unduhan) -> lewati.
            if (! str_contains($html, 'sidebarOpen')) {
                continue;
            }

            $checked++;

            $response->assertSee('data-page-guide=', false);
            $response->assertSee('Panduan halaman');
        }

        $this->assertGreaterThan(15, $checked, 'Terlalu sedikit halaman yang terperiksa — cek filter tes.');
    }

    public function test_modal_menampilkan_judul_ringkasan_dan_isi_panduan(): void
    {
        $this->actingAs($this->p['owner'])
            ->get(route('dashboard.work.tracker.index'))
            ->assertOk()
            ->assertSee('data-page-guide="work-tracker"', false)
            ->assertSee('id="page-guide-dialog"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('Work Tracker')
            ->assertSee('Papan kanban semua task lintas project dan PIC')
            ->assertSee('Kelola Projects')
            ->assertSee('Mengerti, tutup');
    }

    public function test_label_akses_mengikuti_level_user(): void
    {
        $viewer = $this->makeUser('karyawan', ['work' => 'view']);
        $manager = $this->makeUser('karyawan', ['work' => 'manage']);

        $this->actingAs($viewer)
            ->get(route('dashboard.work.tracker.index'))
            ->assertOk()
            ->assertSee('Akses kamu: View (hanya lihat)');

        $this->actingAs($manager)
            ->get(route('dashboard.work.tracker.index'))
            ->assertOk()
            ->assertSee('Akses kamu: Manage (bisa lihat &amp; ubah data)', false);
    }

    public function test_halaman_khusus_owner_memakai_label_khusus_owner(): void
    {
        $this->actingAs($this->p['owner'])
            ->get(route('owner.employees.access.edit', $this->p['aldora']))
            ->assertOk()
            ->assertSee('Khusus Owner');
    }

    public function test_halaman_area_owner_lainnya_memakai_label_owner_dan_developer(): void
    {
        $this->actingAs($this->p['owner'])
            ->get(route('owner.office-settings.edit'))
            ->assertOk()
            ->assertSee('Owner &amp; Developer', false)
            ->assertDontSee('Khusus Owner');
    }

    public function test_halaman_tanpa_panduan_tidak_menampilkan_tombol(): void
    {
        $this->actingAs($this->p['aldora'])
            ->get(route('employee.home'))
            ->assertOk()
            ->assertDontSee('data-page-guide=', false);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('data-page-guide=', false);
    }

    public function test_view_boleh_memaksa_panduan_lewat_variabel_pageGuide(): void
    {
        $guide = PageGuide::resolve('route.tidak.dikenal', $this->p['owner'], 'payroll');

        $this->assertNotNull($guide);
        $this->assertSame('Payroll Overview', $guide['title']);
        $this->assertNull(PageGuide::resolve('route.tidak.dikenal', $this->p['owner'], 'kunci-tidak-ada'));
    }

    public function test_teks_panduan_di_halaman_di_escape(): void
    {
        config(['page_guides.guides.work-tracker.summary' => '<script>alert(1)</script>']);

        $this->actingAs($this->p['owner'])
            ->get(route('dashboard.work.tracker.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}