<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Timeline Calendar gaya Samsung (2026-10-21): tahun = 12 bulan mini yang bisa
 * diklik ke bulan itu, bulan = grid muat layar HP, popup harian tanpa tombol tambah.
 * Aldora = work `manage`, Gepeng = work `view`.
 */
class CalendarSamsungStyleTest extends TestCase
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

    private function project(string $name, array $o = []): Project
    {
        return Project::create(array_merge([
            'name' => $name,
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function item(Project $project, string $title, array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $project->id,
            'section' => 'S',
            'item_no' => 1,
            'title' => $title,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    public function test_year_view_has_twelve_month_cards_that_open_that_month(): void
    {
        $res = $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk();

        $res->assertSee('data-year-months', false);
        foreach (range(1, 12) as $m) {
            $res->assertSee('data-year-month="' . $m . '"', false);
            $res->assertSee(route('dashboard.work.calendar', ['month' => sprintf('2026-%02d', $m)]), false);
        }
    }

    public function test_month_cards_keep_project_and_pic_filters(): void
    {
        $a = $this->project('Alpha');

        $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026, 'project' => $a->id]))
            ->assertOk()
            ->assertSee('href="' . e(route('dashboard.work.calendar', ['month' => '2026-03', 'project' => $a->id])) . '"', false);
    }

    public function test_days_with_deadlines_get_the_project_color_and_a_count(): void
    {
        $a = $this->project('Alpha', ['color' => '#336699']);
        $this->item($a, 'Tonggak', ['due_date' => '2026-03-10']);
        $this->item($a, 'Tonggak dua', ['item_no' => 2, 'due_date' => '2026-03-10']);

        $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk()
            ->assertSee('color-mix(in srgb, #336699 38%, #fff)', false)
            ->assertSee(' · 2 item"', false); // tooltip kartu bulan Maret
    }

    public function test_year_cards_ignore_items_of_other_years_and_restricted_sections(): void
    {
        $a = $this->project('Alpha', ['color' => '#336699']);
        $this->item($a, 'Tahun lalu', ['due_date' => '2025-03-10']);
        $this->item($a, 'Rahasia', ['item_no' => 2, 'section' => 'RAHASIA', 'due_date' => '2026-07-01']);
        \App\Models\ProjectSection::where('project_id', $a->id)->where('name', 'RAHASIA')->firstOrFail()
            ->viewers()->sync([$this->p['hrd']->id]);

        // Aldora tidak termasuk daftar visibility -> bulan Juli tidak berwarna / tidak dihitung.
        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk()
            ->assertDontSee('color-mix(in srgb, #336699 38%, #fff)', false)
            ->assertDontSee(' · 1 item"', false);

        // Owner selalu melihat semua section (pengecualian visibility).
        $this->actingAs($this->p['owner'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk()
            ->assertSee('color-mix(in srgb, #336699 38%, #fff)', false);
    }

    public function test_project_timeline_is_still_below_the_month_cards(): void
    {
        $a = $this->project('Alpha', ['start_date' => '2026-06-01', 'end_date' => '2026-10-31']);

        $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.calendar', ['view' => 'year', 'year' => 2026]))
            ->assertOk()
            ->assertSee('data-year-row="' . $a->id . '"', false)
            ->assertSee('153 hari');
    }

    public function test_month_view_fits_the_screen_and_daily_popup_has_no_add_button(): void
    {
        $a = $this->project('Alpha');
        $this->item($a, 'Judul panjang sekali supaya terpotong di HP', ['due_date' => '2026-09-24']);

        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.calendar', ['month' => '2026-09']))
            ->assertOk()
            ->assertDontSee('Geser kalender ke samping')   // tidak ada lagi scroll samping
            ->assertDontSee('min-w-215', false)
            ->assertSee('Agenda Harian')                   // popup harian tetap ada
            ->assertDontSee('Tambah pd')                   // tanpa tombol "tambah di hari ini"
            ->assertSee('Judul panjang sekali supaya terpotong di HP'); // lengkap di tooltip/popup
    }
}