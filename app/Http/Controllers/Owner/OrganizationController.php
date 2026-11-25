<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DashboardAccess;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * OrganizationController (Owner & Developer)
 * ---------------------------------------------------------------------
 * Bagan hierarki dari AKUN yang sudah ada: tiap karyawan aktif ditaruh di
 * bawah Atasan Langsung-nya (`users.manager_id`). Karyawan nonaktif
 * (soft deleted) tidak ikut; bawahan yang atasannya nonaktif menjadi akar.
 * Tampilan mengikuti gaya gambar "ORGANIZATION NEW" (kartu berwarna,
 * bagan pohon di desktop, daftar bertingkat di HP), tanpa data buatan.
 *
 * Klik kartu membuka pop-up akses akun itu. Data akses dihitung di server
 * dari method User yang sama dengan sidebar, dikirim sebagai JSON.
 * ---------------------------------------------------------------------
 */
class OrganizationController extends Controller
{
    /** Halaman khusus area /owner. Developer = Tingkat 2 (tanpa Akses Dashboard). */
    private const OWNER_PAGES = [
        'Dashboard Owner',
        'Karyawan — tambah, ubah, nonaktifkan akun',
        'Struktur Organisasi',
        'Pengaturan Kantor — lokasi, jam kerja, warna',
        'Kelompok Tim',
        'Pesan Kontak dari website',
    ];

    /** Warna kartu per tim kerja (padanan warna di gambar hierarki). */
    private const TONES = [
        'management' => 'pink',
        'hr' => 'pink',
        'marketing' => 'blue',
        'creative' => 'green',
        'ga' => 'yellow',
    ];

    public function index()
    {
        $users = User::query()->with('dashboardAccess')->orderBy('name')->get();
        $byManager = $users->groupBy(fn(User $user) => $users->contains('id', $user->manager_id) ? $user->manager_id : 'root');

        $nodes = [];
        $access = [];
        $seen = [];
        $tree = [];
        foreach ($byManager->get('root', collect()) as $root) {
            $tree[] = $this->node($root, 0, $byManager, $nodes, $access, $seen);
        }

        return view('owner.organization.index', [
            'tree' => $tree,
            'payload' => ['nodes' => $nodes, 'access' => $access],
            'totalAkun' => $users->count(),
        ]);
    }

    /**
     * @param  array<string,array<string,mixed>>  $nodes   (by reference) id => data pop-up
     * @param  array<int,array<string,mixed>>  $access     (by reference) user id => akses
     * @param  array<int,bool>  $seen                      (by reference) pelindung siklus atasan
     * @return array<string,mixed>
     */
    private function node(User $user, int $depth, Collection $byManager, array &$nodes, array &$access, array &$seen): array
    {
        $seen[$user->id] = true;
        $id = 'u' . $user->id;
        $access[$user->id] = $this->accessFor($user);

        $children = [];
        foreach ($byManager->get($user->id, collect()) as $child) {
            if (! isset($seen[$child->id])) {
                $children[] = $this->node($child, $depth + 1, $byManager, $nodes, $access, $seen);
            }
        }

        $title = $user->job_title ?: $user->division;
        $people = [['name' => $user->name, 'freelance' => false, 'userId' => $user->id]];

        $nodes[$id] = ['title' => $title, 'zone' => null, 'people' => $people];

        return [
            'id' => $id,
            'depth' => $depth,
            'title' => $title,
            'people' => $people,
            'vacant' => false,
            'tone' => $user->isOwner() ? 'pink' : (self::TONES[$user->workTeam()] ?? 'white'),
            'accent' => $user->isOwner(),
            'zone' => null,
            'badge' => null,
            'dotted' => false,
            'layout' => 'row',
            'crew' => [],
            'rows' => $children === [] ? [] : [$children],
            'clickable' => true,
        ];
    }

    /**
     * Ringkasan akses satu akun untuk pop-up. Semua level dibaca dari method
     * User yang sama dipakai sidebar/middleware, bukan dihitung ulang.
     *
     * @return array<string,mixed>
     */
    private function accessFor(User $user): array
    {
        $modules = [];
        foreach (DashboardAccess::MODULES as $key => $meta) {
            $modules[] = [
                'key' => $key,
                'label' => $meta['label'],
                'desc' => $meta['desc'],
                'level' => $user->accessLevel($key),
            ];
        }

        $pages = [];
        if ($user->isOwnerOrDeveloper()) {
            $pages = self::OWNER_PAGES;
            $pages[] = $user->isOwner()
                ? 'Akses Dashboard — mengatur akses orang lain'
                : 'Tidak bisa mengubah akun Owner atau membuka Akses Dashboard';
        }

        if (! $user->canViewModule('people')) {
            $attendance = 'Hanya absensi sendiri (tidak punya akses Rekap Absensi).';
        } elseif ($user->isOwner() || $user->isHrd() || $user->isDeveloper()) {
            $attendance = 'Rekap Absensi seluruh karyawan.';
        } else {
            $subordinates = max(0, $user->visibleAttendanceUserIds()->count() - 1);
            $attendance = $subordinates > 0
                ? "Rekap Absensi dirinya sendiri + {$subordinates} bawahan."
                : 'Rekap Absensi hanya dirinya sendiri (belum punya bawahan).';
        }

        $calendar = $user->seesAllWork()
            ? 'Semua project'
            : 'Project untuk semua tim, tim ' . $user->workTeamLabel() . ', kelompok timnya, atau yang dia pimpin';

        return [
            'name' => $user->name,
            'role' => $user->roleLabel(),
            'roleKey' => $user->role,
            'fullAccess' => $user->isOwner(),
            'modules' => $modules,
            'pages' => $pages,
            'attendance' => $attendance,
            'calendar' => $calendar,
        ];
    }
}