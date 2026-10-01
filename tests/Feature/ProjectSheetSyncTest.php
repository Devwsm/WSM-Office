<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Item 6 selisih prototype v32 — download project ke Excel + sinkron dari
 * tracker Google Sheet. Aldora = work `manage`, Gepeng = work `view`,
 * Kanaya (manajer) tidak punya modul work.
 */
class ProjectSheetSyncTest extends TestCase
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
        $this->project = Project::create([
            'slug' => 'album-q3',
            'name' => 'Album Q3',
            'priority' => 'High',
            'status' => 'On Development',
            'progress_recap' => 'Post-release, OLV',
            'lead_employee_id' => $this->p['aldora']->id,
            'created_by' => $this->p['owner']->id,
        ]);
    }

    private function item(array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $this->project->id,
            'section' => 'CONTRACT',
            'item_no' => '1',
            'title' => 'Contract Rossa',
            'due_date' => '2026-09-25',
            'pic_employee_id' => $this->p['aldora']->id,
            'progress' => 'Pending',
            'is_reminder' => false,
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    /** Tulis file .xlsx sementara dari array baris, kembalikan UploadedFile siap kirim. */
    private function sheetFile(array $rows, string $name = 'tracker.xlsx', array $links = []): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Tracker');
        $sheet->fromArray($rows, null, 'A1', true);
        foreach ($links as $coordinate => $url) {
            $sheet->getCell($coordinate)->getHyperlink()->setUrl($url);
        }

        $path = sys_get_temp_dir() . '/' . uniqid('wsm-sync-') . '.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function csvFile(string $content, string $name = 'tracker.csv'): UploadedFile
    {
        $path = sys_get_temp_dir() . '/' . uniqid('wsm-sync-') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    private function preview(User $as, UploadedFile $file)
    {
        return $this->actingAs($as)->post(route('dashboard.work.tracker.projects.sync.preview', $this->project), ['file' => $file]);
    }

    private function commit(User $as, string $token)
    {
        return $this->actingAs($as)->post(route('dashboard.work.tracker.projects.sync.commit', $this->project), ['token' => $token]);
    }

    // ------------------------------------------------------------------ download

    public function test_project_excel_has_project_info_and_tracker_sheets_in_section_order(): void
    {
        $this->item(['section' => 'CONTRACT', 'title' => 'Contract Rossa', 'item_no' => '1']);
        $this->item(['section' => 'SONG', 'title' => 'Tak Mendua', 'item_no' => '1', 'notes' => 'Mixing', 'link' => 'https://drive.example/x']);
        $this->item(['section' => 'CONTRACT', 'title' => 'Contract Judika', 'item_no' => '2', 'pic_employee_id' => $this->p['gepeng']->id]);

        $response = $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.tracker.projects.excel', $this->project));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString('album-q3', $response->headers->get('content-disposition'));

        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['Project', 'Tracker'], $book->getSheetNames());

        $info = $book->getSheetByName('Project')->toArray();
        $byField = collect($info)->mapWithKeys(fn($r) => [$r[0] => $r[1]]);
        $this->assertSame('Album Q3', $byField['Project']);
        $this->assertSame('High', $byField['Priority']);
        $this->assertSame('Aldora', $byField['Project Lead']);
        $this->assertSame('Post-release, OLV', $byField['Progress Recap']);
        $this->assertEquals(3, $byField['Total Item']);

        $rows = $book->getSheetByName('Tracker')->toArray();
        $this->assertSame(['SECTION', 'NO', 'ITEM', 'DATE', 'FOCUS', 'PIC', 'PROGRESS', 'NOTE', 'LINK', 'ID WSM'], $rows[0]);
        $this->assertSame(['Contract Rossa', 'Contract Judika', 'Tak Mendua'], array_column(array_slice($rows, 1), 2));
        $this->assertSame('Gepeng', $rows[2][5]);
        $this->assertSame('25/09/2026', $rows[1][3]);
        $this->assertSame('https://drive.example/x', $rows[3][8]);
    }

    public function test_project_excel_needs_work_access(): void
    {
        $this->actingAs($this->p['manajer'])
            ->get(route('dashboard.work.tracker.projects.excel', $this->project))
            ->assertForbidden();
    }

    public function test_project_excel_needs_login(): void
    {
        $this->get(route('dashboard.work.tracker.projects.excel', $this->project))->assertRedirect();
    }

    public function test_download_buttons_are_shown_on_projects_page_by_access_level(): void
    {
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.work.projects.index'))
            ->assertOk()
            ->assertSee(route('dashboard.work.tracker.projects.excel', $this->project), false)
            ->assertDontSee(route('dashboard.work.tracker.projects.sync.show', $this->project), false);

        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.projects.index'))
            ->assertOk()
            ->assertSee(route('dashboard.work.tracker.projects.sync.show', $this->project), false);
    }

    // ------------------------------------------------------------------ akses sinkron

    public function test_sync_needs_manage_access_on_every_step(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.work.tracker.projects.sync.show', $this->project))->assertForbidden();

        $this->preview($this->p['gepeng'], $this->sheetFile([['ITEM'], ['x']]))->assertForbidden();
        $this->commit($this->p['gepeng'], 'abc')->assertForbidden();

        $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.tracker.projects.sync.show', $this->project))->assertOk()->assertSee('Sinkron Sheet');
    }

    // ------------------------------------------------------------------ round trip

    public function test_exported_file_edited_and_uploaded_updates_by_id_without_duplicating(): void
    {
        $a = $this->item(['title' => 'Contract Rossa', 'notes' => 'lama']);
        $b = $this->item(['title' => 'Contract Judika', 'item_no' => '2']);

        $export = $this->actingAs($this->p['aldora'])
            ->get(route('dashboard.work.tracker.projects.excel', $this->project));
        $rows = IOFactory::load($export->baseResponse->getFile()->getPathname())->getSheetByName('Tracker')->toArray();

        // Edit di "Google Sheet": ubah progress + catatan baris pertama, judul baris kedua tetap.
        $rows[1][6] = 'Done';
        $rows[1][7] = 'sudah ttd';

        $response = $this->preview($this->p['aldora'], $this->sheetFile($rows));
        $response->assertOk();
        $plan = $response->viewData('plan');

        $this->assertSame(1, $plan['counts']['update']);
        $this->assertSame(1, $plan['counts']['unchanged']);
        $this->assertSame(0, $plan['counts']['create']);
        $this->assertSame(0, $plan['counts']['invalid']);

        // Preview belum menulis apa pun.
        $this->assertSame('Pending', $a->fresh()->progress);

        $this->commit($this->p['aldora'], $response->viewData('token'))
            ->assertRedirect(route('dashboard.work.tracker.index', ['project_id' => $this->project->id]));

        $this->assertSame('Done', $a->fresh()->progress);
        $this->assertSame('sudah ttd', $a->fresh()->notes);
        $this->assertSame('Pending', $b->fresh()->progress);
        $this->assertSame(2, WorkItem::where('project_id', $this->project->id)->count());
        $this->assertTrue(AuditLog::where('action', 'Sinkron Work Tracker dari file')->exists());
    }

    public function test_token_is_single_use_and_bound_to_the_project(): void
    {
        $this->item();
        $token = $this->preview($this->p['aldora'], $this->sheetFile([['ITEM', 'PROGRESS'], ['Contract Rossa', 'Done'], ['Baru', 'Pending']]))
            ->viewData('token');

        $other = Project::create(['slug' => 'lain', 'name' => 'Lain', 'priority' => 'Low', 'status' => 'Pending', 'created_by' => $this->p['owner']->id]);
        $this->actingAs($this->p['aldora'])
            ->post(route('dashboard.work.tracker.projects.sync.commit', $other), ['token' => $token])->assertStatus(410);

        $this->commit($this->p['aldora'], $token)->assertRedirect();
        $this->commit($this->p['aldora'], $token)->assertStatus(410);
        $this->assertSame(2, WorkItem::where('project_id', $this->project->id)->count());
    }

    // ------------------------------------------------------------------ tracker Google Sheet mentah

    public function test_raw_google_sheet_layout_matches_existing_tasks_and_creates_the_rest(): void
    {
        $kanaya = $this->p['manajer'];
        $rossa = $this->item(['title' => 'Contract Rossa', 'section' => 'CONTRACT', 'due_date' => null, 'pic_employee_id' => null]);
        $dupA = $this->item(['title' => 'Book schedule Studio', 'section' => 'PHOTOSHOOT', 'item_no' => '1']);
        $dupB = $this->item(['title' => 'Book schedule Studio', 'section' => 'PHOTOSHOOT', 'item_no' => '2']);

        // Susunan tracker tim: judul tabel di baris ke-2, kolom SECTION tanpa judul, baris judul section.
        $rows = [
            [' ', '', '07/09/2026'],
            ['NO', '', 'ITEM', 'DATE', 'FOCUS', 'PIC', 'PROGRESS', 'NOTE', 'LINK'],
            ['', 'CONTRACT', 'CONTRACT'],
            ['1', 'CONTRACT', 'Contract Rossa', '02/09/2026', 'KELEWAT', 'Kanaya WS Team', 'follow up', 'Sudah kirim revisi', 'LINK'],
            ['', 'PHOTOSHOOT', 'PHOTOSHOOT'],
            ['1', 'PHOTOSHOOT', 'Book schedule Studio', '10/08/2026', 'SELESAI', 'ALL TEAM', 'DONE', 'Kyabin', ''],
            ['2', '', 'Book schedule Studio', '11/08/2026', 'SELESAI', 'Mas Aldora & Gepeng', 'DONE', '', ''],
            ['3', '', 'Studio baru', '', '', 'Orang Asing', 'on development', '', ''],
        ];

        $response = $this->preview($this->p['aldora'], $this->sheetFile($rows, 'gsheet.xlsx', ['I4' => 'https://drive.example/kontrak']));
        $response->assertOk();
        $plan = $response->viewData('plan');

        $this->assertSame(['create' => 1, 'update' => 3, 'unchanged' => 0, 'invalid' => 0, 'missing' => 0], $plan['counts']);

        $this->commit($this->p['aldora'], $response->viewData('token'))->assertRedirect();

        $rossa->refresh();
        $this->assertSame('Follow Up', $rossa->progress);
        $this->assertSame('2026-09-02', $rossa->due_date->format('Y-m-d'));
        $this->assertSame($kanaya->id, $rossa->pic_employee_id);
        $this->assertSame('Sudah kirim revisi', $rossa->notes);
        $this->assertSame('https://drive.example/kontrak', $rossa->link);

        // Dua judul kembar di section yang sama terpasang berurutan, tidak saling menimpa.
        $this->assertSame('2026-08-10', $dupA->fresh()->due_date->format('Y-m-d'));
        $this->assertSame('ALL TEAM', $dupA->fresh()->additional_pic);
        $this->assertNull($dupA->fresh()->pic_employee_id);
        $this->assertSame('2026-08-11', $dupB->fresh()->due_date->format('Y-m-d'));
        $this->assertSame($this->p['aldora']->id, $dupB->fresh()->pic_employee_id);
        $this->assertSame([$this->p['gepeng']->id], $dupB->fresh()->additionalPics->pluck('id')->all());

        // Task baru: section dari baris judul, progress terpetakan, PIC tak dikenal jadi teks.
        $new = WorkItem::where('title', 'Studio baru')->firstOrFail();
        $this->assertSame('PHOTOSHOOT', $new->section);
        $this->assertSame('On Development', $new->progress);
        $this->assertSame('Orang Asing', $new->additional_pic);
        $this->assertSame('3', $new->item_no);
        $this->assertSame($this->p['aldora']->id, $new->created_by);
        $this->assertSame(4, WorkItem::where('project_id', $this->project->id)->count());
    }

    public function test_empty_cells_never_wipe_existing_data_and_missing_tasks_are_left_alone(): void
    {
        $kept = $this->item(['title' => 'Contract Rossa', 'notes' => 'catatan penting', 'link' => 'https://a.test/x']);
        $absent = $this->item(['title' => 'Tidak ada di file', 'section' => 'SONG']);

        $response = $this->preview($this->p['aldora'], $this->sheetFile([
            ['SECTION', 'ITEM', 'DATE', 'PIC', 'PROGRESS', 'NOTE', 'LINK'],
            ['CONTRACT', 'Contract Rossa', '', '', '', '', ''],
        ]));

        $plan = $response->viewData('plan');
        $this->assertSame(1, $plan['counts']['unchanged']);
        $this->assertSame(1, $plan['counts']['missing']);

        $this->commit($this->p['aldora'], $response->viewData('token'));

        $kept->refresh();
        $this->assertSame('catatan penting', $kept->notes);
        $this->assertSame('https://a.test/x', $kept->link);
        $this->assertSame('2026-09-25', $kept->due_date->format('Y-m-d'));
        $this->assertSame($this->p['aldora']->id, $kept->pic_employee_id);
        $this->assertNotNull($absent->fresh());
    }

    public function test_bad_rows_are_reported_and_only_valid_rows_are_applied(): void
    {
        $mine = $this->item();
        $foreign = WorkItem::create([
            'project_id' => Project::create(['slug' => 'lain', 'name' => 'Lain', 'priority' => 'Low', 'status' => 'Pending', 'created_by' => $this->p['owner']->id])->id,
            'section' => 'X',
            'title' => 'Milik project lain',
            'progress' => 'Pending',
            'is_reminder' => false,
            'created_by' => $this->p['owner']->id,
        ]);

        $response = $this->preview($this->p['aldora'], $this->sheetFile([
            ['ITEM', 'DATE', 'PROGRESS', 'ID WSM'],
            ['Contract Rossa', 'Early April', 'ngawur', (string) $mine->id],
            ['Curi task', '', 'Done', (string) $foreign->id],
            ['Ada ID tapi bukan angka', '', '', 'abc'],
            ['Baru valid', '01/10/2026', 'Done', ''],
        ]));

        $plan = $response->viewData('plan');
        $this->assertSame(2, $plan['counts']['invalid']);
        $this->assertSame(1, $plan['counts']['create']);
        $this->assertSame(1, $plan['counts']['unchanged']);

        $unchanged = collect($plan['rows'])->firstWhere('action', 'unchanged');
        $this->assertCount(2, $unchanged['warnings']); // tanggal & progress tak terbaca, dilewati.

        $this->commit($this->p['aldora'], $response->viewData('token'));

        $this->assertSame('Pending', $foreign->fresh()->progress);
        $this->assertSame('Pending', $mine->fresh()->progress);
        $this->assertDatabaseHas('work_items', ['title' => 'Baru valid', 'project_id' => $this->project->id]);
    }

    public function test_csv_upload_and_files_without_an_item_column(): void
    {
        $this->item(['title' => 'Contract Rossa']);

        $ok = $this->preview($this->p['aldora'], $this->csvFile("NO,ITEM,DATE,PIC,PROGRESS\n1,Contract Rossa,25/09/2026,Aldora,Done\n"));
        $this->assertSame(1, $ok->viewData('plan')['counts']['update']);

        $this->preview($this->p['aldora'], $this->csvFile("NAMA,UMUR\nBudi,20\n"))
            ->assertSessionHasErrors('file');

        $this->preview($this->p['aldora'], UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->assertSessionHasErrors('file');
    }

    public function test_changing_pics_writes_up_to_three_real_users(): void
    {
        $item = $this->item(['pic_employee_id' => null]);

        $response = $this->preview($this->p['aldora'], $this->sheetFile([
            ['ITEM', 'PIC', 'ID WSM'],
            ['Contract Rossa', 'Gepeng · Aldora & Kanaya · Rania', (string) $item->id],
        ]));
        $this->commit($this->p['aldora'], $response->viewData('token'));

        $item->refresh();
        $this->assertSame($this->p['gepeng']->id, $item->pic_employee_id);
        $this->assertSame(
            [$this->p['aldora']->id, $this->p['manajer']->id],
            $item->additionalPics->pluck('id')->all(),
        );
        $this->assertNull($item->additional_pic);
        $this->assertSame('Gepeng · Aldora · Kanaya', $item->picLabel());
    }
}