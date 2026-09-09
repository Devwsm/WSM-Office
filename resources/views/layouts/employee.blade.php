{{--
    layouts/employee.blade.php
    ---------------------------------------------------------------------
    Layout Karyawan & Manajer — gaya "mobile app" dengan bottom nav,
    mengikuti visual prototype W.O.S 2.0 (absensi_wsm): hero besar,
    kartu paper/white radius besar, bottom nav pill melayang.
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'WSM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">
    @include('partials.flash-data')

    <div class="min-h-screen pb-28">
        <div class="mx-auto max-w-140 px-4 pb-10 pt-5">
            <header class="mb-8 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-ink text-[10px] font-black text-white">
                        WSM
                    </div>
                    <span class="text-xs font-extrabold text-muted">{{ $title ?? 'WSM' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    {{--
                        2026-09-09 — refactor "permission bukan role" (lihat
                        README). SEBELUMNYA `isManajer() || isOwner() ||
                        isHrd()` — role apa pun di 3 itu otomatis lihat
                        tombol ini & bisa masuk Rekap Absensi/Persetujuan,
                        gak peduli beneran ditugasin ngurus tim atau
                        enggak. SEKARANG `canViewModule('people')` — sama
                        modul yang gerbangin route attendance.recap.* &
                        approval.leave./overtime.* (lihat routes/web.php).
                        Owner otomatis lolos (accessLevel() hardcode
                        'manage' semua modul), gak perlu di-assign manual.
                    --}}
                    @if (auth()->user()->canViewModule('people'))
                        {{-- Entry point tunggal ke dashboard (pola prototype: 1 tombol
                             di halaman/header, bukan tab terpisah di bottom-nav). Landing
                             di attendance.recap.index karena itu satu-satunya route yang
                             dibolehkan buat modul 'people' — dari situ sidebar
                             layouts.app nampilin link lain (Persetujuan, Pelamar, dst)
                             sesuai modul yang di-assign ke user ini. --}}
                        <a href="{{ route('attendance.recap.index') }}"
                            class="grid h-10 place-items-center rounded-2xl bg-ink px-3.5 text-[10px] font-extrabold text-white">
                            Kelola Tim
                        </a>
                    @endif
                    @if (auth()->user()->hasAnyDashboardAccess())
                        {{-- Terpisah dari "Kelola Tim" di atas: dua-duanya SEKARANG
                             sama-sama dashboard_access (Fase 6a/2026-09-09), tapi
                             beda modul — "Kelola Tim" khusus modul 'people'
                             (attendance.recap.*), tombol ini nyala kalau punya
                             akses ke modul APA PUN (termasuk 'work', 'budget',
                             dst — lihat DashboardAccess::MODULES). Bisa aja
                             Karyawan biasa lihat tombol ini doang tanpa
                             tanpa "Kelola Tim", atau sebaliknya. --}}
                        <a href="{{ route('dashboard.index') }}"
                            class="grid h-10 place-items-center rounded-2xl border border-line bg-white px-3.5 text-[10px] font-extrabold text-ink">
                            Dashboard
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" data-confirm="Kamu akan keluar dari akun ini."
                        data-confirm-title="Keluar akun?" data-confirm-button="Ya, keluar">
                        @csrf
                        <button class="grid h-10 w-10 place-items-center rounded-2xl bg-[#ece7dd] text-xs font-black">
                            ⏻
                        </button>
                    </form>
                    {{-- Avatar user (posisi paling kanan header, mengikuti pola
                         prototype) — link ke tab Profile. Sengaja cuma inisial
                         nama, belum ada foto profil karyawan sama sekali di
                         sistem ini. --}}
                    <a href="{{ route('employee.profile.index') }}"
                        class="grid h-10 w-10 flex-none place-items-center rounded-2xl bg-ink text-xs font-black text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </a>
                </div>
            </header>

            @yield('content')
        </div>

        <nav class="bottom-nav-wsm">
            <a href="{{ route('employee.home') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'home' ? 'active' : '' }}">
                <span>⌂</span><em class="not-italic">Home</em>
            </a>
            <a href="{{ route('employee.attendance.history') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'riwayat' ? 'active' : '' }}">
                <span>◷</span><em class="not-italic">Riwayat</em>
            </a>
            <a href="{{ route('employee.home') }}#attendance-card" class="bottom-nav-wsm-item center">
                <span class="text-lg leading-none">+</span>
            </a>
            <a href="{{ route('employee.leave.index') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'pengajuan' ? 'active' : '' }}">
                <span>↗</span><em class="not-italic">Request</em>
            </a>
            <a href="{{ route('employee.profile.index') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'profile' ? 'active' : '' }}">
                <span>◎</span><em class="not-italic">Profile</em>
            </a>
        </nav>
    </div>
</body>

</html>
