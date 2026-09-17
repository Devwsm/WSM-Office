<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\DashboardAccess;
use App\Models\EmployeeContract;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Kpi;
use App\Models\LeaveRequest;
use App\Models\LegalDocument;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\Memo;
use App\Models\MemoRead;
use App\Models\MemoThreadMessage;
use App\Models\OvertimeRequest;
use App\Models\PayrollRecord;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\RoyaltyEntry;
use App\Models\SystemChangelog;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoSeeder
 * ---------------------------------------------------------------------
 * Isi data contoh MENYELURUH — semua modul yang ada di sidebar punya
 * data begitu `php artisan db:seed` (atau `migrate:fresh --seed`)
 * dijalankan, jadi tiap halaman bisa langsung dites tanpa harus isi
 * form manual satu-satu dulu. 5 user dasar (Owner, Kanaya/manajer,
 * Rania/hrd, Aldora & Gepeng/karyawan) TETAP DIPERTAHANKAN — cuma
 * dilengkapi field payroll (Fase 12) yang dulu belum diisi, plus tiap
 * modul dari Fase 9-17 sekarang punya baris contoh:
 * Attendance, Overtime, Koreksi Presensi, Meeting/MoM terstruktur, KPI,
 * Kontrak Karyawan, Payroll, Project Budget, Royalty, Legal, Audit Log,
 * System Changelog, Lowongan & Pelamar, Pesan Kontak.
 *
 * Jalankan:
 * php artisan migrate:fresh --seed
 * PENTING: ganti password default sebelum dipakai beneran.
 * ---------------------------------------------------------------------
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. USERS — Owner, Manajer, HRD, 2 Karyawan
        // =====================================================================
        $owner = User::create([
            'name' => 'Whisnu Santika',
            'email' => 'owner@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'job_title' => 'CEO',
            'division' => 'CEO Office',
        ]);

        $manajer = User::create([
            'name' => 'Kanaya',
            'email' => 'kanaya@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'manajer',
            'division' => 'Operations',
            'job_title' => 'Secretary / Admin',
            'manager_id' => $owner->id,
            'join_date' => '2025-09-15',
            // Fase 12 — field payroll, dulu belum diisi karena belum ada
            // fitur yang benar-benar makainya. Sekarang dipakai buat
            // generate PayrollRecord di bawah.
            'salary_base' => 9500000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 60000,
        ]);

        $aldora = User::create([
            'name' => 'Aldora',
            'email' => 'aldora@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'division' => 'Marketing',
            'job_title' => 'Social Media',
            'manager_id' => $manajer->id,
            'join_date' => '2026-02-01',
            'salary_base' => 6000000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 40000,
        ]);

        $gepeng = User::create([
            'name' => 'Gepeng',
            'email' => 'gepeng@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'division' => 'Creative',
            'job_title' => 'Visual Designer',
            'manager_id' => $manajer->id,
            'join_date' => '2026-05-20',
            'salary_base' => 6500000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 40000,
        ]);

        $rania = User::create([
            'name' => 'Rania',
            'email' => 'rania@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'hrd',
            'division' => 'Human Resources',
            'job_title' => 'HR & Recruitment',
            'manager_id' => $owner->id,
            'join_date' => '2026-04-01',
            'salary_base' => 7000000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 45000,
        ]);

        // =====================================================================
        // 2. DASHBOARD ACCESS — dilengkapi biar SEMUA 10 modul kelihatan
        // isinya minimal dari 1 user non-Owner (Owner selalu 'manage' di
        // semua modul lewat User::isOwner(), gak perlu baris di sini).
        // =====================================================================
        DashboardAccess::create(['user_id' => $rania->id, 'module' => 'people', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $rania->id, 'module' => 'recruitment', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $rania->id, 'module' => 'kpi', 'level' => 'view', 'granted_by' => $owner->id]);

        // Aldora (karyawan biasa) dikasih 'manage' modul Work Control,
        // Gepeng cuma 'view' — contoh nyata kenapa sistemnya per-user,
        // bukan per-role (dipertahankan dari seeder lama).
        DashboardAccess::create(['user_id' => $aldora->id, 'module' => 'work', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $gepeng->id, 'module' => 'work', 'level' => 'view', 'granted_by' => $owner->id]);

        // Kanaya (manajer) — 'people' 'view' (dipertahankan dari seeder
        // lama, buka Rekap Absensi & Persetujuan) + dilengkapi akses ke
        // modul manajerial lain (Budget, Royalty, KPI, Contracts,
        // Payroll manage; Legal & IT view) biar semua halaman itu bisa
        // langsung dites dari akun non-Owner juga.
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'people', 'level' => 'view', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'budget', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'royalty', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'kpi', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'contracts', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'payroll', 'level' => 'manage', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'legal', 'level' => 'view', 'granted_by' => $owner->id]);
        DashboardAccess::create(['user_id' => $manajer->id, 'module' => 'it', 'level' => 'view', 'granted_by' => $owner->id]);

        // =====================================================================
        // 3. MEMO & MoM (cepat, type=memo/mom di tabel `memos`)
        // =====================================================================
        $welcomeMemo = Memo::create([
            'type' => 'memo',
            'title' => 'Selamat datang di WSM Office System',
            'content' => "Halo tim! Mulai sekarang absensi, pengajuan izin/cuti, dan pengumuman internal dipusatkan di sini. Kalau ada kendala, hubungi HRD.",
            'pinned' => true,
            'audience' => 'semua',
            'active' => true,
            'created_by' => $owner->id,
        ]);
        Memo::create([
            'type' => 'mom',
            'title' => 'Kickoff Project Q3',
            'content' => "Bahas timeline & pembagian tugas project Q3. Draft brief dikirim H+1 ke masing-masing PIC.",
            'meeting_date' => now()->subDays(3)->toDateString(),
            'attendees' => 'Whisnu, Kanaya, Aldora',
            'audience' => 'semua',
            'active' => true,
            'created_by' => $manajer->id,
        ]);
        // Memo nonaktif (Fase 16, "Deactivate") — contoh biar halaman
        // manajemen kelihatan yang aktif & nonaktif bisa dibedakan.
        Memo::create([
            'type' => 'memo',
            'title' => 'Reminder Libur Nasional (lama)',
            'content' => 'Kantor tutup tanggal merah bulan lalu, sudah lewat — dimatikan biar gak nyampah di kartu Home.',
            'pinned' => false,
            'audience' => 'semua',
            'active' => false,
            'created_by' => $owner->id,
        ]);

        MemoThreadMessage::create([
            'memo_id' => $welcomeMemo->id,
            'user_id' => $aldora->id,
            'message' => 'Siap, izin cuti kemarin juga udah kelihatan approve-nya di sini. Makasih!',
        ]);
        MemoRead::create(['memo_id' => $welcomeMemo->id, 'user_id' => $gepeng->id, 'read_at' => now()->subDay()]);

        // =====================================================================
        // 4. CUTI (LeaveRequest) — contoh cuti tim bulan ini (banner Home)
        // =====================================================================
        if (now()->day <= 25) {
            $gepengLeave = LeaveRequest::create([
                'user_id' => $gepeng->id,
                'type' => 'cuti_tahunan',
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'work_days' => 2,
                'reason' => 'Acara keluarga',
                'status' => 'pending',
            ]);
            $gepengLeave->approveBy($manajer);
        }
        // Cuti yang masih pending, buat dites di halaman Persetujuan.
        LeaveRequest::create([
            'user_id' => $aldora->id,
            'type' => 'izin_sakit',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'work_days' => 1,
            'reason' => 'Demam, surat dokter menyusul.',
            'status' => 'pending',
        ]);

        // =====================================================================
        // 5. PROJECT & WORK ITEM (Fase 9)
        // =====================================================================
        $albumProject = Project::create([
            'slug' => Project::uniqueSlugFrom('Album Q3 Release'),
            'name' => 'Album Q3 Release',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'priority' => 'High',
            'status' => 'On Development',
            'lead_employee_id' => $manajer->id,
            'created_by' => $owner->id,
        ]);

        // Project kedua — biar Timeline Calendar/Work Tracker board
        // kelihatan >1 warna project, dan Project Budgeting punya >1
        // project buat dipilih di form.
        $merchProject = Project::create([
            'slug' => Project::uniqueSlugFrom('Merch Drop Oktober'),
            'name' => 'Merch Drop Oktober',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addWeeks(3)->toDateString(),
            'priority' => 'Medium',
            'status' => 'Follow Up',
            'lead_employee_id' => $gepeng->id,
            'created_by' => $manajer->id,
        ]);

        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'RELEASE PLAN',
            'title' => 'Finalisasi artwork cover album',
            'due_date' => now()->subDays(2)->toDateString(), // KELEWAT
            'pic_employee_id' => $aldora->id,
            'progress' => 'On Development',
            'priority' => 'High',
            'notes' => 'Tinggal approval final dari Whisnu.',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'MARKETING',
            'title' => 'Posting teaser single ke Instagram',
            'due_date' => now()->toDateString(), // HARI INI
            'pic_employee_id' => $aldora->id,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'CREATIVE',
            'title' => 'Review moodboard visual promo',
            'due_date' => now()->addDay()->toDateString(), // BESOK
            'pic_employee_id' => $gepeng->id,
            'progress' => 'Follow Up',
            'priority' => 'Medium',
            'notes' => 'Nunggu revisi dari klien.',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'AUDIO',
            'title' => 'Mixing & mastering track 5',
            'due_date' => now()->addDays(4)->toDateString(), // MINGGU INI/DEPAN
            'pic_employee_id' => $gepeng->id,
            'progress' => 'On Development',
            'priority' => 'High',
            'link' => 'https://drive.google.com/example-mixing-track5',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'section' => 'GENERAL AFFAIRS',
            'title' => 'Update inventaris alat kantor',
            'due_date' => null, // No date — General WSM (project_id null)
            'pic_employee_id' => $aldora->id,
            'progress' => 'Pending',
            'priority' => 'Low',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'RELEASE DAY',
            'title' => 'Brief press conference',
            'due_date' => now()->addDays(2)->toDateString(),
            'pic_employee_id' => $manajer->id,
            'progress' => 'Confirmed',
            'priority' => 'High',
            'created_by' => $owner->id,
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'section' => 'SONG',
            'title' => 'Approval lirik single terakhir',
            'due_date' => now()->subDays(1)->toDateString(),
            'pic_employee_id' => $aldora->id,
            'progress' => 'Done',
            'priority' => 'Medium',
            'created_by' => $manajer->id,
        ]);
        WorkItem::create([
            'project_id' => $merchProject->id,
            'section' => 'MARKETING',
            'title' => 'Kurasi desain hoodie & tote bag',
            'due_date' => now()->addDays(6)->toDateString(),
            'pic_employee_id' => $gepeng->id,
            'progress' => 'On Development',
            'priority' => 'Medium',
            'created_by' => $manajer->id,
        ]);

        // =====================================================================
        // 6. MEETING (MoM terstruktur, Fase 9) + attendee + action item
        //    Salah satu action item digenerate jadi WorkItem, biar relasi
        //    `MeetingActionItem::workItem()` kelihatan hasilnya juga.
        // =====================================================================
        $kickoffMeeting = Meeting::create([
            'project_id' => $albumProject->id,
            'date' => now()->subDays(3)->toDateString(),
            'time' => '10:00',
            'agenda' => 'Kickoff & pembagian tugas Album Q3 Release',
            'persons_text' => 'Whisnu, Kanaya, Aldora, Gepeng',
            'notes' => 'Semua sepakat target rilis akhir kuartal ini.',
            'decisions' => 'Artwork final disetujui Whisnu paling lambat minggu depan.',
            'blasted_at' => now()->subDays(3)->addHour(),
            'created_by' => $manajer->id,
        ]);
        $kickoffMeeting->attendees()->attach([$owner->id, $manajer->id, $aldora->id, $gepeng->id]);

        $actionItem1 = MeetingActionItem::create([
            'meeting_id' => $kickoffMeeting->id,
            'task' => 'Kirim draft artwork revisi ke Whisnu',
            'pic_employee_id' => $aldora->id,
            'pic_all' => false,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        WorkItem::create([
            'project_id' => $albumProject->id,
            'meeting_action_item_id' => $actionItem1->id,
            'section' => 'RELEASE PLAN',
            'title' => 'From MoM: Kirim draft artwork revisi ke Whisnu',
            'due_date' => now()->addDays(2)->toDateString(),
            'pic_employee_id' => $aldora->id,
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $manajer->id,
        ]);
        MeetingActionItem::create([
            'meeting_id' => $kickoffMeeting->id,
            'task' => 'Briefing seluruh tim soal timeline rilis',
            'pic_all' => true,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        // =====================================================================
        // 7. ATTENDANCE (Fase 4 & 7) — 5 hari kerja terakhir (Senin-Jumat,
        // melompati weekend) buat Aldora & Gepeng, dilengkapi contoh
        // Terlambat, Lupa Absen Pulang (auto_closed), dan mode Lapangan
        // multi-sesi (Fase 7) buat Kanaya.
        //
        // Cara nyari "N hari kerja terakhir" TIDAK dihardcode ke offset
        // tanggal tertentu (mis. subDays(4) selalu weekday) karena hasilnya
        // beda-beda tergantung kapan seeder ini dijalankan — dihitung jalan
        // (`$offset` naik terus sampai ketemu N hari kerja), tanggal
        // hasilnya disimpan ke array biar section Koreksi Presensi di bawah
        // bisa nunjuk balik ke tanggal yang BENERAN ada baris attendance-nya
        // (menghindari coba bikin 2 baris attendance user+tanggal+sesi yang
        // sama, yang bakal kena unique constraint DB).
        // =====================================================================
        $attendanceDatesByEmployee = [];

        foreach ([$aldora, $gepeng] as $employee) {
            $workDates = [];
            $offset = 1;
            while (count($workDates) < 5 && $offset <= 14) {
                $date = now()->subDays($offset);
                $offset++;
                if ($date->isWeekend()) {
                    continue;
                }
                $workDates[] = $date->copy();
            }

            foreach ($workDates as $index => $date) {
                $isLate = $index === 1; // hari kerja ke-2 (dari yang terlama) datang terlambat

                Attendance::create([
                    'user_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'session_number' => 1,
                    'mode' => 'kantor',
                    'work_context' => 'Kantor Depok',
                    'clock_in_at' => $date->copy()->setTime($isLate ? 10 : 9, $isLate ? 5 : 25),
                    'clock_in_within_radius' => true,
                    'clock_out_at' => $date->copy()->setTime(18, 5),
                    'clock_out_within_radius' => true,
                ]);
            }

            $attendanceDatesByEmployee[$employee->id] = $workDates;
        }

        // Lupa absen pulang (auto-closed sistem) — tanggal TERPISAH dari 5
        // hari kerja di atas (2 minggu lalu), biar gak bentrok unique
        // constraint (user_id, date, session_number).
        $gepengForgottenCheckoutDate = now()->subDays(15);
        Attendance::create([
            'user_id' => $gepeng->id,
            'date' => $gepengForgottenCheckoutDate->toDateString(),
            'session_number' => 1,
            'mode' => 'wfh',
            'work_context' => 'WFH',
            'clock_in_at' => $gepengForgottenCheckoutDate->copy()->setTime(9, 20),
            'clock_out_at' => $gepengForgottenCheckoutDate->copy()->setTime(20, 0),
            'auto_closed' => true,
        ]);

        // Mode Lapangan, multi-sesi dalam 1 hari (kebijakan WFO v18) — Kanaya.
        $kanayaLapanganDate = now()->subDay();
        Attendance::create([
            'user_id' => $manajer->id,
            'date' => $kanayaLapanganDate->toDateString(),
            'session_number' => 1,
            'mode' => 'lapangan',
            'work_context' => 'Meeting vendor percetakan merch',
            'clock_in_at' => $kanayaLapanganDate->copy()->setTime(9, 0),
            'clock_out_at' => $kanayaLapanganDate->copy()->setTime(11, 30),
        ]);
        Attendance::create([
            'user_id' => $manajer->id,
            'date' => $kanayaLapanganDate->toDateString(),
            'session_number' => 2,
            'mode' => 'lapangan',
            'work_context' => 'Survey lokasi listening session',
            'clock_in_at' => $kanayaLapanganDate->copy()->setTime(13, 30),
            'clock_out_at' => $kanayaLapanganDate->copy()->setTime(17, 0),
        ]);

        // =====================================================================
        // 8. LEMBUR (OvertimeRequest, Fase 7)
        // =====================================================================
        OvertimeRequest::create([
            'user_id' => $aldora->id,
            'date' => now()->addDay()->toDateString(),
            'reason' => 'Kejar deadline posting teaser sebelum rilis single.',
            'status' => 'pending',
        ]);
        $overtimeApproved = OvertimeRequest::create([
            'user_id' => $gepeng->id,
            'date' => $attendanceDatesByEmployee[$gepeng->id][0]->toDateString(),
            'reason' => 'Revisi moodboard sampai malam sesuai request klien.',
            'status' => 'pending',
        ]);
        $overtimeApproved->approveBy($manajer);

        // =====================================================================
        // 9. KOREKSI PRESENSI (AttendanceCorrectionRequest, 2026-09-16)
        // Kedua contoh nunjuk ke tanggal yang BENERAN sudah punya baris
        // attendance dari section 7 di atas, biar applyToAttendance()
        // (dipanggil approveBy()) nemu barisnya & bukan bikin baris baru.
        // =====================================================================
        AttendanceCorrectionRequest::create([
            'user_id' => $gepeng->id,
            'date' => $gepengForgottenCheckoutDate->toDateString(),
            'requested_clock_out' => '19:45',
            'requested_mode' => 'wfh',
            'reason' => 'Lupa tap pulang, baru sadar pas cek riwayat.',
            'status' => 'pending',
        ]);
        $correctionApproved = AttendanceCorrectionRequest::create([
            'user_id' => $aldora->id,
            'date' => $attendanceDatesByEmployee[$aldora->id][1]->toDateString(), // hari yang tadi ditandai terlambat
            'requested_clock_in' => '09:20',
            'requested_mode' => 'kantor',
            'reason' => 'Jam masuk salah kecatat karena GPS lambat konek.',
            'status' => 'pending',
        ]);
        $correctionApproved->approveBy($manajer);

        // =====================================================================
        // 10. KPI (Fase 10) — 3 contoh, skor beda-beda biar 3 warna badge
        // (hijau >=90%, kuning >=60%, merah <60%) kelihatan semua.
        // =====================================================================
        Kpi::create([
            'employee_id' => $aldora->id,
            'title' => 'Reach Instagram Bulanan',
            'period' => 'Q3 2026',
            'target' => 100000,
            'current' => 96000,
            'unit' => 'reach',
            'weight' => 40,
            'due_date' => now()->endOfMonth()->toDateString(),
            'status' => 'Active',
            'created_by' => $manajer->id,
        ]);
        Kpi::create([
            'employee_id' => $gepeng->id,
            'title' => 'Jumlah Asset Visual Selesai',
            'period' => 'Q3 2026',
            'target' => 20,
            'current' => 13,
            'unit' => 'asset',
            'weight' => 30,
            'due_date' => now()->endOfMonth()->toDateString(),
            'status' => 'Active',
            'created_by' => $manajer->id,
        ]);
        Kpi::create([
            'employee_id' => $manajer->id,
            'title' => 'Ketepatan Timeline Project',
            'period' => 'Q2 2026',
            'target' => 100,
            'current' => 45,
            'unit' => '%',
            'weight' => 30,
            'due_date' => now()->subMonth()->toDateString(),
            'status' => 'Completed',
            'owner_note' => 'Banyak delay dari vendor eksternal, sudah dievaluasi.',
            'created_by' => $owner->id,
        ]);

        // =====================================================================
        // 11. KONTRAK KARYAWAN (EmployeeContract, Fase 11)
        // Path file DUMMY (bukan file fisik beneran) — cukup buat nguji
        // tampilan listing/download link, ganti dengan upload asli lewat
        // form kalau mau tes storage:link beneran.
        // =====================================================================
        EmployeeContract::create([
            'employee_id' => $aldora->id,
            'file_path' => 'contracts/dummy-kontrak-aldora.pdf',
            'original_filename' => 'Kontrak Kerja - Aldora.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 245000,
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(), // segera berakhir
            'notes' => 'Kontrak tahun pertama, evaluasi perpanjangan H-30.',
            'uploaded_by' => $rania->id,
        ]);
        EmployeeContract::create([
            'employee_id' => $gepeng->id,
            'file_path' => 'contracts/dummy-kontrak-gepeng.pdf',
            'original_filename' => 'Kontrak Kerja - Gepeng.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 238000,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->addMonths(9)->toDateString(),
            'uploaded_by' => $rania->id,
        ]);

        // =====================================================================
        // 12. PAYROLL (PayrollRecord, Fase 12)
        // =====================================================================
        $aldoraPayroll = new PayrollRecord([
            'user_id' => $aldora->id,
            'period' => now()->subMonth()->format('Y-m'),
            'base_salary' => $aldora->salary_base,
            'overtime_amount' => 40000,
            'shortage_deduction' => 0,
            'other_adjustment' => 0,
            'status' => 'finalized',
            'notes' => 'Payroll bulan lalu, sudah difinalisasi.',
            'generated_by' => $manajer->id,
        ]);
        $aldoraPayroll->recalculateTotal();
        $aldoraPayroll->save();

        $gepengPayroll = new PayrollRecord([
            'user_id' => $gepeng->id,
            'period' => now()->format('Y-m'),
            'base_salary' => $gepeng->salary_base,
            'overtime_amount' => 0,
            'shortage_deduction' => 50000,
            'other_adjustment' => 0,
            'status' => 'draft',
            'notes' => 'Masih draft, nunggu rekap kehadiran akhir bulan.',
            'generated_by' => $manajer->id,
        ]);
        $gepengPayroll->recalculateTotal();
        $gepengPayroll->save();

        // =====================================================================
        // 13. PROJECT BUDGETING (ProjectBudget, Fase 13)
        // =====================================================================
        ProjectBudget::create([
            'project_id' => $albumProject->id,
            'category' => 'Produksi',
            'item' => 'Studio rekaman & mixing',
            'budget' => 25000000,
            'actual' => 27500000, // over budget, contoh variance minus
            'note' => 'Nambah 1 sesi mixing ulang.',
            'updated_by' => $manajer->id,
        ]);
        ProjectBudget::create([
            'project_id' => $albumProject->id,
            'category' => 'Marketing',
            'item' => 'Ads Instagram & TikTok',
            'budget' => 10000000,
            'actual' => 6200000,
            'updated_by' => $manajer->id,
        ]);
        ProjectBudget::create([
            'project_id' => $merchProject->id,
            'category' => 'Produksi',
            'item' => 'Cetak hoodie & tote bag',
            'budget' => 15000000,
            'actual' => 15000000,
            'note' => 'Sesuai budget, sudah PO ke vendor.',
            'updated_by' => $gepeng->id,
        ]);

        // =====================================================================
        // 14. ROYALTY DASHBOARD (RoyaltyEntry, Fase 13)
        // 3 status beda biar semua badge kelihatan.
        // =====================================================================
        RoyaltyEntry::create([
            'title' => 'Streaming Royalty - Single "Cahaya"',
            'period' => now()->subMonth()->format('Y-m'),
            'source' => 'Spotify & Apple Music',
            'status' => 'Paid',
            'gross' => 18500000,
            'share_pct' => 70,
            'recoup' => 2000000,
            'updated_by' => $manajer->id,
        ]);
        RoyaltyEntry::create([
            'title' => 'Sync License - Iklan Brand X',
            'period' => now()->format('Y-m'),
            'source' => 'Direct Licensing',
            'status' => 'Ready to Pay',
            'gross' => 12000000,
            'share_pct' => 50,
            'recoup' => 0,
            'updated_by' => $manajer->id,
        ]);
        RoyaltyEntry::create([
            'title' => 'Estimasi Royalty Album Q3',
            'period' => now()->addMonth()->format('Y-m'),
            'source' => 'Estimasi awal',
            'status' => 'Estimated',
            'gross' => 30000000,
            'share_pct' => 70,
            'recoup' => 5000000,
            'note' => 'Angka masih estimasi, nunggu laporan resmi label.',
            'updated_by' => $owner->id,
        ]);

        // =====================================================================
        // 15. LEGAL — Album Contracts & Royalty Agreements (LegalDocument, Fase 14)
        // =====================================================================
        LegalDocument::create([
            'category' => 'album',
            'title' => 'Perjanjian Rilis Album Q3',
            'party' => 'PT Label Musik Nusantara',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(), // segera berakhir
            'file_path' => 'legal/dummy-perjanjian-rilis-album.pdf',
            'original_filename' => 'Perjanjian Rilis Album Q3.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 512000,
            'notes' => 'Perlu direview ulang sebelum tanggal berakhir.',
            'created_by' => $owner->id,
        ]);
        LegalDocument::create([
            'category' => 'royalty',
            'title' => 'Perjanjian Bagi Hasil Royalti Streaming',
            'party' => 'Distributor Digital ABC',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'file_path' => 'legal/dummy-perjanjian-royalti.pdf',
            'original_filename' => 'Perjanjian Royalti Streaming.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 398000,
            'created_by' => $owner->id,
        ]);

        // =====================================================================
        // 16. IT — Audit Log & System Changelog (Fase 15)
        // =====================================================================
        AuditLog::record('Login', "{$owner->name} login ke sistem.", $owner);
        AuditLog::record('Ubah Dashboard Access', "Owner mengubah akses modul untuk {$manajer->name}.", $owner);
        AuditLog::record('Buat Memo', "{$manajer->name} membuat memo \"Kickoff Project Q3\".", $manajer);
        AuditLog::record('Generate Payroll', "Payroll periode " . now()->subMonth()->format('Y-m') . " untuk {$aldora->name} digenerate.", $manajer);
        AuditLog::record('Backup Otomatis', 'Backup database harian berhasil dijalankan.', 'System');

        SystemChangelog::create([
            'version' => 'v1.0',
            'release_date' => now()->subDays(10)->toDateString(),
            'status' => 'Released',
            'modules' => ['Absensi', 'Izin/Cuti', 'Lembur', 'Work Control'],
            'title' => 'Rilis Awal WSM Office System',
            'changes' => [
                'Absensi geo-based Kantor/WFH',
                'Pengajuan izin/cuti & lembur',
                'Work Tracker & Shared Calendar',
            ],
            'created_by' => $owner->id,
        ]);
        SystemChangelog::create([
            'version' => 'v1.1',
            'release_date' => now()->addDays(14)->toDateString(),
            'status' => 'Planned',
            'modules' => ['Payroll', 'Legal', 'IT'],
            'title' => 'Payroll Otomatis & Legal Module',
            'changes' => [
                'Generate payroll bulanan otomatis',
                'Monitoring kontrak album & royalti',
                'Audit log & system changelog',
            ],
            'created_by' => $owner->id,
        ]);

        // =====================================================================
        // 17. REKRUTMEN — Lowongan & Pelamar (JobOpening/JobApplication)
        // =====================================================================
        $openingSocial = JobOpening::create([
            'title' => 'Social Media Specialist',
            'slug' => Str::slug('Social Media Specialist'),
            'division' => 'Marketing',
            'employment_type' => 'full_time',
            'description' => 'Mengelola konten & strategi media sosial untuk seluruh roster WSM.',
            'requirements' => "Min. 1 tahun pengalaman social media\nTerbiasa dengan Instagram & TikTok analytics",
            'status' => 'published',
            'created_by' => $rania->id,
            'published_at' => now()->subDays(5),
        ]);
        JobOpening::create([
            'title' => 'Backend Developer (Laravel)',
            'slug' => Str::slug('Backend Developer Laravel'),
            'division' => 'IT',
            'employment_type' => 'contract',
            'description' => 'Bantu maintain & kembangkan sistem internal WSM Office.',
            'requirements' => "Familiar dengan Laravel & MySQL\nBisa kerja remote",
            'status' => 'draft',
            'created_by' => $rania->id,
        ]);

        JobApplication::create([
            'job_opening_id' => $openingSocial->id,
            'name' => 'Dinda Puspita',
            'email' => 'dinda.puspita@example.com',
            'phone' => '081234567801',
            'message' => 'Saya sudah 2 tahun mengelola akun Instagram brand fashion lokal.',
            'status' => 'interview',
        ]);
        JobApplication::create([
            'job_opening_id' => $openingSocial->id,
            'name' => 'Reza Firmansyah',
            'email' => 'reza.firmansyah@example.com',
            'phone' => '081234567802',
            'message' => 'Tertarik karena suka dunia musik & sudah biasa bikin konten TikTok.',
            'status' => 'ditinjau',
        ]);
        JobApplication::create([
            'job_opening_id' => $openingSocial->id,
            'name' => 'Melati Anggraini',
            'email' => 'melati.anggraini@example.com',
            'message' => 'Fresh graduate, semangat belajar.',
            'status' => 'baru',
        ]);

        // =====================================================================
        // 18. PESAN KONTAK (ContactMessage, Fase 1 susulan)
        // =====================================================================
        ContactMessage::create([
            'name' => 'Studio Rekaman Mitra',
            'email' => 'kontak@studiomitrra.example.com',
            'message' => 'Halo, kami ingin menawarkan kerja sama sewa studio untuk sesi rekaman berikutnya.',
            'status' => 'baru',
        ]);
        $readMessage = ContactMessage::create([
            'name' => 'Fan Klub WSM',
            'email' => 'fanklub@example.com',
            'message' => 'Kapan jadwal listening session album terbaru dibuka untuk umum?',
            'status' => 'baru',
        ]);
        $readMessage->markRead();
    }
}