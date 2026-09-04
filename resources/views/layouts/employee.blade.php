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
    <div class="min-h-screen pb-28">
        <div class="mx-auto max-w-140 px-4 pb-10 pt-5">
            <header class="mb-8 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-ink text-[10px] font-black text-white">
                        WSM
                    </div>
                    <span class="text-xs font-extrabold text-muted">{{ $title ?? 'WSM' }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid h-10 w-10 place-items-center rounded-2xl bg-[#ece7dd] text-xs font-black">
                        ⏻
                    </button>
                </form>
            </header>

            @yield('content')
        </div>

        <nav class="bottom-nav-wsm">
            <a href="{{ route('employee.home') }}"
                class="bottom-nav-wsm-item {{ ($navActive ?? '') === 'home' ? 'active' : '' }}">
                <span>⌂</span><em class="not-italic">Home</em>
            </a>
            <a href="#" class="bottom-nav-wsm-item">
                {{-- TODO Fase 4: riwayat absensi --}}
                <span>◷</span><em class="not-italic">Riwayat</em>
            </a>
            <a href="#" class="bottom-nav-wsm-item center">
                {{-- TODO Fase 4: tombol clock-in/out cepat --}}
                <span class="text-lg leading-none">+</span>
            </a>
            <a href="#" class="bottom-nav-wsm-item">
                {{-- TODO Fase 5 --}}
                <span>↗</span><em class="not-italic">Request</em>
            </a>
            <a href="#" class="bottom-nav-wsm-item">
                {{-- TODO Fase 1 --}}
                <span>◎</span><em class="not-italic">Profile</em>
            </a>
        </nav>
    </div>
</body>

</html>
