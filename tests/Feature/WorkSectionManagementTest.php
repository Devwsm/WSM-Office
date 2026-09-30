<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Kelola section di dalam project (2026-09-30): warna, urutan (↑ ↓), tambah
 * section kosong, hapus (section berisi item -> ketik nama). Padanan
 * prototype v24-v28. Aldora = work `manage`, Gepeng = work `view`.
 */
class WorkSectionManagementTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
        $this->project = $this->makeProject('Album Q3');
    }

    private function makeProject(string $name): Project
    {
        return Project::create([
            'name' => $name,
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ]);
    }

    private function item(string $section, array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $this->project->id,
            'section' => $section,
            'item_no' => 1,
            'title' => 'Item ' . $section,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    /** @return list<string> nama section project ini menurut urutan tersimpan */
    private function order(?Project $project = null): array
    {
        return ProjectSection::orderedFor(($project ?? $this->project)->id)->pluck('name')->all();
    }

    private function section(string $name, ?Project $project = null): ProjectSection
    {
        return ProjectSection::where('project_id', ($project ?? $this->project)->id)->where('name', $name)->firstOrFail();
    }

    private function board(?User $as = null, array $query = [])
    {
        return $this->actingAs($as ?? $this->p['aldora'])->get(route('dashboard.work.tracker.index', $query));
    }

    // ---- Pendaftaran otomatis & urutan awal ------------------------------

    public function test_new_sections_register_in_creation_order_from_any_write_path(): void
    {
        $this->item('CONTRACT');
        $this->item('SONG');
        $this->item('CONTRACT', ['item_no' => 2]);       // section sama: tidak dobel
        $this->item('LEGAL', ['project_id' => null]);    // tanpa project: tidak didaftarkan
        $this->item('');                                  // tanpa section: tidak didaftarkan

        $this->assertSame(['CONTRACT', 'SONG'], $this->order());
        $this->assertSame(2, ProjectSection::count());
    }

    public function test_moving_an_item_to_a_new_section_registers_it(): void
    {
        $item = $this->item('CONTRACT');
        $item->update(['section' => 'MARKETING']);

        $this->assertSame(['CONTRACT', 'MARKETING'], $this->order());
    }

    public function test_sync_missing_backfills_rows_for_items_written_without_model_events(): void
    {
        $now = now();
        foreach (['B-SECTION', 'A-SECTION'] as $i => $name) {
            DB::table('work_items')->insert([
                'project_id' => $this->project->id,
                'section' => $name,
                'item_no' => 1,
                'title' => 'x',
                'progress' => 'Pending',
                'created_by' => $this->p['owner']->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->assertSame(0, ProjectSection::count());

        $this->board()->assertOk();

        // Urutan = item pertama yang dibuat (bukan alfabet).
        $this->assertSame(['B-SECTION', 'A-SECTION'], $this->order());
    }

    public function test_default_color_matches_the_old_name_based_color_and_board_uses_it(): void
    {
        $this->item('CONTRACT');
        $expected = ProjectSection::DEFAULT_PALETTE[crc32('CONTRACT') % 11];

        $this->assertNull($this->section('CONTRACT')->color);
        $this->assertSame($expected, $this->section('CONTRACT')->effectiveColor());
        $this->board()->assertSee('background:' . $expected, false);
    }

    // ---- Tampilan board --------------------------------------------------

    public function test_board_lists_sections_in_managed_order_and_shows_empty_ones(): void
    {
        $this->item('CONTRACT');
        $this->item('SONG');
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'Kosong Dulu']);

        $names = $this->board()->assertOk()->viewData('cards')->first()['sections']->pluck('name')->all();
        $this->assertSame(['CONTRACT', 'SONG', 'Kosong Dulu'], $names);

        $this->board()->assertSee('Section ini masih kosong.');
    }

    public function test_filtering_hides_empty_sections_and_reorder_delete_controls(): void
    {
        $this->item('CONTRACT', ['progress' => 'Done']);
        $this->item('SONG');
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'Kosong']);

        $response = $this->board(null, ['progress' => 'Done'])->assertOk();

        $this->assertSame(['CONTRACT'], $response->viewData('cards')->first()['sections']->pluck('name')->all());
        $response->assertDontSee('Pindah section ke atas')->assertDontSee('title="Hapus section"', false);
        $response->assertSee('aria-label="Warna section CONTRACT"', false);   // warna tetap bisa diubah

        $this->board()->assertSee('Pindah section ke atas')->assertSee('title="Hapus section"', false);
    }

    public function test_view_only_user_sees_sections_without_any_controls(): void
    {
        $this->item('CONTRACT');

        $this->board($this->p['gepeng'])->assertOk()->assertSee('CONTRACT')
            ->assertDontSee('aria-label="Warna section', false)
            ->assertDontSee('Pindah section ke atas')
            ->assertDontSee('title="Hapus section"', false)
            ->assertDontSee(route('dashboard.work.tracker.sections.store', $this->project), false)
            ->assertDontSee('name="confirm_name"', false);

        // Pasangan positif: pengelola melihat semuanya (asersi di atas tidak kosong).
        $this->board()->assertSee(route('dashboard.work.tracker.sections.store', $this->project), false)
            ->assertSee('aria-label="Warna section CONTRACT"', false)
            ->assertSee('name="confirm_name"', false);
    }

    public function test_task_form_offers_empty_registered_sections_in_managed_order(): void
    {
        $this->item('CONTRACT');
        $this->item('SONG');
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'Baru Kosong']);
        $this->actingAs($this->p['aldora'])->patch(route('dashboard.work.tracker.sections.move', $this->section('SONG')), ['direction' => 'up']);

        $this->board()->assertViewHas('sectionsByProject', fn($map) => $map[$this->project->id] === ['SONG', 'CONTRACT', 'Baru Kosong']);
    }

    public function test_items_without_project_stay_uncontrolled_in_their_own_card(): void
    {
        $this->item('LEGAL', ['project_id' => null]);

        $card = $this->board()->viewData('cards')->firstWhere('project', null);

        $this->assertSame('LEGAL', $card['sections'][0]['name']);
        $this->assertNull($card['sections'][0]['model']);
    }

    // ---- Tambah ----------------------------------------------------------

    public function test_add_section_creates_it_at_the_bottom_and_rejects_duplicates_ignoring_case(): void
    {
        $this->item('CONTRACT');
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => '  Marketing  '])
            ->assertSessionHas('status', 'Section ditambahkan.');
        $this->assertSame(['CONTRACT', 'Marketing'], $this->order());

        $aldora->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'contract'])
            ->assertSessionHasErrors('name');
        $aldora->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => ''])
            ->assertSessionHasErrors('name');
        $aldora->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => str_repeat('x', 81)])
            ->assertSessionHasErrors('name');

        $this->assertSame(2, ProjectSection::count());
    }

    public function test_same_section_name_can_exist_in_two_projects(): void
    {
        $other = $this->makeProject('Merch');
        $this->item('CONTRACT');
        $this->item('CONTRACT', ['project_id' => $other->id]);

        $this->assertSame(['CONTRACT'], $this->order());
        $this->assertSame(['CONTRACT'], $this->order($other));
    }

    // ---- Warna -----------------------------------------------------------

    public function test_color_can_be_changed_and_is_validated(): void
    {
        $this->item('CONTRACT');
        $section = $this->section('CONTRACT');
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->patch(route('dashboard.work.tracker.sections.update', $section), ['color' => '#AA3355'], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('#aa3355', $section->fresh()->color);
        $this->board()->assertSee('background:#aa3355', false);

        foreach (['red', '#12345', '#gggggg', '', '#1234567'] as $bad) {
            $aldora->patch(route('dashboard.work.tracker.sections.update', $section), ['color' => $bad], ['Accept' => 'application/json'])
                ->assertStatus(422);
        }
        $this->assertSame('#aa3355', $section->fresh()->color);
    }

    public function test_color_is_per_project_not_shared_by_name(): void
    {
        $other = $this->makeProject('Merch');
        $this->item('CONTRACT');
        $this->item('CONTRACT', ['project_id' => $other->id]);

        $this->actingAs($this->p['aldora'])->patch(route('dashboard.work.tracker.sections.update', $this->section('CONTRACT')), ['color' => '#112233']);

        $this->assertNull($this->section('CONTRACT', $other)->color);
    }

    // ---- Urutan ----------------------------------------------------------

    public function test_move_swaps_neighbours_and_ends_are_noops(): void
    {
        foreach (['A', 'B', 'C'] as $name) {
            $this->item($name);
        }
        $move = fn(string $name, string $dir) => $this->actingAs($this->p['aldora'])
            ->patch(route('dashboard.work.tracker.sections.move', $this->section($name)), ['direction' => $dir])->assertSessionHasNoErrors();

        $move('C', 'up');
        $this->assertSame(['A', 'C', 'B'], $this->order());

        $move('A', 'up');    // paling atas: tidak berubah
        $move('B', 'down');  // paling bawah: tidak berubah
        $this->assertSame(['A', 'C', 'B'], $this->order());

        $move('A', 'down');
        $this->assertSame(['C', 'A', 'B'], $this->order());

        $this->actingAs($this->p['aldora'])
            ->patch(route('dashboard.work.tracker.sections.move', $this->section('A')), ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    public function test_move_repairs_duplicate_or_gapped_sort_orders_and_only_touches_its_own_project(): void
    {
        $other = $this->makeProject('Merch');
        foreach (['A', 'B', 'C'] as $name) {
            $this->item($name);
        }
        $this->item('Z', ['project_id' => $other->id]);
        ProjectSection::where('project_id', $this->project->id)->update(['sort_order' => 7]);   // semua kembar

        $this->actingAs($this->p['aldora'])
            ->patch(route('dashboard.work.tracker.sections.move', $this->section('C')), ['direction' => 'up']);

        $this->assertSame(['A', 'C', 'B'], $this->order());
        $this->assertSame([1, 2, 3], ProjectSection::where('project_id', $this->project->id)->orderBy('sort_order')->pluck('sort_order')->all());
        $this->assertSame(1, $this->section('Z', $other)->sort_order);
    }

    public function test_order_persists_on_the_board_and_flags_first_last(): void
    {
        foreach (['A', 'B'] as $name) {
            $this->item($name);
        }
        $this->actingAs($this->p['aldora'])->patch(route('dashboard.work.tracker.sections.move', $this->section('B')), ['direction' => 'up']);

        $sections = $this->board()->viewData('cards')->first()['sections'];

        $this->assertSame(['B', 'A'], $sections->pluck('name')->all());
        $this->assertSame([true, false], $sections->pluck('is_first')->all());
        $this->assertSame([false, true], $sections->pluck('is_last')->all());
    }

    // ---- Hapus -----------------------------------------------------------

    public function test_empty_section_is_deleted_without_confirmation(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'Kosong']);

        $this->actingAs($this->p['aldora'])->delete(route('dashboard.work.tracker.sections.destroy', $this->section('Kosong')))
            ->assertSessionHas('status', 'Section "Kosong" dihapus.');

        $this->assertSame(0, ProjectSection::count());
    }

    public function test_section_with_items_needs_exact_name_and_deletes_only_its_own_items(): void
    {
        $other = $this->makeProject('Merch');
        $this->item('CONTRACT');
        $this->item('CONTRACT', ['item_no' => 2]);
        $keep = $this->item('SONG');
        $sameNameElsewhere = $this->item('CONTRACT', ['project_id' => $other->id]);
        $section = $this->section('CONTRACT');
        $aldora = $this->actingAs($this->p['aldora']);

        // tanpa konfirmasi / salah nama -> ditolak, tidak ada yang terhapus
        $aldora->delete(route('dashboard.work.tracker.sections.destroy', $section))->assertSessionHasErrors('confirm_name');
        $aldora->delete(route('dashboard.work.tracker.sections.destroy', $section), ['confirm_name' => 'contract'])->assertSessionHasErrors('confirm_name');
        $this->assertSame(2, WorkItem::where('project_id', $this->project->id)->where('section', 'CONTRACT')->count());
        $this->assertNotNull($section->fresh());

        $aldora->delete(route('dashboard.work.tracker.sections.destroy', $section), ['confirm_name' => 'CONTRACT'])
            ->assertSessionHas('status', 'Section "CONTRACT" dan 2 item di dalamnya dihapus.');

        $this->assertNull($section->fresh());
        $this->assertSame(0, WorkItem::where('project_id', $this->project->id)->where('section', 'CONTRACT')->count());
        $this->assertNotNull($keep->fresh());
        $this->assertNotNull($sameNameElsewhere->fresh());
        $this->assertSame(['SONG'], $this->order());
    }

    public function test_deleting_a_section_also_removes_extra_pics_of_its_items(): void
    {
        $item = $this->item('CONTRACT');
        $item->additionalPics()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['aldora'])
            ->delete(route('dashboard.work.tracker.sections.destroy', $this->section('CONTRACT')), ['confirm_name' => 'CONTRACT']);

        $this->assertSame(0, DB::table('work_item_additional_pics')->count());
    }

    public function test_deleting_a_project_removes_its_sections(): void
    {
        $this->item('CONTRACT');

        $this->actingAs($this->p['aldora'])->delete(route('dashboard.work.tracker.projects.destroy', $this->project));

        $this->assertSame(0, ProjectSection::count());
    }

    // ---- Akses -----------------------------------------------------------

    public function test_view_only_user_cannot_use_any_section_endpoint(): void
    {
        $this->item('CONTRACT');
        $section = $this->section('CONTRACT');
        $g = $this->actingAs($this->p['gepeng']);

        $g->post(route('dashboard.work.tracker.sections.store', $this->project), ['name' => 'X'])->assertForbidden();
        $g->patch(route('dashboard.work.tracker.sections.update', $section), ['color' => '#112233'])->assertForbidden();
        $g->patch(route('dashboard.work.tracker.sections.move', $section), ['direction' => 'down'])->assertForbidden();
        $g->delete(route('dashboard.work.tracker.sections.destroy', $section), ['confirm_name' => 'CONTRACT'])->assertForbidden();

        $this->assertNotNull($section->fresh());
        $this->assertSame(1, ProjectSection::count());
    }

    public function test_task_form_creates_the_section_when_typing_a_new_one(): void
    {
        $this->actingAs($this->p['aldora'])->post(route('dashboard.work.tracker.items.store'), [
            'project_id' => $this->project->id,
            'section' => 'BARU DARI FORM',
            'title' => 'Task',
            'progress' => 'Pending',
            'priority' => 'Medium',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['BARU DARI FORM'], $this->order());
    }
}
