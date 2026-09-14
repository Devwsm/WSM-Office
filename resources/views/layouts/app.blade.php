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
    {{-- Fase 16 — warna aksen dari Pengaturan Kantor, diinject sebagai
         CSS custom property biar dipakein tanpa perlu fetch OfficeSetting
         di tiap view yang butuh (nav Owner-only di sidebar ini +
         tab bar Work Control di dashboard/work/*). Kolomnya udah ada
         dari Fase 7 tapi baru sekarang dipakein. --}}
    @php $accentSetting = \App\Models\OfficeSetting::current(); @endphp
    <style>
        :root {
            --ceo-accent: {{ $accentSetting->ceo_accent_color }};
            --work-accent: {{ $accentSetting->work_accent_color }};
        }
    </style>
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
                @php
                    /** @var \App\Models\User $u */
                    $u = auth()->user();
                @endphp
                @if ($u->isOwner())
                    <a href="{{ route('owner.dashboard') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'dashboard' ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--ceo-accent)' => ($navActive ?? '') === 'dashboard'])>
                        Dashboard
                    </a>
                    {{-- Fase 1 (susulan, 2026-09-13) — badge angka jumlah pesan
                         status 'baru', numpang query ringan langsung di sini
                         (sama pola sederhana kayak nav lain, belum ada shared
                         view-composer khusus buat ini). --}}
                    <a href="{{ route('owner.contact-messages.index') }}"
                        class="flex items-center justify-between rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'contact-messages' ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--ceo-accent)' => ($navActive ?? '') === 'contact-messages'])>
                        <span>Pesan Kontak</span>
                        @php $unreadContactCount = \App\Models\ContactMessage::unreadCount(); @endphp
                        @if ($unreadContactCount > 0)
                            <span
                                class="ml-2 rounded-full bg-[#e0483e] px-2 py-0.5 text-[10px] font-black text-white">{{ $unreadContactCount }}</span>
                        @endif
                    </a>
                @endif

                {{--
                    2026-09-14 — Sidebar dirombak biar SAMA kayak prototype
                    captur-ui-wos: bukan lagi daftar flat "1 pill per modul
                    dashboard_access", tapi dikelompokin jadi section
                    berwarna bernomor (PEOPLE, WORK CONTROL, FINANCE,
                    ROYALTY, HR ADMIN, LEGAL, IT) yang beberapa di antaranya
                    GABUNGIN lintas beberapa modul jadi 1 grup visual — sama
                    persis pola prototype-nya (mis. "HR ADMIN" isinya modul
                    'people' + 'kpi' + 'contracts' + 'payroll' + halaman
                    Owner-only "Karyawan" sekaligus).

                    Nomor section ("1 · ", "2 · ", dst) DIHITUNG ULANG per
                    user (bukan angka mati kayak prototype) — soalnya siapa
                    yang lihat section mana beda-beda tergantung
                    dashboard_access, jadi kalau di-hardcode "5 · HR ADMIN"
                    buat user yang cuma punya akses HR Admin doang, bakal
                    aneh mulai dari angka 5. $sectionNo jalan cuma pas
                    section-nya beneran dirender.

                    Item "Projects" (prototype, section Work Control) SENGAJA
                    gak ada linknya sendiri — di app ini "Projects" udah
                    dilebur jadi bagian dari Work Tracker board (Fase 9),
                    bukan halaman terpisah. "Executive People Overview"
                    (prototype, section People) SENGAJA di-skip dulu — gak
                    ada padanan halaman yang jelas di app ini. Badge
                    "LIMITED" & "v21" (versi statis di prototype) juga
                    SENGAJA belum diporting — belum ada keputusan gimana itu
                    harus dipetakan ke sistem akses view/manage yang
                    sekarang, jangan asal comot.

                    Section "RECRUITMENT" TIDAK ADA di prototype asli (fitur
                    baru di app ini, lihat catatan DashboardAccess::MODULES)
                    — ditambahin sebagai section ke-8 di urutan paling
                    bawah atas permintaan Arga (2026-09-14), gak ada padanan
                    posisi di prototype buat dijadiin acuan taruh di mana.
                --}}
                @php $sectionNo = 0; @endphp

                {{-- 1 · PEOPLE — cuma "Organization" (Owner-only, padanan
                     Struktur Organisasi). "Executive People Overview"
                     di-skip, lihat catatan di atas. --}}
                @if ($u->isOwner())
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#dff3ee">{{ $sectionNo }} · PEOPLE</p>
                    <a href="{{ route('owner.organization') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'organization' ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--ceo-accent)' => ($navActive ?? '') === 'organization'])>
                        <span class="mr-1.5 inline-block w-4 text-center">⌘</span>Organization
                    </a>
                @endif

                {{-- 2 · WORK CONTROL — Work Tracker (nyakup Projects),
                     Timeline Calendar (kalender-tim, sisi employee.*, gak
                     digerbang module:work tapi ditaruh di grup ini biar
                     nyambung visual sama prototype), MoM/Meeting, Memo
                     Forum. --}}
                @if ($u->canViewModule('work'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#e2e9ff">{{ $sectionNo }} · WORK CONTROL</p>
                    <a href="{{ route('dashboard.work.tracker.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.work.tracker.*') ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--work-accent)' => request()->routeIs('dashboard.work.tracker.*')])>
                        <span class="mr-1.5 inline-block w-4 text-center">☷</span>Work Tracker
                    </a>
                    <a href="{{ route('employee.workTracker.calendar') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('employee.workTracker.calendar') ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--work-accent)' => request()->routeIs('employee.workTracker.calendar')])>
                        <span class="mr-1.5 inline-block w-4 text-center">▦</span>Timeline Calendar
                    </a>
                    <a href="{{ route('dashboard.work.meetings.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.work.meetings.*') ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--work-accent)' => request()->routeIs('dashboard.work.meetings.*')])>
                        <span class="mr-1.5 inline-block w-4 text-center">≡</span>Rapat &amp; Action Item
                    </a>
                    <a href="{{ route('dashboard.work.index') }}"
                        class="flex items-center justify-between rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs(['dashboard.work.index', 'dashboard.work.create', 'dashboard.work.edit']) ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                        @style(['background-color: var(--work-accent)' => request()->routeIs(['dashboard.work.index', 'dashboard.work.create', 'dashboard.work.edit'])])>
                        <span><span class="mr-1.5 inline-block w-4 text-center">✦</span>Memo Forum</span>
                        @php $workUnreadBadge = $u->canManageModule('work') ? \App\Models\MemoThreadMessage::unreadForManagementCount() : 0; @endphp
                        @if ($workUnreadBadge > 0)
                            <span
                                class="ml-2 rounded-full bg-[#a83d35] px-2 py-0.5 text-[10px] font-black text-white">{{ $workUnreadBadge }}</span>
                        @endif
                    </a>
                @endif

                {{-- 3 · FINANCE — cuma modul 'budget' (Project Budgeting). --}}
                @if ($u->canViewModule('budget'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#fff0ad">{{ $sectionNo }} · FINANCE</p>
                    <a href="{{ route('dashboard.budget.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.budget.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">▦</span>Project Budgeting
                    </a>
                @endif

                {{-- 4 · ROYALTY — cuma modul 'royalty'. --}}
                @if ($u->canViewModule('royalty'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#eee3ff">{{ $sectionNo }} · ROYALTY</p>
                    <a href="{{ route('dashboard.royalty.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.royalty.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">♪</span>Royalty Dashboard
                    </a>
                @endif

                {{-- 5 · HR ADMIN — gabungan lintas modul: 'people'
                     (Attendance/Requests), 'kpi', Owner-only "Karyawan &
                     Access", 'contracts', 'payroll'. Header-nya nongol
                     kalau MINIMAL SATU item di bawah kelihatan. --}}
                @php
                    $hrAdminVisible = $u->canViewModule('people') || $u->canViewModule('kpi') || $u->isOwner() || $u->canViewModule('contracts') || $u->canViewModule('payroll');
                @endphp
                @if ($hrAdminVisible)
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#e4f4dc">{{ $sectionNo }} · HR ADMIN</p>
                    @if ($u->canViewModule('people'))
                        <a href="{{ route('attendance.recap.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'attendance' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span class="mr-1.5 inline-block w-4 text-center">⏱</span>Attendance
                        </a>
                        <a href="{{ route('approval.leave.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'approval' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span class="mr-1.5 inline-block w-4 text-center">↗</span>Requests
                        </a>
                    @endif
                    @if ($u->canViewModule('kpi'))
                        <a href="{{ route('dashboard.kpi.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.kpi.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span class="mr-1.5 inline-block w-4 text-center">◎</span>KPI &amp; Performance
                        </a>
                    @endif
                    @if ($u->isOwner())
                        <a href="{{ route('owner.employees.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'employees' ? 'text-white' : 'text-[#5e5951] hover:bg-white' }}"
                            @style(['background-color: var(--ceo-accent)' => ($navActive ?? '') === 'employees'])>
                            <span class="mr-1.5 inline-block w-4 text-center">◉</span>Karyawan &amp; Access
                        </a>
                    @endif
                    @if ($u->canViewModule('contracts'))
                        <a href="{{ route('dashboard.contracts.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.contracts.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span class="mr-1.5 inline-block w-4 text-center">▤</span>Employee Contracts
                        </a>
                    @endif
                    @if ($u->canViewModule('payroll'))
                        <a href="{{ route('dashboard.payroll.index') }}"
                            class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.payroll.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                            <span class="mr-1.5 inline-block w-4 text-center">$</span>Payroll
                        </a>
                    @endif
                @endif

                {{-- Pill hitam "HR / Geo / Color Settings" — padanan
                     Pengaturan Kantor (Owner-only), ditaruh persis kayak
                     posisinya di prototype: antara HR ADMIN & LEGAL. --}}
                @if ($u->isOwner())
                    <a href="{{ route('owner.office-settings.edit') }}"
                        class="mt-1 rounded-2xl bg-ink px-3.5 py-3 text-center font-extrabold text-white hover:bg-black">
                        ⚙ HR / Geo / Color Settings
                    </a>
                @endif

                {{-- 6 · LEGAL — 1 controller/1 route, 2 kategori dibedain
                     query string `category` (lihat catatan LegalController),
                     TAPI prototype nampilin 2 baris terpisah — disamain di
                     sini lewat 2 link ke route yang sama. --}}
                @if ($u->canViewModule('legal'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#ffe7d1">{{ $sectionNo }} · LEGAL</p>
                    <a href="{{ route('dashboard.legal.index', ['category' => 'album']) }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.legal.*') && request('category', 'album') === 'album' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">♪</span>Song / Album Contracts
                    </a>
                    <a href="{{ route('dashboard.legal.index', ['category' => 'royalty']) }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.legal.*') && request('category') === 'royalty' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">▤</span>Royalty Agreements
                    </a>
                @endif

                {{-- 7 · IT — Audit Log (read-only) + System Changelog. --}}
                @if ($u->canViewModule('it'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#ece9e3">{{ $sectionNo }} · IT</p>
                    <a href="{{ route('dashboard.it.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.it.index') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">▤</span>Audit Logs
                    </a>
                    <a href="{{ route('dashboard.it.changelog.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ request()->routeIs('dashboard.it.changelog.*') ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">⟳</span>System Change Log
                    </a>
                @endif

                {{-- 8 · RECRUITMENT — GAK ADA di prototype asli, ditambahin
                     atas permintaan Arga (2026-09-14). Pelamar & Lowongan
                     udah lama punya route/controller sendiri, cuma
                     sekarang dikasih section header berwarna juga biar
                     konsisten visual sama section lain. --}}
                @if ($u->canViewModule('recruitment'))
                    @php $sectionNo++; @endphp
                    <p class="mt-3 rounded-2xl px-3.5 py-2.5 text-[11px] font-extrabold text-ink"
                        style="background-color:#dceefb">{{ $sectionNo }} · RECRUITMENT</p>
                    <a href="{{ route('recruitment.applications.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'applications' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">✎</span>Pelamar
                    </a>
                    <a href="{{ route('recruitment.openings.index') }}"
                        class="rounded-2xl px-3.5 py-3 font-extrabold {{ ($navActive ?? '') === 'openings' ? 'bg-ink text-white' : 'text-[#5e5951] hover:bg-white' }}">
                        <span class="mr-1.5 inline-block w-4 text-center">✎</span>Lowongan
                    </a>
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
