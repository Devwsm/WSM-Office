<?php

namespace Database\Seeders;

use App\Models\DashboardAccess;
use App\Models\LeaveRequest;
use App\Models\Memo;
use App\Models\MemoRead;
use App\Models\MemoThreadMessage;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder
 * ---------------------------------------------------------------------
 * Isi data contoh: 1 Owner, 1 Manajer, 1 HRD, 2 Karyawan — biar bisa
 * langsung dicoba login & lihat org-chart (Fase 2). Sejak Fase 6a/6b
 * juga isi contoh dashboard_access & memo/MoM biar fitur itu langsung
 * kelihatan hasilnya abis seed (Aldora dikasih 'manage' modul Work
 * biar bisa dites bikin memo, Gepeng dikasih 'view' doang biar kelihatan
 * bedanya sama 'manage'). Jalankan:
 * php artisan db:seed --class=DemoSeeder
 * PENTING: ganti password default sebelum dipakai beneran.
 * ---------------------------------------------------------------------
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
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
        ]);

        // Rania (hrd) — 'manage' di `people` (butuh koreksi absensi,
        // bukan cuma lihat) + `recruitment` (kerjaan utamanya). Sama
        // alasan refactor "permission bukan role" seperti grant Kanaya
        // di bawah (dulu 'hrd'/'manajer' otomatis buka layar ini lewat
        // role, sekarang lewat dashboard_access).
        DashboardAccess::create([
            'user_id' => $rania->id,
            'module' => 'people',
            'level' => 'manage',
            'granted_by' => $owner->id,
        ]);
        DashboardAccess::create([
            'user_id' => $rania->id,
            'module' => 'recruitment',
            'level' => 'manage',
            'granted_by' => $owner->id,
        ]);

        // --- Fase 6a: contoh dashboard_access ---
        // Aldora (karyawan biasa) dikasih 'manage' modul Work Control
        // walau jabatannya bukan Manajer/HRD/Owner — ini contoh nyata
        // kenapa sistemnya per-user, bukan per-role. Gepeng cuma 'view'
        // biar kelihatan bedanya (gak ada tombol tambah/edit/hapus).
        DashboardAccess::create([
            'user_id' => $aldora->id,
            'module' => 'work',
            'level' => 'manage',
            'granted_by' => $owner->id,
        ]);
        DashboardAccess::create([
            'user_id' => $gepeng->id,
            'module' => 'work',
            'level' => 'view',
            'granted_by' => $owner->id,
        ]);

        // 2026-09-09 — refactor "permission bukan role": Rekap Absensi
        // & Persetujuan sekarang gerbangnya modul `people`, bukan lagi
        // `role:manajer,owner,hrd`. Kanaya (manajer) dikasih `people`
        // 'view' di sini biar demo tetap bisa lihat Rekap Absensi &
        // Persetujuan abis seed — SAMA seperti backfill migration
        // `backfill_dashboard_access_for_manajer_hrd` buat instalasi
        // yang sudah jalan. Approve/reject request TETAP murni relasi
        // `manager_id` (lihat App\Models\DashboardAccess doc-comment),
        // level 'view' di sini cuma buka gerbang masuk layarnya.
        DashboardAccess::create([
            'user_id' => $manajer->id,
            'module' => 'people',
            'level' => 'view',
            'granted_by' => $owner->id,
        ]);

        // --- Fase 6b: contoh Memo & MoM ---
        $welcomeMemo = Memo::create([
            'type' => 'memo',
            'title' => 'Selamat datang di WSM Office System',
            'content' => "Halo tim! Mulai sekarang absensi, pengajuan izin/cuti, dan pengumuman internal dipusatkan di sini. Kalau ada kendala, hubungi HRD.",
            'pinned' => true,
            'created_by' => $owner->id,
        ]);
        Memo::create([
            'type' => 'mom',
            'title' => 'Kickoff Project Q3',
            'content' => "Bahas timeline & pembagian tugas project Q3. Draft brief dikirim H+1 ke masing-masing PIC.",
            'meeting_date' => now()->subDays(3)->toDateString(),
            'attendees' => 'Whisnu, Kanaya, Aldora',
            'created_by' => $manajer->id,
        ]);

        // --- Fase 8: contoh thread reply & status baca, biar Memo Forum
        // langsung kelihatan hasilnya abis seed (sama pola kayak
        // dashboard_access di atas). Aldora reply ke memo welcome →
        // muncul di badge unread sidebar "Work Control" (siapa pun yang
        // manage-level, di seed ini cuma Owner & Aldora) SAMPAI Aldora
        // sendiri (yang manage) buka /dashboard/work. Gepeng udah baca
        // tapi belum reply. Rania (HRD, gak punya akses modul work sama
        // sekali) SENGAJA gak disentuh di sini — dia tetap bisa lihat &
        // reply dari Home walau gak punya dashboard_access modul work.
        MemoThreadMessage::create([
            'memo_id' => $welcomeMemo->id,
            'user_id' => $aldora->id,
            'message' => 'Siap, izin cuti kemarin juga udah kelihatan approve-nya di sini. Makasih!',
        ]);
        MemoRead::create([
            'memo_id' => $welcomeMemo->id,
            'user_id' => $gepeng->id,
            'read_at' => now()->subDay(),
        ]);

        // --- Fase 8: contoh cuti tim bulan ini — buat demo banner Home.
        // Cuma dibuat kalau tanggal seed-nya masih dalam bulan berjalan
        // (biar gak numpuk cuti "basi" tiap kali db:seed diulang di
        // bulan yang beda).
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

        // --- Fase 9 (2026-09-09): contoh Project & WorkItem, biar "My
        // Work Tracker" & "Shared Calendar" langsung kelihatan hasilnya
        // abis seed (sama pola kayak dashboard_access/memo di atas) —
        // TANPA ini, dua widget itu bakal keliatan kosong terus karena
        // belum ada UI buat Owner/PIC bikin WorkItem baru (Fase 9 sisi
        // admin — Projects CRUD, Work Tracker board — belum digarap,
        // lihat README). Rentang tanggal SENGAJA nyebar (kelewat, hari
        // ini, besok, minggu ini, gak ada tanggal) biar semua warna
        // focus pill (`WorkItem::computedFocus()`) kelihatan pas dites.
        $albumProject = Project::create([
            // 2026-09-09 — FIX: awalnya nggak nulis 'slug' di sini,
            // ngandelin creating()-hook di Project::booted() buat
            // ngisi otomatis. Ternyata pas migrate:fresh --seed jalan
            // beneran, hook itu gak keisi ke query INSERT (kemungkinan
            // terkait Project pakai atribut PHP `#[Fillable(...)]`,
            // bukan `protected $fillable` klasik — entah kenapa
            // interaksinya sama static::creating() beda dari yang
            // diharapkan). JobOpeningController::store() juga SELALU
            // set slug eksplisit sebelum create/save (lihat
            // `$data['slug'] = $this->uniqueSlugFrom(...)` di situ) —
            // bukan ngandelin hook otomatis di model manapun. Diikutin
            // pola yang sama di sini biar konsisten & pasti jalan,
            // daripada debug behavior attribute-based fillable itu
            // (di luar scope Fase 9).
            'slug' => Project::uniqueSlugFrom('Album Q3 Release'),
            'name' => 'Album Q3 Release',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'priority' => 'High',
            'status' => 'On Development',
            'lead_employee_id' => $manajer->id,
            'created_by' => $owner->id,
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
    }
}