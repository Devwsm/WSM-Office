<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Aturan privasi dan kerapian Audit Log (2026-10-11): IP hanya untuk kejadian
 * keamanan, isi catatan task tidak ditulis, jam dan menit pada nilai waktu,
 * rincian keputusan approval, pembersihan log lama, dan migrasi pembersih data lama.
 */
class AuditPrivacyTest extends TestCase
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

    private function workItem(array $o = []): WorkItem
    {
        $project = Project::create([
            'name' => 'Album Q3',
            'priority' => 'High',
            'status' => 'On Development',
            'created_by' => $this->p['owner']->id,
        ]);

        return WorkItem::create(array_merge([
            'project_id' => $project->id,
            'section' => 'CONTRACT',
            'item_no' => 1,
            'title' => 'Approval lirik',
            'due_date' => '2026-09-25',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'pic_employee_id' => $this->p['aldora']->id,
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function lastLog(string $action): AuditLog
    {
        return AuditLog::query()->where('action', $action)->latest('id')->firstOrFail();
    }

    // ---- IP -------------------------------------------------------------

    public function test_ip_hanya_disimpan_untuk_kejadian_keamanan(): void
    {
        $this->actingAs($this->p['aldora']);

        foreach (AuditLog::IP_ACTIONS as $action) {
            $this->assertNotNull(AuditLog::record($action, 'x', $this->p['aldora'])->ip_address, $action);
        }

        foreach (['Task diperbarui', 'Lembur disetujui', 'Karyawan diperbarui', 'Payroll digenerate'] as $action) {
            $this->assertNull(AuditLog::record($action, 'x', $this->p['aldora'])->ip_address, $action);
        }
    }

    public function test_ganti_password_menyimpan_ip_tapi_progress_task_tidak(): void
    {
        $item = $this->workItem();

        $this->actingAs($this->p['aldora'])->patch(route('employee.workItems.progress', $item), ['progress' => 'Done']);
        $this->assertNull($this->lastLog('Progress task diubah (oleh PIC)')->ip_address);

        $this->actingAs($this->p['aldora'])->patch('/app/profile/password', [
            'current_password' => 'password',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($this->lastLog('Password diganti')->ip_address);
    }

    // ---- Catatan task ---------------------------------------------------

    public function test_isi_catatan_task_tidak_ditulis_di_perubahan_maupun_data_baru(): void
    {
        $item = $this->workItem(['notes' => 'Klien belum bayar DP']);

        $changes = Audit::changes($item, ['notes' => 'Klien minta diskon besar'], Audit::labels('work_item'));
        $this->assertSame('Catatan: diubah', $changes);

        $summary = Audit::summary($item, Audit::labels('work_item'));
        $this->assertStringContainsString('Catatan: diisi', $summary);
        $this->assertStringNotContainsString('Klien', $summary);

        // Judul tetap tampil lengkap; hanya catatan yang disamarkan.
        $both = Audit::changes($item, ['title' => 'Approval lirik v2', 'notes' => 'rahasia'], Audit::labels('work_item'));
        $this->assertStringContainsString('Judul: Approval lirik → Approval lirik v2', $both);
        $this->assertStringContainsString('Catatan: diubah', $both);
        $this->assertStringNotContainsString('rahasia', $both);
    }

    public function test_catatan_dari_pic_dan_dashboard_tidak_menyimpan_isinya(): void
    {
        $item = $this->workItem();

        $this->actingAs($this->p['aldora'])->patch(route('employee.workItems.note', $item), ['notes' => 'Kendala dengan vendor X'])->assertSessionHasNoErrors();
        $detail = $this->lastLog('Catatan task diubah (oleh PIC)')->detail;
        $this->assertStringContainsString('Catatan: diubah', $detail);
        $this->assertStringNotContainsString('vendor X', $detail);

        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.tracker.items.note', $item), ['notes' => 'Rahasia internal'])->assertOk();
        $this->assertStringNotContainsString('Rahasia internal', $this->lastLog('Catatan task diubah')->detail);
    }

    // ---- Jam dan menit --------------------------------------------------

    public function test_nilai_waktu_dengan_jam_ditulis_sampai_menit_dan_tanggal_saja_tetap_ringkas(): void
    {
        $this->assertSame('21 Sep 2026, 09:30', Audit::value(Carbon::parse('2026-09-21 09:30:45')));
        $this->assertSame('21 Sep 2026', Audit::value(Carbon::parse('2026-09-21 00:00:00')));
        $this->assertSame('21 Sep 2026, 18:05', Audit::value(new \DateTimeImmutable('2026-09-21 18:05:00')));
    }

    public function test_keputusan_koreksi_presensi_menulis_jam_masuk_dan_pulang(): void
    {
        $correction = AttendanceCorrectionRequest::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-18',
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:15',
            'requested_mode' => 'kantor',
            'reason' => 'Lupa absen',
            'status' => 'pending',
        ]);

        $this->actingAs($this->p['manajer'])->post(route('approval.attendanceCorrection.approve', $correction))->assertSessionHas('status');

        $detail = $this->lastLog('Koreksi presensi disetujui')->detail;
        $this->assertStringContainsString('18 Sep 2026', $detail);
        $this->assertStringContainsString('masuk 09:30', $detail);
        $this->assertStringContainsString('pulang 18:15', $detail);
        $this->assertStringContainsString('mode kantor', $detail);
    }

    public function test_keputusan_lembur_dan_izin_menulis_tanggalnya(): void
    {
        $overtime = OvertimeRequest::create(['user_id' => $this->p['aldora']->id, 'date' => '2026-09-22', 'reason' => 'Deadline', 'status' => 'pending']);
        $leave = LeaveRequest::create([
            'user_id' => $this->p['aldora']->id,
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-24',
            'work_days' => 3,
            'reason' => 'Liburan',
            'status' => 'pending',
        ]);

        $manager = $this->actingAs($this->p['manajer']);
        $manager->post(route('approval.overtime.approve', $overtime))->assertSessionHas('status');
        $manager->post(route('approval.leave.approve', $leave))->assertSessionHas('status');

        $this->assertStringContainsString('tanggal 22 Sep 2026', $this->lastLog('Lembur disetujui')->detail);
        $leaveDetail = $this->lastLog('Izin/cuti disetujui')->detail;
        $this->assertStringContainsString('22 Sep 2026 s/d 24 Sep 2026', $leaveDetail);
        $this->assertStringContainsString('3 hari kerja', $leaveDetail);
    }

    // ---- Pengajuan karyawan sendiri ------------------------------------

    public function test_pengajuan_izin_dan_koreksi_dari_karyawan_tidak_masuk_audit_log_maupun_log_generik(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->post(route('employee.leave.store'), [
            'type' => 'izin_pribadi',
            'start_date' => '2026-09-24',
            'end_date' => '2026-09-24',
            'reason' => 'Urusan keluarga',
        ])->assertSessionHasNoErrors();
        $aldora->post(route('employee.attendanceCorrection.store'), [
            'date' => '2026-09-18',
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:00',
            'requested_mode' => 'kantor',
            'reason' => 'Lupa absen',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, LeaveRequest::count());
        $this->assertSame(1, AttendanceCorrectionRequest::count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // ---- Pembersihan log lama ------------------------------------------

    private function logAt(string $action, string $when): AuditLog
    {
        $log = AuditLog::query()->create(['action' => $action, 'detail' => 'x', 'actor_label' => 'System']);
        DB::table('audit_logs')->where('id', $log->id)->update(['created_at' => $when]);

        return $log;
    }

    public function test_prune_menghapus_log_lebih_tua_dari_masa_simpan(): void
    {
        $old = $this->logAt('Lama', Carbon::now()->subMonths(13)->toDateTimeString());
        $edge = $this->logAt('Hampir', Carbon::now()->subMonths(11)->toDateTimeString());
        $new = $this->logAt('Baru', Carbon::now()->toDateTimeString());

        $this->assertSame(1, AuditLog::pruneExpired());

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $edge->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $new->id]);
    }

    public function test_masa_simpan_bisa_diatur_dari_config(): void
    {
        config(['audit.retention_months' => 3]);
        $this->logAt('Empat bulan', Carbon::now()->subMonths(4)->toDateTimeString());
        $this->logAt('Dua bulan', Carbon::now()->subMonths(2)->toDateTimeString());

        $this->assertSame(1, AuditLog::pruneExpired());
    }

    public function test_membuka_audit_log_membersihkan_log_lama_maksimal_sekali_sehari(): void
    {
        Cache::forget('audit-log:pruned');
        $this->logAt('Lama 1', Carbon::now()->subMonths(14)->toDateTimeString());

        $this->actingAs($this->p['owner'])->get(route('dashboard.it.index'))->assertOk();
        $this->assertSame(0, AuditLog::query()->where('action', 'Lama 1')->count());

        // Pembukaan kedua di hari yang sama tidak membersihkan lagi.
        $this->logAt('Lama 2', Carbon::now()->subMonths(14)->toDateTimeString());
        $this->actingAs($this->p['owner'])->get(route('dashboard.it.index'))->assertOk();
        $this->assertSame(1, AuditLog::query()->where('action', 'Lama 2')->count());
    }

    public function test_perintah_audit_prune_berjalan(): void
    {
        $this->logAt('Lama', Carbon::now()->subMonths(20)->toDateTimeString());

        $this->artisan('audit:prune')->expectsOutputToContain('1 baris')->assertSuccessful();
    }

    // ---- Migrasi pembersih data lama -----------------------------------

    public function test_migrasi_membersihkan_data_audit_lama(): void
    {
        $insert = fn(string $action, string $detail, ?string $ip) => DB::table('audit_logs')->insertGetId([
            'action' => $action,
            'detail' => $detail,
            'ip_address' => $ip,
            'actor_label' => 'System',
            'created_at' => now(),
        ]);

        $login = $insert('Login berhasil', 'Aldora masuk ke aplikasi.', '10.0.0.1');
        $keepIp = $insert('Login gagal', 'Percobaan login gagal untuk aldora@wsm.local (password salah).', '10.0.0.2');
        $dropIp = $insert('Task diperbarui', 'Task "A": Judul: a → b.', '10.0.0.3');
        $unknown = $insert('Login gagal', 'Percobaan login gagal untuk rahasia123 (email tidak terdaftar).', '10.0.0.4');
        $note = $insert('Catatan task diubah', 'Task "A": Catatan: — → Klien minta diskon. Besar.', '10.0.0.5');
        $mixed = $insert('Task diperbarui', 'Task "A": Judul: a → b; Catatan: lama → baru; Link: — → https://x.test.', null);
        $other = $insert('Memo diperbarui', 'Memo "Catatan: penting"', null);

        // Nama file migrasi memuat timestamp yang bisa berbeda antar mesin, jadi dicari lewat polanya.
        $files = glob(database_path('migrations/*_clean_audit_logs_for_privacy.php'));
        $this->assertCount(1, $files, 'harus ada tepat satu migrasi clean_audit_logs_for_privacy');

        (require $files[0])->up();

        $this->assertDatabaseMissing('audit_logs', ['id' => $login]);
        $this->assertSame('10.0.0.2', DB::table('audit_logs')->where('id', $keepIp)->value('ip_address'));
        $this->assertNull(DB::table('audit_logs')->where('id', $dropIp)->value('ip_address'));

        $unknownRow = DB::table('audit_logs')->where('id', $unknown)->first();
        $this->assertSame('Percobaan login gagal: email tidak terdaftar.', $unknownRow->detail);
        $this->assertStringNotContainsString('rahasia123', $unknownRow->detail);

        $this->assertSame('Task "A": Catatan: diubah.', DB::table('audit_logs')->where('id', $note)->value('detail'));
        $this->assertSame('Task "A": Judul: a → b; Catatan: diubah; Link: — → https://x.test.', DB::table('audit_logs')->where('id', $mixed)->value('detail'));
        $this->assertSame('Memo "Catatan: penting"', DB::table('audit_logs')->where('id', $other)->value('detail'), 'aksi lain tidak disentuh');
    }
}