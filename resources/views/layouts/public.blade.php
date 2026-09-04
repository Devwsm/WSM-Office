{{--
    layouts/public.blade.php
    ---------------------------------------------------------------------
    Layout untuk halaman publik (Fase 1): Beranda, Tentang Kami, Layanan,
    Karir, Kontak — semua tanpa login. Header atas dibikin minimal
    (cuma brand mark), navigasi utamanya ada di floating pill nav yang
    nempel di tengah-bawah layar (mirip pola bottom-nav internal tool,
    tapi versi horizontal untuk marketing site).
    ---------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'WSM Office System' }} — Whisnu Santika Music</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream text-ink antialiased">
    @php
        $navItems = [
            ['route' => 'public.home', 'label' => 'Beranda'],
            ['route' => 'public.about', 'label' => 'Tentang Kami'],
            ['route' => 'public.services', 'label' => 'Layanan'],
            ['route' => 'public.careers', 'label' => 'Karir'],
            ['route' => 'public.contact', 'label' => 'Kontak'],
        ];
    @endphp

    <header class="sticky top-0 z-20 px-4 py-4 sm:px-6">
        <a href="{{ route('public.home') }}" class="inline-flex items-center gap-2.5">
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-ink text-[10px] font-black text-white">
                WSM
            </div>
            <span class="hidden text-sm font-black tracking-tight sm:inline">Whisnu Santika Music</span>
        </a>
    </header>

    <main class="pb-32">
        @yield('content')
    </main>

    <footer class="border-t border-line px-4 pb-28 pt-10 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5">
                <div class="grid h-9 w-9 place-items-center rounded-xl bg-ink text-[9px] font-black text-white">
                    WSM
                </div>
                <span class="text-xs font-extrabold text-muted">
                    © {{ now()->year }} Whisnu Santika Music. Semua hak dilindungi.
                </span>
            </div>
            <nav class="flex flex-wrap gap-4 text-xs font-extrabold text-muted">
                <a href="{{ route('public.about') }}" class="hover:text-ink">Tentang Kami</a>
                <a href="{{ route('public.careers') }}" class="hover:text-ink">Karir</a>
                <a href="{{ route('public.contact') }}" class="hover:text-ink">Kontak</a>
            </nav>
        </div>
    </footer>

    <nav class="floating-nav-wsm">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}"
                class="floating-nav-wsm-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
        <span class="floating-nav-wsm-divider"></span>
        <a href="{{ route('login') }}" class="floating-nav-wsm-cta">Masuk</a>
    </nav>
</body>

</html>
