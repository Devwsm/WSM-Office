<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual C5–C15 — absen masuk/pulang dari App Mode karyawan:
 * geofence (Haversine di server), WFH, Lapangan/Gigs multi-sesi, selfie,
 * bentrok dengan cuti, auto-close "lupa absen pulang", dan riwayat bulanan.
 * Geolocation & kamera browser sungguhan tidak bisa dites di sini; yang dites
 * adalah kontrak server-nya (lat/lng/foto base64 yang dikirim browser).
 */
class AttendanceFlowTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private User $employee;

    private FilesystemAdapter $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday(); // Senin 2026-09-21 10:00
        $this->officeSetting();
        $this->private = Storage::fake('local');
        Storage::fake('public');

        $this->employee = $this->makeUser('karyawan');
    }

    private function clockIn(array $overrides = [])
    {
        return $this->actingAs($this->employee)->post('/app/absensi/masuk', array_merge(
            ['mode' => 'kantor'],
            $this->officeCoordinates(),
            $overrides,
        ));
    }

    private function clockOut(array $overrides = [])
    {
        return $this->actingAs($this->employee)->post('/app/absensi/pulang', array_merge(
            $this->officeCoordinates(),
            $overrides,
        ));
    }

    private function todaySessions()
    {
        return Attendance::where('user_id', $this->employee->id)->orderBy('session_number')->get();
    }

    // ---- C5 / C6 / C7: mode Kantor & geofence ---------------------------

    public function test_office_clock_in_inside_radius_is_recorded_with_distance(): void
    {
        $this->clockIn(['accuracy' => 12.6])
            ->assertSessionHas('status', 'Absen masuk berhasil dicatat.');

        $row = $this->todaySessions()->sole();
        $this->assertSame('kantor', $row->mode);
        $this->assertSame(1, $row->session_number);
        $this->assertSame('2026-09-21', $row->date->toDateString());
        $this->assertSame('10:00', $row->clock_in_at->format('H:i'));
        $this->assertSame(0, $row->clock_in_distance_meters);
        $this->assertTrue($row->clock_in_within_radius);
        $this->assertSame(13, $row->clock_in_accuracy_meters);
        $this->assertNull($row->clock_out_at);
    }

    public function test_office_clock_in_outside_radius_is_still_recorded_but_flagged(): void
    {
        $this->clockIn($this->farCoordinates())
            ->assertSessionHas('status', fn($msg) => str_contains($msg, 'di luar radius 200m'));

        $row = $this->todaySessions()->sole();
        $this->assertFalse($row->clock_in_within_radius);
        $this->assertGreaterThan(200, $row->clock_in_distance_meters);
    }

    public function test_when_strict_radius_is_off_distance_is_kept_but_never_flagged(): void
    {
        $this->officeSetting(['enforce_radius' => false]);

        $this->clockIn($this->farCoordinates())
            ->assertSessionHas('status', fn($msg) => ! str_contains($msg, 'di luar radius'));

        $row = $this->todaySessions()->sole();
        $this->assertTrue($row->clock_in_within_radius);
        $this->assertGreaterThan(200, $row->clock_in_distance_meters);
    }

    public function test_when_geo_attendance_is_off_no_distance_is_calculated_at_all(): void
    {
        $this->officeSetting(['geo_attendance_enabled' => false]);

        $this->clockIn($this->farCoordinates())->assertSessionHas('status');

        $row = $this->todaySessions()->sole();
        $this->assertNull($row->clock_in_distance_meters);
        $this->assertNull($row->clock_in_within_radius);
    }

    public function test_missing_location_is_rejected_with_helpful_message(): void
    {
        $this->actingAs($this->employee)->post('/app/absensi/masuk', ['mode' => 'kantor'])
            ->assertSessionHasErrors(['lat' => 'Lokasi belum kebaca. Coba "Test Lokasi" dulu atau izinkan akses lokasi di browser.']);

        $this->assertSame(0, Attendance::count());
    }

    public function test_out_of_range_coordinates_and_unknown_mode_are_rejected(): void
    {
        $this->clockIn(['lat' => 95])->assertSessionHasErrors('lat');
        $this->clockIn(['lng' => 200])->assertSessionHasErrors('lng');
        $this->clockIn(['mode' => 'liburan'])->assertSessionHasErrors('mode');
        $this->clockIn(['work_context' => str_repeat('x', 151)])->assertSessionHasErrors('work_context');

        $this->assertSame(0, Attendance::count());
    }

    // ---- C8: WFH --------------------------------------------------------

    public function test_wfh_clock_in_skips_distance_calculation(): void
    {
        $this->clockIn(['mode' => 'wfh', 'lat' => -6.9, 'lng' => 107.6, 'work_context' => 'Editing di rumah'])
            ->assertSessionHas('status');

        $row = $this->todaySessions()->sole();
        $this->assertSame('wfh', $row->mode);
        $this->assertSame('Editing di rumah', $row->work_context);
        $this->assertNull($row->clock_in_distance_meters);
        $this->assertNull($row->clock_in_within_radius);
    }

    // ---- C9: satu sesi per hari (Kantor/WFH) ---------------------------

    public function test_office_and_wfh_allow_only_one_session_per_day(): void
    {
        $this->clockIn()->assertSessionHas('status');

        // Masih terbuka → harus checkout dulu.
        $this->clockIn()->assertSessionHas('warning', fn($m) => str_contains($m, 'belum checkout'));

        $this->clockOut()->assertSessionHas('status');

        // Sudah checkout, tapi kantor/WFH tetap cuma 1 sesi.
        $this->clockIn()->assertSessionHas('warning', fn($m) => str_contains($m, 'cuma 1 sesi per hari'));
        $this->clockIn(['mode' => 'wfh'])->assertSessionHas('warning', fn($m) => str_contains($m, 'cuma 1 sesi per hari'));

        $this->assertCount(1, $this->todaySessions());
    }

    // ---- C10: Lapangan / Gigs multi-sesi --------------------------------

    public function test_field_mode_allows_sequential_sessions_but_not_overlapping_ones(): void
    {
        $this->clockIn(['mode' => 'lapangan'])->assertSessionHas('status', 'Absen masuk berhasil dicatat.');

        // Masuk lagi sebelum pulang ditolak.
        $this->clockIn(['mode' => 'lapangan'])->assertSessionHas('warning', fn($m) => str_contains($m, 'belum checkout'));

        $this->clockOut()->assertSessionHas('status', 'Absen pulang berhasil dicatat. Selamat istirahat!');

        // Sesi ke-2 diizinkan.
        $this->clockIn(['mode' => 'gigs'])->assertSessionHas('status', 'Absen masuk sesi ke-2 berhasil dicatat.');
        $this->clockOut()->assertSessionHas('status', 'Absen pulang sesi ke-2 berhasil dicatat.');

        $sessions = $this->todaySessions();
        $this->assertSame([1, 2], $sessions->pluck('session_number')->all());
        $this->assertSame(['lapangan', 'gigs'], $sessions->pluck('mode')->all());
        $this->assertTrue($sessions->every(fn($s) => $s->clock_out_at !== null));
    }

    // ---- C11: pulang tanpa masuk ---------------------------------------

    public function test_clock_out_without_clock_in_is_an_error(): void
    {
        $this->clockOut()->assertSessionHas('error', 'Kamu belum absen masuk hari ini.');
        $this->assertSame(0, Attendance::count());
    }

    public function test_clock_out_records_time_and_distance(): void
    {
        $this->clockIn();
        $this->freezeWorkday('2026-09-21 18:30:00');

        $this->clockOut($this->farCoordinates())->assertSessionHas('status');

        $row = $this->todaySessions()->sole();
        $this->assertSame('18:30', $row->clock_out_at->format('H:i'));
        $this->assertFalse($row->clock_out_within_radius);
        $this->assertGreaterThan(200, $row->clock_out_distance_meters);
        $this->assertSame(510, $row->workedMinutes());
    }

    public function test_clock_out_twice_is_rejected_because_no_open_session_remains(): void
    {
        $this->clockIn();
        $this->clockOut()->assertSessionHas('status');
        $this->clockOut()->assertSessionHas('error');
    }

    // ---- C12: selfie ----------------------------------------------------

    public function test_valid_selfie_is_stored_privately_for_both_clock_in_and_out(): void
    {
        $this->clockIn(['photo' => self::PNG]);
        $this->clockOut(['photo' => self::PNG]);

        $row = $this->todaySessions()->sole();

        $this->assertNotNull($row->clock_in_photo);
        $this->assertNotNull($row->clock_out_photo);
        $this->private->assertExists($row->clock_in_photo);
        $this->private->assertExists($row->clock_out_photo);
        $this->assertSame([], Storage::disk('public')->allFiles(), 'Selfie tidak boleh masuk disk publik.');
    }

    public function test_attendance_still_works_without_a_photo(): void
    {
        $this->clockIn()->assertSessionHas('status');

        $this->assertNull($this->todaySessions()->sole()->clock_in_photo);
    }

    public function test_fake_image_is_dropped_but_attendance_is_still_saved(): void
    {
        // Prefix "data:image/png" benar, tapi isinya bukan gambar.
        $this->clockIn(['photo' => 'data:image/png;base64,' . base64_encode('<?php echo 1;')])
            ->assertSessionHas('status');

        $this->assertNull($this->todaySessions()->sole()->clock_in_photo);
        $this->assertSame([], $this->private->allFiles());
    }

    public function test_malformed_photo_string_fails_validation(): void
    {
        $this->clockIn(['photo' => 'bukan-data-uri'])->assertSessionHasErrors('photo');
        $this->assertSame(0, Attendance::count());
    }

    // ---- C13: bentrok dengan cuti --------------------------------------

    public function test_clock_in_is_refused_while_on_approved_leave(): void
    {
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'type' => 'izin_sakit',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-22',
            'work_days' => 2,
            'reason' => 'Demam',
            'status' => 'disetujui',
        ]);

        $this->clockIn()->assertSessionHas('error', fn($m) => str_contains($m, 'sedang izin/cuti hari ini'));
        $this->assertSame(0, Attendance::count());
    }

    public function test_pending_or_rejected_leave_does_not_block_clock_in(): void
    {
        foreach (['pending', 'ditolak', 'dibatalkan'] as $status) {
            LeaveRequest::create([
                'user_id' => $this->employee->id,
                'type' => 'izin_pribadi',
                'start_date' => '2026-09-21',
                'end_date' => '2026-09-21',
                'work_days' => 1,
                'reason' => $status,
                'status' => $status,
            ]);
        }

        $this->clockIn()->assertSessionHas('status');
    }

    // ---- C14: lupa absen pulang (auto-close) ---------------------------

    public function test_forgotten_office_session_from_yesterday_is_closed_at_normal_end_time(): void
    {
        $yesterday = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-18 09:30:00',
        ]);

        $this->actingAs($this->employee)->get('/app/home')->assertOk();

        $yesterday->refresh();
        $this->assertTrue($yesterday->auto_closed);
        $this->assertSame('2026-09-18 20:00:00', $yesterday->clock_out_at->format('Y-m-d H:i:s'));
    }

    public function test_forgotten_session_with_approved_overtime_is_closed_at_end_of_day(): void
    {
        $row = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-18 09:30:00',
        ]);
        OvertimeRequest::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-18',
            'reason' => 'Deadline rilis',
            'status' => 'disetujui',
        ]);

        $this->actingAs($this->employee)->get('/app/riwayat')->assertOk();

        $row->refresh();
        $this->assertTrue($row->auto_closed);
        $this->assertSame('23:59:59', $row->clock_out_at->format('H:i:s'));
    }

    public function test_forgotten_field_session_is_closed_at_end_of_day(): void
    {
        $row = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'lapangan',
            'clock_in_at' => '2026-09-18 08:00:00',
        ]);

        $this->clockIn(); // reconcile jalan sebelum absen baru dicatat

        $this->assertSame('23:59:59', $row->fresh()->clock_out_at->format('H:i:s'));
    }

    public function test_todays_office_session_is_closed_once_the_normal_window_has_ended(): void
    {
        $row = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-21',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-21 09:30:00',
        ]);

        // Masih sebelum 20:00 → dibiarkan terbuka.
        $this->actingAs($this->employee)->get('/app/home')->assertOk();
        $this->assertNull($row->fresh()->clock_out_at);

        // Lewat 20:00 → ditutup pukul 20:00 hari yang sama.
        $this->freezeWorkday('2026-09-21 21:15:00');
        $this->actingAs($this->employee)->get('/app/home')->assertOk();

        $row->refresh();
        $this->assertTrue($row->auto_closed);
        $this->assertSame('20:00', $row->clock_out_at->format('H:i'));
    }

    public function test_todays_field_session_stays_open_past_the_normal_window(): void
    {
        $row = Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-21',
            'session_number' => 1,
            'mode' => 'gigs',
            'clock_in_at' => '2026-09-21 18:00:00',
        ]);

        $this->freezeWorkday('2026-09-21 22:00:00');
        $this->actingAs($this->employee)->get('/app/home')->assertOk();

        $this->assertNull($row->fresh()->clock_out_at);
    }

    public function test_after_auto_close_the_employee_can_clock_in_again_next_day(): void
    {
        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-18 09:30:00',
        ]);

        $this->clockIn()->assertSessionHas('status', 'Absen masuk berhasil dicatat.');
        $this->assertSame(2, Attendance::count());
    }

    // ---- C15: riwayat ---------------------------------------------------

    public function test_history_lists_only_own_records_of_the_selected_month(): void
    {
        $other = $this->makeUser('karyawan');

        foreach ([[$this->employee, '2026-09-10'], [$this->employee, '2026-08-12'], [$other, '2026-09-11']] as [$user, $date]) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $date,
                'session_number' => 1,
                'mode' => $user->is($this->employee) ? 'kantor' : 'wfh',
                'clock_in_at' => "{$date} 09:30:00",
                'clock_out_at' => "{$date} 18:00:00",
            ]);
        }

        $september = $this->actingAs($this->employee)->get('/app/riwayat')->assertOk();
        $september->assertViewHas('rows', fn($rows) => $rows->count() === 1 && $rows->first()->date->toDateString() === '2026-09-10');
        $september->assertViewHas('prevMonth', '2026-08');
        $september->assertViewHas('nextMonth', '2026-10');
        $september->assertViewHas('isCurrentMonth', true);

        $this->actingAs($this->employee)->get('/app/riwayat?bulan=2026-08')
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1 && $rows->first()->date->toDateString() === '2026-08-12')
            ->assertViewHas('isCurrentMonth', false);
    }

    public function test_history_falls_back_to_current_month_on_garbage_input(): void
    {
        $this->actingAs($this->employee)->get('/app/riwayat?bulan=ngawur')
            ->assertOk()
            ->assertViewHas('currentMonth', '2026-09');
    }

    public function test_history_shows_shortage_blocks_for_short_days(): void
    {
        // 09:30–13:30 = 240 menit dari wajib 480 → kurang 240 menit = 4 blok.
        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-09-15',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-15 09:30:00',
            'clock_out_at' => '2026-09-15 13:30:00',
        ]);

        $this->actingAs($this->employee)->get('/app/riwayat')
            ->assertOk()
            ->assertViewHas('shortage', fn($s) => $s['total_shortage_minutes'] === 240 && $s['blocks'] === 4 && $s['remainder_minutes'] === 0);
    }

    // ---- akses & throttle ----------------------------------------------

    public function test_attendance_endpoints_require_login(): void
    {
        Auth::logout();

        $this->post('/app/absensi/masuk', ['mode' => 'kantor'])->assertRedirect('/login');
        $this->post('/app/absensi/pulang', [])->assertRedirect('/login');
        $this->get('/app/riwayat')->assertRedirect('/login');
    }

    public function test_every_internal_role_can_clock_in(): void
    {
        foreach (['owner', 'manajer', 'hrd', 'karyawan'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->post('/app/absensi/masuk', array_merge(['mode' => 'wfh'], $this->officeCoordinates()))
                ->assertSessionHas('status');

            $this->assertSame(1, Attendance::where('user_id', $user->id)->count(), "Role {$role} gagal absen");
        }
    }

    public function test_clock_in_is_throttled_at_ten_requests_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->clockIn(['mode' => 'lapangan'])->assertRedirect();
        }

        $this->clockIn(['mode' => 'lapangan'])->assertStatus(429);
    }
}