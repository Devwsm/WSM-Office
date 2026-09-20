<?php

namespace Tests\Feature;

use App\Http\Controllers\Employee\AttendanceController;
use App\Models\Attendance;
use App\Models\DashboardAccess;
use App\Models\EmployeeContract;
use App\Models\LegalDocument;
use App\Models\User;
use App\Support\PrivateFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Kontrak karyawan, dokumen legal, dan selfie absensi TIDAK boleh
 * terbuka tanpa login: tersimpan di disk private dan hanya keluar lewat
 * route berotorisasi.
 *
 * Catatan: migrasi proyek memakai `ALTER TABLE ... MODIFY ENUM` (khusus
 * MySQL/MariaDB), jadi jalankan dengan koneksi MySQL, mis.
 *   DB_CONNECTION=mysql DB_DATABASE=wsm_test php artisan test --filter=PrivateFileAccessTest
 */
class PrivateFileAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj\n<< >>\nendobj\ntrailer\n<< >>\n%%EOF";

    private FilesystemAdapter $local;

    private FilesystemAdapter $public;

    protected function setUp(): void
    {
        parent::setUp();

        $this->local = Storage::fake('local');
        $this->public = Storage::fake('public');
    }

    private function user(string $role = 'karyawan', array $modules = [], ?int $managerId = null): User
    {
        $user = User::factory()->create(['role' => $role, 'manager_id' => $managerId]);

        foreach ($modules as $module => $level) {
            DashboardAccess::create(['user_id' => $user->id, 'module' => $module, 'level' => $level]);
        }

        return $user;
    }

    private function contract(string $path = 'contracts/1/kontrak.pdf', string $body = self::PDF): EmployeeContract
    {
        $this->local->put($path, $body);

        return EmployeeContract::create([
            'employee_id' => $this->user()->id,
            'file_path' => $path,
            'original_filename' => 'Kontrak Kerja.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($body),
            'uploaded_by' => $this->user('owner')->id,
        ]);
    }

    private function attendanceWithPhoto(User $employee): Attendance
    {
        $path = "attendance/{$employee->id}/2026-09-19-in-abc12345.png";
        $this->local->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return Attendance::create([
            'user_id' => $employee->id,
            'date' => '2026-09-19',
            'session_number' => 1,
            'mode' => 'kantor',
            'auto_closed' => false,
            'clock_in_photo' => $path,
        ]);
    }

    // ---------------------------------------------------------------
    // Kontrak karyawan
    // ---------------------------------------------------------------

    public function test_contract_file_redirects_guest_to_login(): void
    {
        $contract = $this->contract();

        $this->get(route('dashboard.contracts.file', $contract))->assertRedirect('/login');
    }

    public function test_contract_file_is_forbidden_without_contracts_module(): void
    {
        $contract = $this->contract();
        $stranger = $this->user('karyawan', ['work' => 'manage']);

        $this->actingAs($stranger)->get(route('dashboard.contracts.file', $contract))->assertForbidden();
    }

    public function test_contract_file_is_served_to_user_with_contracts_view(): void
    {
        $contract = $this->contract();
        $viewer = $this->user('karyawan', ['contracts' => 'view']);

        $response = $this->actingAs($viewer)->get(route('dashboard.contracts.file', $contract));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame(self::PDF, $response->streamedContent());
    }

    public function test_contract_file_with_unsafe_type_is_forced_to_download(): void
    {
        $contract = $this->contract('contracts/1/catatan.docx', '<script>alert(1)</script>');
        $owner = $this->user('owner');

        $response = $this->actingAs($owner)->get(route('dashboard.contracts.file', $contract));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_contract_file_missing_on_disk_returns_404(): void
    {
        $contract = $this->contract();
        $this->local->delete($contract->file_path);

        $this->actingAs($this->user('owner'))
            ->get(route('dashboard.contracts.file', $contract))
            ->assertNotFound();
    }

    public function test_legacy_file_still_on_public_disk_is_served_through_the_route_only(): void
    {
        $this->public->put('contracts/9/lama.pdf', self::PDF);
        $contract = EmployeeContract::create([
            'employee_id' => $this->user()->id,
            'file_path' => 'contracts/9/lama.pdf',
            'original_filename' => 'Lama.pdf',
            'uploaded_by' => $this->user('owner')->id,
        ]);

        $this->actingAs($this->user('owner'))
            ->get(route('dashboard.contracts.file', $contract))
            ->assertOk();
    }

    public function test_uploading_a_contract_stores_it_on_the_private_disk_only(): void
    {
        $owner = $this->user('owner');
        $employee = $this->user();

        $this->actingAs($owner)->post(route('dashboard.contracts.store'), [
            'employee_id' => $employee->id,
            'file' => UploadedFile::fake()->create('kontrak.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('dashboard.contracts.index'));

        $contract = EmployeeContract::firstOrFail();

        $this->local->assertExists($contract->file_path);
        $this->assertSame([], $this->public->allFiles());
    }

    public function test_deleting_a_contract_removes_private_and_legacy_copies(): void
    {
        $contract = $this->contract();
        $this->public->put($contract->file_path, 'sisa lama');

        $this->actingAs($this->user('owner'))
            ->delete(route('dashboard.contracts.destroy', $contract))
            ->assertRedirect();

        $this->local->assertMissing($contract->file_path);
        $this->public->assertMissing($contract->file_path);
    }

    // ---------------------------------------------------------------
    // Dokumen legal
    // ---------------------------------------------------------------

    public function test_legal_file_requires_login_and_legal_module(): void
    {
        $this->local->put('legal/album/a.pdf', self::PDF);
        $doc = LegalDocument::create([
            'category' => 'album',
            'title' => 'Perjanjian Album',
            'file_path' => 'legal/album/a.pdf',
            'original_filename' => 'a.pdf',
            'created_by' => $this->user('owner')->id,
        ]);

        $this->get(route('dashboard.legal.file', $doc))->assertRedirect('/login');

        $this->actingAs($this->user('karyawan', ['contracts' => 'manage']))
            ->get(route('dashboard.legal.file', $doc))
            ->assertForbidden();

        $this->actingAs($this->user('karyawan', ['legal' => 'view']))
            ->get(route('dashboard.legal.file', $doc))
            ->assertOk();
    }

    public function test_uploading_a_legal_document_stores_it_on_the_private_disk_only(): void
    {
        $this->actingAs($this->user('owner'))->post(route('dashboard.legal.store'), [
            'category' => 'album',
            'title' => 'Perjanjian Baru',
            'file' => UploadedFile::fake()->create('perjanjian.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('dashboard.legal.index'));

        $doc = LegalDocument::firstOrFail();

        $this->local->assertExists($doc->file_path);
        $this->assertSame([], $this->public->allFiles());
    }

    // ---------------------------------------------------------------
    // Selfie absensi
    // ---------------------------------------------------------------

    public function test_attendance_photo_redirects_guest_to_login(): void
    {
        $attendance = $this->attendanceWithPhoto($this->user());

        $this->get(route('attendance.recap.photo', [$attendance, 'masuk']))->assertRedirect('/login');
    }

    public function test_attendance_photo_respects_recap_scope(): void
    {
        $manager = $this->user('manajer', ['people' => 'view']);
        $subordinate = $this->user('karyawan', [], $manager->id);
        $attendance = $this->attendanceWithPhoto($subordinate);

        $peer = $this->user('karyawan', ['people' => 'view']);
        $hrd = $this->user('hrd', ['people' => 'view']);
        $owner = $this->user('owner');

        $url = route('attendance.recap.photo', [$attendance, 'masuk']);

        $this->actingAs($manager)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($hrd)->get($url)->assertOk();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($peer)->get($url)->assertForbidden();
    }

    public function test_attendance_photo_without_people_module_is_forbidden(): void
    {
        $employee = $this->user();
        $attendance = $this->attendanceWithPhoto($employee);

        // Pemilik foto sendiri pun tidak punya gerbang `people`; tidak ada jalur publik.
        $this->actingAs($employee)
            ->get(route('attendance.recap.photo', [$attendance, 'masuk']))
            ->assertForbidden();
    }

    public function test_attendance_photo_missing_or_invalid_type_returns_404(): void
    {
        $owner = $this->user('owner');
        $attendance = $this->attendanceWithPhoto($this->user());

        $this->actingAs($owner)->get(route('attendance.recap.photo', [$attendance, 'pulang']))->assertNotFound();
        $this->actingAs($owner)->get("/absensi/foto/{$attendance->id}/lainnya")->assertNotFound();
    }

    public function test_store_photo_writes_to_private_disk_and_rejects_bad_input(): void
    {
        $method = new ReflectionMethod(AttendanceController::class, 'storePhoto');
        $controller = app(AttendanceController::class);

        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $path = $method->invoke($controller, $png, 7, '2026-09-19', 'in');

        $this->assertNotNull($path);
        $this->local->assertExists($path);
        $this->assertSame([], $this->public->allFiles());

        // Bukan gambar sungguhan (isi teks berlabel png) → ditolak.
        $fake = 'data:image/png;base64,' . base64_encode('<?php echo 1;');
        $this->assertNull($method->invoke($controller, $fake, 7, '2026-09-19', 'in'));

        // Terlalu besar (> 4 MB) → ditolak.
        $big = 'data:image/png;base64,' . base64_encode(str_repeat('A', 4 * 1024 * 1024 + 10));
        $this->assertNull($method->invoke($controller, $big, 7, '2026-09-19', 'in'));
    }

    // ---------------------------------------------------------------
    // Helper & command
    // ---------------------------------------------------------------

    public function test_private_file_rejects_path_traversal(): void
    {
        $this->assertNull(PrivateFile::diskHolding('../.env'));
        $this->assertNull(PrivateFile::diskHolding('/etc/passwd'));
        $this->assertNull(PrivateFile::diskHolding(''));
        $this->assertNull(PrivateFile::diskHolding(null));
    }

    public function test_privatize_command_moves_legacy_files_and_dry_run_changes_nothing(): void
    {
        $this->public->put('contracts/1/a.pdf', self::PDF);
        $this->public->put('legal/album/b.pdf', self::PDF);
        $this->public->put('attendance/1/c.png', 'x');
        $this->public->put('lain/d.txt', 'bukan folder terkelola');

        $this->artisan('files:privatize', ['--dry-run' => true])->assertSuccessful();
        $this->public->assertExists('contracts/1/a.pdf');
        $this->local->assertMissing('contracts/1/a.pdf');

        $this->artisan('files:privatize')->assertSuccessful();

        foreach (['contracts/1/a.pdf', 'legal/album/b.pdf', 'attendance/1/c.png'] as $path) {
            $this->local->assertExists($path);
            $this->public->assertMissing($path);
        }

        $this->public->assertExists('lain/d.txt');
    }

    // ---------------------------------------------------------------
    // Halaman tidak lagi memuat link /storage/ publik
    // ---------------------------------------------------------------

    public function test_pages_link_to_authorised_routes_instead_of_public_storage_urls(): void
    {
        $owner = $this->user('owner');
        $contract = $this->contract();

        $this->local->put('legal/album/a.pdf', self::PDF);
        $legal = LegalDocument::create([
            'category' => 'album',
            'title' => 'Perjanjian Album',
            'file_path' => 'legal/album/a.pdf',
            'original_filename' => 'a.pdf',
            'created_by' => $owner->id,
        ]);

        $employee = $this->user();
        $attendance = $this->attendanceWithPhoto($employee);

        $pages = [
            [route('dashboard.contracts.index'), route('dashboard.contracts.file', $contract, false)],
            [route('dashboard.contracts.edit', $contract), route('dashboard.contracts.file', $contract, false)],
            [route('dashboard.legal.index'), route('dashboard.legal.file', $legal, false)],
            [route('dashboard.legal.edit', $legal), route('dashboard.legal.file', $legal, false)],
            [route('attendance.recap.show', $employee) . '?bulan=2026-09', route('attendance.recap.photo', [$attendance, 'masuk'], false)],
        ];

        foreach ($pages as [$page, $expectedLink]) {
            $response = $this->actingAs($owner)->get($page)->assertOk();
            $response->assertSee($expectedLink, false);
            $response->assertDontSee('/storage/', false);
        }
    }
}