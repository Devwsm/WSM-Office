{{--
    layouts/app.blade.php
    ---------------------------------------------------------------------
    Layout untuk sisi Owner & staf yang di-assign dashboard_access.
    Gaya visual disamakan dengan prototype W.O.S 2.0 (absensi_wsm):
    palet cream/paper, brand mark hitam, nav pill aktif hitam.

    2026-09-09 — SEMUA link sidebar di sini (kecuali grup Owner-only)
    SEKARANG murni dashboard_access (User::canViewModule()), TERMASUK
    Absensi & Persetujuan yang dulu role-based (`isManajer()`/`isHrd()`)
    — bahkan link "Absensi" dulu KOSONG SAMA SEKALI tanpa gate apa pun,
    itu bug yang lagi dibenerin (lihat komentar inline di link-nya).
    "Role" (`users.role`) sekarang murni label jabatan, gak menentukan
    apa pun yang kelihatan di sidebar ini selain grup Owner (Owner
    memang konsep akun super-admin terpisah, bukan permission yang
    didelegasikan — accessLevel() hardcode 'manage' semua modul buat
    Owner). Lihat README "Dashboard permission-based, bukan role".
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Audit ronde 6 (2026-09-09) — dibutuhkan Work Tracker board buat
         fetch() PATCH drag-drop (dashboard/work/tracker/index.blade.php).
         SEBELUMNYA gak ada meta csrf-token sama sekali di layout ini
         (semua interaksi lain pola form POST biasa + @csrf, gak butuh
         token lewat JS) — board ini pertama yang butuh AJAX beneran. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — WSM Office System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">
    @include('partials.flash-data')

    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex h-screen w-64 -translate-x-full flex-col border-r border-line bg-paper p-4 transition-transform duration-200 ease-in-out lg:sticky lg:top-0 lg:translate-x-0">
            <div class="flex items-center gap-3 px-1 pb-6 pt-1">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-ink text-[11px] font-black text-white">
                    WSM
                </div>
                <div class="leading-tight">
                    <strong class="block text-sm">WSM Office</strong>
                    <span class="text-[11px] text-muted">Whisnu Santika Music</span>
                </div>
            </div>

            {{--
                2026-09-09 — nav ini SEKARANG bisa jadi panjang (Owner
                4 link + Rekrutmen 2 + Absensi/Persetujuan + daftar
                modul dashboard_access yang di-assign, bisa sampai 10).
                `<aside>` tingginya fixed (`h-screen`), jadi tanpa
                `flex-1 overflow-y-auto` di sini, sidebar kepanjangan
                bakal ke-cut / footer (Kunci Dashboard, Keluar) ke-dorong
                keluar layar / gak bisa di-scroll sama sekali. `min-h-0`
                WAJIB ada bareng `flex-1` — tanpa itu flexbox gak mau
                nyusutin nav ini di bawah tinggi kontennya sendiri
                (gotcha klasik flexbox), jadi overflow-y-auto gak akan
                efektif walau udah dipasang.

                2026-09-10 — BUG (dilaporkan Arga, kelihatan di akun
                akses minim kayak karyawan yang cuma punya 1 modul):
                `grid` defaultnya `align-content: stretch` — sisa
                ruang kosong di sidebar (karena isinya dikit) dibagi
                rata ke SEMUA baris grid, bukan numpuk di bawah kayak
                block layout biasa. Item non-aktif ikut melar juga
                tapi gak keliatan (background transparan) — yang
                keliatan cuma link yang lagi *active* (`bg-ink`),
                jadinya kotak hitam raksasa nutupin separuh sidebar.
                `content-start` maksa baris grid tetap seukuran
                konten & sisa ruang numpuk di bawah, `items-start`
                jaga-jaga item individual juga gak ikut melar. --}}
            <nav class="grid content-start items-start flex-1 min-h-0 gap-1.5 overflow-y-auto text-sm">
                {{-- 2026-09-09 — dulu di-@if (isManajer/isHrd/isOwner), padahal
                     SIAPA PUN yang bisa nyampe layouts.app ini juga otomatis
                     anggota grup route 'employee.*' (base /app), jadi link ini
                     selalu valid buat siapa pun yang lihatnya — @if-nya dicabut,
                     bukan diganti permission check. --}}
                <a href="{{ route('employee.home') }}"
                    class="rounded-2xl px-3.5 py-3 font-extrabold text-[#5e5951] hover:bg-white">
                    ← App Saya
                </a>
                @if (auth()->user()->isOwner())
                    <a href="{{ route('owner.dashboard') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'dashboard' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('owner.employees.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'employees' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Karyawan
                    </a>
                    <a href="{{ route('owner.organization') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'organization' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Struktur Organisasi
                    </a>
                    <a href="{{ route('owner.office-settings.edit') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'office-settings' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Pengaturan Kantor
                    </a>
                @endif
                {{-- 2026-09-09 — dulu `isHrd() || isOwner()`. SEKARANG
                     `canViewModule('recruitment')` (routes/web.php grup
                     'recruitment.*' juga udah pindah ke module:recruitment). --}}
                @if (auth()->user()->canViewModule('recruitment'))
                    <a href="{{ route('recruitment.applications.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'applications' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Pelamar
                    </a>
                    <a href="{{ route('recruitment.openings.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'openings' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Lowongan
                    </a>
                @endif
                {{--
                    2026-09-09 — INI BUG YANG DILAPORKAN: link "Absensi" di
                    bawah ini SEBELUMNYA SAMA SEKALI TANPA @if (nol gate),
                    nempel gitu aja di luar kondisi manapun. Efeknya: SIAPA
                    PUN yang somehow nyampe layouts.app (misal Aldora — role
                    'karyawan' biasa — lewat modul 'work' yang dia PUNYA
                    akses beneran) otomatis lihat link ke Rekap Absensi juga,
                    padahal nggak ada dashboard_access ke situ sama sekali.
                    Sekarang digerbang `canViewModule('people')` — sama modul
                    yang gerbangin route attendance.recap.* (routes/web.php).
                --}}
                @if (auth()->user()->canViewModule('people'))
                    <a href="{{ route('attendance.recap.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'attendance' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Absensi
                    </a>
                    {{-- 2026-09-09 — dulu `isManajer() || isOwner()` (HRD
                         sengaja dikecualikan, kesepakatan Fase 5, TETAP
                         berlaku — lihat routes/web.php grup
                         'approval.leave.'). SEKARANG sama-sama gerbang
                         `canViewModule('people')` kayak Absensi di atas
                         (approve/reject beneran tetap dicek manager_id di
                         controller, link ini cuma nampilin/nyembunyiin
                         entry point-nya). --}}
                    <a href="{{ route('approval.leave.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'approval' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        Persetujuan
                    </a>
                @endif

                @php $accessibleModules = collect(\App\Models\DashboardAccess::MODULES)->keys()->filter(fn($m) => auth()->user()->canViewModule($m)); @endphp
                @if ($accessibleModules->isNotEmpty())
                    <p class="mt-3 px-3.5 text-[10px] font-extrabold uppercase tracking-wide text-muted">Modul</p>
                    @foreach ($accessibleModules as $moduleKey)
                        @php
                            $moduleActive =
                                $moduleKey === 'work'
                                    ? request()->routeIs('dashboard.work.*')
                                    : request()->routeIs('dashboard.show') && request()->route('module') === $moduleKey;
                            // Fase 8: badge unread cuma di "Work Control" (Memo
                            // Forum ada di situ) & cuma buat yang manage-level
                            // (padanan badge ceo-response-badge di prototype,
                            // yang cuma muncul buat Owner/CEO).
                            $unreadBadge =
                                $moduleKey === 'work' && auth()->user()->canManageModule('work')
                                    ? \App\Models\MemoThreadMessage::unreadForManagementCount()
                                    : 0;
                        @endphp
                        <a href="{{ $moduleKey === 'work' ? route('dashboard.work.index') : route('dashboard.show', $moduleKey) }}"
                            class="flex items-center justify-between gap-2 rounded-2xl px-3.5 py-3 font-extrabold {{ $moduleActive ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span>
                                <span
                                    class="mr-1.5 inline-block w-4 text-center">{{ \App\Models\DashboardAccess::MODULES[$moduleKey]['icon'] }}</span>
                                {{ \App\Models\DashboardAccess::MODULES[$moduleKey]['label'] }}
                            </span>
                            @if ($unreadBadge > 0)
                                <span
                                    class="grid h-5 min-w-5 flex-none place-items-center rounded-full bg-[#a83d35] px-1 text-[10px] font-black text-white">
                                    {{ $unreadBadge }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                @endif
            </nav>

            <div class="mt-auto border-t border-line pt-3.5">
                {{-- "Kunci Dashboard" — padanan tombol merah footer sidebar
                     Owner di prototype (lockOwner()), diadaptasi ke password
                     akun sendiri. Cuma role yang beneran masuk layouts.app
                     yang lihat tombol ini (samain sama grup role rute
                     dashboard.lock.*). --}}
                <form method="POST" action="{{ route('dashboard.lock.lock') }}" class="mb-2.5"
                    data-confirm="Kamu perlu masukin password buat buka lagi." data-confirm-title="Kunci dashboard?"
                    data-confirm-button="Ya, kunci">
                    @csrf
                    <button
                        class="w-full rounded-2xl border border-[#f1c7c2] bg-[#fff0ee] py-2.5 text-xs font-extrabold text-[#a83d35]">
                        🔒 Kunci Dashboard
                    </button>
                </form>
                <div class="flex items-center gap-2.5 px-1 pb-3">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-[#ece7dd] text-xs font-black">
                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0 leading-tight">
                        <strong class="block truncate text-xs">{{ auth()->user()->name }}</strong>
                        <span class="text-[10px] capitalize text-muted">{{ auth()->user()->role }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" data-confirm="Kamu akan keluar dari akun ini."
                    data-confirm-title="Keluar akun?" data-confirm-button="Ya, keluar">
                    @csrf
                    <button class="btn-wsm-white w-full py-2.5! text-xs">Keluar</button>
                </form>
            </div>
        </aside>

        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/40 lg:hidden">
        </div>

        <div class="flex min-w-0 flex-col">
            <header class="flex items-center justify-between gap-4 px-4 py-4 lg:hidden">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="grid h-10 w-10 place-items-center rounded-2xl bg-[#ece7dd]">
                    ☰
                </button>
                <h1 class="text-sm font-extrabold">{{ $title ?? 'Dashboard' }}</h1>
                <div class="w-10"></div>
            </header>

            <main class="flex-1 px-4 pb-16 pt-2 lg:px-8 lg:pb-20 lg:pt-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
